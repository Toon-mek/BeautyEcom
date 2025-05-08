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
    SELECT ci.*, p.ProductName, p.Price, p.ProdIMG1, ci.Quantity 
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

// Handle voucher application
$voucher = null;
$error_message = null;
$voucher_code = $_POST['voucher_code'] ?? null;
$voucher_discount = 0;

// Only process voucher if "apply_voucher" button was clicked or if a voucher code exists
if (isset($_POST['apply_voucher']) || $voucher_code) {
    if (!empty($voucher_code)) {
        $stmt = $pdo->prepare("
            SELECT * FROM voucher 
            WHERE Code = ? AND Status = 'Active' AND ExpiryDate >= CURDATE()
        ");
        $stmt->execute([$voucher_code]);
        $voucher = $stmt->fetch();

        if ($voucher) {
            $voucher_discount = $voucher['Discount'];
            // Apply percentage discount
            $discounted_amount = $total * ($voucher_discount / 100);
            $total = $total - $discounted_amount;
        } else {
            $error_message = "Invalid or expired voucher code.";
        }
    } else {
        $error_message = "Please enter a voucher code.";
    }
    
    // If just applying a voucher (not checking out), regenerate the form with selected items
    if (isset($_POST['apply_voucher'])) {
        // We're just applying a voucher, not checking out yet
        $_POST['checkout'] = null;
    }
}

// Handle checkout
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

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
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

                        <!-- Voucher Section -->
                        <div class="voucher-section">
                            <div class="voucher-input">
                                <input type="text" name="voucher_code" id="voucher_code" placeholder="Enter voucher code" 
                                       value="<?php echo htmlspecialchars($voucher_code ?? ''); ?>" 
                                       class="form-input" <?php echo isset($voucher) && $voucher ? 'disabled' : ''; ?>>
                                
                                <button type="button" id="apply_voucher_btn" class="btn-apply-voucher" 
                                        <?php echo isset($voucher) && $voucher ? 'disabled' : ''; ?>>Apply</button>
                            </div>
                            
                            <div id="voucher-message" class="<?php echo isset($voucher) ? 'voucher-success' : (isset($error_message) ? 'voucher-error' : ''); ?>">
                                <?php if (isset($voucher) && $voucher): ?>
                                    <i class="fas fa-check-circle"></i> Voucher applied successfully!
                                <?php elseif (isset($error_message)): ?>
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Voucher Discount Row -->
                        <?php if ($voucher_discount > 0): ?>
                            <div class="order-summary-item">
                                <span>Voucher Discount</span>
                                <span>-<?php echo $voucher_discount; ?>%</span>
                            </div>
                        <?php endif; ?>

                        <div class="order-summary-item order-total">
                            <strong>Total</strong>
                            <strong id="total-amount">RM <?php echo number_format($total + 5, 2); ?></strong>
                        </div>

                        <!-- Hidden input for shipping fee -->
                        <input type="hidden" name="shipping_fee" id="shipping-fee-input" value="5">

                        <!-- Add voucher information to the form submission -->
                        <?php if ($voucher): ?>
                            <input type="hidden" name="voucher_id" value="<?php echo $voucher['VoucherID']; ?>">
                            <input type="hidden" name="voucher_code" value="<?php echo htmlspecialchars($voucher['Code']); ?>">
                            <input type="hidden" name="voucher_discount" value="<?php echo $voucher_discount; ?>">
                        <?php endif; ?>

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
            // Add this code at the start of the DOMContentLoaded event
            // Initially disable the voucher section
            const voucherInput = document.getElementById('voucher_code');
            const applyButton = document.getElementById('apply_voucher_btn');
            const voucherSection = document.querySelector('.voucher-section');
            
            if (voucherInput && applyButton) {
                voucherInput.disabled = true;
                applyButton.disabled = true;
                
                // Add a disabled message
                const disabledMessage = document.createElement('div');
                disabledMessage.id = 'voucher-disabled-message';
                disabledMessage.className = 'voucher-info';
                disabledMessage.innerHTML = '<i class="fas fa-info-circle"></i> Please select a state before applying a voucher';
                voucherSection.appendChild(disabledMessage);
            }

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

                    // Enable or disable voucher section based on state selection
                    const voucherInput = document.getElementById('voucher_code');
                    const applyButton = document.getElementById('apply_voucher_btn');
                    const disabledMessage = document.getElementById('voucher-disabled-message');
                    
                    if (selectedState === '') {
                        // Disable voucher section when no state is selected
                        if (voucherInput) {
                            voucherInput.disabled = true;
                        }
                        if (applyButton) {
                            applyButton.disabled = true;
                        }
                        if (disabledMessage) {
                            disabledMessage.style.display = 'block';
                        }
                        
                        // Hide shipping fee row
                        shippingFeeRow.style.display = 'none';
                        shippingFee = 0;
                    } else {
                        // Enable voucher section when state is selected
                        if (voucherInput) {
                            voucherInput.disabled = false;
                        }
                        if (applyButton) {
                            applyButton.disabled = false;
                        }
                        if (disabledMessage) {
                            disabledMessage.style.display = 'none';
                        }
                        
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

                    // Check if a voucher is applied by looking for the discount row
                    let voucherDiscount = 0;
                    let discountRow = null;
                    document.querySelectorAll('.order-summary-item').forEach(item => {
                        if (item.querySelector('span') && item.querySelector('span').textContent === 'Voucher Discount') {
                            discountRow = item;
                            // Extract the discount percentage
                            const discountText = item.querySelector('span:last-child').textContent;
                            voucherDiscount = parseFloat(discountText.replace('-', '').replace('%', '')) || 0;
                        }
                    });

                    // If no discount row found, check for hidden input
                    if (!discountRow) {
                        const voucherDiscountInput = document.querySelector('input[name="voucher_discount"]');
                        if (voucherDiscountInput) {
                            voucherDiscount = parseFloat(voucherDiscountInput.value) || 0;
                        }
                    }

                    // Calculate final total with current voucher discount (if any)
                    const subtotal = <?php echo $total; ?>;
                    const discountAmount = subtotal * (voucherDiscount / 100);
                    const newTotal = subtotal - discountAmount + shippingFee;
                    
                    console.log(`Recalculating total - Subtotal: ${subtotal}, Discount: ${voucherDiscount}%, Amount: ${discountAmount}, Shipping: ${shippingFee}, Total: ${newTotal}`);
                    
                    totalAmountDisplay.textContent = `RM ${newTotal.toFixed(2)}`;
                }
            }
        });

        // Voucher application with AJAX
        function applyVoucherHandler() {
            const voucherCode = document.getElementById('voucher_code').value;
            const messageDiv = document.getElementById('voucher-message');
            
            if (!voucherCode) {
                messageDiv.className = 'voucher-error';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a voucher code.';
                return;
            }
            
            // Collect selected items
            const selectedItems = [];
            document.querySelectorAll('input[name="selected_items[]"]').forEach(item => {
                selectedItems.push(item.value);
            });
            
            // Create form data
            const formData = new FormData();
            formData.append('voucher_code', voucherCode);
            formData.append('apply_voucher', '1');
            selectedItems.forEach(item => {
                formData.append('selected_items[]', item);
            });
            
            // Show loading indicator
            messageDiv.className = '';
            messageDiv.textContent = 'Checking voucher...';
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary div to parse the response
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Check if there's an error message
                const newMessage = doc.getElementById('voucher-message');
                if (newMessage) {
                    messageDiv.className = newMessage.className;
                    messageDiv.innerHTML = newMessage.innerHTML;
                    
                    // If it's an error message, don't recalculate the total
                    if (newMessage.className === 'voucher-error') {
                        return;  // Exit early, don't calculate anything
                    }
                }
                
                // Continue with the rest of the code only if the voucher is valid
                // Get updated total
                var newTotal = doc.getElementById('total-amount')?.textContent;
                if (newTotal) {
                    document.getElementById('total-amount').textContent = newTotal;
                }
                
                // Update voucher discount row - FIXED SELECTOR CODE
                // Find discount row in the fetched HTML
                let discountRow = null;
                doc.querySelectorAll('.order-summary-item').forEach(item => {
                    if (item.querySelector('span') && item.querySelector('span').textContent === 'Voucher Discount') {
                        discountRow = item;
                    }
                });
                
                // Find discount row in the current page
                let currentDiscountRow = null;
                document.querySelectorAll('.order-summary-item').forEach(item => {
                    if (item.querySelector('span') && item.querySelector('span').textContent === 'Voucher Discount') {
                        currentDiscountRow = item;
                    }
                });
                
                if (discountRow && !currentDiscountRow) {
                    // Add discount row if it doesn't exist
                    const orderTotal = document.querySelector('.order-total');
                    orderTotal.insertAdjacentHTML('beforebegin', discountRow.outerHTML);
                } else if (!discountRow && currentDiscountRow) {
                    // Remove discount row if voucher is invalid
                    currentDiscountRow.remove();
                }
                
                // Update hidden fields for checkout
                const voucherFields = doc.querySelectorAll('input[name^="voucher_"]');
                if (voucherFields.length > 0) {
                    // Add or update voucher fields
                    document.querySelectorAll('input[name^="voucher_"]').forEach(el => el.remove());
                    
                    let voucherDiscountValue = 0;
                    
                    voucherFields.forEach(field => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = field.name;
                        input.value = field.value;
                        document.querySelector('form').appendChild(input);
                        
                        // Get discount value for calculation
                        if (field.name === 'voucher_discount') {
                            voucherDiscountValue = parseFloat(field.value) || 0;
                        }
                    });
                    
                    // Disable the voucher input and apply button
                    const voucherInput = document.getElementById('voucher_code');
                    const applyButton = document.getElementById('apply_voucher_btn');
                    
                    if (voucherInput) voucherInput.disabled = true;
                    if (applyButton) applyButton.disabled = true;
                    
                    // Calculate new total with current shipping fee and valid voucher
                    const currentShippingFee = parseFloat(document.getElementById('shipping-fee-input').value) || 0;
                    calculateOrderTotal(currentShippingFee, voucherDiscountValue);
                }
            })
            .catch(error => {
                messageDiv.className = 'voucher-error';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error checking voucher. Please try again.';
                console.error('Error:', error);
                
                // Don't recalculate the total when there's an error
                // Just restore the previous total from before the voucher application attempt
            });
        }

        // Remove voucher handler
        function removeVoucherHandler() {
            // Clear the voucher code field and enable it
            const voucherInput = document.getElementById('voucher_code');
            voucherInput.value = '';
            voucherInput.disabled = false;
            
            // Update message div
            const messageDiv = document.getElementById('voucher-message');
            messageDiv.className = '';
            messageDiv.innerHTML = '';
            
            // Remove the voucher discount row if it exists
            let discountRow = null;
            document.querySelectorAll('.order-summary-item').forEach(item => {
                if (item.querySelector('span') && item.querySelector('span').textContent === 'Voucher Discount') {
                    discountRow = item;
                }
            });
            
            if (discountRow) {
                discountRow.remove();
            }
            
            // Remove all hidden voucher fields
            document.querySelectorAll('input[name^="voucher_"]').forEach(el => el.remove());
            
            // Replace the remove button with an apply button
            const removeButton = document.getElementById('remove_voucher_btn');
            const applyButton = document.createElement('button');
            applyButton.type = 'button';
            applyButton.id = 'apply_voucher_btn';
            applyButton.className = 'btn-apply-voucher';
            applyButton.textContent = 'Apply';
            removeButton.parentNode.replaceChild(applyButton, removeButton);
            
            // Update the total (remove discount)
            const shippingFee = parseFloat(document.getElementById('shipping-fee-input').value) || 0;
            const newTotal = <?php echo $total; ?> + shippingFee;
            document.getElementById('total-amount').textContent = `RM ${newTotal.toFixed(2)}`;
            
            // Add event listener to the new apply button
            document.getElementById('apply_voucher_btn').addEventListener('click', applyVoucherHandler);
        }

        // Add event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Add this after your existing DOMContentLoaded code
            
            // Initialize apply button event listener
            const applyButton = document.getElementById('apply_voucher_btn');
            if (applyButton) {
                applyButton.addEventListener('click', applyVoucherHandler);
            }
        });

        function updateShippingFee() {
            const selectedState = stateSelect.value;
            let shippingFee = 5; // Default shipping fee
        
            // Enable or disable voucher section based on state selection
            const voucherInput = document.getElementById('voucher_code');
            const applyButton = document.getElementById('apply_voucher_btn');
            const disabledMessage = document.getElementById('voucher-disabled-message');
            
            if (selectedState === '') {
                // Disable voucher section when no state is selected
                if (voucherInput) voucherInput.disabled = true;
                if (applyButton) applyButton.disabled = true;
                if (disabledMessage) disabledMessage.style.display = 'block';
                
                // Hide shipping fee row
                shippingFeeRow.style.display = 'none';
                shippingFee = 0;
            } else {
                // Enable voucher section when state is selected
                if (voucherInput) voucherInput.disabled = false;
                if (applyButton) applyButton.disabled = false;
                if (disabledMessage) disabledMessage.style.display = 'none';
                
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
            
            // Always recalculate total with proper discount if voucher is applied
            recalculateTotal(shippingFee);
        }
        
        // Add a new function to handle total recalculation
        function recalculateTotal(shippingFee) {
            const subtotal = <?php echo $total; ?>;
            let voucherDiscount = 0;
            
            // Check for voucher discount from discount row
            document.querySelectorAll('.order-summary-item').forEach(item => {
                if (item.querySelector('span') && item.querySelector('span').textContent === 'Voucher Discount') {
                    const discountText = item.querySelector('span:last-child').textContent;
                    voucherDiscount = parseFloat(discountText.replace('-', '').replace('%', '')) || 0;
                }
            });
            
            // If no row found, check hidden input
            if (voucherDiscount === 0) {
                const voucherDiscountInput = document.querySelector('input[name="voucher_discount"]');
                if (voucherDiscountInput) {
                    voucherDiscount = parseFloat(voucherDiscountInput.value) || 0;
                }
            }
            
            const discountAmount = subtotal * (voucherDiscount / 100);
            const newTotal = subtotal - discountAmount + shippingFee;
            
            console.log(`Total recalculation - Subtotal: ${subtotal}, Discount: ${voucherDiscount}%, Amount: ${discountAmount}, Shipping: ${shippingFee}, Total: ${newTotal}`);
            
            document.getElementById('total-amount').textContent = `RM ${newTotal.toFixed(2)}`;
        }

        // Replace your existing calculation code with this consolidated function
        function calculateOrderTotal(shippingFee = null, voucherDiscount = null) {
            // Get shipping fee from parameter or input field
            if (shippingFee === null) {
                shippingFee = parseFloat(document.getElementById('shipping-fee-input').value) || 0;
            }
            
            // Get voucher discount from parameter or check UI elements
            if (voucherDiscount === null) {
                // Check for voucher discount from discount row
                document.querySelectorAll('.order-summary-item').forEach(item => {
                    if (item.querySelector('span') && item.querySelector('span').textContent === 'Voucher Discount') {
                        const discountText = item.querySelector('span:last-child').textContent;
                        voucherDiscount = parseFloat(discountText.replace('-', '').replace('%', '')) || 0;
                    }
                });
                
                // If no row found, check hidden input
                if (!voucherDiscount) {
                    const voucherDiscountInput = document.querySelector('input[name="voucher_discount"]');
                    if (voucherDiscountInput) {
                        voucherDiscount = parseFloat(voucherDiscountInput.value) || 0;
                    } else {
                        voucherDiscount = 0;
                    }
                }
            }
            
            // Calculate final price
            const subtotal = <?php echo $total; ?>;
            const discountAmount = subtotal * (voucherDiscount / 100);
            const newTotal = subtotal - discountAmount + shippingFee;
            
            console.log(`Order calculation - Subtotal: ${subtotal}, Discount: ${voucherDiscount}%, Amount: ${discountAmount}, Shipping: ${shippingFee}, Total: ${newTotal}`);
            
            // Update UI
            document.getElementById('total-amount').textContent = `RM ${newTotal.toFixed(2)}`;
            
            return newTotal;
        }
    </script>
</body>

</html>
<?php require_once __DIR__ . '/../_foot.php'; ?>