function showEditForm(id, category, name, desc, price, qty, img1, img2, img3) {
    document.getElementById('editModal').classList.add('active');
    document.getElementById('edit_product_id').value = id;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = desc;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_quantity').value = qty;
    // File inputs left blank for security reasons
}

function hideEditForm() {
    document.getElementById('editModal').classList.remove('active');
}

function showAddForm() {
    document.getElementById('addModal').classList.add('active');
    document.getElementById('addForm').reset();
}

function hideAddForm() {
    document.getElementById('addModal').classList.remove('active');
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="GET"]');
    const inputs = form.querySelectorAll('select, input[type="text"]');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            form.submit();
        });
    });

    // For text input, submit on Enter key
    const searchInput = form.querySelector('input[type="text"]');
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            form.submit();
        }
    });
});