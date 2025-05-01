<?php
require_once __DIR__ . '/../_base.php';

// Validate ID
if (!isset($_GET['id'])) {
    header("Location: all_product.php");
    exit();
}

$product_id = $_GET['id'];
$product = getProduct($product_id);

if (!$product) {
    header("Location: all_product.php");
    exit();
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = (int)($_POST['quantity'] ?? 1);
    handleAddToCart($product_id, $quantity);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['ProductName']); ?> - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__ . '/../_head.php'; ?>
<div class="container py-5">
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6">
            <div class="product-gallery">
                <img src="<?php echo htmlspecialchars($product['ProdIMG1']); ?>" class="main-image" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                <div class="thumbnails">
                    <img src="<?php echo htmlspecialchars($product['ProdIMG1']); ?>" class="thumbnail active" alt="Thumbnail 1">
                    <?php if ($product['ProdIMG2']): ?>
                        <img src="<?php echo htmlspecialchars($product['ProdIMG2']); ?>" class="thumbnail" alt="Thumbnail 2">
                    <?php endif; ?>
                    <?php if ($product['ProdIMG3']): ?>
                        <img src="<?php echo htmlspecialchars($product['ProdIMG3']); ?>" class="thumbnail" alt="Thumbnail 3">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <h1><?php echo htmlspecialchars($product['ProductName']); ?></h1>
            <p class="text-muted">Category: <?php echo htmlspecialchars($product['CategoryName']); ?></p>
            <h3 class="mb-4">$<?php echo number_format($product['Price'], 2); ?></h3>

            <div class="mb-4">
                <h5>Description</h5>
                <p><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>
            </div>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['Quantity']; ?>">
                </div>
                <button type="submit" name="add_to_cart" class="btn btn-dark btn-lg w-100">Add to Cart</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../_foot.php'; ?>

<script>
// Image gallery functionality
document.querySelectorAll('.thumbnail').forEach(thumb => {
    thumb.addEventListener('click', function() {
        document.querySelector('.main-image').src = this.src;
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>
</body>
</html>
