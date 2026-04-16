<?php
/**
 * CivicTrack — includes/header.php
 * Public page shared header. Loads core files and renders navigation.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';

// Initialize language if defined in lang.php
if (function_exists('loadLang')) {
    loadLang();
}

$pageTitle  ??= APP_NAME;
$activePage ??= '';
?>
<!DOCTYPE html>
<html lang="<?= function_exists('currentLang') ? e(currentLang()) : 'en' ?>">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
  
  <link rel="stylesheet" href="<?= APP_URL ?>/style.css"/>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
</head>
<body>

<div class="bg-animated" aria-hidden="true">
  <span></span><span></span><span></span><span></span>
  <span></span><span></span><span></span>
</div>

<nav id="topbar">
  <a class="logo" href="<?= APP_URL ?>/index.php">
    <div class="logo-icon">🏛️</div>
    <span class="logo-text">Civic<span>Track</span></span>
  </a>

  <button class="hamburger" id="hamburger" aria-label="Menu" onclick="toggleMenu()">
    <span></span><span></span><span></span>
  </button>

  <div class="nav-links" id="navLinks">
    <a href="<?= APP_URL ?>/index.php" class="nav-btn <?= $activePage==='home' ?'active':'' ?>"><?= te('nav_home') ?></a>

    <?php if (isCitizen()): ?>
        <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="nav-btn <?= $activePage==='track' ?'active':'' ?>"><?= te('nav_track') ?></a>
    <?php else: ?>
        <a href="<?= APP_URL ?>/track.php" class="nav-btn <?= $activePage==='track' ?'active':'' ?>"><?= te('nav_track') ?></a>
    <?php endif; ?>

    <?php if (isCitizen()): ?>
        <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="nav-btn <?= $activePage==='dashboard' ?'active':'' ?>"><?= te('nav_my_complaints') ?></a>
        <a href="<?= APP_URL ?>/citizen/logout.php" class="nav-btn logout-link" style="color:rgba(255,255,255,0.55)"><?= te('nav_logout') ?></a>
        <span class="nav-citizen-name">👤 <?= e(citizenName()) ?></span>
    <?php else: ?>
        <a href="<?= APP_URL ?>/citizen/login.php" class="nav-btn"><?= te('nav_login') ?></a>
        <a href="<?= APP_URL ?>/citizen/register.php" class="nav-btn"><?= te('nav_register') ?></a>
    <?php endif; ?>

    <a href="<?= APP_URL ?>/citizen/submit.php" class="nav-btn primary"><?= te('nav_report') ?></a>

    <?= function_exists('langSwitcher') ? langSwitcher() : '' ?>
  </div>
</nav>

<script>
/** Mobile Menu Toggle */
function toggleMenu() {
  const nav = document.getElementById('navLinks');
  nav.classList.toggle('open');
}
</script>