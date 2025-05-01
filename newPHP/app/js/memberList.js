function showEditForm(id, name, email, phone, profilePhoto, gender, dob) {
    document.getElementById('editModal').classList.add('active');
    document.getElementById('edit_member_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_gender').value = gender;
    document.getElementById('edit_dob').value = dob;
    // Profile photo input is left blank for security reasons
}

function hideEditForm() {
    document.getElementById('editModal').classList.remove('active');
}