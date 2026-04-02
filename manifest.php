<?php
/**
 * Serve manifest.json with proper headers
 * Workaround for InfinityFree blocking direct .json access
 */

// Clean any output buffer and start fresh
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set headers
header('Content-Type: application/manifest+json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');

$manifestPath = __DIR__ . '/manifest.json';

if (file_exists($manifestPath)) {
    $content = file_get_contents($manifestPath);
    
    // Validate JSON
    $decoded = json_decode($content);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo $content;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid JSON in manifest']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Manifest not found']);
}

ob_end_flush();
exit;
