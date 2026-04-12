<?php
session_start();
include("../../database/db.php");

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$with = intval($_GET['with']);
$after = intval($_GET['after'] ?? 0);

$sql = "SELECT * FROM messages 
WHERE (
    (sender_id='$user_id' AND sender_type='user' AND receiver_id='$with' AND receiver_type='worker')
    OR
    (sender_id='$with' AND sender_type='worker' AND receiver_id='$user_id' AND receiver_type='user')
)
AND id > '$after'
ORDER BY id ASC";

$result = $conn->query($sql);

$messages = [];
while($row = $result->fetch_assoc()){
    $messages[] = $row;
}

// mark as read
$conn->query("UPDATE messages SET is_read=1 
WHERE receiver_id='$user_id' AND receiver_type='user'");

echo json_encode(['messages'=>$messages]);