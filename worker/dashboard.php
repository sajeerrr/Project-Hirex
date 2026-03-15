<?php
session_start();
include("../database/db.php");

/* SECURITY */
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'worker'){
header("Location:../login.php");
exit;
}

$workerId = $_SESSION['worker_id'];

/* WORKER INFO */

$worker = [];

$r = $conn->query("SELECT * FROM workers WHERE id='$workerId'");

if($r && $r->num_rows > 0){
$worker = $r->fetch_assoc();
}

$workerName = $worker['name'] ?? 'Worker';
$workerInitial = strtoupper(substr($workerName,0,1));
$category = $worker['category'] ?? 'Worker';
$rating = $worker['rating'] ?? 0;
$status = $worker['status'] ?? 'active';
$location = $worker['city'] ?? '-';

/* COUNTS */

$total_requests = 0;
$pending_requests = 0;
$completed_jobs = 0;
$total_earned = 0;

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE worker_id='$workerId'");
if($r) $total_requests = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE worker_id='$workerId' AND status='pending'");
if($r) $pending_requests = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE worker_id='$workerId' AND status='completed'");
if($r) $completed_jobs = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT SUM(amount) as total FROM requests WHERE worker_id='$workerId' AND status='completed'");
if($r){
$row = $r->fetch_assoc();
$total_earned = $row['total'] ?? 0;
}

/* RECENT REQUESTS */

$recent_requests = [];

$r = $conn->query("
SELECT r.id,
u.name AS user_name,
r.status,
r.amount,
r.created_at
FROM requests r
LEFT JOIN users u ON r.user_id = u.id
WHERE r.worker_id='$workerId'
ORDER BY r.created_at DESC
LIMIT 6
");

if($r){
while($row = $r->fetch_assoc()){
$recent_requests[] = $row;
}
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Worker Dashboard</title>

<link rel="stylesheet" href="../assets/css/worker-dashboard.css">

</head>

<body>

<div class="dashboard">

<!-- SIDEBAR -->

<div class="sidebar">

<h2>HireX</h2>

<ul>

<li class="active">Dashboard</li>

<li><a href="my_requests.php">My Requests</a></li>

<li><a href="job_history.php">Job History</a></li>

<li><a href="earnings.php">Earnings</a></li>

<li><a href="profile.php">Profile</a></li>

<li><a href="../logout.php">Logout</a></li>

</ul>

</div>


<!-- MAIN -->

<div class="main">

<h2>Welcome <?php echo $workerName; ?></h2>

<!-- PROFILE -->

<div class="profile">

<div class="avatar"><?php echo $workerInitial; ?></div>

<div>

<h3><?php echo $workerName; ?></h3>

<p><?php echo $category; ?> • <?php echo $location; ?></p>

<p>⭐ Rating: <?php echo $rating; ?></p>

</div>

</div>


<!-- STATS -->

<div class="stats">

<div class="card">

<h3><?php echo $total_requests; ?></h3>

<p>Total Requests</p>

</div>

<div class="card">

<h3><?php echo $pending_requests; ?></h3>

<p>Pending Jobs</p>

</div>

<div class="card">

<h3><?php echo $completed_jobs; ?></h3>

<p>Completed Jobs</p>

</div>

<div class="card">

<h3>₹<?php echo $total_earned; ?></h3>

<p>Total Earnings</p>

</div>

</div>


<!-- REQUEST TABLE -->

<div class="table-box">

<h3>Recent Requests</h3>

<table>

<tr>

<th>ID</th>
<th>Customer</th>
<th>Amount</th>
<th>Status</th>

</tr>

<?php foreach($recent_requests as $req){ ?>

<tr>

<td>#<?php echo $req['id']; ?></td>

<td><?php echo htmlspecialchars($req['user_name']); ?></td>

<td>₹<?php echo $req['amount']; ?></td>

<td><?php echo ucfirst($req['status']); ?></td>

</tr>

<?php } ?>

</table>

</div>

</div>

</div>

</body>

</html>