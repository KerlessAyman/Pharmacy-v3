<?php
session_start();
require_once 'config.php';

// Verify database connection
if (!isset($pdo)) {
    die("Database connection not established");
}

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Get user's orders
try {
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.order_date, o.total_amount, o.status, 
               o.payment_method, o.payment_status, o.shipping_address,
               COUNT(oi.order_item_id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_item_id
        WHERE o.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching orders: " . $e->getMessage();
}

// Get order details for a specific order if requested
$order_details = [];
if (isset($_GET['order_id'])) {
    try {
        // Verify the order belongs to the current user
        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE order_id = ?");
        $stmt->execute([$_GET['order_id']]);
        $order_owner = $stmt->fetchColumn();
        
        if ($order_owner != $user_id) {
            $error = "You are not authorized to view this order";
        } else {
            $stmt = $pdo->prepare("
                SELECT oi.*, p.name, p.image_url
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$_GET['order_id']]);
            $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Error fetching order details: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
      --primary-color: #159b85;
      --primary-dark: #12806d;
      --light-gray: #f4f4f4;
      --medium-gray: #ddd;
      --dark-gray: #333;
      --white: #fff;
      --black: #000;
      --success-color: #27ae60;
      --warning-color: #f39c12;
      --error-color: #e74c3c;
      --border-radius: 8px;
      --box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s ease;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Arial', sans-serif;
      line-height: 1.6;
      color: var(--dark-gray);
      background-color: var(--light-gray);
      padding-bottom: 40px;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .header {
      background-color: var(--white);
      padding: 15px 0;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .header-house-icon {
      color: var(--primary-color);
      font-size: 1.8rem;
      transition: var(--transition);
    }

    .header-house-icon:hover {
      transform: scale(1.1);
      color: var(--primary-dark);
    }

    .navbar {
      background-color: var(--primary-color);
      padding: 15px 0;
      margin-bottom: 30px;
    }

    .nav-list {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 25px;
      list-style: none;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .nav-list a {
      color: var(--white);
      text-decoration: none;
      font-weight: 600;
      position: relative;
      padding: 5px 0;
      transition: var(--transition);
    }

    .nav-list a::after {
      content: "";
      position: absolute;
      width: 0;
      height: 2px;
      background-color: var(--white);
      left: 0;
      bottom: 0;
      transition: var(--transition);
    }

    .nav-list a:hover::after {
      width: 100%;
    }

    .orders-container {
      background: var(--white);
      padding: 30px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      margin: 0 auto 40px;
    }

    .page-title {
      text-align: center;
      color: var(--primary-color);
      margin-bottom: 30px;
      position: relative;
      padding-bottom: 15px;
    }

    .page-title::after {
      content: "";
      position: absolute;
      width: 80px;
      height: 3px;
      background-color: var(--primary-color);
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
    }

    .orders-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .order-card {
      background-color: var(--white);
      padding: 20px;
      margin-bottom: 20px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      transition: var(--transition);
      border-left: 4px solid var(--primary-color);
    }

    .order-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .order-id {
      font-weight: bold;
      color: var(--primary-color);
      font-size: 1.1rem;
    }

    .order-date {
      color: var(--dark-gray);
      font-size: 0.9rem;
    }

    .order-status {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      text-transform: uppercase;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-processing {
      background-color: #cce5ff;
      color: #004085;
    }

    .status-completed {
      background-color: #d4edda;
      color: #155724;
    }

    .status-cancelled {
      background-color: #f8d7da;
      color: #721c24;
    }

    .order-details {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      flex-wrap: wrap;
      gap: 15px;
    }

    .order-items {
      font-size: 0.9rem;
      color: var(--dark-gray);
    }

    .order-total {
      font-weight: bold;
      font-size: 1.1rem;
    }

    .view-details-btn {
      background-color: var(--primary-color);
      color: var(--white);
      padding: 8px 15px;
      border: none;
      border-radius: var(--border-radius);
      font-size: 0.9rem;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-block;
    }

    .view-details-btn:hover {
      background-color: var(--primary-dark);
    }

    .order-details-container {
      margin-top: 30px;
      background: var(--white);
      padding: 25px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
    }

    .details-title {
      color: var(--primary-color);
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--medium-gray);
    }

    .details-items {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .details-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid var(--light-gray);
    }

    .item-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .item-image {
      width: 60px;
      height: 60px;
      object-fit: contain;
      border-radius: 4px;
      border: 1px solid var(--medium-gray);
    }

    .item-name {
      font-weight: 600;
    }

    .item-price {
      font-weight: bold;
      color: var(--primary-color);
    }

    .item-quantity {
      color: var(--dark-gray);
    }

    .details-summary {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid var(--medium-gray);
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .summary-label {
      font-weight: 600;
    }

    .summary-value {
      font-weight: bold;
    }

    .total-row {
      font-size: 1.1rem;
      color: var(--primary-color);
    }

    .back-btn {
      display: inline-block;
      margin-top: 20px;
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
    }

    .back-btn:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: var(--border-radius);
      font-weight: 600;
      text-align: center;
    }

    .error {
      background-color: #fdecea;
      color: var(--error-color);
      border-left: 4px solid var(--error-color);
    }

    .empty-orders {
      text-align: center;
      padding: 40px 20px;
    }

    .empty-orders-icon {
      font-size: 4rem;
      color: var(--medium-gray);
      margin-bottom: 20px;
    }

    .empty-orders h3 {
      color: var(--dark-gray);
      margin-bottom: 15px;
    }

    @media (max-width: 768px) {
      .order-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .order-details {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .details-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="index.php" class="header-house-icon">
                <i class="fas fa-home"></i>
            </a>
        </div>
    </div>

    <div class="navbar">
        <ul class="nav-list">
            <li><a href="products.php">Products</a></li>
            <li><a href="cart.php">Cart</a></li>
            <li><a href="orders.php">My Orders</a></li>
        </ul>
    </div>

    <div class="container">
        <div class="orders-container">
            <h2 class="page-title">My Orders</h2>
            
            <?php if(isset($error)): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if(empty($order_details) && empty($orders)): ?>
                <div class="empty-orders">
                    <i class="fas fa-box-open empty-orders-icon"></i>
                    <h3>You haven't placed any orders yet</h3>
                    <a href="products.php" class="view-details-btn">Browse Products</a>
                </div>
            <?php elseif(empty($order_details)): ?>
                <ul class="orders-list">
                    <?php foreach($orders as $order): ?>
                    <li class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Order #<?= htmlspecialchars($order['order_id']) ?></span>
                                <span class="order-date"><?= date('M j, Y', strtotime($order['order_date'])) ?></span>
                            </div>
                            <span class="order-status status-<?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                        </div>
                        <div class="order-details">
                            <div>
                                <div class="order-items"><?= htmlspecialchars($order['item_count']) ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></div>
                                <div class="order-total">$<?= number_format($order['total_amount'], 2) ?></div>
                            </div>
                            <a href="orders.php?order_id=<?= $order['order_id'] ?>" class="view-details-btn">View Details</a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <a href="orders.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                
                <div class="order-details-container">
                    <h3 class="details-title">Order Details #<?= htmlspecialchars($_GET['order_id']) ?></h3>
                    
                    <ul class="details-items">
                        <?php foreach($order_details as $item): ?>
                        <li class="details-item">
                            <div class="item-info">
                                <?php if($item['image_url']): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                                <?php endif; ?>
                                <div>
                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="item-quantity">Quantity: <?= htmlspecialchars($item['quantity']) ?></div>
                                </div>
                            </div>
                            <div class="item-price">$<?= number_format($item['unit_price'], 2) ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="details-summary">
                        <?php 
                        // Find the current order from the orders list
                        $current_order = null;
                        foreach($orders as $order) {
                            if($order['order_id'] == $_GET['order_id']) {
                                $current_order = $order;
                                break;
                            }
                        }
                        ?>
                        
                        <div class="summary-row">
                            <span class="summary-label">Payment Method:</span>
                            <span class="summary-value"><?= htmlspecialchars($current_order['payment_method']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Payment Status:</span>
                            <span class="summary-value"><?= htmlspecialchars($current_order['payment_status']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Shipping Address:</span>
                            <span class="summary-value"><?= htmlspecialchars($current_order['shipping_address']) ?></span>
                        </div>
                        <div class="summary-row total-row">
                            <span class="summary-label">Total:</span>
                            <span class="summary-value">$<?= number_format($current_order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>