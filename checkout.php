<?php 
require_once 'includes/config.php';

// Rule 1 & 2: Only registered users can access checkout
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?msg=Please login to checkout');
    exit();
}

// Note: Proceed even if database connection fails - we have fallback data in $global_products
// Database will be attempted for orders, but form display will work with fallback data

// Rule 3: Admin cannot checkout
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    header('Location: admin_dashboard.php');
    exit();
}

// Check if user is logged in and exists in database
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verify user exists to prevent foreign key constraint issues (if DB was reset)
if ($pdo !== null) {
    $check_user = $pdo->prepare("SELECT user_id FROM User WHERE user_id = ?");
    $check_user->execute([$_SESSION['user_id']]);
    if (!$check_user->fetch()) {
        session_destroy();
        header('Location: login.php?error=session_expired');
        exit();
    }
}

// Check if there are items to checkout
if (empty($_SESSION['checkout_items']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$selected_ids = $_SESSION['checkout_items'];
$order_type = $_SESSION['order_type'] ?? 'standard';  // Get order_type from session
$checkout_items = [];
$subtotal_amount = 0;
$has_preorder = false;

foreach ($selected_ids as $id) {
    if (isset($_SESSION['cart'][$id])) {
        $item = $_SESSION['cart'][$id];
        $checkout_items[$id] = $item;
        $subtotal_amount += $item['price'] * $item['quantity'];
        if ($item['is_preorder']) $has_preorder = true;
    }
}

// ========== CALCULATE TOTALS BASED ON ORDER TYPE ==========
// Pre-order always includes ₱500 in the order total
$order_total = $subtotal_amount;
$service_fee = 0;

if ($order_type === 'preorder') {
    // Pre-order: Add ₱500 to order total
    $order_total = $subtotal_amount + 500;
}
// Standard: No fee added yet (will be added based on payment method in checkout)

if (empty($checkout_items)) {
    header('Location: cart.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Check if database connection is available before processing order
    if ($pdo === null) {
        die("⚠️ Database connection unavailable. Please try again later or contact support.");
    }
    
    try {
        $pdo->beginTransaction();

        $user_id = $_SESSION['user_id'];
        $order_number = 'RC-' . strtoupper(bin2hex(random_bytes(4)));
        $payment_method = $_POST['payment_method'];  // 'COP' or 'Online'
        $ref_number = $_POST['ref_number'] ?? '';
        $proof_image_path = null;

        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['proof_image'];
            $file_size = $file['size'];
            $file_type = $file['type'];
            $file_name = $file['name'];

            if ($file_size > 3 * 1024 * 1024) {
                throw new Exception('File size must not exceed 3MB');
            }
            if (!in_array($file_type, ['image/png', 'image/jpeg'])) {
                throw new Exception('File must be PNG or JPG');
            }

            $upload_dir = __DIR__ . '/uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_name = 'payment_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $file_ext;
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $proof_image_path = 'uploads/payments/' . $unique_name;
            } else {
                throw new Exception('Failed to upload payment proof image');
            }
        }
        
        // ========== CALCULATE FINAL AMOUNTS FOR DATABASE ==========
        
        // For Orders table: store the full order total
        $orders_total = $order_total;
        
        // For Payment table: calculate what user actually pays upfront
        $amount_to_pay = 0;
        
        if ($payment_method === 'Online') {
            // GCash/PayMaya: User pays full amount
            if ($order_type === 'preorder') {
                // Pre-order with online: Pay full including the 500
                $amount_to_pay = $order_total;  // subtotal + 500
            } else {
                // Standard with online: Pay full without fee
                $amount_to_pay = $subtotal_amount;
            }
        } else {
            // Cash on Pick-up: User pays only downpayment upfront
            if ($order_type === 'preorder') {
                // Pre-order with COP: Pay only 500 downpayment
                $amount_to_pay = 500;
            } else {
                // Standard with COP: Pay 500 service fee
                $amount_to_pay = 500;
            }
        }
        
        $order_record_type = ($order_type === 'preorder') ? 'preorder' : 'standard';
        $reservation_expiry = ($order_type === 'preorder') ? date('Y-m-d H:i:s', strtotime('+7 days')) : null;
        $discount_amount = ($payment_method === 'Online') ? 0 : $amount_to_pay;
        
        // 1. Insert into Orders
        $stmt = $pdo->prepare("INSERT INTO Orders (user_id, order_number, total_amount, discount_amount, status, order_type, reservation_expiry) 
                       VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$user_id, $order_number, $orders_total, $discount_amount, $order_record_type, $reservation_expiry]);
        $order_id = $pdo->lastInsertId();

        // 2. Insert Order Line Items and Deduct Inventory
        $stmt_item = $pdo->prepare("INSERT INTO Order_Line_Item (order_id, variant_id, quantity, unit_price, line_total) 
                                    VALUES (?, ?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE Inventory SET quantity_on_hand = quantity_on_hand - ? 
                                     WHERE variant_id = ? AND quantity_on_hand >= ?");

        foreach ($checkout_items as $id => $item) {
            $variant_id = $item['variant_id'] ?? $item['product_id'] ?? 0;
            $product_id = $item['product_id'] ?? $id;
            $line_total = $item['price'] * $item['quantity'];
            $quantity = $item['quantity'];
            
            // Insert line item
            $stmt_item->execute([$order_id, $variant_id, $quantity, $item['price'], $line_total]);
            
            // Deduct stock from Inventory table
            // First check if variant exists
            $stmt_v_check = $pdo->prepare("SELECT variant_id, quantity_on_hand FROM Inventory WHERE variant_id = ?");
            $stmt_v_check->execute([$variant_id]);
            $inventory_record = $stmt_v_check->fetch();
            
            if ($inventory_record) {
                // Variant exists - deduct stock
                $current_stock = (int)$inventory_record['quantity_on_hand'];
                if ($current_stock >= $quantity) {
                    // Sufficient stock - deduct it
                    $stmt_stock->execute([$quantity, $variant_id, $quantity]);
                    error_log("✓ Stock deducted: Product ID $product_id (Variant $variant_id) - Quantity: $quantity. Remaining: " . ($current_stock - $quantity));
                } else {
                    // Insufficient stock but allow order (backorder scenario)
                    error_log("⚠ WARNING: Insufficient stock for Product ID $product_id (Variant $variant_id). Requested: $quantity, Available: $current_stock. Order allowed as backorder.");
                    $stmt_stock->execute([$quantity, $variant_id, $quantity]);
                }
            } else {
                // Variant doesn't exist in Inventory - create it with initial stock from global_products
                $initial_stock = getProductStock($product_id, 10); // Use helper to get initial stock
                try {
                    $stmt_v_insert = $pdo->prepare("INSERT INTO Inventory (variant_id, quantity_on_hand, reserved_quantity, reorder_level) VALUES (?, ?, 0, 5)");
                    $stmt_v_insert->execute([$variant_id, $initial_stock - $quantity]);
                    error_log("ℹ INFO: Created inventory record for Variant $variant_id (Product ID $product_id) with initial stock $initial_stock. Deducted $quantity.");
                } catch (Exception $e) {
                    error_log("Error creating inventory record: " . $e->getMessage());
                }
            }
        }

        // 3. Insert Payment
        $stmt_pay = $pdo->prepare("INSERT INTO Payment (order_id, method_id, amount_paid, reference_number, proof_image_path, payment_status) 
                                   VALUES (?, ?, ?, ?, ?, 'pending')");
        // Check if Payment_method table has entries, if not, we need to handle it
        $stmt_check_method = $pdo->prepare("SELECT method_id FROM Payment_method WHERE method_name LIKE ? LIMIT 1");
        
        $method_name_search = ($payment_method === 'Online') ? '%GCash%' : '%Pickup%';
        $stmt_check_method->execute([$method_name_search]);
        $method = $stmt_check_method->fetch();
        
        if (!$method) {
            // Fallback to ID 1 or 3 based on method type if name match fails
            $method_id = ($payment_method === 'Online') ? 1 : 3;
        } else {
            $method_id = $method['method_id'];
        }
        
        $stmt_pay->execute([$order_id, $method_id, $amount_to_pay, $ref_number, $proof_image_path]);

        $pdo->commit();

        // Remove checked out items from cart
        foreach ($selected_ids as $id) {
            unset($_SESSION['cart'][$id]);
        }
        unset($_SESSION['checkout_items']);
        unset($_SESSION['order_type']);
        $_SESSION['last_order_number'] = $order_number;
        
        header('Location: order_success.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Order Failed: " . $e->getMessage());
    }
}

include 'includes/header.php'; 
?>

<section class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-green-50 rounded-2xl border-l-4 border-primary">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-info-circle text-primary text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-900 text-sm">Order Summary Below</p>
                    <p class="text-gray-600 text-xs mt-1">Please review all items in your cart before confirming your order. All prices are final.</p>
                </div>
            </div>
        </div>

        <h1 class="text-4xl font-bold mb-10 text-gray-800">Secure <span class="text-primary">Checkout</span></h1>

        <form action="checkout.php" method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row gap-12" id="checkout-form">
            <!-- Pick-up and Payment Details -->
            <div class="lg:w-2/3 space-y-12">
                <!-- Store Pick-up Section -->
                <div class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-bold mb-8 flex items-center text-gray-800 border-l-4 border-primary pl-4">
                        Pick-up Information
                    </h2>
                    <p class="text-gray-500 mb-6 italic"><i class="fas fa-info-circle mr-2"></i> All orders are for <strong>Store Pick-up only</strong> at our main branch.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-gray-700 font-bold mb-3" for="full_name">Full Name of Receiver</label>
                            <input type="text" id="full_name" name="receiver_name" required value="<?php echo $_SESSION['first_name'] ?? ''; ?>" class="w-full px-6 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-3" for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="receiver_phone" required placeholder="09XXXXXXXXX" class="w-full px-6 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-bold mb-3">Store Location</label>
                            <div class="p-4 bg-green-50 rounded-xl border border-green-100 text-dark font-medium">
                                <i class="fas fa-map-marker-alt mr-2 text-primary"></i> 123 Tech Avenue, Silicon Valley, Metro Manila, Philippines
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Policies Section -->
                <div id="policies-section" class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-bold mb-8 flex items-center text-gray-800 border-l-4 border-primary pl-4">
                        Our Policies
                    </h2>
                    <div class="space-y-4 text-sm text-gray-600 leading-relaxed">
                        <div class="flex items-start">
                            <i class="fas fa-shield-alt mt-1 mr-3 text-primary"></i>
                            <p><strong>Downpayment Policy:</strong> To secure your order and prevent fraudulent transactions, a minimum downpayment of <strong>₱500.00</strong> is required for Cash on Pick-up and Pre-orders. This will be deducted from your total bill.</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-history mt-1 mr-3 text-primary"></i>
                            <p><strong>Pick-up Window:</strong> Orders must be picked up within <strong>3 business days</strong> from notification. Failure to pick up will result in order cancellation and forfeiture of downpayment.</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-file-contract mt-1 mr-3 text-primary"></i>
                            <p>By placing this order, you agree to our standard terms of service, including warranty and return policies for computer hardware.</p>
                        </div>
                    </div>
                    <label class="flex items-center mt-8 cursor-pointer">
                        <input type="checkbox" id="policy-check" required class="w-6 h-6 text-primary rounded-lg border-gray-300 focus:ring-primary transition-all">
                        <span class="ml-4 font-bold text-gray-700 text-lg">I agree to the downpayment and store pick-up policies</span>
                    </label>
                </div>

                <!-- Payment Method Section -->
                <div id="payment-section" class="hidden animate-fade-in-up">
                    <div class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100">
                        <h2 class="text-2xl font-bold mb-8 flex items-center text-gray-800 border-l-4 border-primary pl-4">
                            Payment Method
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <label class="relative flex items-center p-6 border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-primary transition-all group has-[:checked]:border-primary has-[:checked]:bg-green-50">
                                <input type="radio" name="payment_method" value="COP" checked onclick="togglePaymentForm('COP')" class="w-5 h-5 text-primary focus:ring-primary border-gray-300">
                                <div class="ml-4">
                                    <span class="block text-lg font-bold text-gray-800 group-hover:text-primary transition-colors">Cash on Pick-up</span>
                                    <span class="text-gray-500 text-sm">Pay ₱500 reservation fee first</span>
                                </div>
                                <i class="fas fa-money-bill-wave text-3xl text-gray-300 group-hover:text-primary transition-colors ml-auto"></i>
                            </label>
                            <label class="relative flex items-center p-6 border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-primary transition-all group has-[:checked]:border-primary has-[:checked]:bg-green-50">
                                <input type="radio" name="payment_method" value="Online" onclick="togglePaymentForm('Online')" class="w-5 h-5 text-primary focus:ring-primary border-gray-300">
                                <div class="ml-4">
                                    <span class="block text-lg font-bold text-gray-800 group-hover:text-primary transition-colors">GCash / PayMaya</span>
                                    <span class="text-gray-500 text-sm">Full amount</span>
                                </div>
                                <i class="fas fa-mobile-alt text-3xl text-gray-300 group-hover:text-primary transition-colors ml-auto"></i>
                            </label>
                        </div>

                        <!-- Payment Proof Form -->
                        <div id="payment-proof-section" class="p-8 bg-gray-50 rounded-2xl border border-gray-100 animate-fade-in-up">
                            <div class="flex flex-col md:flex-row gap-8 items-start">
                                <div class="md:w-1/2 bg-white p-6 rounded-xl text-center border border-gray-200 shadow-sm">
                                    <div class="flex items-center justify-center mb-4">
                                        <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                                            <i class="fas fa-qrcode"></i>
                                        </div>
                                        <span class="ml-3 font-bold text-gray-800">RigCheck Payment Info</span>
                                    </div>
                                    <p class="text-xs text-gray-400 font-bold mb-1 uppercase tracking-widest">GCash / Maya Number</p>
                                    <p class="text-2xl font-black text-primary">0912 345 6789</p>
                                    <p class="text-xs text-gray-500 mt-2 font-medium">Name: RigCheck Computer Store</p>
                                </div>
                                <div class="md:w-1/2 space-y-6">
                                    <div>
                                        <label class="block text-gray-700 font-bold mb-2 text-sm" for="ref_number">Transaction Reference Number</label>
                                        <input type="text" id="ref_number" name="ref_number" required placeholder="Enter 13-digit reference" class="w-full px-5 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-bold mb-2 text-sm">Upload Proof of Payment (Receipt)</label>
                                        <div class="relative group cursor-pointer">
                                            <input type="file" name="proof_image" accept="image/png,image/jpeg" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                            <div class="border-2 border-dashed border-gray-300 group-hover:border-primary rounded-xl p-6 text-center transition-all bg-white">
                                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 group-hover:text-primary mb-2"></i>
                                                <p class="text-sm text-gray-500 group-hover:text-gray-700">Click or drag image to upload receipt</p>
                                                <p class="text-[10px] text-gray-400 mt-1">PNG, JPG up to 3MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:w-1/3">
                <div class="bg-white p-10 rounded-3xl shadow-sm border border-gray-100 sticky top-28">
                    <h3 class="text-2xl font-bold mb-8 text-gray-800 border-b border-gray-100 pb-4">Order Summary</h3>
                    
                    <?php if ($has_preorder): ?>
                    <div class="mb-8 p-6 bg-yellow-50 rounded-2xl border-2 border-yellow-200 animate-pulse">
                        <div class="flex items-center gap-3 text-yellow-800">
                            <i class="fas fa-clock text-xl"></i>
                            <div>
                                <p class="font-extrabold text-sm uppercase tracking-wider">Pre-order Active</p>
                                <p class="text-xs opacity-80">This order contains items currently on pre-order status.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="max-h-80 overflow-y-auto mb-8 pr-2 space-y-3 border-b border-gray-100 pb-6">
                        <?php if (empty($checkout_items)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-shopping-cart text-4xl text-gray-200 mb-3 block"></i>
                                <p class="text-gray-400 font-bold">No items in checkout</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($checkout_items as $cart_id => $item): 
                                // Get the product_id from the item or use cart_id as fallback
                                $product_id = $item['product_id'] ?? $cart_id;
                                $product_image = getProductImage($product_id, $item['image'] ?? '');
                                $item_name = htmlspecialchars($item['name'] ?? 'Unknown Product');
                                $item_price = floatval($item['price'] ?? 0);
                                $item_qty = intval($item['quantity'] ?? 1);
                                $item_total = $item_price * $item_qty;
                                $is_preorder = $item['is_preorder'] ?? false;
                            ?>
                                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all">
                                    <div class="w-14 h-14 flex-shrink-0 bg-white rounded-lg overflow-hidden border border-gray-100 relative shadow-sm">
                                        <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                             alt="<?php echo $item_name; ?>" 
                                             class="w-full h-full object-cover" 
                                             loading="lazy"
                                             onerror="this.src='https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'">
                                        <?php if ($is_preorder): ?>
                                            <div class="absolute inset-0 bg-yellow-500/30 flex items-center justify-center">
                                                <i class="fas fa-clock text-[10px] text-yellow-700 font-bold"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="font-bold text-gray-800 text-sm line-clamp-2"><?php echo $item_name; ?></p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <p class="text-xs text-gray-500">Qty: <span class="font-bold text-gray-700"><?php echo $item_qty; ?></span></p>
                                            <span class="text-gray-300">×</span>
                                            <p class="text-xs text-gray-500">₱<span class="font-bold text-gray-700"><?php echo number_format($item_price, 2); ?></span></p>
                                        </div>
                                        <?php if ($is_preorder): ?>
                                            <p class="text-[10px] text-yellow-700 font-bold mt-1 bg-yellow-100 inline-block px-2 py-0.5 rounded">
                                                <i class="fas fa-clock mr-1"></i>PRE-ORDER
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="font-bold text-gray-900">₱<?php echo number_format($item_total, 2); ?></p>
                                        <p class="text-[10px] text-gray-400 font-medium">Subtotal</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-4 mb-8" id="summary-details">
                        <div class="flex justify-between text-gray-600">
                            <span>Selected Items Total</span>
                            <span class="font-bold">₱<?php echo number_format($subtotal_amount, 2); ?></span>
                        </div>
                        
                        <?php if ($order_type === 'preorder'): ?>
                        <!-- Pre-order: Always show ₱500 in order total -->
                        <div class="flex justify-between text-yellow-600 font-bold">
                            <span>Reservation Fee (Pre-order)</span>
                            <span>₱500.00</span>
                        </div>
                        <div class="border-t border-gray-100 pt-6 flex justify-between">
                            <span class="text-lg font-bold text-gray-800">Order Total</span>
                            <span class="text-2xl font-extrabold text-primary">₱<?php echo number_format($subtotal_amount + 500, 2); ?></span>
                        </div>
                        <?php else: ?>
                        <!-- Standard: Show base amount only -->
                        <div id="fee-row" class="hidden flex justify-between text-blue-600 font-bold">
                            <span id="fee-label">Downpayment (COP)</span>
                            <span id="fee-amount">₱500.00</span>
                        </div>
                        <div class="border-t border-gray-100 pt-6 flex justify-between">
                            <span class="text-lg font-bold text-gray-800">Order Total</span>
                            <span class="text-2xl font-extrabold text-primary" id="order-total-display">₱<?php echo number_format($subtotal_amount, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Payment Display Section -->
                        <div id="payment-display-section" class="mt-8 pt-6 border-t border-gray-200">
                            <?php if ($order_type === 'preorder'): ?>
                                <!-- Pre-order Payment Display -->
                                <div id="preorder-cop-display" class="hidden">
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-2">Downpayment (Cash on Pick-up)</p>
                                    <p class="text-2xl font-extrabold text-primary mb-2">₱500.00</p>
                                    <p class="text-[10px] text-gray-400 italic">You will pay ₱500 now. Balance due upon availability.</p>
                                </div>
                                <div id="preorder-online-display" class="hidden">
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-2">Full Payment (GCash/PayMaya)</p>
                                    <p class="text-2xl font-extrabold text-primary mb-2">₱<?php echo number_format($subtotal_amount + 500, 2); ?></p>
                                    <p class="text-[10px] text-gray-400 italic">Full amount including ₱500 reservation fee.</p>
                                </div>
                            <?php else: ?>
                                <!-- Standard Payment Display -->
                                <div id="standard-cop-display" class="hidden">
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-2">Downpayment Due Now (Cash on Pick-up)</p>
                                    <p class="text-2xl font-extrabold text-primary mb-2">₱500.00</p>
                                    <p class="text-[10px] text-gray-400 italic">This downpayment will be deducted from your total bill.</p>
                                </div>
                                <div id="standard-online-display" class="hidden">
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-2">Full Payment (GCash/PayMaya)</p>
                                    <p class="text-2xl font-extrabold text-primary mb-2">₱<?php echo number_format($subtotal_amount, 2); ?></p>
                                    <p class="text-[10px] text-gray-400 italic">Pay full amount via GCash/PayMaya.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" name="place_order" id="submit-btn" disabled class="w-full bg-gray-300 text-white py-5 rounded-2xl font-bold transition-all shadow-xl block text-center text-lg cursor-not-allowed">
                        Confirm Pick-up Order <i class="fas fa-store ml-2"></i>
                    </button>
                    <p class="text-center text-gray-400 text-xs mt-6">
                        Clicking confirm will process your selected items.
                    </p>
                </div>
            </div>
        </form>
        <!-- Verify receiver info and policies -->
        <script>
            const submitBtn = document.getElementById('submit-btn');
            const policyCheck = document.getElementById('policy-check');
            const fullName = document.getElementById('full_name');
            const phone = document.getElementById('phone');
            const paymentSection = document.getElementById('payment-section');

            function validateInputs() {
                const isValid = fullName.value.trim() !== '' && 
                              phone.value.trim() !== '' && 
                              policyCheck.checked;
                
                if (isValid) {
                    paymentSection.classList.remove('hidden');
                } else {
                    paymentSection.classList.add('hidden');
                    submitBtn.disabled = true;
                    submitBtn.classList.add('bg-gray-300', 'cursor-not-allowed');
                    submitBtn.classList.remove('bg-primary', 'hover:bg-green-600');
                }
            }

            fullName.addEventListener('input', validateInputs);
            phone.addEventListener('input', validateInputs);
            policyCheck.addEventListener('change', validateInputs);
        </script>
    </div>
</section>

<script>
function togglePaymentForm(method) {
    const onlineForm = document.getElementById('payment-proof-section');
    const policiesSection = document.getElementById('policies-section');
    const submitBtn = document.getElementById('submit-btn');
    const policyCheck = document.getElementById('policy-check');
    
    // Values from PHP
    const subtotal = <?php echo $subtotal_amount; ?>;
    const orderType = '<?php echo $order_type; ?>';
    
    // Get payment display elements
    const standardCopDisplay = document.getElementById('standard-cop-display');
    const standardOnlineDisplay = document.getElementById('standard-online-display');
    const preorderCopDisplay = document.getElementById('preorder-cop-display');
    const preorderOnlineDisplay = document.getElementById('preorder-online-display');
    
    // Hide all displays first
    if (standardCopDisplay) standardCopDisplay.classList.add('hidden');
    if (standardOnlineDisplay) standardOnlineDisplay.classList.add('hidden');
    if (preorderCopDisplay) preorderCopDisplay.classList.add('hidden');
    if (preorderOnlineDisplay) preorderOnlineDisplay.classList.add('hidden');
    
    // Update standard summary display
    const feeRow = document.getElementById('fee-row');
    const orderTotalDisplay = document.getElementById('order-total-display');

    if (method === 'Online') {
        // GCash/PayMaya: Full payment (no COP policies needed)
        onlineForm.classList.remove('hidden');
        policiesSection.classList.add('hidden');
        policyCheck.required = false;
        
        if (orderType === 'preorder') {
            if (preorderOnlineDisplay) preorderOnlineDisplay.classList.remove('hidden');
        } else {
            if (standardOnlineDisplay) standardOnlineDisplay.classList.remove('hidden');
            // Standard + Online: order total remains subtotal
            if (feeRow) feeRow.classList.add('hidden');
            if (orderTotalDisplay) orderTotalDisplay.innerText = '₱' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
        
        // Auto-enable submit button for online payment
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
        submitBtn.classList.add('bg-primary', 'hover:bg-green-600', 'transform', 'hover:-translate-y-1', 'active:scale-95');
    } else {
        // Cash on Pick-up: Show policies and payment proof section
        onlineForm.classList.remove('hidden');
        policiesSection.classList.remove('hidden');
        policyCheck.required = true;
        
        if (orderType === 'preorder') {
            if (preorderCopDisplay) preorderCopDisplay.classList.remove('hidden');
        } else {
            if (standardCopDisplay) standardCopDisplay.classList.remove('hidden');
            // Standard + COP: show downpayment line while keeping order total unchanged
            if (feeRow) feeRow.classList.remove('hidden');
            if (orderTotalDisplay) orderTotalDisplay.innerText = '₱' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
        
        // Re-check policy requirement for COP
        if (!policyCheck.checked) {
            submitBtn.disabled = true;
            submitBtn.classList.add('bg-gray-300', 'cursor-not-allowed');
            submitBtn.classList.remove('bg-primary', 'hover:bg-green-600', 'transform', 'hover:-translate-y-1', 'active:scale-95');
            document.getElementById('payment-section').classList.add('hidden');
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
            submitBtn.classList.add('bg-primary', 'hover:bg-green-600', 'transform', 'hover:-translate-y-1', 'active:scale-95');
            document.getElementById('payment-section').classList.remove('hidden');
        }
    }
}

document.getElementById('policy-check')?.addEventListener('change', function() {
    const btn = document.getElementById('submit-btn');
    const paymentSection = document.getElementById('payment-section');
    const subtotal = <?php echo $subtotal_amount; ?>;
    const orderType = '<?php echo $order_type; ?>';
    const feeRow = document.getElementById('fee-row');
    const orderTotalDisplay = document.getElementById('order-total-display');
    
    if (this.checked) {
        paymentSection.classList.remove('hidden');
        btn.disabled = false;
        btn.classList.remove('bg-gray-300', 'cursor-not-allowed');
        btn.classList.add('bg-primary', 'hover:bg-green-600', 'transform', 'hover:-translate-y-1', 'active:scale-95');
        
        // Update downpayment display for COP
        if (orderType !== 'preorder') {
            if (feeRow) feeRow.classList.remove('hidden');
            if (orderTotalDisplay) orderTotalDisplay.innerText = '₱' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
    } else {
        paymentSection.classList.add('hidden');
        btn.disabled = true;
        btn.classList.add('bg-gray-300', 'cursor-not-allowed');
        btn.classList.remove('bg-primary', 'hover:bg-green-600', 'transform', 'hover:-translate-y-1', 'active:scale-95');
        
        // Reset fee display
        if (orderType !== 'preorder') {
            if (feeRow) feeRow.classList.add('hidden');
            if (orderTotalDisplay) orderTotalDisplay.innerText = '₱' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
