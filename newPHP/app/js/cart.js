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
document.addEventListener('DOMContentLoaded', function() {
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
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
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
    }

    // Initialize the summary
    updateSelectedTotal();
    updateCheckoutButton();

    // Add form submission handler
    const cartForm = document.getElementById('cartForm');
    if (cartForm) {
        cartForm.addEventListener('submit', function(e) {
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
    }
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