<?php
/**
 * CivicTrack — track.php  (UPGRADED v3)
 */
$pageTitle  = 'Track Complaints';
$activePage = 'track';
require_once __DIR__ . '/includes/header.php';

$search       = trim($_GET['q']      ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$filters = [];
if ($statusFilter !== 'all' && $statusFilter !== '') $filters['status'] = $statusFilter;
if ($search !== '') $filters['q'] = $search;

$complaints   = getComplaints($filters);
$stats        = getStats();
$hasEscalated = !empty(array_filter($complaints, fn($c) => $c['status'] === 'Escalated'));
?>

<div class="page-hero">
  <div class="breadcrumb"><a href="<?= APP_URL ?>/index.php"><?= te('nav_home') ?></a><span>›</span> <?= te('track_title') ?></div>
  <h1>🔍 <?= te('track_title') ?></h1>
  <p><?= te('track_sub') ?></p>
</div>

<div class="section">
  <?= renderFlash() ?>

  <!-- Search + status filter -->
  <form method="GET" style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:22px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
    <div style="flex:1;min-width:200px">
      <div class="search-box" style="max-width:100%">
        <span class="search-icon">🔍</span>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= te('search_placeholder') ?>" style="border-radius:9px"/>
      </div>
    </div>
    <div style="min-width:160px">
      <select name="status" class="form-control" style="height:40px">
        <option value="all"        <?= $statusFilter==='all'         ?'selected':'' ?>>All Statuses</option>
        <option value="Pending"    <?= $statusFilter==='Pending'     ?'selected':'' ?>><?= te('filter_pending') ?></option>
        <option value="In Progress"<?= $statusFilter==='In Progress' ?'selected':'' ?>><?= te('filter_inprogress') ?></option>
        <option value="Resolved"   <?= $statusFilter==='Resolved'    ?'selected':'' ?>><?= te('filter_resolved') ?></option>
        <option value="Escalated"  <?= $statusFilter==='Escalated'   ?'selected':'' ?>><?= te('filter_escalated') ?></option>
      </select>
    </div>
    <button type="submit" class="btn-submit" style="height:40px;padding:0 22px"><?= te('btn_apply') ?></button>
    <a href="<?= APP_URL ?>/track.php" class="btn-ghost" style="height:40px;padding:0 16px;display:flex;align-items:center"><?= te('btn_reset') ?></a>
  </form>

  <?php if ($hasEscalated): ?>
    <div class="escalation-banner">
      <span class="esc-icon">⚠️</span>
      <span><strong>Escalation Notice:</strong> Some complaints have exceeded the <?= ESCALATION_DAYS ?>-day resolution deadline and have been automatically escalated.</span>
    </div>
  <?php endif; ?>

  <div style="font-size:13.5px;color:var(--text-muted);margin-bottom:16px">
    <?= te('showing') ?> <strong style="color:var(--text)"><?= count($complaints) ?></strong>
    <?= te('of') ?> <strong style="color:var(--text)"><?= (int)$stats['Total'] ?></strong>
    <?= te('complaints') ?>
  </div>

  <?php if (empty($complaints)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3><?= te('no_complaints') ?></h3>
      <p><a href="<?= APP_URL ?>/citizen/submit.php" style="color:var(--teal)"><?= te('nav_report') ?></a></p>
    </div>
  <?php else: ?>
    <div class="complaints-grid">
      <?php foreach ($complaints as $c): ?>
        <a href="<?= APP_URL ?>/citizen/view.php?id=<?= urlencode($c['complaint_id']) ?>"
           class="card complaint-card" style="text-decoration:none;display:block">
          <div class="complaint-card-header">
            <div class="complaint-icon" style="background:var(--bg);font-size:22px;width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
