<?php
// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data safely
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = htmlspecialchars(trim($_POST["email"]));
    $message = htmlspecialchars(trim($_POST["message"]));

    // Database connection (edit credentials as needed)
    $conn = new mysqli("localhost", "root", "", "tubia_pharmacy");

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Insert the message into the database (assuming a table `contact_messages`)
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);
    
    if ($stmt->execute()) {
        $success = "Message sent successfully!";
    } else {
        $error = "Failed to send message. Please try again.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us - Pharmacy</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="home">
    <div class="header">
        <a href="index.html"><i class="fa-solid fa-house header-house-icon"></i></a>
        <div class="header-search-bar">
            <input class="search-bar" type="text" placeholder="Search...">
            <i class="search-icon">🔍</i>
        </div>
        <ul class="header-list">
            <li><a class="list-item" href="index.html">Home</a></li>
        </ul>
    </div>

    <section class="contact-section">
        <h2><span class="brand-name">Contact</span> Us</h2>
        <p>If you have any questions, feel free to get in touch with us using the form below or reach us directly via email or phone.</p>

        <?php if (!empty($success)): ?>
            <p style="color: green; font-weight: bold;"><?php echo $success; ?></p>
        <?php elseif (!empty($error)): ?>
            <p style="color: red; font-weight: bold;"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="contact-container">
            <form action="contact.php" method="POST" class="contact-form">
                <input type="text" name="name" placeholder="Your Name" required>
                <input type="email" name="email" placeholder="Your Email" required>
                <textarea class="textarea" name="message" rows="5" placeholder="Your Message" required></textarea>
                <button type="submit">Send Message</button>
            </form>

            <div class="contact-info">
                <h3>Our Information</h3>
                <p><strong>Email:</strong> support@pharmacy.com</p>
                <p><strong>Phone:</strong> +20 1206839712</p>
                <p><strong>Address:</strong> 56 ELPharanna street, Alex, Egypt</p>
            </div>
        </div>
    </section>

    <div class="footer-container">
        <div class="footer-item">
            <img class="footer-img" src="Photo/mini-logo.PNG" alt="img">
        </div>
        <div class="footer-icons">
            <i class="fa-brands fa-facebook-f footer-i"></i>
            <i class="fa-brands fa-twitter footer-i"></i>
            <i class="fa-brands fa-youtube footer-i"></i>
            <i class="fa-brands fa-linkedin-in footer-i"></i>
            <i class="fa-brands fa-instagram footer-i"></i>
            <i class="fa-brands fa-google-plus-g footer-i"></i>
        </div>
        <div class="footer-details">
            <i class="fa-regular fa-copyright footer-p-i"></i>
            <p class="footer-p">2025 . all right reserved by TABIBLINE</p>
        </div>
    </div>
</div>
</body>
</html>
