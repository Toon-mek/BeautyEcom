<?php
session_start();
require_once __DIR__ . '/../_base.php';

// Handle cart actions
handleCartActions($pdo);

// Get cart items
$cart_items = getCartItems($pdo);

// Calculate total
$total = calculateCartTotal($cart_items);
?>
<?php require_once __DIR__ . '/../_head.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <a href="/../auth/logout.php" class="btn btn-outline-dark">Logout</a>
                    <?php else: ?>
                        <a href="/../auth/login.php" class="btn btn-outline-dark me-2">Login</a>
                        <a href="/../auth/register.php" class="btn btn-dark">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="mb-4">Shopping Cart</h1>
        
        <?php if(count($cart_items) > 0): ?>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-8">
                        <?php foreach($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($item['ProdIMG1']); ?>" class="product-image" alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h5><?php echo htmlspecialchars($item['ProductName']); ?></h5>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control" name="quantity[<?php echo $item['CartItemID']; ?>]" value="<?php echo $item['Quantity']; ?>" min="1">
                                    </div>
                                    <div class="col-md-2">
                                        <p class="mb-0">$<?php echo number_format($item['Price'], 2); ?></p>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="remove_item" class="btn btn-outline-danger" value="<?php echo $item['CartItemID']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-3">
                            <button type="submit" name="update_cart" class="btn btn-outline-dark">Update Cart</button>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Order Summary</h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span>$<?php echo number_format($total, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping</span>
                                    <span>Free</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total</strong>
                                    <strong>$<?php echo number_format($total, 2); ?></strong>
                                </div>
                                <button type="submit" name="checkout" class="btn btn-dark w-100">Proceed to Checkout</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="products.php">Continue shopping</a>
            </div>
        <?php endif; ?>
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