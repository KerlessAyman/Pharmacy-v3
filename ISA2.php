<?php
session_start();

// Check if user is logged in and has pharmacist/admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'pharmacist' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tubia_pharmacy');

// Create database connection
try {
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit Medicine
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_medicine' || $action === 'edit_medicine') {
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = (float)$_POST['price'];
            $cost_price = (float)$_POST['cost_price'];
            $category = $_POST['category'];
            $subcategory = $_POST['subcategory'];
            $stock_quantity = (int)$_POST['stock_quantity'];
            $barcode = $_POST['barcode'];
            $is_prescription_required = isset($_POST['is_prescription_required']) ? 1 : 0;
            $expiry_date = $_POST['expiry_date'];
            $manufacturer = $_POST['manufacturer'];
            
            if ($action === 'add_medicine') {
                $stmt = $conn->prepare("INSERT INTO Products 
                    (name, description, price, cost_price, category, subcategory, 
                    stock_quantity, barcode, is_prescription_required, expiry_date, manufacturer) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name, $description, $price, $cost_price, $category, $subcategory,
                    $stock_quantity, $barcode, $is_prescription_required, $expiry_date, $manufacturer
                ]);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE Products SET 
                    name = ?, description = ?, price = ?, cost_price = ?, category = ?, 
                    subcategory = ?, stock_quantity = ?, barcode = ?, 
                    is_prescription_required = ?, expiry_date = ?, manufacturer = ?
                    WHERE product_id = ?");
                $stmt->execute([
                    $name, $description, $price, $cost_price, $category, $subcategory,
                    $stock_quantity, $barcode, $is_prescription_required, $expiry_date, $manufacturer, $id
                ]);
            }
        } 
        elseif ($action === 'delete_medicine') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM Products WHERE product_id = ?");
            $stmt->execute([$id]);
        }
        elseif ($action === 'add_review') {
            $content = $_POST['content'];
            $rating = (int)$_POST['rating'];
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO Reviews (user_id, content, rating) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $content, $rating]);
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch dashboard statistics
$stats = [];
$stmt = $conn->query("SELECT 
    (SELECT SUM(total_amount) FROM Orders WHERE status = 'Delivered') as total_sales,
    (SELECT COUNT(*) FROM Products) as medicine_count,
    (SELECT COUNT(*) FROM Products WHERE expiry_date < CURDATE()) as expired_count");
$stats = $stmt->fetch();

// Fetch medicines inventory
$stmt = $conn->query("SELECT * FROM Products ORDER BY expiry_date");
$medicines = $stmt->fetchAll();

// Fetch reviews
$stmt = $conn->query("SELECT r.*, u.full_name FROM Reviews r JOIN Users u ON r.user_id = u.user_id ORDER BY r.created_at DESC");
$reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard</title>
    <link rel="stylesheet" href="ISA2.css">
     <style>
            .dashboard-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 30px;
                background-color: #448444;
                color: white;
                border-radius: 10px;
                margin-bottom: 30px;
            }

            .header-content h1 {
                margin: 0;
                font-size: 24px;
            }

            .user-greeting {
                margin: 5px 0 0;
                font-size: 14px;
                opacity: 0.9;
            }

            .user-role {
                font-style: italic;
            }

            .view-prescription-btn {
                background-color: #fff;
                color: #1a5276;
                padding: 10px 20px;
                border-radius: 25px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                transition: all 0.3s ease;
                font-weight: 600;
            }

            .view-prescription-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }

            .view-prescription-btn i {
                margin-right: 10px;
                font-size: 18px;
            }
        </style>
       
