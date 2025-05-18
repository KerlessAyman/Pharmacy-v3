<?php


// Configuration
$SECRET_PIN = '1234'; 


if(isset($_SESSION['pin_verified']) && $_SESSION['pin_verified'] === true) {
    header('Location: signup_admin.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredPin = $_POST['pin'] ?? '';
    
    if(password_verify($enteredPin, password_hash($SECRET_PIN, PASSWORD_DEFAULT))) {
        $_SESSION['pin_verified'] = true;
        header('Location: signup_admin.php');
        exit();
    } else {
        $error = "Invalid PIN Code";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verification</title>
    <link rel="stylesheet" href="ISA3.css">
   <style>
    body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background-image: url('Photo/Home.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed; /* اختياري - يثبت الخلفية عند التمرير */
}
    .pin-container {
        max-width: 400px;
        margin: 100px auto;
        padding: 30px;
        background: #f0faf0; 
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(76, 175, 80, 0.1); 
        border: 1px solid #d4edda; 
    }
    
    .pin-form input {
        width: 100%;
        padding: 14px;
        margin: 15px 0;
        border: 2px solid #c3e6cb;
        border-radius: 8px;
        text-align: center;
        font-size: 18px;
        color: #2c3e50;
        transition: all 0.3s ease;
    }
    
    .pin-form input:focus {
        border-color: #4CAF50; 
        box-shadow: 0 0 8px rgba(76, 175, 80, 0.2);
        outline: none;
    }
    
    .pin-form button {
        background: #28a745; 
        color: white;
        padding: 14px 24px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        width: 100%;
        font-size: 16px;
        font-weight: 600;
        transition: background 0.3s ease;
    }
    
    .pin-form button:hover {
        background: #218838; 
    }
    
    .error-message {
        color: #c0392b; 
        text-align: center;
        margin: 15px 0;
        padding: 10px;
        background: #f8d7da;
        border-radius: 5px;
        border: 1px solid #f5c6cb;
    }
    
   
    .pin-container a {
        color: #2ecc71 !important;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .pin-container a:hover {
        color: #27ae60 !important;
    }
</style>
</head>
<body>
    <div class="pin-container">
        <h2 style="text-align: center; margin-bottom: 20px;">Admin Portal Verification</h2>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form class="pin-form" method="POST">
            <input type="password" name="pin" 
                   placeholder="Enter Security PIN" 
                   required
                   pattern="\d{4}" 
                   title="4-digit PIN required">
            
            <button type="submit">Verify Identity</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="index.php" style="color: #3498db; text-decoration: none;">
                ← Return to Home Page
            </a>
        </div>
    </div>
</body>
</html>