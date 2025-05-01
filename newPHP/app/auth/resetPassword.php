<?php
require_once __DIR__ . '/../_base.php';

$error = null;
$success = null;
$validToken = false;
$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $pdo->prepare("SELECT MemberID FROM member WHERE ResetToken = ? AND ResetTokenExpiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $validToken = true;
    } else {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Update password and clear reset token
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE member SET Password = ?, ResetToken = NULL, ResetTokenExpiry = NULL WHERE ResetToken = ?");
        $stmt->execute([$hashedPassword, $token]);
        
        $success = "Password has been reset successfully!";
        // Redirect to login after 3 seconds
        header("refresh:3;url=login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Beauty & Wellness</title>
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="text-center mb-4">Reset Password</h2>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-message">
                <?php echo $success; ?>
                <p>Redirecting to login page...</p>
            </div>
        <?php endif; ?>

        <?php if($validToken && !$success): ?>
            <form method="POST" action="" onsubmit="return validatePasswords();">
                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-input" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn">Reset Password</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <script>
    function validatePasswords() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Password validation
        const passwordRegex = /^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[!@#$]).{8,}$/;
        if (!passwordRegex.test(password)) {
            alert("Password must be at least 8 characters and include numbers, letters, and one of !@#$.");
            return false;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            alert("Passwords do not match!");
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html> 