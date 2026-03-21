<?php
/**
 * SVMS - Shared Utility Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Handle file upload
 */
function uploadFile($file, $directory, $allowedTypes = null) {
    if (!$allowedTypes) {
        $allowedTypes = ALLOWED_IMAGE_TYPES;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large (max 5MB)'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    $dir = UPLOAD_DIR . $directory . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $path = $dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => true, 'filename' => $filename, 'path' => '/uploads/' . $directory . '/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

/**
 * Get paginated results
 */
function paginate($query, $params, $page = 1, $perPage = 15) {
    $pdo = getDBConnection();
    
    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as countTable";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages ?: 1));
    $offset = ($page - 1) * $perPage;
    
    // Get data
    $dataQuery = $query . " LIMIT $perPage OFFSET $offset";
    $dataStmt = $pdo->prepare($dataQuery);
    $dataStmt->execute($params);
    $data = $dataStmt->fetchAll();
    
    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages
    ];
}

/**
 * Generate pagination HTML
 */
function renderPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous
    $prevClass = $pagination['page'] <= 1 ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevClass . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($pagination['page'] - 1) . '">&laquo;</a></li>';
    
    // Pages
    $start = max(1, $pagination['page'] - 2);
    $end = min($pagination['total_pages'], $pagination['page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = $i === $pagination['page'] ? 'active' : '';
        $html .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next
    $nextClass = $pagination['page'] >= $pagination['total_pages'] ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextClass . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($pagination['page'] + 1) . '">&raquo;</a></li>';
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Generate UUID v4
 */
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Get severity badge HTML
 */
function severityBadge($severity) {
    $classes = [
        'minor' => 'badge-soft-warning',
        'major' => 'badge-soft-orange',
        'critical' => 'badge-soft-danger'
    ];
    $class = $classes[$severity] ?? 'badge-soft-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst($severity) . '</span>';
}

/**
 * Get status badge HTML
 */
function statusBadge($status) {
    $classes = [
        'pending' => 'badge-soft-warning',
        'reviewed' => 'badge-soft-info',
        'resolved' => 'badge-soft-success',
        'dismissed' => 'badge-soft-secondary',
        'active' => 'badge-soft-success',
        'inactive' => 'badge-soft-secondary',
        'completed' => 'badge-soft-success',
        'cancelled' => 'badge-soft-danger'
    ];
    $class = $classes[$status] ?? 'badge-soft-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst($status) . '</span>';
}

/**
 * Get action type badge
 */
function actionBadge($type) {
    $classes = [
        'warning' => 'badge-soft-warning',
        'detention' => 'badge-soft-orange',
        'suspension' => 'badge-soft-danger',
        'expulsion' => 'badge-soft-dark',
        'community_service' => 'badge-soft-info',
        'counseling' => 'badge-soft-primary'
    ];
    $labels = [
        'warning' => 'Warning',
        'detention' => 'Detention',
        'suspension' => 'Suspension',
        'expulsion' => 'Expulsion',
        'community_service' => 'Community Service',
        'counseling' => 'Counseling'
    ];
    $class = $classes[$type] ?? 'badge-soft-secondary';
    $label = $labels[$type] ?? ucfirst($type);
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/**
 * Flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Get student violation count
 */
function getStudentViolationCount($studentId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM violations WHERE student_id = ?");
    $stmt->execute([$studentId]);
    return $stmt->fetch()['count'];
}

/**
 * Get initials from name
 */
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) $initials .= strtoupper($word[0]);
    }
    return substr($initials, 0, 2);
}

/**
 * Generate a Base64 Data URI for the manifest with absolute URLs
 * Required to bypass InfinityFree security while avoiding relative URL resolution errors in Data URIs
 */
function getManifestDataUri() {
    $manifestPath = __DIR__ . '/../manifest.json';
    if (!file_exists($manifestPath)) return '';
    
    $manifestContent = file_get_contents($manifestPath);
    $manifest = json_decode($manifestContent, true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($manifest)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        
        // Convert relative paths to absolute paths
        if (isset($manifest['start_url'])) $manifest['start_url'] = $baseUrl . $manifest['start_url'];
        if (isset($manifest['id'])) $manifest['id'] = $baseUrl . $manifest['id'];
        if (isset($manifest['scope'])) $manifest['scope'] = $baseUrl . rtrim($manifest['scope'], '/') . '/';
        
        if (isset($manifest['icons']) && is_array($manifest['icons'])) {
            foreach ($manifest['icons'] as &$icon) {
                if (isset($icon['src']) && strpos($icon['src'], '/') === 0) {
                    $icon['src'] = $baseUrl . $icon['src'];
                }
            }
        }
        
        $manifestContent = json_encode($manifest);
    }
    
    return 'data:application/manifest+json;base64,' . base64_encode($manifestContent);
}

/**
 * Get user avatar HTML (Image or Initials fallback)
 */
function getAvatarHtml($avatarPath, $fullName, $containerClass = 'user-avatar', $extraStyle = '') {
    if (!empty($avatarPath)) {
        return '<img src="' . BASE_PATH . $avatarPath . '" alt="Avatar" class="' . $containerClass . '" style="object-fit: cover; border-radius: 50%; ' . $extraStyle . '">';
    }
    return '<div class="' . $containerClass . '" ' . ($extraStyle ? 'style="' . $extraStyle . '"' : '') . '>' . getInitials($fullName) . '</div>';
}
