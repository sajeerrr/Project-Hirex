<?php
session_start();
include("../../database/db.php");

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

$sql = "INSERT INTO messages (sender_id, receiver_id, message)
        VALUES ('$sender_id','$receiver_id','$message')";

if($conn->query($sql)){
    $id = $conn->insert_id;
    $res = $conn->query("SELECT * FROM messages WHERE id='$id'");
    $msg = $res->fetch_assoc();

    echo json_encode([
        "success"=>true,
        "message"=>$msg
    ]);
}