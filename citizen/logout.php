<?php
/**
 * CivicTrack — citizen/logout.php
 */
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/lang.php';
loadLang();
logoutCitizen();
flash('info', 'You have been signed out.');
redirect(APP_URL . '/index.php');
