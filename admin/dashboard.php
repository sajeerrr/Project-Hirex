<?php
/**
 * HireX Admin Dashboard - Complete Single File (dashboard.php)
 * All-in-one admin dashboard with stats, tables, and activity log
 */

session_start();
require_once('../database/db.php');

// Security: Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function tableExists($conn, $table) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
    ");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $exists = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
    $stmt->close();
    return $exists;
}

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $exists = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
    $stmt->close();
    return $exists;
}

function scalarQuery($conn, $sql, $fallback = 0) {
    $result = $conn->query($sql);
    if (!$result) {
        return $fallback;
    }
    $row = $result->fetch_assoc();
    if (!$row) {
        return $fallback;
    }
    $value = reset($row);
    return $value === null ? $fallback : $value;
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$adminStatusFilter = columnExists($conn, 'admin', 'status') ? " AND status = 'active'" : "";
$admin_stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?" . $adminStatusFilter);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin = $admin_stmt->get_result()->fetch_assoc();
$admin_stmt->close();

if (!$admin) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$adminName = htmlspecialchars($admin['name'] ?? 'Admin');
$adminEmail = htmlspecialchars($admin['email'] ?? '');
$adminRole = htmlspecialchars($admin['role'] ?? 'admin');

// Generate user initial for avatar
$adminInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $adminName), 0, 1));
$adminInitial = !empty($adminInitial) ? $adminInitial : 'A';

