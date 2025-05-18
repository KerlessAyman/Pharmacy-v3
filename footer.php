<?php
// footer.php
?>
    </main> <!-- Closing main content tag -->
    
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="skin_care.php">Skin Care</a></li>
                        <li><a href="hair_care.php">Hair Care</a></li>
                        <li><a href="medicines.php">Medicines</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Information</h4>
                    <ul>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Contact Us</h4>
                    <address>
                        <p><i class="fas fa-map-marker-alt"></i> 123 Pharmacy St, Health City</p>
                        <p><i class="fas fa-phone"></i> (123) 456-7890</p>
                        <p><i class="fas fa-envelope"></i> info@pharmacity.com</p>
                    </address>
                </div>
                
                <div class="footer-column">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Pharmacity. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <style>
        /* Footer styles */
        .site-footer {
            background-color: #343a40;
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 3rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-column h4 {
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column li {
            margin-bottom: 0.5rem;
        }
        
        .footer-column a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-column a:hover {
            color: #fff;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-links a {
            color: white;
            font-size: 1.2rem;
        }
        
        .copyright {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #495057;
            color: #adb5bd;
            font-size: 0.9rem;
        }
        
        address p {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</body>
</html>