</head>
<body>
     <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Pharmacy Dashboard</h1>
                <p class="user-greeting">
                    Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                    <span class="user-role">(<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                </p>
            </div>
            <a href="read_prescription.php" class="view-prescription-btn">
                <i class="fas fa-file-medical"></i>
                View Prescriptions
            </a>
        </header>

        <div class="stats-container">
            <div class="stat-box">
                <h2>Total Sales</h2>
                <p>$<?php echo number_format($stats['total_sales'] ?? 0, 2); ?></p>
            </div>
            <div class="stat-box">
                <h2>Number of Medicines</h2>
                <p><?php echo $stats['medicine_count'] ?? 0; ?></p>
            </div>
            <div class="stat-box">
                <h2>Expired Medicines</h2>
                <p><?php echo $stats['expired_count'] ?? 0; ?></p>
            </div>
        </div>

        <div class="inventory">
            <h2>Inventory Management</h2>
            <button id="addMedicineBtn">Add New Medicine</button>
            <table id="inventoryTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Cost</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th>Quantity</th>
                        <th>Barcode</th>
                        <th>Rx Required</th>
                        <th>Expiry Date</th>
                        <th>Manufacturer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                            <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                            <td>$<?php echo number_format($medicine['price'], 2); ?></td>
                            <td>$<?php echo number_format($medicine['cost_price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($medicine['category']); ?></td>
                            <td><?php echo htmlspecialchars($medicine['subcategory']); ?></td>
                            <td><?php echo htmlspecialchars($medicine['stock_quantity']); ?></td>
                            <td><?php echo htmlspecialchars($medicine['barcode']); ?></td>
                            <td><?php echo $medicine['is_prescription_required'] ? 'Yes' : 'No'; ?></td>
                            <td class="<?php 
                                $today = new DateTime();
                                $expiry = new DateTime($medicine['expiry_date']);
                                if ($expiry < $today) echo 'expired';
                                elseif ($today->diff($expiry)->days <= 30) echo 'warning';
                            ?>">
                                <?php echo date('M d, Y', strtotime($medicine['expiry_date'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($medicine['manufacturer']); ?></td>
                            <td>
                                <button class="edit-btn" onclick="openEditModal(
                                    <?php echo $medicine['product_id']; ?>,
                                    '<?php echo addslashes($medicine['name']); ?>',
                                    '<?php echo addslashes($medicine['description']); ?>',
                                    <?php echo $medicine['price']; ?>,
                                    <?php echo $medicine['cost_price']; ?>,
                                    '<?php echo addslashes($medicine['category']); ?>',
                                    '<?php echo addslashes($medicine['subcategory']); ?>',
                                    <?php echo $medicine['stock_quantity']; ?>,
                                    '<?php echo addslashes($medicine['barcode']); ?>',
                                    <?php echo $medicine['is_prescription_required']; ?>,
                                    '<?php echo $medicine['expiry_date']; ?>',
                                    '<?php echo addslashes($medicine['manufacturer']); ?>'
                                )">Edit</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_medicine">
                                    <input type="hidden" name="id" value="<?php echo $medicine['product_id']; ?>">
                                    <button type="submit" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="daily-review">
            <h2>Daily Review</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_review">
                <textarea name="content" placeholder="Write your review..." required style="width:100%; height:100px;"></textarea>
                <div class="rating-stars">
                    <input type="hidden" name="rating" id="ratingValue" value="0">
                    <span class="star" onclick="setRating(1)">★</span>
                    <span class="star" onclick="setRating(2)">★</span>
                    <span class="star" onclick="setRating(3)">★</span>
                    <span class="star" onclick="setRating(4)">★</span>
                    <span class="star" onclick="setRating(5)">★</span>
                </div>
                <button type="submit">Submit Review</button>
            </form>
            
            <div class="review-list">
                <?php foreach ($reviews as $review): ?>
                    <div style="border-bottom:1px solid #eee; padding:10px 0;">
                        <div style="display:flex; justify-content:space-between;">
                            <span><?php echo htmlspecialchars($review['full_name']); ?></span>
                            <span><?php echo date('M d, Y H:i', strtotime($review['created_at'])); ?></span>
                        </div>
                        <div style="color:gold;">
                            <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                        </div>
                        <p><?php echo htmlspecialchars($review['content']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Medicine Modal -->
    <div id="medicineModal" class="modal">
        <div class="modal-content">
            <span style="float:right;cursor:pointer;" onclick="closeModal()">×</span>
            <h2 id="modalTitle">Add New Medicine</h2>
            <form method="POST" id="medicineForm">
                <input type="hidden" name="action" id="formAction" value="add_medicine">
                <input type="hidden" name="id" id="medicineId">
                
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" id="medicineName" required>
                </div>
                
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" id="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Price ($):</label>
                    <input type="number" name="price" id="price" min="0.01" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Cost Price ($):</label>
                    <input type="number" name="cost_price" id="cost_price" min="0.01" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Category:</label>
                    <select name="category" id="category" required>
                        <option value="">Select</option>
                        <option value="Skin Care">Skin Care</option>
                        <option value="Hair Care">Hair Care</option>
                        <option value="Medicines">Medicines</option>
                        <option value="Vitamins">Vitamins</option>
                        <option value="First Aid">First Aid</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subcategory:</label>
                    <input type="text" name="subcategory" id="subcategory">
                </div>
                
                <div class="form-group">
                    <label>Quantity:</label>
                    <input type="number" name="stock_quantity" id="stock_quantity" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Barcode:</label>
                    <input type="text" name="barcode" id="barcode">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_prescription_required" id="is_prescription_required" value="1">
                        Prescription Required
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Expiry Date:</label>
                    <input type="date" name="expiry_date" id="expiryDate" required>
                </div>
                
                <div class="form-group">
                    <label>Manufacturer:</label>
                    <input type="text" name="manufacturer" id="manufacturer">
                </div>
                
                <div style="text-align:right; margin-top:20px;">
                    <button type="button" onclick="closeModal()">Cancel</button>
                    <button type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openEditModal(id, name, description, price, cost_price, category, subcategory, 
                             stock_quantity, barcode, is_prescription_required, expiry_date, manufacturer) {
            document.getElementById('modalTitle').textContent = 'Edit Medicine';
            document.getElementById('formAction').value = 'edit_medicine';
            document.getElementById('medicineId').value = id;
            document.getElementById('medicineName').value = name;
            document.getElementById('description').value = description;
            document.getElementById('price').value = price;
            document.getElementById('cost_price').value = cost_price;
            document.getElementById('category').value = category;
            document.getElementById('subcategory').value = subcategory;
            document.getElementById('stock_quantity').value = stock_quantity;
            document.getElementById('barcode').value = barcode;
            document.getElementById('is_prescription_required').checked = is_prescription_required == 1;
            document.getElementById('expiryDate').value = expiry_date;
            document.getElementById('manufacturer').value = manufacturer;
            
            document.getElementById('medicineModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('medicineModal').style.display = 'none';
        }
        
        // Rating stars
        function setRating(rating) {
            document.getElementById('ratingValue').value = rating;
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.classList.toggle('selected', index < rating);
            });
        }
        
        // Add medicine button
        document.getElementById('addMedicineBtn').addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Add New Medicine';
            document.getElementById('formAction').value = 'add_medicine';
            document.getElementById('medicineForm').reset();
            document.getElementById('medicineModal').style.display = 'block';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('medicineModal')) {
                closeModal();
            }
        });
        
    </script>
    
</body>
</html>