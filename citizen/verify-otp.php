<?php
/**
 * CivicTrack — citizen/verify-otp.php
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

$pendingPhone = $_SESSION['pending_phone']    ?? '';
$pendingName  = $_SESSION['pending_name']     ?? '';
$pendingEmail = $_SESSION['pending_email']    ?? ''; 
$pendingPass  = $_SESSION['pending_password'] ?? ''; 

if (!$pendingPhone) redirect(APP_URL . '/citizen/login.php');

$error = '';

// Handle Resend
if (isset($_GET['resend'])) {
    unset($_SESSION['otp_created_for']);
    $otp = createOtp($pendingPhone);
    if (sendOtp($pendingPhone, $otp, $pendingEmail)) {
        flash('info', 'A new OTP has been sent.');
    } else {
        flash('error', 'Failed to resend OTP.');
    }
    redirect(APP_URL . '/citizen/verify-otp.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered = trim(implode('', $_POST['otp'] ?? []));
    if (empty($entered)) $entered = trim($_POST['otp_full'] ?? '');

    if (strlen($entered) !== 6 || !ctype_digit($entered)) {
        $error = 'Please enter a valid 6-digit OTP.';
    } elseif (!verifyOtp($pendingPhone, $entered)) {
        $error = 'Incorrect or expired OTP.';
    } else {
        // SUCCESS! Finalize registration with the Password Hash
        loginCitizen($pendingPhone, $pendingName, $pendingEmail, $pendingPass);

        // Clear session locker
        unset(
            $_SESSION['pending_phone'],
            $_SESSION['pending_name'],
            $_SESSION['pending_email'],
            $_SESSION['pending_password'],
            $_SESSION['otp_created_for']
        );

        $next = $_SESSION['otp_next'] ?? APP_URL . '/citizen/dashboard.php';
        unset($_SESSION['otp_next']);
        flash('success', 'Welcome to CivicTrack! Registration complete.');
        redirect($next);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= te('otp_title') ?> — <?= APP_NAME ?></title>
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
      <div class="auth-icon">🔐</div>
      <h2><?= te('otp_title') ?></h2>
      <p><?= te('otp_sub') ?></p>
      <p style="margin-top:6px;font-size:13px;color:var(--teal)">+91 <?= e($pendingPhone) ?></p>
    </div>

    <?= renderFlash() ?>
    <?php if ($error): ?>
      <div class="flash-msg flash-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="otpForm">
      <div class="otp-inputs" id="otpInputs">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="tel" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" name="otp[]" required/>
        <?php endfor; ?>
      </div>
      <input type="hidden" name="otp_full" id="otpFull"/>
      <button type="submit" class="btn-submit" style="width:100%;padding:13px;font-size:15px;margin-top:18px">
        <?= te('btn_verify') ?>
      </button>
    </form>

    <p class="auth-switch" style="margin-top:16px">
      Didn't receive? <a href="?resend=1"><?= te('resend_otp') ?></a>
    </p>
  </div>
</div>

<script>
const inputs = document.querySelectorAll('.otp-digit');
const fullInput = document.getElementById('otpFull');

inputs.forEach((inp, i) => {
  inp.addEventListener('input', () => {
    inp.value = inp.value.replace(/\D/g, '').slice(-1);
    if (inp.value && i < inputs.length - 1) inputs[i + 1].focus();
    fullInput.value = Array.from(inputs).map(i => i.value).join('');
  });
  inp.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !inp.value && i > 0) inputs[i - 1].focus();
  });
});
</script>
</body>
</html>