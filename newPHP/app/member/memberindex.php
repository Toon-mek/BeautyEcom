<?php
session_start();  // Start session
include('../config.php');  // Include database configuration

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, redirect to login page
    header('Location: ../auth/login.php');
    exit();
}

// Fetch the logged-in user's details from the database
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM member WHERE MemberID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <?php include('../_head.php'); ?>
    </header>

    <div class="profile-container">
        <h2>Your Profile</h2>

        <!-- Display Profile Photo -->
        <div class="profile-picture">
            <img src="../uploads/<?= htmlspecialchars($user['ProfilePhoto'] ?: 'default.jpg'); ?>" alt="Profile Picture">
        </div>

        <!-- Profile Picture Upload -->
        <form method="POST" action="upload_photo.php" enctype="multipart/form-data">
            <label for="profile_image">Upload New Photo:</label>
            <input type="file" name="profile_image" id="profile_image" accept="image/*">
            <button type="submit">Upload Photo</button>
        </form>

        <!-- Profile Details Update Form -->
        <form method="POST" action="update_profile.php">
            <label for="name">Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['Name']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['Email']); ?>" required>

            <button type="submit">Update Profile</button>
        </form>

        <!-- Password Change Form -->
        <form method="POST" action="change_password.php">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" required>

            <button type="submit">Change Password</button>
        </form>
    </div>

    <footer>
        <?php include('../_foot.php'); ?>
    </footer>
</body>
</html>
