function showEditForm(member) {
    document.getElementById("edit_member_id").value = member.id;
    document.getElementById("edit_name").value = member.name;
    document.getElementById("edit_email").value = member.email;
    document.getElementById("edit_phone").value = member.phone;
    document.getElementById("edit_status").value = member.status;
    document.getElementById("edit_profile_photo").value = '';

    // ✅ Make modal visible
    document.getElementById("editModal").classList.add('active');
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
