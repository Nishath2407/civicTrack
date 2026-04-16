<?php
/**
 * CivicTrack — citizen/login.php
 * Login via Password or OTP
 */
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/lang.php';
require_once APP_ROOT . '/includes/otp.php';
loadLang();

if (isCitizen()) redirect(APP_URL . '/citizen/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? 'password'; // Determine if user clicked "Login" or "Send OTP"

    if (!preg_match('/^\d{10}$/', $phone)) {
        $error = 'Enter a valid 10-digit mobile number.';
    } else {
        // Fetch the user record
        $user = DB::row("SELECT * FROM citizens WHERE phone = ?", [$phone]);

        if (!$user) {
            $error = 'No account found for this number. Please register first.';
        } else {
            if ($action === 'password') {
                // --- OPTION 1: LOGIN WITH PASSWORD ---
                if (empty($password)) {
                    $error = 'Please enter your password.';
                } elseif (password_verify($password, $user['password_hash'])) {
                    // Success!
                    $_SESSION['citizen_id'] = $user['id'];
                    $_SESSION['citizen_phone'] = $user['phone'];
                    $_SESSION['citizen_name'] = $user['full_name'];
                    flash('success', 'Welcome back!');
                    redirect(APP_URL . '/citizen/dashboard.php');
                } else {
                    $error = 'Incorrect password. Try again or use OTP.';
                }
            } else {
                // --- OPTION 2: LOGIN WITH OTP ---
                $otp = createOtp($phone);
                $ok  = sendOtp($phone, $otp, $user['email']); // Sending to saved email
                if ($ok) {
                    $_SESSION['pending_phone'] = $phone;
                    $_SESSION['pending_name']  = $user['full_name'];
                    $_SESSION['pending_email'] = $user['email'];
                    redirect(APP_URL . '/citizen/verify-otp.php');
                } else {
                    $error = 'Could not send OTP. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= te('login_title') ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
</head>
<body>
<div class="auth-page">
  <div class="auth-box">
    <div class="auth-logo">
      <div class="auth-icon">🏛️</div>
      <h2><?= te('login_title') ?></h2>
      <p><?= te('login_sub') ?></p>
    </div>

    <?= renderFlash() ?>
    <?php if ($error): ?>
      <div class="flash-msg flash-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label><?= te('phone_label') ?> <span class="req">*</span></label>
        <div class="phone-input-wrap">
          <span class="phone-prefix">+91</span>
          <input type="tel" class="form-control" name="phone"
                 value="<?= e($_POST['phone'] ?? '') ?>"
                 maxlength="10" pattern="\d{10}" required autofocus/>
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" class="form-control" name="password" placeholder="Enter your password"/>
      </div>

      <button type="submit" name="action" value="password" class="btn-submit" style="width:100%;padding:13px;font-size:15px;margin-top:6px">
        Login with Password
      </button>

      <div style="text-align: center; margin: 15px 0; color: #aaa; font-size: 13px;">— OR —</div>

      <button type="submit" name="action" value="otp" class="btn-submit" style="width:100%;padding:10px;font-size:14px;background:var(--teal-light);color:var(--teal-dark)">
        Login via OTP
      </button>
    </form>

    <p class="auth-switch">
      <?= te('no_account') ?>
      <a href="<?= APP_URL ?>/citizen/register.php"><?= te('register_link') ?></a>
    </p>
  </div>
</div>
</body>
</html>