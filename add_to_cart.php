<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Validate input
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    
    if (!$product_id) {
        throw new Exception('Invalid product ID');
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];

    // Check if product exists
    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Product not found');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Get or create cart
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();

    if (!$cart) {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        $cart_id = $pdo->lastInsertId();
    } else {
        $cart_id = $cart['cart_id'];
    }

    // Add or update item in cart
    $stmt = $pdo->prepare("
        INSERT INTO cart_items (cart_id, product_id, quantity) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    $stmt->execute([$cart_id, $product_id, $quantity]);

    $pdo->commit();

    $response = [
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => getCartCount($pdo, $user_id)
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
}

// Function to get cart count
function getCartCount($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT SUM(quantity) as count 
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

echo json_encode($response);
