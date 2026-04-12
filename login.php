<?php
session_start();

include("includes/header.php");
include("database/db.php");

if(isset($_POST['login'])){

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // ---------- CHECK USER ----------
    $userQuery = "SELECT * FROM users WHERE email='$email'";
    $userResult = $conn->query($userQuery);

    if($userResult && $userResult->num_rows > 0){
        $user = $userResult->fetch_assoc();

        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'user';

            header("Location: user/dashboard.php");
            exit;
        }
    }

    // ---------- CHECK WORKER ----------
    $workerQuery = "SELECT * FROM workers WHERE email='$email'";
    $workerResult = $conn->query($workerQuery);

    if($workerResult && $workerResult->num_rows > 0){
        $worker = $workerResult->fetch_assoc();

        if(password_verify($password, $worker['password'])){
            $_SESSION['worker_id'] = $worker['id'];
            $_SESSION['worker_name'] = $worker['name'];
            $_SESSION['role'] = 'worker';

            header("Location: worker/dashboard.php");
            exit;
        }
    }

    // ---------- CHECK ADMIN ----------
    $adminQuery = "SELECT * FROM admin WHERE email='$email'";
    $adminResult = $conn->query($adminQuery);

    if($adminResult && $adminResult->num_rows > 0){
        $admin = $adminResult->fetch_assoc();

        if(password_verify($password, $admin['password'])){
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';

            header("Location: admin/dashboard.php");
            exit;
        }
    }

    // ---------- IF NOTHING MATCH ----------
    echo "<script>alert('Invalid Email or Password');</script>";
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