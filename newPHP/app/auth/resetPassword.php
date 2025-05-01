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
    <style>
        .form-container {
            max-width: 400px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn {
            background-color: #e67e22;
            color: white;
            padding: 10px 0;
            width: 100%;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        .alert-box {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #842029;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
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
