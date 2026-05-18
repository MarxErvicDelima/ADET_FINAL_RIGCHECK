<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "sql312.byethost6.com";
$username = "b6_41634584";
$password = "ADMIN123";
$dbname = "b6_41634584_rigcheck_db";

$pdo = null;

try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    $pdo = null;
}

// Shared Hardcoded Products Data
$global_products = [
    [
        'product_id' => 201,
        'product_name' => 'ASUS ROG Zephyrus G14 (2024)',
        'base_price' => 185000,
        'category_name' => 'Laptops',
        'brand_name' => 'ASUS',
        'specs' => '{"GPU": "RTX 4090", "CPU": "Ryzen 9 8945HS", "RAM": "32GB LPDDR5X", "Storage": "1TB NVMe Gen4"}',
        'image_url' => 'product-images/E0275281-F18B-42C3-A025-3331C35A888F.png',
        'stock' => 8
    ],
    [
        'product_id' => 202,
        'product_name' => 'LG UltraGear 34GP950G-B',
        'base_price' => 45000,
        'category_name' => 'Monitors',
        'brand_name' => 'LG',
        'specs' => '{"Resolution": "3440x1440", "Panel": "Nano IPS", "Refresh": "144Hz (OC 180Hz)", "Features": "G-SYNC Ultimate"}',
        'image_url' => 'product-images/lg2.avif',
        'stock' => 5
    ],
    [
        'product_id' => 203,
        'product_name' => 'Corsair One i300 Gaming PC',
        'base_price' => 225000,
        'category_name' => 'Pre-built PC',
        'brand_name' => 'Corsair',
        'specs' => '{"GPU": "RTX 3080 Ti", "CPU": "Core i9-12900K", "RAM": "64GB DDR5", "Storage": "2TB M.2 NVMe"}',
        'image_url' => 'product-images/CORSAIR_ONE_i300_2022_01.avif',
        'stock' => 3
    ],
    [
        'product_id' => 204,
        'product_name' => 'Intel Core i9-13900K Processor',
        'base_price' => 65000,
        'category_name' => 'CPU',
        'brand_name' => 'Intel',
        'specs' => '{"Cores": "24 (8P+16E)", "Threads": "32", "Base": "3.0GHz", "Boost": "5.8GHz", "Socket": "LGA1700"}',
        'image_url' => 'product-images/19-118-412-V01.webp',
        'stock' => 12
    ],
    [
        'product_id' => 205,
        'product_name' => 'Razer DeathAdder V3 Pro Wireless',
        'base_price' => 8500,
        'category_name' => 'Mouse',
        'brand_name' => 'Razer',
        'specs' => '{"Sensor": "Focus Pro 30K", "DPI": "30000", "Wireless": "HyperSpeed", "Weight": "63g"}',
        'image_url' => 'product-images/Razer DeathAdder V3 Pro, White (RZ01-04630200-R3G1) (29).webp',
        'stock' => 20
    ],
    [
        'product_id' => 206,
        'product_name' => 'Corsair K100 RGB Mechanical Keyboard',
        'base_price' => 12000,
        'category_name' => 'Keyboard',
        'brand_name' => 'Corsair',
        'specs' => '{"Switches": "AXON Hyper-Processing", "RGB": "44-zone LightEdge", "Polling": "4000Hz", "Keys": "PBT Double-Shot"}',
        'image_url' => 'product-images/images.jpeg',
        'stock' => 15
    ],
    [
        'product_id' => 207,
        'product_name' => 'SteelSeries Arctis Nova Pro Wireless',
        'base_price' => 18000,
        'category_name' => 'Headset',
        'brand_name' => 'SteelSeries',
        'specs' => '{"Driver": "Premium Hi-Res", "ANC": "4-mic System", "Wireless": "Dual Connect", "Battery": "Hot-swappable"}',
        'image_url' => 'product-images/steelseries_xbox_arctis_nova_pro_wireless_white_61525_-4.webp',
        'stock' => 10
    ],
    [
        'product_id' => 208,
        'product_name' => 'Creative GigaWorks T40 Series II',
        'base_price' => 22000,
        'category_name' => 'Speaker',
        'brand_name' => 'Creative',
        'specs' => '{"Channels": "2.0", "Power": "32W RMS", "Frequency": "50Hz-20kHz", "Tech": "BasXPort"}',
        'image_url' => 'product-images/creative.jpeg',
        'stock' => 8
    ],
    [
        'product_id' => 209,
        'product_name' => 'Dell XPS 13 Plus (9320)',
        'base_price' => 35000,
        'category_name' => 'Laptops',
        'brand_name' => 'Dell',
        'specs' => '{"GPU": "Intel Iris Xe", "CPU": "Core i7-1260P", "RAM": "16GB LPDDR5", "Storage": "512GB Gen4 SSD"}',
        'image_url' => 'product-images/dell1.jpeg',
        'stock' => 18
    ],
    [
        'product_id' => 210,
        'product_name' => 'Dell UltraSharp U2723QE 4K',
        'base_price' => 28000,
        'category_name' => 'Monitors',
        'brand_name' => 'Dell',
        'specs' => '{"Resolution": "3840x2160", "Panel": "IPS Black", "Contrast": "2000:1", "Ports": "USB-C Hub"}',
        'image_url' => 'product-images/dell4.jpg',
        'stock' => 6
    ],
    [
        'product_id' => 211,
        'product_name' => 'HP Omen 40L Desktop',
        'base_price' => 95000,
        'category_name' => 'Pre-built PC',
        'brand_name' => 'HP',
        'specs' => '{"GPU": "RTX 3070", "CPU": "Ryzen 7 5800X", "RAM": "16GB RGB", "Storage": "1TB WD Black SSD"}',
        'image_url' => 'product-images/omen1.avif',
        'stock' => 4
    ],
    [
        'product_id' => 212,
        'product_name' => 'AMD Ryzen 9 7950X Processor',
        'base_price' => 42000,
        'category_name' => 'CPU',
        'brand_name' => 'AMD',
        'specs' => '{"Cores": "16", "Threads": "32", "Base": "4.5GHz", "Boost": "5.7GHz", "Socket": "AM5"}',
        'image_url' => 'product-images/amd.avif',
        'stock' => 9
    ]
];

// Create product image URL mapping from global_products
$product_images = [];
foreach ($global_products as $product) {
    $product_images[$product['product_id']] = $product['image_url'];
}

// Helper function to get correct image URL for a product
function getProductImage($product_id, $fallback_url = '') {
    global $product_images;
    return $product_images[$product_id] ?? $fallback_url ?? 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80';
}

// Helper function to get correct stock for a product
function getProductStock($product_id, $fallback_stock = 0) {
    global $global_products, $pdo;
    
    // Try to get stock from database first if available
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("SELECT quantity_on_hand FROM Inventory WHERE variant_id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $stock = $stmt->fetchColumn();
            if ($stock !== false) {
                return intval($stock);
            }
        } catch (Exception $e) {
            error_log("Error fetching stock from DB: " . $e->getMessage());
        }
    }

    // Fallback to hardcoded stock
    foreach ($global_products as $product) {
        if ($product['product_id'] === $product_id) {
            return intval($product['stock']);
        }
    }
    return intval($fallback_stock);
}
?>
