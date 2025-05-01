<?php
require_once __DIR__ . '/../_base.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

// Get cart items
$cart_items = getCartItems($pdo);

// Redirect if cart is empty
redirectIfCartIsEmpty($cart_items);

// Calculate total
$total = calculateCartTotal($cart_items);

// Set default shipping fee
$shipping_fee = 5.00;  // Default shipping fee

// Check if the user has selected a state and set shipping fee accordingly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['state'])) {
    $state = $_POST['state'];
    
    // Check if order total exceeds RM200 for free shipping
    if ($total > 200) {
        $shipping_fee = 0;  // Free shipping for orders over RM200
    } else {
        // Set shipping fee based on state
        if (in_array($state, ['Sabah', 'Sarawak', 'Labuan'])) {
            $shipping_fee = 10.00;  // RM10 shipping for Sabah, Sarawak, and Labuan
        } else {
            $shipping_fee = 5.00;  // RM5 shipping for other states
        }
    }
}

// Calculate total with shipping fee
$total_with_shipping = $total + $shipping_fee;

// Handle checkout process (using existing processCheckout function)
$error = processCheckout($pdo, $cart_items, $total_with_shipping);

// Handle receipt image upload (if applicable)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
    $receipt_image = $_FILES['receipt_image'];
    $upload_dir = '../uploads/receipts/';
    $upload_file = $upload_dir . basename($receipt_image['name']);
    
    // Validate image file (add other validation if needed)
    if (move_uploaded_file($receipt_image['tmp_name'], $upload_file)) {
        // Process the uploaded file (e.g., store the path in the database)
        $receipt_path = $upload_file;
    } else {
        $error = 'Failed to upload receipt image.';
    }
}

// After successful checkout, redirect to order confirmation page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Retrieve the order ID (assuming it's generated in the checkout process)
    $order_id = $pdo->lastInsertId(); // Assuming the last inserted ID is the order ID

    // Redirect to the order confirmation page with the order ID
    header("Location: /newPHP/app/order/confirmation_order.php?order_id=" . $order_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Beauty & Wellness</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../_head.php'; ?> <!-- Existing Header -->

    <div class="container py-5">
        <div class="row">
            <!-- Left Column: Checkout Form -->
            <div class="col-md-8">
                <h2 class="mb-4">Checkout</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Shipping Address -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Shipping Address</h5>
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="mb-3">
                                <label for="zip" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zip" name="zip" required>
                            </div>
                            <div class="mb-3">
                                <label for="state" class="form-label">State</label>
                                <select class="form-select" id="state" name="state" required>
                                    <option value="">Select State</option>
                                    <?php
                                    $states = [
                                        'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis',
                                        'Penang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'
                                    ];
                                    foreach ($states as $state_option) {
                                        echo "<option value=\"$state_option\">$state_option</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Payment Method</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="duitnow" value="DuitNow" checked>
                                <label class="form-check-label" for="duitnow">
                                    DuitNow
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="tng" value="Touch N Go">
                                <label class="form-check-label" for="tng">
                                    Touch 'n Go (TNG)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="Bank Transfer">
                                <label class="form-check-label" for="bank_transfer">
                                    Bank Transfer
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Transaction Receipt -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Upload Transaction Receipt</h5>
                            <div class="mb-3">
                                <label for="receipt_image" class="form-label">Receipt Image</label>
                                <input type="file" class="form-control" id="receipt_image" name="receipt_image" accept="image/jpeg, image/png">
                                <small class="form-text text-muted">Please upload an image of your transaction receipt (JPEG/PNG).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Place Order Button -->
                    <button type="submit" class="btn btn-dark btn-lg w-100">Place Order</button>
                </form>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo htmlspecialchars($item['ProductName']); ?> x <?php echo $item['Quantity']; ?></span>
                                <span>RM<?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <!-- Shipping Fee -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Fee</span>
                            <span>RM<?php echo number_format($shipping_fee, 2); ?></span>
                        </div>
                        <hr>
                        <!-- Total -->
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>RM<?php echo number_format($total_with_shipping, 2); ?></strong>
                        </div>
                    </div>
                </div>
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
