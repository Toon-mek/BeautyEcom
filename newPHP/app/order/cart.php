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
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            align-items: start;
        }
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .cart-item {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            position: relative;
        }
        .cart-item:hover {
            transform: translateY(-2px);
        }
        .item-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin: 0;
        }
        .cart-item.selected {
            border: 2px solid #28a745;
            background-color: #f8fff9;
        }
        .cart-item-content {
            display: grid;
            grid-template-columns: 120px 1fr auto auto auto;
            gap: 1.5rem;
            align-items: center;
        }
        .product-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: #212529;
        }
        .product-price {
            color: #666;
            margin: 0;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 6px;
        }
        .quantity-btn {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quantity-btn:hover {
            background: #e9ecef;
            border-color: #ced4da;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.25rem;
            font-size: 1rem;
            background: white;
            transition: border-color 0.2s;
        }
        .quantity-input:focus {
            outline: none;
            border-color: #28a745;
        }
        .stock-warning {
            color: #dc3545;
            font-size: 0.875rem;
            margin: 0;
        }
        .item-total {
            text-align: right;
            min-width: 100px;
        }
        .item-total-price {
            font-weight: 600;
            font-size: 1.1rem;
            color: #212529;
            margin: 0;
        }
        .remove-item {
            background: none;
            border: none;
            color: #dc3545;
            padding: 0.5rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .remove-item:hover {
            color: #c82333;
        }
        .update-cart-btn {
            display: none;
        }
        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .summary-row:last-child {
            margin-bottom: 0;
        }
        .selected-items-count {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 1rem;
        }
        .checkout-btn {
            background: #212529;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 1rem;
            width: 100%;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .checkout-btn:hover {
            background: #343a40;
        }
        .empty-cart {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .continue-shopping {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: #212529;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .continue-shopping:hover {
            background: #343a40;
        }
        .select-all-container {
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .select-all-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            margin: 0;
        }
        .select-all-label {
            font-weight: 500;
            cursor: pointer;
            margin: 0;
        }
        .no-selection-message {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 1rem;
            display: none;
        }
        @media (max-width: 768px) {
            .cart-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .product-image {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../_head.php'; ?>

    <div class="cart-container">
        <h1 class="mb-4">Shopping Cart</h1>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(count($cart_items) > 0): ?>
            <form method="POST" action="" id="cartForm">
                <div class="cart-grid">
                    <div class="cart-items">
                        <div class="select-all-container">
                            <input type="checkbox" id="selectAll" class="select-all-checkbox">
                            <label for="selectAll" class="select-all-label">Select All Items</label>
                        </div>

                        <?php foreach($cart_items as $item): ?>
                            <div class="cart-item" data-price="<?php echo $item['Price'] * $item['Quantity']; ?>">
                                <div class="cart-item-content">
                                    <img src="../uploads/<?php echo htmlspecialchars($item['ProdIMG1']); ?>" 
                                         class="product-image" 
                                         alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                                    
                                    <div class="product-info">
                                        <h5 class="product-name"><?php echo htmlspecialchars($item['ProductName']); ?></h5>
                                        <p class="product-price">RM <?php echo number_format($item['Price'], 2); ?></p>
                                        <?php if($item['StockQuantity'] < 5): ?>
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
                                               class="quantity-input"
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

                                    <input type="checkbox" 
                                           name="selected_items[]" 
                                           value="<?php echo $item['CartItemID']; ?>"
                                           class="item-checkbox"
                                           data-price="<?php echo $item['Price'] * $item['Quantity']; ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="no-selection-message">
                            <i class="fas fa-shopping-cart fa-2x" style="color: #dee2e6; margin-bottom: 1rem;"></i>
                            <h3>No Items Selected</h3>
                            <p>Please select at least one item to proceed to checkout.</p>
                        </div>
                    </div>
                    
                    <div class="cart-summary">
                        <h5 style="margin-top: 0;">Order Summary</h5>
                        <div class="selected-items-count">
                            <span id="selectedCount">0</span> items selected
                        </div>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal">RM 0.00</span>
                        </div>
                        <hr style="margin: 1rem 0;">
                        <div class="summary-row">
                            <strong>Total</strong>
                            <strong id="total">RM 0.00</strong>
                        </div>
                        <button type="submit" name="checkout" class="checkout-btn" disabled>
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-3x" style="color: #dee2e6; margin-bottom: 1rem;"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="../product/all_product.php" class="continue-shopping">
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(cartItemId, change) {
            const input = document.querySelector(`input[data-cart-item-id="${cartItemId}"]`);
            const newValue = Math.max(1, Math.min(parseInt(input.value) + change, parseInt(input.max)));
            input.value = newValue;
            handleQuantityChange(input);
        }

        function handleQuantityChange(input) {
            const cartItemId = input.dataset.cartItemId;
            const quantity = input.value;
            
            // Update the item total
            const price = parseFloat(input.dataset.price);
            const itemTotal = price * quantity;
            const cartItem = input.closest('.cart-item');
            const itemTotalElement = cartItem.querySelector('.item-total-price');
            itemTotalElement.textContent = `RM ${itemTotal.toFixed(2)}`;
            
            // Update the checkbox data-price
            const checkbox = cartItem.querySelector('.item-checkbox');
            checkbox.dataset.price = itemTotal;
            
            // Update the cart item data-price
            cartItem.dataset.price = itemTotal;
            
            // Update the selected total if the item is selected
            if (checkbox.checked) {
                updateSelectedTotal();
            }
            
            // Submit the form to update the cart
            const form = document.getElementById('cartForm');
            const formData = new FormData(form);
            formData.append('update_cart', '1');
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    // Update successful
                }
            }).catch(error => {
                console.error('Error updating cart:', error);
            });
        }

        // Handle item selection
        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const cartItem = this.closest('.cart-item');
                if (this.checked) {
                    cartItem.classList.add('selected');
                } else {
                    cartItem.classList.remove('selected');
                }
                updateSelectedTotal();
                updateCheckoutButton();
            });
        });

        // Handle select all
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                const cartItem = checkbox.closest('.cart-item');
                if (this.checked) {
                    cartItem.classList.add('selected');
                } else {
                    cartItem.classList.remove('selected');
                }
            });
            updateSelectedTotal();
            updateCheckoutButton();
        });

        function updateSelectedTotal() {
            let selectedTotal = 0;
            const selectedItems = document.querySelectorAll('.item-checkbox:checked');
            
            selectedItems.forEach(checkbox => {
                selectedTotal += parseFloat(checkbox.dataset.price);
            });
            
            document.getElementById('subtotal').textContent = `RM ${selectedTotal.toFixed(2)}`;
            document.getElementById('total').textContent = `RM ${selectedTotal.toFixed(2)}`;
            document.getElementById('selectedCount').textContent = selectedItems.length;
        }

        function updateCheckoutButton() {
            const selectedItems = document.querySelectorAll('.item-checkbox:checked').length;
            const checkoutBtn = document.querySelector('.checkout-btn');
            const noSelectionMsg = document.querySelector('.no-selection-message');
            
            if (selectedItems > 0) {
                checkoutBtn.disabled = false;
                noSelectionMsg.style.display = 'none';
            } else {
                checkoutBtn.disabled = true;
                noSelectionMsg.style.display = 'block';
            }
        }

        // Initialize the summary with 0 items selected
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedTotal();
            updateCheckoutButton();
        });

        // Add form submission handler
        document.getElementById('cartForm').addEventListener('submit', function(e) {
            const submitButton = e.submitter;
            if (submitButton && submitButton.name === 'checkout') {
                const selectedItems = document.querySelectorAll('.item-checkbox:checked').length;
                if (selectedItems === 0) {
                    e.preventDefault();
                    return;
                }
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    </script>

    <?php require_once __DIR__ . '/../_foot.php'; ?>
</body>
</html>
