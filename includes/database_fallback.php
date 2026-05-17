<?php
/**
 * Local Database Fallback System
 * Provides sample data when remote database is unavailable
 */

class LocalDatabaseFallback {
    private static $products = [
        [
            'product_id' => 201,
            'product_name' => 'Gaming Laptop Pro',
            'description' => 'High-performance gaming laptop with RTX 4090',
            'base_price' => 185000,
            'category_id' => 1,
            'category_name' => 'Laptops',
            'brand_id' => 1,
            'brand_name' => 'ASUS',
            'status' => 'active',
            'created_at' => '2026-05-01',
            'specs' => '{"CPU":"Intel Core i9-13900K","RAM":"32GB DDR5","GPU":"RTX 4090","Storage":"1TB NVMe SSD","Display":"4K OLED","Battery":"8 hours","Weight":"2.5kg"}',
            'image_url' => 'https://images.unsplash.com/photo-1588729219742-d6e04d8b4f1c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 15
        ],
        [
            'product_id' => 202,
            'product_name' => 'Pre-built Gaming PC Ultimate',
            'description' => 'Top-tier gaming PC with RTX 4090 and Core i9',
            'base_price' => 250000,
            'category_id' => 2,
            'category_name' => 'Pre-built PC',
            'brand_id' => 2,
            'brand_name' => 'iBuyPower',
            'status' => 'active',
            'created_at' => '2026-05-02',
            'specs' => '{"CPU":"Intel Core i9-13900K","RAM":"32GB DDR5","GPU":"RTX 4090 (16GB GDDR6)","Storage":"2TB NVMe SSD","Motherboard":"Z890 DDR5","PSU":"1000W 80+ Gold","Cooling":"Liquid AIO 360mm","Case":"Corsair Crystal"}',
            'image_url' => 'https://images.unsplash.com/photo-1587829191301-b33d0b8e8e0d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 8
        ],
        [
            'product_id' => 203,
            'product_name' => '4K Gaming Monitor 32"',
            'description' => 'Ultra HD 32 inch gaming monitor with 144Hz',
            'base_price' => 45000,
            'category_id' => 3,
            'category_name' => 'Monitors',
            'brand_id' => 3,
            'brand_name' => 'ASUS ROG',
            'status' => 'active',
            'created_at' => '2026-05-03',
            'specs' => '{"Resolution":"3840x2160 4K","Size":"32 inches","Panel Type":"OLED","Refresh Rate":"144Hz","Response Time":"1ms","Color Accuracy":"99% DCI-P3"}',
            'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 22
        ],
        [
            'product_id' => 204,
            'product_name' => 'Intel Core i9-13900K Processor',
            'description' => '24-core CPU for extreme gaming and productivity',
            'base_price' => 68000,
            'category_id' => 4,
            'category_name' => 'CPU',
            'brand_id' => 4,
            'brand_name' => 'Intel',
            'status' => 'active',
            'created_at' => '2026-05-04',
            'specs' => '{"Cores":"24","Base Clock":"3.0 GHz","Max Turbo":"5.8 GHz","TDP":"253W","Socket":"LGA1700","Cache":"36MB"}',
            'image_url' => 'https://images.unsplash.com/photo-1591290619735-73b8a37b3466?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 30
        ],
        [
            'product_id' => 205,
            'product_name' => 'Wireless Gaming Mouse RGB',
            'description' => 'High-precision gaming mouse with customizable RGB',
            'base_price' => 4500,
            'category_id' => 5,
            'category_name' => 'Mouse',
            'brand_id' => 5,
            'brand_name' => 'Corsair',
            'status' => 'active',
            'created_at' => '2026-05-05',
            'specs' => '{"DPI":"26000","Buttons":"12","Wireless":"2.4GHz","Battery":"50 hours","RGB":"16 million colors"}',
            'image_url' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 50
        ],
        [
            'product_id' => 206,
            'product_name' => 'Mechanical Gaming Keyboard',
            'description' => 'Premium mechanical keyboard with RGB backlight',
            'base_price' => 12000,
            'category_id' => 6,
            'category_name' => 'Keyboard',
            'brand_id' => 5,
            'brand_name' => 'Corsair',
            'status' => 'active',
            'created_at' => '2026-05-06',
            'specs' => '{"Switches":"Cherry MX Red","Layout":"Full Size","RGB":"Per-key","Polling Rate":"8000Hz"}',
            'image_url' => 'https://images.unsplash.com/photo-1587829191301-b33d0b8e8e0d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 35
        ],
        [
            'product_id' => 207,
            'product_name' => 'Wireless Gaming Headset 7.1',
            'description' => 'Premium wireless headset with 7.1 surround sound',
            'base_price' => 8500,
            'category_id' => 7,
            'category_name' => 'Headset',
            'brand_id' => 6,
            'brand_name' => 'SteelSeries',
            'status' => 'active',
            'created_at' => '2026-05-07',
            'specs' => '{"Sound":"7.1 Surround","Drivers":"40mm","Wireless":"2.4GHz","Battery":"20 hours","Microphone":"Detachable"}',
            'image_url' => 'https://images.unsplash.com/photo-1487215078519-e21cc028cb29?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 25
        ],
        [
            'product_id' => 208,
            'product_name' => 'Studio Monitor Speaker Pair',
            'description' => 'Professional studio quality speakers',
            'base_price' => 18000,
            'category_id' => 8,
            'category_name' => 'Speaker',
            'brand_id' => 7,
            'brand_name' => 'Yamaha',
            'status' => 'active',
            'created_at' => '2026-05-08',
            'specs' => '{"Power":"120W per speaker","Frequency":"20Hz-20kHz","Drivers":"2-way","Impedance":"4 Ohms"}',
            'image_url' => 'https://images.unsplash.com/photo-1589003077984-894fdbb6e075?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 12
        ],
        [
            'product_id' => 209,
            'product_name' => 'Mid-Range Gaming Laptop',
            'description' => 'Balanced gaming laptop for esports',
            'base_price' => 95000,
            'category_id' => 1,
            'category_name' => 'Laptops',
            'brand_id' => 1,
            'brand_name' => 'ASUS',
            'status' => 'active',
            'created_at' => '2026-05-09',
            'specs' => '{"CPU":"Intel Core i7-12700H","RAM":"16GB DDR5","GPU":"RTX 3080","Storage":"512GB NVMe SSD","Display":"1440p 165Hz"}',
            'image_url' => 'https://images.unsplash.com/photo-1588729219742-d6e04d8b4f1c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 18
        ],
        [
            'product_id' => 210,
            'product_name' => 'Entry-Level Gaming PC',
            'description' => 'Great starter gaming PC',
            'base_price' => 85000,
            'category_id' => 2,
            'category_name' => 'Pre-built PC',
            'brand_id' => 2,
            'brand_name' => 'iBuyPower',
            'status' => 'active',
            'created_at' => '2026-05-10',
            'specs' => '{"CPU":"Intel Core i5-12400","RAM":"16GB DDR4","GPU":"RTX 3060","Storage":"512GB SSD","Case":"Mid-Tower ATX"}',
            'image_url' => 'https://images.unsplash.com/photo-1587829191301-b33d0b8e8e0d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 20
        ],
        [
            'product_id' => 211,
            'product_name' => '2K Gaming Monitor 27"',
            'description' => '27 inch 2K gaming monitor with 144Hz',
            'base_price' => 18000,
            'category_id' => 3,
            'category_name' => 'Monitors',
            'brand_id' => 8,
            'brand_name' => 'Dell',
            'status' => 'active',
            'created_at' => '2026-05-11',
            'specs' => '{"Resolution":"2560x1440 2K","Size":"27 inches","Panel Type":"IPS","Refresh Rate":"144Hz","Response Time":"1ms"}',
            'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 28
        ],
        [
            'product_id' => 212,
            'product_name' => 'RTX 4070 Graphics Card',
            'description' => 'High-performance mid-range GPU',
            'base_price' => 55000,
            'category_id' => 4,
            'category_name' => 'CPU',
            'brand_id' => 9,
            'brand_name' => 'NVIDIA',
            'status' => 'active',
            'created_at' => '2026-05-12',
            'specs' => '{"Memory":"12GB GDDR6X","Bus":"192-bit","Boost Clock":"2.475 GHz","TDP":"200W"}',
            'image_url' => 'https://images.unsplash.com/photo-1591290619735-73b8a37b3466?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            'stock' => 24
        ]
    ];

    public static function getProducts($filters = []) {
        $products = self::$products;
        
        // Apply category filter
        if (!empty($filters['category'])) {
            $products = array_filter($products, function($p) use ($filters) {
                return $p['category_name'] === $filters['category'];
            });
        }
        
        // Apply price filter
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $min = $filters['min_price'] ?? 0;
            $max = $filters['max_price'] ?? 999999;
            $products = array_filter($products, function($p) use ($min, $max) {
                return $p['base_price'] >= $min && $p['base_price'] <= $max;
            });
        }
        
        // Apply specs filter
        if (!empty($filters['specs'])) {
            $specs_search = strtolower($filters['specs']);
            $products = array_filter($products, function($p) use ($specs_search) {
                $specs = strtolower($p['specs']);
                return stripos($specs, $specs_search) !== false;
            });
        }
        
        return array_values($products);
    }

    public static function getProductById($id) {
        foreach (self::$products as $product) {
            if ($product['product_id'] == $id) {
                return $product;
            }
        }
        return null;
    }
}
?>
