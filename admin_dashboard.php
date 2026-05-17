<?php 
require_once 'includes/config.php';

// Resolve the current role before sending any output.
if ($pdo !== null && !isset($_SESSION['role_id']) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT role_id FROM User WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
        if ($currentUser) {
            $_SESSION['role_id'] = (int)$currentUser['role_id'];
        }
    } catch (Exception $e) {
        error_log("Error resolving user role: " . $e->getMessage());
    }
}

// Check if user is admin
if ((int)($_SESSION['role_id'] ?? 0) !== 1) {
    header('Location: index.php');
    exit();
}

include 'includes/header.php'; 

// Handle Order Actions
if ($pdo !== null && isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $action = $_GET['action'];
    
    try {
        if ($action == 'mark_ready') {
            $pdo->prepare("UPDATE Orders SET status = 'ready' WHERE order_id = ?")->execute([$order_id]);
            $_SESSION['success_message'] = '✓ Order #' . $order_id . ' was marked ready for pick-up.';
        } elseif ($action == 'mark_completed') {
            $pdo->prepare("UPDATE Orders SET status = 'completed' WHERE order_id = ?")->execute([$order_id]);
            $pdo->prepare("UPDATE Payment SET payment_status = 'verified' WHERE order_id = ?")->execute([$order_id]);
            $_SESSION['success_message'] = '✓ Order #' . $order_id . ' was marked completed and payment verified.';
        }
        header('Location: admin_dashboard.php');
        exit();
    } catch (Exception $e) {
        error_log("Error updating order: " . $e->getMessage());
    }
}

// Initialize dashboard statistics
$total_products = 0;
$total_orders = 0;
$total_users = 0;
$low_stock_count = 0;
$recent_orders = [];
$products = [];
$top_selling = [];

