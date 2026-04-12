<?php
session_start();
include("../../database/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["success"=>false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$worker_id = $_POST['worker_id'];
$date = $_POST['date'];
$time = $_POST['time'];

$sql = "INSERT INTO bookings (user_id, worker_id, booking_date, booking_time)
        VALUES ('$user_id','$worker_id','$date','$time')";

if($conn->query($sql)){
    echo json_encode(["success"=>true]);
}else{
    echo json_encode(["success"=>false]);
}