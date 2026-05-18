<?php 
require_once 'includes/config.php';

// Set success message
$_SESSION['success_message'] = '✓ Your order has been placed successfully! Order #' . ($_SESSION['last_order_number'] ?? 'UNKNOWN');

// Fetch order details for invoice
$order_details = null;
$order_items = [];
$order_total = 0;
$order_type = 'standard';

if (isset($_SESSION['last_order_number']) && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Orders WHERE order_number = ? AND user_id = ?");
        $stmt->execute([$_SESSION['last_order_number'], $_SESSION['user_id']]);
        $order_details = $stmt->fetch();
        
        if ($order_details) {
            $order_id = $order_details['order_id'];
            $order_total = $order_details['total_amount'];
            $order_type = $order_details['order_type'] ?? 'standard';
            
            // Fetch order line items - simple approach, will enrich with $global_products data
            $stmt = $pdo->prepare("SELECT * FROM Order_Line_Item WHERE order_id = ? ORDER BY order_item_id ASC");
            $stmt->execute([$order_id]);
            $raw_items = $stmt->fetchAll() ?: [];
            
            // Enrich items with product data from $global_products
            $order_items = [];
            foreach ($raw_items as $item) {
                $product_id = $item['variant_id'];  // variant_id = product_id in our case
                $product_data = null;
                
                // Find product in $global_products
                foreach ($global_products as $gp) {
                    if ($gp['product_id'] == $product_id) {
                        $product_data = $gp;
                        break;
                    }
                }
                
                // Merge order item with product data
                $enriched_item = array_merge($item, [
                    'product_id' => $product_id,
                    'product_name' => $product_data['product_name'] ?? 'Unknown Product',
                    'image_url' => $product_data['image_url'] ?? ''
                ]);
                
                $order_items[] = $enriched_item;
            }
            
            // Log for debugging
            error_log("Order ID: $order_id, Items fetched: " . count($order_items));
            foreach ($order_items as $item) {
                error_log("  - Product ID: {$item['product_id']}, Name: {$item['product_name']}, Qty: {$item['quantity']}");
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching order details: " . $e->getMessage());
    }
}

include 'includes/header.php'; 
?>

<section class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <!-- INVOICE DISPLAY -->
        <?php if ($order_details): ?>
        <div class="mb-12 bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 flex items-center gap-3">
                            <i class="fas fa-receipt text-primary text-2xl"></i>
                            Purchase Invoice
                        </h2>
                        <p class="text-xs text-gray-500 font-bold mt-1 uppercase tracking-widest">Order Number: <?php echo htmlspecialchars($order_details['order_number']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Date</p>
                        <p class="text-lg font-black text-gray-900"><?php echo date('M d, Y', strtotime($order_details['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr class="text-gray-600 text-xs font-black uppercase tracking-[0.15em]">
                            <th class="px-8 py-4 text-left">Product</th>
                            <th class="px-8 py-4 text-center">QTY</th>
                            <th class="px-8 py-4 text-right">Unit Price</th>
                            <th class="px-8 py-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!empty($order_items)): ?>
                            <?php foreach ($order_items as $item):
                                $product_id = $item['product_id'];
                                $item_name = htmlspecialchars($item['product_name'] ?? 'Unknown Product');
                                $item_price = floatval($item['unit_price'] ?? 0);
                                $item_qty = intval($item['quantity'] ?? 1);
                                $item_total = $item['line_total'];
                            ?>
                                <tr class="hover:bg-gray-50 transition-all">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                                                <img src="<?php echo htmlspecialchars(getProductImage($product_id, '')); ?>" 
                                                     alt="<?php echo $item_name; ?>" 
                                                     class="w-full h-full object-cover"
                                                     onerror="this.src='https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'">
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900"><?php echo $item_name; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-center font-bold text-gray-900"><?php echo $item_qty; ?></td>
                                    <td class="px-8 py-5 text-right font-bold text-gray-900">₱<?php echo number_format($item_price, 2); ?></td>
                                    <td class="px-8 py-5 text-right font-bold text-primary text-lg">₱<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-100">
                <div class="flex justify-end max-w-xs ml-auto space-y-3">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal:</span>
                        <span class="font-bold">₱<?php echo number_format($order_total - ($order_type === 'preorder' ? 500 : 0), 2); ?></span>
                    </div>
                    <?php if ($order_type === 'preorder'): ?>
                        <div class="flex justify-between text-sm text-yellow-600 font-bold border-t border-gray-200 pt-3">
                            <span>Reservation Fee:</span>
                            <span>₱500.00</span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-base font-black text-gray-900 border-t border-gray-200 pt-3">
                        <span>Total Amount:</span>
                        <span class="text-primary text-lg">₱<?php echo number_format($order_total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- /INVOICE DISPLAY -->

        <!-- Success Message -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-16 text-center animate-fade-in-up">
            <div class="w-32 h-32 bg-green-100 rounded-full flex items-center justify-center text-primary mx-auto mb-10 shadow-inner relative">
                <div class="absolute inset-0 bg-primary rounded-full blur-2xl opacity-20 animate-pulse"></div>
                <i class="fas fa-check text-6xl relative z-10"></i>
            </div>
            
            <h1 class="text-5xl font-extrabold text-gray-800 mb-6">Order Placed Successfully!</h1>
            <p class="text-xl text-gray-500 mb-12 leading-relaxed max-w-md mx-auto">
                Thank you for choosing <span class="text-primary font-bold">RigCheck</span>. Your order has been received and is being processed by our team.
            </p>

            <div class="bg-gray-50 rounded-2xl p-8 mb-12 border border-gray-100">
                <div class="flex flex-col md:flex-row justify-around gap-8 text-sm">
                    <div class="text-center">
                        <p class="text-gray-400 font-bold uppercase tracking-widest mb-2">Order Number</p>
                        <p class="text-gray-800 font-extrabold text-xl">#<?php echo $_SESSION['last_order_number'] ?? 'RC-UNKNOWN'; ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-400 font-bold uppercase tracking-widest mb-2">Pick-up Window</p>
                        <p class="text-gray-800 font-extrabold text-xl"><?php echo date('M d, Y', strtotime('+3 days')); ?></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-6 justify-center">
                <a href="products.php" class="bg-primary hover:bg-green-600 text-white px-10 py-5 rounded-2xl font-bold transition-all shadow-xl text-lg transform hover:-translate-y-1 active:scale-95">
                    Keep Shopping <i class="fas fa-shopping-bag ml-2"></i>
                </a>
                <a href="index.php" class="border-2 border-gray-200 text-gray-500 hover:border-primary hover:text-primary px-10 py-5 rounded-2xl font-bold transition-all text-lg transform hover:-translate-y-1 active:scale-95">
                    Go to Home <i class="fas fa-home ml-2"></i>
                </a>
            </div>
            
            <p class="mt-12 text-gray-400 text-sm">
                A confirmation email has been sent to your registered email address.
            </p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
