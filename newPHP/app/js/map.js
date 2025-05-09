document.addEventListener("DOMContentLoaded", function() {
    var myLatLng = [3.1573, 101.7122]; // Kuala Lumpur example
    var map = L.map('map').setView(myLatLng, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    L.marker(myLatLng).addTo(map)
        .bindPopup('Beauty & Wellness Location')
        .openPopup();
}); 