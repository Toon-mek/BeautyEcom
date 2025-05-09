<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: staffLogin.php");
    exit();
}

// Get staff information
$stmt = $pdo->prepare("SELECT * FROM staff WHERE StaffUsername = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();

// If not first time login or profile already completed, redirect to admin panel
if (!$staff['FirstTimeLogin']) {
    header("Location: ../auth/staffLogin.php");
    exit();
}

$error = null;
$success = null;

// Handle profile setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $profilePhoto = null;

    // Validate passwords match if new password is provided
    if (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = "Passwords do not match!";
    } else {
        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "../uploads/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = uniqid() . "_" . basename($_FILES['profile_photo']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
                $profilePhoto = $fileName;
            }
        }

        try {
            $pdo->beginTransaction();

            // Update staff information
            $updateFields = [
                'StaffName' => $name,
                'Email' => $email,
                'Contact' => $contact,
                'FirstTimeLogin' => 0
            ];

            if ($profilePhoto) {
                $updateFields['StaffProfilePhoto'] = $profilePhoto;
            }

            if (!empty($newPassword)) {
                $updateFields['Password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $sql = "UPDATE staff SET " .
                implode(" = ?, ", array_keys($updateFields)) . " = ? " .
                "WHERE StaffUsername = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($updateFields), $_SESSION['staff_id']]);

            $pdo->commit();

            // Redirect to admin panel
            header("Location: ../admin/adminindex.php?success=Profile setup completed");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred while setting up your profile. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>

<body>
    <div class="setup-container">
        <div class="welcome-message">
            <h1>Welcome to the Team!</h1>
            <p>Please complete your profile to continue</p>
        </div>

        <?php if (isset($_GET['setup']) && $_GET['setup'] == 1): ?>
            <div class="alert-box alert-success">🎉 Profile setup completed. Please login.</div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="crud-form" onsubmit="return formValidation('staff')">
            <div style="text-align: center; margin-bottom: 30px;">
                <img id="photoPreview" src="../uploads/default-avatar.png" class="preview-image" alt="">
            </div>
            <label>Profile Photo</label>
            <input type="file" name="profile_photo" accept="image/*" onchange="previewImage(this)">
            <label>Full Name</label>
            <input type="text" name="name" id="name" required>
            <label>Email</label>
            <input type="email" name="email" id="email" required>
            <label>Contact Number</label>
            <input type="text" name="contact" id="phone" required>
            <label>Password</label>
            <input type="password" name="password" id="password">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password">
            <div id="password_error" style="color: red; display: none;">Passwords do not match!</div>
            <button type="submit" class="crud-btn" style="background:#2ecc71;color:white;width:100%;">
                Complete Profile Setup
            </button>
        </form>
    </div>
    <script src="../js/staffSetup.js"></script>
</body>

</html>