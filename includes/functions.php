<?php
/**
 * CivicTrack — includes/functions.php
 * All shared helpers: complaints, citizens, OTP, validation, badges,
 * and AUTO-ESCALATION for stale complaints (no update within 7 days).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ════════════════════════════════════════════════════════════
// OUTPUT & SECURITY HELPERS
// ════════════════════════════════════════════════════════════

/** Escape HTML for output */
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

/** Simple redirect helper */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** Set a flash message in the session */
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Render and clear flash messages */
function renderFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    
    $cls  = match ($f['type']) { 'success' => 'flash-success', 'error' => 'flash-error', default => 'flash-info' };
    $icon = match ($f['type']) { 'success' => '✅', 'error' => '⚠️', default => 'ℹ️' };
    
    return '<div class="flash-msg ' . $cls . '">' . $icon . ' ' . e($f['message']) . '</div>';
}

// ════════════════════════════════════════════════════════════
// COMPLAINT DATA ACCESS
// ════════════════════════════════════════════════════════════

/** Fetch all complaints with optional filters */
function getComplaints(array $filters = []): array {
    $where  = ['1=1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[]  = 'c.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['priority'])) {
        $where[]  = 'c.priority = ?';
        $params[] = $filters['priority'];
    }
    if (!empty($filters['citizen_id'])) {
        $where[]  = 'c.citizen_id = ?';
        $params[] = (int)$filters['citizen_id'];
    }
    if (!empty($filters['q'])) {
        $q = '%' . $filters['q'] . '%';
        $where[]  = '(c.complaint_id LIKE ? OR c.type LIKE ? OR c.description LIKE ? OR c.citizen_name LIKE ?)';
        array_push($params, $q, $q, $q, $q);
    }

    $sql = "SELECT c.*, 
                   DATE_FORMAT(c.submitted_at, '%d %b %Y') AS date_fmt,
                   DATEDIFF(NOW(), c.submitted_at) AS age_days,
                   f.rating AS fb_rating, 
                   f.comment AS fb_comment
            FROM complaints c
            LEFT JOIN feedback f ON f.complaint_id = c.complaint_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY FIELD(c.priority,'High','Medium','Low'), c.submitted_at DESC";

    return DB::rows($sql, $params);
}

/** Fetch a single complaint by ID */
function getComplaint(string $id): ?array {
    $row = DB::row(
        "SELECT c.*, 
                DATE_FORMAT(c.submitted_at, '%d %b %Y') AS date_fmt,
                DATEDIFF(NOW(), c.submitted_at) AS age_days,
                f.rating AS fb_rating, 
                f.comment AS fb_comment
         FROM complaints c
         LEFT JOIN feedback f ON f.complaint_id = c.complaint_id
         WHERE c.complaint_id = ?",
        [$id]
    );
    if (!$row) return null;
    $row['timeline'] = getTimeline($id);
    return $row;
}

/** Get timeline events for a complaint */
function getTimeline(string $id): array {
    return DB::rows(
        "SELECT label, DATE_FORMAT(event_date, '%d %b %Y') AS date, is_done
         FROM complaint_timeline WHERE complaint_id = ?
         ORDER BY event_date ASC, id ASC",
        [$id]
    );
}

/** Get dashboard statistics */
function getStats(): array {
    $stats = [
        'Total'       => 0,
        'Pending'     => 0,
        'In Progress' => 0,
        'Resolved'    => 0,
        'Escalated'   => 0,
        'HighPriority'=> 0,
    ];

    $rows = DB::rows("SELECT status, COUNT(*) as count FROM complaints GROUP BY status");
    foreach ($rows as $row) {
        $status = $row['status'];
        if (array_key_exists($status, $stats)) {
            $stats[$status] = (int)$row['count'];
        }
        $stats['Total'] += (int)$row['count'];
    }

    $stats['HighPriority'] = (int)DB::value("SELECT COUNT(*) FROM complaints WHERE priority = 'High'");

    return $stats;
}

/** Fetch Category-wise statistics for Performance Scores */
function getCategoryStats(): array {
    $sql = "SELECT type as category, 
            COUNT(*) as total, 
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
            FROM complaints 
            GROUP BY type";
            
    return DB::rows($sql);
}

// ════════════════════════════════════════════════════════════
// COMPLAINT MUTATIONS
// ════════════════════════════════════════════════════════════

