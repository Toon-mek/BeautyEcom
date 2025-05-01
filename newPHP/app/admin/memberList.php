<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

// Ensure staff is logged in
requireLogin('staff');

// Handle Delete Member
handleDeleteMember($pdo);

// Handle Edit Member
handleEditMember($pdo);

// Fetch all members
$members = fetchAllMembers($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Management</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-flex-container">
    <?php include 'adminSidebar.php'; ?>
    <div class="admin-main-content">
        <h1>Member Management</h1>
        <!-- Member Table -->
        <table class="member-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Profile Photo</th>
                    <th>Gender</th>
                    <th>Date of Birth</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td><?php echo $member['MemberID']; ?></td>
                    <td><?php echo htmlspecialchars($member['Name']); ?></td>
                    <td><?php echo htmlspecialchars($member['Email']); ?></td>
                    <td><?php echo htmlspecialchars($member['PhoneNumber']); ?></td>
                    <td>
                        <?php if (!empty($member['ProfilePhoto'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($member['ProfilePhoto']); ?>" alt="Profile Photo" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <span style="color:#aaa;">No Photo</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($member['Gender']); ?></td>
                    <td><?php echo htmlspecialchars($member['DateOfBirth']); ?></td>
                    <td><?php echo htmlspecialchars($member['CreatedAt']); ?></td>
                    <td>
                        <!-- Edit Form Trigger -->
                        <button class="crud-btn edit-btn"
                            onclick="showEditForm(
                                <?php echo $member['MemberID']; ?>,
                                '<?php echo htmlspecialchars(addslashes($member['Name'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($member['Email'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($member['PhoneNumber'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($member['ProfilePhoto'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($member['Gender'])); ?>',
                                '<?php echo htmlspecialchars($member['DateOfBirth']); ?>'
                            )">Edit</button>
                        <a href="?delete=<?php echo $member['MemberID']; ?>" class="crud-btn delete-btn" onclick="return confirm('Are you sure you want to delete this member?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <button type="button" class="modal-close-btn" onclick="hideEditForm()">&times;</button>
        <form class="crud-form" id="editForm" method="POST" enctype="multipart/form-data" style="margin-bottom:0;box-shadow:none;">
            <h3>Edit Member</h3>
            <input type="hidden" name="member_id" id="edit_member_id">
            <label>Name</label>
            <input type="text" name="name" id="edit_name" required>
            <label>Email</label>
            <input type="email" name="email" id="edit_email" required>
            <label>Phone</label>
            <input type="text" name="phone" id="edit_phone" required>
            <label>Profile Photo (leave blank to keep current)</label>
            <input type="file" name="profile_photo" id="edit_profile_photo" accept="image/*">
            <label>Gender</label>
            <select name="gender" id="edit_gender" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
            <label>Date of Birth</label>
            <input type="date" name="dob" id="edit_dob" required>
            <label>Password (leave blank to keep current)</label>
            <input type="password" name="password">
            <button type="submit" name="edit_member" class="crud-btn edit-btn">Update Member</button>
            <button type="button" class="crud-btn" onclick="hideEditForm()">Cancel</button>
        </form>
    </div>
</div>
<script src="../js/memberList.js"></script>
</body>
</html>