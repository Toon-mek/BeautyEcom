<?php
require_once __DIR__ . '/../_base.php';

// Ensure the order ID is passed in the URL
if (!isset($_GET['order_id'])) {
    die("Order ID not provided.");
}

// Get the order ID from the URL
$order_id = $_GET['order_id'];

// Retrieve the order details from the database
$stmt = $pdo->prepare("SELECT * FROM orders WHERE OrderID = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// If the order doesn't exist, show an error
if (!$order) {
    die("Order not found.");
}

// Retrieve the order items
$stmt_items = $pdo->prepare("SELECT oi.*, p.ProductName, p.ProdIMG1 
                             FROM order_items oi
                             JOIN products p ON oi.ProductID = p.ProductID
                             WHERE oi.OrderID = ?");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// Retrieve payment details
$stmt_payment = $pdo->prepare("SELECT * FROM payments WHERE OrderID = ?");
$stmt_payment->execute([$order_id]);
$payment = $stmt_payment->fetch();
?>

<?php require_once __DIR__ . '/../_head.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Beauty & Wellness</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navbar -->
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
        <!-- Confirmation Message -->
        <div class="text-center mb-5">
            <i class="fas fa-check-circle confirmation-icon"></i>
            <h1 class="mt-3">Thank You for Your Order!</h1>
            <p class="lead">Your order has been placed successfully.</p>
        </div>

        <div class="row">
            <!-- Left Column: Order Details -->
            <div class="col-md-6">
                <!-- Order Details Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Details</h5>
                        <p><strong>Order Number:</strong> #<?php echo $order['OrderID']; ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['OrderDate'])); ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-success"><?php echo $order['OrderStatus']; ?></span></p>
                    </div>
                </div>

                <!-- Customer Information Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Customer Information</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['Name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['Email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['PhoneNumber']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Items and Payment Details -->
            <div class="col-md-6">
                <!-- Order Items Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Items</h5>
                        <?php foreach($items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <img src="<?php echo !empty($item['ProdIMG1']) ? htmlspecialchars($item['ProdIMG1']) : 'path/to/default-image.jpg'; ?>" alt="<?php echo htmlspecialchars($item['ProductName']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                    <span class="ms-2"><?php echo htmlspecialchars($item['ProductName']); ?> x <?php echo $item['OrderItemQTY']; ?></span>
                                </div>
                                <span>$<?php echo number_format($item['OrderItemPrice'] * $item['OrderItemQTY'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>$<?php echo number_format($order['OrderTotalAmount'], 2); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Payment Information Card -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Information</h5>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['PaymentMethod']); ?></p>
                        <p><strong>Payment Status:</strong> <span class="badge bg-<?php echo $payment['PaymentStatus'] === 'Completed' ? 'success' : ($payment['PaymentStatus'] === 'Pending' ? 'warning' : 'danger'); ?>">
                            <?php echo $payment['PaymentStatus']; ?></span></p>
                        <p><strong>Amount Paid:</strong> $<?php echo number_format($payment['AmountPaid'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Continue Shopping Button -->
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-dark">Continue Shopping</a>
        </div>
    </div>

    <!-- Footer -->
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
