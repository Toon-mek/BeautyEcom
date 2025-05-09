<?php
require_once __DIR__ . '/../_base.php';

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$error = '';
$success = '';
$showForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (empty($password) || empty($confirm)) {
        $error = "Both fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        if (resetPasswordByToken($token, $password)) {
            $_SESSION['success'] = "Password reset successful. Please login.";
            header("Location: login.php");
            exit;
        } else {
            $error = "Invalid or expired token.";
            $showForm = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="../css/style.css" rel="stylesheet" />
</head>
<body>
    <div class="form-container">
        <h2>Reset Password</h2>
        <?php if ($error): ?>
            <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($showForm): ?>
        <form method="post" onsubmit="return validateResetForm();">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-group">
                <label for="password" class="form-label">New Password:</label>
                <input type="password" class="form-input" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm" class="form-label">Confirm Password:</label>
                <input type="password" class="form-input" id="confirm" name="confirm" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Reset Password</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
