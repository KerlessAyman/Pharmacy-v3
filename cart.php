<?php
session_start();
require_once 'config.php';

// Verify database connection
if (!isset($pdo)) {
    die("Database connection not established");
}

// Initialize variables
$error = '';
$success = '';
$user_id = $_SESSION['user_id'] ?? null;

// Function to get cart items
function getCartItems($pdo, $user_id) {
    try {
        if ($user_id) {
            $cartStmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
            $cartStmt->execute([$user_id]);
            $cart = $cartStmt->fetch();

            if ($cart) {
                $stmt = $pdo->prepare("
                    SELECT ci.*, p.name, p.price, p.stock_quantity 
                    FROM cart_items ci 
                    JOIN products p ON ci.product_id = p.product_id 
                    WHERE ci.cart_id = ?
                ");
                $stmt->execute([$cart['cart_id']]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return [];
        } else {
            $cart_items = $_SESSION['cart'] ?? [];
            foreach ($cart_items as &$item) {
                if (!isset($item['stock_quantity'])) {
                    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
                    $stmt->execute([$item['id']]);
                    $product = $stmt->fetch();
                    $item['stock_quantity'] = $product['stock_quantity'] ?? 0;
                }
            }
            unset($item);
            return $cart_items;
        }
    } catch (PDOException $e) {
        error_log("Error getting cart items: " . $e->getMessage());
        return [];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['checkout'])) {
            // Validate inputs
            $required_fields = ['name', 'email', 'phone', 'delivery_option'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("All required fields must be filled!");
                }
            }

            $name = htmlspecialchars(trim($_POST['name']));
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $phone = htmlspecialchars(trim($_POST['phone']));
            $delivery_option = $_POST['delivery_option'];
            $address = $delivery_option === 'delivery' ? htmlspecialchars(trim($_POST['address'])) : 'Store Pickup';
            $payment_method = htmlspecialchars(trim($_POST['payment'] ?? 'Cash on Delivery'));
            
            if (!$email) {
                throw new Exception("Invalid email address!");
            }

            // Get cart items
            $cart_items = getCartItems($pdo, $user_id);
            
            if (empty($cart_items)) {
                throw new Exception("Your cart is empty!");
            }
            
            // Calculate total and check stock
            $total = 0;
            $out_of_stock = [];
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock_quantity']) {
                    $out_of_stock[] = $item['name'];
                }
                $total += $item['price'] * $item['quantity'];
            }
            
            if (!empty($out_of_stock)) {
                throw new Exception("Insufficient stock for: " . implode(", ", $out_of_stock));
            }

            // Start transaction
            $pdo->beginTransaction();
            
            // First check if delivery_option column exists
            $deliveryOptionSupported = false;
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_option'");
                $deliveryOptionSupported = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                error_log("Column check failed: " . $e->getMessage());
            }
            
            // Create order with or without delivery_option based on column existence
            if ($deliveryOptionSupported) {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, payment_status, delivery_option) 
                                      VALUES (?, ?, 'Pending', ?, ?, 'Pending', ?)");
                $stmt->execute([$user_id, $total, $address, $payment_method, $delivery_option]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, payment_status) 
                                      VALUES (?, ?, 'Pending', ?, ?, 'Pending')");
                $stmt->execute([$user_id, $total, $address, $payment_method]);
            }
            
            $order_id = $pdo->lastInsertId();
            
            // Add order items and update inventory
            foreach ($cart_items as $item) {
                $product_id = $user_id ? $item['product_id'] : $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                // Add order item
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $product_id, $quantity, $price]);
                
                // Update inventory
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
                $stmt->execute([$quantity, $product_id]);
                
                // Log inventory change (if table exists)
                try {
                    $pdo->query("SELECT 1 FROM inventory_log LIMIT 1");
                    $stmt = $pdo->prepare("INSERT INTO inventory_log (product_id, quantity_change, previous_quantity, 
                                          new_quantity, reason, reference_id, reference_type) 
                                          SELECT ?, ?, stock_quantity, (stock_quantity - ?), 'Sale', ?, 'Order'
                                          FROM products WHERE product_id = ?");
                    $stmt->execute([$product_id, -$quantity, $quantity, $order_id, $product_id]);
                } catch (PDOException $e) {
                    error_log("Inventory logging skipped: " . $e->getMessage());
                }
            }
            
            // Clear cart
            if ($user_id) {
                $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $cart = $stmt->fetch();
                
                if ($cart) {
                    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                    $stmt->execute([$cart['cart_id']]);
                }
            } else {
                unset($_SESSION['cart']);
            }
            
            $pdo->commit();
            
            $success = "Order placed successfully! Order ID: #{$order_id}";
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Get current cart items for display
$cart_items = getCartItems($pdo, $user_id);
$cart_total = 0;

