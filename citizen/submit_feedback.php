<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['complaint_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    // This function is already in your functions.php!
    saveFeedback($id, $rating, $comment);

    flash('success', 'Thank you for your feedback!');
    redirect(APP_URL . "/citizen/view.php?id=" . $id);
}