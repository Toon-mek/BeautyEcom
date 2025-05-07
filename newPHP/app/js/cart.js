document.addEventListener('DOMContentLoaded', function() {
    const cartForm = document.getElementById('cartForm');
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const checkoutBtn = document.querySelector('.checkout-btn');
    const noSelectionMessage = document.querySelector('.no-selection-message');
    const selectedCountElements = document.querySelectorAll('.selected-items-count span');

    // Initialize the cart state
    updateCartSummary();
    updateNoSelectionMessage();

    // Handle "Select All" functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateCartSummary();
            updateNoSelectionMessage();
        });
    }

    // Handle individual item selection
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllCheckbox();
            updateCartSummary();
            updateNoSelectionMessage();
        });
    });

    // Handle quantity updates
    window.updateQuantity = function(cartItemId, change) {
        const input = document.querySelector(`input[data-cart-item-id="${cartItemId}"]`);
        if (input) {
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.getAttribute('max'));
            const newValue = currentValue + change;
            
            if (newValue >= 1 && newValue <= maxValue) {
                input.value = newValue;
                handleQuantityChange(input);
            }
        }
    };

    // Handle quantity input changes
    window.handleQuantityChange = function(input) {
        const cartItemId = input.dataset.cartItemId;
        const price = parseFloat(input.dataset.price);
        const quantity = parseInt(input.value);
        const maxQuantity = parseInt(input.getAttribute('max'));

        // Validate quantity
        if (quantity < 1) {
            input.value = 1;
        } else if (quantity > maxQuantity) {
            input.value = maxQuantity;
        }

        // Update item total price
        const itemTotal = price * parseInt(input.value);
        const itemTotalElement = input.closest('.cart-item-content').querySelector('.item-total-price');
        if (itemTotalElement) {
            itemTotalElement.textContent = `RM ${itemTotal.toFixed(2)}`;
        }

        // Update cart item data price attribute
        const cartItem = input.closest('.cart-item');
        if (cartItem) {
            cartItem.dataset.price = itemTotal;
        }

        // Update cart summary
        updateCartSummary();

        // Send AJAX request to update quantity in database
        fetch('../api/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_item_id=${cartItemId}&quantity=${input.value}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to update quantity:', data.message);
            }
        })
        .catch(error => {
            console.error('Error updating quantity:', error);
        });
    };

    // Update cart summary totals
    function updateCartSummary() {
        let subtotal = 0;
        let selectedCount = 0;

        itemCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const cartItem = checkbox.closest('.cart-item');
                subtotal += parseFloat(cartItem.dataset.price);
                selectedCount++;
            }
        });

        // Update selected count
        selectedCountElements.forEach(element => {
            element.textContent = selectedCount;
        });

        // Update subtotal and total
        document.getElementById('subtotal').textContent = `RM ${subtotal.toFixed(2)}`;
        document.getElementById('total').textContent = `RM ${subtotal.toFixed(2)}`;

        // Enable/disable checkout button
        if (checkoutBtn) {
            checkoutBtn.disabled = selectedCount === 0;
        }
    }

    // Update "Select All" checkbox state
    function updateSelectAllCheckbox() {
        if (selectAllCheckbox) {
            const allChecked = Array.from(itemCheckboxes).every(checkbox => checkbox.checked);
            const someChecked = Array.from(itemCheckboxes).some(checkbox => checkbox.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        }
    }

    // Update visibility of no selection message
    function updateNoSelectionMessage() {
        if (noSelectionMessage) {
            const hasSelectedItems = Array.from(itemCheckboxes).some(checkbox => checkbox.checked);
            noSelectionMessage.style.display = hasSelectedItems ? 'none' : 'block';
        }
    }

    // Handle form submission
    if (cartForm) {
        cartForm.addEventListener('submit', function(e) {
            const hasSelectedItems = Array.from(itemCheckboxes).some(checkbox => checkbox.checked);
            if (!hasSelectedItems && e.submitter.name === 'checkout') {
                e.preventDefault();
                alert('Please select at least one item to proceed to checkout.');
            }
        });
    }
}); 