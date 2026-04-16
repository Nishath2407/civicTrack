<?php
/**
 * CivicTrack — admin/index.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin(); // Ensure only admins are here

// FIX: Fetch the data so the variable is defined
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
        <a href="<?= APP_URL ?>/index.php" class="btn btn-outline" style="font-size:13px;">View Site</a>
        <a href="logout.php" class="btn btn-teal" style="background:#e84545; font-size:13px;">Logout</a>
    </div>
</div>

<div class="stats-bar" style="margin-bottom:30px; grid-template-columns: repeat(4, 1fr);">
    <div class="stat-item">
        <span class="stat-num"><?= (int)$stats['total'] ?></span>
        <span class="stat-label">Total Reports</span>
    </div>
    <div class="stat-item">
        <span class="stat-num" style="color:var(--amber);"><?= (int)$stats['pending'] ?></span>
        <span class="stat-label">Pending</span>
    </div>
    <div class="stat-item">
        <span class="stat-num" style="color:var(--teal);"><?= (int)$stats['resolved'] ?></span>
        <span class="stat-label">Resolved</span>
    </div>
    <div class="stat-item">
        <span class="stat-num" style="color:#e84545;"><?= (int)$stats['escalated'] ?></span>
        <span class="stat-label">Escalated</span>
    </div>
</div>

<div class="card">
    <div class="section-header" style="border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <div class="section-title">Manage All Complaints</div>
    </div>
    
    <div class="admin-table-wrapper" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Citizen</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($all_complaints)): ?>
                <?php foreach ($all_complaints as $c): ?>
                <tr>
              <td>
                  <a href="<?= APP_URL ?>/admin/view.php?id=<?= urlencode($c['complaint_id']) ?>" 
                  style="color:var(--teal); font-weight:bold; text-decoration:none; border-bottom:1px dashed var(--teal);">
                   <?= e($c['complaint_id']) ?>
              </a>
            </td>
            <td><?= e($c['type']) ?></td>
                    <td>
                        <?= e($c['citizen_name']) ?><br>
                        <small style="color:var(--text-muted)"><?= e($c['citizen_phone']) ?></small>
                    </td>
                    <td>
                        <form method="POST" action="<?= APP_URL ?>/admin/update_status.php" style="display:flex; gap:8px;">
                            <input type="hidden" name="complaint_id" value="<?= e($c['complaint_id']) ?>">
                            <select name="status" class="form-control" style="padding:4px 8px; font-size:12px; height:auto; width:130px;">
                                <option value="Pending" <?= $c['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $c['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Resolved" <?= $c['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="Escalated" <?= $c['status'] == 'Escalated' ? 'selected' : '' ?>>Escalated</option>
                            </select>
                    </td>
                    <td><?= priorityBadge($c['priority']) ?></td>
                    <td>
                            <button type="submit" class="btn-submit" style="padding:6px 12px; font-size:11px;">Update</button>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>; ?>