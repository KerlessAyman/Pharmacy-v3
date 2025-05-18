<html>

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>TABIBLINE-Home</title>
</head>

<body>

    <div class="home">
        <div class="header">
            <a href="index.html"><i class="fa-solid fa-house header-house-icon"></i></a>
            <div class="header-search-bar">
                <input class="search-bar" type="text" placeholder="Search...">

                <i class="search-icon">🔍</i> <!-- This will display a search icon -->
            </div> <!-- /header-search-bar -->
            <ul class="header-list">
                <li><a class="list-item" href="index.php">Home</a></li>
                <li><a class="list-item" href="about.php">About</a></li>
                <li><a class="list-item" href="blog.html">OUR BLOG</a></li>
                <li><a class="list-item" href="products.php">Product</a></li>
                <li class="join"><a class="list-item" href="#">Join us</a>
                    <ul class="dropdown-list">
                        <li class="dropdown-item list-item"><a href="login.php">Sign in</a></li>
                        <li class="dropdown-item list-item"><a href="sign up.php">Signup</a></li>
                    </ul> <!-- /dropdown-list -->
                </li>
                <li><a class="list-item" href="contact.php">Contact</a></li>
            </ul> <!-- /header-list -->
        </div> <!-- /header -->
        <div class="home-overlay">
            <div class="home-content">
                <h2 class="home-title"><span class="brand-name">TUBIA</span> Your Trusted Online Pharmacy</h2>
                <p class="home-p">“We specialize in providing trusted pharmaceutical care, combining convenience, health
                    expertise, and innovation
                    . We deliver not just medicine—but confidence, comfort, and care to your doorstep.</p>
                <a href="login.html"><button class="home-btn">Get Started</button></a>
                <a href="about.html"><button class="home-btn home-btn-2">ABOUT US </button></a>
            </div> <!-- /home-content -->
        </div> <!-- /home-overlay -->
    </div> <!-- /home -->

    <section id="partners" class="partners-section">
        <div class="container">
            <h2 class="section-title">Our Partners</h2>
            <p class="section-description">
                We’re proud to collaborate with trusted healthcare and pharmaceutical companies to bring you top-quality
                services and products.
            </p>

            <div class="partners-grid">
                <!-- Partner 1 -->
                <div class="partner-card">
                    <img src="Photo/anu.png" alt="ANU.img"
                        class="partner-logo">
                    <h3>Alex National University</h3>
                    <p>One of our key partners in education and innovation, working with us to support the next
                        generation of healthcare professionals</p>
                </div>

                <!-- Partner 2 -->
                <div class="partner-card">
                    <img src="Photo/Medicare-Logo.png" alt="MediCare Plus" class="partner-logo">
                    <h3>MediCare Plus</h3>
                    <p>A leading pharmaceutical provider ensuring quality and safety across all products.</p>
                </div>

                <!-- Partner 3 -->
                <div class="partner-card">
                    <img src="Photo/spa-logo-template-health-wellness-business-branding-design-vector-wellness-text_53876-136277.avif" alt="Wellness Group" class="partner-logo">
                    <h3>Wellness Group</h3>
                    <p>Promoting wellness through organic supplements and patient-focused solutions.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- FAQ Section -->
    <section class="faq-section" id="faq">
        <h2 class="faq-title">Frequently Asked Questions</h2>

        <div class="faq">
            <div class="question">
                <h3>1. What services does your pharmacy offer?</h3>
                <p>We offer prescription refills, over-the-counter medicines, health consultations, and online delivery
                    services.</p>
            </div>

            <div class="question">
                <h3>2. Do you provide home delivery?</h3>
                <p>Yes! We offer fast and reliable home delivery for all orders placed online or through our mobile app.
                </p>
            </div>

            <div class="question">
                <h3>3. Can I consult a pharmacist online?</h3>
                <p>Absolutely. You can chat with a licensed pharmacist through our website during business hours.</p>
            </div>

            <div class="question">
                <h3>4. What are your working hours?</h3>
                <p>We are open every day from 8:00 AM to 10:00 PM, including weekends and holidays.</p>
            </div>
        </div>
    </section>

    <div class="fixed-img-container">
        <div class="fixed-img-parent">
            <div class="fixed-img">
                <div class="fixed-img-overlay">
                    <div class="fixed-content">
                        <div class="fixed-item fixed-item-margin">
                            <i class="fa-solid fa-users fixed-i"></i>
                            <p class="fixed-p-1">15K+</p>
                            <p class="fixed-p-2">Happy Customers</p>
                        </div> <!-- /fixed-item -->
                        <div class="fixed-item fixed-item-margin">
                            <i class="fa-solid fa-trophy fixed-i"></i>
                            <p class="fixed-p-1">12</p>
                            <p class="fixed-p-2">Trusted Partners</p>
                        </div> <!-- /fixed-item -->
                        <div class="fixed-item fixed-item-margin">
                            <i class="fa-solid fa-mug-hot fixed-i"></i>
                            <p class="fixed-p-1">30+</p>
                            <p class="fixed-p-2">Pharmacy Experts</p>
                        </div> <!-- /fixed-item -->
                        <div class="fixed-item">
                            <i class="fa-solid fa-file-lines fixed-i"></i>
                            <p class="fixed-p-1">120+</p>
                            <p class="fixed-p-2">Health Products</p>
                        </div> <!-- /fixed-item -->
                    </div> <!-- /fixed-content -->
                </div> <!-- /fixed-img-overlay -->
            </div> <!-- /fixed-img -->
        </div> <!-- /fixed-img-parent -->
    </div> <!-- /fixed-img-container -->

    <div class="footer-container">
        <div class="footer-item">
            <img class="footer-img" src="Photo/mini-logo.PNG" alt="img">
        </div> <!-- /footer-img -->
        <div class="footer-icons">
            <i class="fa-brands fa-facebook-f footer-i"></i>
            <i class="fa-brands fa-twitter footer-i"></i>
            <i class="fa-brands fa-youtube footer-i"></i>
            <i class="fa-brands fa-linkedin-in footer-i"></i>
            <i class="fa-brands fa-instagram footer-i"></i>
            <i class="fa-brands fa-google-plus-g footer-i"></i>
        </div> <!-- /footer-icons -->
        <div class="footer-details">
            <i class="fa-regular fa-copyright footer-p-i"></i>
            <p class="footer-p">2025 . all right reserved by TABIBLINE</p>
        </div> <!-- /footer-details -->
    </div> <!-- /footer-container -->
</body>

</html>