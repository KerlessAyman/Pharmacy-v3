<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tubia_pharmacy";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to fetch orders from database
function getOrders($conn, $search = '') {
    $sql = "SELECT o.*, u.username, u.full_name 
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " WHERE o.order_id LIKE '%$search%' 
                  OR u.username LIKE '%$search%' 
                  OR u.full_name LIKE '%$search%'";
    }
    $sql .= " ORDER BY o.order_date DESC";
    
    $result = $conn->query($sql);
    $orders = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Get order items
            $items = [];
            $itemsResult = $conn->query("SELECT * FROM order_items WHERE order_id = '".$row['order_id']."'");
            if ($itemsResult->num_rows > 0) {
                while($item = $itemsResult->fetch_assoc()) {
                    $items[] = $item;
                }
            }
            
            $orders[] = [
                'orderId' => $row['order_id'],
                'customerName' => $row['full_name'] ?? $row['username'], // Use full name if available, otherwise username
                'date' => $row['order_date'],
                'status' => $row['status'],
                'total' => $row['total_amount'],
                'items' => $items
            ];
        }
    }
    
    return $orders;
}

// Handle search request
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$orders = getOrders($conn, $searchTerm);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    
    <link rel="stylesheet" href="ISA3.css">
</head>

<body>
    <div class="orders-container">
        <header class="orders-header">
            <h1>Order Management</h1>

        </header>

        <div class="search-filter">
            <form method="GET" action="">
                <input type="text" name="search" id="searchInput" placeholder="Search by Order ID or Customer Name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="orders-list">
            <table id="ordersTable" border="1" cellspacing="0" cellpadding="10">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['orderId']); ?></td>
                        <td><?php echo htmlspecialchars($order['customerName']); ?></td>
                        <td><?php echo htmlspecialchars($order['date']); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                        <td>
                            <button onclick="viewOrderDetails('<?php echo $order['orderId']; ?>')">View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Order Details Popup -->
        <div id="orderDetailsPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup()">&times;</span>
                <h2>Order Details</h2>
                <div id="orderDetails"></div>
                <div class="order-actions">
                    <button onclick="updateOrderStatus()">Update Status</button>
                    <button onclick="printInvoice()">Print Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <div class="links" style="position: fixed; bottom: 20px; right: 20px; font-size: 16px; background-color: rgba(50, 205, 50, 0.2); padding: 10px 15px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
        <a href="index.php" style="color: #50a150; text-decoration: none; font-weight: bold;">Home Page</a>
    </div>

    <script>
        // Function to view order details (now using AJAX)
        function viewOrderDetails(orderId) {
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(order => {
                    const orderDetailsDiv = document.getElementById("orderDetails");
                    orderDetailsDiv.innerHTML = `
                        <p><strong>Order ID:</strong> ${order.orderId}</p>
                        <p><strong>Customer Name:</strong> ${order.customerName}</p>
                        <p><strong>Date:</strong> ${order.date}</p>
                        <p><strong>Status:</strong> ${order.status}</p>
                        <p><strong>Total:</strong> $${order.total.toFixed(2)}</p>
                        <h3>Items:</h3>
                        <ul>
                            ${order.items.map(item => `<li>${item.name} - ${item.quantity} x $${item.price.toFixed(2)}</li>`).join('')}
                        </ul>
                    `;
                    document.getElementById("orderDetailsPopup").style.display = "flex";
                })
                .catch(error => console.error('Error:', error));
        }

        // Close the popup
        function closePopup() {
            document.getElementById("orderDetailsPopup").style.display = "none";
        }

        // Function to update order status
        function updateOrderStatus() {
            const orderId = document.querySelector("#orderDetails p:first-child").textContent.replace("Order ID: ", "");
            const newStatus = prompt("Enter new status (Pending/Processing/Shipped/Delivered/Cancelled):");
            
            if (newStatus) {
                fetch('update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${encodeURIComponent(orderId)}&status=${encodeURIComponent(newStatus)}`
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    location.reload(); // Refresh the page to see changes
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // Function to print invoice
        function printInvoice() {
            const orderId = document.querySelector("#orderDetails p:first-child").textContent.replace("Order ID: ", "");
            window.open('print_invoice.php?order_id=' + orderId, '_blank');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>