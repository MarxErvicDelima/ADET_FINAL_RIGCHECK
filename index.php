<?php 
require_once 'includes/config.php';

include 'includes/header.php'; 

// Fetch featured products (newest arrivals)
$featured_products = [];
$promo_products = [];

if ($pdo !== null) {
    // 1. Fetch Promotional Products (Rule 4)
    try {
        $promo_stmt = $pdo->query("SELECT p.product_id, p.name as product_name, p.base_price, p.status, 
                                         c.name as category_name, b.brand_name, 
                                         pr.name as promo_name, pr.value as discount_value, pr.type as discount_type,
                                         p.image_url
                                  FROM Product p 
                                  JOIN Category c ON p.category_id = c.category_id 
                                  JOIN Brand b ON p.brand_id = b.brand_id
                                  JOIN Product_Variant pv ON p.product_id = pv.product_id
                                  JOIN Promotion_Product pp ON pv.variant_id = pp.variant_id
                                  JOIN Promotions pr ON pp.promo_id = pr.promo_id
                                  WHERE p.status = 'active' AND pr.is_active = 1 AND NOW() BETWEEN pr.start_date AND pr.end_date
                                  ORDER BY p.product_id ASC"); // Order to be consistent
        $raw_promo = $promo_stmt->fetchAll();
        $promo_products = [];
        $seen_names = [];
        foreach ($raw_promo as $rp) {
            $name = trim($rp['product_name']);
            $lower_name = strtolower($name);
            if (!in_array($lower_name, $seen_names)) {
                $rp['product_name'] = $name; // Ensure trimmed name
                $promo_products[] = $rp;
                $seen_names[] = $lower_name;
            }
            if (count($promo_products) >= 4) break;
        }
    } catch (Exception $e) {
        $promo_products = [];
    }

    // 2. Fetch featured products (newest arrivals), excluding those already in promotions
    try {
        $promo_ids = array_column($promo_products, 'product_id');
        $promo_names = $seen_names;
        
        $exclude_id_clause = !empty($promo_ids) ? "AND p.product_id NOT IN (" . implode(',', $promo_ids) . ")" : "";
        
        $featured_stmt = $pdo->query("SELECT p.product_id, p.name as product_name, p.base_price, p.status, p.created_at,
                                            c.name as category_name, b.brand_name, p.image_url
                                     FROM Product p 
                                     JOIN Category c ON p.category_id = c.category_id 
                                     JOIN Brand b ON p.brand_id = b.brand_id 
                                     WHERE p.status = 'active' $exclude_id_clause
                                     ORDER BY p.created_at DESC");
        $raw_featured = $featured_stmt->fetchAll();
        
        $featured_products = [];
        $featured_names = [];
        foreach ($raw_featured as $rf) {
            $name = trim($rf['product_name']);
            $lower_name = strtolower($name);
            // Exclude if already in promo or already in featured
            if (!in_array($lower_name, $promo_names) && !in_array($lower_name, $featured_names)) {
                $rf['product_name'] = $name; // Ensure trimmed name
                $featured_products[] = $rf;
                $featured_names[] = $lower_name;
            }
            if (count($featured_products) >= 4) break;
        }
    } catch (Exception $e) {
        $featured_products = [];
    }
}

// Fallback to hardcoded products if DB empty or failed
if (empty($promo_products)) {
    // Pick different products for promo than featured to avoid overlap
    $promo_products = [];
    $seen_names = [];
    // Start from middle of global products for promo
    $potential_promo = array_slice($global_products, 2); 
    foreach ($potential_promo as $pp) {
        $name = trim($pp['product_name']);
        $lower_name = strtolower($name);
        if (!in_array($lower_name, $seen_names)) {
            $pp['promo_name'] = 'Special Offer';
            $pp['discount_value'] = 15;
            $pp['discount_type'] = 'percentage';
            $promo_products[] = $pp;
            $seen_names[] = $lower_name;
        }
        if (count($promo_products) >= 4) break;
    }
}

if (empty($featured_products)) {
    $promo_ids = array_column($promo_products, 'product_id');
    $promo_names = array_map(function($p) { return strtolower(trim($p['product_name'])); }, $promo_products);
    
    $featured_products = [];
    $featured_names = [];
    foreach ($global_products as $gp) {
        $name = trim($gp['product_name']);
        $lower_name = strtolower($name);
        if (!in_array($gp['product_id'], $promo_ids) && !in_array($lower_name, $promo_names) && !in_array($lower_name, $featured_names)) {
            $featured_products[] = $gp;
            $featured_names[] = $lower_name;
        }
        if (count($featured_products) >= 4) break;
    }
}

// Ensure images and stock from config.php/DB are used even if DB has different paths
foreach ($featured_products as &$product) {
    $product['stock'] = getProductStock($product['product_id'], $product['stock'] ?? 0);
    foreach ($global_products as $gp) {
        if ($product['product_id'] == $gp['product_id'] || $product['product_name'] == $gp['product_name']) {
            $product['image_url'] = $gp['image_url'];
            break;
        }
    }
}
foreach ($promo_products as &$product) {
    $product['stock'] = getProductStock($product['product_id'], $product['stock'] ?? 0);
    foreach ($global_products as $gp) {
        if ($product['product_id'] == $gp['product_id'] || $product['product_name'] == $gp['product_name']) {
            $product['image_url'] = $gp['image_url'];
            break;
        }
    }
}
?>

<!-- Hero Section -->
<section class="bg-dark text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-20 bg-[url('https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80')] bg-cover bg-center"></div>
    <div class="container mx-auto px-4 py-24 relative z-10 flex flex-col md:flex-row items-center justify-between">
        <div class="md:w-1/2 space-y-8 animate-fade-in-up">
            <h1 class="text-5xl md:text-7xl font-extrabold leading-tight tracking-tight">
                Upgrade Your <span class="text-primary underline decoration-primary decoration-4 underline-offset-8">Rig</span> Today
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 leading-relaxed max-w-lg">
                High-performance gear for gamers and professionals. 
                <span class="block mt-4 text-primary font-bold"><i class="fas fa-store mr-2"></i> Store Pick-up Only | Pre-order Available</span>
            </p>
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                <a href="products.php" class="bg-primary hover:bg-green-600 text-white px-10 py-4 rounded-lg text-lg font-bold transition-all transform hover:scale-105 shadow-xl flex items-center justify-center">
                    Shop Now <i class="fas fa-arrow-right ml-3"></i>
                </a>
                <a href="#categories" class="border-2 border-primary text-primary hover:bg-primary hover:text-white px-10 py-4 rounded-lg text-lg font-bold transition-all flex items-center justify-center">
                    Browse Categories
                </a>
            </div>
        </div>
        <div class="md:w-1/2 mt-16 md:mt-0 flex justify-center">
            <div class="relative w-full max-w-lg animate-float">
                <div class="absolute -inset-4 bg-primary rounded-full blur-3xl opacity-30 animate-pulse"></div>
                <img src="https://images.unsplash.com/photo-1587831990711-23ca6441447b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Gaming PC" class="relative rounded-2xl shadow-2xl border border-green-700/50">
            </div>
        </div>
    </div>
</section>

<!-- Category Icons -->
<section id="categories" class="py-20 bg-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold mb-4">Shop By <span class="text-primary">Category</span></h2>
        <div class="w-24 h-1.5 bg-primary mx-auto mb-16 rounded-full"></div>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
            <a href="products.php?category=Laptops" class="group flex flex-col items-center p-8 bg-gray-50 rounded-2xl border-2 border-transparent hover:border-primary hover:shadow-2xl transition-all transform hover:-translate-y-2">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-primary mb-6 group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                    <i class="fas fa-laptop text-4xl"></i>
                </div>
                <span class="text-lg font-bold text-gray-700">Laptops</span>
            </a>
            <a href="products.php?category=Pre-built PC" class="group flex flex-col items-center p-8 bg-gray-50 rounded-2xl border-2 border-transparent hover:border-primary hover:shadow-2xl transition-all transform hover:-translate-y-2">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-primary mb-6 group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                    <i class="fas fa-desktop text-4xl"></i>
                </div>
                <span class="text-lg font-bold text-gray-700">Pre-built PCs</span>
            </a>
            <a href="products.php?category=Peripherals" class="group flex flex-col items-center p-8 bg-gray-50 rounded-2xl border-2 border-transparent hover:border-primary hover:shadow-2xl transition-all transform hover:-translate-y-2">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-primary mb-6 group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                    <i class="fas fa-keyboard text-4xl"></i>
                </div>
                <span class="text-lg font-bold text-gray-700">Peripherals</span>
            </a>
            <a href="products.php?category=Monitors" class="group flex flex-col items-center p-8 bg-gray-50 rounded-2xl border-2 border-transparent hover:border-primary hover:shadow-2xl transition-all transform hover:-translate-y-2">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-primary mb-6 group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                    <i class="fas fa-tv text-4xl"></i>
                </div>
                <span class="text-lg font-bold text-gray-700">Monitors</span>
            </a>
            <a href="products.php?category=CPU" class="group flex flex-col items-center p-8 bg-gray-50 rounded-2xl border-2 border-transparent hover:border-primary hover:shadow-2xl transition-all transform hover:-translate-y-2">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-primary mb-6 group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                    <i class="fas fa-microchip text-4xl"></i>
                </div>
                <span class="text-lg font-bold text-gray-700">CPUs</span>
            </a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <!-- Rule 4: Promotions Section -->
        <?php if (!empty($promo_products)): ?>
        <div class="mb-24">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-12">
                <div>
                    <h2 class="text-4xl font-bold">Limited <span class="text-primary">Promotions</span></h2>
                    <p class="text-gray-600 mt-2 text-lg">Grab these exclusive deals before they're gone</p>
                </div>
                <a href="products.php" class="text-primary font-bold hover:underline flex items-center text-lg whitespace-nowrap">
                    View All Deals <i class="fas fa-chevron-right ml-2 text-sm"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                <?php foreach ($promo_products as $index => $product): ?>
                <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 group transition-all hover:shadow-2xl hover:border-primary animate-fade-in-up" style="animation-delay: <?php echo ($index * 100); ?>ms;">
                    <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="block relative overflow-hidden aspect-square bg-gray-100">
                        <div class="absolute top-0 right-0 z-20">
                            <div class="bg-primary text-white px-4 py-2 font-black text-sm rounded-bl-2xl shadow-lg">
                                -<?php echo $product['discount_type'] == 'percentage' ? $product['discount_value'].'%' : '₱'.number_format($product['discount_value']); ?>
                            </div>
                        </div>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                    </a>
                    <div class="p-6">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="bg-green-100 text-primary text-[10px] font-bold px-2.5 py-1 rounded-full uppercase"><?php echo htmlspecialchars($product['promo_name']); ?></span>
                        </div>
                        <h3 class="text-base font-bold text-gray-800 mb-3 group-hover:text-primary transition-colors line-clamp-2 min-h-[3rem]">
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </a>
                        </h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-black text-primary">₱<?php 
                                    $final_price = $product['base_price'];
                                    if ($product['discount_type'] == 'percentage') {
                                        $final_price -= ($final_price * ($product['discount_value'] / 100));
                                    } else {
                                        $final_price -= $product['discount_value'];
                                    }
                                    echo number_format($final_price, 2); 
                                ?></p>
                                <p class="text-sm text-gray-400 line-through">₱<?php echo number_format($product['base_price'], 2); ?></p>
                            </div>
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="bg-primary hover:bg-green-600 text-white p-3 rounded-xl transition-all shadow-md active:scale-90 flex-shrink-0 btn-animate" title="View details" aria-label="View product details">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-12">
            <div>
                <h2 class="text-4xl font-bold">Featured <span class="text-primary">Gear</span></h2>
                <p class="text-gray-600 mt-2 text-lg">Top-selling products handpicked for you</p>
            </div>
            <a href="products.php" class="text-primary font-bold hover:underline flex items-center text-lg whitespace-nowrap">
                View All <i class="fas fa-chevron-right ml-2 text-sm"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
            <?php foreach ($featured_products as $index => $product): ?>
            <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 group transition-all hover:shadow-2xl hover:border-primary animate-fade-in-up" style="animation-delay: <?php echo ($index * 100); ?>ms;">
                <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="block relative overflow-hidden aspect-square bg-gray-100">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                    <?php if (isset($product['is_preorder']) && $product['is_preorder']): ?>
                    <div class="absolute top-4 left-4 animate-bounce-subtle">
                        <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg flex items-center whitespace-nowrap">
                            <i class="fas fa-clock mr-1.5"></i> Pre-order
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="absolute top-4 left-4">
                        <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">New</span>
                    </div>
                    <?php endif; ?>
                </a>
                <div class="p-6 flex flex-col h-full">
                    <p class="text-primary text-xs font-bold uppercase tracking-widest mb-2"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    <h3 class="text-base font-bold text-gray-800 mb-3 group-hover:text-primary transition-colors line-clamp-2 min-h-[3rem]">
                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </a>
                    </h3>
                    <div class="flex items-center text-yellow-400 mb-4">
                        <i class="fas fa-star text-xs"></i>
                        <i class="fas fa-star text-xs"></i>
                        <i class="fas fa-star text-xs"></i>
                        <i class="fas fa-star text-xs"></i>
                        <i class="fas fa-star text-xs"></i>
                        <span class="text-gray-400 text-xs ml-2">(5.0)</span>
                    </div>
                    <div class="flex items-center justify-between mt-auto">
                        <span class="text-2xl font-bold text-dark">₱<?php echo number_format($product['base_price'], 2); ?></span>
                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="bg-primary hover:bg-green-600 text-white p-3 rounded-xl transition-all shadow-md active:scale-90 flex-shrink-0 btn-animate" title="View details" aria-label="View product details">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="py-24 bg-dark text-white">
    <div class="container mx-auto px-4 max-w-4xl text-center">
        <div class="bg-green-800/30 p-12 rounded-3xl border border-green-700/50">
            <h2 class="text-4xl font-bold mb-6">Stay Updated with <span class="text-primary">New Gear</span></h2>
            <p class="text-xl text-gray-300 mb-10 leading-relaxed">Join our newsletter to receive exclusive deals, hardware reviews, and setup inspiration directly to your inbox.</p>
            <form class="flex flex-col md:flex-row gap-4 max-w-2xl mx-auto">
                <input type="email" placeholder="Enter your email address" class="flex-grow px-6 py-4 rounded-xl bg-green-900/50 border border-green-700 focus:outline-none focus:border-primary text-white text-lg">
                <button type="submit" class="bg-primary hover:bg-green-600 text-white px-10 py-4 rounded-xl font-bold transition-all shadow-lg text-lg">
                    Subscribe
                </button>
            </form>
        </div>
    </div>
</section>

<style>
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    .animate-fade-in-up { animation: fade-in-up 1s ease-out forwards; }
    .animate-float { animation: float 6s ease-in-out infinite; }
</style>

<?php include 'includes/footer.php'; ?>
