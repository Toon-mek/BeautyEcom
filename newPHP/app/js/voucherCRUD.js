// Show add voucher form modal
function showAddVoucherForm() {
    document.getElementById('addVoucherModal').classList.add('active');
}

// Hide add voucher form modal
function hideAddVoucherForm() {
    document.getElementById('addVoucherModal').classList.remove('active');
    document.getElementById('addVoucherForm').reset();
}

// Set minimum expiry date to 24 hours from now
window.addEventListener('DOMContentLoaded', function() {
    var expiryInput = document.getElementById('expiry_date');
    if (expiryInput) {
        var now = new Date();
        now.setDate(now.getDate() + 1); // 24 hours from now
        var minDate = now.toISOString().split('T')[0];
        expiryInput.min = minDate;
    }
    // For edit modal
    var editExpiryInput = document.getElementById('edit_expiry_date');
    if (editExpiryInput) {
        var now = new Date();
        now.setDate(now.getDate() + 1);
        var minDate = now.toISOString().split('T')[0];
        editExpiryInput.min = minDate;
    }
});

// Validate discount input (1-100, integer only)
function validateVoucherForm() {
    var discount = document.querySelector('#addVoucherForm input[name="discount"]');
    var value = parseInt(discount.value, 10);
    if (isNaN(value) || value < 1 || value > 100) {
        alert('Discount must be an integer between 1 and 100.');
        discount.focus();
        return false;
    }
    return true;
}

// Validate edit form
function validateEditVoucherForm() {
    var discount = document.querySelector('#editVoucherForm input[name="discount"]');
    var value = parseInt(discount.value, 10);
    if (isNaN(value) || value < 1 || value > 100) {
        alert('Discount must be an integer between 1 and 100.');
        discount.focus();
        return false;
    }
    return true;
}

// Show used by modal and fetch data
function showUsedByModal(voucherId) {
    var modal = document.getElementById('usedByModal');
    var content = document.getElementById('usedByContent');
    modal.classList.add('active');
    content.innerHTML = 'Loading...';
    // AJAX to fetch users
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'voucherCRUD.php?usedby=' + voucherId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            content.innerHTML = xhr.responseText;
        } else {
            content.innerHTML = 'Error loading data.';
        }
    };
    xhr.send();
}

// Hide used by modal
function hideUsedByModal() {
    document.getElementById('usedByModal').classList.remove('active');
} 