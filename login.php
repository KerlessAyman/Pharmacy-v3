<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tubia_pharmacy');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Get user from database
        $stmt = $conn->prepare("SELECT user_id, email, password, full_name, role FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                // Redirect to products page
                header("Location: products.php");
                exit();
                }
               else {
                  // Redirect to products page
                header("Location: ISA2.php");
                exit();
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background-image: url(Photo/Home.jpg);
      background-color: #f0f8ff;
      height: 100vh;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-container {
      background-color: #e8f5e9;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 320px;
    }

    h2 {
      color: #333;
      margin-bottom: 20px;
    }

    label {
      display: block;
      text-align: left;
      margin-top: 15px;
      margin-bottom: 5px;
      font-weight: bold;
      color: #555;
    }

    input, button {
      display: block;
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }

    input:focus, button:focus {
      outline: none;
      border-color: #4caf50;
    }

    button {
      background-color: #4caf50;
      color: white;
      cursor: pointer;
      font-size: 16px;
      margin-top: 20px;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: black;
    }

    .register-link {
      margin-top: 20px;
      color: #333;
    }

    .register-link a {
      color: #4caf50;
      text-decoration: none;
    }

    .register-link a:hover {
      text-decoration: underline;
    }

    .error-message {
      color: #f44336;
      margin: 10px 0;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <h2>Login</h2>
    
    <?php if (isset($error)): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="Enter your email" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>

      <button type="submit">Login</button>
    </form>
    <div class="register-link">
      <p>Don't have an account? <a href="signup.php">Register</a></p>
    </div>
  </div>

</body>
</html>