<?php
/**
 * Media View API
 * GET /api/media/view?filename={filename}
 * 
 * Serves uploaded media files
 */

// Get filename from query parameter
$filename = $_GET['filename'] ?? $_GET['file'] ?? null;

if (!$filename) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Filename required']);
    exit();
}

// Sanitize filename to prevent directory traversal
$filename = basename($filename);

// Define storage directory based on type
$type = $_GET['type'] ?? '';
$storageDir = '/home/sharipbf/public_html/storage/media/';

// Check if this is a background image
if ($type === 'background') {
    $storageDir = '/home/sharipbf/public_html/storage/media/backgrounds/';
}

$filepath = $storageDir . $filename;

// Check if file exists
if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit();
}

// Get MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filepath);
finfo_close($finfo);

// Serve the file
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
header('Content-Disposition: inline; filename="' . $filename . '"');

readfile($filepath);
exit();

