// 🧠 Update quantity from +/- buttons
function updateQuantity(cartItemId, change) {
    const input = document.querySelector(`input[data-cart-item-id="${cartItemId}"]`);
    const newValue = Math.max(1, Math.min(parseInt(input.value) + change, parseInt(input.max)));
    input.value = newValue;
    handleQuantityChange(input);
}

// 🔄 Handle quantity input change
function handleQuantityChange(input) {
    const cartItemId = input.dataset.cartItemId;
    const quantity = input.value;
    const price = parseFloat(input.dataset.price);
    const itemTotal = price * quantity;
    const cartItem = input.closest('.cart-item');
    const itemTotalElement = cartItem.querySelector('.item-total-price');

    itemTotalElement.textContent = `RM ${itemTotal.toFixed(2)}`;

    // Update checkbox data-price and container
    const checkbox = cartItem.querySelector('.item-checkbox');
    checkbox.dataset.price = itemTotal;
    cartItem.dataset.price = itemTotal;

    // If selected, recalculate total
    if (checkbox.checked) {
        updateSelectedTotal();
    }

    // Submit quantity change to server
    const form = document.getElementById('cartForm');
    const formData = new FormData(form);
    formData.append('update_cart', '1');

    fetch(form.action, {
        method: 'POST',
        body: formData
    }).then(res => {
        if (!res.ok) {
            console.error('Cart update failed.');
        }
    }).catch(console.error);
}

// ✅ Update "Select All" checkbox status
function updateSelectAllCheckbox() {
    const all = document.querySelectorAll('.item-checkbox');
    const checked = document.querySelectorAll('.item-checkbox:checked');
    document.getElementById('selectAll').checked = all.length && checked.length === all.length;
}

// ✅ Update selected total + UI
function updateSelectedTotal() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    let selectedTotal = 0;

    selectedItems.forEach(checkbox => {
        selectedTotal += parseFloat(checkbox.dataset.price);
    });

    document.getElementById('subtotal').textContent = `RM ${selectedTotal.toFixed(2)}`;
    document.getElementById('total').textContent = `RM ${selectedTotal.toFixed(2)}`;
    document.getElementById('summarySelectedCount').textContent = selectedItems.length;
    document.getElementById('headerSelectedCount').textContent = selectedItems.length;

    const selectedCountElements = document.querySelectorAll('.selectedCount');
    selectedCountElements.forEach(el => el.textContent = selectedItems.length);
}

// ✅ Show or hide checkout button
function updateCheckoutButton() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked').length;
    const checkoutBtn = document.querySelector('.checkout-btn');
    const noSelectionMsg = document.querySelector('.no-selection-message');

    checkoutBtn.disabled = selectedItems === 0;
    noSelectionMsg.style.display = selectedItems === 0 ? 'block' : 'none';
}

// ✅ Toggle select all logic
function setupSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.item-checkbox');

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            checkbox.closest('.cart-item').classList.toggle('selected', this.checked);
        });
        updateSelectedTotal();
        updateCheckoutButton();
    });
}

// ✅ Handle form submission + enforce selected items only
function setupFormSubmission() {
    const cartForm = document.getElementById('cartForm');

    if (!cartForm) return;

    cartForm.addEventListener('submit', function (e) {
        const submitBtn = e.submitter;

        // Only intercept if it's a checkout submission
        if (submitBtn && submitBtn.name === 'checkout') {
            const checked = document.querySelectorAll('.item-checkbox:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert("Please select at least one item to checkout.");
                return;
            }

            // 🔄 Clean old inputs
            document.querySelectorAll('input[name="selected_items[]"]').forEach(el => el.remove());

            // 🔁 Inject selected item inputs
            checked.forEach(cb => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'selected_items[]';
                hidden.value = cb.value;
                cartForm.appendChild(hidden);
            });

            // ⏳ Visual feedback
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }
    });
}

// 🧠 MAIN INIT
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.item-checkbox');

    // Checkbox logic
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const cartItem = this.closest('.cart-item');
            cartItem.classList.toggle('selected', this.checked);
            updateSelectedTotal();
            updateCheckoutButton();
            updateSelectAllCheckbox();
        });
    });

    setupSelectAll();
    setupFormSubmission();

    updateSelectedTotal();
    updateCheckoutButton();
    updateSelectAllCheckbox();
});
