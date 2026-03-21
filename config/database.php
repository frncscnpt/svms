<?php
/**
 * SVMS - Database Configuration
 * Lyceum of Subic Bay
 */

date_default_timezone_set('Asia/Manila');

define('DB_HOST', 'localhost');
define('DB_NAME', 'svms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 */
function getDBConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Try to set Manila timezone. Silently ignore if the host restricts it.
            try {
                $pdo->exec("SET time_zone = '+08:00'");
            } catch (PDOException $tzError) {
                // Gracefully ignored - shared hosts like InfinityFree don't allow this
            }
        }
        catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed. Please try again later.']));
        }
    }

    return $pdo;
}

/**
 * Application Configuration
 */
// Flawless Base Path Detection (Symlink, Case, and Server independent)
$scriptName = $_SERVER['SCRIPT_NAME'];
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$appDir = str_replace('\\', '/', dirname(__DIR__));

// The relative path from the app root to the currently executing file
$trailingPath = str_replace($appDir, '', $scriptFile); 

// Strip the trailing path from SCRIPT_NAME to get the true network base path
$basePath = '';
if (!empty($trailingPath) && substr_compare($scriptName, $trailingPath, -strlen($trailingPath), null, true) === 0) {
    $basePath = substr($scriptName, 0, -strlen($trailingPath));
} else {
    // Absolute fallback
    $basePath = (stripos($scriptName, '/svms/') === 0) ? substr($scriptName, 0, 5) : '';
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

define('BASE_PATH', $basePath);
define('APP_NAME', 'SVMS');
define('APP_FULL_NAME', 'Student Violation Management System');
define('SCHOOL_NAME', 'Lyceum of Subic Bay');
define('APP_VERSION', '1.0.0');
define('APP_URL', $protocol . '://' . $host . $basePath);

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EVIDENCE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4']);

// QR Code settings
define('QR_CODE_DIR', __DIR__ . '/../uploads/qrcodes/');
define('QR_PREFIX', 'LSB-STU-');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'SVMS_SESSION');
