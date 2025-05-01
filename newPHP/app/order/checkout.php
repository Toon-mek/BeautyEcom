<?php
require_once __DIR__ . '/../_base.php';
// Redirect if not logged in
redirectIfNotLoggedIn();
// Get cart items
$cart_items = getCartItems(pdo: $pdo);
// Redirect if cart is empty
redirectIfCartIsEmpty($cart_items);
// Calculate total
$total = calculateCartTotal($cart_items);
// Handle checkout
$error = processCheckout($pdo, $cart_items, $total);
?>

<?php require_once __DIR__ . '/../_head.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Beauty & Wellness</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">Beauty & Wellness</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="cart.php" class="btn btn-outline-dark me-2">
                        <i class="fas fa-shopping-cart"></i> Cart
                    </a>
                    <?php if(isset($_SESSION['member_id'])): ?>
                        <a href="profile.php" class="btn btn-outline-dark me-2">Profile</a>
                        <a href="logout.php" class="btn btn-outline-dark">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-dark me-2">Login</a>
                        <a href="register.php" class="btn btn-dark">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Checkout</h2>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Payment Method</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="Credit Card" checked>
                                <label class="form-check-label" for="credit_card">
                                    Credit Card
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="PayPal">
                                <label class="form-check-label" for="paypal">
                                    PayPal
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <?php foreach($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($item['ProductName']); ?> x <?php echo $item['Quantity']; ?></span>
                                    <span>$<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100">Place Order</button>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Your trusted source for premium health and beauty products.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>Email: info@beautywellness.com<br>
                    Phone: (123) 456-7890</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php require_once __DIR__ . '/../_foot.php'; ?>