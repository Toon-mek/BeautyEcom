function validateResetForm() {
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm").value;

    const passwordRegex = /^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[!@#$]).{8,}$/;

    if (!passwordRegex.test(password)) {
        alert("Password must be at least 8 characters and include numbers, letters, and one of !@#$.");
        return false;
    }

    if (password !== confirm) {
        alert("Passwords do not match.");
        return false;
    }

    return true;
}
