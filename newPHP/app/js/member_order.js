document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change
    const filterForm = document.querySelector('.filters-form');
    const filterInputs = filterForm.querySelectorAll('select, input[type="text"]');
    
    filterInputs.forEach(input => {
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        }
    });
}); 