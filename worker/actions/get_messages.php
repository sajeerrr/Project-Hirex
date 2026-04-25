<?php
session_start();
include('../../database/db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['worker_id'])) { echo json_encode([]); exit; }
$worker_id=(int)$_SESSION['worker_id'];
$user_id=(int)($_GET['user_id']??0);
$last_id=(int)($_GET['last_id']??0);

if (!$user_id) { echo json_encode([]); exit; }

$r=$conn->query("SELECT m.id,m.message,m.sender_type,m.created_at
    FROM messages m
    WHERE m.id>$last_id
    AND ((m.sender_id=$worker_id AND m.sender_type='worker' AND m.receiver_id=$user_id AND m.receiver_type='user')
      OR (m.sender_id=$user_id AND m.sender_type='user' AND m.receiver_id=$worker_id AND m.receiver_type='worker'))
    ORDER BY m.created_at ASC");

$msgs=[];
if ($r) {
    // Mark received as read
    $conn->query("UPDATE messages SET is_read=1 WHERE sender_id=$user_id AND sender_type='user' AND receiver_id=$worker_id AND receiver_type='worker' AND id>$last_id");
    while ($row=$r->fetch_assoc()) {
        $msgs[]=['id'=>$row['id'],'message'=>htmlspecialchars($row['message'],ENT_QUOTES,'UTF-8'),'mine'=>$row['sender_type']==='worker','time'=>date('h:i A',strtotime($row['created_at']))];
    }
}
echo json_encode($msgs);
