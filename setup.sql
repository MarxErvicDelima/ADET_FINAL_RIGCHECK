-- ============================================================
-- RigCheck Online — Full Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS rigcheck_db;
USE rigcheck_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables to rebuild clean
DROP TABLE IF EXISTS Reviews;
DROP TABLE IF EXISTS Payment;
DROP TABLE IF EXISTS Order_Line_Item;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Cart_Items;
DROP TABLE IF EXISTS Carts;
DROP TABLE IF EXISTS Promotion_Product;
DROP TABLE IF EXISTS Promotions;
DROP TABLE IF EXISTS Inventory;
DROP TABLE IF EXISTS Product_Variant;
DROP TABLE IF EXISTS Online_method;
DROP TABLE IF EXISTS Manual_method;
DROP TABLE IF EXISTS Payment_method;
DROP TABLE IF EXISTS Color;
DROP TABLE IF EXISTS Product;
DROP TABLE IF EXISTS Category;
DROP TABLE IF EXISTS Brand;
DROP TABLE IF EXISTS User;
DROP TABLE IF EXISTS Address;
DROP TABLE IF EXISTS Roles;

-- ============================================================
-- ROLES
-- ============================================================
CREATE TABLE Roles (
    role_id     INT AUTO_INCREMENT PRIMARY KEY,
    role_name   VARCHAR(50) NOT NULL
);

-- ============================================================
-- ADDRESS
-- ============================================================
CREATE TABLE Address (
    address_id      INT AUTO_INCREMENT PRIMARY KEY,
    street_address  VARCHAR(255) NOT NULL,
    barangay_dist   VARCHAR(100),
    municipality    VARCHAR(100),
    province        VARCHAR(100),
    postal_code     INT,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    is_default      BOOLEAN NOT NULL DEFAULT FALSE
);

-- ============================================================
-- USER
-- ============================================================
CREATE TABLE User (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    role_id         INT NOT NULL,
    address_id      INT DEFAULT NULL,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(20),
    status          VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_role    FOREIGN KEY (role_id)    REFERENCES Roles(role_id),
    CONSTRAINT fk_user_address FOREIGN KEY (address_id) REFERENCES Address(address_id)
);

-- ============================================================
-- BRAND
-- ============================================================
CREATE TABLE Brand (
    brand_id    INT AUTO_INCREMENT PRIMARY KEY,
    brand_name  VARCHAR(100) NOT NULL,
    description TEXT
);

-- ============================================================
-- CATEGORY
-- ============================================================
CREATE TABLE Category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL
);

-- ============================================================
-- PRODUCT
-- ============================================================
CREATE TABLE Product (
    product_id  INT AUTO_INCREMENT PRIMARY KEY,
    brand_id    INT NOT NULL,
    category_id INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    base_price  DECIMAL(12, 2) NOT NULL,
    description TEXT,
    image_url   VARCHAR(500),
    status      VARCHAR(50) NOT NULL DEFAULT 'active',
    CONSTRAINT fk_product_brand    FOREIGN KEY (brand_id)    REFERENCES Brand(brand_id),
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES Category(category_id)
);

-- ============================================================
-- COLOR
-- ============================================================
CREATE TABLE Color (
    color_id    INT AUTO_INCREMENT PRIMARY KEY,
    color_name  VARCHAR(50) NOT NULL
);

-- ============================================================
-- PRODUCT_VARIANT
-- Each variant = a specific SKU (e.g., 16GB RAM / 512GB SSD / Space Gray)
-- ============================================================
CREATE TABLE Product_Variant (
    variant_id      INT AUTO_INCREMENT PRIMARY KEY,
    product_id      INT NOT NULL,
    color_id        INT DEFAULT NULL,
    sku             VARCHAR(100) NOT NULL UNIQUE,
    storage         VARCHAR(50),
    ram             VARCHAR(50),
    specs_json      JSON,
    price_override  DECIMAL(12, 2) DEFAULT NULL,
    CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES Product(product_id),
    CONSTRAINT fk_variant_color   FOREIGN KEY (color_id)   REFERENCES Color(color_id)
);