/** Generate a unique ID */
function generateComplaintId(): string {
    $count = (int) DB::value("SELECT COUNT(*) FROM complaints");
    return 'CMP-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

/** Create a new complaint record */
function createComplaint(array $data, ?string $imagePath, ?int $citizenId = null): string {
    $id = generateComplaintId();
    
    $sql = "INSERT INTO complaints 
            (complaint_id, type, description, address, landmark, 
             priority, status, citizen_name, citizen_phone, citizen_id, 
             image_path, lat, lng)
            VALUES (?,?,?,?,?,?,'Pending',?,?,?,?,?,?)";

    $params = [
        $id, $data['type'], $data['description'], $data['address'], 
        $data['landmark'] ?? '', $data['priority'], $data['name'], 
        $data['phone'], $citizenId, $imagePath, $data['lat'] ?? null, $data['lng'] ?? null
    ];

    DB::exec($sql, $params);
    addTimeline($id, 'Complaint Submitted');
    return $id;
}

/** Log a timeline event */
function addTimeline(string $id, string $label, bool $isDone = true): void {
    DB::exec(
        "INSERT INTO complaint_timeline (complaint_id, label, event_date, is_done)
         VALUES (?, ?, NOW(), ?)",
        [$id, $label, (int)$isDone]
    );
}

/** Save feedback and auto-resolve */
function saveFeedback(string $complaintId, int $rating, string $comment): void {
    DB::exec(
        "INSERT INTO feedback (complaint_id, rating, comment, created_at) 
         VALUES (?, ?, ?, NOW())",
        [$complaintId, $rating, $comment]
    );

    DB::exec("UPDATE complaints SET status = 'Resolved' WHERE complaint_id = ?", [$complaintId]);
    addTimeline($complaintId, "Citizen provided feedback. Case marked as Resolved.");
}

// ════════════════════════════════════════════════════════════
// AUTO-ESCALATION
// ════════════════════════════════════════════════════════════

/**
 * Run auto-escalation check.
 *
 * Finds every complaint whose status is still Pending or In Progress AND whose
 * most-recent timeline event is older than $thresholdDays (default: 7).
 * Each matching complaint is:
 *   1. Flipped to status = 'Escalated' in the complaints table.
 *   2. Given a new timeline entry explaining why it was escalated.
 *   3. Logged into the `escalation_notices` table so admins can review it.
 *
 * Call this once per page-load (or via a cron job) at the top of your
 * admin dashboard:
 *     checkAndEscalateComplaints();
 *
 * Required table (add to your schema if not yet present):
 *
 *   CREATE TABLE IF NOT EXISTS escalation_notices (
 *       id            INT AUTO_INCREMENT PRIMARY KEY,
 *       complaint_id  VARCHAR(20) NOT NULL,
 *       reason        TEXT        NOT NULL,
 *       escalated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *       is_read       TINYINT(1)  NOT NULL DEFAULT 0,
 *       INDEX (complaint_id),
 *       INDEX (is_read)
 *   );
 */
function checkAndEscalateComplaints(int $thresholdDays = 7): int {
    // Find stale complaints — not yet escalated or resolved, and last timeline
    // activity is older than the threshold (or they were submitted that long ago
    // and have NO timeline activity at all beyond the initial submission).
    $sql = "
        SELECT c.complaint_id,
               c.type,
               c.citizen_name,
               c.priority,
               DATEDIFF(NOW(), COALESCE(
                   (SELECT MAX(event_date)
                    FROM complaint_timeline t
                    WHERE t.complaint_id = c.complaint_id),
                   c.submitted_at
               )) AS days_since_update
        FROM complaints c
        WHERE c.status IN ('Pending', 'In Progress')
          AND DATEDIFF(NOW(), COALESCE(
                  (SELECT MAX(event_date)
                   FROM complaint_timeline t
                   WHERE t.complaint_id = c.complaint_id),
                  c.submitted_at
              )) >= ?
    ";

    $stale = DB::rows($sql, [$thresholdDays]);
    $count = 0;

    foreach ($stale as $complaint) {
        $id      = $complaint['complaint_id'];
        $days    = (int)$complaint['days_since_update'];
        $reason  = "No update for {$days} day(s). Auto-escalated to senior official for immediate attention.";

        // 1. Update complaint status
        DB::exec(
            "UPDATE complaints SET status = 'Escalated' WHERE complaint_id = ?",
            [$id]
        );

        // 2. Add a visible timeline event
        addTimeline(
            $id,
            "⚠️ Auto-Escalated: No activity for {$days} day(s). Referred to senior official.",
            true
        );

        // 3. Log the escalation notice
        logEscalationNotice($id, $reason);

        $count++;
    }

    return $count; // number of complaints escalated this run
}

/**
 * Insert a record into escalation_notices.
 * Called internally by checkAndEscalateComplaints(); you can also call it
 * manually when an admin manually escalates a complaint.
 */
function logEscalationNotice(string $complaintId, string $reason): void {
    DB::exec(
        "INSERT INTO escalation_notices (complaint_id, reason, escalated_at, is_read)
         VALUES (?, ?, NOW(), 0)",
        [$complaintId, $reason]
    );
}

/**
 * Fetch escalation alert notices for the admin panel.
 *
 * @param bool $unreadOnly  Pass true to show only unread alerts.
 * @return array            Rows from escalation_notices joined with complaints.
 */
function getEscalatedAlerts(bool $unreadOnly = false): array {
    $whereClause = $unreadOnly ? 'AND en.is_read = 0' : '';

    $sql = "
        SELECT en.id           AS notice_id,
               en.complaint_id,
               en.reason,
               DATE_FORMAT(en.escalated_at, '%d %b %Y %H:%i') AS escalated_at_fmt,
               en.is_read,
               c.type          AS complaint_type,
               c.citizen_name,
               c.citizen_phone,
               c.address,
               c.priority,
               DATE_FORMAT(c.submitted_at, '%d %b %Y') AS submitted_fmt
        FROM escalation_notices en
        JOIN complaints c ON c.complaint_id = en.complaint_id
        WHERE 1=1 {$whereClause}
        ORDER BY en.escalated_at DESC
    ";

    return DB::rows($sql);
}

/**
 * Mark one or all escalation notices as read.
 *
 * @param int|null $noticeId  Pass a notice ID to mark one, or null to mark all.
 */
function markEscalationRead(?int $noticeId = null): void {
    if ($noticeId !== null) {
        DB::exec(
            "UPDATE escalation_notices SET is_read = 1 WHERE id = ?",
            [$noticeId]
        );
    } else {
        DB::exec("UPDATE escalation_notices SET is_read = 1");
    }
}

/**
 * Count unread escalation notices — use this for the admin nav badge.
 *
 *   $unread = countUnreadEscalations();   // e.g.  3
 */
function countUnreadEscalations(): int {
    return (int) DB::value(
        "SELECT COUNT(*) FROM escalation_notices WHERE is_read = 0"
    );
}

// ════════════════════════════════════════════════════════════
// HTML BADGE & UI HELPERS
// ════════════════════════════════════════════════════════════

function statusBadge(string $status): string {
    $map = [
        'Pending'     => 'badge-pending',
        'In Progress' => 'badge-inprogress',
        'Resolved'    => 'badge-resolved',
        'Escalated'   => 'badge-escalated',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}

function priorityBadge(string $priority): string {
    $map = ['High' => 'priority-high', 'Medium' => 'priority-medium', 'Low' => 'priority-low'];
    $cls = $map[$priority] ?? 'priority-low';
    return '<span class="priority-badge ' . $cls . '">' . e($priority) . '</span>';
}

function typeIcon(string $type): string {
    $map = [
        'Pothole'      => '🕳️',
        'Garbage'      => '🗑️',
        'Water'        => '💧',
        'Streetlight'  => '💡',
        'Tree'         => '🌳',
        'Drainage'     => '🚧',
        'Construction' => '🏗️',
        'Toilet'       => '🚻',
    ];
    foreach ($map as $key => $icon) {
        if (stripos($type, $key) !== false) return $icon;
    }
    return '📋';
}

function getCategories(): array {
    return [
        '🕳️ Pothole / Road Damage',
        '🗑️ Garbage Mismanagement',
        '💧 Water Supply Interruption',
        '💡 Streetlight Failure',
        '🌳 Tree / Vegetation Issue',
        '🚧 Drainage / Sewage Problem',
        '🏗️ Construction Hazard',
        '🚻 Public Toilet Issue',
        '📶 Other Civic Issue',
    ];
}

/** Form Validation */
function validateComplaintForm(array $data): array {
    $errors = [];
    if (empty($data['type'])) $errors[] = 'Please select a category.';
    if (empty($data['description']) || strlen($data['description']) < 10) $errors[] = 'Description too short.';
    if (empty($data['address'])) $errors[] = 'Address required.';
    if (empty($data['name'])) $errors[] = 'Name required.';
    if (empty($data['phone']) || !preg_match('/^[6-9]\d{9}$/', $data['phone'])) $errors[] = 'Invalid phone number.';
    if (empty($data['lat']) || empty($data['lng'])) $errors[] = 'Pin location on map.';
    
    return $errors;
}

/** Image Upload */
function handleImageUpload(array $file): string {
    $targetDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) throw new RuntimeException("Invalid file type.");

    $filename = uniqid('IMG_', true) . '.' . $ext;
    $targetPath = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) return 'uploads/' . $filename;
    throw new RuntimeException("Save failed.");
}
