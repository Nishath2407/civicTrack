<?php
/**
 * CivicTrack — admin/index.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin(); // Ensure only admins are here

// Fetch data
$all_complaints = getComplaints(); 
$stats = getStats();
$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

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

<div class="stats-bar" style="margin-bottom:30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <span class="stat-num" style="display:block; font-size:1.5rem; font-weight:bold;"><?= (int)$stats['Total'] ?></span>
        <span class="stat-label" style="font-size:0.8rem; color:#666;">Total Reports</span>
    </div>
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <span class="stat-num" style="display:block; font-size:1.5rem; font-weight:bold; color:var(--amber);"><?= (int)$stats['Pending'] ?></span>
        <span class="stat-label" style="font-size:0.8rem; color:#666;">Pending</span>
    </div>
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <span class="stat-num" style="display:block; font-size:1.5rem; font-weight:bold; color:var(--teal);"><?= (int)$stats['Resolved'] ?></span>
        <span class="stat-label" style="font-size:0.8rem; color:#666;">Resolved</span>
    </div>
    <div class="stat-item" style="background:#fff; padding:20px; border-radius:10px; text-align:center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <span class="stat-num" style="display:block; font-size:1.5rem; font-weight:bold; color:#e84545;"><?= (int)$stats['Escalated'] ?></span>
        <span class="stat-label" style="font-size:0.8rem; color:#666;">Escalated</span>
    </div>
</div>

<div class="card">
    <div class="section-header" style="border-bottom: 1px solid #eee; padding: 20px;">
        <div class="section-title" style="font-weight:bold; color:var(--navy);">Manage All Complaints</div>
    </div>
    
    <div class="admin-table-wrapper" style="overflow-x:auto; padding:20px;">
    <table style="width:100%; border-collapse: collapse;">
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
                        <form method="POST" action="<?= APP_URL ?>/admin/update_status.php" style="display:flex; gap:8px;">
                            <input type="hidden" name="complaint_id" value="<?= e($c['complaint_id']) ?>">
                            <select name="status" class="form-control" style="padding:4px 8px; font-size:12px; height:auto; width:130px;">
                                <option value="Pending" <?= $c['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $c['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Resolved" <?= $c['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="Escalated" <?= $c['status'] == 'Escalated' ? 'selected' : '' ?>>Escalated</option>
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
                    <td colspan="6" style="text-align:center; padding:40px;">No complaints found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
