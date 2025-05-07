<?php
require_once __DIR__ . '/../_base.php';
// Redirect if not logged in
redirectIfNotLoggedIn();
// Get cart items
$selected_ids = $_POST['selected_items'] ?? [];
if (empty($selected_ids)) {
    header("Location: cart.php");
    exit();
}
$selected_ids = array_map('intval', $selected_ids); // sanitize
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
// Total only selected items
$total = calculateCartTotal($cart_items);
// Redirect if cart is empty
redirectIfCartIsEmpty($cart_items);
// Calculate total
$total = calculateCartTotal($cart_items);
// Handle checkout
// $error = processCheckout($pdo, $cart_items, $total);
$error = null;
handleCartActions($pdo);
?>

<?php require_once __DIR__ . '/../_head.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Beauty & Wellness</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="checkout-container">
        <h1>Checkout</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="order_confirmation.php" enctype="multipart/form-data">
            <div class="checkout-grid">
                <div class="checkout-details">
                    <!-- Shipping Information Section -->
                    <div class="checkout-section">
                        <h2 class="section-title">Shipping Information</h2>

                        <div class="form-group">
                            <label class="form-label" for="fullname">Full Name</label>
                            <input class="form-input" type="text" name="fullname" id="fullname" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="address">Address</label>
                            <input class="form-input" type="text" name="address" id="address" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="city">City</label>
                            <input class="form-input" type="text" name="city" id="city" required>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label" for="postcode">Postcode</label>
                                <input class="form-input" type="text" name="postcode" id="postcode" pattern="[0-9]{5}" title="Five digit postcode" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="state">State</label>
                                <select class="form-input" name="state" id="state" required>
                                    <option value="">Select State</option>
                                    <option value="Johor">Johor</option>
                                    <option value="Kedah">Kedah</option>
                                    <option value="Kelantan">Kelantan</option>
                                    <option value="Malacca">Malacca</option>
                                    <option value="Negeri Sembilan">Negeri Sembilan</option>
                                    <option value="Pahang">Pahang</option>
                                    <option value="Penang">Penang</option>
                                    <option value="Perak">Perak</option>
                                    <option value="Perlis">Perlis</option>
                                    <option value="Sabah">Sabah</option>
                                    <option value="Sarawak">Sarawak</option>
                                    <option value="Selangor">Selangor</option>
                                    <option value="Terengganu">Terengganu</option>
                                    <option value="Kuala Lumpur">Kuala Lumpur</option>
                                    <option value="Putrajaya">Putrajaya</option>
                                    <option value="Labuan">Labuan</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input class="form-input" type="tel" name="phone" id="phone" pattern="[0-9\-\+]{10,13}" title="Phone number (10-13 digits)" required>
                        </div>
                    </div>

                    <!-- Payment Method Section -->
                    <div class="checkout-section">
                        <h2 class="section-title">Payment Method</h2>

                        <div class="payment-method">
                            <div class="payment-method-label" style="display: flex; align-items: center; border: 2px solid #d38db2; background-color: #faf4f8; padding: 15px;">
                                <i class="fas fa-university" style="font-size: 24px; margin-right: 10px;"></i>
                                <strong>Cash on Delivery</strong>
                            </div>
                        </div>

                        <!-- Hidden input with fixed payment method -->
                        <input type="hidden" name="payment_method" value="Cash on Delivery">
                    </div>
                </div>

                <!-- Order Summary Section -->
                <div class="checkout-summary">
                    <div class="checkout-section">
                        <h2 class="section-title">Order Summary</h2>

                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-summary-item">
                                <span><?php echo htmlspecialchars($item['ProductName']); ?> x <?php echo $item['Quantity']; ?></span>
                                <span>RM <?php echo number_format($item['Price'] * $item['Quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>

                        <!-- Shipping Fee Row -->
                        <div class="order-summary-item" id="shipping-fee-row">
                            <span>Shipping Fee</span>
                            <span id="shipping-fee-display">RM 5.00</span>
                        </div>

                        <div class="order-summary-item order-total">
                            <strong>Total</strong>
                            <strong id="total-amount">RM <?php echo number_format($total + 5, 2); ?></strong>
                        </div>

                        <!-- Hidden input for shipping fee -->
                        <input type="hidden" name="shipping_fee" id="shipping-fee-input" value="5">

                        <!-- If selected_items is passed, include them in the form -->
                        <?php foreach ($cart_items as $item): ?>
                            <input type="hidden" name="selected_items[]" value="<?php echo htmlspecialchars($item['CartItemID']); ?>">
                        <?php endforeach; ?>

                        <button type="submit" name="checkout" class="btn-checkout">
                            Complete Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <script>
        // File upload handling
        const uploadInput = document.getElementById('transaction_proof');
        const uploadContainer = document.getElementById('upload-container');
        const selectedFileDiv = document.getElementById('selected-file');

        // Standard file upload functionality
        uploadInput?.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                selectedFileDiv.textContent = fileName;
                selectedFileDiv.style.display = 'block';
            } else {
                selectedFileDiv.textContent = 'No file selected';
                selectedFileDiv.style.display = 'none';
            }
        });

        // Drag and drop functionality for file upload
        if (uploadContainer) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadContainer.addEventListener(eventName, preventDefaults, false);
            });
        }

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (uploadContainer) {
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadContainer.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadContainer.addEventNameventListener(eventName, unhighlight, false);
            });

            uploadContainer.addEventListener('drop', handleDrop, false);
        }

        function highlight() {
            uploadContainer.classList.add('active');
        }

        function unhighlight() {
            uploadContainer.classList.remove('active');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                uploadInput.files = files;
                const fileName = files[0].name;
                selectedFileDiv.textContent = fileName;
                selectedFileDiv.style.display = 'block';
            }
        }

        // Payment method handling
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const hiddenInput = document.getElementById('selected_payment_method');

            // Set initial value
            const checkedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (checkedMethod && hiddenInput) {
                hiddenInput.value = checkedMethod.value;
                console.log("Initial payment method: " + hiddenInput.value);
            }

            // Update when selection changes
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if (hiddenInput) {
                        hiddenInput.value = this.value;
                        console.log("Payment method selected: " + this.value);
                        console.log("Hidden input updated: " + hiddenInput.value);
                    }
                });
            });

            // Add form submission debug
            document.querySelector('form').addEventListener('submit', function(e) {
                if (hiddenInput) {
                    console.log("Form submitting with payment method: " + hiddenInput.value);
                }
            });

            // Shipping fee calculation based on state
            const stateSelect = document.getElementById('state');
            const shippingFeeRow = document.getElementById('shipping-fee-row');
            const shippingFeeDisplay = document.getElementById('shipping-fee-display');
            const shippingFeeInput = document.getElementById('shipping-fee-input');
            const totalAmountDisplay = document.getElementById('total-amount');
            const cartTotal = <?php echo $total; ?>;

            // Initially hide the shipping fee row if no state is selected
            if (stateSelect && stateSelect.value === '') {
                shippingFeeRow.style.display = 'none';
                shippingFeeInput.value = 0;
                totalAmountDisplay.textContent = `RM ${cartTotal.toFixed(2)}`;
            }

            if (stateSelect) {
                stateSelect.addEventListener('change', updateShippingFee);

                function updateShippingFee() {
                    const selectedState = stateSelect.value;
                    let shippingFee = 5; // Default shipping fee

                    // Check if state is empty
                    if (selectedState === '') {
                        // Hide shipping fee row
                        shippingFeeRow.style.display = 'none';
                        shippingFee = 0;
                    } else {
                        // Show shipping fee row
                        shippingFeeRow.style.display = 'flex';

                        // Check if selected state is Sabah, Sarawak or Labuan
                        if (selectedState === 'Sabah' || selectedState === 'Sarawak' || selectedState === 'Labuan') {
                            shippingFee = 10; // Higher shipping fee for East Malaysia
                        }
                    }

                    // Update the shipping fee display
                    shippingFeeDisplay.textContent = `RM ${shippingFee.toFixed(2)}`;

                    // Update the hidden input
                    shippingFeeInput.value = shippingFee;

                    // Update the total amount
                    const newTotal = cartTotal + shippingFee;
                    totalAmountDisplay.textContent = `RM ${newTotal.toFixed(2)}`;

                    console.log(`Shipping fee updated: ${selectedState === '' ? 'Hidden' : 'RM' + shippingFee.toFixed(2)} for ${selectedState || 'No state selected'}`);
                }
            }
        });
    </script>
</body>

</html>
<?php require_once __DIR__ . '/../_foot.php'; ?>