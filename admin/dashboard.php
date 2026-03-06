<?php
session_start();

include("../includes/header.php");
include("../database/db.php");

/* SECURITY CHECK */

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
header("Location:../login.php");
exit;
}

/* COUNT USERS */

$user_count = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM users");
if($result){
$row = $result->fetch_assoc();
$user_count = $row['total'];
}

/* COUNT WORKERS */

$worker_count = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM workers");
if($result){
$row = $result->fetch_assoc();
$worker_count = $row['total'];
}

?>

<section class="dashboard-section">

<div class="dashboard-container">

<h2 class="dashboard-title">Admin Dashboard</h2>

<div class="dashboard-grid">

<!-- USERS -->

<div class="dashboard-card">
<h3>Total Users</h3>
<p><?php echo $user_count; ?> Registered Users</p>
<a href="manage_users.php" class="dashboard-btn">Manage Users</a>
</div>

<!-- WORKERS -->

<div class="dashboard-card">
<h3>Total Workers</h3>
<p><?php echo $worker_count; ?> Registered Workers</p>
<a href="manage_workers.php" class="dashboard-btn">Manage Workers</a>
</div>

<!-- REPORTS -->

<div class="dashboard-card">
<h3>Reports</h3>
<p>View platform statistics and activity.</p>
<a href="reports.php" class="dashboard-btn">View Reports</a>
</div>

</div>

</div>

</section>

<?php include("../includes/footer.php"); ?>