<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

requireLogin('staff');
// Ensure only managers can access this page
if (!isManager($_SESSION['staff_id'])) {
    header("Location: adminindex.php");
    exit();
}

// Handle Add Staff
if (isset($_POST['add_staff'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO staff (StaffUsername, Password, CreatedAt, FirstTimeLogin) VALUES (?, ?, NOW(), 1)");
    if ($stmt->execute([$username, $password])) {
        header("Location: staffList.php?success=Staff member added successfully. They can now login to complete their profile.");
        exit();
    } else {
        header("Location: staffList.php?error=Failed to add staff member");
        exit();
    }
}

// Allowed sort columns and directions
$allowedSort = ['StaffID', 'StaffName', 'StaffUsername', 'CreatedAt'];
$allowedDir  = ['asc', 'desc'];

$sort         = $_GET['sort'] ?? 'CreatedAt';
$dir          = $_GET['order'] ?? 'desc';
$search       = $_GET['search'] ?? '';
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

// Validate sort and order
if (!in_array($sort, $allowedSort)) {
    $sort = 'CreatedAt';
}

if (!in_array(strtolower($dir), $allowedDir)) {
    $dir = 'desc';
}

// Build WHERE clause
$where        = '';
$params       = [];
$whereClauses = [];

if (!empty($search)) {
    $whereClauses[]          = "(StaffName LIKE :search_name OR StaffUsername LIKE :search_username OR Email LIKE :search_email)";
    $params[':search_name']  = "%$search%";
    $params[':search_username'] = "%$search%";
    $params[':search_email'] = "%$search%";
}

if ($whereClauses) {
    $where = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM staff $where");
$countStmt->execute($params);
$totalStaff = $countStmt->fetchColumn();
$totalPages   = ceil($totalStaff / $perPage);

// Main query with pagination
$query = "SELECT * FROM staff $where ORDER BY $sort $dir LIMIT :limit OFFSET :offset";
$stmt  = $pdo->prepare($query);

// Bind LIMIT and OFFSET separately
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

$stmt->execute();
$staffMembers = $stmt->fetchAll();

// Sorting link helper
function buildSortLink($column, $label)
{
    $currentSort = $_GET['sort'] ?? 'CreatedAt';
    $currentDir  = $_GET['order'] ?? 'desc';
    $nextDir     = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow       = ($currentSort === $column) ? ($currentDir === 'asc' ? '↑' : '↓') : '';
    return "<a href='?sort=$column&order=$nextDir'>" . htmlspecialchars($label) . " $arrow</a>";
}

// Handle Delete Staff
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM staff WHERE StaffID = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: staffList.php?success=Staff member deleted successfully");
    exit();
}

// Handle Edit Staff
if (isset($_POST['edit_staff'])) {
    $id = $_POST['staff_id'];
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $status = $_POST['status'];

    // Get existing profile photo
    $stmt = $pdo->prepare("SELECT StaffProfilePhoto FROM staff WHERE StaffID = ?");
    $stmt->execute([$id]);
    $profilePhoto = $stmt->fetchColumn();

    // Handle new profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/";
        $fileName = uniqid() . "_" . basename($_FILES['profile_photo']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
            $profilePhoto = $fileName;
        }
    }

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE staff SET StaffName = ?, StaffUsername = ?, Email = ?, Contact = ?, Password = ?, StaffProfilePhoto = ?, StaffStatus = ? WHERE StaffID = ?");
        $stmt->execute([$name, $username, $email, $contact, $password, $profilePhoto, $status, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE staff SET StaffName = ?, StaffUsername = ?, Email = ?, Contact = ?, StaffProfilePhoto = ?, StaffStatus = ? WHERE StaffID = ?");
        $stmt->execute([$name, $username, $email, $contact, $profilePhoto, $status, $id]);
    }

    header("Location: staffList.php?success=Staff member updated successfully");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Staff Management</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>

<body>
    <div class="admin-flex-container">
        <?php include 'adminSidebar.php'; ?>
        <div class="admin-main-content">
            <h1>Staff Management</h1>

            <!-- Add Staff Button -->
            <button onclick="showAddForm()" class="crud-btn" style="background:#2ecc71;color:white;margin-bottom:20px;">
                Add New Staff
            </button>

            <form method="GET" style="margin-bottom: 20px;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search name, username or email" class="crud-select">
                <select name="sort" class="crud-select">
                    <option value="CreatedAt" <?php if ($sort === 'CreatedAt') echo 'selected'; ?>>Created At</option>
                    <option value="StaffName" <?php if ($sort === 'StaffName') echo 'selected'; ?>>Name</option>
                    <option value="StaffUsername" <?php if ($sort === 'StaffUsername') echo 'selected'; ?>>Username</option>
                </select>
                <select name="order" class="crud-select">
                    <option value="desc" <?php if ($dir === 'desc') echo 'selected'; ?>>Descending</option>
                    <option value="asc" <?php if ($dir === 'asc') echo 'selected'; ?>>Ascending</option>
                </select>
                <button type="submit" class="crud-btn" style="background:#3498db;color:white;">Apply</button>
            </form>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <table class="member-table">
                <thead>
                    <tr>
                        <th><?php echo buildSortLink('StaffID', 'ID'); ?></th>
                        <th><?php echo buildSortLink('StaffName', 'Name'); ?></th>
                        <th><?php echo buildSortLink('StaffUsername', 'Username'); ?></th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Profile Photo</th>
                        <th>Status</th>
                        <th><?php echo buildSortLink('CreatedAt', 'Joined'); ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffMembers as $staff): ?>
                        <tr>
                            <td><?php echo $staff['StaffID']; ?></td>
                            <td><?php echo !empty($staff['StaffName']) ? htmlspecialchars($staff['StaffName']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($staff['StaffUsername']); ?></td>
                            <td><?php echo !empty($staff['Email']) ? htmlspecialchars($staff['Email']) : '-'; ?></td>
                            <td><?php echo !empty($staff['Contact']) ? htmlspecialchars($staff['Contact']) : '-'; ?></td>
                            <td>
                                <?php if (!empty($staff['StaffProfilePhoto'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($staff['StaffProfilePhoto']); ?>"
                                        alt="Profile" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                <?php else: ?>
                                    <span style="color:#aaa;">No Photo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($staff['StaffStatus'] ?? 'Active'); ?></td>
                            <td><?php echo htmlspecialchars($staff['CreatedAt']); ?></td>
                            <td>
                                <button class="crud-btn edit-btn"
                                    onclick='showEditForm(<?php 
                                        echo json_encode([
                                            "id" => $staff["StaffID"],
                                            "name" => $staff["StaffName"] ?? "",
                                            "username" => $staff["StaffUsername"],
                                            "email" => $staff["Email"] ?? "",
                                            "contact" => $staff["Contact"] ?? "",
                                            "status" => $staff["StaffStatus"] ?? "Active"
                                        ]); 
                                    ?>)'>
                                    Edit
                                </button>
                                <a href="?delete=<?php echo $staff['StaffID']; ?>" class="crud-btn delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this staff member?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?sort=<?php echo $sort; ?>&order=<?php echo $dir; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $p; ?>"
                            class="<?php echo $p == $page ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <button type="button" class="modal-close-btn" onclick="hideAddForm()">&times;</button>
            <form class="crud-form" id="addForm" method="POST">
                <h3>Add New Staff Member</h3>
                <p style="margin-bottom: 15px; color: #666;">Create initial login credentials. Staff will complete their profile on first login.</p>
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit" name="add_staff" class="crud-btn" style="background:#2ecc71;color:white;">Add Staff</button>
                <button type="button" class="crud-btn" onclick="hideAddForm()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <button type="button" class="modal-close-btn" onclick="hideEditForm()">&times;</button>
            <form class="crud-form" id="editForm" method="POST" enctype="multipart/form-data">
                <h3>Edit Staff Member</h3>
                <input type="hidden" name="staff_id" id="edit_staff_id">
                <label>Name</label>
                <input type="text" name="name" id="edit_name" required>
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required>
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
                <label>Contact</label>
                <input type="text" name="contact" id="edit_contact" required>
                <label>Status</label>
                <select name="status" id="edit_status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                <label>Password (leave blank to keep current)</label>
                <input type="password" name="password">
                <label>Profile Photo</label>
                <input type="file" name="profile_photo" accept="image/*">
                <button type="submit" name="edit_staff" class="crud-btn edit-btn">Update</button>
                <button type="button" class="crud-btn" onclick="hideEditForm()">Cancel</button>
            </form>
        </div>
    </div>

    <script src="../js/staffList.js"></script>
</body>

</html>