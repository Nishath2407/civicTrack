<?php
/**
 * CivicTrack — includes/auth.php  (UPGRADED v3)
 * Handles BOTH admin and citizen sessions.
 */
if (!defined('APP_URL')) die('auth.php: config.php must be included first.');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ════════════════════════════════════════════════════════════
// ADMIN AUTH
// ════════════════════════════════════════════════════════════

function isAdmin(): bool {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_username']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function adminName(): string {
    return $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Admin';
}
function adminLogin(string $username, string $password): bool {
    // 1. Check if DB is actually connected
    $db = DB::get();
    if (!$db) { die("CRITICAL: Database not connected."); }

    // 2. Fetch the row
    $row = DB::row(
        "SELECT id, username, password_hash FROM admins WHERE username = ?",
        [trim($username)]
    );

    // 3. Trace the failure
    if (!$row) {
        die("FAIL: Username '" . htmlspecialchars($username) . "' not found in table 'admins'.");
    }

    if (!password_verify($password, $row['password_hash'])) {
        die("FAIL: Password check failed. The typed password does not match the hash in the DB.");
    }

    // 4. Success logic
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $row['id'];
    $_SESSION['admin_username'] = $row['username'];
    return true;
}
function adminLogout(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name'], $_SESSION['logged_in_at']);
}

// ════════════════════════════════════════════════════════════
// CITIZEN AUTH
// ════════════════════════════════════════════════════════════

/** Returns true if a citizen is logged in. */
function isCitizen(): bool {
    return !empty($_SESSION['citizen_id']) && !empty($_SESSION['citizen_phone']);
}

/** Redirect to login if citizen is not authenticated. */
function requireCitizen(): void {
    if (!isCitizen()) {
        header('Location: ' . APP_URL . '/citizen/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Ensures the user is logged in as a citizen.
 * If not, redirects to the citizen login page.
 */
function requireLogin() {
    if (!isCitizen()) {
        flash('error', 'Please log in to access this page.');
        header('Location: ' . APP_URL . '/citizen/login.php');
        exit;
    }
}

/** Returns the logged-in citizen's ID. */
function citizenId(): int {
    return (int)($_SESSION['citizen_id'] ?? 0);
}

/** Returns the logged-in citizen's phone. */
function citizenPhone(): string {
    return $_SESSION['citizen_phone'] ?? '';
}

/** Returns the logged-in citizen's name. */
function citizenName(): string {
    return $_SESSION['citizen_name'] ?? 'Citizen';
}

/**
 * Creates a citizen session after successful OTP verification.
 * Creates the citizen record in DB if it doesn't exist yet.
 */
/**
 * Creates a citizen session after successful OTP verification.
 * Saves the password hash during initial registration.
 */
function loginCitizen($phone, $name, $email, $passwordHash = null) {
    // Check if user already exists
    $user = DB::row("SELECT id FROM citizens WHERE phone = ?", [$phone]);
    
    if (!$user) {
        // Create user
        DB::exec(
            "INSERT INTO citizens (phone, full_name, email, password_hash) VALUES (?, ?, ?, ?)", 
            [$phone, $name, $email, $passwordHash]
        );
        
        // This is where line 109 was crashing:
        $userId = DB::lastInsertId(); 
    } else {
        $userId = $user['id'];
    }

    // Log them in
    $_SESSION['citizen_id'] = $userId;
    $_SESSION['citizen_phone'] = $phone;
    $_SESSION['citizen_name'] = $name;
}

/** Logs out the citizen (preserves admin session if any). */
function logoutCitizen(): void {
    unset($_SESSION['citizen_id'], $_SESSION['citizen_phone'], $_SESSION['citizen_name']);
}

// ════════════════════════════════════════════════════════════
// CSRF
// ════════════════════════════════════════════════════════════


function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token    = $_POST['csrf'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$token || !hash_equals($expected, $token)) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;padding:40px;color:red">Invalid request. Go back and try again.</p>');
    }
}
