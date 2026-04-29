<?php
/**
 * CivicTrack — citizen/register.php
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
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old      = $_POST;
    $name     = trim($_POST['name']    ?? '');
    $phone    = trim($_POST['phone']   ?? '');
    $email    = trim($_POST['email']   ?? '');
    $password = $_POST['password']    ?? '';

    // ── VALIDATION ───────────────────────────────────────────────
    if (strlen($name) < 2) {
        $error = 'Please enter your full name.';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = 'Enter a valid 10-digit mobile number.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if already registered
        $exists = DB::value("SELECT COUNT(*) FROM citizens WHERE phone = ?", [$phone]);
        if ($exists) {
            $error = 'This phone number is already registered. Please log in instead.';
        } else {
            // Generate and send OTP
            $otp = createOtp($phone);
            $ok  = sendOtp($phone, $otp, $email);

            if ($ok) {
                // Store all data in session locker to be saved AFTER OTP is verified
                $_SESSION['pending_phone']    = $phone;
                $_SESSION['pending_name']     = $name;
                $_SESSION['pending_email']    = $email;
                $_SESSION['pending_password'] = password_hash($password, PASSWORD_BCRYPT);
                
                redirect(APP_URL . '/citizen/verify-otp.php');
            } else {
                $lastError = error_get_last();
                $errorMsg = $lastError ? $lastError['message'] : 'Check your internet or SMTP settings.';
                $error = 'Could not send OTP. ' . $errorMsg;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= te('register_title') ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
</head>
<body>

<div class="bg-animated" aria-hidden="true">
  <span></span><span></span><span></span><span></span><span></span>
</div>

<div class="auth-page">
  <div class="auth-box">
    <div class="auth-logo">
      <div class="auth-icon">🏛️</div>
      <h2><?= te('register_title') ?></h2>
      <p><?= te('register_sub') ?></p>
    </div>

    <?php if ($error): ?>
      <div class="flash-msg flash-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <?php if (OTP_DEMO_MODE): ?>
      <div class="flash-msg flash-info" style="font-size:12px;opacity:0.85">
        🛠️ Demo mode: OTP is written to <code>otp_debug.log</code> in project root.
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label><?= te('full_name_label') ?> <span class="req">*</span></label>
        <input type="text" class="form-control" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="<?= te('full_name_ph') ?>" required autofocus/>
      </div>

      <div class="form-group">
        <label><?= te('phone_label') ?> <span class="req">*</span></label>
        <div class="phone-input-wrap">
          <span class="phone-prefix">+91</span>
          <input type="tel" class="form-control" name="phone" value="<?= e($old['phone'] ?? '') ?>" placeholder="<?= te('phone_ph') ?>" maxlength="10" pattern="\d{10}" required/>
        </div>
      </div>

      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input type="email" class="form-control" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="Enter your email" required/>
      </div>

      <div class="form-group">
        <label>Create Password <span class="req">*</span></label>
        <input type="password" class="form-control" name="password" placeholder="Min. 6 characters" required/>
      </div>

      <button type="submit" class="btn-submit" style="width:100%;padding:13px;font-size:15px;margin-top:6px">
        <?= te('btn_register') ?>
      </button>
    </form>

    <p class="auth-switch">
      <?= te('have_account') ?>
      <a href="<?= APP_URL ?>/citizen/login.php"><?= te('login_link') ?></a>
    </p>

    <div style="text-align:center;margin-top:12px">
      <?= langSwitcher() ?>
    </div>
  </div>
</div>
</body>
</html>
