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

/** * Get dashboard statistics 
 * UPDATED: Added initialization to prevent "Undefined array key" errors
 */
function getStats(): array {
    // 1. Initialize keys to 0 so the frontend charts don't crash
    $stats = [
        'Total' => 0,
        'Pending' => 0,
        'In Progress' => 0,
        'Resolved' => 0,
        'Escalated' => 0,
        'HighPriority' => 0
    ];

    // 2. Fetch counts per status
    $rows = DB::rows("SELECT status, COUNT(*) as count FROM complaints GROUP BY status");
    foreach ($rows as $row) {
        $status = $row['status'];
        if (array_key_exists($status, $stats)) {
            $stats[$status] = (int)$row['count'];
        }
        $stats['Total'] += (int)$row['count'];
    }

    // 3. Fetch High Priority count specifically
    $stats['HighPriority'] = (int)DB::value("SELECT COUNT(*) FROM complaints WHERE priority = 'High'");

    return $stats;
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
    
    $sql = "INSERT INTO complaints 
            (complaint_id, type, description, address, landmark, 
             priority, status, citizen_name, citizen_phone, citizen_id, 
             image_path, lat, lng)
            VALUES (?,?,?,?,?,?,'Pending',?,?,?,?,?,?)";

    $params = [
        $id,                        // 1
        $data['type'],              // 2
        $data['description'],       // 3
        $data['address'],           // 4
        $data['landmark'] ?? '',    // 5
        $data['priority'],          // 6
        $data['name'],              // 7
        $data['phone'],             // 8
        $citizenId,                 // 9
        $imagePath,                 // 10
        $data['lat'] ?? null,       // 11
        $data['lng'] ?? null        // 12
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

/** * Validate the complaint submission form 
 * UPDATED: Includes GPS validation
 */
function validateComplaintForm(array $data): array {
    $errors = [];
    if (empty($data['type'])) $errors[] = 'Please select a complaint category.';
    if (empty($data['description']) || strlen($data['description']) < 10) $errors[] = 'Please provide a detailed description.';
    if (empty($data['address'])) $errors[] = 'Please provide the location/address.';
    if (empty($data['name'])) $errors[] = 'Reporter name is required.';
    if (empty($data['phone']) || !preg_match('/^[6-9]\d{9}$/', $data['phone'])) $errors[] = 'Valid 10-digit mobile number required.';
    
    // GPS Check
    if (empty($data['lat']) || empty($data['lng'])) {
        $errors[] = 'Please pin the exact location on the map.';
    }
    
    return $errors;
}

/**
 * Handles image uploads for complaints
 */
function handleImageUpload(array $file): string {
    $targetDir = dirname(__DIR__) . '/uploads/';
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        throw new RuntimeException("Invalid file type. Only JPG, PNG, and WebP allowed.");
    }

    $filename = uniqid('IMG_', true) . '.' . $ext;
    $targetPath = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/' . $filename;
    }

    throw new RuntimeException("Failed to save the uploaded image.");
}

