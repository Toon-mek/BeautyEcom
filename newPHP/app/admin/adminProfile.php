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
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%);
            padding: 40px 20px;
            border-radius: 15px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-photo-section {
            position: relative;
            margin-bottom: -60px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            object-fit: cover;
            margin: 0 auto;
            display: block;
            background-color: white;
        }

        .photo-upload-label {
            position: absolute;
            bottom: 0;
            right: 50%;
            transform: translateX(80px);
            background: #f1c40f;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .photo-upload-label:hover {
            background: #f39c12;
        }

        /* CSS Camera Icon */
        .camera-icon {
            width: 18px;
            height: 12px;
            border: 2px solid #2c3e50;
            border-radius: 3px;
            position: relative;
        }

        .camera-icon::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            border: 2px solid #2c3e50;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .camera-icon::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 3px;
            background: #2c3e50;
            border-radius: 3px;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
        }

        .photo-upload-input {
            display: none;
        }

        .profile-form {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #f1c40f;
            outline: none;
            box-shadow: 0 0 0 3px rgba(241, 196, 15, 0.1);
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            gap: 8px;
            width: 100%;
            height: 45px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .btn-primary {
            background: #f1c40f;
            color: #2c3e50;
        }

        .btn-primary:hover {
            background: #f39c12;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #e5e5e5;
            color: #2c3e50;
            padding: 8px 15px;
            font-size: 13px;
            margin-left: 10px;
        }

        .btn-edit:hover {
            background: #d5d5d5;
        }

        .btn-logout {
            background: #e74c3c;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-logout:hover {
            background: #c0392b;
        }

        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn-edit {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-left: 0;
        }

        .success-message {
            background-color: #2ecc71;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }

        /* CSS Icons */
        .icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            position: relative;
        }

        .icon-pencil::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 12px;
            background: currentColor;
            transform: rotate(-45deg);
            left: 7px;
            top: 0;
        }

        .icon-pencil::after {
            content: '';
            position: absolute;
            border-style: solid;
            border-width: 3px;
            border-color: transparent transparent currentColor currentColor;
            transform: rotate(-45deg);
            left: 5px;
            top: -2px;
        }

        .icon-save {
            border: 2px solid currentColor;
            border-radius: 2px;
        }

        .icon-save::before {
            content: '';
            position: absolute;
            width: 6px;
            height: 2px;
            background: currentColor;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .icon-save::after {
            content: '';
            position: absolute;
            width: 2px;
            height: 6px;
            background: currentColor;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .icon-logout::before {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            border: 2px solid currentColor;
            border-radius: 50%;
            left: 0;
            top: 2px;
        }

        .icon-logout::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 2px;
            background: currentColor;
            left: 8px;
            top: 7px;
        }

        .button-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 25px;
        }

        .button-container button,
        .button-container a {
            margin: 0;
            text-align: center;
            line-height: 21px;
        }
    </style>
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

<script>
document.getElementById('photoInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePhotoPreview').src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

function toggleEdit() {
    const input = document.getElementById("edit_name_field");
    const btn = document.getElementById("editToggleBtn");
    const isEditing = input.hasAttribute("readonly");

    if (isEditing) {
        // Enable editing
        input.removeAttribute("readonly");
        input.focus();
        input.style.backgroundColor = "#fff";
        btn.innerHTML = '<span class="icon icon-pencil"></span> Cancel';
    } else {
        // Disable editing
        input.setAttribute("readonly", "readonly");
        input.style.backgroundColor = "#f8f9fa";
        btn.innerHTML = '<span class="icon icon-pencil"></span> Edit';
    }
}

// Auto-hide success message after 3 seconds
if (document.getElementById('successMessage').textContent) {
    setTimeout(() => {
        document.getElementById('successMessage').style.display = 'none';
    }, 3000);
}
</script>
</body>
</html>
