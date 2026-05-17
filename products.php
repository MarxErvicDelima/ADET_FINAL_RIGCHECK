<?php 
require_once 'includes/config.php';

include 'includes/header.php'; 

// Get filter parameters
$category_filter = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;
$specs_filter = isset($_GET['specs']) ? htmlspecialchars($_GET['specs']) : '';

// Use global hardcoded products
$products = $global_products;

// Apply category filter
if ($category_filter) {
    $products = array_filter($products, function($p) use ($category_filter) {
        return stripos($p['category_name'], $category_filter) !== false;
    });
}

// Apply price range filter
$products = array_filter($products, function($p) use ($min_price, $max_price) {
    return $p['base_price'] >= $min_price && $p['base_price'] <= $max_price;
});

// Apply specs filter
if ($specs_filter) {
    $products = array_filter($products, function($p) use ($specs_filter) {
        $specs = json_decode($p['specs'] ?? '{}', true);
        $specs_str = implode(' ', array_keys($specs)) . ' ' . implode(' ', array_values($specs));
        return stripos($specs_str, $specs_filter) !== false || stripos($p['product_name'], $specs_filter) !== false;
    });
}

// Re-index array
$products = array_values($products);

// Get specs suggestions for popular filter options
$specs_suggestions = [
    'RAM' => ['8GB', '16GB', '32GB', '64GB'],
    'GPU' => ['RTX 4090', 'RTX 4080', 'RTX 4070', 'RTX 4060', 'RTX 3080'],
    'Resolution' => ['1920x1080', '2560x1440', '3840x2160', '1440p', '4K'],
    'Processor' => ['Core i9', 'Core i7', 'Core i5', 'Ryzen 9', 'Ryzen 7'],
    'Panel Type' => ['IPS', 'VA', 'OLED', 'Mini-LED'],
    'Screen Size' => ['27 inches', '32 inches', '34 inches', '42 inches'],
];
?>

