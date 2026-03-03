<?php include("includes/header.php"); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow p-4">
                <h3 class="text-center mb-3">Login</h3>
                <form method="POST">
                    <input type="email" class="form-control mb-3" placeholder="Email" required>
                    <input type="password" class="form-control mb-3" placeholder="Password" required>
                    <button class="btn btn-warning w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>