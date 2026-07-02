<?php
/**
 * Image Upload API Endpoint
 */

header('Content-Type: application/json');
require_once 'config.php';
handleCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image file uploaded']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Upload failed']);
    exit;
}

$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image too large (max 5MB)']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowedMimeTypes[$mimeType])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and WEBP images are allowed']);
    exit;
}

$extension = $allowedMimeTypes[$mimeType];
$filename = 'product_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

// ── Upload to Cloudflare R2 (primary) ────────────────
$r2Result = uploadToR2($file['tmp_name'], $filename, $mimeType);
if ($r2Result['success']) {
    echo json_encode(['success' => true, 'url' => $r2Result['url']]);
    exit;
}

// ── Fallback: local upload ───────────────────────────
$uploadsDir = dirname(__DIR__) . '/uploads';
if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
    exit;
}

$destination = $uploadsDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    exit;
}

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $isSecure ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$publicUrl = $scheme . '://' . $host . '/uploads/' . $filename;

echo json_encode([
    'success' => true,
    'url' => $publicUrl
]);
