<?php

include("includes/header.php");
include("database/db.php");

if(isset($_POST['register'])){

    $name     = $conn->real_escape_string($_POST['name']);
    $email    = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $location = $conn->real_escape_string($_POST['location']);
    $phone    = $conn->real_escape_string($_POST['phone']);

    // Check if email already exists
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($check->num_rows > 0){
        echo "<script>alert('Email already exists');</script>";
    } else {

        $sql = "INSERT INTO users (name,email,password,location,phone,bio,photo)
                VALUES ('$name','$email','$password','$location','$phone','','')";

        if($conn->query($sql)){
            echo "<script>alert('Registration Successful'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Error: Try again');</script>";
        }
    }
}
?>

<section class="register-section">
<div class="register-container">
<h2 class="register-title">Create Account</h2>
<p class="register-sub">
Join HireX to find trusted workers near you
</p>

<form class="register-form" method="POST">

<input 
type="text" 
name="name"
placeholder="Full Name"
required
>

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

<input 
type="text"
name="location"
placeholder="City / Location"
required
>

<input 
type="text"
name="phone"
placeholder="Phone Number"
required
>

<button type="submit" class="register-btn" name="register">
Register
</button>

</form>

<p class="register-login">
Already have an account?
<a href="login.php">Login</a>
</p>

</div>

</section>

<?php include("includes/footer.php"); ?>