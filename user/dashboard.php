<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'user'){
    header("Location:../login.php");
    exit;
}

include("../includes/header.php");

$userName = $_SESSION['name'] ?? 'User';
$userInitial = strtoupper(substr($userName,0,1));
?>

<link rel="stylesheet" href="../assets/css/user-dashboard.css">

<div class="dashboard-layout">

<!-- SIDEBAR -->

<aside class="sidebar">

<div class="sidebar-logo">Hire<span>X</span></div>

<nav class="sidebar-nav">

<a href="#" class="nav-item active">Dashboard</a>
<a href="#">My Requests</a>
<a href="#">Workers</a>
<a href="#">Profile</a>
<a href="#">Notifications</a>

</nav>

</aside>

<!-- MAIN CONTENT -->

<div class="main">

<header class="topbar">

<div>
<h2>User Dashboard</h2>
<p>Welcome back, <?php echo $userName; ?></p>
</div>

<div class="avatar">
<?php echo $userInitial; ?>
</div>

</header>

<div class="page-body">

<div class="stat-grid">

<div class="stat-card">
<h3>12</h3>
<p>Total Requests</p>
</div>

<div class="stat-card">
<h3>7</h3>
<p>Completed Jobs</p>
</div>

<div class="stat-card">
<h3>3</h3>
<p>Pending</p>
</div>

<div class="stat-card">
<h3>₹4820</h3>
<p>Total Spent</p>
</div>

</div>

<!-- WORKERS -->

<h3 class="section-title">Nearby Workers</h3>

<div class="workers-grid">

<?php

$workers = [
    ["Rajan Kumar",    "Electrician", "4.8", "₹400/hr"],
    ["Suresh Menon",   "Plumber",     "5.0", "₹350/hr"],
    ["Arjun Pillai",   "Carpenter",   "3.9", "₹500/hr"],
    ["Vijay Nair",     "Painter",     "4.5", "₹300/hr"],
    ["Manoj Thomas",   "Technician",  "4.8", "₹450/hr"],
    ["Priya Sharma",   "Cleaner",     "4.2", "₹250/hr"],
    ["Deepak Verma",   "Electrician", "4.6", "₹420/hr"],
    ["Anil Yadav",     "Plumber",     "4.3", "₹370/hr"],
    ["Ramesh Gupta",   "Mason",       "4.7", "₹480/hr"],
    ["Sanjay Patil",   "Welder",      "4.9", "₹550/hr"],
    ["Kiran Reddy",    "Carpenter",   "4.1", "₹490/hr"],
    ["Mohan Das",      "Painter",     "3.8", "₹280/hr"],
    ["Vinod Kumar",    "AC Repair",   "4.6", "₹500/hr"],
    ["Prakash Nair",   "Plumber",     "4.4", "₹360/hr"],
    ["Sunil Sharma",   "Electrician", "4.7", "₹410/hr"],
    ["Ajay Singh",     "Carpenter",   "4.0", "₹470/hr"],
    ["Rohit Mehta",    "Technician",  "4.5", "₹430/hr"],
    ["Ashok Tiwari",   "Mason",       "4.8", "₹460/hr"],
    ["Naveen Pillai",  "Painter",     "4.3", "₹310/hr"],
    ["Harish Menon",   "Welder",      "4.6", "₹530/hr"],
    ["Santosh Rao",    "Cleaner",     "4.1", "₹240/hr"],
    ["Dinesh Patel",   "Electrician", "3.9", "₹390/hr"],
    ["Mukesh Joshi",   "AC Repair",   "4.7", "₹520/hr"],
    ["Rajesh Iyer",    "Plumber",     "4.5", "₹340/hr"],
    ["Ganesh Kumar",   "Carpenter",   "4.2", "₹460/hr"],
    ["Biju Thomas",    "Mason",       "4.9", "₹490/hr"],
    ["Sreejith Nair",  "Painter",     "4.4", "₹320/hr"],
    ["Anoop Varma",    "Technician",  "4.6", "₹440/hr"],
    ["Jithin Raj",     "Electrician", "4.3", "₹415/hr"],
    ["Vishnu Das",     "Welder",      "4.8", "₹560/hr"],
];

foreach($workers as $w){

?>

<div class="worker-card">

<h4><?php echo $w[0]; ?></h4>
<p><?php echo $w[1]; ?></p>

<div class="rating">⭐ <?php echo $w[2]; ?></div>

<div class="rate"><?php echo $w[3]; ?></div>

<button class="hire-btn">Request</button>

</div>

<?php } ?>

</div>

</div>

</div>

</div>

<?php include("../includes/footer.php"); ?>