// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (togglePassword && passwordInput && toggleIcon) {
        togglePassword.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        });
    }

    // Forgot password message
    function showForgotMessage() {
        const alertBox = document.querySelector(".alert-box.alert-error");
        if (alertBox) {
            alertBox.textContent = "Please contact admin to reset your password.";
            alertBox.style.display = "block";
        } else {
            const div = document.createElement("div");
            div.className = "alert-box alert-error";
            div.textContent = "Please contact admin to reset your password.";
            document.querySelector(".form-container").insertBefore(div, document.querySelector("form"));
        }
    }

    // Make showForgotMessage available globally
    window.showForgotMessage = showForgotMessage;
});