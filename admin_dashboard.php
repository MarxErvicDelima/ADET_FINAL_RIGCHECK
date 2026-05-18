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

// ── Handle New Product Form Submission ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    if ($pdo !== null) {
        try {
            $name       = trim($_POST['product_name'] ?? '');
            $brand_id   = (int)($_POST['brand_id'] ?? 0);
            $cat_id     = (int)($_POST['category_id'] ?? 0);
            $price      = floatval($_POST['base_price'] ?? 0);
            $stock      = (int)($_POST['stock'] ?? 0);
            $image_url  = trim($_POST['image_url'] ?? '');
            $desc       = trim($_POST['description'] ?? '');

            if ($name && $price > 0) {
                $stmt = $pdo->prepare("INSERT INTO Product (name, brand_id, category_id, base_price, image_url, description)
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $brand_id ?: null, $cat_id ?: null, $price, $image_url ?: null, $desc ?: null]);
                $product_id = $pdo->lastInsertId();

                // Also insert into Inventory table
                $stmt = $pdo->prepare("INSERT INTO Inventory (variant_id, quantity_on_hand) VALUES (?, ?)");
                $stmt->execute([$product_id, $stock]);

                $_SESSION['flash_success'] = '✓ Product "' . htmlspecialchars($name) . '" added successfully.';
            } else {
                $_SESSION['flash_error'] = 'Product name and price are required.';
            }
        } catch (Exception $e) {
            error_log("Add product error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Failed to add product. Please try again.';
        }
    } else {
        $_SESSION['flash_success'] = '✓ (Demo mode) Product form submitted successfully.';
    }
    header('Location: admin_dashboard.php');
    exit();
}

// ── Handle Edit Product Form Submission ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    if ($pdo !== null) {
        try {
            $product_id = (int)($_POST['product_id'] ?? 0);
            $name       = trim($_POST['product_name'] ?? '');
            $price      = floatval($_POST['base_price'] ?? 0);
            $stock      = (int)($_POST['stock'] ?? 0);
            $image_url  = trim($_POST['image_url'] ?? '');

            if ($product_id && $name && $price > 0) {
                // Update Product table
                $stmt = $pdo->prepare("UPDATE Product SET name = ?, base_price = ?, image_url = ? WHERE product_id = ?");
                $stmt->execute([$name, $price, $image_url ?: null, $product_id]);

                // Update Inventory table
                $stmt = $pdo->prepare("INSERT INTO Inventory (variant_id, quantity_on_hand) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE quantity_on_hand = ?");
                $stmt->execute([$product_id, $stock, $stock]);

                $_SESSION['flash_success'] = '✓ Product "' . htmlspecialchars($name) . '" updated successfully.';
            } else {
                $_SESSION['flash_error'] = 'Product ID, name and price are required.';
            }
        } catch (Exception $e) {
            error_log("Edit product error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Failed to update product. Please try again.';
        }
    } else {
        $_SESSION['flash_success'] = '✓ (Demo mode) Product updated successfully.';
    }
    header('Location: admin_dashboard.php');
    exit();
}

// ── Handle Order Actions ─────────────────────────────────────────────────────
if ($pdo !== null && isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $action   = $_GET['action'];

    try {
        if ($action === 'mark_ready') {
            $pdo->prepare("UPDATE Orders SET status = 'ready' WHERE order_id = ?")->execute([$order_id]);
            $_SESSION['flash_success'] = '✓ Order #' . $order_id . ' marked as ready for pick-up.';
        } elseif ($action === 'mark_completed') {
            $pdo->prepare("UPDATE Orders SET status = 'completed' WHERE order_id = ?")->execute([$order_id]);
            $pdo->prepare("UPDATE Payment SET payment_status = 'verified' WHERE order_id = ?")->execute([$order_id]);
            $_SESSION['flash_success'] = '✓ Order #' . $order_id . ' completed and payment verified.';
        }
        header('Location: admin_dashboard.php');
        exit();
    } catch (Exception $e) {
        error_log("Error updating order: " . $e->getMessage());
    }
}

