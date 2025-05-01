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