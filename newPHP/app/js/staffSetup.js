function formValidation(formType = 'staff') {
    const name = document.getElementById("name")?.value.trim();
    const email = document.getElementById("email")?.value.trim();
    const phone = document.getElementById("phone")?.value.trim();
    const password = document.getElementById("password")?.value;
    const confirmPassword = document.getElementById("confirm_password")?.value;

    if (!name || !email || !phone || !password || !confirmPassword) {
        alert("All fields must be filled.");
        return false;
    }

    const nameRegex = /^[a-zA-Z\s]+$/;
    if (!nameRegex.test(name)) {
        alert("Name must contain only letters and spaces.");
        return false;
    }

    const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    if (!gmailRegex.test(email)) {
        alert("Email must be a valid Gmail address.");
        return false;
    }

    const phoneRegex = /^01\d-\d{7,8}$/;
    if (!phoneRegex.test(phone)) {
        alert("Phone number must follow format: 011-12345678");
        return false;
    }

    const passwordRegex = /^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[!@#$]).{8,}$/;
    if (!passwordRegex.test(password)) {
        alert("Password must be at least 8 characters and include numbers, letters, and one of !@#$.");
        return false;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    return true;
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Password validation
document.getElementById('editForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const errorDiv = document.getElementById('password_error');

    if (newPassword && newPassword !== confirmPassword) {
        e.preventDefault();
        errorDiv.style.display = 'block';
    } else {
        errorDiv.style.display = 'none';
    }
});
