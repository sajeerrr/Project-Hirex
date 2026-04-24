<?php
session_start();
include("../../database/db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Invalid method']); exit;
}

$user_id   = intval($_SESSION['user_id']);
$worker_id = intval($_POST['worker_id'] ?? 0);
$date      = $conn->real_escape_string($_POST['date'] ?? '');
$time      = $conn->real_escape_string($_POST['time'] ?? '');
$duration  = intval($_POST['duration'] ?? 1);
$address   = $conn->real_escape_string(trim($_POST['address'] ?? ''));
$notes     = $conn->real_escape_string(trim($_POST['notes'] ?? ''));

if (!$worker_id || !$date || !$time || !$address) {
    echo json_encode(['success'=>false,'error'=>'Please fill all required fields.']); exit;
}

// Validate date is not in the past
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    echo json_encode(['success'=>false,'error'=>'Please select a future date.']); exit;
}

// Check if worker is available
$avail = $conn->query("SELECT available, price FROM workers WHERE id='$worker_id'")->fetch_assoc();
if (!$avail || !$avail['available']) {
    echo json_encode(['success'=>false,'error'=>'Worker is currently unavailable.']); exit;
}

// Check no duplicate active booking
$dup = $conn->query("SELECT id FROM bookings WHERE user_id='$user_id' AND worker_id='$worker_id' AND status IN ('pending','confirmed') LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    echo json_encode(['success'=>false,'error'=>'You already have an active booking with this worker.']); exit;
}

$total = intval($avail['price']) * $duration;
$datetime = $date . ' ' . $time . ':00';

$sql = "INSERT INTO bookings (user_id, worker_id, booking_date, duration_hours, address, notes, total_amount, status, created_at)
        VALUES ('$user_id','$worker_id','$datetime','$duration','$address','$notes','$total','pending',NOW())";

if ($conn->query($sql)) {
    echo json_encode(['success'=>true,'booking_id'=>$conn->insert_id,'total'=>$total]);
} else {
    echo json_encode(['success'=>false,'error'=>'Database error: '.$conn->error]);
}