<?php
// search.php
$pageTitle = "Search Results - Pharmacity";
include 'header.php';

require 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($searchQuery)) {
    die("Please enter a search term");
}

// Search products by name
$stmt = $conn->prepare("SELECT * FROM Products WHERE name LIKE ? ORDER BY name");
$searchParam = "%$searchQuery%";
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="search-results-container">
    <h1>Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h1>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="product-grid">
            <?php while($product = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                        <a href="product_details.php?id=<?= $product['product_id'] ?>" class="view-product">View Product</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <p>No products found matching your search.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.search-results-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-info {
    padding: 1rem;
}

.view-product {
    display: inline-block;
    margin-top: 0.5rem;
    color: #007bff;
    text-decoration: none;
}

.no-results {
    text-align: center;
    padding: 2rem;
    font-size: 1.2rem;
}
</style>

<?php
$stmt->close();
$conn->close();
include 'footer.php';
?>