<?php
session_start();

include("includes/header.php");
include("database/db.php");

if(isset($_POST['login'])){

$email = $_POST['email'];
$password = $_POST['password'];

/* CHECK USERS */

$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if($result && $result->num_rows > 0){

$user = $result->fetch_assoc();

if(password_verify($password,$user['password'])){

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = 'user';

header("Location: user/dashboard.php");
exit;

}

}


/* CHECK WORKERS */

$sql = "SELECT * FROM workers WHERE email='$email'";
$result = $conn->query($sql);

if($result && $result->num_rows > 0){

$worker = $result->fetch_assoc();

if(password_verify($password,$worker['password'])){

$_SESSION['worker_id'] = $worker['id'];
$_SESSION['role'] = 'worker';

header("Location: worker/dashboard.php");
exit;

}

}


/* CHECK ADMIN */

$sql = "SELECT * FROM admin WHERE email='$email'";
$result = $conn->query($sql);

if($result && $result->num_rows > 0){

$admin = $result->fetch_assoc();

if(password_verify($password,$admin['password'])){

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['role'] = 'admin';

header("Location: admin/dashboard.php");
exit;

}

}

/* IF NOTHING MATCHES */

echo "Invalid email or password";

}
?>

<section class="login-section">

<div class="login-container">

<h2 class="login-title">Welcome Back</h2>

<p class="login-sub">
Login to continue to HireX
</p>

<form class="login-form" method="POST">

<input 
type="email" 
name="email"
placeholder="Email Address"
required
>

<input 
type="password"
name="password"
placeholder="Password"
required
>

<button type="submit" class="login-btn" name="login">
Login
</button>

</form>

<p class="login-register">
Don't have an account? 
<a href="register.php">Register</a>
</p>

</div>

</section>

<?php include("includes/footer.php"); ?>