<?php
require_once __DIR__ . '/../_base.php';
requireLogin('staff');

$staff_id = $_SESSION['staff_id'] ?? null;
$isManager = isManager($staff_id);

// Check if user is manager or staff and get appropriate data
if ($isManager) {
    $stmt = $pdo->prepare("SELECT * FROM manager WHERE ManagerUsername = ?");
    $stmt->execute([$staff_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $nameField = 'ManagerName';
    $photoField = 'ManagerProfilePhoto';
    $table = 'manager';
    $usernameField = 'ManagerUsername';
} else {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE StaffUsername = ?");
    $stmt->execute([$staff_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $nameField = 'StaffName';
    $photoField = 'StaffProfilePhoto';
    $table = 'staff';
    $usernameField = 'StaffUsername';
}

if (!$user) {
    echo "<script>alert('User not found');</script>";
    exit;
}

if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $password = $_POST['password'];
    $photoName = $user[$photoField] ?? null;

    if (!empty($_FILES['photo']['name'])) {
        $photoName = uniqid() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/" . $photoName);
    }

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE $table SET $nameField=?, $photoField=?, Password=? WHERE $usernameField=?");
        $stmt->execute([$name, $photoName, $hashed, $staff_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE $table SET $nameField=?, $photoField=? WHERE $usernameField=?");
        $stmt->execute([$name, $photoName, $staff_id]);
    }

    $_SESSION['staff_name'] = $name;
    $_SESSION['success_message'] = 'Profile updated successfully!';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get success message if exists
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-flex-container">
    <?php include 'adminSidebar.php'; ?>
    <div class="admin-main-content">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-photo-section">
                    <img src="../uploads/<?php echo htmlspecialchars($user[$photoField] ?? 'defaultprofilephoto.jpg'); ?>" 
                         alt="Profile Photo" 
                         class="profile-photo" 
                         id="profilePhotoPreview">
                    <label for="photoInput" class="photo-upload-label">
                        <span class="camera-icon"></span>
                    </label>
                </div>
            </div>

            <div id="successMessage" class="success-message"<?php if ($success_message): ?> style="display: block;"<?php endif; ?>>
                <?php echo htmlspecialchars($success_message); ?>
            </div>

            <form method="POST" enctype="multipart/form-data" class="profile-form" id="profileForm">
                <input type="file" name="photo" id="photoInput" class="photo-upload-input" accept="image/*">
                
                <div class="form-group">
                    <label for="edit_name_field"><?php echo $isManager ? 'Manager' : 'Staff' ?> Name</label>
                    <div class="input-group">
                        <input type="text" 
                               name="name" 
                               id="edit_name_field" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($user[$nameField]); ?>" 
                               readonly>
                        <button type="button" onclick="toggleEdit()" id="editToggleBtn" class="btn btn-edit">
                            <span class="icon icon-pencil"></span> Edit
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-control" 
                           placeholder="Leave blank to keep current password">
                </div>

                <div class="button-container">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <span class="icon icon-save"></span> Save Changes
                    </button>
                    
                    <a href="../auth/logout.php" class="btn btn-logout">
                        <span class="icon icon-logout"></span> Logout
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../js/adminProfile.js"></script>
</body>
</html>
