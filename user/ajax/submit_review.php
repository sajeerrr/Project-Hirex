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
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($worker_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid worker ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Please provide a rating between 1 and 5 stars']);
    exit;
}

// Sanitize comment
$comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
$comment = $conn->real_escape_string($comment);

// Insert the review
$insertQuery = "INSERT INTO reviews (user_id, worker_id, rating, comment) VALUES ('$user_id', '$worker_id', '$rating', '$comment')";
if ($conn->query($insertQuery)) {
    // Optionally update the average rating and review count in workers table
    $avgQuery = "SELECT AVG(rating) as avg_rating, COUNT(id) as count FROM reviews WHERE worker_id='$worker_id'";
    $avgResult = $conn->query($avgQuery);
    if ($avgResult && $row = $avgResult->fetch_assoc()) {
        $newAvg = round($row['avg_rating'], 1);
        $newCount = intval($row['count']);
        $updateWorker = "UPDATE workers SET rating='$newAvg', reviews='$newCount' WHERE id='$worker_id'";
        $conn->query($updateWorker);
    }

    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit review: ' . $conn->error]);
}
?>
