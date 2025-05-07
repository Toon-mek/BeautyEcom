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

// Get cart items with stock information
$stmt = $pdo->prepare("
    SELECT ci.*, p.ProductName, p.Price, p.ProdIMG1, p.Quantity as StockQuantity 
    FROM cartitem ci 
    JOIN product p ON ci.ProductID = p.ProductID 
    JOIN cart c ON ci.CartID = c.CartID 
    WHERE c.MemberID = ? AND c.CartStatus = 'Active'
");
$stmt->execute([$_SESSION['member_id']]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = calculateCartTotal($cart_items);
?>

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
    <?php require_once __DIR__ . '/../_head.php'; ?>

    <div class="cart-container">
        <h1 class="mb-4">Shopping Cart</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <form method="POST" action="checkout.php" id="cartForm">
                <div class="cart-grid">
                    <div class="cart-items">
                        <div class="select-all-container">
                            <input type="checkbox" id="selectAll" class="select-all-checkbox">
                            <label for="selectAll" class="select-all-label">Select All Items</label>
                            <div style="flex-grow: 1;"></div>
                            <div class="selected-items-count">
                                <span id="headerSelectedCount">0</span> items selected
                            </div>
                        </div>

                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" data-price="<?php echo $item['Price'] * $item['Quantity']; ?>">
                                <div class="cart-item-content">
                                    <input type="checkbox"
                                        name="selected_items[]"
                                        value="<?php echo $item['CartItemID']; ?>"
                                        class="item-checkbox"
                                        data-price="<?php echo $item['Price'] * $item['Quantity']; ?>">


                                    <?php if (!empty($item['ProdIMG1']) && file_exists("../uploads/" . $item['ProdIMG1'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($item['ProdIMG1']); ?>"
                                            class="product-image"
                                            alt="<?php echo htmlspecialchars($item['ProductName']); ?>"
                                            onerror="this.onerror=null; this.src='../uploads/default-product.jpg';">
                                    <?php else: ?>
                                        <img src="../uploads/default-product.jpg"
                                            class="product-image"
                                            alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                                    <?php endif; ?>

                                    <div class="product-info">
                                        <h5 class="product-name"><?php echo htmlspecialchars($item['ProductName']); ?></h5>
                                        <p class="product-price">RM <?php echo number_format($item['Price'], 2); ?></p>
                                        <?php if ($item['StockQuantity'] < 5): ?>
                                            <p class="stock-warning">
                                                Only <?php echo $item['StockQuantity']; ?> left in stock!
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['CartItemID']; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number"
                                            class="quantity-input-cart"
                                            name="quantity[<?php echo $item['CartItemID']; ?>]"
                                            value="<?php echo $item['Quantity']; ?>"
                                            min="1"
                                            max="<?php echo $item['StockQuantity']; ?>"
                                            data-price="<?php echo $item['Price']; ?>"
                                            data-cart-item-id="<?php echo $item['CartItemID']; ?>"
                                            onchange="handleQuantityChange(this)">
                                        <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['CartItemID']; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>

                                    <div class="item-total">
                                        <p class="item-total-price">
                                            RM <?php echo number_format($item['Price'] * $item['Quantity'], 2); ?>
                                        </p>
                                        <button type="submit"
                                            name="remove_item"
                                            value="<?php echo $item['CartItemID']; ?>"
                                            class="remove-item">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="no-selection-message">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                            <h3>No Items Selected</h3>
                            <p>Please select at least one item to proceed to checkout.</p>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <h5>Order Summary</h5>
                        <div class="selected-items-count">
                            <span id="summarySelectedCount">0</span> items selected
                        </div>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal">RM 0.00</span>
                        </div>
                        <hr>
                        <div class="summary-row">
                            <strong>Total</strong>
                            <strong id="total">RM 0.00</strong>
                        </div>
                        <button type="submit" class="checkout-btn" name="checkout">
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-3x"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="../product/all_product.php" class="continue-shopping">
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="../js/cart.js"></script>
    <?php require_once __DIR__ . '/../_foot.php'; ?>
</body>

</html>