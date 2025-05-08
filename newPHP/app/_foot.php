    </main>
    <footer class="footer">
        <div class="footer-container" style="display: flex; flex-wrap: wrap; align-items: flex-start; gap: 32px;">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Your trusted source for beauty and wellness products.</p>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p>Email: info@beautywellness.com</p>
                <p>Phone: (123) 456-7890</p>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#" class="social-link">Facebook</a>
                    <a href="#" class="social-link">Instagram</a>
                    <a href="#" class="social-link">Twitter</a>
                </div>
            </div>
            <div class="footer-section" style="min-width:200px;max-width:200px;">
                <h3>Our Location</h3>
                <div id="map" style="width: 200px; height: 200px; border-radius: 10px; overflow: hidden;"></div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Beauty & Wellness Shop. All rights reserved.</p>
        </div>
    </footer>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
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
    </script>
</body>
</html>
