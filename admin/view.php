<?php
/**
 * CivicTrack — admin/view.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$id = trim($_GET['id'] ?? '');
if (!$id) { 
    flash('error', 'No complaint ID provided.'); 
    redirect(APP_URL . '/admin/index.php'); 
}

$c = getComplaint($id);
if (!$c) { 
    flash('error', "Complaint '{$id}' not found."); 
    redirect(APP_URL . '/admin/index.php'); 
}

$pageTitle = "View Complaint - " . $id;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/admin/index.php">Admin Dashboard</a><span>›</span>
    <?= e($c['complaint_id']) ?>
  </div>
  <h1><?= typeIcon($c['type']) ?> <?= e(preg_replace('/^[^\s]+\s/', '', $c['type'])) ?></h1>
  <p style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px">
    <?= statusBadge($c['status']) ?>
    <?= priorityBadge($c['priority']) ?>
    <span style="color:rgba(255,255,255,0.55);font-size:13px">Submitted <?= e($c['date_fmt']) ?></span>
  </p>
</div>

<div class="section" style="max-width:820px">
  <?= renderFlash() ?>

  <div class="card" style="margin-bottom:22px">
    <div class="card-body">
      <div class="form-section-title">Issue Information</div>
      <div class="detail-row"><span class="detail-label">Citizen Info</span><span class="detail-value"><strong><?= e($c['citizen_name']) ?></strong> · +91 <?= e($c['citizen_phone']) ?></span></div>
      <div class="detail-row"><span class="detail-label">Location</span><span class="detail-value"><?= e($c['address']) ?></span></div>
      <div class="detail-row"><span class="detail-label">Landmark</span><span class="detail-value"><?= e($c['landmark'] ?: 'N/A') ?></span></div>
      <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value"><?= nl2br(e($c['description'])) ?></span></div>
      
      <?php if ($c['image_path']): ?>
        <div class="detail-row">
          <span class="detail-label">Evidence</span>
          <span class="detail-value">
            <a href="<?= APP_URL . '/' . e($c['image_path']) ?>" target="_blank">
                <img src="<?= APP_URL . '/' . e($c['image_path']) ?>" style="max-width:100%; max-height:400px; border-radius:8px; border:1px solid var(--border);">
            </a>
          </span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($c['lat']) && !empty($c['lng'])): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <div class="card" style="margin-bottom:22px;">
        <div class="card-body">
            <div class="form-section-title">Precise Map Location</div>
            <div id="adminMap" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid var(--border); z-index:1;"></div>
            <div style="margin-top: 10px; font-size: 13px; color: #666;">
                <strong>Coordinates:</strong> <?= $c['lat'] ?>, <?= $c['lng'] ?> 
                | <a href="https://www.google.com/maps/search/?api=1&query=<?= $c['lat'] ?>,<?= $c['lng'] ?>" target="_blank" style="color:var(--teal); font-weight:bold;">View on Google Maps</a>
            </div>
        </div>
    </div>
    <script>
        var map = L.map('adminMap').setView([<?= $c['lat'] ?>, <?= $c['lng'] ?>], 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        L.marker([<?= $c['lat'] ?>, <?= $c['lng'] ?>]).addTo(map).bindPopup("<b>Issue Spot</b>").openPopup();
    </script>
  <?php endif; ?>
  <div class="card" style="margin-bottom:22px; border-left: 5px solid var(--teal); background: #f4fdfb;">
    <div class="card-body">
      <h4 style="margin-bottom:15px; font-size: 16px;">🛠️ Update Issue Status</h4>
      <form method="POST" action="<?= APP_URL ?>/admin/update_status.php" onsubmit="return confirm('Update status now?');">
          <input type="hidden" name="complaint_id" value="<?= e($c['complaint_id']) ?>">
          <div style="display:flex; gap:10px;">
              <select name="status" class="form-control" style="flex:1">
                  <option value="Pending" <?= $c['status'] == 'Pending' ? 'selected' : '' ?>>Pending Review</option>
                  <option value="In Progress" <?= $c['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                  <option value="Resolved" <?= $c['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                  <option value="Escalated" <?= $c['status'] == 'Escalated' ? 'selected' : '' ?>>Escalated</option>
              </select>
              <button type="submit" class="btn btn-teal">Apply Update</button>
          </div>
      </form>
    </div>
  </div>

  <div class="card" style="margin-bottom:22px;">
    <div class="card-body">
        <div class="form-section-title">Tracking History</div>
        <div class="timeline">
            <?php foreach ($c['timeline'] as $t): ?>
                <div class="timeline-item">
                    <div class="timeline-dot <?= $t['is_done'] ? 'done' : '' ?>"></div>
                    <div class="timeline-text">
                        <strong><?= e($t['label']) ?></strong>
                        <small><?= e($t['date']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
  </div>

  <?php if (!empty($c['fb_rating'])): ?>
    <div class="card" style="margin-bottom:22px; border-top: 4px solid var(--amber);">
        <div class="card-body">
            <div class="form-section-title" style="color:var(--amber);">Citizen Review</div>
            <div class="stars" style="margin-bottom:10px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span style="font-size:24px; color: <?= $i <= $c['fb_rating'] ? 'var(--amber)' : '#ddd' ?>;">★</span>
                <?php endfor; ?>
                <span style="margin-left:10px; font-weight:700; font-size:18px; color:var(--navy);"><?= $c['fb_rating'] ?> / 5</span>
            </div>
            <blockquote style="border-left:3px solid var(--border); padding-left:15px; margin:10px 0; font-style:italic; color:var(--navy); font-size:15px;">
                "<?= e($c['fb_comment'] ?: 'No written comment provided.') ?>"
            </blockquote>
            <small style="color:var(--text-muted);">Review received for case <?= e($c['complaint_id']) ?></small>
        </div>
    </div>
  <?php elseif ($c['status'] === 'Resolved'): ?>
    <div class="card" style="margin-bottom:22px; background: var(--bg);">
        <div class="card-body" style="text-align:center; padding:30px;">
            <p style="color:var(--text-muted); font-size:14px;">✅ This issue is marked as Resolved. Waiting for the citizen to submit their review.</p>
        </div>
    </div>
  <?php endif; ?>

  <div style="text-align:center; margin-top:30px;">
      <a href="<?= APP_URL ?>/admin/index.php" class="btn-ghost">← Back to Dashboard</a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
