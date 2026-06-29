<?php
session_start();
require_once(__DIR__ . '/../../database/db.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$verificationId = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';
$columns = [
    'government_id' => 'government_id',
    'selfie' => 'selfie',
    'certificate' => 'certificate'
];

if ($verificationId <= 0 || !isset($columns[$type])) {
    http_response_code(400);
    exit('Invalid request');
}

// The column comes only from the fixed allowlist above.
$column = $columns[$type];
$stmt = $conn->prepare("SELECT {$column} AS file_path FROM worker_verifications WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $verificationId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record || empty($record['file_path'])) {
    http_response_code(404);
    exit('File not found');
}

$uploadRoot = realpath(__DIR__ . '/../../hirex-ai/uploads');
$storedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $record['file_path']);
$candidate = realpath(__DIR__ . '/../../hirex-ai/' . ltrim($storedPath, DIRECTORY_SEPARATOR));

if (
    $uploadRoot === false ||
    $candidate === false ||
    !is_file($candidate) ||
    !is_readable($candidate) ||
    strpos($candidate, $uploadRoot . DIRECTORY_SEPARATOR) !== 0
) {
    http_response_code(404);
    exit('File not found');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($candidate);
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/avif'];

if (!in_array($mime, $allowedMimeTypes, true)) {
    http_response_code(415);
    exit('Unsupported file type');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($candidate));
header('Content-Disposition: inline; filename="' . basename($candidate) . '"');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
readfile($candidate);
exit;