foreach ($cart_items as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
// Add this near the top of the file where other POST handlers are
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    try {
        $product_id = $_POST['product_id'];
        
        if ($user_id) {
            // Remove from database cart for logged-in users
            $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cart = $stmt->fetch();
            
            if ($cart) {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
                $stmt->execute([$cart['cart_id'], $product_id]);
            }
        } else {
            // Remove from session cart for guests
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ($item['id'] == $product_id) {
                        unset($_SESSION['cart'][$key]);
                        break;
                    }
                }
            }
        }
        
        header("Location: cart.php");
        exit();
    } catch (Exception $e) {
        $error = "Error removing item: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
      --primary-color: #159b85;
      --primary-dark: #12806d;
      --danger-color: #e74c3c;
      --danger-dark: #c0392b;
      --light-gray: #f4f4f4;
      --medium-gray: #ddd;
      --dark-gray: #333;
      --white: #fff;
      --black: #000;
      --success-color: #27ae60;
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

    .cart-container {
      background: var(--white);
      padding: 30px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      margin: 0 auto 40px;
      max-width: 1000px;
    }

    .cart-title {
      text-align: center;
      color: var(--primary-color);
      margin-bottom: 30px;
      position: relative;
      padding-bottom: 15px;
    }

    .cart-title::after {
      content: "";
      position: absolute;
      width: 80px;
      height: 3px;
      background-color: var(--primary-color);
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
    }

    .cart-items {
      list-style: none;
      padding: 0;
      margin: 0 0 30px;
    }

    .cart-item {
      background-color: var(--white);
      padding: 15px 20px;
      margin: 10px 0;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: var(--transition);
    }

    .cart-item:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .item-info {
      flex: 1;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 15px;
    }

    .item-name {
      font-weight: 600;
    }

    .item-price {
      font-weight: bold;
      color: var(--primary-color);
      min-width: 80px;
      text-align: right;
    }

    .quantity-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 0 20px;
    }

    .quantity-btn {
      background-color: var(--primary-color);
      color: var(--white);
      border: none;
      border-radius: 4px;
      width: 28px;
      height: 28px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
    }

    .quantity-btn:hover {
      background-color: var(--primary-dark);
    }

    .quantity-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      background-color: var(--medium-gray);
    }

    .quantity {
      font-weight: bold;
      min-width: 25px;
      text-align: center;
    }

    .button {
      background-color: var(--primary-color);
      color: var(--white);
      padding: 12px 25px;
      border: none;
      border-radius: 30px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      text-align: center;
      display: inline-block;
      text-decoration: none;
    }

    .button:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(21, 155, 133, 0.3);
    }

    .remove-btn {
      background-color: var(--danger-color);
      color: var(--white);
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: var(--transition);
    }

    .remove-btn:hover {
      background-color: var(--danger-dark);
    }

    .cart-total {
      text-align: right;
      margin: 20px 0;
      font-size: 1.3rem;
      padding: 15px;
      background: var(--light-gray);
      border-radius: var(--border-radius);
      font-weight: bold;
      border-left: 4px solid var(--primary-color);
    }

    .cart-total strong {
      color: var(--primary-color);
    }

    .empty-cart {
      text-align: center;
      padding: 40px 20px;
    }

    .empty-cart-icon {
      font-size: 4rem;
      color: var(--medium-gray);
      margin-bottom: 20px;
    }

    .empty-cart h3 {
      color: var(--dark-gray);
      margin-bottom: 15px;
    }

    .checkout-form {
      background: var(--white);
      padding: 25px;
      border-radius: var(--border-radius);
      margin-top: 30px;
      box-shadow: var(--box-shadow);
    }

    .checkout-form h3 {
      color: var(--primary-color);
      margin-top: 0;
      text-align: center;
      padding-bottom: 15px;
      margin-bottom: 25px;
      border-bottom: 1px solid var(--medium-gray);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--dark-gray);
    }

    .form-group input, .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid var(--medium-gray);
      border-radius: var(--border-radius);
      font-size: 15px;
      transition: var(--transition);
    }

    .form-group input:focus, .form-group select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 5px rgba(21, 155, 133, 0.2);
    }

    .delivery-options {
      margin-bottom: 25px;
      padding: 15px;
      background: var(--light-gray);
      border-radius: var(--border-radius);
    }

    .delivery-options h4 {
      font-weight: 600;
      color: var(--dark-gray);
      margin-bottom: 15px;
    }

    .delivery-option {
      margin-bottom: 10px;
    }

    .delivery-option label {
      display: flex;
      align-items: center;
      font-weight: normal;
      cursor: pointer;
      transition: var(--transition);
    }

    .delivery-option label:hover {
      color: var(--primary-color);
    }

    .delivery-option input {
      width: auto;
      margin-right: 10px;
    }

    .payment-methods {
      margin-bottom: 25px;
      padding: 15px;
      background: var(--light-gray);
      border-radius: var(--border-radius);
      display: none;
    }

    .payment-methods h4 {
      font-weight: 600;
      color: var(--dark-gray);
      margin-bottom: 15px;
    }

    .payment-method {
      margin-bottom: 10px;
    }

    .payment-method label {
      display: flex;
      align-items: center;
      font-weight: normal;
      cursor: pointer;
    }

    .payment-method input {
      width: auto;
      margin-right: 10px;
    }

    .checkout-btn {
      width: 100%;
      padding: 15px;
      font-size: 1.1rem;
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

    .success {
      background-color: #e8f8f0;
      color: var(--success-color);
      border-left: 4px solid var(--success-color);
    }

    .success .button {
      margin-top: 15px;
    }

    .address-group {
      display: none;
      margin-top: 15px;
    }

    @media (max-width: 768px) {
      .cart-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .item-info {
        width: 100%;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--medium-gray);
      }
      
      .quantity-controls {
        margin: 10px 0;
      }
      
      .item-actions {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        <div class="cart-container">
            <h2 class="cart-title">Your Shopping Cart</h2>
            
            <?php if($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert success">
                    <?= htmlspecialchars($success) ?>
                    <a href="products.php" class="button">Continue Shopping</a>
                </div>
            <?php else: ?>
                <?php if(empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart empty-cart-icon"></i>
                        <h3>Your cart is empty</h3>
                        <a href="products.php" class="button">Browse Products</a>
                    </div>
                <?php else: ?>
                    <ul class="cart-items">
                        <?php foreach($cart_items as $item): ?>
                        <li class="cart-item">
                            <div class="item-info">
                                <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                <span class="item-price">$<?= number_format($item['price'], 2) ?></span>
                            </div>
                            <div class="quantity-controls">
                                <button class="quantity-btn" data-action="decrease" data-id="<?= $item['id'] ?? $item['product_id'] ?>">-</button>
                                <span class="quantity"><?= $item['quantity'] ?></span>
                                <button class="quantity-btn" data-action="increase" data-id="<?= $item['id'] ?? $item['product_id'] ?>">+</button>
                            </div>
                            <button class="remove-btn" data-id="<?= $item['id'] ?? $item['product_id'] ?>">Remove</button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="cart-total">
                        <strong>Total: $<?= number_format($cart_total, 2) ?></strong>
                    </div>
                    
                    <form method="post" class="checkout-form">
                        <h3>Checkout Information</h3>
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?= isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?= isset($_SESSION['user_phone']) ? htmlspecialchars($_SESSION['user_phone']) : '' ?>">
                        </div>
                        
                        <div class="delivery-options">
                            <h4>Delivery Option *</h4>
                            <div class="delivery-option">
                                <label>
                                    <input type="radio" name="delivery_option" value="delivery" checked> 
                                    Home Delivery (+$5 delivery fee)
                                </label>
                            </div>
                            <div class="delivery-option">
                                <label>
                                    <input type="radio" name="delivery_option" value="pickup"> 
                                    Store Pickup (No delivery fee)
                                </label>
                            </div>
                            
                            <div id="addressGroup" class="address-group">
                                <div class="form-group">
                                    <label for="address">Shipping Address *</label>
                                    <input type="text" id="address" name="address"
                                           value="<?= isset($_SESSION['user_address']) ? htmlspecialchars($_SESSION['user_address']) : '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div id="paymentMethods" class="payment-methods">
                            <h4>Payment Method *</h4>
                            <div class="payment-method">
                                <label>
                                    <input type="radio" name="payment" value="Cash on Delivery" checked> 
                                    Cash on Delivery
                                </label>
                            </div>
                            <div class="payment-method">
                                <label>
                                    <input type="radio" name="payment" value="Credit Card"> 
                                    Credit Card
                                </label>
                            </div>
                            <div class="payment-method">
                                <label>
                                    <input type="radio" name="payment" value="Mobile Payment"> 
                                    Mobile Payment
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="checkout" class="checkout-btn">Place Order</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle delivery option changes
        const deliveryOptions = document.querySelectorAll('input[name="delivery_option"]');
        const addressGroup = document.getElementById('addressGroup');
        const paymentMethods = document.getElementById('paymentMethods');
        
        function updateDeliveryOptions() {
            const selectedOption = document.querySelector('input[name="delivery_option"]:checked').value;
            
            if (selectedOption === 'delivery') {
                addressGroup.style.display = 'block';
                paymentMethods.style.display = 'block';
                document.getElementById('address').setAttribute('required', '');
            } else {
                addressGroup.style.display = 'none';
                paymentMethods.style.display = 'block';
                document.getElementById('address').removeAttribute('required');
            }
        }
        
        deliveryOptions.forEach(option => {
            option.addEventListener('change', updateDeliveryOptions);
        });
        
        // Initialize on page load
        updateDeliveryOptions();
        
        // Quantity adjustment
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action;
                const productId = this.dataset.id;
                const isLoggedIn = <?= $user_id ? 'true' : 'false' ?>;
                
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        action: action,
                        is_logged_in: isLoggedIn
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            });
        });
        
        // Replace the remove button event listener with:
document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Remove this item from your cart?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cart.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_id';
            input.value = this.dataset.id;
            form.appendChild(input);
            
            const action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'remove_item';
            action.value = '1';
            form.appendChild(action);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
});
    });
    </script>
</body>
</html>