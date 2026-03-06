<?php include("includes/header.php"); ?>

<section class="worker-register-section">

<div class="worker-register-container">

<h2 class="worker-register-title">Register as Worker</h2>

<p class="worker-register-sub">
Join HireX and offer your services to customers
</p>

<form class="worker-register-form" method="POST" enctype="multipart/form-data">

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

<select name="category" required>

<option value="">Select Service Category</option>
<option>Electrician</option>
<option>Plumber</option>
<option>Carpenter</option>
<option>Painter</option>
<option>Technician</option>

</select>

<input 
type="file"
name="photo"
>

<button type="submit" class="worker-register-btn">
Register
</button>

</form>

<p class="worker-register-login">
Already registered?
<a href="login.php">Login</a>
</p>

</div>

</section>

<?php include("includes/footer.php"); ?>