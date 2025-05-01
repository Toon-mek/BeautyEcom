function validateForm() {
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const phone = document.getElementById("phone").value.trim();
    const gender = document.getElementById("gender").value;
    const dob = document.getElementById("dob").value;

    // Name required
    const nameRegex = /^[a-zA-Z\s]+$/;
if (!nameRegex.test(name)) {
    alert("Name must contain only letters and spaces.");
    return false;
}
    // Gmail validation
    const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    if (!gmailRegex.test(email)) {
        alert("Email must be a valid Gmail address.");
        return false;
    }

    // Password validation
    const passwordRegex = /^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[!@#$]).{8,}$/;
    if (!passwordRegex.test(password)) {
        alert("Password must be at least 8 characters and include numbers, letters, and one of !@#$.");
        return false;
    }

    // Phone format: 01X-XXXXXXX or 01X-XXXXXXXX
    const phoneRegex = /^01\d-\d{7,8}$/;
    if (!phoneRegex.test(phone)) {
        alert("Phone number must follow format: 011-12345678");
        return false;
    }

    // Gender
    if (!gender) {
        alert("Please select a gender.");
        return false;
    }

    // Age check: at least 18 years old
    const birthDate = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    if (age < 18) {
        alert("You must be at least 18 years old.");
        return false;
    }
    return true;
}

// Set max date for DOB to be 18 years ago
document.addEventListener("DOMContentLoaded", () => {
    const dobInput = document.getElementById("dob");
    const today = new Date();
    today.setFullYear(today.getFullYear() - 18);
    dobInput.max = today.toISOString().split('T')[0];
});
