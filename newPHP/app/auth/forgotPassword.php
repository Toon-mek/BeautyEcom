<?php
require_once __DIR__ . '/../_base.php';
$email = '';
$error = '';
$success = '';
$linkHTML = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $member = findMemberByEmail($email);
        if ($member) {
            $token = createPasswordResetToken($member['MemberID']);
            echo "<pre>DEBUG: Token created = $token</pre>";
            $link = "resetPassword.php?token=$token";

            $success = "A reset link has been generated below:";
            $linkHTML = "<a href='$link'>$link</a>";
        } else {
            $error = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="../css/style.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
</head>
<body>
    <div class="form-container">
        <h2>Forgot Password</h2>

        <?php if ($error): ?>
            <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
            <div style="word-break: break-all; text-align:center;"><?= $linkHTML ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post">
            <div class="form-group">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-input" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Send Reset Link</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
