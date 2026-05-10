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
$catStats = getCategoryStats(); // Now calling the new function from functions.php

$percentResolved = ($s['Total'] > 0) ? round(($s['Resolved'] / $s['Total']) * 100) : 0;

$pageTitle = "My Reports";
require_once __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-hero" style="background: var(--navy); color: white; padding: 40px 20px; text-align: center; margin-bottom: 30px;">
    <h1>My Reported Issues</h1>
    <p>Track the progress and history of your civic requests</p>
</div>

<div class="section" style="max-width:1000px; margin: 0 auto; padding: 0 20px;">
    <?= renderFlash() ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card" style="border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; background: #fff; height: 100%;">
                <div class="card-body">
                    <h2 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--navy); display: flex; align-items: center; gap: 10px;">
                        📊 <span>City-Wide Progress</span>
                    </h2>
                    <div style="height: 220px; position: relative;">
                        <canvas id="citizenAnalytics"></canvas>
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <div style="background: #f0fff4; padding: 10px; border-radius: 10px; border-left: 4px solid #28a745;">
                            <strong style="font-size: 1.2rem; color: var(--navy);"><?= $percentResolved ?>% Overall Resolution</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <h2 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--navy);">🏢 Department Performance</h2>
            <div class="row">
                <?php foreach($catStats as $stat): 
                    $perc = ($stat['total'] > 0) ? round(($stat['resolved'] / $stat['total']) * 100) : 0;
                ?>
                <div class="col-sm-6 mb-3">
                    <div class="card shadow-sm border-0" style="border-radius: 12px;">
                        <div class="card-body" style="padding: 15px;">
                            <h6 class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;"><?= typeIcon($stat['category']) ?> <?= e($stat['category']) ?></h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h4 mb-0"><?= $stat['total'] ?> <small style="font-size: 0.7rem; color: #999;">Cases</small></span>
                                <span class="badge" style="background: #e1f5fe; color: #01579b;"><?= $perc ?>% Fixed</span>
                            </div>
                            <div class="progress mt-2" style="height: 6px; border-radius: 10px;">
                                <div class="progress-bar bg-teal" style="width: <?= $perc ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <h2 style="margin: 30px 0 20px 0; font-size: 1.1rem; color: var(--navy);">📝 My Submission History</h2>
    <?php if (empty($my_complaints)): ?>
        <div class="card">
            <div class="card-body" style="text-align:center; padding:60px 20px;">
                <div style="font-size: 50px; margin-bottom: 20px;">📋</div>
                <h3 style="color:var(--navy)">No reports found</h3>
                <a href="<?= APP_URL ?>/citizen/submit.php" class="btn btn-teal">Report an Issue Now</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($my_complaints as $c): ?>
                <div class="card" style="border-left: 5px solid var(--teal); border-top: none; border-right: none; border-bottom: none; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                    <div class="card-body" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                        <div>
                            <div style="font-weight:700; font-size:16px; color:var(--navy);">
                                <?= typeIcon($c['type']) ?> <?= e($c['complaint_id']) ?> 
                            </div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top: 4px;">
                                📅 Submitted: <?= e($c['date_fmt']) ?>
                            </div>
                            <div style="margin-top:8px;">
                                <?= statusBadge($c['status']) ?>
                            </div>
                        </div>
                        <a href="<?= APP_URL ?>/citizen/view.php?id=<?= e($c['complaint_id']) ?>" class="btn btn-outline-teal" style="padding: 8px 16px; font-size: 0.9rem;">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
                borderWidth: 0
            }]
        },
        options: {
            cutout: '75%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
