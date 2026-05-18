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
    CONSTRAINT fk_cartitem_cart    FOREIGN KEY (cart_id)    REFERENCES Carts(cart_id)
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
    CONSTRAINT fk_lineitem_order   FOREIGN KEY (order_id)   REFERENCES Orders(order_id)
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

INSERT INTO Roles (role_name) VALUES ('admin'), ('customer');

INSERT INTO Payment_method (method_name, requires_proof, is_active) VALUES
    ('GCash', TRUE, TRUE),
    ('Maya', TRUE, TRUE),
    ('Pay upon Pickup', FALSE, TRUE);

INSERT INTO Online_method (method_id, gateway_provider, processing_fee) VALUES
    (1, 'GCash', 0.0000),
    (2, 'Maya', 0.0000);

INSERT INTO Manual_method (method_id, account_name, account_number) VALUES
    (3, 'RigCheck Store', NULL);
