<?php
include("../includes/header.php");
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'user'){
header("Location:../login.php");
exit;
}
?>

<section class="dashboard-section">

<div class="dashboard-container">

<h2 class="dashboard-title">User Dashboard</h2>

<div class="dashboard-grid">

<div class="dashboard-card">
<h3>Search Workers</h3>
<p>Find electricians, plumbers and technicians near you.</p>
<a href="../index.php" class="dashboard-btn">Search</a>
</div>

<div class="dashboard-card">
<h3>My Requests</h3>
<p>View workers you contacted.</p>
<a href="#" class="dashboard-btn">View</a>
</div>

<div class="dashboard-card">
<h3>Profile</h3>
<p>Update your account details.</p>
<a href="#" class="dashboard-btn">Edit</a>
</div>

</div>

</div>

</section>

<?php include("../includes/footer.php"); ?>