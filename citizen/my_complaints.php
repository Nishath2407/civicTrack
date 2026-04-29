<?php
/**
 * CivicTrack — citizen/my_complaints.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin(); 

$citizen_id = citizenId();
$my_complaints = getComplaints(['citizen_id' => $citizen_id]);

// --- FETCH Global Stats ---
$s = getStats(); 

// --- CALCULATE PERCENTAGE SAFELY ---
// If Total is 0, we set percent to 0 to avoid "Division by zero"
$percentResolved = ($s['Total'] > 0) ? round(($s['Resolved'] / $s['Total']) * 100) : 0;

$pageTitle = "My Reports";
require_once __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-hero">
    <h1>My Reported Issues</h1>
    <p>Track the progress and history of your civic requests</p>
</div>

<div class="section" style="max-width:800px">
    <?= renderFlash() ?>

    <div class="card" style="border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; background: #fff;">
        <div class="card-body">
            <h2 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--navy); display: flex; align-items: center; gap: 10px;">
                📊 <span>City-Wide Progress Scenario</span>
            </h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; align-items: center;">
                <div style="height: 180px; position: relative;">
                    <canvas id="citizenAnalytics"></canvas>
                </div>

                <div>
                    <div style="background: #f0fff4; padding: 12px; border-radius: 10px; border-left: 4px solid #28a745; margin-bottom: 10px;">
                        <span style="display: block; font-size: 0.75rem; color: #28a745; font-weight: bold; text-transform: uppercase;">Issues Resolved</span>
                        <strong style="font-size: 1.4rem; color: var(--navy);"><?= $s['Resolved'] ?></strong>
                    </div>
                    <p style="font-size: 0.85rem; color: #666; line-height: 1.5; margin: 0;">
                        Currently, <strong><?= $percentResolved ?>%</strong> of all issues in the city have been successfully fixed. Your contributions are making a difference!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($my_complaints)): ?>
        <div class="card">
            <div class="card-body" style="text-align:center; padding:60px 20px;">
                <div style="font-size: 50px; margin-bottom: 20px;">📋</div>
                <h3 style="color:var(--navy)">No reports found</h3>
                <p style="color:var(--text-muted); margin-bottom: 25px;">You haven't submitted any civic complaints yet.</p>
                <a href="<?= APP_URL ?>/citizen/submit.php" class="btn btn-teal">Report an Issue Now</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('citizenAnalytics').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Resolved', 'In Progress', 'Pending'],
            datasets: [{
                data: [<?= $s['Resolved'] ?>, <?= $s['In Progress'] ?>, <?= $s['Pending'] ?>],
                backgroundColor: ['#28a745', '#17a2b8', '#ffc107'],
                hoverOffset: 4,
                borderWidth: 0
            }]
        },
        options: {
            cutout: '70%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, font: { size: 10 } }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