-- ============================================================
-- INVENTORY
-- One row per variant. Tracks stock and reservations.
-- ============================================================
CREATE TABLE Inventory (
    inventory_id        INT AUTO_INCREMENT PRIMARY KEY,
    variant_id          INT NOT NULL UNIQUE,
    quantity_on_hand    INT NOT NULL DEFAULT 0,
    reserved_quantity   INT NOT NULL DEFAULT 0,
    reorder_level       INT NOT NULL DEFAULT 5
);

-- ============================================================
-- PAYMENT_METHOD (base table)
-- ============================================================
CREATE TABLE Payment_method (
    method_id       INT AUTO_INCREMENT PRIMARY KEY,
    method_name     VARCHAR(100) NOT NULL,
    requires_proof  BOOLEAN NOT NULL DEFAULT FALSE,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE
);

-- Online payment subtable (GCash, Maya)
CREATE TABLE Online_method (
    method_id           INT PRIMARY KEY,
    gateway_provider    VARCHAR(100),
    processing_fee      DECIMAL(8, 4) NOT NULL DEFAULT 0.0000,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_online_method FOREIGN KEY (method_id) REFERENCES Payment_method(method_id)
);

-- Manual / Pay-upon-pickup subtable
CREATE TABLE Manual_method (
    method_id       INT PRIMARY KEY,
    account_name    VARCHAR(100),
    account_number  VARCHAR(100),
    CONSTRAINT fk_manual_method FOREIGN KEY (method_id) REFERENCES Payment_method(method_id)
);

-- ============================================================
-- PROMOTIONS
-- ============================================================
CREATE TABLE Promotions (
    promo_id        INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    type            VARCHAR(50)  NOT NULL DEFAULT 'percentage',
    value           DECIMAL(10, 2) NOT NULL,
    start_date      DATETIME DEFAULT NULL,
    end_date        DATETIME DEFAULT NULL,
    min_purchase    DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE
);

-- ============================================================
-- PROMOTION_PRODUCT
-- Links promotions to specific product variants
-- ============================================================
CREATE TABLE Promotion_Product (
    promo_product_id    INT AUTO_INCREMENT PRIMARY KEY,
    variant_id          INT NOT NULL,
    promo_id            INT NOT NULL,
    CONSTRAINT fk_promoprod_variant FOREIGN KEY (variant_id) REFERENCES Product_Variant(variant_id),
    CONSTRAINT fk_promoprod_promo   FOREIGN KEY (promo_id)   REFERENCES Promotions(promo_id)
);

-- ============================================================
-- CARTS
-- One active cart per user at a time
-- ============================================================
CREATE TABLE Carts (
    cart_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    status      VARCHAR(50) NOT NULL DEFAULT 'active',
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES User(user_id)
);

-- ============================================================
-- CART_ITEMS
-- ============================================================
CREATE TABLE Cart_Items (
    cart_item_id    INT AUTO_INCREMENT PRIMARY KEY,
    cart_id         INT NOT NULL,
    variant_id      INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cartitem_cart    FOREIGN KEY (cart_id)    REFERENCES Carts(cart_id),
    CONSTRAINT fk_cartitem_variant FOREIGN KEY (variant_id) REFERENCES Product_Variant(variant_id)
);

-- ============================================================
-- ORDERS
-- ============================================================
CREATE TABLE Orders (
    order_id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    order_number        VARCHAR(50) NOT NULL UNIQUE,
    total_amount        DECIMAL(12, 2) NOT NULL,
    discount_amount     DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    status              VARCHAR(50) NOT NULL DEFAULT 'pending',
    order_type          VARCHAR(50) NOT NULL DEFAULT 'reservation',
    reservation_expiry  DATETIME DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES User(user_id)
);

-- ============================================================
-- ORDER_LINE_ITEM
-- Stores price at time of purchase — never recalculate from Product
-- ============================================================
CREATE TABLE Order_Line_Item (
    order_item_id   INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    variant_id      INT NOT NULL,
    quantity        INT NOT NULL,
    unit_price      DECIMAL(12, 2) NOT NULL,
    line_total      DECIMAL(12, 2) NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lineitem_order   FOREIGN KEY (order_id)   REFERENCES Orders(order_id),
    CONSTRAINT fk_lineitem_variant FOREIGN KEY (variant_id) REFERENCES Product_Variant(variant_id)
);

