<?php
session_start();
include("../../database/db.php");

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;

if ($worker_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid worker ID']);
    exit;
}

// Check if already saved
$checkQuery = "SELECT id FROM saved_workers WHERE user_id='$user_id' AND worker_id='$worker_id'";
$checkResult = $conn->query($checkQuery);

if ($checkResult && $checkResult->num_rows > 0) {
    // Already saved, so we remove it (toggle off)
    $deleteQuery = "DELETE FROM saved_workers WHERE user_id='$user_id' AND worker_id='$worker_id'";
    if ($conn->query($deleteQuery)) {
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove worker']);
    }
} else {
    // Not saved, so we insert it (toggle on)
    $insertQuery = "INSERT INTO saved_workers (user_id, worker_id) VALUES ('$user_id', '$worker_id')";
    if ($conn->query($insertQuery)) {
        echo json_encode(['success' => true, 'action' => 'saved']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save worker']);
    }
}
?>
