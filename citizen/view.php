<?php
/**
 * CivicTrack — citizen/view.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$id = trim($_GET['id'] ?? '');
if (!$id) { 
    flash('error', 'No complaint ID provided.'); 
    redirect(APP_URL . '/citizen/my_complaints.php'); 
}

$c = getComplaint($id);

if (!$c || $c['citizen_id'] != $_SESSION['citizen_id']) {
    flash('error', "Report not found or access denied."); 
    redirect(APP_URL . '/citizen/my_complaints.php'); 
}

$pageTitle = "Track Report — " . $id;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="breadcrumb">
        <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="btn btn-outline"><?= te('hero_btn_track') ?></a> <?= e($c['complaint_id']) ?>
    </div>
    <h1><?= typeIcon($c['type']) ?> <?= e(preg_replace('/^[^\s]+\s/', '', $c['type'])) ?></h1>
    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-top:15px">
        <?= statusBadge($c['status']) ?>
        <span style="color:rgba(255,255,255,0.7); font-size:14px">
            📅 Submitted on <?= e($c['date_fmt']) ?>
        </span>
    </div>
</div>

<div class="section" style="max-width:900px">
    <?= renderFlash() ?>

    <div style="display: grid; grid-template-columns: 1fr 320px; gap: 25px; align-items: start;">
        <div>
            <div class="card" style="margin-bottom:20px">
                <div class="card-body">
                    <h3 style="margin-bottom:15px; font-size:18px">Report Details</h3>
                    <p style="line-height:1.6; color:var(--text); background:var(--bg); padding:15px; border-radius:8px; border-left: 4px solid var(--border)">
                        <?= nl2br(e($c['description'])) ?>
                    </p>

                    <?php if ($c['image_path']): ?>
                        <div style="margin-top:20px">
                            <div style="margin-bottom:10px; font-weight:700;">Evidence Provided:</div>
                            <img src="<?= APP_URL . '/' . e($c['image_path']) ?>" style="width:100%; max-height:400px; object-fit:contain; border-radius:12px; border:1px solid var(--border)">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($c['lat']) && !empty($c['lng'])): ?>
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-body">
                        <h3 style="margin-bottom:15px; font-size:18px">📍 Pinpointed Location</h3>
                        <div id="viewMap" style="height: 250px; width: 100%; border-radius: 12px; border: 1px solid var(--border); z-index:1;"></div>
                    </div>
                </div>
                <script>
                    var map = L.map('viewMap').setView([<?= $c['lat'] ?>, <?= $c['lng'] ?>], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    L.marker([<?= $c['lat'] ?>, <?= $c['lng'] ?>]).addTo(map)
                        .bindPopup("<b>Reported Location</b>").openPopup();
                </script>
            <?php endif; ?>
            <?php if ($c['status'] === 'Resolved' && empty($c['fb_rating'])): ?>
                <div class="card" style="border: 2px solid var(--amber); background: #fffcf5;">
                    <div class="card-body">
                        <h3 style="color:var(--navy); margin-bottom:10px">🎉 Issue Resolved</h3>
                        <p style="font-size:14px; margin-bottom:20px">How would you rate our service for this request?</p>
                        <form action="<?= APP_URL ?>/citizen/submit_feedback.php" method="POST">
                            <input type="hidden" name="complaint_id" value="<?= e($id) ?>">
                            <select name="rating" class="form-control" required style="margin-bottom:15px">
                                <option value="">Select Rating...</option>
                                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                <option value="4">⭐⭐⭐⭐ Very Good</option>
                                <option value="3">⭐⭐⭐ Satisfactory</option>
                                <option value="2">⭐⭐ Poor</option>
                                <option value="1">⭐ Very Bad</option>
                            </select>
                            <textarea name="comment" class="form-control" placeholder="Any additional comments?" rows="3"></textarea>
                            <button type="submit" class="btn btn-teal" style="margin-top:15px; width:100%">Submit Review</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($c['fb_rating'])): ?>
                <div class="card" style="border-top: 5px solid var(--amber);">
                    <div class="card-body">
                        <h3 style="color:var(--amber); font-size:16px; margin-bottom:10px">Your Feedback</h3>
                        <div style="margin-bottom:10px">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <span style="font-size:20px; color: <?= $i <= $c['fb_rating'] ? 'var(--amber)' : '#ddd' ?>;">★</span>
                            <?php endfor; ?>
                        </div>
                        <p style="font-style:italic; color:var(--text-muted)">"<?= e($c['fb_comment'] ?: 'No comment provided.') ?>"</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside>
            <div class="card">
                <div class="card-body">
                    <h4 style="margin-bottom:15px">Progress Timeline</h4>
                    <div class="timeline">
                        <?php foreach ($c['timeline'] as $log): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot <?= $log['is_done'] ? 'done' : '' ?>"></div>
                                <div class="timeline-content">
                                    <div style="font-size:13px; font-weight:700"><?= e($log['label']) ?></div>
                                    <small style="color:var(--text-muted)"><?= e($log['date']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div style="margin-top:20px; text-align:center">
                <a href="<?= APP_URL ?>/citizen/my_complaints.php" style="text-decoration:none; color:var(--teal); font-weight:700; font-size:14px">← Back to My Reports</a>
            </div>
        </aside>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