-- ============================================================
-- PAYMENT
-- ============================================================
CREATE TABLE Payment (
    payment_id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id            INT NOT NULL,
    method_id           INT NOT NULL,
    amount_paid         DECIMAL(12, 2) NOT NULL,
    reference_number    VARCHAR(100) DEFAULT NULL,
    proof_image_path    VARCHAR(255) DEFAULT NULL,
    payment_status      VARCHAR(50) NOT NULL DEFAULT 'pending',
    verified_by         INT DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_order   FOREIGN KEY (order_id)    REFERENCES Orders(order_id),
    CONSTRAINT fk_payment_method  FOREIGN KEY (method_id)   REFERENCES Payment_method(method_id),
    CONSTRAINT fk_payment_admin   FOREIGN KEY (verified_by) REFERENCES User(user_id)
);

-- ============================================================
-- REVIEWS
-- Can only be written by a user who purchased the item (via order_item_id)
-- ============================================================
CREATE TABLE Reviews (
    review_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    order_item_id   INT NOT NULL,
    rating          INT NOT NULL,
    comment         TEXT,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_rating         CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_review_user     FOREIGN KEY (user_id)       REFERENCES User(user_id),
    CONSTRAINT fk_review_lineitem FOREIGN KEY (order_item_id) REFERENCES Order_Line_Item(order_item_id)
);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- INITIAL SETUP DATA
-- ============================================================

-- Roles
INSERT INTO Roles (role_id, role_name) VALUES 
    (1, 'admin'), 
    (2, 'customer');

-- Payment Methods
INSERT INTO Payment_method (method_id, method_name, requires_proof, is_active) VALUES
    (1, 'GCash', TRUE, TRUE),
    (2, 'Maya', TRUE, TRUE),
    (3, 'Pay upon Pickup', FALSE, TRUE);

INSERT INTO Online_method (method_id, gateway_provider, processing_fee) VALUES
    (1, 'GCash', 0.0000),
    (2, 'Maya', 0.0000);

INSERT INTO Manual_method (method_id, account_name, account_number) VALUES
    (3, 'RigCheck Store', NULL);

-- Admin User (Email: admin@rigcheck.com, Password: admin123)
INSERT INTO User (user_id, role_id, address_id, first_name, last_name, email, password_hash, phone, status, created_at) VALUES
    (1, 1, NULL, 'Admin', 'User', 'admin@rigcheck.com', '$2y$10$c/1.MfAo1IZLLXYSOd7PG.yE27.Ubtr86aLDfcGe0CXzUl6zb6JD2', '+63 9XX XXX XXXX', 'active', NOW());

-- ============================================================
-- BRANDS
-- ============================================================
INSERT INTO Brand (brand_id, brand_name, description) VALUES
    (1, 'ASUS', 'Republic of Gamers - Premium gaming hardware'),
    (2, 'LG', 'LG Electronics - Display and monitor technology'),
    (3, 'Corsair', 'Corsair - Gaming peripherals and components'),
    (4, 'Intel', 'Intel Corporation - Processors'),
    (5, 'Razer', 'Razer Inc. - Gaming peripherals'),
    (6, 'SteelSeries', 'SteelSeries - Professional gaming gear'),
    (7, 'Creative', 'Creative Technology - Audio solutions'),
    (8, 'Dell', 'Dell Technologies - Computers and monitors'),
    (9, 'HP', 'HP Inc. - Computing solutions'),
    (10, 'AMD', 'AMD - Processors and graphics');

-- ============================================================
-- CATEGORIES
-- ============================================================
INSERT INTO Category (category_id, name) VALUES
    (1, 'Laptops'),
    (2, 'Monitors'),
    (3, 'Pre-built PC'),
    (4, 'CPU'),
    (5, 'Mouse'),
    (6, 'Keyboard'),
    (7, 'Headset'),
    (8, 'Speaker');