// ── Initialize Stats ─────────────────────────────────────────────────────────
$total_products   = 0;
$total_orders     = 0;
$total_users      = 0;
$low_stock_count  = 0;
$total_revenue    = 0;
$recent_orders    = [];
$products         = [];
$top_selling      = [];
$brands           = [];
$categories       = [];
$monthly_revenue  = [];

if ($pdo !== null) {
    try {
        $total_products  = $pdo->query("SELECT COUNT(*) FROM Product")->fetchColumn() ?: count($global_products);
        $total_orders    = $pdo->query("SELECT COUNT(*) FROM Orders")->fetchColumn() ?: 0;
        $total_users     = $pdo->query("SELECT COUNT(*) FROM User WHERE role_id = 2")->fetchColumn() ?: 0;
        $low_stock_count = $pdo->query("SELECT COUNT(*) FROM Inventory WHERE quantity_on_hand <= reorder_level")->fetchColumn() ?: 0;
        $total_revenue   = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM Orders WHERE status = 'completed'")->fetchColumn() ?: 0;

        $stmt = $pdo->query("SELECT o.*, u.first_name, u.last_name 
                             FROM Orders o 
                             JOIN User u ON o.user_id = u.user_id 
                             ORDER BY o.created_at DESC LIMIT 10");
        $recent_orders = $stmt->fetchAll() ?: [];

        $stmt = $pdo->query("SELECT p.*, c.name as category_name, b.brand_name 
                             FROM Product p 
                             LEFT JOIN Category c ON p.category_id = c.category_id 
                             LEFT JOIN Brand b ON p.brand_id = b.brand_id 
                             ORDER BY p.product_id DESC");
        $products = $stmt->fetchAll() ?: [];

        $stmt = $pdo->query("SELECT oli.variant_id, SUM(oli.quantity) as total_sold, 
                                    p.name as product_name, b.brand_name, c.name as category_name
                             FROM Order_Line_Item oli
                             JOIN Product_Variant pv ON oli.variant_id = pv.variant_id
                             JOIN Product p ON pv.product_id = p.product_id
                             JOIN Brand b ON p.brand_id = b.brand_id
                             JOIN Category c ON p.category_id = c.category_id
                             GROUP BY oli.variant_id
                             ORDER BY total_sold DESC LIMIT 5");
        $top_selling = $stmt->fetchAll() ?: [];

        // Monthly revenue for chart (last 6 months)
        $stmt = $pdo->query("SELECT DATE_FORMAT(created_at,'%b') as month, 
                                    MONTH(created_at) as month_num,
                                    COALESCE(SUM(total_amount),0) as revenue
                             FROM Orders
                             WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                             GROUP BY month_num, month
                             ORDER BY month_num ASC");
        $monthly_revenue = $stmt->fetchAll() ?: [];

        // Brands & Categories for new product form
        $brands     = $pdo->query("SELECT brand_id, brand_name FROM Brand ORDER BY brand_name")->fetchAll() ?: [];
        $categories = $pdo->query("SELECT category_id, name FROM Category ORDER BY name")->fetchAll() ?: [];

    } catch (Exception $e) {
        error_log("DB query error: " . $e->getMessage());
    }
}

// Fallbacks
if (empty($products) && !empty($global_products)) {
    $products        = $global_products;
    $total_products  = count($products);
}

// Merge stock values from database or $global_products into products array
if (!empty($products)) {
    foreach ($products as &$product) {
        $product['stock'] = getProductStock($product['product_id'], $product['stock'] ?? 0);
    }
    unset($product);
}
// ──────────────────────────────────────────────────────────────────────────────

if (empty($top_selling) && !empty($global_products)) {
    $top_selling = [
        ['product_name' => $global_products[4]['product_name'] ?? 'Razer DeathAdder', 'total_sold' => 42, 'brand_name' => $global_products[4]['brand_name'] ?? 'Razer',   'category_name' => 'Mouse'],
        ['product_name' => $global_products[0]['product_name'] ?? 'ASUS ROG Zephyrus','total_sold' => 28, 'brand_name' => $global_products[0]['brand_name'] ?? 'ASUS',    'category_name' => 'Laptops'],
        ['product_name' => $global_products[5]['product_name'] ?? 'Corsair K100',      'total_sold' => 15, 'brand_name' => $global_products[5]['brand_name'] ?? 'Corsair', 'category_name' => 'Keyboard'],
    ];
}

// Mock monthly revenue if empty
if (empty($monthly_revenue)) {
    $monthly_revenue = [
        ['month' => 'Dec', 'revenue' => 48200],
        ['month' => 'Jan', 'revenue' => 62500],
        ['month' => 'Feb', 'revenue' => 54800],
        ['month' => 'Mar', 'revenue' => 71300],
        ['month' => 'Apr', 'revenue' => 83900],
        ['month' => 'May', 'revenue' => 91200],
    ];
}

include 'includes/header.php'; 
?>

<!-- ══════════════════════════════════════════════
     MODAL SYSTEM
══════════════════════════════════════════════════ -->

<!-- Generic Confirm / Alert Modal -->
<div id="generic-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('generic-modal')"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 animate-fade-in-up">
        <div id="modal-icon" class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6"></div>
        <h3 id="modal-title" class="text-xl font-black text-gray-900 text-center mb-3"></h3>
        <p  id="modal-message" class="text-gray-500 text-center text-sm mb-8"></p>
        <div id="modal-buttons" class="flex gap-3 justify-center"></div>
    </div>
</div>

<!-- New Product Modal -->
<div id="new-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('new-product-modal')"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto animate-fade-in-up">
        <div class="p-8 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl z-10">
            <div>
                <h3 class="text-xl font-black text-gray-900">Add New Product</h3>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Fill in the hardware details below</p>
            </div>
            <button onclick="closeModal('new-product-modal')" class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="admin_dashboard.php" class="p-8 space-y-5">
            <input type="hidden" name="action" value="add_product">

            <div>
                <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Product Name <span class="text-red-500">*</span></label>
                <input type="text" name="product_name" required placeholder="e.g., ASUS ROG Zephyrus G14"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Brand</label>
                    <select name="brand_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all bg-white">
                        <option value="">Select brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>"><?php echo htmlspecialchars($brand['brand_name']); ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($brands)): ?>
                            <option value="1">ASUS</option>
                            <option value="2">Dell</option>
                            <option value="3">Razer</option>
                            <option value="4">Corsair</option>
                            <option value="5">Logitech</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Category</label>
                    <select name="category_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all bg-white">
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                            <option value="1">Laptops</option>
                            <option value="2">Monitors</option>
                            <option value="3">Mouse</option>
                            <option value="4">Keyboard</option>
                            <option value="5">Headset</option>
                            <option value="6">CPU</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Base Price (₱) <span class="text-red-500">*</span></label>
                    <input type="number" name="base_price" required min="0" step="0.01" placeholder="0.00"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Stock Qty</label>
                    <input type="number" name="stock" min="0" placeholder="0"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Image URL</label>
                <input type="url" name="image_url" placeholder="https://..."
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Description</label>
                <textarea name="description" rows="3" placeholder="Short product description..."
                          class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all resize-none"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('new-product-modal')"
                        class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-primary text-white font-black text-sm hover:bg-green-600 transition-all shadow-lg shadow-green-100 active:scale-95 flex items-center justify-center gap-2">
                    <i class="fas fa-plus text-xs"></i> Add Product
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="edit-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('edit-product-modal')"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto animate-fade-in-up">
        <div class="p-8 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl z-10">
            <div>
                <h3 class="text-xl font-black text-gray-900">Edit Product</h3>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Update the hardware details</p>
            </div>
            <button onclick="closeModal('edit-product-modal')" class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="admin_dashboard.php" class="p-8 space-y-5">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit-product-id">

            <div>
                <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Product Name <span class="text-red-500">*</span></label>
                <input type="text" name="product_name" id="edit-product-name" required
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Base Price (₱) <span class="text-red-500">*</span></label>
                    <input type="number" name="base_price" id="edit-product-price" required min="0" step="0.01"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Stock Qty</label>
                    <input type="number" name="stock" id="edit-product-stock" min="0"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Image URL</label>
                <input type="url" name="image_url" id="edit-product-image"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-green-100 transition-all">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('edit-product-modal')"
                        class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-blue-500 text-white font-black text-sm hover:bg-blue-600 transition-all shadow-lg shadow-blue-100 active:scale-95 flex items-center justify-center gap-2">
                    <i class="fas fa-save text-xs"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════════ -->
<div class="bg-gray-100 min-h-screen py-12">
    <div class="container mx-auto px-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="glass-card bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 flex items-center justify-between animate-fade-in-up">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span class="font-bold"><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700 ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="glass-card bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 flex items-center justify-between animate-fade-in-up">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                    <span class="font-bold"><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
            <div>
                <h1 class="text-4xl font-black text-gray-900 tracking-tight">
                    <span class="gradient-text">RigCheck</span> Control Center
                </h1>
                <p class="text-gray-500 font-medium mt-1">Welcome back, Administrator. Here's your store overview.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="#inventory" class="glass-card bg-white text-gray-700 px-6 py-3 rounded-xl font-bold border border-gray-200 hover:border-primary transition-all flex items-center gap-2">
                    <i class="fas fa-list text-primary"></i> View Inventory
                </a>
                <button onclick="openModal('new-product-modal')"
                        class="bg-primary text-white px-8 py-3 rounded-xl font-black shadow-xl shadow-green-100 hover:bg-green-600 transition-all transform active:scale-95 btn-animate flex items-center gap-2">
                    <i class="fas fa-plus"></i> New Product
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-12">
            <!-- Total Hardware -->
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

            <!-- Total Sales -->
            <div class="glass-card bg-white p-8 rounded-3xl shadow-sm border border-gray-100 group hover:border-blue-500 transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shopping-bag text-2xl"></i>
                    </div>
                    <span class="text-[10px] font-black text-blue-600 bg-blue-100 px-3 py-1 rounded-full uppercase tracking-widest">Orders</span>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest mb-1">Total Sales</h4>
                <p class="text-3xl font-black text-gray-900"><?php echo $total_orders; ?></p>
            </div>

            <!-- Total Revenue -->
            <div class="glass-card bg-white p-8 rounded-3xl shadow-sm border border-gray-100 group hover:border-emerald-500 transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-500 group-hover:scale-110 transition-transform">
                        <i class="fas fa-peso-sign text-2xl"></i>
                    </div>
                    <span class="text-[10px] font-black text-emerald-600 bg-emerald-100 px-3 py-1 rounded-full uppercase tracking-widest">Revenue</span>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest mb-1">Total Revenue</h4>
                <p class="text-2xl font-black text-gray-900">₱<?php echo number_format($total_revenue, 0); ?></p>
            </div>

            <!-- Registered Clients -->
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

            <!-- Low Stock -->
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

        <!-- Revenue Chart + Top Selling Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">

            <!-- Revenue Chart -->
            <div class="lg:col-span-2 glass-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-xl font-black text-gray-900">Revenue Overview</h3>
                        <p class="text-xs text-gray-400 font-medium mt-0.5">Completed orders — last 6 months</p>
                    </div>
                    <span class="text-[10px] font-black text-blue-600 bg-blue-100 px-3 py-1 rounded-full uppercase tracking-widest">Analytics</span>
                </div>
                <div class="p-8">
                    <canvas id="revenueChart" height="180"></canvas>
                </div>
            </div>

            <!-- Top Selling (compact sidebar) -->
            <div class="glass-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-black text-gray-900">Bestsellers</h3>
                    <span class="text-[10px] font-black text-orange-600 bg-orange-100 px-3 py-1 rounded-full uppercase tracking-widest">Hot</span>
                </div>
                <div class="p-6 space-y-4">
                    <?php foreach ($top_selling as $index => $item): ?>
                        <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 transition-all group">
                            <div class="w-9 h-9 bg-primary text-white rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0 group-hover:scale-110 transition-transform">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-black text-gray-900 text-sm line-clamp-1"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($item['brand_name']); ?></p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-black text-primary text-sm"><?php echo $item['total_sold']; ?></p>
                                <p class="text-[9px] text-gray-400 font-bold">units</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($top_selling)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-fire text-4xl text-gray-200 mb-3 block"></i>
                            <p class="text-gray-400 font-bold text-sm">No sales data yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Orders + Inventory Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Recent Orders -->
            <div id="orders" class="lg:col-span-1 scroll-mt-24">
                <div class="glass-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="text-xl font-black text-gray-900">Recent Transactions</h3>
                        <a href="my_orders.php" class="text-primary text-xs font-black uppercase tracking-widest hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
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
                                            <p class="font-black text-gray-900">#<?php echo htmlspecialchars($order['order_number'] ?? $order['order_id']); ?></p>
                                            <p class="text-xs text-gray-400 font-medium"><?php echo date('M d, Y • h:i A', strtotime($order['created_at'])); ?></p>
                                        </div>
                                        <span class="text-primary font-black text-base">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center text-[10px] font-bold text-gray-500">
                                                <?php echo strtoupper(substr($order['first_name'], 0, 1)); ?>
                                            </div>
                                            <span class="text-xs text-gray-600 font-bold"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-tighter <?php
                                                echo match($order['status']) {
                                                    'pending'   => 'bg-yellow-100 text-yellow-600',
                                                    'ready'     => 'bg-blue-100 text-blue-600',
                                                    'completed' => 'bg-green-100 text-green-600',
                                                    'cancelled' => 'bg-red-100 text-red-600',
                                                    default     => 'bg-gray-100 text-gray-600'
                                                };
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button onclick="confirmOrderAction(<?php echo $order['order_id']; ?>, 'mark_ready', 'Mark Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['order_id']); ?> as ready for pick-up?')"
                                                        class="w-8 h-8 flex items-center justify-center bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition shadow-lg shadow-blue-100 active:scale-90" title="Mark Ready">
                                                    <i class="fas fa-truck-loading text-xs"></i>
                                                </button>
                                            <?php elseif ($order['status'] === 'ready'): ?>
                                                <button onclick="confirmOrderAction(<?php echo $order['order_id']; ?>, 'mark_completed', 'Mark Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['order_id']); ?> as completed and verify payment?')"
                                                        class="w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-lg hover:bg-green-600 transition shadow-lg shadow-green-100 active:scale-90" title="Mark Completed">
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

            <!-- Hardware Inventory Table -->
            <div id="inventory" class="lg:col-span-2 scroll-mt-24">
                <div class="glass-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <h3 class="text-xl font-black text-gray-900">Hardware Catalog</h3>
                        <div class="relative w-full sm:w-64">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" id="product-search" placeholder="Filter by name, brand..."
                                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:outline-none focus:border-primary transition-all shadow-inner"
                                   autocomplete="off">
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white text-gray-400 uppercase text-[9px] font-black tracking-[0.2em] border-b border-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-8 py-5">Product Details</th>
                                    <th class="px-8 py-5">Category</th>
                                    <th class="px-8 py-5">MSRP</th>
                                    <th class="px-8 py-5">Stock</th>
                                    <th class="px-8 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm" id="products-table-body">
                                <?php foreach ($products as $product): ?>
                                    <tr class="hover:bg-gray-50/50 transition-all group product-row"
                                        data-product-name="<?php echo strtolower(htmlspecialchars($product['product_name'] ?? $product['name'] ?? '')); ?>"
                                        data-brand="<?php echo strtolower(htmlspecialchars($product['brand_name'] ?? '')); ?>">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center">
                                                <div class="w-12 h-12 bg-gray-50 rounded-xl mr-4 overflow-hidden border border-gray-100 p-1 flex-shrink-0">
                                                    <img src="<?php echo htmlspecialchars(getProductImage($product['product_id'], $product['image_url'] ?? '')); ?>"
                                                         class="w-full h-full object-cover rounded-lg group-hover:scale-110 transition-transform" loading="lazy">
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
                                                $stock = $product['stock'] ?? getProductStock($product['product_id'], 0);
                                                $stock_class = $stock <= 5 ? 'text-red-500' : 'text-green-500';
                                                $bar_class   = $stock <= 5 ? 'bg-red-500' : 'bg-green-500';
                                            ?>
                                            <div class="flex flex-col">
                                                <span class="<?php echo $stock_class; ?> font-black text-xs"><?php echo $stock; ?> units</span>
                                                <div class="w-16 h-1 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
                                                    <div class="h-full <?php echo $bar_class; ?>" style="width: <?php echo min(100, $stock * 10); ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>"
                                                   class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-primary hover:bg-green-50 rounded-lg transition-all" title="View">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </a>
                                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode([
                                                    'product_id'   => $product['product_id'],
                                                    'product_name' => $product['product_name'] ?? $product['name'] ?? '',
                                                    'base_price'   => $product['base_price'] ?? 0,
                                                    'stock'        => $product['stock'] ?? 0,
                                                    'image_url'    => $product['image_url'] ?? '',
                                                ])); ?>)"
                                                        class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-all" title="Edit">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </button>
                                                <button onclick="confirmArchive(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($product['product_name'] ?? $product['name'] ?? 'this product')); ?>')"
                                                        class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Archive">
                                                    <i class="fas fa-archive text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <!-- No-results placeholder (JS-injected) -->
                    </div>
                    <!-- Product count -->
                    <div class="px-8 py-4 border-t border-gray-100 bg-gray-50/50">
                        <p class="text-xs text-gray-400 font-bold">
                            Showing <span id="visible-count"><?php echo count($products); ?></span> of <?php echo count($products); ?> products
                        </p>
                    </div>
                </div>
            </div>

        </div><!-- /grid -->
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
// ══════════════════════════════════════════════
//  MODAL SYSTEM
// ══════════════════════════════════════════════
function openModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    const el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
    document.body.style.overflow = '';
}

// Close modal on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['generic-modal','new-product-modal','edit-product-modal'].forEach(closeModal);
    }
});

