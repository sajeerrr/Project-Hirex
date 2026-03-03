<?php include("includes/header.php"); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow p-4">
                <h3 class="text-center mb-3">User Registration</h3>
                <form method="POST">
                    <input type="text" class="form-control mb-3" placeholder="Full Name" required>
                    <input type="email" class="form-control mb-3" placeholder="Email" required>
                    <input type="password" class="form-control mb-3" placeholder="Password" required>
                    <input type="text" class="form-control mb-3" placeholder="City" required>
                    <button class="btn btn-warning w-100">Register</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>