<?php
/**
 * CivicTrack — citizen/dashboard.php
 * Authenticated citizen's complaint history.
 */
$pageTitle  = 'My Complaints';
$activePage = 'dashboard';
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/header.php';
requireCitizen();

$complaints = getComplaints(['citizen_id' => citizenId()]);
$stats      = [
    'total'    => count($complaints),
    'pending'  => count(array_filter($complaints, fn($c) => $c['status'] === 'Pending')),
    'progress' => count(array_filter($complaints, fn($c) => $c['status'] === 'In Progress')),
    'resolved' => count(array_filter($complaints, fn($c) => $c['status'] === 'Resolved')),
];
?>

<div class="page-hero">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/index.php"><?= te('nav_home') ?></a><span>›</span>
    <?= te('my_complaints') ?>
  </div>
  <h1>👤 <?= te('welcome_back') ?>, <?= e(citizenName()) ?>!</h1>
  <p><?= te('my_complaints_sub') ?></p>
</div>

<div class="section">
  <?= renderFlash() ?>

  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px">
    <?php $cards = [
      ['📋', $stats['total'],    'Total'],
      ['⏳', $stats['pending'],  'Pending'],
      ['🔧', $stats['progress'], 'In Progress'],
      ['✅', $stats['resolved'], 'Resolved'],
    ];
    foreach ($cards as [$ico, $num, $lbl]): ?>
      <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:var(--bg);font-size:20px"><?= $ico ?></div>
        <div>
          <div class="admin-stat-num"><?= $num ?></div>
          <div class="admin-stat-label"><?= $lbl ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="section-header" style="padding:0;margin-bottom:20px">
    <div class="section-title"><?= te('my_complaints') ?></div>
    <a href="<?= APP_URL ?>/citizen/submit.php" class="btn btn-teal"><?= te('nav_report') ?></a>
  </div>

  <?php if (empty($complaints)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>No complaints yet</h3>
      <p>You haven't submitted any complaints. <a href="<?= APP_URL ?>/citizen/submit.php" style="color:var(--teal)">Report an issue</a> to get started.</p>
    </div>
  <?php else: ?>
    <div class="complaints-grid">
      <?php foreach ($complaints as $c): ?>
        <a href="<?= APP_URL ?>/citizen/view.php?id=<?= urlencode($c['complaint_id']) ?>"
           class="card complaint-card" style="text-decoration:none;display:block">
          <div class="complaint-card-header">
            <div class="complaint-icon" style="background:var(--bg);width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px">
              <?= typeIcon($c['type']) ?>
            </div>
            <div class="complaint-meta">
              <div class="complaint-type"><?= e(preg_replace('/^[^\s]+\s/', '', $c['type'])) ?></div>
              <div class="complaint-id"><?= e($c['complaint_id']) ?> · <?= e($c['date_fmt']) ?></div>
            </div>
            <?= priorityBadge($c['priority']) ?>
          </div>
          <div class="complaint-card-body">
            <div class="complaint-desc"><?= e($c['description']) ?></div>
            <div class="complaint-footer">
              <span class="complaint-location">📍 <?= e($c['address']) ?></span>
              <?= statusBadge($c['status']) ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>