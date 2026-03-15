<?php
session_start(); 
include("../includes/header.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'worker'){
header("Location:../login.php");
exit;
}
?>

<section class="dashboard-section">

<div class="dashboard-container">

<h2 class="dashboard-title">Worker Dashboard</h2>

<div class="dashboard-grid">

<div class="dashboard-card">
<h3>My Profile</h3>
<p>Update your service information.</p>
<a href="#" class="dashboard-btn">Edit Profile</a>
</div>

<div class="dashboard-card">
<h3>Service Requests</h3>
<p>See customer requests.</p>
<a href="#" class="dashboard-btn">View Requests</a>
</div>

<div class="dashboard-card">
<h3>Reviews</h3>
<p>Check ratings from customers.</p>
<a href="#" class="dashboard-btn">View Reviews</a>
</div>

</div>

</div>

</section>

<?php include("../includes/footer.php"); ?>