<section class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Breadcrumbs & Active Filters -->
        <div class="mb-10">
            <nav class="flex mb-4 text-gray-500 text-sm">
                <a href="index.php" class="hover:text-primary transition-colors">Home</a>
                <span class="mx-3 text-gray-400">/</span>
                <span class="text-gray-900 font-bold gradient-text"><?php echo $category_filter ?: 'Products'; ?></span>
            </nav>
            
            <!-- Active Filters Display -->
            <?php if ($category_filter || $min_price > 0 || $max_price < 999999 || $specs_filter): ?>
            <div class="glass-card border border-blue-200 rounded-xl p-4 flex flex-wrap items-center gap-3">
                <span class="text-sm font-semibold text-blue-700"><i class="fas fa-filter mr-2"></i> Active Filters:</span>
                
                <?php if ($category_filter): ?>
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-2">
                        <i class="fas fa-tag text-xs"></i> <?php echo htmlspecialchars($category_filter); ?>
                        <a href="products.php<?php echo ($min_price > 0 || $max_price < 999999 || $specs_filter) ? '?' : ''; ?><?php echo $min_price > 0 ? '&min_price=' . $min_price : ''; ?><?php echo $max_price < 999999 ? '&max_price=' . $max_price : ''; ?><?php echo $specs_filter ? '&specs=' . urlencode($specs_filter) : ''; ?>" class="hover:text-blue-900 transition">✕</a>
                    </span>
                <?php endif; ?>
                
                <?php if ($min_price > 0 || $max_price < 999999): ?>
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-2">
                        <i class="fas fa-money-bill-wave text-xs"></i> ₱<?php echo number_format($min_price, 0); ?> - ₱<?php echo number_format($max_price, 0); ?>
                        <a href="products.php<?php echo $category_filter || $specs_filter ? '?' : ''; ?><?php echo $category_filter ? 'category=' . urlencode($category_filter) : ''; ?><?php echo $specs_filter ? ($category_filter ? '&' : '') . 'specs=' . urlencode($specs_filter) : ''; ?>" class="hover:text-blue-900 transition">✕</a>
                    </span>
                <?php endif; ?>
                
                <?php if ($specs_filter): ?>
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-2">
                        <i class="fas fa-sliders-h text-xs"></i> <?php echo htmlspecialchars($specs_filter); ?>
                        <a href="products.php<?php echo $category_filter || $min_price > 0 || $max_price < 999999 ? '?' : ''; ?><?php echo $category_filter ? 'category=' . urlencode($category_filter) : ''; ?><?php echo ($min_price > 0 || $max_price < 999999) ? ($category_filter ? '&' : '') . 'min_price=' . $min_price . '&max_price=' . $max_price : ''; ?>" class="hover:text-blue-900 transition">✕</a>
                    </span>
                <?php endif; ?>
                
                <a href="products.php" class="ml-auto text-sm text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                    <i class="fas fa-times"></i> Clear All Filters
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex flex-col md:flex-row gap-12">
            <!-- Sidebar Filters -->
            <aside class="md:w-1/4 space-y-6">
                <!-- Category Filter -->
                <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold mb-6 border-l-4 border-primary pl-3 text-gray-800">
                        <i class="fas fa-list mr-2 text-primary"></i> Categories
                    </h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="products.php" class="flex justify-between items-center text-sm <?php echo !$category_filter ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                All Products <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=Laptops" class="flex justify-between items-center text-sm <?php echo $category_filter == 'Laptops' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                Laptops <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=Pre-built PC" class="flex justify-between items-center text-sm <?php echo $category_filter == 'Pre-built PC' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                Pre-built PCs <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=Monitors" class="flex justify-between items-center text-sm <?php echo $category_filter == 'Monitors' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                Monitors <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=CPU" class="flex justify-between items-center text-sm <?php echo $category_filter == 'CPU' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                CPUs <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=Mouse" class="flex justify-between items-center text-sm <?php echo $category_filter == 'Mouse' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                Mouse <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=Keyboard" class="flex justify-between items-center text-sm <?php echo $category_filter == 'Keyboard' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                Keyboard <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=Headset" class="flex justify-between items-center text-sm <?php echo $category_filter == 'Headset' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                Headset <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                        <li>
                            <a href="products.php?category=Speaker" class="flex justify-between items-center text-sm <?php echo $category_filter == 'Speaker' ? 'text-primary font-bold bg-green-50 -mx-4 px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-primary'; ?> transition-colors group">
                                Speaker <i class="fas fa-chevron-right text-[10px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Price Range Filter -->
                <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold mb-6 border-l-4 border-primary pl-3 text-gray-800">
                        <i class="fas fa-tag mr-2 text-primary"></i> Price Range
                    </h3>
                    <form method="GET" action="products.php" id="price-filter-form" class="space-y-4">
                        <!-- Keep category filter if set -->
                        <?php if ($category_filter): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                        <?php endif; ?>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm text-gray-600 font-semibold mb-3 block">Price Range: <span class="text-primary">₱<span id="min-display"><?php echo $min_price > 0 ? number_format($min_price, 0) : '0'; ?></span> - ₱<span id="max-display"><?php echo $max_price < 999999 ? number_format($max_price, 0) : '999,999'; ?></span></span></label>
                                <div class="relative pt-4 pb-2">
                                    <input type="range" name="min_price" id="min-slider" min="0" max="300000" step="5000" value="<?php echo $min_price > 0 ? $min_price : 0; ?>" class="absolute w-full h-2 bg-transparent rounded-lg appearance-none cursor-pointer pointer-events-none" style="z-index: 6;">
                                    <input type="range" name="max_price" id="max-slider" min="0" max="300000" step="5000" value="<?php echo $max_price < 999999 ? $max_price : 300000; ?>" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary" style="z-index: 4;">
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-3">
                                    <span>₱0</span>
                                    <span>₱300,000+</span>
                                </div>
                            </div>
                        </div>
                        
                        <style>
                            #min-slider::-webkit-slider-thumb {
                                appearance: none;
                                width: 18px;
                                height: 18px;
                                border-radius: 50%;
                                background: #10b981;
                                cursor: pointer;
                                border: 3px solid white;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                pointer-events: auto;
                                z-index: 7;
                            }

                            #min-slider::-moz-range-thumb {
                                width: 18px;
                                height: 18px;
                                border-radius: 50%;
                                background: #10b981;
                                cursor: pointer;
                                border: 3px solid white;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                pointer-events: auto;
                                z-index: 7;
                            }

                            #max-slider::-webkit-slider-thumb {
                                appearance: none;
                                width: 18px;
                                height: 18px;
                                border-radius: 50%;
                                background: #10b981;
                                cursor: pointer;
                                border: 3px solid white;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                pointer-events: auto;
                            }

                            #max-slider::-moz-range-thumb {
                                width: 18px;
                                height: 18px;
                                border-radius: 50%;
                                background: #10b981;
                                cursor: pointer;
                                border: 3px solid white;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                pointer-events: auto;
                            }

                            #min-slider::-webkit-slider-runnable-track {
                                background: transparent;
                                height: 8px;
                                border-radius: 4px;
                            }

                            #min-slider::-moz-range-track {
                                background: transparent;
                                border: none;
                            }
                        </style>
                        <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-green-600 transition-all shadow-md active:scale-95 flex items-center justify-center gap-2 btn-animate">
                            <i class="fas fa-search text-sm"></i> Apply Price Filter
                        </button>
                        <?php if ($min_price > 0 || $max_price < 999999): ?>
                            <a href="products.php<?php echo $category_filter ? '?category=' . urlencode($category_filter) : ''; ?>" class="w-full bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-200 transition-all text-center text-sm">
                                <i class="fas fa-times mr-1"></i> Clear Price Filter
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- Price range display -->
                    <?php if ($min_price > 0 || $max_price < 999999): ?>
                        <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                            <p class="text-sm text-gray-700 font-semibold">
                                <i class="fas fa-filter text-primary mr-2"></i>
                                Showing: ₱<?php echo number_format($min_price, 0); ?> - ₱<?php echo number_format($max_price, 0); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Specs Filter -->
                <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold mb-6 border-l-4 border-primary pl-3 text-gray-800">
                        <i class="fas fa-sliders-h mr-2 text-primary"></i> Specifications
                    </h3>
                    <form method="GET" action="products.php" id="specs-filter-form" class="space-y-4">
                        <!-- Keep other filters if set -->
                        <?php if ($category_filter): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                        <?php endif; ?>
                        <?php if ($min_price > 0): ?>
                            <input type="hidden" name="min_price" value="<?php echo $min_price; ?>">
                        <?php endif; ?>
                        <?php if ($max_price < 999999): ?>
                            <input type="hidden" name="max_price" value="<?php echo $max_price; ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="text-sm text-gray-600 font-semibold mb-3 block">Filter by Spec</label>
                            <input type="text" name="specs" placeholder="e.g., RTX 4090, 16GB, 4K, IPS..." value="<?php echo htmlspecialchars($specs_filter); ?>" class="w-full p-3 border border-gray-200 rounded-lg text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-green-100 transition-all placeholder-gray-400">
                            <p class="text-xs text-gray-400 mt-2">Search any specification or component</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" class="col-span-2 bg-primary text-white py-2 rounded-lg font-bold hover:bg-green-600 transition-all shadow-md text-sm flex items-center justify-center gap-2 btn-animate">
                                <i class="fas fa-search text-xs"></i> Search Specs
                            </button>
                        </div>

                        <!-- Quick filter buttons -->
                        <div class="border-t border-gray-100 pt-4 mt-4">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Popular Specs:</p>
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-1">
                                    <a href="products.php?specs=16GB<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>" class="text-[11px] px-2 py-1 bg-gray-100 hover:bg-primary hover:text-white rounded-lg transition-all border border-gray-200 font-semibold">16GB RAM</a>
                                    <a href="products.php?specs=RTX%204090<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>" class="text-[11px] px-2 py-1 bg-gray-100 hover:bg-primary hover:text-white rounded-lg transition-all border border-gray-200 font-semibold">RTX 4090</a>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <a href="products.php?specs=4K<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>" class="text-[11px] px-2 py-1 bg-gray-100 hover:bg-primary hover:text-white rounded-lg transition-all border border-gray-200 font-semibold">4K</a>
                                    <a href="products.php?specs=OLED<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>" class="text-[11px] px-2 py-1 bg-gray-100 hover:bg-primary hover:text-white rounded-lg transition-all border border-gray-200 font-semibold">OLED</a>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <a href="products.php?specs=Core%20i9<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>" class="text-[11px] px-2 py-1 bg-gray-100 hover:bg-primary hover:text-white rounded-lg transition-all border border-gray-200 font-semibold">Core i9</a>
                                    <a href="products.php?specs=32GB<?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>" class="text-[11px] px-2 py-1 bg-gray-100 hover:bg-primary hover:text-white rounded-lg transition-all border border-gray-200 font-semibold">32GB</a>
                                </div>
                            </div>
                        </div>

                        <?php if ($specs_filter): ?>
                            <a href="products.php<?php echo $category_filter ? '?category=' . urlencode($category_filter) : ''; ?>" class="w-full bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-200 transition-all text-center text-sm block">
                                <i class="fas fa-times mr-1"></i> Clear Specs Filter
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </aside>

            <!-- Product Grid -->
            <main class="md:w-3/4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">
                            <i class="fas fa-shopping-bag text-primary mr-3"></i>
                            <?php 
                                $title = 'All Products';
                                if ($category_filter) $title = htmlspecialchars($category_filter);
                                echo $title;
                            ?>
                        </h1>
                        <p class="text-gray-500 text-sm mt-2">
                            <span class="font-bold text-primary"><?php echo count($products); ?></span> 
                            item<?php echo count($products) != 1 ? 's' : ''; ?> available
                            <?php if ($min_price > 0 || $max_price < 999999 || $specs_filter): ?>
                                <span class="text-blue-600 font-semibold">• Filtered</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-4 w-full sm:w-auto">
                        <span class="text-gray-500 text-sm whitespace-nowrap">Sort by:</span>
                        <select id="sort-select" class="flex-1 sm:flex-initial bg-white border border-gray-200 p-3 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer shadow-sm transition-all">
                            <option value="newest">Newest Arrivals</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                            <option value="popularity">Popularity</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="bg-white rounded-3xl p-20 text-center shadow-sm border border-gray-100 animate-fade-in-up">
                        <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center text-primary mx-auto mb-8 shadow-inner">
                            <i class="fas fa-box-open text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">No Products Found</h2>
                        <p class="text-gray-500 mb-8 text-lg">
                            <?php 
                                if ($category_filter || $min_price > 0 || $max_price < 999999 || $specs_filter) {
                                    echo "We couldn't find any products matching your filters. Try adjusting your criteria.";
                                } else {
                                    echo "No products available at the moment. Check back soon!";
                                }
                            ?>
                        </p>
                        <a href="products.php" class="bg-primary text-white px-10 py-4 rounded-xl font-bold hover:bg-green-600 transition-all shadow-lg inline-block">
                            <i class="fas fa-home mr-2"></i> Browse All Products
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                        <?php foreach ($products as $product): ?>
                            <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 group transition-all hover:shadow-2xl hover:border-primary animate-fade-in-up product-card">
                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="block relative overflow-hidden aspect-square bg-gray-100">
                                    <img src="<?php echo isset($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80'; ?>" alt="<?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Product'); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                                    <?php if (isset($product['is_preorder']) && $product['is_preorder']): ?>
                                        <div class="absolute top-4 left-4 animate-bounce-subtle">
                                            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg flex items-center whitespace-nowrap">
                                                <i class="fas fa-clock mr-1.5"></i> Pre-order
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($product['stock'] <= 0): ?>
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                            <span class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold">Out of Stock</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="p-6 flex flex-col h-full">
                                    <p class="text-primary text-xs font-bold uppercase tracking-widest mb-2"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                    <h3 class="text-base font-bold text-gray-800 mb-2 group-hover:text-primary transition-colors line-clamp-2 min-h-[3rem]">
                                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                                            <?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Product'); ?>
                                        </a>
                                    </h3>
                                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                                        <p class="text-gray-400 text-xs font-medium"><?php echo htmlspecialchars($product['brand_name']); ?></p>
                                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full <?php echo $product['stock'] > 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
                                            <?php echo $product['stock'] > 0 ? $product['stock'] . ' left' : 'Out'; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between mt-auto">
                                        <div>
                                            <p class="text-gray-500 text-xs mb-1">Price</p>
                                            <p class="text-2xl font-black text-dark">₱<?php echo number_format($product['base_price'], 2); ?></p>
                                        </div>
                                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="bg-primary hover:bg-green-600 text-white p-3 rounded-xl transition-all shadow-md active:scale-90 flex-shrink-0 btn-animate" title="View details" aria-label="View product details">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<script>
    // Sorting functionality
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            // In a real implementation, this would submit to server
            // For now, it's a placeholder for frontend sorting
            console.log('Sort by:', e.target.value);
        });
    }

    // Price Range Slider functionality
    const minSlider = document.getElementById('min-slider');
    const maxSlider = document.getElementById('max-slider');
    const minDisplay = document.getElementById('min-display');
    const maxDisplay = document.getElementById('max-display');
    const priceForm = document.getElementById('price-filter-form');

    if (minSlider && maxSlider) {
        const updateSliders = () => {
            let minVal = parseInt(minSlider.value);
            let maxVal = parseInt(maxSlider.value);

            // Prevent crossing
            if (minVal > maxVal) {
                [minSlider.value, maxSlider.value] = [maxSlider.value, minSlider.value];
                minVal = parseInt(minSlider.value);
                maxVal = parseInt(maxSlider.value);
            }

            // Update display
            minDisplay.textContent = minVal.toLocaleString();
            maxDisplay.textContent = maxVal.toLocaleString();
        };

        minSlider.addEventListener('input', updateSliders);
        maxSlider.addEventListener('input', updateSliders);

        // Auto-submit on slider change (optional - remove if you prefer manual submit)
        minSlider.addEventListener('change', () => priceForm.submit());
        maxSlider.addEventListener('change', () => priceForm.submit());
    }
</script>

<?php include 'includes/footer.php'; ?>
