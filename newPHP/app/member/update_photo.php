<?php
session_start();  // Start session
include('../config.php');  // Include database configuration

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');  // Redirect to login if not logged in
    exit;
}

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $user_id = $_SESSION['user_id'];
    $photo = $_FILES['profile_image'];

    // Validate and upload the profile picture
    $target_dir = "../uploads/";
    $target_file = $target_dir . uniqid() . '-' . basename($photo['name']);
    $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate file type (JPEG, PNG, GIF)
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($image_type, $allowed_types)) {
        echo "Only JPG, JPEG, PNG & GIF files are allowed.";
        exit;
    }

    // Move the uploaded file to the server
    if (move_uploaded_file($photo['tmp_name'], $target_file)) {
        // Update the user's profile photo in the database
        $stmt = $pdo->prepare("UPDATE member SET ProfilePhoto = ? WHERE MemberID = ?");
        $stmt->execute([$target_file, $user_id]);

        // Update the session with the new profile photo path
        $_SESSION['profile_picture'] = $target_file;

        // Redirect back to the profile page
        header('Location: memberindex.php');
        exit();
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>
