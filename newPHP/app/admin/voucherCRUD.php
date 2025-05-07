<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../_base.php';
requireLogin('staff');

// Only managers can access
if (!isManager($_SESSION['staff_id'])) {
    header('Location: adminindex.php');
    exit;
}

// Allowed sort columns and directions
$allowedSort = ['VoucherID', 'Code', 'Discount', 'ExpiryDate', 'Status', 'CreatedAt', 'UpdatedAt'];
$allowedDir = ['asc', 'desc'];

$sort = $_GET['sort'] ?? 'VoucherID';
$dir = $_GET['order'] ?? 'desc';
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';

// Validate sort and order
if (!in_array($sort, $allowedSort)) {
    $sort = 'VoucherID';
}
if (!in_array(strtolower($dir), $allowedDir)) {
    $dir = 'desc';
}

// Filter vouchers
$vouchers = array_filter(fetchAllVouchers($sort, $dir), function($voucher) use ($search, $statusFilter) {
    $match = true;
    if ($search) {
        $match = stripos($voucher['Code'], $search) !== false || stripos($voucher['Description'], $search) !== false;
    }
    if ($match && $statusFilter) {
        $match = $voucher['Status'] === $statusFilter;
    }
    return $match;
});

$editVoucher = null;
if (isset($_GET['edit'])) {
    $editVoucher = getVoucherById($_GET['edit']);
}

// Handle Add Voucher
if (isset($_POST['add_voucher'])) {
    $data = [
        'code' => $_POST['code'],
        'discount' => $_POST['discount'],
        'expiry_date' => $_POST['expiry_date'],
        'description' => $_POST['description'],
        'status' => $_POST['status'] ?? 'Active',
    ];
    addVoucher($data);
    header('Location: voucherCRUD.php');
    exit;
}

// Handle Edit Voucher
if (isset($_POST['edit_voucher'])) {
    $id = $_POST['voucher_id'];
    $data = [
        'code' => $_POST['code'],
        'discount' => $_POST['discount'],
        'expiry_date' => $_POST['expiry_date'],
        'description' => $_POST['description'],
        'status' => $_POST['status'],
    ];
    editVoucher($id, $data);
    header('Location: voucherCRUD.php');
    exit;
}

// Handle Delete Voucher
if (isset($_GET['delete'])) {
    deleteVoucher($_GET['delete']);
    header('Location: voucherCRUD.php');
    exit;
}

function buildSortLink($column, $label) {
    $currentSort = $_GET['sort'] ?? 'VoucherID';
    $currentDir = $_GET['order'] ?? 'desc';
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $column) ? ($currentDir === 'asc' ? '↑' : '↓') : '';
    return "<a href='?sort=$column&order=$nextDir'>" . htmlspecialchars($label) . " $arrow</a>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher Management</title>
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-flex-container">
    <?php include 'adminSidebar.php'; ?>
    <div class="admin-main-content">
        <h1>Voucher Management</h1>
        <form method="GET" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;" id="filterForm">
            <div class="filter-group" style="display: flex; flex-direction: column;">
                <label for="search" style="margin-bottom: 5px;">Search</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search vouchers..." class="crud-select" onchange="this.form.submit()">
            </div>
            <div class="filter-group" style="display: flex; flex-direction: column;">
                <label for="status_filter" style="margin-bottom: 5px;">Status</label>
                <select name="status_filter" id="status_filter" class="crud-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="filter-group" style="display: flex; flex-direction: column;">
                <label for="sort" style="margin-bottom: 5px;">Sort By</label>
                <select name="sort" id="sort" class="crud-select" onchange="this.form.submit()">
                    <option value="VoucherID" <?= ($sort === 'VoucherID') ? 'selected' : '' ?>>ID</option>
                    <option value="Code" <?= ($sort === 'Code') ? 'selected' : '' ?>>Code</option>
                    <option value="Discount" <?= ($sort === 'Discount') ? 'selected' : '' ?>>Discount</option>
                    <option value="ExpiryDate" <?= ($sort === 'ExpiryDate') ? 'selected' : '' ?>>Expiry Date</option>
                    <option value="Status" <?= ($sort === 'Status') ? 'selected' : '' ?>>Status</option>
                    <option value="CreatedAt" <?= ($sort === 'CreatedAt') ? 'selected' : '' ?>>Created At</option>
                    <option value="UpdatedAt" <?= ($sort === 'UpdatedAt') ? 'selected' : '' ?>>Updated At</option>
                </select>
            </div>
            <div class="filter-group" style="display: flex; flex-direction: column;">
                <label for="order" style="margin-bottom: 5px;">Order</label>
                <select name="order" id="order" class="crud-select" onchange="this.form.submit()">
                    <option value="desc" <?= ($dir === 'desc') ? 'selected' : '' ?>>Descending</option>
                    <option value="asc" <?= ($dir === 'asc') ? 'selected' : '' ?>>Ascending</option>
                </select>
            </div>
        </form>
        <button class="crud-btn add-btn" onclick="showAddVoucherForm()" style="margin-bottom:18px;background:#27ae60;color:#fff;">Add Voucher</button>
        <table class="product-table">
            <thead>
                <tr>
                    <th><?= buildSortLink('VoucherID', 'ID') ?></th>
                    <th><?= buildSortLink('Code', 'Code') ?></th>
                    <th style="width: 80px; text-align: center;"><?= buildSortLink('Discount', 'Discount (%)') ?></th>
                    <th style="width: 120px; text-align: center;"><?= buildSortLink('ExpiryDate', 'Expiry Date') ?></th>
                    <th>Description</th>
                    <th><?= buildSortLink('Status', 'Status') ?></th>
                    <th><?= buildSortLink('CreatedAt', 'Created At') ?></th>
                    <th><?= buildSortLink('UpdatedAt', 'Updated At') ?></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><?= htmlspecialchars($voucher['VoucherID']) ?></td>
                    <td><?= htmlspecialchars($voucher['Code']) ?></td>
                    <td style="width: 80px; text-align: center;"><?= htmlspecialchars($voucher['Discount']) ?></td>
                    <td style="width: 120px; text-align: center;"><?= htmlspecialchars($voucher['ExpiryDate']) ?></td>
                    <td><span class="truncate-description" title="<?= htmlspecialchars($voucher['Description']) ?>"><?= htmlspecialchars($voucher['Description']) ?></span></td>
                    <td><?= htmlspecialchars($voucher['Status']) ?></td>
                    <td><?= htmlspecialchars($voucher['CreatedAt']) ?></td>
                    <td><?= htmlspecialchars($voucher['UpdatedAt']) ?></td>
                    <td>
                        <a href="?edit=<?= $voucher['VoucherID'] ?>" class="crud-btn edit-btn">Edit</a>
                        <a href="?delete=<?= $voucher['VoucherID'] ?>" class="crud-btn delete-btn" onclick="return confirm('Delete this voucher?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Voucher Modal -->