-- ============================================================
-- PRODUCTS (IDs 201-212)
-- ============================================================
INSERT INTO Product (product_id, brand_id, category_id, name, base_price, description, image_url, status) VALUES
    (201, 1, 1, 'ASUS ROG Zephyrus G14 (2024)', 185000.00, 'Premium gaming laptop with RTX 4090 and Ryzen 9 8945HS processor', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (202, 2, 2, 'LG UltraGear 34GP950G-B', 45000.00, 'Ultrawide curved gaming monitor with 144Hz refresh rate', 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (203, 3, 3, 'Corsair One i300 Gaming PC', 225000.00, 'High-performance pre-built gaming PC with RTX 3080 Ti', 'https://images.unsplash.com/photo-1589241160732-46c11d4d56b7?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (204, 4, 4, 'Intel Core i9-13900K Processor', 65000.00, 'High-end desktop processor with 24 cores', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (205, 5, 5, 'Razer DeathAdder V3 Pro Wireless', 8500.00, 'Professional wireless gaming mouse', 'https://images.unsplash.com/photo-1527814050087-3793815479db?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (206, 3, 6, 'Corsair K100 RGB Mechanical Keyboard', 12000.00, 'Premium RGB mechanical gaming keyboard', 'https://images.unsplash.com/photo-1587829191301-4b94e4d5f7e0?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (207, 6, 7, 'SteelSeries Arctis Nova Pro Wireless', 18000.00, 'Professional wireless gaming headset with Hi-Res audio', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (208, 7, 8, 'Creative GigaWorks T40 Series II', 22000.00, '2.0 channel gaming speaker system with 32W RMS power', 'https://images.unsplash.com/photo-1589003077984-894fbb89b948?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (209, 8, 1, 'Dell XPS 13 Plus (9320)', 35000.00, 'Ultraportable laptop with Intel Core i7 processor', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (210, 8, 2, 'Dell UltraSharp U2723QE 4K', 28000.00, 'Professional 4K IPS monitor with USB-C hub', 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (211, 9, 3, 'HP Omen 40L Desktop', 95000.00, 'Powerful gaming desktop with RTX 3070 and Ryzen 7 5800X', 'https://images.unsplash.com/photo-1589241160732-46c11d4d56b7?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active'),
    (212, 10, 4, 'AMD Ryzen 9 7950X Processor', 42000.00, 'High-performance 16-core desktop processor', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80', 'active');

-- ============================================================
-- COLORS (Optional - for future variants)
-- ============================================================
INSERT INTO Color (color_id, color_name) VALUES
    (1, 'Black'),
    (2, 'White'),
    (3, 'Space Gray'),
    (4, 'Silver'),
    (5, 'Gold'),
    (6, 'Red'),
    (7, 'Blue'),
    (8, 'Green');

-- ============================================================
-- PRODUCT VARIANTS (one variant per product with full inventory)
-- ============================================================
INSERT INTO Product_Variant (variant_id, product_id, color_id, sku, storage, ram, specs_json, price_override) VALUES
    (1, 201, NULL, 'ASUS-ROG-G14-2024-001', '1TB NVMe Gen4', '32GB LPDDR5X', '{"GPU": "RTX 4090", "CPU": "Ryzen 9 8945HS", "RAM": "32GB LPDDR5X", "Storage": "1TB NVMe Gen4"}', NULL),
    (2, 202, NULL, 'LG-ULTRAGEAR-34GP-001', NULL, NULL, '{"Resolution": "3440x1440", "Panel": "Nano IPS", "Refresh": "144Hz (OC 180Hz)", "Features": "G-SYNC Ultimate"}', NULL),
    (3, 203, NULL, 'CORSAIR-ONE-i300-001', '2TB M.2 NVMe', '64GB DDR5', '{"GPU": "RTX 3080 Ti", "CPU": "Core i9-12900K", "RAM": "64GB DDR5", "Storage": "2TB M.2 NVMe"}', NULL),
    (4, 204, NULL, 'INTEL-i9-13900K-001', NULL, NULL, '{"Cores": "24 (8P+16E)", "Threads": "32", "Base": "3.0GHz", "Boost": "5.8GHz", "Socket": "LGA1700"}', NULL),
    (5, 205, NULL, 'RAZER-DEATHADDER-V3-001', NULL, NULL, '{"Sensor": "Focus Pro 30K", "DPI": "30000", "Wireless": "HyperSpeed", "Weight": "63g"}', NULL),
    (6, 206, NULL, 'CORSAIR-K100-RGB-001', NULL, NULL, '{"Switches": "AXON Hyper-Processing", "RGB": "44-zone LightEdge", "Polling": "4000Hz", "Keys": "PBT Double-Shot"}', NULL),
    (7, 207, NULL, 'STEELSERIES-ARCTIS-NOVA-001', NULL, NULL, '{"Driver": "Premium Hi-Res", "ANC": "4-mic System", "Wireless": "Dual Connect", "Battery": "Hot-swappable"}', NULL),
    (8, 208, NULL, 'CREATIVE-GIGAWORKS-T40-001', NULL, NULL, '{"Channels": "2.0", "Power": "32W RMS", "Frequency": "50Hz-20kHz", "Tech": "BasXPort"}', NULL),
    (9, 209, NULL, 'DELL-XPS-13-PLUS-001', '512GB Gen4 SSD', '16GB LPDDR5', '{"GPU": "Intel Iris Xe", "CPU": "Core i7-1260P", "RAM": "16GB LPDDR5", "Storage": "512GB Gen4 SSD"}', NULL),
    (10, 210, NULL, 'DELL-ULTRASHARP-U2723QE-001', NULL, NULL, '{"Resolution": "3840x2160", "Panel": "IPS Black", "Contrast": "2000:1", "Ports": "USB-C Hub"}', NULL),
    (11, 211, NULL, 'HP-OMEN-40L-001', '1TB WD Black SSD', '16GB RGB', '{"GPU": "RTX 3070", "CPU": "Ryzen 7 5800X", "RAM": "16GB RGB", "Storage": "1TB WD Black SSD"}', NULL),
    (12, 212, NULL, 'AMD-RYZEN-9-7950X-001', NULL, NULL, '{"Cores": "16", "Threads": "32", "Base": "4.5GHz", "Boost": "5.7GHz", "Socket": "AM5"}', NULL);

-- ============================================================
-- INVENTORY (Stock levels for each variant)
-- ============================================================
INSERT INTO Inventory (inventory_id, variant_id, quantity_on_hand, reserved_quantity, reorder_level) VALUES
    (1, 1, 8, 0, 5),
    (2, 2, 5, 0, 5),
    (3, 3, 3, 0, 5),
    (4, 4, 12, 0, 5),
    (5, 5, 20, 0, 5),
    (6, 6, 15, 0, 5),
    (7, 7, 10, 0, 5),
    (8, 8, 7, 0, 5),
    (9, 9, 18, 0, 5),
    (10, 10, 6, 0, 5),
    (11, 11, 4, 0, 5),
    (12, 12, 9, 0, 5);

-- ============================================================
-- SAMPLE PROMOTION
-- ============================================================
INSERT INTO Promotions (promo_id, name, type, value, start_date, end_date, min_purchase, is_active) VALUES
    (1, 'Grand Opening Sale', 'percentage', 15.00, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 0.00, TRUE);

-- Link promotion to all variants
INSERT INTO Promotion_Product (promo_product_id, variant_id, promo_id) VALUES
    (1, 1, 1), (2, 2, 1), (3, 3, 1), (4, 4, 1), (5, 5, 1), (6, 6, 1), 
    (7, 7, 1), (8, 8, 1), (9, 9, 1), (10, 10, 1), (11, 11, 1), (12, 12, 1);

-- ============================================================
-- DATABASE SETUP COMPLETE
-- ============================================================
-- All tables created and seeded with sample data
-- Admin account: admin@rigcheck.com / admin123
-- 12 Products available (IDs 201-212)
-- Inventory levels set based on product demand
-- Payment methods configured (GCash, Maya, Pay upon Pickup)
