<?php
session_start();
require 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch user data
$user = [];
$stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    header("Location: index.php");
    exit();
}

// Fetch user statistics
$stats = [
    'orders' => 0,
    'wishlist' => 0,
    'reviews' => 0
];

// Get order count
$order_stmt = $conn->prepare("SELECT COUNT(*) FROM Orders WHERE user_id = ?");
$order_stmt->bind_param("i", $_SESSION['user_id']);
$order_stmt->execute();
$stats['orders'] = $order_stmt->get_result()->fetch_row()[0];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    // Update profile information
    if (isset($_POST['update_profile'])) {
        $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $postal_code = filter_input(INPUT_POST, 'postal_code', FILTER_SANITIZE_STRING);
        
        $stmt = $conn->prepare("UPDATE Users SET 
                               full_name = ?,
                               phone = ?,
                               address = ?,
                               city = ?,
                               postal_code = ?
                               WHERE user_id = ?");
        
        $stmt->bind_param("sssssi", $full_name, $phone, $address, $city, $postal_code, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Refresh user data
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                $stmt->execute();
                
                $_SESSION['password_change_success'] = "Password changed successfully!";
                header("Location: profile.php");
                exit();
            } else {
                $_SESSION['password_error'] = "New passwords don't match!";
            }
        } else {
            $_SESSION['password_error'] = "Current password is incorrect!";
        }
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_picture'])) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file = $_FILES['profile_picture'];

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $upload_dir = 'uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = $_SESSION['user_id'] . '_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Delete old profile picture if exists
                if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }
                
                $stmt = $conn->prepare("UPDATE Users SET profile_picture = ? WHERE user_id = ?");
                $stmt->bind_param("si", $target_path, $_SESSION['user_id']);
                $stmt->execute();
                
                $_SESSION['success_message'] = "Profile picture updated successfully!";
                header("Location: profile.php");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Invalid file type or size too large (max 2MB)";
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Pharmacy System</title>
    <style>
        /* Your existing CSS styles */
        .success-message {
            color: green;
            padding: 10px;
            margin: 10px 0;
            background: #e6ffe6;
            border-radius: 5px;
        }
        .error-message {
            color: red;
            padding: 10px;
            margin: 10px 0;
            background: #ffebeb;
            border-radius: 5px;
        }
            .success-message {
        color: green;
        padding: 10px;
        margin: 10px 0;
        background: #e6ffe6;
        border-radius: 5px;
    }
    .error-message {
        color: red;
        padding: 10px;
        margin: 10px 0;
        background: #ffebeb;
        border-radius: 5px;
    }
    :root {
  --primary-green: #00e454;
  --dark-green: #00a03e;
  --light-green: #e0f7e9;
  --white: #ffffff;
  --gray: #f5f5f5;
}

body {
  font-family: 'Arial', sans-serif;
  background-color: var(--light-green);
  margin: 0;
  min-height: 100vh;
  padding: 20px;
}

.main-container {
  display: flex;
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.profile-data {
  width: 350px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.profile-card {
  background: var(--white);
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0, 228, 84, 0.1);
}

.profile-header {
  text-align: center;
  margin-bottom: 25px;
}

.profile-picture {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  background-color: #d1fae0;
  margin: 0 auto 15px;
  cursor: pointer;
  border: 5px solid var(--primary-green);
  overflow: hidden;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--dark-green);
  font-size: 60px;
  font-weight: bold;
}

.profile-picture img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-name {
  color: var(--dark-green);
  margin: 15px 0;
  font-size: 28px;
}

.profile-email {
  color: #666;
  margin-bottom: 20px;
}

.profile-stats {
  display: flex;
  justify-content: space-around;
  margin: 20px 0;
}

.stat {
  text-align: center;
}

.stat-value {
  color: var(--primary-green);
  font-size: 24px;
  font-weight: bold;
}

.stat-label {
  color: #666;
  font-size: 14px;
}

.nav-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.nav-box {
  background: var(--white);
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0, 228, 84, 0.1);
  transition: all 0.3s ease;
}

.nav-box:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0, 228, 84, 0.2);
}

.nav-box h3 {
  color: var(--dark-green);
  margin-top: 0;
  border-bottom: 2px solid var(--light-green);
  padding-bottom: 10px;
}

.info-item {
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--light-green);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-label {
  color: var(--dark-green);
  font-weight: bold;
  margin-right: 10px;
}

.editable-field {
  border: 1px solid #ddd;
  padding: 8px;
  border-radius: 4px;
  width: 60%;
}

