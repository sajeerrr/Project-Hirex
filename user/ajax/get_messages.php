<?php
session_start();
include("../../database/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$user_id    = intval($_SESSION['user_id']);
$partner_id = isset($_GET['with'])  ? intval($_GET['with'])  : 0;
$after_id   = isset($_GET['after']) ? intval($_GET['after']) : 0;

if (!$partner_id) {
    echo json_encode(['messages' => []]); exit;
}

// Mark messages as read (worker → user)
$conn->query("
    UPDATE messages SET is_read=1
    WHERE sender_id='$partner_id' AND sender_type='worker'
      AND receiver_id='$user_id'  AND receiver_type='user'
      AND is_read=0
");

$afterClause = $after_id > 0 ? "AND id > '$after_id'" : "";

$sql = "
    SELECT id, sender_id, sender_type, receiver_id, receiver_type, message, is_read, created_at
    FROM messages
    WHERE (
        (sender_id='$user_id'    AND sender_type='user'   AND receiver_id='$partner_id' AND receiver_type='worker')
     OR (sender_id='$partner_id' AND sender_type='worker' AND receiver_id='$user_id'    AND receiver_type='user')
    )
    $afterClause
    ORDER BY created_at ASC, id ASC
    LIMIT 300
";

$result = $conn->query($sql);
$messages = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

echo json_encode(['messages' => $messages]);