<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';

requireLogin('staff');
handleDeleteMember();
handleEditMember();

// Allowed sort columns and directions
$allowedSort = ['MemberID', 'Name', 'Email', 'PhoneNumber', 'MembershipStatus', 'CreatedAt'];
$allowedDir  = ['asc', 'desc'];

$sort         = $_GET['sort'] ?? 'MemberID';
$dir          = $_GET['order'] ?? 'asc';
$search       = $_GET['search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

// Validate sort and order
if (! in_array($sort, $allowedSort)) {
    $sort = 'MemberID';
}

if (! in_array(strtolower($dir), $allowedDir)) {
    $dir = 'asc';
}

// Build WHERE clause
$where        = '';
$params       = [];
$whereClauses = [];

if (! empty($search)) {
    $whereClauses[]          = "(Name LIKE :search_name OR Email LIKE :search_email)";
    $params[':search_name']  = "%$search%";
    $params[':search_email'] = "%$search%";
}

if (! empty($statusFilter)) {
    $whereClauses[]    = "MembershipStatus = :status";
    $params[':status'] = $statusFilter;
}
if ($whereClauses) {
    $where = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM member $where");
$countStmt->execute($params);
$totalMembers = $countStmt->fetchColumn();
$totalPages   = ceil($totalMembers / $perPage);

// Main query with pagination
$query = "SELECT * FROM member $where ORDER BY $sort $dir LIMIT :limit OFFSET :offset";
$stmt  = $pdo->prepare($query);

// Bind LIMIT and OFFSET separately
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

$stmt->execute();
$members = $stmt->fetchAll();

// Sorting link helper
function buildSortLink($column, $label)
{
    $currentSort = $_GET['sort'] ?? 'MemberID';
    $currentDir  = $_GET['order'] ?? 'asc';
    $nextDir     = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow       = ($currentSort === $column) ? ($currentDir === 'asc' ? '↑' : '↓') : '';
    return "<a href='?sort=$column&order=$nextDir'>" . htmlspecialchars($label) . " $arrow</a>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Member Management</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>

<body>
    <div class="admin-flex-container">
        <?php include 'adminSidebar.php'; ?>
        <div class="admin-main-content">
            <h1>Member Management</h1>

            <form method="GET" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div class="filter-group" style="display: flex; flex-direction: column;">
                    <label for="search" style="margin-bottom: 5px;">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search members..." class="crud-select">
                </div>
                <div class="filter-group" style="display: flex; flex-direction: column;">
                    <label for="status_filter" style="margin-bottom: 5px;">Status</label>
                    <select name="status_filter" id="status_filter" class="crud-select">
                        <option value="">All Status</option>
                        <option value="Active" <?php if ($statusFilter === 'Active') { echo 'selected'; } ?>>Active</option>
                        <option value="Blocked" <?php if ($statusFilter === 'Blocked') { echo 'selected'; } ?>>Blocked</option>
                    </select>
                </div>
                <div class="filter-group" style="display: flex; flex-direction: column;">
                    <label for="sort" style="margin-bottom: 5px;">Sort By</label>
                    <select name="sort" id="sort" class="crud-select" onchange="this.form.submit()">
                        <option value="MemberID" <?php echo ($sort === 'MemberID') ? 'selected' : ''; ?>>ID</option>
                        <option value="Name" <?php echo ($sort === 'Name') ? 'selected' : ''; ?>>Name</option>
                        <option value="Email" <?php echo ($sort === 'Email') ? 'selected' : ''; ?>>Email</option>
                        <option value="PhoneNumber" <?php echo ($sort === 'PhoneNumber') ? 'selected' : ''; ?>>Phone</option>
                        <option value="MembershipStatus" <?php echo ($sort === 'MembershipStatus') ? 'selected' : ''; ?>>Status</option>
                        <option value="CreatedAt" <?php echo ($sort === 'CreatedAt') ? 'selected' : ''; ?>>Joined</option>
                    </select>
                </div>
                <div class="order-filter-group">
                    <label for="order">Order</label>
                    <select name="order" id="order" class="crud-select" onchange="this.form.submit()">
                        <option value="asc" <?php echo ($dir === 'asc') ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?php echo ($dir === 'desc') ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
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
                        <th><?php echo buildSortLink('MemberID', 'ID'); ?></th>
                        <th><?php echo buildSortLink('Name', 'Name'); ?></th>
                        <th><?php echo buildSortLink('Email', 'Email'); ?></th>
                        <th>Phone</th>
                        <th>Profile Photo</th>
                        <th>Status</th>
                        <th><?php echo buildSortLink('CreatedAt', 'Joined'); ?></th>
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
                                <?php if (! empty($member['ProfilePhoto'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($member['ProfilePhoto']); ?>" alt="Photo" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                <?php else: ?>
                                    <span style="color:#aaa;">No Photo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($member['MembershipStatus']); ?></td>
                            <td><?php echo htmlspecialchars($member['CreatedAt']); ?></td>
                            <td>
                                <button class="crud-btn edit-btn"
                                    onclick='showEditForm(
        <?php echo (int) $member["MemberID"]; ?>,
        <?php echo json_encode($member["Name"]); ?>,
        <?php echo json_encode($member["Email"]); ?>,
        <?php echo json_encode($member["PhoneNumber"]); ?>,
        <?php echo json_encode($member["ProfilePhoto"]); ?>,
        <?php echo json_encode($member["MembershipStatus"]); ?>
    )'>
                                    Edit
                                </button>

                                <a href="?delete=<?php echo $member['MemberID']; ?>" class="crud-btn delete-btn" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?sort=<?php echo $sort; ?>&order=<?php echo $dir; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($statusFilter); ?>&page=<?php echo $p; ?>"
                            class="<?php echo $p == $page ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <button type="button" class="modal-close-btn" onclick="hideEditForm()">&times;</button>
            <form class="crud-form" id="editForm" method="POST" enctype="multipart/form-data">
                <h3>Edit Member</h3>
                <input type="hidden" name="member_id" id="edit_member_id">
                <label>Name</label>
                <input type="text" name="name" id="edit_name" required>
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
                <label>Phone</label>
                <input type="tel" name="phone" id="edit_phone" required>
                <label>Profile Photo</label>
                <input type="file" name="profile_photo" id="edit_profile_photo" accept="image/*">
                <label>Status</label>
                <select name="status" id="edit_status" required>
                    <option value="Active">Active</option>
                    <option value="Blocked">Blocked</option>
                </select>
                <label>Password (leave blank to keep current)</label>
                <input type="password" name="password">
                <button type="submit" name="edit_member" class="crud-btn edit-btn">Update</button>
                <button type="button" class="crud-btn" onclick="hideEditForm()">Cancel</button>
            </form>
        </div>
    </div>

    <script src="../js/memberList.js"></script>
</body>

</html>