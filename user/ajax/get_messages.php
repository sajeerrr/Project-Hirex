<?php
session_start();
include("../../database/db.php");

$user_id = $_SESSION['user_id'];
$with = $_GET['with'];
$after = $_GET['after'] ?? 0;

$sql = "SELECT * FROM messages 
        WHERE ((sender_id='$user_id' AND receiver_id='$with') 
        OR (sender_id='$with' AND receiver_id='$user_id'))
        AND id > '$after'
        ORDER BY id ASC";

$result = $conn->query($sql);

$messages = [];

while($row = $result->fetch_assoc()){
    $messages[] = $row;
}

echo json_encode(["messages"=>$messages]);