// Generic alert / confirm modal
function showModal(type, title, message, onConfirm = null) {
    const iconEl    = document.getElementById('modal-icon');
    const titleEl   = document.getElementById('modal-title');
    const msgEl     = document.getElementById('modal-message');
    const btnsEl    = document.getElementById('modal-buttons');

    titleEl.textContent   = title;
    msgEl.textContent     = message;
    btnsEl.innerHTML      = '';

    // Icon styling
    const icons = {
        confirm: { bg: 'bg-blue-50',   color: 'text-blue-500',  icon: 'fas fa-question-circle' },
        error:   { bg: 'bg-red-50',    color: 'text-red-500',   icon: 'fas fa-exclamation-circle' },
        success: { bg: 'bg-green-50',  color: 'text-green-500', icon: 'fas fa-check-circle' },
        warning: { bg: 'bg-yellow-50', color: 'text-yellow-500',icon: 'fas fa-exclamation-triangle' },
    };
    const cfg = icons[type] || icons.error;
    iconEl.className = `w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6 ${cfg.bg} ${cfg.color}`;
    iconEl.innerHTML = `<i class="${cfg.icon}"></i>`;

    if (type === 'confirm' && onConfirm) {
        // Cancel button
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'flex-1 py-3 px-6 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition-all';
        cancelBtn.textContent = 'Cancel';
        cancelBtn.onclick = () => closeModal('generic-modal');

        // Confirm button
        const confirmBtn = document.createElement('button');
        confirmBtn.className = 'flex-1 py-3 px-6 rounded-xl bg-blue-500 text-white font-black text-sm hover:bg-blue-600 transition-all active:scale-95';
        confirmBtn.textContent = 'Confirm';
        confirmBtn.onclick = () => { closeModal('generic-modal'); onConfirm(); };

        btnsEl.appendChild(cancelBtn);
        btnsEl.appendChild(confirmBtn);
    } else {
        const okBtn = document.createElement('button');
        okBtn.className = 'px-10 py-3 rounded-xl bg-primary text-white font-black text-sm hover:bg-green-600 transition-all active:scale-95';
        okBtn.textContent = 'OK';
        okBtn.onclick = () => closeModal('generic-modal');
        btnsEl.appendChild(okBtn);
    }

    openModal('generic-modal');
}

