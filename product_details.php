<?php
// product_details.php
if (!isset($_GET['id'])) {
    header("Location: cart.php");
    exit();
}

$pageTitle = "Product Details - Pharmacity";
include 'header.php';

require 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$productId = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM Products WHERE product_id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found");
}
?>

<div class="product-detail-container">
    <div class="product-images">
        <img src="<?= htmlspecialchars($product['image_url']) ?>" 
             alt="<?= htmlspecialchars($product['name']) ?>">
    </div>
    
    <div class="product-info">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p class="price">$<?= number_format($product['price'], 2) ?></p>
        <p class="description"><?= htmlspecialchars($product['description']) ?></p>
        
        <form class="add-to-cart-form">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <button type="submit" class="add-to-cart-btn">
                <i class="fas fa-cart-plus"></i> Add to Cart
            </button>
        </form>
    </div>
</div>

<style>
.product-detail-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.product-images img {
    width: 100%;
    max-height: 500px;
    object-fit: contain;
}

.add-to-cart-btn {
    background: #28a745;
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .product-detail-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$stmt->close();
$conn->close();
include 'footer.php';
?>