// Fetch statistics and data
if ($pdo !== null) {
    try {
        // Get total products
        $total_products = $pdo->query("SELECT COUNT(*) FROM Product")->fetchColumn() ?: count($global_products);
        
        // Get total orders
        $total_orders = $pdo->query("SELECT COUNT(*) FROM Orders")->fetchColumn() ?: 0;
        
        // Get total customer users (role_id = 2)
        $total_users = $pdo->query("SELECT COUNT(*) FROM User WHERE role_id = 2")->fetchColumn() ?: 0;
        
        // Get low stock count
        $low_stock_count = $pdo->query("SELECT COUNT(*) FROM Inventory WHERE quantity_on_hand <= reorder_level")->fetchColumn() ?: 0;

        // Fetch recent orders (last 5)
        $stmt = $pdo->query("SELECT o.*, u.first_name, u.last_name 
                            FROM Orders o 
                            JOIN User u ON o.user_id = u.user_id 
                            ORDER BY o.created_at DESC LIMIT 5");
        $recent_orders = $stmt->fetchAll() ?: [];

        // Fetch all products with category and brand info
        $stmt = $pdo->query("SELECT p.*, c.name as category_name, b.brand_name 
                            FROM Product p 
                            LEFT JOIN Category c ON p.category_id = c.category_id 
                            LEFT JOIN Brand b ON p.brand_id = b.brand_id 
                            ORDER BY p.product_id DESC");
        $products = $stmt->fetchAll() ?: [];

        // Fetch Top Selling Products
        $stmt = $pdo->query("SELECT oli.variant_id, SUM(oli.quantity) as total_sold, 
                                    p.name as product_name, b.brand_name, c.name as category_name
                             FROM Order_Line_Item oli
                             JOIN Product_Variant pv ON oli.variant_id = pv.variant_id
                             JOIN Product p ON pv.product_id = p.product_id
                             JOIN Brand b ON p.brand_id = b.brand_id
                             JOIN Category c ON p.category_id = c.category_id
                             GROUP BY oli.variant_id
                             ORDER BY total_sold DESC
                             LIMIT 5");
        $top_selling = $stmt->fetchAll() ?: [];
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
    }
}

// Fallback to hardcoded products if database is empty
if (empty($products) && !empty($global_products)) {
    $products = $global_products;
    $total_products = count($products);
}

// Mock top selling if database is empty
if (empty($top_selling) && !empty($global_products)) {
    $top_selling = [
        ['product_name' => $global_products[4]['product_name'] ?? 'Razer DeathAdder', 'total_sold' => 42, 'brand_name' => $global_products[4]['brand_name'] ?? 'Razer', 'category_name' => $global_products[4]['category_name'] ?? 'Mouse'],
        ['product_name' => $global_products[0]['product_name'] ?? 'ASUS ROG', 'total_sold' => 28, 'brand_name' => $global_products[0]['brand_name'] ?? 'ASUS', 'category_name' => $global_products[0]['category_name'] ?? 'Laptops'],
        ['product_name' => $global_products[5]['product_name'] ?? 'Corsair K100', 'total_sold' => 15, 'brand_name' => $global_products[5]['brand_name'] ?? 'Corsair', 'category_name' => $global_products[5]['category_name'] ?? 'Keyboard'],
    ];
}
?>

<div class="bg-gray-100 min-h-screen py-12">
    <div class="container mx-auto px-4">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="glass-card bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 flex items-center justify-between animate-fade-in-up">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span class="font-bold"><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
            <div>
                <h1 class="text-4xl font-black text-gray-900 tracking-tight">
                    <span class="gradient-text">RigCheck</span> Control Center
                </h1>
                <p class="text-gray-500 font-medium mt-1">Welcome back, Administrator. Here's your store overview.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="#inventory" class="glass-card bg-white text-gray-700 px-6 py-3 rounded-xl font-bold border border-gray-200 hover:border-primary transition-all flex items-center gap-2 scroll-smooth">
                    <i class="fas fa-list text-primary"></i> View Inventory
                </a>
                <a href="#" class="bg-primary text-white px-8 py-3 rounded-xl font-black shadow-xl shadow-green-100 hover:bg-green-600 transition-all transform active:scale-95 btn-animate flex items-center gap-2">
                    <i class="fas fa-plus"></i> New Product
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card bg-white p-8 rounded-3xl shadow-sm border border-gray-100 group hover:border-primary transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                        <i class="fas fa-box-open text-2xl"></i>
                    </div>
                    <span class="text-[10px] font-black text-green-600 bg-green-100 px-3 py-1 rounded-full uppercase tracking-widest">Active</span>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest mb-1">Total Hardware</h4>
                <p class="text-3xl font-black text-gray-900"><?php echo $total_products; ?></p>
            </div>
            
            <div class="glass-card bg-white p-8 rounded-3xl shadow-sm border border-gray-100 group hover:border-blue-500 transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shopping-bag text-2xl"></i>
                    </div>
                    <span class="text-[10px] font-black text-blue-600 bg-blue-100 px-3 py-1 rounded-full uppercase tracking-widest">Growth</span>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest mb-1">Total Sales</h4>
                <p class="text-3xl font-black text-gray-900"><?php echo $total_orders; ?></p>
            </div>

            <div class="glass-card bg-white p-8 rounded-3xl shadow-sm border border-gray-100 group hover:border-purple-500 transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-500 group-hover:scale-110 transition-transform">
                        <i class="fas fa-user-shield text-2xl"></i>
                    </div>
                    <span class="text-[10px] font-black text-purple-600 bg-purple-100 px-3 py-1 rounded-full uppercase tracking-widest">Secure</span>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest mb-1">Registered Clients</h4>
                <p class="text-3xl font-black text-gray-900"><?php echo $total_users; ?></p>
            </div>

            <div class="glass-card bg-white p-8 rounded-3xl shadow-sm border border-gray-100 group hover:border-yellow-500 transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-yellow-50 rounded-2xl flex items-center justify-center text-yellow-500 group-hover:scale-110 transition-transform">
                        <i class="fas fa-warehouse text-2xl"></i>
                    </div>
                    <?php if ($low_stock_count > 0): ?>
                        <span class="text-[10px] font-black text-red-600 bg-red-100 px-3 py-1 rounded-full uppercase tracking-widest animate-pulse">Critical</span>
                    <?php else: ?>
                        <span class="text-[10px] font-black text-green-600 bg-green-100 px-3 py-1 rounded-full uppercase tracking-widest">Healthy</span>
                    <?php endif; ?>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest mb-1">Low Stock Alerts</h4>
                <p class="text-3xl font-black text-gray-900"><?php echo $low_stock_count; ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Top Selling Products Section -->
            <div class="lg:col-span-3">
                <div class="glass-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-12">
                    <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="text-xl font-black text-gray-900">Top Selling Hardware</h3>
                        <span class="text-[10px] font-black text-primary bg-green-100 px-3 py-1 rounded-full uppercase tracking-widest">Bestsellers</span>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <?php foreach ($top_selling as $index => $item): ?>
                                <div class="relative p-6 rounded-2xl border border-gray-100 bg-gray-50/30 group hover:border-primary transition-all">
                                    <div class="absolute -top-3 -right-3 w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-black shadow-lg">
                                        #<?php echo $index + 1; ?>
                                    </div>
                                    <h4 class="font-black text-gray-900 mb-1 line-clamp-1"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-4"><?php echo htmlspecialchars($item['brand_name']); ?></p>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-xs text-gray-500 font-bold">Total Sold</p>
                                            <p class="text-2xl font-black text-primary"><?php echo $item['total_sold']; ?> <span class="text-xs font-medium text-gray-400">units</span></p>
                                        </div>
                                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-fire text-orange-500"></i>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div id="orders" class="lg:col-span-1 scroll-mt-24">
                <div class="glass-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden h-full">
                    <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="text-xl font-black text-gray-900">Recent Transactions</h3>
                        <a href="my_orders.php" class="text-primary text-xs font-black uppercase tracking-widest hover:underline">View Ledger</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php if (empty($recent_orders)): ?>
                            <div class="p-12 text-center">
                                <i class="fas fa-receipt text-4xl text-gray-200 mb-4 block"></i>
                                <p class="text-gray-400 font-bold">No recent activity detected.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="p-6 hover:bg-gray-50/80 transition-all group">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <p class="font-black text-gray-900">#<?php echo $order['order_number']; ?></p>
                                            <p class="text-xs text-gray-400 font-medium"><?php echo date('M d, Y • h:i A', strtotime($order['created_at'])); ?></p>
                                        </div>
                                        <span class="text-primary font-black text-lg">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center text-[10px] font-bold text-gray-500">
                                                <?php echo substr($order['first_name'], 0, 1); ?>
                                            </div>
                                            <span class="text-xs text-gray-600 font-bold"><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-tighter <?php 
                                                echo match($order['status']) {
                                                    'pending' => 'bg-yellow-100 text-yellow-600',
                                                    'ready' => 'bg-blue-100 text-blue-600',
                                                    'completed' => 'bg-green-100 text-green-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                            ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <button onclick="confirmOrderAction(<?php echo $order['order_id']; ?>, 'mark_ready', 'Mark this order as ready for pick-up?')" class="w-8 h-8 flex items-center justify-center bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition shadow-lg shadow-blue-100 active:scale-90" title="Mark Ready">
                                                    <i class="fas fa-truck-loading text-xs"></i>
                                                </button>
                                            <?php elseif ($order['status'] == 'ready'): ?>
                                                <button onclick="confirmOrderAction(<?php echo $order['order_id']; ?>, 'mark_completed', 'Mark this order as completed and verify payment?')" class="w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-lg hover:bg-green-600 transition shadow-lg shadow-green-100 active:scale-90" title="Mark Completed">
                                                    <i class="fas fa-check text-xs"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Hardware Inventory Section -->
            <div id="inventory" class="lg:col-span-2 scroll-mt-24">
                <div class="glass-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <h3 class="text-xl font-black text-gray-900">Hardware Catalog</h3>
                        <div class="relative w-full sm:w-64">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" id="product-search" placeholder="Filter by name, brand..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:outline-none focus:border-primary transition-all shadow-inner" autocomplete="off">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white text-gray-400 uppercase text-[9px] font-black tracking-[0.2em] border-b border-gray-100">
                                <tr>
                                    <th class="px-8 py-5">Product Details</th>
                                    <th class="px-8 py-5">Classification</th>
                                    <th class="px-8 py-5">MSRP</th>
                                    <th class="px-8 py-5">Availability</th>
                                    <th class="px-8 py-5 text-right">Ops</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm" id="products-table-body">
                                <?php foreach ($products as $product): ?>
                                    <tr class="hover:bg-gray-50/50 transition-all group product-row" data-product-name="<?php echo strtolower(htmlspecialchars($product['product_name'] ?? $product['name'] ?? '')); ?>" data-brand="<?php echo strtolower(htmlspecialchars($product['brand_name'] ?? '')); ?>">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center">
                                                <div class="w-12 h-12 bg-gray-50 rounded-xl mr-4 overflow-hidden border border-gray-100 p-1 flex-shrink-0">
                                                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'); ?>" class="w-full h-full object-cover rounded-lg group-hover:scale-110 transition-transform" loading="lazy">
                                                </div>
                                                <div>
                                                    <p class="font-black text-gray-900 line-clamp-1"><?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Generic Product'); ?></p>
                                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($product['brand_name'] ?? 'No Brand'); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <span class="text-[10px] font-black text-gray-600 bg-gray-100 px-3 py-1.5 rounded-lg uppercase tracking-wider">
                                                <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 font-black text-gray-900">₱<?php echo number_format($product['base_price'] ?? 0, 2); ?></td>
                                        <td class="px-8 py-6">
                                            <?php 
                                                $stock = $product['stock'] ?? 10;
                                                $stock_class = $stock <= 5 ? 'text-red-500' : 'text-green-500';
                                            ?>
                                            <div class="flex flex-col">
                                                <span class="<?php echo $stock_class; ?> font-black text-xs"><?php echo $stock; ?> units</span>
                                                <div class="w-16 h-1 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
                                                    <div class="h-full <?php echo $stock <= 5 ? 'bg-red-500' : 'bg-green-500'; ?>" style="width: <?php echo min(100, $stock * 10); ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-primary hover:bg-green-50 rounded-lg transition-all" title="View Product">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </a>
                                                <button onclick="showModal('error', 'Coming Soon', 'Product editing features coming soon!')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-all" title="Edit Product">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </button>
                                                <button onclick="showModal('error', 'Coming Soon', 'Product archiving features coming soon!')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Archive">
                                                    <i class="fas fa-archive text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmOrderAction(orderId, action, message) {
    const actionLabel = action === 'mark_ready' ? 'Mark Ready' : 'Mark Completed';
    showModal('confirm', actionLabel + '?', message, () => {
        window.location.href = '?action=' + action + '&order_id=' + orderId;
    });
}

// Product Search/Filter
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('product-search');
    const productRows = document.querySelectorAll('.product-row');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            let visibleCount = 0;

            productRows.forEach(row => {
                const productName = row.dataset.productName || '';
                const brand = row.dataset.brand || '';
                const matches = productName.includes(searchTerm) || brand.includes(searchTerm);
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            // Show "No results" message if needed
            if (visibleCount === 0 && searchTerm) {
                const tbody = document.getElementById('products-table-body');
                let noResults = tbody.querySelector('.no-results');
                if (!noResults) {
                    noResults = document.createElement('tr');
                    noResults.className = 'no-results';
                    noResults.innerHTML = '<td colspan="5" class="px-8 py-12 text-center"><i class="fas fa-search text-4xl text-gray-200 mb-4 block"></i><p class="text-gray-400 font-bold">No products found matching "' + searchTerm + '"</p></td>';
                    tbody.appendChild(noResults);
                }
            } else {
                const noResults = document.querySelector('.no-results');
                if (noResults) noResults.remove();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
