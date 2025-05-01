<?php
require_once __DIR__ . '/session.php';
startSessionIfNotStarted();

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_head.php';

$categories = getCategories();
$featuredProducts = getProducts();
?>

<div class="hero-section">
    <h1>Welcome to Beauty & Wellness</h1>
    <p>Discover our premium collection of beauty and wellness products</p>
    <a href="product/all_product.php" class="btn">Shop Now</a>
</div>

<div class="categories-section">
    <h2>Shop by Category</h2>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <a href="product/all_product.php?category=<?php echo $category['CategoryID']; ?>" class="category-card">
                <h3><?php echo htmlspecialchars($category['CategoryName']); ?></h3>
                <p><?php echo htmlspecialchars($category['CategoryDescription']); ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="featured-products">
    <h2>Featured Products</h2>
    <div class="product-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <img src="<?php echo htmlspecialchars($product['ProdIMG1']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>" class="product-image">
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                    <p class="product-price">$<?php echo number_format($product['Price'], 2); ?></p>
                    <a href="product/product.php?id=<?php echo $product['ProductID']; ?>" class="btn">View Details</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_foot.php'; ?>
