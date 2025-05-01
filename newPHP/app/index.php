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
    <div class="hero-content">
        <h1>Discover Your Beauty Journey</h1>
        <p>Premium beauty and wellness products for your unique needs</p>
        <div class="hero-buttons">
            <a href="product/all_product.php" class="btn btn-primary">Shop Now</a>
            <a href="#featured" class="btn btn-outline">View Featured</a>
        </div>
    </div>
    <div class="hero-overlay"></div>
</div>

<div class="categories-section">
    <div class="section-header">
        <h2>Shop by Category</h2>
        <p>Find the perfect products for your needs</p>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <a href="product/all_product.php?category=<?php echo $category['CategoryID']; ?>" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-spa"></i>
                </div>
                <h3><?php echo htmlspecialchars($category['CategoryName']); ?></h3>
                <p><?php echo htmlspecialchars($category['CategoryDescription']); ?></p>
                <span class="category-link">Explore <i class="fas fa-arrow-right"></i></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="featured-products" id="featured">
    <div class="section-header">
        <h2>Featured Products</h2>
        <p>Our most popular and highly-rated items</p>
    </div>
    <div class="product-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="uploads/<?php echo htmlspecialchars($product['ProdIMG1']); ?>" 
                         alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                    <div class="product-actions">
                        <a href="product/product.php?id=<?php echo $product['ProductID']; ?>" 
                           class="view-details-btn">View Details</a>
                    </div>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                    <p class="product-category"><?php echo htmlspecialchars($product['CategoryName']); ?></p>
                    <p class="product-price">RM <?php echo number_format($product['Price'], 2); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="view-all-container">
        <a href="product/all_product.php" class="btn btn-outline">View All Products</a>
    </div>
</div>

<?php require_once __DIR__ . '/_foot.php'; ?>
