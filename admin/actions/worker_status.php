<?php
session_start();
require_once('../../database/db.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: ../dashboard.php?error=csrf");
    exit;
}

$workerId = isset($_POST['worker_id']) ? (int) $_POST['worker_id'] : 0;
$action = $_POST['action'] ?? '';
$newStatus = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : '');

if ($workerId <= 0 || $newStatus === '') {
    header("Location: ../dashboard.php?error=invalid_worker");
    exit;
}

$columnStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'workers' AND column_name = 'status'
");
$columnStmt->execute();
$hasStatus = (int) $columnStmt->get_result()->fetch_assoc()['total'] > 0;
$columnStmt->close();

if (!$hasStatus) {
    header("Location: ../dashboard.php?error=worker_status_missing");
    exit;
}

$stmt = $conn->prepare("UPDATE workers SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $workerId);
$stmt->execute();
$stmt->close();

header("Location: ../dashboard.php?success=worker_" . $newStatus);
exit;

