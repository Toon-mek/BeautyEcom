<?php
require_once __DIR__ . '/../_base.php';

handleCartActions($pdo);

// We need to access BOTH potential form fields
$payment_method = $_POST['payment_method'] ?? $_POST['selected_payment_method'] ?? 'Cash on Delivery';
$_SESSION['last_payment_method'] = $payment_method; // Store in session for later use

$order_id = $_GET['order_id'] ?? null;

?>
<script>
    // Debug information in browser console
    console.log("Payment Method POST: <?= addslashes($_POST['payment_method'] ?? 'Not set') ?>");
    console.log("Selected Payment Method: <?= addslashes($_POST['selected_payment_method'] ?? 'Not set') ?>");
    console.log("Final payment method selected: <?= addslashes($payment_method) ?>");
</script>
<?php

// Redirect if the order is invalid
$order = redirectIfInvalidOrder($pdo, $order_id);

// Get order items
$order_items = getOrderItems($pdo, $order_id);

// Better debugging approach
echo "<!-- DEBUG ORDER ITEMS: Order ID: {$order_id} -->\n";
echo "<!-- Items count: " . count($order_items) . " -->\n";

// Debug each item separately
foreach ($order_items as $idx => $item) {
    echo "<!-- Item #{$idx}: " . 
         htmlspecialchars($item['ProductName']) . 
         " (ID: " . $item['ProductID'] . ") - " .
         "Qty: " . ($item['OrderItemQTY'] ?? $item['Quantity']) . 
         " -->\n";
}

// Get payment details - explicitly pass the payment method as a parameter
$payment = getPaymentDetails($pdo, $order_id, $payment_method);
?>
<?php require_once __DIR__ . '/../_head.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Beauty & Wellness</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="order-confirmation-container">
        <div class="order-confirmation-header">
            <i class="fas fa-check-circle confirmation-icon"></i>
            <h1>Thank You for Your Order!</h1>
            <p class="lead">Your order has been placed successfully.</p>
        </div>

        <div class="order-details-grid">
            <div class="order-details-column">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Details</h5>
                        <p><strong>Order Number:</strong> #<?php echo $order['OrderID']; ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['OrderDate'])); ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-success"><?php echo $order['OrderStatus']; ?></span></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Customer Information</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['Name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['Email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['PhoneNumber']); ?></p>
                    </div>
                </div>
            </div>

            <div class="order-details-column">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Items</h5>
                        <div class="order-items-container">
                            <?php 
                            // Debug information
                            echo "<!-- Total order items: " . count($order_items) . " -->";
                            
                            // Make sure we're iterating through all items
                            foreach($order_items as $item): 
                            ?>
                                <div class="order-item">
                                    <div class="d-flex align-items-center">
                                        <?php if(isset($item['ProdIMG1']) && !empty($item['ProdIMG1'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($item['ProdIMG1']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                                        <?php endif; ?>
                                        <div class="order-item-details">
                                            <span class="order-item-name"><?php echo htmlspecialchars($item['ProductName']); ?></span>
                                            <span class="order-item-quantity">Quantity: <?php echo $item['OrderItemQTY'] ?? $item['Quantity']; ?></span>
                                        </div>
                                    </div>
                                    <span class="order-item-price">RM <?php echo number_format(($item['OrderItemPrice'] ?? $item['Price']) * ($item['OrderItemQTY'] ?? $item['Quantity']), 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>RM <?php echo number_format($order['OrderTotalAmount'], 2); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Information</h5>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['PaymentMethod']); ?></p>
                        <p><strong>Payment Status:</strong> <span class="badge bg-success"><?php echo $payment['PaymentStatus']; ?></span></p>
                        <p><strong>Amount Paid:</strong> RM <?php echo number_format($payment['AmountPaid'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="continue-shopping-container">
            <a href="../product/product.php" class="btn btn-dark">Continue Shopping</a>
        </div>
    </div>
</body>
</html>
<?php require_once __DIR__ . '/../_foot.php'; ?>