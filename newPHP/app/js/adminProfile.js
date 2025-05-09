// Handle photo upload preview
document.getElementById('photoInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePhotoPreview').src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

// Toggle edit mode for name field
function toggleEdit() {
    const input = document.getElementById("edit_name_field");
    const btn = document.getElementById("editToggleBtn");
    const isEditing = input.hasAttribute("readonly");

    if (isEditing) {
        // Enable editing
        input.removeAttribute("readonly");
        input.focus();
        input.style.backgroundColor = "#fff";
        btn.innerHTML = '<span class="icon icon-pencil"></span> Cancel';
    } else {
        // Disable editing
        input.setAttribute("readonly", "readonly");
        input.style.backgroundColor = "#f8f9fa";
        btn.innerHTML = '<span class="icon icon-pencil"></span> Edit';
    }
}

// Auto-hide success message after 3 seconds
if (document.getElementById('successMessage').textContent) {
    setTimeout(() => {
        document.getElementById('successMessage').style.display = 'none';
    }, 3000);
} 