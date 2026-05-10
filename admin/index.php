<?php
/**
 * CivicTrack — admin/index.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

// 1. Run auto-escalation FIRST (marks stale complaints as Escalated)
checkAndEscalateComplaints();

// 2. Fetch fresh data AFTER escalation runs
$stats          = getStats();
$all_complaints = getComplaints();
$catStats       = getCategoryStats();

$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<?php
// ── Escalation alert banner ──────────────────────────────────────────────────
$escalated_count = (int)$stats['Escalated'];
if ($escalated_count > 0):
?>
<div style="background:#fff5f5; border-left:6px solid #e84545; padding:20px; margin-bottom:25px; border-radius:10px; box-shadow:0 4px 12px rgba(232,69,69,0.15); display:flex; align-items:center; gap:20px;">
    <div style="background:#e84545; width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:white; flex-shrink:0;">
        ⚠️
    </div>
    <div style="flex-grow:1;">
        <h3 style="margin:0; color:#b22222; font-size:1.1rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">
            SLA Protocol Violation Detected
        </h3>
        <p style="margin:5px 0 0 0; color:#555; font-size:0.95rem; line-height:1.4;">
            There are <strong><?= $escalated_count ?></strong> unresolved complaints exceeding the 7-day resolution window.
            An automated notice has been logged in the audit trail for the <strong>District Commissioner's</strong> review.
        </p>
    </div>
    <div style="flex-shrink:0;">
        <a href="#complaint-table" style="background:#e84545; color:white; padding:8px 15px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold;">Review Now</a>
    </div>
</div>
<?php endif; ?>
<!-- ^^^ endif is HERE — only the banner is conditional, NOT the rest of the page -->

<!-- ── Admin header ──────────────────────────────────────────────────────── -->
<div class="admin-header-flex" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
    <div>
        <h1 style="margin:0; color:var(--navy);">Admin Control Panel</h1>
        <p style="color:#666; margin:5px 0 0 0;">Welcome back, <strong><?= e(adminName()) ?></strong></p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?= APP_URL ?>/index.php" class="btn" style="background:#007bff; color:white; padding:8px 15px; border-radius:5px; text-decoration:none; font-size:13px; font-weight:bold; border:none; display:flex; align-items:center; gap:5px;">
            🌐 View Site
        </a>
        <a href="logout.php" class="btn" style="background:#e84545; color:white; padding:8px 15px; border-radius:5px; text-decoration:none; font-size:13px; font-weight:bold; border:none;">
            Logout
        </a>
    </div>
</div>

<!-- ── Stats bar ─────────────────────────────────────────────────────────── -->
<div class="stats-bar" style="margin-bottom:20px; display:grid; grid-template-columns:repeat(4,1fr); gap:15px;">
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <span style="display:block; font-size:1.5rem; font-weight:bold;"><?= (int)$stats['Total'] ?></span>
        <span style="font-size:0.8rem; color:#666;">Total Reports</span>
    </div>
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <span style="display:block; font-size:1.5rem; font-weight:bold; color:var(--amber);"><?= (int)$stats['Pending'] ?></span>
        <span style="font-size:0.8rem; color:#666;">Pending</span>
    </div>
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <span style="display:block; font-size:1.5rem; font-weight:bold; color:var(--teal);"><?= (int)$stats['Resolved'] ?></span>
        <span style="font-size:0.8rem; color:#666;">Resolved</span>
    </div>
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <span style="display:block; font-size:1.5rem; font-weight:bold; color:#e84545;"><?= (int)$stats['Escalated'] ?></span>
        <span style="font-size:0.8rem; color:#666;">Escalated</span>
    </div>
</div>

<!-- ── Department performance ─────────────────────────────────────────────── -->
<div style="margin-bottom:30px;">
    <h3 style="font-size:1rem; color:var(--navy); margin-bottom:15px;">Department-wise Performance Analytics</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:15px;">
        <?php foreach ($catStats as $stat):
            $perc  = ($stat['total'] > 0) ? round(($stat['resolved'] / $stat['total']) * 100) : 0;
            $color = ($perc < 40) ? '#e84545' : '#28a745';
        ?>
        <div style="background:#fff; padding:15px; border-radius:8px; border-top:4px solid <?= $color ?>; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <div style="font-size:0.7rem; color:#888; font-weight:bold; text-transform:uppercase;">
                <?= typeIcon($stat['category']) ?> <?= e($stat['category']) ?>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-top:5px;">
                <span style="font-size:1.4rem; font-weight:800;"><?= $perc ?>%</span>
                <small style="color:#666; font-size:0.75rem;"><?= $stat['resolved'] ?>/<?= $stat['total'] ?> Done</small>
            </div>
            <div style="background:#eee; height:4px; border-radius:10px; margin-top:8px;">
                <div style="background:<?= $color ?>; width:<?= $perc ?>%; height:100%;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Complaints table ───────────────────────────────────────────────────── -->
<div class="card" id="complaint-table">
    <div class="section-header" style="border-bottom:1px solid #eee; padding:20px;">
        <div class="section-title" style="font-weight:bold; color:var(--navy);">Manage All Complaints</div>
    </div>

    <div class="admin-table-wrapper" style="overflow-x:auto; padding:20px;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="text-align:left; border-bottom:2px solid #eee;">
                    <th style="padding:12px;">ID</th>
                    <th style="padding:12px;">Category</th>
                    <th style="padding:12px;">Citizen</th>
                    <th style="padding:12px;">Status</th>
                    <th style="padding:12px;">Priority</th>
                    <th style="padding:12px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($all_complaints)): ?>
                    <?php foreach ($all_complaints as $c): ?>
                    <tr style="border-bottom:1px solid #f9f9f9;">
                        <td style="padding:12px;">
                            <a href="<?= APP_URL ?>/admin/view.php?id=<?= urlencode($c['complaint_id']) ?>"
                               style="color:var(--teal); font-weight:bold; text-decoration:none;">
                                <?= e($c['complaint_id']) ?>
                            </a>
                        </td>
                        <td style="padding:12px;">
                            <?= e($c['type']) ?>
                            <?php if (!empty($c['lat'])): ?>
                                <span title="GPS Location Available">📍</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px;">
                            <?= e($c['citizen_name']) ?><br>
                            <small style="color:var(--text-muted)"><?= e($c['citizen_phone']) ?></small>
                        </td>
                        <td style="padding:12px;">
                            <form method="POST" action="<?= APP_URL ?>/admin/update_status.php" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="complaint_id" value="<?= e($c['complaint_id']) ?>">
                                <select name="status" class="form-control" style="padding:4px 8px; font-size:12px; height:auto; width:130px;">
                                    <option value="Pending"     <?= $c['status'] === 'Pending'     ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Progress" <?= $c['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Resolved"    <?= $c['status'] === 'Resolved'    ? 'selected' : '' ?>>Resolved</option>
                                    <option value="Escalated"   <?= $c['status'] === 'Escalated'   ? 'selected' : '' ?>>Escalated</option>
                                </select>
                        </td>
                        <td style="padding:12px;"><?= priorityBadge($c['priority']) ?></td>
                        <td style="padding:12px;">
                                <button type="submit" class="btn-submit" style="padding:6px 12px; font-size:11px; background:var(--navy); color:white; border:none; border-radius:4px; cursor:pointer;">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#888;">No complaints found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
