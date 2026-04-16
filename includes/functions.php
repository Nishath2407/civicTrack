<?php
/**
 * CivicTrack — includes/functions.php
 * All shared helpers: complaints, citizens, OTP, validation, and badges.
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
/** Get dashboard statistics */
function getStats(): array {
   return DB::row(
    "SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status = 'Escalated' THEN 1 ELSE 0 END) AS escalated,
        SUM(CASE WHEN priority = 'High' THEN 1 ELSE 0 END) AS highPriority
     FROM complaints"
    ) ?: ['total'=>0, 'pending'=>0, 'in_progress'=>0, 'resolved'=>0, 'escalated'=>0, 'highPriority'=>0];
}

// ════════════════════════════════════════════════════════════
// COMPLAINT MUTATIONS
// ════════════════════════════════════════════════════════════

/** Generate a unique ID (CMP-001, CMP-002, etc) */
function generateComplaintId(): string {
    $count = (int) DB::value("SELECT COUNT(*) FROM complaints");
    return 'CMP-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

/** Create a new complaint record */
function createComplaint(array $data, ?string $imagePath, ?int $citizenId = null): string {
    $id = generateComplaintId();
    DB::exec(
        "INSERT INTO complaints 
            (complaint_id, type, description, address, landmark, 
             priority, status, citizen_name, citizen_phone, citizen_id, image_path)
         VALUES (?,?,?,?,?,?,'Pending',?,?,?,?)",
        [
            $id,
            $data['type'],
            $data['description'],
            $data['address'],
            $data['landmark'] ?? '',
            $data['priority'],
            $data['name'],
            $data['phone'],
            $citizenId,
            $imagePath,
        ]
    );
    addTimeline($id, 'Complaint Submitted');
    return $id;
}

/** Log a timeline event */
function addTimeline(string $id, string $label, bool $isDone = true): void {
    DB::exec(
        "INSERT INTO complaint_timeline (complaint_id, label, event_date, is_done)
         VALUES (?, ?, CURDATE(), ?)",
        [$id, $label, (int)$isDone]
    );
}

/** Save citizen feedback and auto-resolve the complaint */
function saveFeedback(string $complaintId, int $rating, string $comment): void {
    DB::exec(
        "INSERT INTO feedback (complaint_id, rating, comment, created_at) 
         VALUES (?, ?, ?, NOW())",
        [$complaintId, $rating, $comment]
    );

    DB::exec(
        "UPDATE complaints SET status = 'Resolved' WHERE complaint_id = ?",
        [$complaintId]
    );

    addTimeline($complaintId, "Citizen provided feedback. Case marked as Resolved.");
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

/** Validate the complaint submission form */
function validateComplaintForm(array $data): array {
    $errors = [];
    if (empty($data['type'])) $errors[] = 'Please select a complaint category.';
    if (empty($data['description']) || strlen($data['description']) < 10) $errors[] = 'Please provide a detailed description.';
    if (empty($data['address'])) $errors[] = 'Please provide the location/address.';
    if (empty($data['name'])) $errors[] = 'Reporter name is required.';
    if (empty($data['phone']) || !preg_match('/^[6-9]\d{9}$/', $data['phone'])) $errors[] = 'Valid 10-digit mobile number required.';
    return $errors;
}

/**
 * Handles image uploads for complaints
 * Saves to root /uploads/ folder
 */
function handleImageUpload(array $file): string {
    // 1. Setup the target directory (absolute path to root)
    $targetDir = dirname(__DIR__) . '/uploads/';
    
    // 2. Create folder if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // 3. Validate file type
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        throw new RuntimeException("Invalid file type. Only JPG, PNG, and WebP allowed.");
    }

    // 4. Create unique filename
    $filename = uniqid('IMG_', true) . '.' . $ext;
    $targetPath = $targetDir . $filename;

    // 5. Move the file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/' . $filename;
    }

    throw new RuntimeException("Failed to save the uploaded image.");
}
/** Temporary helper if you don't have a translation file yet */
