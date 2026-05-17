<?php 
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php'; 

$user_id = $_SESSION['user_id'];
$orders = [];

if ($pdo !== null) {
    // Fetch user's orders
    $stmt = $pdo->prepare("SELECT o.*, o.discount_amount as downpayment_amount, p.method_name as payment_method, py.payment_status, py.amount_paid
                           FROM Orders o 
                           LEFT JOIN Payment py ON o.order_id = py.order_id
                           LEFT JOIN Payment_method p ON py.method_id = p.method_id
                           WHERE o.user_id = ? 
                           ORDER BY o.created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
}
?>

<section class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
            <h1 class="text-4xl font-black text-gray-800"><i class="fas fa-box-open text-primary mr-3"></i> My <span class="text-primary">Orders</span></h1>
            <a href="products.php" class="bg-white text-primary border-2 border-primary px-6 py-3 rounded-xl font-bold hover:bg-primary hover:text-white transition-all shadow-sm">
                <i class="fas fa-plus mr-2"></i> New Order
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-3xl p-20 text-center shadow-sm border border-gray-100 max-w-2xl mx-auto">
                <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center text-primary mx-auto mb-8">
                    <i class="fas fa-shopping-basket text-5xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">No orders yet</h2>
                <p class="text-gray-500 mb-8 text-lg">You haven't placed any orders yet. Check out our amazing rigs and components!</p>
                <a href="products.php" class="bg-primary text-white px-10 py-4 rounded-xl font-bold hover:bg-green-600 transition-all shadow-lg inline-block">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-8">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md">
                        <!-- Order Header -->
                        <div class="p-6 md:p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-primary shadow-sm border border-gray-100">
                                    <i class="fas fa-receipt text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-1">Order Number</p>
                                    <h3 class="text-xl font-black text-gray-800"><?php echo $order['order_number']; ?></h3>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y • h:i A', strtotime($order['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <span class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider 
                                    <?php 
                                        echo match($order['status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-600',
                                            'ready' => 'bg-blue-100 text-blue-600',
                                            'completed' => 'bg-green-100 text-green-600',
                                            'cancelled' => 'bg-red-100 text-red-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                    ?>">
                                    Status: <?php echo ucfirst($order['status']); ?>
                                </span>
                                <span class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-black uppercase tracking-wider">
                                    <?php echo $order['order_type'] == 'preorder' ? 'Pre-order' : 'Standard Pickup'; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="p-6 md:p-8">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <div class="lg:col-span-2">
                                    <h4 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Items Ordered</h4>
                                    <div class="space-y-6">
                                        <?php 
                                        $items = [];
                                        if ($pdo !== null) {
                                            $stmt_items = $pdo->prepare("SELECT oli.* FROM Order_Line_Item oli WHERE oli.order_id = ?");
                                            $stmt_items->execute([$order['order_id']]);
                                            $line_items = $stmt_items->fetchAll();
                                            
                                            foreach ($line_items as $item) {
                                                // Enrich with product data from global_products
                                                $product_info = null;
                                                foreach ($global_products as $p) {
                                                    if ($p['product_id'] == $item['variant_id']) {
                                                        $product_info = $p;
                                                        break;
                                                    }
                                                }
                                                
                                                if ($product_info) {
                                                    $item['name'] = $product_info['product_name'];
                                                    $item['sku'] = 'SKU-' . $item['variant_id'];
                                                    $item['color_name'] = 'Standard';
                                                    $item['image'] = $product_info['image_url'];
                                                } else {
                                                    // Try DB if not in global_products
                                                    try {
                                                        $stmt_db = $pdo->prepare("SELECT p.name, pv.sku, c.color_name 
                                                                                FROM Product_Variant pv 
                                                                                JOIN Product p ON pv.product_id = p.product_id 
                                                                                LEFT JOIN Color c ON pv.color_id = c.color_id
                                                                                WHERE pv.variant_id = ?");
                                                        $stmt_db->execute([$item['variant_id']]);
                                                        $db_info = $stmt_db->fetch();
                                                        if ($db_info) {
                                                            $item['name'] = $db_info['name'];
                                                            $item['sku'] = $db_info['sku'];
                                                            $item['color_name'] = $db_info['color_name'];
                                                        }
                                                    } catch (Exception $e) {}
                                                }
                                                
                                                $items[] = $item;
                                            }
                                        }
                                        
                                        foreach ($items as $item): ?>
                                            <div class="flex items-center gap-6 group">
                                                <div class="w-20 h-20 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 flex-shrink-0">
                                                    <img src="<?php echo $item['image'] ?? 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'; ?>" class="w-full h-full object-cover">
                                                </div>
                                                <div class="flex-grow">
                                                    <h5 class="font-bold text-gray-800 group-hover:text-primary transition-colors"><?php echo $item['name'] ?? 'Unknown Product'; ?></h5>
                                                    <p class="text-xs text-gray-400 mt-1">
                                                        SKU: <?php echo $item['sku'] ?? 'N/A'; ?> • 
                                                        Color: <?php echo $item['color_name'] ?? 'Standard'; ?>
                                                    </p>
                                                    <div class="flex items-center gap-4 mt-2">
                                                        <span class="text-sm font-bold text-gray-700">₱<?php echo number_format($item['unit_price'], 2); ?></span>
                                                        <span class="text-xs text-gray-400">Qty: <?php echo $item['quantity']; ?></span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="font-black text-gray-800">₱<?php echo number_format($item['line_total'], 2); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Order Summary / Payment -->
                                <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 h-fit">
                                    <h4 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Order Summary</h4>
                                    <div class="space-y-4 text-sm">
                                        <div class="flex justify-between text-gray-600">
                                            <span>Subtotal</span>
                                            <span class="font-bold">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                        <?php if ($order['downpayment_amount'] > 0): ?>
                                            <div class="flex justify-between text-primary font-bold">
                                                <span>Downpayment (Paid)</span>
                                                <span>₱<?php echo number_format($order['downpayment_amount'], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="pt-4 border-t border-gray-200 flex justify-between items-center">
                                            <span class="font-black text-gray-800">Balance Due</span>
                                            <span class="text-xl font-black text-primary">
                                                ₱<?php 
                                                if (($order['amount_paid'] ?? 0) >= $order['total_amount']) {
                                                    echo '0.00';
                                                } else {
                                                    echo number_format($order['total_amount'] - ($order['amount_paid'] ?? 0), 2); 
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-8 pt-6 border-t border-gray-200">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-3">Payment Info</p>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-gray-600">Method:</span>
                                            <span class="text-xs font-black text-gray-800"><?php echo $order['payment_method'] ?: 'N/A'; ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-gray-600">Payment:</span>
                                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full <?php echo $order['payment_status'] == 'verified' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                                <?php echo $order['payment_status'] ?: 'Pending'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($order['status'] == 'ready'): ?>
                                        <div class="mt-8 p-4 bg-primary/10 rounded-2xl border border-primary/20 text-center">
                                            <p class="text-xs font-bold text-primary mb-1"><i class="fas fa-store mr-1"></i> Ready for Pick-up!</p>
                                            <p class="text-[10px] text-gray-500">Please visit our store within 3 days.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
