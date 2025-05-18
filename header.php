<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    /* Basic reset and global styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Arial', sans-serif;
        line-height: 1.6;
        color: #333;
        background-color: #f8f9fa;
    }
    
    /* Green color scheme */
    :root {
        --primary-green: #28a745;  /* Bootstrap success green */
        --dark-green: #218838;
        --light-green: #d4edda;
        --header-green: #2e7d32;  /* Material green 800 */
    }
    
    /* Header styles */
    .main-header {
        background-color: var(--header-green);
        color: white;
        padding: 1rem 0;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .header-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .logo img {
        height: 40px;
        transition: transform 0.3s;
    }
    
    .logo:hover img {
        transform: scale(1.05);
    }
    
    .search-bar {
        display: flex;
        flex-grow: 1;
        max-width: 500px;
        margin: 0 2rem;
        transition: all 0.3s;
    }
    
    .search-bar:focus-within {
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
    }
    
    .search-bar input {
        width: 100%;
        padding: 0.6rem 1rem;
        border: none;
        border-radius: 4px 0 0 4px;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .search-bar input:focus {
        outline: none;
        box-shadow: inset 0 0 0 2px var(--primary-green);
    }
    
    .search-bar button {
        padding: 0 1.2rem;
        background-color: var(--primary-green);
        color: white;
        border: none;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
    }
    
    .search-bar button:hover {
        background-color: var(--dark-green);
    }
    
    .cart-icon-container {
        position: relative;
        transition: transform 0.3s;
    }
    
    .cart-icon-container:hover {
        transform: scale(1.1);
    }
    
    .cart-icon {
        color: white;
        font-size: 1.5rem;
        text-decoration: none;
        display: flex;
        padding: 0.5rem;
    }
    
    .cart-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    /* Navigation */
    .main-nav {
        background-color: var(--header-green);
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .main-nav ul {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0;
        list-style: none;
        display: flex;
        justify-content: center;
    }
    
    .main-nav li {
        position: relative;
    }
    
    .main-nav li::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 3px;
        background-color: white;
        transition: all 0.3s;
    }
    
    .main-nav li:hover::after {
        width: 100%;
        left: 0;
    }
    
    .main-nav a {
        color: white;
        text-decoration: none;
        padding: 1rem 1.5rem;
        display: block;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .main-nav a:hover {
        background-color: rgba(255,255,255,0.1);
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .header-container {
            flex-wrap: wrap;
            padding: 0.5rem;
        }
        
        .logo {
            order: 1;
        }
        
        .cart-icon-container {
            order: 2;
        }
        
        .search-bar {
            order: 3;
            width: 100%;
            margin: 0.5rem 0;
            max-width: none;
        }
        
        .main-nav ul {
            flex-wrap: wrap;
        }
        
        .main-nav li {
            flex: 1 0 auto;
            text-align: center;
        }
    }
    
    @media (max-width: 480px) {
        .main-nav a {
            padding: 0.8rem;
            font-size: 0.9rem;
        }
    }
</style>
<header class="main-header">
    <div class="header-container">
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Pharmacity Logo">
        </a>
        
        <form class="search-bar" action="search.php" method="GET">
            <input type="text" name="query" placeholder="Search products..." required>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        
        <div class="cart-icon-container">
            <a href="cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cartCount">0</span>
            </a>
        </div>
    </div>
    
    <nav class="main-nav">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="skin_care.php">Skin Care</a></li>
            <li><a href="hair_care.php">Hair Care</a></li>
            <li><a href="medicines.php">Medicines</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>
</header>
