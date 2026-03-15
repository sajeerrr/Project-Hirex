<?php
session_start();
include("../database/db.php");

/* SECURITY */

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
header("Location: ../login.php");
exit;
}

$adminName = $_SESSION['name'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminName,0,1));

/* COUNTS */

$user_count = 0;
$worker_count = 0;
$total_requests = 0;
$pending_count = 0;
$completed_count = 0;
$total_revenue = 0;

/* USERS */

$r = $conn->query("SELECT COUNT(*) as total FROM users");
if($r) $user_count = $r->fetch_assoc()['total'];

/* WORKERS */

$r = $conn->query("SELECT COUNT(*) as total FROM workers");
if($r) $worker_count = $r->fetch_assoc()['total'];

/* TOTAL REQUESTS */

$r = $conn->query("SELECT COUNT(*) as total FROM requests");
if($r) $total_requests = $r->fetch_assoc()['total'];

/* PENDING */

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='pending'");
if($r) $pending_count = $r->fetch_assoc()['total'];

/* COMPLETED */

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='completed'");
if($r) $completed_count = $r->fetch_assoc()['total'];

/* REVENUE */

$r = $conn->query("SELECT SUM(amount) as total FROM requests WHERE status='completed'");
if($r){
$row = $r->fetch_assoc();
$total_revenue = $row['total'] ?? 0;
}

/* RECENT REQUESTS */

$recent_requests = [];

$r = $conn->query("
SELECT 
r.id,
u.name AS user_name,
w.name AS worker_name,
w.category AS trade,
r.status,
r.amount,
r.created_at
FROM requests r
LEFT JOIN users u ON r.user_id = u.id
LEFT JOIN workers w ON r.worker_id = w.id
ORDER BY r.created_at DESC
LIMIT 8
");

if($r){
while($row = $r->fetch_assoc()){
$recent_requests[] = $row;
}
}

/* RECENT WORKERS */

$recent_workers = [];

$r = $conn->query("
SELECT id,name,category,rating,status,created_at
FROM workers
ORDER BY created_at DESC
LIMIT 6
");

if($r){
while($row = $r->fetch_assoc()){
$recent_workers[] = $row;
}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">

<title>HireX Admin Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/admin-dashboard.css">

</head>

<body>


<!-- SIDEBAR -->

<aside class="sidebar">

<div class="sidebar-logo">Hire<span>X</span></div>

<nav class="sidebar-nav">

<div class="nav-group">Overview</div>

<a class="nav-item active" href="dashboard.php">Dashboard</a>

<div class="nav-group">Manage</div>

<a class="nav-item" href="manage_users.php">Users</a>

<a class="nav-item" href="manage_workers.php">Workers</a>

<a class="nav-item" href="manage_requests.php">Requests</a>

<div class="nav-group">System</div>

<a class="nav-item" href="reports.php">Reports</a>

<a class="nav-item" href="settings.php">Settings</a>

<a class="nav-item" href="../logout.php">Logout</a>

</nav>

</aside>


<div class="main">

<header class="topbar">

<h1>Admin Dashboard</h1>

<div class="t-avatar"><?php echo $adminInitial; ?></div>

</header>


<div class="page-body">


<!-- STATISTICS -->

<div class="stat-grid">

<div class="stat-card">
<div class="sc-num"><?php echo number_format($user_count); ?></div>
<div class="sc-label">Total Users</div>
</div>

<div class="stat-card">
<div class="sc-num"><?php echo number_format($worker_count); ?></div>
<div class="sc-label">Total Workers</div>
</div>

<div class="stat-card">
<div class="sc-num"><?php echo number_format($total_requests); ?></div>
<div class="sc-label">Total Requests</div>
</div>

<div class="stat-card">
<div class="sc-num"><?php echo number_format($pending_count); ?></div>
<div class="sc-label">Pending Jobs</div>
</div>

<div class="stat-card">
<div class="sc-num"><?php echo number_format($completed_count); ?></div>
<div class="sc-label">Completed Jobs</div>
</div>

<div class="stat-card">
<div class="sc-num">₹<?php echo number_format($total_revenue); ?></div>
<div class="sc-label">Total Revenue</div>
</div>

</div>



<!-- RECENT REQUESTS -->

<div class="panel">

<div class="ph">
<div class="ph-title">Recent Requests</div>
</div>

<table class="data-table">

<thead>
<tr>
<th>ID</th>
<th>User</th>
<th>Worker</th>
<th>Trade</th>
<th>Amount</th>
<th>Status</th>
</tr>
</thead>

<tbody>

<?php foreach($recent_requests as $req): ?>

<tr>

<td>#<?php echo $req['id']; ?></td>

<td><?php echo htmlspecialchars($req['user_name']); ?></td>

<td><?php echo htmlspecialchars($req['worker_name']); ?></td>

<td><?php echo htmlspecialchars($req['trade']); ?></td>

<td>₹<?php echo number_format($req['amount']); ?></td>

<td>
<span class="pill <?php echo strtolower($req['status']); ?>">
<?php echo ucfirst($req['status']); ?>
</span>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>


<br>


<!-- RECENT WORKERS -->

<div class="panel">

<div class="ph">
<div class="ph-title">Recent Workers</div>
</div>

<table class="data-table">

<thead>

<tr>
<th>Name</th>
<th>Trade</th>
<th>Joined</th>
</tr>

</thead>

<tbody>

<?php foreach($recent_workers as $worker): ?>

<tr>

<td><?php echo htmlspecialchars($worker['name']); ?></td>

<td><?php echo htmlspecialchars($worker['category']); ?></td>

<td><?php echo date("d M Y",strtotime($worker['created_at'])); ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>


</div>

</div>

</body>
</html>