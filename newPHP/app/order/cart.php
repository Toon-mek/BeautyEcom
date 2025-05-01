<?php
session_start();
require_once __DIR__ . '/../_base.php';

// Ensure user is logged in before proceeding with cart actions
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle cart actions
handleCartActions($pdo);

// Get cart items
$cart_items = getCartItems($pdo);

// Calculate total
$total = calculateCartTotal($cart_items);
?>
  <!-- Include header here -->

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
    <!-- Header Section (Included once) -->
    <header class="main-header">
        <div class="header-container">
            <a href="/newPHP/app/index.php" class="header-logo">Beauty & Wellness</a>
            <nav class="header-nav">
                <ul class="nav-list">
                    <li><a href="/newPHP/app/index.php" class="nav-link">Home</a></li>
                    <li><a href="/newPHP/app/product/product.php" class="nav-link">Products</a></li>
                    <li><a href="/newPHP/app/order/cart.php" class="nav-link">Cart</a></li>

                    <?php if (isLoggedIn()): ?>
                        <li class="profile-dropdown">
                            <?php
                            $profilePhoto = getMemberProfilePhoto($_SESSION['member_id']);
                            $photoPath = "/newPHP/app/uploads/" . htmlspecialchars($profilePhoto);
                            $defaultPhoto = "/newPHP/app/uploads/default-profile.png";
                            
                            // Check if file exists, if not use default
                            if (!file_exists(__DIR__ . '/uploads/' . $profilePhoto)) {
                                $photoPath = $defaultPhoto;
                            }
                            ?>
                            <img src="<?php echo $photoPath; ?>" 
                                 alt="Profile" 
                                 class="profile-photo"
                                 onerror="this.src='<?php echo $defaultPhoto; ?>'">
                            <div class="dropdown-content">
                                <a href="/newPHP/app/member/settings.php">Settings</a>
                                <a href="/newPHP/app/auth/logout.php">Logout</a>
                            </div>
                        </li>

                        <!-- Display Logout Link -->
                        <li><a href="/newPHP/app/index.php" class="nav-link">Logout</a></li>
                    <?php else: ?>
                        <!-- Display Login Link if the user is not logged in -->
                        <li><a href="/newPHP/app/auth/login.php" class="nav-link">Log In</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Cart Content Section -->
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
                Your cart is empty. <a href="/newPHP/app/product/product.php">Continue shopping</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
<?php require_once __DIR__ . '/../_foot.php'; ?>
