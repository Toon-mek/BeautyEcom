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

// Function to check if all individual checkboxes are checked
function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    
    if (checkboxes.length > 0 && checkedBoxes.length === checkboxes.length) {
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.checked = false;
    }
}

// Handle item selection
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    const checkboxes = document.querySelectorAll('.item-checkbox');
    console.log('Found checkboxes:', checkboxes.length);
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            console.log('Checkbox changed:', this.checked);
            const cartItem = this.closest('.cart-item');
            console.log('Cart item found:', cartItem);
            
            if (this.checked) {
                console.log('Adding selected class');
                cartItem.classList.add('selected');
            } else {
                console.log('Removing selected class');
                cartItem.classList.remove('selected');
            }
            updateSelectedTotal();
            updateCheckoutButton();
            updateSelectAllCheckbox(); // Add this call here
        });
    });

    // Handle select all
    const selectAllCheckbox = document.getElementById('selectAll');
    console.log('Select all checkbox found:', selectAllCheckbox);
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            console.log('Select all changed:', this.checked);
            const checkboxes = document.querySelectorAll('.item-checkbox');
            console.log('Total checkboxes to update:', checkboxes.length);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                const cartItem = checkbox.closest('.cart-item');
                console.log('Updating cart item:', cartItem);
                
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

    console.log('Initializing summary');
    updateSelectedTotal();
    updateCheckoutButton();
    updateSelectAllCheckbox(); // Add this call here for initial state

    // Add form submission handler
    const cartForm = document.getElementById('cartForm');
    console.log('Cart form found:', cartForm);
    
    if (cartForm) {
        cartForm.addEventListener('submit', function(e) {
            console.log('Form submitted');
            const submitButton = e.submitter;
            console.log('Submit button:', submitButton);
            
            if (submitButton && submitButton.name === 'checkout') {
                const selectedItems = document.querySelectorAll('.item-checkbox:checked').length;
                console.log('Selected items for checkout:', selectedItems);
                
                if (selectedItems === 0) {
                    console.log('No items selected, preventing submission');
                    e.preventDefault();
                    return;
                }
                console.log('Processing checkout');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    }
});

function updateSelectedTotal() {
    console.log('Updating selected total');
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    var selectedTotal = 0;
    console.log('Found selected items:', selectedItems.length);
    
    selectedItems.forEach(checkbox => {
        const price = parseFloat(checkbox.dataset.price);
        console.log('Item price:', price);
        selectedTotal += price;
    });
    
    console.log('Final total:', selectedTotal);
    document.getElementById('subtotal').textContent = `RM ${selectedTotal.toFixed(2)}`;
    document.getElementById('total').textContent = `RM ${selectedTotal.toFixed(2)}`;
    
    // Update all selectedCount elements
    const selectedCountElements = document.querySelectorAll('.selectedCount');
    document.getElementById('summarySelectedCount').textContent = selectedItems.length;
    document.getElementById('headerSelectedCount').textContent = selectedItems.length;
    selectedCountElements.forEach(element => {
        element.textContent = selectedItems.length;
    });
}

function updateCheckoutButton() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked').length;
    const checkoutBtn = document.querySelector('.checkout-btn');
    const noSelectionMsg = document.querySelector('.no-selection-message');
    
    console.log('Selected items:', selectedItems);
    console.log('Checkout button:', checkoutBtn);
    console.log('No selection message:', noSelectionMsg);

    if (selectedItems > 0) {
        checkoutBtn.disabled = false;
        noSelectionMsg.style.display = 'none';
    } else {
        checkoutBtn.disabled = true;
        noSelectionMsg.style.display = 'block';
    }

    console.log('Checkout button state:', checkoutBtn.disabled);
    console.log('No selection message visibility:', noSelectionMsg.style.display);
}