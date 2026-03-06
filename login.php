<?php include("includes/header.php"); ?>

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

<button type="submit" class="login-btn">
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