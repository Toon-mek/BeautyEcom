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

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        icon.textContent = '👁️';
    }
});