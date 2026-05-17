<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';

// Rule 1 & 2: Only registered users can add to cart
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?msg=Please login to add items to cart');
    exit();
}

// Rule 3: Admins cannot add to cart
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    header('Location: admin_dashboard.php');
    exit();
}

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $variant_id = isset($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;
    $quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
    if ($quantity < 1) {
        $quantity = 1;
    }

    // Find product in global hardcoded list
    $product = null;
    foreach ($global_products as $p) {
        if ($p['product_id'] === $product_id) {
            $product = $p;
            break;
        }
    }

    if ($product) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $price = (float)$product['base_price'];
        // Use product_id as variant_id for hardcoded products if not specified
        $actual_variant_id = $variant_id ?: $product_id;
        $cart_key = $actual_variant_id;

        // Check if product already in cart
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'product_id' => $product_id,
                'variant_id' => $actual_variant_id,
                'name' => $product['product_name'],
                'price' => $price,
                'quantity' => $quantity,
                'is_preorder' => false,
                'image' => $product['image_url'] ?? 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80'
            ];
        }
        
        // Set success message and redirect
        $_SESSION['success_message'] = '✓ ' . $product['product_name'] . ' has been added to your cart!';
        
        // Redirect back to referring page or cart
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
}

header('Location: index.php');
?>