<div class="modal-overlay" id="addVoucherModal">
    <div class="modal-content">
        <button type="button" class="modal-close-btn" onclick="hideAddVoucherForm()">&times;</button>
        <form method="post" class="crud-form" id="addVoucherForm" onsubmit="return validateVoucherForm()">
            <h3>Add Voucher</h3>
            <input type="text" name="code" placeholder="Voucher Code" required class="crud-select">
            <input type="number" name="discount" min="1" max="100" step="1" placeholder="Discount (%)" required class="crud-select" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,3)">
            <input type="date" name="expiry_date" id="expiry_date" required class="crud-select">
            <input type="text" name="description" placeholder="Description" class="crud-select">
            <select name="status" class="crud-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <button type="submit" name="add_voucher" class="crud-btn add-btn" style="background:#27ae60;color:#fff;">Add Voucher</button>
            <button type="button" class="crud-btn" onclick="hideAddVoucherForm()">Cancel</button>
        </form>
    </div>
</div>

<!-- Edit Voucher Modal -->
<?php if ($editVoucher): ?>
<div class="modal-overlay active" id="editVoucherModal">
    <div class="modal-content">
        <button type="button" class="modal-close-btn" onclick="window.location.href='voucherCRUD.php'">&times;</button>
        <form method="post" class="crud-form" id="editVoucherForm" onsubmit="return validateEditVoucherForm()">
            <h3>Edit Voucher</h3>
            <input type="hidden" name="voucher_id" value="<?= htmlspecialchars($editVoucher['VoucherID']) ?>">
            <input type="text" name="code" placeholder="Voucher Code" required class="crud-select" value="<?= htmlspecialchars($editVoucher['Code']) ?>">
            <input type="number" name="discount" min="1" max="100" step="1" placeholder="Discount (%)" required class="crud-select" value="<?= htmlspecialchars($editVoucher['Discount']) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,3)">
            <input type="date" name="expiry_date" id="edit_expiry_date" required class="crud-select" value="<?= htmlspecialchars($editVoucher['ExpiryDate']) ?>">
            <input type="text" name="description" placeholder="Description" class="crud-select" value="<?= htmlspecialchars($editVoucher['Description']) ?>">
            <select name="status" class="crud-select">
                <option value="Active" <?= $editVoucher['Status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $editVoucher['Status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button type="submit" name="edit_voucher" class="crud-btn edit-btn" style="background:#f39c12;color:#fff;">Save Changes</button>
            <button type="button" class="crud-btn" onclick="window.location.href='voucherCRUD.php'">Cancel</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function showAddVoucherForm() {
    document.getElementById('addVoucherModal').classList.add('active');
}
function hideAddVoucherForm() {
    document.getElementById('addVoucherModal').classList.remove('active');
    document.getElementById('addVoucherForm').reset();
}
// Set minimum expiry date to 24 hours from now
window.addEventListener('DOMContentLoaded', function() {
    var expiryInput = document.getElementById('expiry_date');
    if (expiryInput) {
        var now = new Date();
        now.setDate(now.getDate() + 1); // 24 hours from now
        var minDate = now.toISOString().split('T')[0];
        expiryInput.min = minDate;
    }
    // For edit modal
    var editExpiryInput = document.getElementById('edit_expiry_date');
    if (editExpiryInput) {
        var now = new Date();
        now.setDate(now.getDate() + 1);
        var minDate = now.toISOString().split('T')[0];
        editExpiryInput.min = minDate;
    }
});
// Validate discount input (1-100, integer only)
function validateVoucherForm() {
    var discount = document.querySelector('#addVoucherForm input[name="discount"]');
    var value = parseInt(discount.value, 10);
    if (isNaN(value) || value < 1 || value > 100) {
        alert('Discount must be an integer between 1 and 100.');
        discount.focus();
        return false;
    }
    return true;
}
// Validate edit form
function validateEditVoucherForm() {
    var discount = document.querySelector('#editVoucherForm input[name="discount"]');
    var value = parseInt(discount.value, 10);
    if (isNaN(value) || value < 1 || value > 100) {
        alert('Discount must be an integer between 1 and 100.');
        discount.focus();
        return false;
    }
    return true;
}
</script>
</body>
</html>