// ══════════════════════════════════════════════
//  ORDER ACTIONS
// ══════════════════════════════════════════════
function confirmOrderAction(orderId, action, message) {
    const label = action === 'mark_ready' ? 'Mark Ready' : 'Mark Completed';
    showModal('confirm', label + '?', message, () => {
        window.location.href = '?action=' + action + '&order_id=' + orderId;
    });
}

// ══════════════════════════════════════════════
//  EDIT PRODUCT MODAL
// ══════════════════════════════════════════════
function openEditModal(product) {
    document.getElementById('edit-product-id').value    = product.product_id;
    document.getElementById('edit-product-name').value  = product.product_name;
    document.getElementById('edit-product-price').value = product.base_price;
    document.getElementById('edit-product-stock').value = product.stock;
    document.getElementById('edit-product-image').value = product.image_url;
    openModal('edit-product-modal');
}

// ══════════════════════════════════════════════
//  ARCHIVE PRODUCT
// ══════════════════════════════════════════════
function confirmArchive(productId, productName) {
    showModal('warning', 'Archive Product?',
        'Archive "' + productName + '"? It will be hidden from the storefront.',
        () => {
            window.location.href = '?action=archive_product&product_id=' + productId;
        }
    );
}

// ══════════════════════════════════════════════
//  PRODUCT SEARCH / FILTER
// ══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    const searchInput  = document.getElementById('product-search');
    const tbody        = document.getElementById('products-table-body');
    const visibleCount = document.getElementById('visible-count');
    const productRows  = () => document.querySelectorAll('.product-row');
    const total        = productRows().length;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            let count  = 0;

            productRows().forEach(row => {
                const name  = row.dataset.productName || '';
                const brand = row.dataset.brand || '';
                const match = !term || name.includes(term) || brand.includes(term);
                row.style.display = match ? '' : 'none';
                if (match) count++;
            });

            if (visibleCount) visibleCount.textContent = count;

            // No-results row
            let noResults = tbody.querySelector('.no-results-row');
            if (count === 0 && term) {
                if (!noResults) {
                    noResults = document.createElement('tr');
                    noResults.className = 'no-results-row';
                    noResults.innerHTML = `<td colspan="5" class="px-8 py-16 text-center">
                        <i class="fas fa-search text-4xl text-gray-200 mb-4 block"></i>
                        <p class="text-gray-400 font-bold">No products matching "<span class="text-gray-600">${term}</span>"</p>
                    </td>`;
                    tbody.appendChild(noResults);
                } else {
                    noResults.querySelector('span').textContent = term;
                }
                noResults.style.display = '';
            } else if (noResults) {
                noResults.style.display = 'none';
            }
        });
    }

    // ══════════════════════════════════════════
    //  REVENUE CHART
    // ══════════════════════════════════════════
    const ctx = document.getElementById('revenueChart');
    if (ctx && typeof Chart !== 'undefined') {
        const labels   = <?php echo json_encode(array_column($monthly_revenue, 'month')); ?>;
        const revenues = <?php echo json_encode(array_map('floatval', array_column($monthly_revenue, 'revenue'))); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: revenues,
                    backgroundColor: revenues.map((_, i) =>
                        i === revenues.length - 1 ? '#10b981' : 'rgba(16,185,129,0.15)'
                    ),
                    borderColor: revenues.map((_, i) =>
                        i === revenues.length - 1 ? '#10b981' : 'rgba(16,185,129,0.4)'
                    ),
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ₱' + ctx.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold', size: 11 }, color: '#9ca3af' }
                    },
                    y: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: {
                            font: { weight: 'bold', size: 11 },
                            color: '#9ca3af',
                            callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                        }
                    }
                }
            }
        });
    }

    // Auto-dismiss flash messages after 5s
    setTimeout(() => {
        document.querySelectorAll('.glass-card.bg-green-50, .glass-card.bg-red-50').forEach(el => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 5000);
});
</script>

<?php include 'includes/footer.php'; ?>