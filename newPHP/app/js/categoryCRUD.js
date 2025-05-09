// Show add category modal
function showAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}

// Close add category modal
function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

// Edit category function
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.CategoryID;
    document.getElementById('edit_category_name').value = category.CategoryName;
    document.getElementById('edit_category_description').value = category.CategoryDescription;
    document.getElementById('editModal').style.display = 'flex';
}

// Close edit category modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}

// Show add modal on page load if there's an error
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.alert-error')) {
        showAddModal();
    }
}); 