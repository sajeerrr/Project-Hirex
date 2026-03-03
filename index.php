<?php include("includes/header.php"); ?>

<section class="hero text-center text-white d-flex align-items-center">
    <div class="container">
        <h1 class="display-4 fw-bold">Find Trusted Workers Near You</h1>
        <p class="lead">Electricians, Plumbers, Carpenters & More</p>

        <form action="user/search.php" method="GET" class="row justify-content-center mt-4">
            <div class="col-md-3">
                <input type="text" name="city" class="form-control" placeholder="Enter City" required>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select" required>
                    <option value="">Select Service</option>
                    <option>Electrician</option>
                    <option>Plumber</option>
                    <option>Carpenter</option>
                    <option>Painter</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-warning w-100">Search</button>
            </div>
        </form>
    </div>
</section>

<section class="container py-5">
    <h2 class="text-center mb-4">Popular Services</h2>
    <div class="row text-center">

        <div class="col-md-3">
            <div class="card p-3 shadow">
                <h5>Electrician</h5>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow">
                <h5>Plumber</h5>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow">
                <h5>Carpenter</h5>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow">
                <h5>Painter</h5>
            </div>
        </div>

    </div>
</section>

<?php include("includes/footer.php"); ?>