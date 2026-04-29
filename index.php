<?php
/**
 * CivicTrack — index.php
 */
$pageTitle  = 'Home';
$activePage = 'home';
require_once __DIR__ . '/includes/functions.php';

if (!class_exists('DB')) {
    die('❌ DB CLASS STILL NOT LOADED');
}
require_once __DIR__ . '/includes/header.php';

// We are saving the data into $stats
$stats = getStats(); 
?>

<div id="hero">
  <div class="hero-badge"><?= te('hero_badge') ?></div>
  <h1><?= nl2br(te('hero_title')) ?></h1>
  <p><?= te('hero_sub') ?></p>
  <div class="hero-actions">
    <a href="<?= APP_URL ?>/citizen/submit.php" class="btn btn-teal"><?= te('hero_btn_report') ?></a>
    <a href="<?= APP_URL ?>/track.php"  class="btn btn-outline"><?= te('hero_btn_track') ?></a>
  </div>
</div>

<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-number"><?= $stats['Total'] ?></div> 
    <p>Total Complaints</p>
  </div>

  <div class="stat-item">
    <div class="stat-number"><?= $stats['Resolved'] ?></div>
    <p>Resolved</p>
  </div>

  <div class="stat-item">
    <div class="stat-number"><?= $stats['Pending'] ?></div>
    <p>Pending</p>
  </div>

  <div class="stat-item">
    <div class="stat-number"><?= $stats['Escalated'] ?></div>
    <p>Escalated</p>
  </div>
</div>

<div class="section">
  <?= renderFlash() ?>
  
  <div class="empty-state">
    <div class="empty-icon">🛡️</div>
    <h3>Secure Civic Reporting</h3>
    <p>To view your reported issues, please visit <strong>My Complaints</strong> or use the <strong>Track</strong> button above.</p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