// Get admin photo path
$adminPhoto = null;
if (isset($admin['photo']) && !empty($admin['photo'])) {
    if (filter_var($admin['photo'], FILTER_VALIDATE_URL)) {
        $adminPhoto = $admin['photo'];
    } else {
        $adminPhoto = '../assets/images/admin/' . $admin['photo'];
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$hasComplaintsTable = tableExists($conn, 'complaints');
$hasContactsTable = tableExists($conn, 'contacts');
$hasPaymentsTable = tableExists($conn, 'payments');
$hasRequestsTable = tableExists($conn, 'requests');
$hasAdminLogsTable = tableExists($conn, 'admin_logs');
$hasUserActivityTable = tableExists($conn, 'user_activity');
$workersHaveStatus = columnExists($conn, 'workers', 'status');
$workersHaveCreatedAt = columnExists($conn, 'workers', 'created_at');

// Get pending support/complaint count for notification badge.
if ($hasComplaintsTable) {
    $pendingComplaints = scalarQuery($conn, "SELECT COUNT(*) FROM complaints WHERE status IN ('open', 'pending', 'under_review')");
} elseif ($hasContactsTable) {
    $pendingComplaints = scalarQuery($conn, "SELECT COUNT(*) FROM contacts WHERE status IN ('open', 'pending')");
} else {
    $pendingComplaints = 0;
}

// ============================================
// CALCULATE DASHBOARD STATS
// ============================================

$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$totalWorkers = $conn->query("SELECT COUNT(*) as total FROM workers")->fetch_assoc()['total'];
$totalBookings = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
$pendingApprovals = $workersHaveStatus ? scalarQuery($conn, "SELECT COUNT(*) FROM workers WHERE status='pending'") : 0;

if ($hasComplaintsTable) {
    $openDisputes = scalarQuery($conn, "SELECT COUNT(*) FROM complaints WHERE status IN ('open', 'under_review')");
} elseif ($hasContactsTable) {
    $openDisputes = scalarQuery($conn, "SELECT COUNT(*) FROM contacts WHERE status IN ('open', 'pending')");
} else {
    $openDisputes = 0;
}

if ($hasPaymentsTable) {
    $totalRevenue = scalarQuery($conn, "SELECT SUM(amount) FROM payments WHERE status='completed'");
} elseif ($hasRequestsTable) {
    $totalRevenue = scalarQuery($conn, "SELECT SUM(amount) FROM requests WHERE status='completed'");
} else {
    $totalRevenue = scalarQuery($conn, "SELECT SUM(total_amount) FROM bookings WHERE status='completed'");
}

// ============================================
// RECENT BOOKINGS (Last 5)
// ============================================

$recentBookingsQuery = "
    SELECT b.id, b.status, b.booking_date, b.total_amount, b.created_at,
           u.name as user_name, u.photo as user_photo,
           w.name as worker_name, w.photo as worker_photo, w.role
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN workers w ON b.worker_id = w.id
    ORDER BY b.created_at DESC
    LIMIT 5
";
$recentBookings = $conn->query($recentBookingsQuery);

// ============================================
// PENDING WORKER APPROVALS (Last 5)
// ============================================

$pendingWorkersQuery = "
    SELECT id, name, role, location, " . ($workersHaveCreatedAt ? "created_at" : "NULL AS created_at") . "
    FROM workers
    WHERE " . ($workersHaveStatus ? "status = 'pending'" : "1 = 0") . "
    ORDER BY " . ($workersHaveCreatedAt ? "created_at DESC" : "id DESC") . "
    LIMIT 5
";
$pendingWorkers = $conn->query($pendingWorkersQuery);

// ============================================
// RECENT ACTIVITY LOG (Last 10)
// ============================================

if ($hasAdminLogsTable) {
    $adminLogsHaveTargetType = columnExists($conn, 'admin_logs', 'target_type');
    $adminLogsHaveTargetId = columnExists($conn, 'admin_logs', 'target_id');
    $adminLogsHaveDetails = columnExists($conn, 'admin_logs', 'details');
    $activityActionColumn = $adminLogsHaveDetails
        ? "CONCAT(al.action, CASE WHEN al.details IS NOT NULL AND al.details != '' THEN CONCAT(' - ', al.details) ELSE '' END) AS action"
        : "al.action";
    $activityLogQuery = "
        SELECT " . $activityActionColumn . ",
               " . ($adminLogsHaveTargetType ? "al.target_type" : "NULL") . " AS target_type,
               " . ($adminLogsHaveTargetId ? "al.target_id" : "NULL") . " AS target_id,
               al.created_at,
               a.name as admin_name
        FROM admin_logs al
        LEFT JOIN admin a ON al.admin_id = a.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ";
} elseif ($hasUserActivityTable) {
    $activityLogQuery = "
        SELECT ua.activity AS action, 'user' AS target_type, ua.user_id AS target_id, ua.created_at, u.name AS admin_name
        FROM user_activity ua
        LEFT JOIN users u ON ua.user_id = u.id
        ORDER BY ua.created_at DESC
        LIMIT 10
    ";
} else {
    $activityLogQuery = null;
}
$activityLogs = $activityLogQuery ? $conn->query($activityLogQuery) : false;

// ============================================
// HELPER FUNCTIONS
// ============================================

function getIcon($name, $size = 20, $class = '') {
    $icons = [
        'grid' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'dashboard' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'user' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'users' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'workers' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'calendar' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'star' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'alert-circle' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        'card' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'layers' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
        'map' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
        'bar-chart' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'activity' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
        'settings' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'shield' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'logout' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'bell' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'moon' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'sun' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'menu' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'arrow-right' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
        'check' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'x' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'clock' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'dollar-sign' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'flag' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
        'trash-2' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function getStatusClass($status) {
    $classes = [
        'approved' => 'status-approved',
        'pending' => 'status-pending',
        'rejected' => 'status-rejected',
        'suspended' => 'status-suspended',
        'active' => 'status-approved',
        'banned' => 'status-suspended',
        'inactive' => 'status-pending',
        'completed' => 'status-approved',
        'confirmed' => 'status-approved',
        'cancelled' => 'status-suspended',
        'disputed' => 'status-pending',
        'open' => 'status-pending',
        'under_review' => 'status-pending',
        'resolved' => 'status-approved',
        'dismissed' => 'status-rejected',
    ];
    return $classes[$status] ?? 'status-pending';
}

function getStatusLabel($status) {
    $labels = [
        'approved' => 'Approved',
        'pending' => 'Pending',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
        'active' => 'Active',
        'banned' => 'Banned',
        'inactive' => 'Inactive',
        'completed' => 'Completed',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'disputed' => 'Disputed',
        'open' => 'Open',
        'under_review' => 'Under Review',
        'resolved' => 'Resolved',
        'dismissed' => 'Dismissed',
    ];
    return $labels[$status] ?? ucfirst($status);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX Admin Dashboard - Manage your platform">
    <title>Dashboard — HireX Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --mint-50: #f0fdf7;
            --mint-100: #dcfce7;
            --mint-200: #bbf7d0;
            --mint-300: #86efac;
            --mint-400: #4ade80;
            --mint-500: #22c55e;
            --mint-600: #16a34a;
            
            --teal-50: #f0fdfa;
            --teal-100: #ccfbf1;
            --teal-500: #14b8a6;
            --teal-600: #0d9488;
            
            --bg: #f8faf9;
            --bg-secondary: #ffffff;
            --sidebar-width: 250px;
            --primary: var(--mint-600);
            --primary-hover: #15803d;
            --primary-light: var(--mint-100);
            --secondary: var(--teal-500);
            --secondary-hover: #0f766e;
            --text-primary: #1a2f24;
            --text-secondary: #4a5d55;
            --text-gray: #789085;
            --border: #d1e8dd;
            --shadow: rgba(22, 163, 74, 0.08);
            --shadow-lg: rgba(22, 163, 74, 0.15);
            --danger: #ef4444;
            --success: var(--mint-500);
            --warning: #f59e0b;
            --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg: #0d1411;
            --bg-secondary: #141c18;
            --text-primary: #e0f2e8;
            --text-secondary: #9dbfa8;
            --text-gray: #789085;
            --border: #2d3d33;
            --shadow: rgba(0,0,0,0.4);
            --shadow-lg: rgba(0,0,0,0.6);
            --primary: var(--mint-500);
            --primary-hover: var(--mint-400);
            --primary-light: rgba(34, 197, 94, 0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            display: flex;
            color: var(--text-primary);
            transition: var(--transition);
            overflow-x: hidden;
            line-height: 1.6;
            background-image: 
                radial-gradient(ellipse at top right, rgba(34, 197, 94, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(20, 184, 166, 0.08) 0%, transparent 50%);
        }

        svg { display: block; }

        /* ==================== SIDEBAR ==================== */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-secondary);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            position: fixed;
            border-right: 1px solid var(--border);
            z-index: 1000;
            transition: var(--transition);
            overflow: hidden;
        }

        .logo {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 32px;
            padding-left: 14px;
            letter-spacing: -0.5px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .logo .x { color: var(--primary); }

        .admin-badge {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar nav {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
            margin-right: -4px;
        }

        .nav-group { margin-bottom: 24px; }

        .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-gray);
            margin-bottom: 12px;
            padding-left: 14px;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 11px 14px;
            text-decoration: none;
            color: var(--text-secondary);
            border-radius: 10px;
            margin-bottom: 4px;
            transition: var(--transition);
            font-weight: 500;
            cursor: pointer;
            font-size: 13px;
            gap: 12px;
        }

        .nav-item:hover {
            background: var(--primary-light);
            color: var(--primary);
            transform: translateX(4px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            box-shadow: 0 4px 15px var(--shadow-lg);
        }

        .nav-item svg { width: 18px; height: 18px; }

        .signout-container {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        .signout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            width: 100%;
            text-decoration: none;
            color: var(--danger);
            background: #fef2f2;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            justify-content: flex-start;
        }

        [data-theme="dark"] .signout-btn { background: rgba(239, 68, 68, 0.15); }

        .signout-btn:hover {
            background: var(--danger);
            color: white;
            transform: translateX(4px);
        }

        .signout-btn svg { width: 18px; height: 18px; }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 0 32px 32px 32px;
            transition: var(--transition);
        }

        .mobile-toggle {
            display: none;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            cursor: pointer;
            color: var(--text-primary);
            padding: 10px 12px;
            border-radius: 10px;
            transition: var(--transition);
        }

        .mobile-toggle:hover { background: var(--primary-light); border-color: var(--primary); }

        /* ==================== HEADER ==================== */
        header {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 13px;
            flex: 1;
        }

        .page-title-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
        }

        .page-title-header p {
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 3px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .theme-toggle {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 10px 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-secondary);
            transition: var(--transition);
            font-weight: 500;
        }

        .theme-toggle:hover { border-color: var(--primary); background: var(--primary-light); }

        .icon-btn {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 11px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .icon-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .icon-btn .notification-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-secondary);
            padding: 5px 5px 5px 5px;
            border-radius: 30px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            padding-right: 15px;
        }

        .user-pill:hover { border-color: var(--primary); box-shadow: 0 4px 14px var(--shadow); }

        .avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: white;
            overflow: hidden;
        }

        .avatar img { width: 100%; height: 100%; object-fit: cover; }

        .user-info { display: flex; flex-direction: column; }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .user-role {
            font-size: 10px;
            color: var(--text-gray);
            font-weight: 500;
        }

        /* ==================== STATS GRID ==================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 26px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--shadow);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon.green { background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); color: var(--mint-600); }
        .stat-icon.teal { background: linear-gradient(135deg, var(--teal-100), #99f6e4); color: var(--teal-600); }
        .stat-icon.yellow { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
        .stat-icon.red { background: linear-gradient(135deg, #fee2e2, #fecaca); color: var(--danger); }
        .stat-icon.purple { background: linear-gradient(135deg, #f3e8ff, #e9d5ff); color: #7c3aed; }
        .stat-icon.blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }

        .stat-info h4 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .stat-info p {
            font-size: 11px;
            color: var(--text-gray);
            margin-top: 2px;
            font-weight: 500;
        }

        /* ==================== DASHBOARD SECTIONS ==================== */
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 26px;
        }

        .section-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 20px;
            transition: var(--transition);
        }

        .section-card:hover {
            box-shadow: 0 10px 30px var(--shadow);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .section-header a {
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .section-header a:hover { text-decoration: underline; }

        /* ==================== TABLES ==================== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 12px;
            font-size: 13px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .data-table tr:last-child td { border-bottom: none; }

        .data-table tr:hover td {
            background: var(--mint-50);
        }

        [data-theme="dark"] .data-table tr:hover td {
            background: rgba(34, 197, 94, 0.08);
        }

        .table-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: white;
            overflow: hidden;
        }

        .table-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .table-user-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-user-role {
            font-size: 11px;
            color: var(--text-gray);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-approved {
            background: rgba(34, 197, 94, 0.12);
            color: var(--mint-600);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.12);
            color: var(--warning);
        }

        .status-rejected, .status-suspended, .status-cancelled {
            background: rgba(239, 68, 68, 0.12);
            color: var(--danger);
        }

        .status-badge svg { width: 10px; height: 10px; }

        /* Action Buttons */
        .action-btn {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-approve {
            background: rgba(34, 197, 94, 0.12);
            color: var(--mint-600);
        }

        .btn-approve:hover {
            background: var(--mint-500);
            color: white;
        }

        .btn-reject {
            background: rgba(239, 68, 68, 0.12);
            color: var(--danger);
        }

        .btn-reject:hover {
            background: var(--danger);
            color: white;
        }

        .action-btn svg { width: 12px; height: 12px; }

        /* ==================== ACTIVITY LOG ==================== */
        .activity-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 20px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child { border-bottom: none; }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.action { background: rgba(34, 197, 94, 0.12); color: var(--mint-600); }
        .activity-icon.warning { background: rgba(245, 158, 11, 0.12); color: var(--warning); }
        .activity-icon.danger { background: rgba(239, 68, 68, 0.12); color: var(--danger); }

        .activity-icon svg { width: 16px; height: 16px; }

        .activity-content { flex: 1; }

        .activity-text {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 3px;
        }

        .activity-text strong { color: var(--text-primary); }

        .activity-time {
            font-size: 11px;
            color: var(--text-gray);
        }

        /* ==================== TOAST ==================== */
        .toast {
            position: fixed;
            bottom: 26px;
            right: 26px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-left: 5px solid var(--success);
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 14px 45px var(--shadow-lg);
            transform: translateX(150%);
            transition: var(--transition);
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
        }

        .toast.show { transform: translateX(0); }
        .toast.error { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }

        .toast-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast.success .toast-icon { background: rgba(34, 197, 94, 0.15); color: var(--success); }
        .toast.error .toast-icon { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
        .toast-icon svg { width: 17px; height: 17px; }

        .toast-content { flex: 1; }
        .toast-title { font-weight: 600; color: var(--text-primary); font-size: 13px; }
        .toast-message { font-size: 12px; color: var(--text-gray); margin-top: 2px; }

        /* ==================== OVERLAY ==================== */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 20, 17, 0.65);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            transition: var(--transition);
        }

        .overlay.active { display: block; opacity: 1; }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-sections { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 18px 18px 18px; }
            .mobile-toggle { display: flex; }
            .stats-grid { grid-template-columns: 1fr; }
            .header-left { width: 100%; justify-content: space-between; }
            .page-title-header { order: 3; width: 100%; margin-top: 13px; }
            .toast { left: 18px; right: 18px; bottom: 18px; min-width: auto; }
        }

        @media (max-width: 480px) {
            .user-role { display: none; }
            .theme-toggle span:last-child { display: none; }
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--mint-300); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--mint-500); }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ==================== SIDEBAR ==================== -->
<aside class="sidebar" id="sidebar">
    <div class="logo">
        Hire<span class="x">X</span>
        <span class="admin-badge">Admin</span>
    </div>

    <nav>
        <div class="nav-group">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-item active">
                <?php echo getIcon('dashboard', 18); ?> Dashboard
            </a>
            <a href="workers.php" class="nav-item">
                <?php echo getIcon('workers', 18); ?> Workers
            </a>
            <a href="users.php" class="nav-item">
                <?php echo getIcon('users', 18); ?> Users
            </a>
            <a href="bookings.php" class="nav-item">
                <?php echo getIcon('calendar', 18); ?> Bookings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Moderation</div>
            <a href="reviews.php" class="nav-item">
                <?php echo getIcon('star', 18); ?> Reviews
            </a>
            <a href="complaints.php" class="nav-item">
                <?php echo getIcon('alert-circle', 18); ?> Complaints
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Content</div>
            <a href="categories.php" class="nav-item">
                <?php echo getIcon('layers', 18); ?> Categories
            </a>
            <a href="cities.php" class="nav-item">
                <?php echo getIcon('map', 18); ?> Cities
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Reports</div>
            <a href="analytics.php" class="nav-item">
                <?php echo getIcon('bar-chart', 18); ?> Analytics
            </a>
            <a href="activity_log.php" class="nav-item">
                <?php echo getIcon('activity', 18); ?> Activity Log
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Settings</div>
            <a href="settings.php" class="nav-item">
                <?php echo getIcon('settings', 18); ?> Settings
            </a>
            <a href="admin.php" class="nav-item">
                <?php echo getIcon('shield', 18); ?> Admin Accounts
            </a>
        </div>
    </nav>

    <div class="signout-container">
        <a href="logout.php" class="signout-btn">
            <?php echo getIcon('logout', 18); ?> Sign Out
        </a>
    </div>
