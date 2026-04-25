<?php
session_start();
include('../../database/db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['worker_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$worker_id=(int)$_SESSION['worker_id'];

if ($_SERVER['REQUEST_METHOD']!=='POST') { echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }
$booking_id=(int)($_POST['booking_id']??0);
if (!$booking_id) { echo json_encode(['success'=>false,'message'=>'Invalid booking ID']); exit; }

$check=$conn->prepare("SELECT id FROM bookings WHERE id=? AND worker_id=? AND status='pending'");
$check->bind_param('ii',$booking_id,$worker_id); $check->execute();
$exists=$check->get_result()->num_rows>0; $check->close();

if (!$exists) { echo json_encode(['success'=>false,'message'=>'Booking not found or not pending']); exit; }

$upd=$conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=? AND worker_id=?");
$upd->bind_param('ii',$booking_id,$worker_id); $upd->execute(); $upd->close();
echo json_encode(['success'=>true,'message'=>'Job accepted successfully']);
