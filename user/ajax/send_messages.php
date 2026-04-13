<?php
session_start();
include("../../database/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']); exit;
}

$sender_id   = intval($_SESSION['user_id']);
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message     = isset($_POST['message'])     ? trim($_POST['message'])       : '';

if (!$receiver_id || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Missing fields']); exit;
}

$safeMsg = $conn->real_escape_string($message);

// user → worker, with sender_type and receiver_type
$sql = "
    INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, message, is_read, created_at)
    VALUES ('$sender_id', 'user', '$receiver_id', 'worker', '$safeMsg', 0, NOW())
";

if ($conn->query($sql)) {
    $new_id = $conn->insert_id;
    $row = $conn->query("SELECT * FROM messages WHERE id='$new_id'")->fetch_assoc();
    echo json_encode(['success' => true, 'message' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
}