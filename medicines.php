
<?php
$pageTitle = "Skin Care Products - Pharmacity";
include 'header.php';

// Database connection
require 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch skin care products from database
$sql = "SELECT * FROM Products WHERE category = 'Medicines' AND stock_quantity > 0";
$result = $conn->query($sql);
?>

<div class="category-container">
    <div class="category-header">
        <h1>Skin Care Products</h1>
        <p>Premium products to protect and nourish your skin</p>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="product-grid">
            <?php while($product = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-desc"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <div class="price-section">
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <?php if ($product['is_prescription_required']): ?>
                                <span class="prescription-badge">Prescription Required</span>
                            <?php endif; ?>
                        </div>
                        
                        <button class="add-to-cart" 
                                onclick="addToCart(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <p>Currently no skin care products available. Please check back later.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Skin Care Specific Styles */
    .category-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
        min-height: 60vh;
    }

    .category-header {
        text-align: center;
        margin-bottom: 2rem;
        padding: 0 1rem;
    }

    .category-header h1 {
        color: #007bff;
        font-size: 2.2rem;
        margin-bottom: 0.5rem;
    }

    .category-header p {
        color: #666;
        font-size: 1.1rem;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
        padding: 0 1rem;
    }

    .product-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }

    .product-card img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover img {
        transform: scale(1.03);
    }

    .product-info {
        padding: 1.2rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .product-info h3 {
        color: #333;
        margin: 0 0 0.5rem;
        font-size: 1.2rem;
    }

    .product-desc {
        color: #666;
        font-size: 0.9rem;
        margin: 0.3rem 0 1rem;
        flex-grow: 1;
    }

    .price-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0.5rem 0 1rem;
    }

    .product-price {
        color: #007bff;
        font-weight: bold;
        font-size: 1.1rem;
        margin: 0;
    }

    .prescription-badge {
        background-color: #ffc107;
        color: #333;
        padding: 0.2rem 0.5rem;
        border-radius: 3px;
        font-size: 0.7rem;
        font-weight: bold;
    }

    .add-to-cart {
        width: 100%;
        padding: 0.7rem;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.95rem;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: auto;
    }

    .add-to-cart:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }

    .add-to-cart:active {
        transform: translateY(0);
    }

    .no-products {
        text-align: center;
        padding: 2rem;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 2rem auto;
        max-width: 600px;
    }

    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        }
    }

    @media (max-width: 480px) {
        .product-grid {
            grid-template-columns: 1fr;
            padding: 0;
        }
        
        .category-header h1 {
            font-size: 1.8rem;
        }
        
        .price-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
</style>

<script>
function addToCart(productId, productName, productPrice) {
    // Check if user is logged in
    <?php if(isset($_SESSION['user_id'])): ?>
        // AJAX call to add to cart
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                updateCartCount();
                showNotification(`${productName} added to cart!`);
            } else {
                alert(data.message || 'Error adding to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding to cart');
        });
    <?php else: ?>
        // For guests, use localStorage
        let cart = JSON.parse(localStorage.getItem("cart")) || [];
        
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                name: productName,
                price: productPrice,
                quantity: 1
            });
        }
        
        localStorage.setItem("cart", JSON.stringify(cart));
        updateCartCount();
        showNotification(`${productName} added to cart!`);
    <?php endif; ?>
}

function updateCartCount() {
    <?php if(isset($_SESSION['user_id'])): ?>
        // AJAX call to get cart count
        fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.getElementById("cartCount").textContent = data.count;
            }
        });
    <?php else: ?>
        // For guests, use localStorage
        let cart = JSON.parse(localStorage.getItem("cart")) || [];
        let totalItems = cart.reduce((total, item) => total + item.quantity, 0);
        document.getElementById("cartCount").textContent = totalItems;
    <?php endif; ?>
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart count
    updateCartCount();
    
    // Add event listeners to all add-to-cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            addToCart(productId, this);
        });
    });
});

async function addToCart(productId, button) {
    button.disabled = true;
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

    try {
        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=1`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Added to cart!');
            updateCartCount();
            button.innerHTML = '<i class="fas fa-check"></i> Added';
            
            // Update cart count in UI
            document.getElementById('cartCount').textContent = data.cart_count;
        } else {
            throw new Error(data.message || 'Failed to add to cart');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}

function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('cartCount').textContent = data.count;
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
        ${message}
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
<script>
// Ajax search functionality
document.querySelector('.search-bar').addEventListener('submit', function(e) {
    e.preventDefault();
    const query = this.querySelector('input').value.trim();
    
    if (query.length > 0) {
        fetch(`search.php?query=${encodeURIComponent(query)}`)
            .then(response => response.text())
            .then(html => {
                document.querySelector('.main-content').innerHTML = 
                    html.split('<main class="main-content">')[1].split('</main>')[0];
            })
            .catch(error => {
                window.location.href = `search.php?query=${encodeURIComponent(query)}`;
            });
    }
});
</script>

<style>
.cart-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #28a745;
    color: white;
    padding: 15px 25px;
    border-radius: 5px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

.cart-notification.fade-out {
    animation: fadeOut 0.3s ease-in;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}
</style>

<?php 
$conn->close();
include 'footer.php'; 
?>