<?php include("includes/header.php"); ?>

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
name="city"
placeholder="City"
required
>

<button type="submit" class="register-btn">
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