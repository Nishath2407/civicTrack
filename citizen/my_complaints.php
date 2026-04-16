<?php
/**
 * CivicTrack — citizen/my_complaints.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure the user is logged in before showing the page
requireLogin(); 

// Use the helper from auth.php to get the current citizen's ID
$citizen_id = citizenId();

// Fetch only the complaints belonging to this logged-in citizen
$my_complaints = getComplaints(['citizen_id' => $citizen_id]);

$pageTitle = "My Reports";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <h1>My Reported Issues</h1>
    <p>Track the progress and history of your civic requests</p>
</div>

<div class="section" style="max-width:800px">
    <?= renderFlash() ?>

    <?php if (empty($my_complaints)): ?>
        <div class="card">
            <div class="card-body" style="text-align:center; padding:60px 20px;">
                <div style="font-size: 50px; margin-bottom: 20px;">📋</div>
                <h3 style="color:var(--navy)">No reports found</h3>
                <p style="color:var(--text-muted); margin-bottom: 25px;">You haven't submitted any civic complaints yet.</p>
                <a href="<?= APP_URL ?>/submit.php" class="btn btn-teal">Report an Issue Now</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($my_complaints as $c): ?>
                <div class="card" style="border-left: 5px solid var(--teal); transition: transform 0.2s ease;">
                    <div class="card-body" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                        <div>
                            <div style="font-weight:700; font-size:17px; color:var(--navy); margin-bottom: 5px;">
                                <?= typeIcon($c['type']) ?> <?= e($c['complaint_id']) ?> 
                                <span style="font-weight: 400; color: #666; margin-left: 5px;">| <?= e($c['type']) ?></span>
                            </div>
                            <div style="font-size:13px; color:var(--text-muted);">
                                📅 Submitted: <?= e($c['date_fmt']) ?>
                            </div>
                            <div style="margin-top:10px;">
                                <?= statusBadge($c['status']) ?>
                                <?php if (!empty($c['fb_rating'])): ?>
                                    <span style="font-size: 12px; color: var(--amber); margin-left: 10px;">★ Reviewed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <a href="<?= APP_URL ?>/citizen/view.php?id=<?= e($c['complaint_id']) ?>" class="btn btn-teal" style="text-decoration:none; padding: 10px 20px;">
                            Track Progress →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 30px; text-align: center;">
        <p style="font-size: 14px; color: var(--text-muted);">
            Don't see a recent report? It may take a few minutes to appear.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>