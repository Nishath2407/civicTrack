<?php
/**
 * CivicTrack — includes/config.php  (FIXED)
 */
// ── SESSION (safe start — only if not already active) ─────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── DATABASE ──────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'civictrack');
define('DB_USER',    'root');
define('DB_PASS',    '1234');
define('DB_CHARSET', 'utf8mb4');

// ── APPLICATION ───────────────────────────────────────────────
define('APP_NAME',    'CivicTrack');
define('APP_URL',     'http://localhost/civictrack');  // ← no trailing slash
define('APP_VERSION', '3.0.0');

// ── UPLOADS ───────────────────────────────────────────────────
define('UPLOAD_DIR',       __DIR__ . '/../uploads/');
define('UPLOAD_URL',       APP_URL . '/uploads/');
define('MAX_UPLOAD_BYTES',  5 * 1024 * 1024);
define('ALLOWED_MIME',     ['image/jpeg', 'image/png', 'image/webp']);

// ── ESCALATION SLA ────────────────────────────────────────────
define('ESCALATION_DAYS', 7);

// ── SESSION ───────────────────────────────────────────────────
define('SESSION_LIFETIME', 3600);

// ── OTP SETTINGS ─────────────────────────────────────────────
if (!defined('OTP_LENGTH')) {
    define('OTP_LENGTH', 6);
}
if (!defined('OTP_EXPIRY_MINS')) {
    define('OTP_EXPIRY_MINS', 10);
}
// Change this line in includes/config.php
if (!defined('OTP_DEMO_MODE')) {
    define('OTP_DEMO_MODE', false); // Set to false to stop logging and start mailing
}

// ── TIMEZONE ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ── DEBUG (set to false in production) ───────────────────────
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true); // ← TURNED ON so you can see real errors
}

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── EMAIL OTP CONFIG ──────────────────────────────────────────
define('OTP_EMAIL', 'yourgmail@gmail.com'); // sender email (used when OTP_DEMO_MODE = false)