</aside>

<!-- ==================== MAIN CONTENT ==================== -->
<main class="main-content" id="mainContent">
    <header>
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle Menu">
                <?php echo getIcon('menu', 20); ?>
            </button>
            <div class="page-title-header">
                <h1>Dashboard</h1>
                <p>Overview of platform statistics and recent activity</p>
            </div>
        </div>

        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle Dark Mode">
                <span id="themeIcon"><?php echo getIcon('moon', 16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            
            <button class="icon-btn" aria-label="Notifications" onclick="showToast('Notifications', 'You have <?php echo $pendingComplaints; ?> open complaints', true)">
                <?php echo getIcon('bell', 18); ?>
                <?php if ($pendingComplaints > 0): ?>
                    <span class="notification-dot"></span>
                <?php endif; ?>
            </button>
            
            <div class="user-pill" aria-label="Admin Profile">
                <div class="avatar">
                    <?php if ($adminPhoto): ?>
                        <img src="<?php echo $adminPhoto; ?>" alt="<?php echo $adminName; ?>">
                    <?php else: ?>
                        <?php echo $adminInitial; ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo $adminName; ?></span>
                    <span class="user-role"><?php echo $adminRole; ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- ==================== STATS GRID ==================== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><?php echo getIcon('users', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo number_format($totalUsers); ?></h4>
                <p>Total Users</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><?php echo getIcon('workers', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo number_format($totalWorkers); ?></h4>
                <p>Total Workers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><?php echo getIcon('calendar', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo number_format($totalBookings); ?></h4>
                <p>Total Bookings</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><?php echo getIcon('alert-circle', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo number_format($pendingApprovals); ?></h4>
                <p>Pending Approvals</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><?php echo getIcon('flag', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo number_format($openDisputes); ?></h4>
                <p>Open Disputes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><?php echo getIcon('dollar-sign', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo formatCurrency($totalRevenue); ?></h4>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>

    <!-- ==================== DASHBOARD SECTIONS ==================== -->
    <div class="dashboard-sections">
        <!-- Recent Bookings -->
        <div class="section-card">
            <div class="section-header">
                <h3>Recent Bookings</h3>
                <a href="bookings.php">View All <?php echo getIcon('arrow-right', 14); ?></a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Worker</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentBookings && $recentBookings->num_rows > 0): ?>
                        <?php while($booking = $recentBookings->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <div class="table-user">
                                        <div class="table-avatar">
                                            <?php echo strtoupper(substr($booking['user_name'], 0, 1)); ?>
                                        </div>
                                        <span class="table-user-name"><?php echo htmlspecialchars($booking['user_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-user">
                                        <div class="table-avatar">
                                            <?php echo strtoupper(substr($booking['worker_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="table-user-name"><?php echo htmlspecialchars($booking['worker_name']); ?></div>
                                            <div class="table-user-role"><?php echo htmlspecialchars($booking['role']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($booking['status']); ?>">
                                        <?php echo getIcon(in_array($booking['status'], ['completed', 'confirmed', 'approved']) ? 'check' : 'clock', 10); ?>
                                        <?php echo getStatusLabel($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($booking['booking_date'], 'M d'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-gray);">No bookings found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pending Worker Approvals -->
        <div class="section-card">
            <div class="section-header">
                <h3>Pending Worker Approvals</h3>
                <a href="workers.php?status=pending">View All <?php echo getIcon('arrow-right', 14); ?></a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Applied</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingWorkers && $pendingWorkers->num_rows > 0): ?>
                        <?php while($worker = $pendingWorkers->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <div class="table-avatar">
                                            <?php echo strtoupper(substr($worker['name'], 0, 1)); ?>
                                        </div>
                                        <span class="table-user-name"><?php echo htmlspecialchars($worker['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($worker['role']); ?></td>
                                <td><?php echo htmlspecialchars($worker['location']); ?></td>
                                <td><?php echo formatDate($worker['created_at'], 'M d'); ?></td>
                                <td>
                                    <form method="POST" action="actions/approve_worker.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="action-btn btn-approve">
                                            <?php echo getIcon('check', 12); ?> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="actions/reject_worker.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                                        <button type="submit" name="action" value="reject" class="action-btn btn-reject" onclick="return confirm('Are you sure you want to reject this worker?')">
                                            <?php echo getIcon('x', 12); ?> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-gray);">No pending approvals</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== ACTIVITY LOG ==================== -->
    <div class="activity-section">
        <div class="section-header">
            <h3>Recent Activity</h3>
            <a href="activity_log.php">View Log <?php echo getIcon('arrow-right', 14); ?></a>
        </div>
        <ul class="activity-list">
            <?php if ($activityLogs && $activityLogs->num_rows > 0): ?>
                <?php while($log = $activityLogs->fetch_assoc()): ?>
                    <li class="activity-item">
                        <div class="activity-icon <?php 
                            if (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'ban') !== false) echo 'danger';
                            elseif (stripos($log['action'], 'suspend') !== false || stripos($log['action'], 'reject') !== false) echo 'warning';
                            else echo 'action';
                        ?>">
                            <?php 
                            if (stripos($log['action'], 'approve') !== false || stripos($log['action'], 'add') !== false) echo getIcon('check', 16);
                            elseif (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'remove') !== false) echo getIcon('trash-2', 16);
                            elseif (stripos($log['action'], 'suspend') !== false || stripos($log['action'], 'ban') !== false) echo getIcon('alert-circle', 16);
                            else echo getIcon('activity', 16);
                            ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong><?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?></strong>
                                <?php echo htmlspecialchars($log['action']); ?>
                                <?php if ($log['target_id']): ?>
                                    <span style="color: var(--text-gray);">(<?php echo $log['target_type']; ?> #<?php echo $log['target_id']; ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="activity-time"><?php echo formatDate($log['created_at'], 'M d, Y h:i A'); ?></div>
                        </div>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li class="activity-item" style="justify-content: center; color: var(--text-gray);">
                    No recent activity
                </li>
            <?php endif; ?>
        </ul>
    </div>
</main>

<!-- ==================== TOAST NOTIFICATION ==================== -->
<div class="toast" id="toast">
    <div class="toast-icon" id="toastIconBox"><?php echo getIcon('check', 17); ?></div>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Success</div>
        <div class="toast-message" id="toastMessage">Action completed!</div>
    </div>
</div>

<!-- ==================== JAVASCRIPT ==================== -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    function toggleTheme() {
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        
        if (html.getAttribute('data-theme') === 'dark') {
            html.removeAttribute('data-theme');
            themeIcon.innerHTML = '<?php echo getIcon("moon", 16); ?>';
            themeText.textContent = 'Dark';
            localStorage.setItem('theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            themeIcon.innerHTML = '<?php echo getIcon("sun", 16); ?>';
            themeText.textContent = 'Light';
            localStorage.setItem('theme', 'dark');
        }
    }

    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('themeIcon').innerHTML = '<?php echo getIcon("sun", 16); ?>';
            document.getElementById('themeText').textContent = 'Light';
        }
    })();

    function showToast(title, message, success = true) {
        const toast = document.getElementById('toast');
        const toastIconBox = document.getElementById('toastIconBox');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        
        toastIconBox.innerHTML = success ? '<?php echo getIcon("check", 17); ?>' : '<?php echo getIcon("x", 17); ?>';
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        toast.className = 'toast' + (success ? ' success' : ' error') + ' show';
        
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.mobile-toggle');
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('sidebar').classList.contains('active')) {
            toggleSidebar();
        }
    });
</script>

</body>
</html>
