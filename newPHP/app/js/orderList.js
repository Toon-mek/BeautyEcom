// Show order details modal
function showOrderDetails(orderId) {
    fetch(`orderList.php?action=get_details&order_id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
            document.getElementById('orderDetailsModal').classList.add('active');
        });
}

// Hide order details modal
function hideOrderDetails() {
    document.getElementById('orderDetailsModal').classList.remove('active');
} 