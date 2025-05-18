<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tubia_pharmacy');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $username = trim($conn->real_escape_string($_POST['username'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($conn->real_escape_string($_POST['full_name'] ?? ''));
    $phone = trim($conn->real_escape_string($_POST['phone'] ?? ''));
    $address = trim($conn->real_escape_string($_POST['address'] ?? ''));
    $city = trim($conn->real_escape_string($_POST['city'] ?? ''));
    $postal_code = trim($conn->real_escape_string($_POST['postal_code'] ?? ''));

    // Validate inputs
    $errors = [];
    
    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        $errors[] = "Username must be 4-20 characters (letters, numbers, _)";
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter and one number";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Full name validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    // If no validation errors, proceed with registration
    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $errors[] = "Username or email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user
                $stmt = $conn->prepare("
                    INSERT INTO Users 
                    (username, email, password, full_name, phone, address, city, postal_code, role) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'customer')
                ");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("ssssssss", 
                    $username, 
                    $email, 
                    $hashed_password, 
                    $full_name, 
                    $phone, 
                    $address, 
                    $city, 
                    $postal_code
                );
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Registration successful! You can now login.";
                    header("Location: login.php");
                    exit();
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // Store errors and form data in session
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postal_code
        ];
    }
    
    // Redirect back to form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Pharmacy System</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #388E3C;
            --error-color: #F44336;
            --success-color: #4CAF50;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --white: #fff;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-image: url('Photo/Home.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .signup-container {
            background-color: rgba(232, 245, 233, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            width: 350px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        h2 {
            color: var(--text-color);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            border: none;
            background-color: var(--primary-color);
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: var(--secondary-color);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: var(--error-color);
            font-size: 12px;
            margin: 10px 0;
            text-align: center;
        }
        
        .error-list {
            list-style-type: none;
            padding: 0;
            margin: 0 0 15px 0;
        }
        
        .success-message {
            color: var(--success-color);
            font-size: 14px;
            margin: 10px 0;
            text-align: center;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            background: #ddd;
            margin-top: 5px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        /* Scrollbar styling */
        .signup-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .signup-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .signup-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Create Account</h2>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="error-message">
                <ul class="error-list">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>
        
        <form id="signupForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required
                       value="<?= htmlspecialchars($_SESSION['form_data']['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Your email address" required
                       value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password (min 8 characters)" required>
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Your full name" required
                       value="<?= htmlspecialchars($_SESSION['form_data']['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="Your phone number"
                       value="<?= htmlspecialchars($_SESSION['form_data']['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" placeholder="Your street address"
                       value="<?= htmlspecialchars($_SESSION['form_data']['address'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" placeholder="Your city"
                       value="<?= htmlspecialchars($_SESSION['form_data']['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" placeholder="Your postal code"
                       value="<?= htmlspecialchars($_SESSION['form_data']['postal_code'] ?? '') ?>">
            </div>

            <button type="submit">Sign Up</button>
        </form>
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p>Are You Admin? <a href="pin_verification.php">SignUp here</a></p>
        </div>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;
            
            // Update strength bar
            const colors = ['#ff0000', '#ff5a00', '#ffb400', '#a0ff00', '#00ff00'];
            strengthBar.style.width = (strength * 25) + '%';
            strengthBar.style.backgroundColor = colors[strength];
        });
        
        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters');
                e.preventDefault();
                return;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
<?php
// Clear form data from session
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>