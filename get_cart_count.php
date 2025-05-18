<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'count' => 0];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT SUM(quantity) as count 
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();

    $response = [
        'success' => true,
        'count' => $result['count'] ?? 0
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);