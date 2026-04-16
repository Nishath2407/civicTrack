<?php
/**
 * CivicTrack — admin/login.php
 */
$base_path = "D:/xampp/htdocs/civictrack";
require_once $base_path . '/includes/config.php';
require_once $base_path . '/includes/db.php';
require_once $base_path . '/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF protection check
    // verifyCsrf(); 

    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if (adminLogin($user, $pass)) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | CivicTrack</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="auth-page">
    <div class="bg-animated"><span></span><span></span><span></span></div>
    
    <div class="auth-box">
        <div class="auth-logo">
            <div class="auth-icon">🔐</div>
            <h2>Admin Portal</h2>
            <p>Access requires authorization</p>
        </div>

        <?php if ($error): ?>
            <div class="flash-msg flash-error" style="margin-bottom: 20px; color: #e84545; background: rgba(232,69,69,0.1); padding: 10px; border-radius: 5px; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-teal" style="width:100%; justify-content:center; margin-top: 10px;">
                Secure Login
            </button>
        </form>
        
        <div class="auth-switch">
            <a href="../index.php">&larr; Return to Home</a>
        </div>
    </div>
</body>
</html>