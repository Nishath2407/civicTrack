<?php
/**
 * CivicTrack — admin/logout.php
 */
$base_path = "D:/xampp/htdocs/civictrack";

require_once $base_path . '/includes/config.php';
require_once $base_path . '/includes/db.php';
require_once $base_path . '/includes/auth.php';

// 1. Run the logout function defined in your auth.php
adminLogout();

// 2. Redirect to the login page with a success message
header("Location: login.php?msg=logged_out");
exit;