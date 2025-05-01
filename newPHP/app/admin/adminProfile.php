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
    echo "<script>window.onload = () => alert('Profile updated successfully!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile</title>
    <link href="../css/admin.css" rel="stylesheet">
    <style>
        .profile-form {
            background: white;
            padding: 30px;
            max-width: 500px;
            margin: 20px auto;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .profile-form img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }
        .profile-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-form label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        .profile-form input[type="text"],
        .profile-form input[type="password"],
        .profile-form input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .profile-form button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            border: none;
            background: #f1c40f;
            color: black;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .profile-form button:hover {
            background: #f39c12;
        }
        .logout-btn {
    display: block;
    text-align: center;
    margin-top: 15px;
    padding: 10px;
    background: #e74c3c;
    color: white;
    font-weight: bold;
    border-radius: 6px;
    text-decoration: none;
}
.logout-btn:hover {
    background: #c0392b;
}
    input#edit_name_field {
        width: 100%;
        height: 38px;
        padding-left: 12px;
        font-size: 14px;
        border-radius: 6px;
    }

    .tiny-btn {
        align-self: flex-start;
        padding: 3px 10px;
        font-size: 12px;
        background-color: #f1c40f;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .tiny-btn:hover {
        background-color: #e0b90a;
    }
</style>

    </style>
</head>
<body>
<div class="admin-flex-container">
    <?php include 'adminSidebar.php'; ?>
    <div class="admin-main-content">
        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <h2>Profile Management</h2>

            <div style="text-align:center;">
                <img src="../uploads/<?php echo htmlspecialchars($user[$photoField] ?? 'default-profile.png'); ?>" alt="Profile Photo">
            </div>

            <label for="edit_name_field"><strong><?php echo $isManager ? 'Manager' : 'Staff' ?> Name</strong></label>
            <div class="input-edit-wrapper" style="display: flex; flex-direction: column; gap: 5px;">
                <input type="text" name="name" id="edit_name_field"
                    value="<?php echo htmlspecialchars($user[$nameField]); ?>" readonly>
                <button type="button" id="editToggleBtn" onclick="toggleEdit()" class="tiny-btn">Edit</button>
            </div>

            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password">

            <label>Change Profile Photo</label>
            <input type="file" name="photo" accept="image/*">

            <button type="submit" name="update_profile">Update Profile</button>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </form>
    </div>
</div>
<script>
function toggleEdit() {
    const input = document.getElementById("edit_name_field");
    const btn = document.getElementById("editToggleBtn");

    if (input.hasAttribute("readonly")) {
        input.removeAttribute("readonly");
        input.focus();
        btn.textContent = "Cancel";
    } else {
        input.setAttribute("readonly", true);
        btn.textContent = "Edit";
    }
}
</script>
</body>
</html>
