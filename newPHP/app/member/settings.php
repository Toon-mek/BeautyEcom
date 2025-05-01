<?php
require_once __DIR__ . '/../_base.php';
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_photo'])) {
        $result = updateMemberPhoto($_SESSION['member_id'], $_FILES['profile_photo']);
        if (isset($result['success'])) $success = $result['success'];
        if (isset($result['error'])) $error = $result['error'];
    }
    
    if (isset($_POST['update_profile'])) {
        $result = updateMemberDetails($_SESSION['member_id'], $_POST);
        if (isset($result['success'])) $success = $result['success'];
        if (isset($result['error'])) $error = $result['error'];
    }
    
    if (isset($_POST['update_address'])) {
        $result = updateMemberAddress($_SESSION['member_id'], $_POST['address']);
        if (isset($result['success'])) $success = $result['success'];
        if (isset($result['error'])) $error = $result['error'];
    }
    
    if (isset($_POST['change_password'])) {
        $result = updateMemberPassword(
            $_SESSION['member_id'],
            $_POST['current_password'],
            $_POST['new_password'],
            $_POST['confirm_password']
        );
        if (isset($result['success'])) $success = $result['success'];
        if (isset($result['error'])) $error = $result['error'];
    }
}

// Get current member details
$member = getMemberDetails($_SESSION['member_id']);
if (!$member) {
    header("Location: /newPHP/app/auth/logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Settings - Beauty & Wellness Shop</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../_head.php'; ?>
    <main class="main-content">
        <div class="settings-container">
            <h1 class="settings-title">Account Settings</h1>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Profile Photo Section -->
            <div class="settings-section">
                <h2 class="settings-title">Profile Photo</h2>
                <div class="profile-photo-container">
                    <img src="/newPHP/app/uploads/<?php echo htmlspecialchars($member['ProfilePhoto'] ?: 'default-profile.png'); ?>" 
                         alt="Current Profile Photo" 
                         class="current-photo">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Upload New Photo</label>
                            <input type="file" name="profile_photo" accept="image/*" class="form-input">
                        </div>
                        <button type="submit" name="update_photo" class="btn">Update Photo</button>
                    </form>
                </div>
            </div>

            <!-- Personal Information Section -->
            <div class="settings-section">
                <h2 class="settings-title">Personal Information</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($member['Name']); ?>" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($member['Email']); ?>" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($member['PhoneNumber']); ?>" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-input" required>
                            <option value="Male" <?php echo $member['Gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $member['Gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $member['Gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($member['DateOfBirth']); ?>" class="form-input" required>
                    </div>

                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>

            <!-- Default Address Section -->
            <div class="settings-section">
                <h2 class="settings-title">Default Address</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-input" rows="4" required><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_address" class="btn">Update Address</button>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="settings-section">
                <h2 class="settings-title">Change Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>

                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html> 