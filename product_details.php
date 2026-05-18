<?php 
require_once 'includes/config.php';

include 'includes/header.php'; 

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

// Ensure database connection is available
if ($pdo === null) {
    // We can still show hardcoded product info even if DB is down
}

$product_id = (int)$_GET['id'];

// Use global hardcoded products
$all_products = $global_products;

// Find product in hardcoded list
$product = null;
foreach ($all_products as $p) {
    if ($p['product_id'] === $product_id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    header('Location: products.php');
    exit();
}

// Update product stock from database if available (same as in products.php)
$product['stock'] = getProductStock($product['product_id'], $product['stock']);

// Mock variants and reviews since we're hardcoding
$variants = [
    ['variant_id' => $product_id, 'product_id' => $product_id, 'color_name' => 'Standard', 'stock' => $product['stock'], 'price_override' => null, 'sku' => 'SKU-' . $product_id]
];

$specs = json_decode($product['specs'] ?? '{}', true);

// Product-specific technical specifications, reviews, and images
$productData = [
    201 => [
        'image' => 'product-images/AKA05359.jpg',
        'specs' => [
            'Processor' => 'AMD Ryzen 9 8945HS',
            'RAM' => '32GB LPDDR5X-6400MHz',
            'GPU' => 'NVIDIA GeForce RTX 4090 Laptop GPU (16GB GDDR6)',
            'Storage' => '1TB PCIe 4.0 NVMe M.2 SSD',
            'Display' => '14" QHD+ (2560 x 1600) OLED, 120Hz',
            'Battery' => '73Wh, Up to 10 hours',
            'Weight' => '1.50 kg',
            'OS' => 'Windows 11 Home'
        ],
        'reviews' => [
            ['name' => 'David Kim', 'rating' => 5, 'comment' => 'The best 14-inch gaming laptop period. The OLED screen is breathtaking.'],
            ['name' => 'Lisa Chen', 'rating' => 5, 'comment' => 'Incredible power in such a small form factor. ASUS really nailed the design this year.'],
            ['name' => 'Carlos Rodriguez', 'rating' => 4, 'comment' => 'Runs a bit hot under heavy load, but the performance is undeniable.']
        ]
    ],
    202 => [
        'image' => 'product-images/lg.jpeg',
        'specs' => [
            'Resolution' => '3440 x 1440 (UltraWide QHD)',
            'Panel' => 'Nano IPS with ATW Polarizer',
            'Refresh Rate' => '144Hz (Overclockable to 180Hz)',
            'Response Time' => '1ms (GtG)',
            'HDR' => 'VESA DisplayHDR 600',
            'G-SYNC' => 'NVIDIA G-SYNC Ultimate',
            'Color' => 'DCI-P3 98% (Typ.)'
        ],
        'reviews' => [
            ['name' => 'Jason Miller', 'rating' => 5, 'comment' => 'Best ultrawide monitor I have ever used. The colors are so accurate and vibrant.'],
            ['name' => 'Sarah White', 'rating' => 5, 'comment' => 'G-SYNC Ultimate makes gaming so smooth. Highly recommended for enthusiasts.']
        ]
    ],
    203 => [
        'image' => 'product-images/70c37199-2b2c-40fd-b716-220f5a73ba6c.jpg',
        'specs' => [
            'CPU' => 'Intel Core i9-12900K',
            'GPU' => 'NVIDIA GeForce RTX 3080 Ti',
            'RAM' => '64GB (2x32GB) DDR5-4800MHz',
            'Storage' => '2TB M.2 NVMe Gen4 SSD',
            'Cooling' => 'Liquid Cooling for CPU and GPU',
            'PSU' => '750W 80 Plus Platinum SFX',
            'Case' => 'Compact 12L Form Factor'
        ],
        'reviews' => [
            ['name' => 'Alex Turner', 'rating' => 5, 'comment' => 'It is tiny but so powerful. Quiet too, even when gaming in 4K.'],
            ['name' => 'Emma Watson', 'rating' => 5, 'comment' => 'Premium build quality and amazing performance. Worth the premium price.']
        ]
    ],
    204 => [
        'image' => 'product-images/intel-core-i9-13900K-review-01.jpg',
        'specs' => [
            'Total Cores' => '24 (8 Performance-cores, 16 Efficient-cores)',
            'Total Threads' => '32',
            'Max Turbo Frequency' => '5.80 GHz',
            'Intel Smart Cache' => '36 MB',
            'Processor Base Power' => '125 W',
            'Max Turbo Power' => '253 W',
            'Socket' => 'LGA1700'
        ],
        'reviews' => [
            ['name' => 'Mark Spencer', 'rating' => 5, 'comment' => 'Absolute beast for both gaming and heavy multitasking. Best in class.'],
            ['name' => 'Jessica Lee', 'rating' => 4, 'comment' => 'Performance is insane, but it requires a very high-end cooling solution.']
        ]
    ],
    205 => [
        'image' => 'product-images/jerTCgwTP2nbkyLZbK7GmW-1200-80.jpg',
        'specs' => [
            'Sensor' => 'Focus Pro 30K Optical Sensor',
            'Max Sensitivity' => '30000 DPI',
            'Max Speed' => '750 IPS',
            'Max Acceleration' => '70 G',
            'Switch Type' => 'Optical Mouse Switches Gen-3',
            'Weight' => '63g (Ultra-lightweight)',
            'Battery Life' => 'Up to 90 hours'
        ],
        'reviews' => [
            ['name' => 'Ryan Gosling', 'rating' => 5, 'comment' => 'The most comfortable and precise gaming mouse I have ever used.'],
            ['name' => 'Chris Pratt', 'rating' => 5, 'comment' => 'So light it feels like an extension of my hand. Highly recommended.']
        ]
    ],
    206 => [
        'image' => 'product-images/51ouYYPtx-L._AC_UF1000,1000_QL80_.jpg',
        'specs' => [
            'Switches' => 'CORSAIR OPX Optical-Mechanical',
            'Backlighting' => 'Individually addressable RGB',
            'Media Keys' => 'Dedicated with Volume Roller',
            'Polling Rate' => 'Up to 4000Hz (AXON)',
            'Macro Keys' => '6 Dedicated G-keys',
            'Keycaps' => 'PBT Double-Shot',
            'Wrist Rest' => 'Magnetic Detachable'
        ],
        'reviews' => [
            ['name' => 'Mark Wilson', 'rating' => 5, 'comment' => 'The best keyboard I have ever owned. The optical switches feel amazing.'],
            ['name' => 'Sarah Connor', 'rating' => 5, 'comment' => 'The RGB lighting is stunning and the dedicated macro keys are very useful.']
        ]
    ],
    207 => [
        'image' => 'product-images/Steelseries-Arctis-Nova-Pro-main.webp',
        'specs' => [
            'Drivers' => 'Premium High Fidelity Drivers',
            'ANC' => '4-mic Active Noise Cancellation',
            'Wireless' => 'Dual Wireless (2.4GHz + Bluetooth)',
            'Audio System' => 'Infinity Power System (Hot-swap batteries)',
            'Mic' => 'ClearCast Gen 2 Retractable Mic',
            'Compatibility' => 'PC, Mac, PlayStation, Switch'
        ],
        'reviews' => [
            ['name' => 'Ben Affleck', 'rating' => 5, 'comment' => 'Sound quality is superb and the swappable batteries are a game changer.'],
            ['name' => 'Jenna Ortega', 'rating' => 5, 'comment' => 'Most comfortable headset for long sessions. ANC works great.']
        ]
    ],
    208 => [
        'image' => 'product-images/pdt-mhl-gigaworks_t40_series_II.jpg',
        'specs' => [
            'System' => '2.0 Speaker System',
            'Power' => '32W RMS (16W per satellite)',
            'Drivers' => 'Dual Woven Glass Fiber Cone',
            'Technology' => 'BasXPort Technology for Enhanced Bass',
            'Controls' => 'Bas, Treble, and Volume adjustment',
            'Input' => '3.5mm Aux-in, Headphone-out'
        ],
        'reviews' => [
            ['name' => 'Peter Parker', 'rating' => 5, 'comment' => 'Excellent sound quality for desktop speakers. Deep bass without a subwoofer.'],
            ['name' => 'Tony Stark', 'rating' => 4, 'comment' => 'Solid performance and design. Great for music and casual gaming.']
        ]
    ],
    209 => [
        'image' => 'product-images/dell.jpeg',
        'specs' => [
            'Processor' => 'Intel Core i7-1260P (12-Core, 18MB Cache)',
            'RAM' => '16GB LPDDR5-5200MHz',
            'Storage' => '512GB M.2 PCIe Gen4 SSD',
            'Display' => '13.4" FHD+ (1920 x 1200) InfinityEdge, 500 nits',
            'Graphics' => 'Intel Iris Xe Graphics',
            'Keyboard' => 'Zero-lattice capacitive touch row',
            'Ports' => '2 x Thunderbolt 4 (USB-C)'
        ],
        'reviews' => [
            ['name' => 'Steve Jobs', 'rating' => 5, 'comment' => 'The design is minimalist and beautiful. Performance is snappy for everyday tasks.'],
            ['name' => 'Bill Gates', 'rating' => 4, 'comment' => 'Great productivity machine. The new touch row takes some time to get used to.']
        ]
    ],
    210 => [
        'image' => 'product-images/dell3.avif',
        'specs' => [
            'Resolution' => '3840 x 2160 (4K)',
            'Panel' => 'IPS Black Technology',
            'Contrast Ratio' => '2000:1',
            'Brightness' => '400 nits',
            'Connectivity' => 'USB-C (90W PD), DisplayPort 1.4, HDMI 2.0',
            'Color Support' => '1.07 Billion colors, 98% DCI-P3',
            'USB Hub' => 'Built-in 10Gbps high speed hub'
        ],
        'reviews' => [
            ['name' => 'Leonardo da Vinci', 'rating' => 5, 'comment' => 'The contrast on this IPS panel is unbelievable. Perfect for creative professionals.'],
            ['name' => 'Elon Musk', 'rating' => 5, 'comment' => 'Crystal clear display and the USB-C hub simplifies my entire setup.']
        ]
    ],
    211 => [
        'image' => 'product-images/omen2.jpg',
        'specs' => [
            'CPU' => 'AMD Ryzen 7 5800X',
            'GPU' => 'NVIDIA GeForce RTX 3070 (8GB GDDR6)',
            'RAM' => '16GB HyperX DDR4-3733MHz RGB',
            'Storage' => '1TB WD Black PCIe NVMe SSD',
            'Cooling' => '120mm RGB Liquid Cooler',
            'Case' => 'Tool-less design with Tempered Glass',
            'PSU' => '800W 80 Plus Gold'
        ],
        'reviews' => [
            ['name' => 'Gordon Ramsay', 'rating' => 5, 'comment' => 'Raw performance! Handles everything I throw at it with style.'],
            ['name' => 'Keanu Reeves', 'rating' => 5, 'comment' => 'The build is clean and the RGB is breathtaking. Excellent mid-range choice.']
        ]
    ],
    212 => [
        'image' => 'product-images/amd1.jpg',
        'specs' => [
            'Total Cores' => '16 Cores (Zen 4 Architecture)',
            'Total Threads' => '32 Threads',
            'Base Clock' => '4.5 GHz',
            'Boost Clock' => 'Up to 5.7 GHz',
            'L3 Cache' => '64 MB',
            'TDP' => '170 W',
            'Socket' => 'AM5'
        ],
        'reviews' => [
            ['name' => 'Lisa Su', 'rating' => 5, 'comment' => 'The ultimate processor for enthusiasts and creators. Unmatched performance.'],
            ['name' => 'Linus Tech', 'rating' => 5, 'comment' => 'Efficiency and power in one package. The AM5 platform is looking very promising.']
        ]
    ]
];

// Fallback for specs/reviews if not in productData
$display_specs = isset($productData[$product_id]['specs']) ? $productData[$product_id]['specs'] : $specs;
$display_reviews = isset($productData[$product_id]['reviews']) ? $productData[$product_id]['reviews'] : [];
$display_image = isset($productData[$product_id]['image']) ? $productData[$product_id]['image'] : ($product['image_url'] ?? 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');

?>

<section class="py-12 md:py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Breadcrumb -->
        <nav class="flex mb-10 text-gray-500 text-sm animate-fade-in-up">
            <a href="index.php" class="hover:text-primary transition-colors">Home</a>
            <span class="mx-3 text-gray-400">/</span>
            <a href="products.php" class="hover:text-primary transition-colors">Products</a>
            <span class="mx-3 text-gray-400">/</span>
            <span class="text-gray-900 font-bold gradient-text"><?php echo htmlspecialchars($product['product_name']); ?></span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-16">
            <!-- Product Images -->
            <div class="lg:w-1/2 animate-fade-in-up">
                <div class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100 overflow-hidden group relative">
                    <img src="<?php echo $display_image; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="w-full h-auto rounded-2xl transition-transform duration-700 group-hover:scale-105">
                    <?php if (isset($product['is_preorder']) && $product['is_preorder']): ?>
                        <div class="absolute top-8 left-8 animate-bounce-subtle">
                            <span class="bg-yellow-500 text-white px-4 py-2 rounded-full text-sm font-bold shadow-xl flex items-center">
                                <i class="fas fa-clock mr-2"></i> Pre-order Item
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="lg:w-1/2 space-y-10 animate-fade-in-up" style="animation-delay: 0.1s;">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <span class="bg-green-100 text-primary px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest shadow-sm">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                        <span class="text-gray-400 text-sm font-bold">
                            <i class="fas fa-barcode mr-1"></i> SKU: SKU-<?php echo $product_id; ?>
                        </span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black text-gray-900 mb-4 leading-tight">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </h1>
                    <div class="flex items-center gap-6">
                        <div class="flex text-yellow-400 text-sm">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="text-gray-400 text-sm font-medium">(<?php echo count($display_reviews); ?> verified reviews)</span>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 glass-card">
                    <p class="text-gray-400 text-sm font-bold uppercase tracking-widest mb-2">Retail Price</p>
                    <div class="flex items-end gap-3">
                        <span class="text-5xl font-black text-dark">₱<?php echo number_format($product['base_price'], 2); ?></span>
                        <span class="text-green-500 text-sm font-bold mb-2">
                            <i class="fas fa-check-circle mr-1"></i> Tax Included
                        </span>
                    </div>
                </div>

                <!-- Variant Selection (Simulated) -->
                <div class="space-y-6">
                    <label class="block text-gray-700 font-bold uppercase tracking-widest text-xs">Available Options</label>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($variants as $variant): ?>
                            <button class="px-6 py-3 rounded-xl border-2 border-primary bg-green-50 text-primary font-bold transition-all shadow-sm">
                                <?php echo htmlspecialchars($variant['color_name']); ?> 
                                <span class="ml-2 opacity-50 font-medium">
                                    (<?php echo $variant['stock'] > 0 ? $variant['stock'] . ' in stock' : 'Out of stock'; ?>)
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <div class="flex items-center border-2 border-gray-200 rounded-2xl bg-white p-1">
                        <button onclick="decrementQty()" class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-primary transition-colors">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="w-16 text-center bg-transparent font-black text-gray-800 text-lg focus:outline-none">
                        <button onclick="incrementQty()" class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-primary transition-colors">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <button onclick="addToCart()" class="flex-grow bg-primary text-white py-5 px-10 rounded-2xl font-black text-lg hover:bg-green-600 transition-all shadow-xl shadow-green-100 flex items-center justify-center gap-3 active:scale-95 btn-animate">
                        <i class="fas fa-shopping-cart"></i> Add to Bag
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4">
                    <div class="flex items-center gap-3 text-gray-500 text-sm font-medium p-4 bg-white rounded-2xl border border-gray-100">
                        <i class="fas fa-shield-alt text-primary text-xl"></i>
                        <span>1 Year Official Warranty</span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-500 text-sm font-medium p-4 bg-white rounded-2xl border border-gray-100">
                        <i class="fas fa-undo text-primary text-xl"></i>
                        <span>7 Days Easy Return</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Tabs -->
        <div class="mt-24">
            <div class="flex border-b border-gray-200 mb-12 overflow-x-auto">
                <button class="px-8 py-4 text-gray-900 border-b-4 border-primary font-black uppercase tracking-widest text-sm whitespace-nowrap">Technical Specifications</button>
                <button class="px-8 py-4 text-gray-400 hover:text-primary transition-colors font-black uppercase tracking-widest text-sm whitespace-nowrap">Customer Reviews (<?php echo count($display_reviews); ?>)</button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-16">
                <!-- Specs Table -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                        <table class="w-full">
                            <tbody>
                                <?php 
                                $even = false;
                                foreach ($display_specs as $key => $value): 
                                ?>
                                    <tr class="<?php echo $even ? 'bg-gray-50/50' : ''; ?>">
                                        <td class="px-8 py-5 text-gray-400 font-bold uppercase tracking-widest text-[10px] w-1/3 border-r border-gray-100"><?php echo htmlspecialchars($key); ?></td>
                                        <td class="px-8 py-5 text-gray-800 font-bold text-sm"><?php echo htmlspecialchars($value); ?></td>
                                    </tr>
                                <?php 
                                $even = !$even;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reviews Preview -->
                <div class="space-y-8">
                    <h3 class="text-xl font-black text-gray-900 flex items-center">
                        Recent Reviews <span class="ml-3 text-xs bg-gray-100 text-gray-400 px-2 py-1 rounded-full"><?php echo count($display_reviews); ?></span>
                    </h3>
                    <?php if (empty($display_reviews)): ?>
                        <p class="text-gray-400 italic">No reviews yet for this product.</p>
                    <?php else: ?>
                        <?php foreach ($display_reviews as $review): ?>
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative">
                                <div class="flex text-yellow-400 text-[10px] mb-3">
                                    <?php for($i=0; $i<$review['rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed mb-4">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-green-50 rounded-full flex items-center justify-center text-primary font-bold text-xs">
                                        <?php echo substr($review['name'], 0, 1); ?>
                                    </div>
                                    <span class="text-gray-900 font-bold text-xs"><?php echo htmlspecialchars($review['name']); ?></span>
                                    <span class="text-[10px] text-green-500 font-bold ml-auto"><i class="fas fa-check-circle mr-1"></i> Verified</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function incrementQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decrementQty() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCart() {
    const qty = document.getElementById('quantity').value;
    const productId = <?php echo $product_id; ?>;
    window.location.href = `add_to_cart.php?id=${productId}&qty=${qty}`;
}
</script>

<?php include 'includes/footer.php'; ?>
