<?php
session_start();
include("../../database/db.php");

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit;
}

$sender_id = intval($_SESSION['user_id']);
$receiver_id = intval($_POST['receiver_id']);
$message = trim($_POST['message']);

// ALWAYS user sending from this panel
$sender_type = 'user';
$receiver_type = 'worker';

if($message == ''){
    echo json_encode(['success'=>false,'error'=>'Empty message']); exit;
}

// 🚫 BLOCK same role chat (extra safety)
if($sender_type == $receiver_type){
    echo json_encode(['success'=>false,'error'=>'Invalid chat type']); exit;
}

// CHECK worker exists
$check = $conn->query("SELECT id FROM workers WHERE id='$receiver_id'");
if(!$check || $check->num_rows == 0){
    echo json_encode(['success'=>false,'error'=>'Worker not found']); exit;
}

// INSERT
$sql = "INSERT INTO messages 
(sender_id, sender_type, receiver_id, receiver_type, message)
VALUES ('$sender_id','user','$receiver_id','worker','$message')";

if($conn->query($sql)){
    $id = $conn->insert_id;
    $res = $conn->query("SELECT * FROM messages WHERE id='$id'");
    echo json_encode(['success'=>true,'message'=>$res->fetch_assoc()]);
}else{
    echo json_encode(['success'=>false,'error'=>$conn->error]);
}