```php
<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'user'){
header("Location: ../login.php");
exit;
}

include("../includes/header.php");
?>

<section class="dashboard-section">

<div class="user-dashboard">

<!-- SIDEBAR -->
<div class="user-sidebar">

<h3>User Panel</h3>

<ul class="user-menu">
<li><a href="#">Dashboard</a></li>
<li><a href="../index.php">Search Workers</a></li>
<li><a href="#">My Requests</a></li>
<li><a href="#">Notifications</a></li>
<li><a href="#">Profile</a></li>
<li><a href="../logout.php">Logout</a></li>
</ul>

</div>


<!-- MAIN CONTENT -->
<div class="user-main">

<!-- TOP BAR -->
<div class="user-topbar">

<div class="user-search">
<input type="text" id="searchInput" placeholder="Search workers..." onkeyup="searchWorkers()">
</div>

<div class="user-profile">
<div class="notification">🔔</div>
<img src="../assets/images/user.png">
<span><?php echo $_SESSION['username'] ?? "User"; ?></span>
</div>

</div>


<!-- TITLE + FILTER -->
<div class="worker-header">

<h2 class="dashboard-title">Available Workers</h2>

<select id="skillFilter" onchange="filterWorkers()">
<option value="all">All Skills</option>
<option value="Coolipani">Coolipani</option>
<option value="Coconut tree climber">Coconut tree climber</option>
<option value="Gardening">Gardening</option>
<option value="House keeper">House keeper</option>
</select>

</div>


<!-- WORKER LIST -->
<div class="worker-list" id="workerList">


<!-- WORKER -->
<div class="worker-row" data-skill="Coolipani">

<div class="worker-info">
<img src="../assets/images/user.png">

<div>
<div class="worker-name">Coolipani Rajan</div>
<div class="worker-skill">Coolipani</div>
<div class="worker-location">Kattakkada</div>

<div class="worker-rating">★★★★★</div>
</div>

</div>

<div class="worker-action">
<a href="#" class="dashboard-btn">Hire</a>
<a href="#" class="dashboard-btn view-btn">View</a>
</div>

</div>



<!-- WORKER -->
<div class="worker-row" data-skill="Coconut tree climber">

<div class="worker-info">
<img src="../assets/images/user.png">

<div>
<div class="worker-name">Mathayi</div>
<div class="worker-skill">Coconut tree climber</div>
<div class="worker-location">Karicode</div>

<div class="worker-rating">★★★★☆</div>
</div>

</div>

<div class="worker-action">
<a href="#" class="dashboard-btn">Hire</a>
<a href="#" class="dashboard-btn view-btn">View</a>
</div>

</div>



<!-- WORKER -->
<div class="worker-row" data-skill="Gardening">

<div class="worker-info">
<img src="../assets/images/user.png">

<div>
<div class="worker-name">Nehmal Pallimukku</div>
<div class="worker-skill">Gardening</div>
<div class="worker-location">Pallimukku</div>

<div class="worker-rating">★★★★★</div>
</div>

</div>

<div class="worker-action">
<a href="#" class="dashboard-btn">Hire</a>
<a href="#" class="dashboard-btn view-btn">View</a>
</div>

</div>



<!-- WORKER -->
<div class="worker-row" data-skill="House keeper">

<div class="worker-info">
<img src="../assets/images/user.png">

<div>
<div class="worker-name">Shantha</div>
<div class="worker-skill">House keeper</div>
<div class="worker-location">Chinnakkada</div>

<div class="worker-rating">★★★★☆</div>
</div>

</div>

<div class="worker-action">
<a href="#" class="dashboard-btn">Hire</a>
<a href="#" class="dashboard-btn view-btn">View</a>
</div>

</div>


</div>

</div>

</div>

</section>

<script>

/* SEARCH */

function searchWorkers(){

let input = document.getElementById("searchInput").value.toLowerCase();
let workers = document.querySelectorAll(".worker-row");

workers.forEach(worker => {

let name = worker.querySelector(".worker-name").innerText.toLowerCase();

if(name.includes(input)){
worker.style.display="flex";
}else{
worker.style.display="none";
}

});

}


/* FILTER */

function filterWorkers(){

let filter = document.getElementById("skillFilter").value;
let workers = document.querySelectorAll(".worker-row");

workers.forEach(worker => {

let skill = worker.getAttribute("data-skill");

if(filter === "all" || skill === filter){
worker.style.display="flex";
}else{
worker.style.display="none";
}

});

}

</script>

<?php include("../includes/footer.php"); ?>
```
