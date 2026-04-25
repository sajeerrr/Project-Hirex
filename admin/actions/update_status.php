<?php
session_start();
require_once(__DIR__ . '/../../database/db.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: ../dashboard.php?error=csrf");
    exit;
}

$allowed = [
    'users' => ['active', 'inactive', 'banned'],
    'workers' => ['active', 'pending', 'approved', 'rejected', 'suspended'],
    'bookings' => ['pending', 'confirmed', 'completed', 'cancelled'],
    'contacts' => ['pending', 'open', 'resolved'],
    'admin' => ['active', 'inactive'],
];

$table = $_POST['table'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!isset($allowed[$table]) || !in_array($status, $allowed[$table], true) || $id <= 0) {
    header("Location: ../dashboard.php?error=invalid_status");
    exit;
}

$columnStmt = $conn->prepare("SELECT COUNT(*) AS total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'status'");
$columnStmt->bind_param("s", $table);
$columnStmt->execute();
$hasStatus = (int) $columnStmt->get_result()->fetch_assoc()['total'] > 0;
$columnStmt->close();

if ($hasStatus) {
    $sql = "UPDATE `$table` SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
}

$base = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';
// Strip any existing query params we add, then append updated=1
$base = preg_replace('/[?&](updated|error)=[^&]*/', '', $base);
$sep  = strpos($base, '?') !== false ? '&' : '?';
header("Location: " . $base . $sep . "updated=1");
exit;
