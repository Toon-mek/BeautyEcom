function showEditForm(id, name, email, phone, status, profilePhoto) {
    // Open modal
    document.getElementById('editModal').classList.add('active');

    // Set values
    document.getElementById('edit_member_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;

    // Set status dropdown with safe match
    const statusSelect = document.getElementById('edit_status');

    // Reset file input
    document.getElementById('edit_profile_photo').value = '';
}


function hideEditForm() {
    document.getElementById('editModal').classList.remove('active');
}

// Optional: Click outside to close modal
window.addEventListener('click', function (e) {
    const modal = document.getElementById('editModal');
    if (e.target === modal) {
        hideEditForm();
    }
});
