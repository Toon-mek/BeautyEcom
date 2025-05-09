// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get the modal
    const modal = document.getElementById("cancelModal");
    
    // Get the button that opens the modal
    const btn = document.getElementById("cancelOrderBtn");
    
    // Get the close button
    const closeBtn = document.getElementsByClassName("close-modal")[0];
    
    // When the user clicks the button, open the modal 
    if (btn) {
        btn.onclick = function() {
            modal.style.display = "block";
        }
    }
    
    // When the user clicks on close button, close the modal
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
    }
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Debug information in browser console
    console.log("Payment Method POST: " + (document.querySelector('input[name="payment_method"]')?.value || 'Not set'));
    console.log("Selected Payment Method: " + (document.querySelector('input[name="selected_payment_method"]')?.value || 'Not set'));
    console.log("Final payment method selected: " + (document.querySelector('input[name="payment_method"]')?.value || 'Not set'));

}); 