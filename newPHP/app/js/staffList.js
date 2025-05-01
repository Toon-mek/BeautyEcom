
function showAddForm() {
    document.getElementById('addModal').classList.add('active');
}

function hideAddForm() {
    document.getElementById('addModal').classList.remove('active');
    document.getElementById('addForm').reset();
}

function showEditForm(staff) {
    document.getElementById('editModal').classList.add('active');
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_name').value = staff.name;
    document.getElementById('edit_username').value = staff.username;
    document.getElementById('edit_email').value = staff.email;
    document.getElementById('edit_contact').value = staff.contact;
    document.getElementById('edit_status').value = staff.status;
}

function hideEditForm() {
    document.getElementById('editModal').classList.remove('active');
    document.getElementById('editForm').reset();
}