.btn {
  padding: 12px 30px;
  background-color: var(--primary-green);
  color: white;
  border: none;
  border-radius: 25px;
  cursor: pointer;
  font-weight: bold;
  transition: all 0.3s ease;
  margin-top: 20px;
  display: inline-block;
  text-align: center;
}

.btn:hover {
  background-color: var(--dark-green);
  transform: translateY(-2px);
}

.btn-edit {
  background-color: transparent;
  border: 2px solid var(--primary-green);
  color: var(--primary-green);
}

.btn-edit:hover {
  background-color: var(--primary-green);
  color: white;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: auto;
}

.quick-links {
  display: flex;
  gap: 10px;
  margin-top: 20px;
  justify-content: center;
}

.success-message {
  color: #155724;
  background-color: #d4edda;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.error-message {
  color: #721c24;
  background-color: #f8d7da;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}

/* Responsive styles */
@media (max-width: 768px) {
  .main-container {
    flex-direction: column;
  }

  .profile-data {
    width: 100%;
  }

  .editable-field {
    width: 50%;
  }
  
  .info-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .editable-field {
    width: 100%;
  }
  
  .profile-stats {
    flex-direction: column;
    gap: 15px;
  }
}
 
    </style>
</head>
<body>
    <div class="main-container">
        <!-- LEFT SIDE - PROFILE DATA -->
        <div class="profile-data">
            <div class="profile-card">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="success-message"><?= $_SESSION['success_message'] ?></div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="error-message"><?= $_SESSION['error_message'] ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <div class="profile-header">
                    <form method="POST" enctype="multipart/form-data" id="profilePictureForm" style="display:none;">
                        <input type="file" name="profile_picture" id="profileUpload" accept="image/*">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    </form>
                    <div class="profile-picture" onclick="document.getElementById('profileUpload').click()">
                        <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile">
                        <?php else: ?>
                            <?= strtoupper(substr($user['full_name'] ?? 'J', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <h2 class="profile-name"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></h2>
                    <p class="profile-email"><?= htmlspecialchars($user['email'] ?? 'email@example.com') ?></p>
                    <div class="profile-stats">
                        <div class="stat">
                            <div class="stat-value"><?= $stats['orders'] ?></div>
                            <div class="stat-label">Orders</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?= $user['role'] ?></div>
                            <div class="stat-label">Role</div>
                        </div>
                    </div>
                </div>
                <div class="quick-links">
                    <a href="products.php" class="btn">Products</a>
                    <a href="cart.php" class="btn btn-edit">Cart</a>
                </div>
                <div class="action-buttons">
                    <form method="POST" action="index.html">
                        <button type="submit" class="btn">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE - EDITABLE FIELDS -->
        <div class="nav-section">
            <form method="POST" id="profileForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="update_profile" value="1">

                <!-- Personal Information Box -->
                <div class="nav-box">
                    <h3>Personal Information</h3>
                    <div class="info-item">
                        <span class="info-label">Full Name</span>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                               class="editable-field" required>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                               class="editable-field">
                    </div>
                    <button type="submit" class="btn">Save Changes</button>
                </div>

                <!-- Address Box -->
                <div class="nav-box">
                    <h3>Address</h3>
                    <div class="info-item">
                        <span class="info-label">Address</span>
                        <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" 
                               class="editable-field">
                    </div>
                    <div class="info-item">
                        <span class="info-label">City</span>
                        <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" 
                               class="editable-field">
                    </div>
                    <div class="info-item">
                        <span class="info-label">Postal Code</span>
                        <input type="text" name="postal_code" value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>" 
                               class="editable-field">
                    </div>
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>

            <!-- Password Change Form -->
            <form method="POST" id="passwordForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="nav-box">
                    <h3>Change Password</h3>
                    <?php if (isset($_SESSION['password_error'])): ?>
                        <div class="error-message"><?= $_SESSION['password_error'] ?></div>
                        <?php unset($_SESSION['password_error']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['password_change_success'])): ?>
                        <div class="success-message"><?= $_SESSION['password_change_success'] ?></div>
                        <?php unset($_SESSION['password_change_success']); ?>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Current Password</span>
                        <input type="password" name="current_password" class="editable-field" required>
                    </div>
                    <div class="info-item">
                        <span class="info-label">New Password</span>
                        <input type="password" name="new_password" class="editable-field" required>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Confirm Password</span>
                        <input type="password" name="confirm_password" class="editable-field" required>
                    </div>
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-submit picture form when file selected
        document.getElementById('profileUpload').addEventListener('change', function() {
            document.getElementById('profilePictureForm').submit();
        });

        // Auto-submit profile form when any field changes
        document.querySelectorAll('.editable-field').forEach(field => {
            field.addEventListener('change', function() {
                if (this.form.id === 'profileForm') {
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>