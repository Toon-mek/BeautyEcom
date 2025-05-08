<?php
require_once __DIR__ . '/../_base.php';
handleCartActions($pdo);

// Initialize cart_items to avoid undefined variable error
$cart_items = [];

// If we're processing a new order (POST data available)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
    $selected_ids = array_map('intval', $_POST['selected_items']);
    if (!empty($selected_ids)) {
        $placeholders = rtrim(str_repeat('?,', count($selected_ids)), ',');
        $stmt = $pdo->prepare("
            SELECT ci.*, p.ProductName, p.Price, p.ProdIMG1 
            FROM cartitem ci 
            JOIN product p ON ci.ProductID = p.ProductID 
            JOIN cart c ON ci.CartID = c.CartID 
            WHERE ci.CartItemID IN ($placeholders) 
            AND c.MemberID = ? AND c.CartStatus = 'Active'
        ");
        $stmt->execute([...$selected_ids, $_SESSION['member_id']]);
        $cart_items = $stmt->fetchAll();
    }
}

// If viewing an existing order, use order_items instead
if (empty($cart_items) && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    $order_items = getOrderItems($pdo, $order_id);
    if (is_array($order_items) && !empty($order_items)) {
        $cart_items = $order_items;
    }
}

// Process voucher discount if available
$voucherId = $_POST['voucher_id'] ?? null;
$voucherDiscount = isset($_POST['voucher_discount']) ? floatval($_POST['voucher_discount']) : 0;
$voucherCode = $_POST['voucher_code'] ?? null;

// Calculate subtotal from cart items
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
}

// Apply voucher discount if available
$discountAmount = 0;
if ($voucherDiscount > 0) {
    $discountAmount = $subtotal * ($voucherDiscount / 100);
    // Store the original subtotal for display purposes
    $originalSubtotal = $subtotal;
    // Apply discount to subtotal
    $subtotal = $subtotal - $discountAmount;
}

// Add shipping fee
$shippingFee = isset($_POST['shipping_fee']) ? floatval($_POST['shipping_fee']) : 0;
$totalAmount = $subtotal + $shippingFee;

// Handle order cancellation
if (isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    $order_id_to_cancel = $_POST['order_id'];
    
    // Update order status to Cancelled
    $stmt = $pdo->prepare("UPDATE orders SET OrderStatus = 'Cancelled' WHERE OrderID = ?");
    $result = $stmt->execute([$order_id_to_cancel]);
    
    if ($result) {
        // Update payment status to Cancelled
        $stmt = $pdo->prepare("UPDATE payment SET PaymentStatus = 'Cancelled' WHERE OrderID = ?");
        $stmt->execute([$order_id_to_cancel]);
        
        // Set success message
        $_SESSION['success_message'] = "Order #$order_id_to_cancel has been cancelled successfully.";
    } else {
        // Set error message
        $_SESSION['error_message'] = "Failed to cancel order. Please try again.";
    }
    
    // Redirect to avoid form resubmission
    header("Location: order_confirmation.php?order_id=$order_id_to_cancel");
    exit();
}

$payment_method = $_POST['payment_method'] ?? $_POST['selected_payment_method'] ?? 'Cash on Delivery';
$_SESSION['last_payment_method'] = $payment_method;

// We need to access BOTH potential form fields
$payment_method = $_POST['payment_method'] ?? $_POST['selected_payment_method'] ?? 'Cash on Delivery';
$_SESSION['last_payment_method'] = $payment_method; // Store in session for later use
$order_id = $_GET['order_id'] ?? null;

// Redirect if the order is invalid
$order = redirectIfInvalidOrder($pdo, $order_id);
// Retrieve the order details from the database
$stmt = $pdo->prepare("SELECT o.*, m.Name, m.Email, m.PhoneNumber 
                      FROM orders o
                      LEFT JOIN member m ON o.MemberID = m.MemberID
                      WHERE o.OrderID = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
// Fetch order items for this order
$order_items = getOrderItems($pdo, $order_id);
if (!is_array($order_items)) {
    $order_items = [];
}
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

// Fetch member info
$member = getMemberDetails($order['MemberID']);

// Only process checkout on POST, then redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout']) && isset($_POST['selected_items'])) {
    error_log('Selected items: ' . print_r($_POST['selected_items'], true));
    processCheckout($pdo, $_POST['selected_items']);
    exit; // Prevent further code execution after redirect
}
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
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

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
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($member['Name'] ?? ''); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($member['Email'] ?? ''); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['PhoneNumber'] ?? ''); ?></p>
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
                            $subtotal = 0;
                            foreach($order_items as $item): 
                                $item_total = ($item['OrderItemPrice'] ?? $item['Price']) * ($item['OrderItemQTY'] ?? $item['Quantity']);
                                $subtotal += $item_total;
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
                                    <span class="order-item-price">RM <?php echo number_format($item_total, 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span>RM <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Shipping Fee</span>
                            <span>RM <?php echo number_format($order['ShippingFee'] ?? 0, 2); ?></span>
                        </div>
                        <?php if ($voucherDiscount > 0): ?>
                            <div class="d-flex justify-content-between">
                                <span>Voucher Discount (<?php echo $voucherDiscount; ?>%)</span>
                                <span>-RM <?php echo number_format($discountAmount ?? 0, 2); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>RM <?php echo number_format($order['OrderTotalAmount'], 2); ?></strong>
                        </div>
                    </div>
                </div>
                <!-- Payment Information Card -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Information</h5>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['PaymentMethod']); ?></p>
                        <p><strong>Payment Status:</strong> <span class="badge bg-success"><?php echo $payment['PaymentStatus']; ?></span></p>
                        <p><strong>Amount Paid:</strong> RM <?php echo number_format($order['OrderTotalAmount'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="continue-shopping-container">
            <?php if ($order['OrderStatus'] !== 'Cancelled'): ?>
                <button id="cancelOrderBtn" class="cancel-order-btn">Cancel Order</button>
            <?php endif; ?>
            <a href="../product/product.php" class="btn btn-dark">Continue Shopping</a>
        </div>
    </div>

    <!-- Cancel Order Confirmation Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <h2>Cancel Order</h2>
            <p>Are you sure you want to cancel order #<?php echo $order['OrderID']; ?>?</p>
            <p>This action cannot be undone.</p>
            
            <div class="modal-buttons">
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                    <button type="button" class="close-modal">No, Keep Order</button>
                    <button type="submit" name="cancel_order" class="confirm-cancel">Yes, Cancel Order</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("cancelModal");
        
        // Get the button that opens the modal
        var btn = document.getElementById("cancelOrderBtn");
        
        // Get the close button
        var closeBtn = document.getElementsByClassName("close-modal")[0];
        
        // When the user clicks the button, open the modal 
        if (btn) {
            btn.onclick = function() {
                modal.style.display = "block";
            }
        }
        
        // When the user clicks on close button, close the modal
        if (closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = "none";
            }
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

<?php require_once __DIR__ . '/../_foot.php'; ?>
<script>
    // Debug information in browser console
    console.log("Payment Method POST: <?= addslashes($_POST['payment_method'] ?? 'Not set') ?>");
    console.log("Selected Payment Method: <?= addslashes($_POST['selected_payment_method'] ?? 'Not set') ?>");
    console.log("Final payment method selected: <?= addslashes($payment_method) ?>");
</script>
