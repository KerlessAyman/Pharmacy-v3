<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pharmacity Products</title>
  <style>
     body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f0f8ff;
    }
    header {
      color: white;
      text-align: center;
      padding: auto;
    }
    .main-container {
      display: flex;
      justify-content: space-around;
      flex-wrap: wrap;
      padding: 30px;
    }
    .section {
      width: 30%;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
      margin-bottom: 30px;
      transition: transform 0.3s ease;
    }
     
    .section:hover {
      transform: scale(1.05);
    }
    .section img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 10px;
    }
    .section h3 {
      margin-top: 10px;
      color: #333;
    }
    .product-list {
      margin-top: 15px;
    }
    .product-list div {
      margin-bottom: 10px;
    }
    
   
  </style>
  <link rel="stylesheet" href="style.css">
</head>

<body>
   <div class="home">
        <div class="header">
            <a href="index.html"><i class="fa-solid fa-house header-house-icon"></i></a>
          
           
            <ul class="header-list">
                <li><a class="list-item" href="index.html">Home</a></li>
                <li><a class="list-item" href="about.html">About</a></li>
                <li><a class="list-item" href="blog.html">OUR BLOG</a></li>
                <li><a class="list-item" href="contact.html">Contact</a></li>
            </ul>
        </div>
        <br><br><br><br>
       
       
  <header>
    <h1>Pharmacity</h1>
    <p>The best health medicines and the best services</p>
  </header>
       <br><br><br>
  <div class="main-container">
    <!-- Skin Care Section -->
    <div class="section" style="background-color: #e8f5e9;" onclick="window.location.href='skin_care.php'">
      <img src="Photo/skin.jpg" alt="Skin Care">
      <h3>Skin Care Products</h3>
      <p>The best skin care creams and products to protect and nourish your skin.</p>
      <a href="skin_care.php">Find out more</a>
    </div>

    <!-- Hair Care Section -->
    <div class="section" style="background-color: #e3f2fd;" onclick="window.location.href='hair_care.php'">
      <img src="Photo/hair.jpg" alt="Hair Care">
      <h3>Hair Care Products</h3>
      <p>The perfect hair care, nourishment and protection products.</p>
      <a href="hair_care.php">Find out more</a>
    </div>

    <!-- Medicines Section -->
    <div class="section" style="background-color: #fff3e0;" onclick="window.location.href='medicines.php'">
      <img src="Photo/med.jpg" alt="Medicines">
      <h3>Medicines</h3>
      <p>Therapeutic medications for all health conditions and essential medications.</p>
      <a href="medicines.php">Find out more</a>
    </div>
  </div>
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

  <script>
 
  </script>
</body>
</html>