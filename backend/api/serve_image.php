<?php
require_once('../config/security.php');
require_once('../config/database.php');
require_once('../config/constants.php');
require_once('../config/auth.php');

// Only authenticated admins can retrieve uploaded images
require_admin_auth();

$filename = basename($_GET['file'] ?? '');
if (empty($filename) || !preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png)$/i', $filename)) {
    http_response_code(404);
    exit('Not found.');
}

$filePath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('Not found.');
}

// Validate MIME type before serving
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($filePath);
if (!in_array($mime, ['image/jpeg', 'image/png'])) {
    http_response_code(403);
    exit('Forbidden.');
}

header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=3600');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
