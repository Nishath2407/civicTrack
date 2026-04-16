<?php
/**
 * CivicTrack — admin/update_status.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin(); // Security check

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = $_POST['complaint_id'] ?? '';
    $newStatus   = $_POST['status'] ?? '';

    if ($complaintId && $newStatus) {
        $sql = "UPDATE complaints SET status = ? WHERE complaint_id = ?";
        $success = DB::exec($sql, [$newStatus, $complaintId]);

        if ($success) {
            flash('success', "Status updated for $complaintId");
        } else {
            flash('error', "No changes made or error occurred.");
        }
    }
}

// Redirect back to the main admin dashboard
redirect(APP_URL . '/admin/index.php');