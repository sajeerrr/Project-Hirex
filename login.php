<?php
session_start();

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
            $_SESSION['success'] = 'Login successful! Welcome back.';

            header("Location: user/dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = 'Invalid Email or Password';
        }
    }

    // ---------- CHECK WORKER ----------
    if(!isset($_SESSION['error'])){
        $workerQuery = "SELECT * FROM workers WHERE email='$email'";
        $workerResult = $conn->query($workerQuery);

        if($workerResult && $workerResult->num_rows > 0){
            $worker = $workerResult->fetch_assoc();

            if(password_verify($password, $worker['password'])){
                $_SESSION['worker_id'] = $worker['id'];
                $_SESSION['worker_name'] = $worker['name'];
                $_SESSION['role'] = 'worker';
                $_SESSION['success'] = 'Login successful! Welcome back, ' . $worker['name'];

                header("Location: worker/dashboard.php");
                exit;
            } else {
                $_SESSION['error'] = 'Invalid Email or Password';
            }
        }
    }

    // ---------- CHECK ADMIN ----------
    if(!isset($_SESSION['error'])){
        $adminQuery = "SELECT * FROM admin WHERE email='$email'";
        $adminResult = $conn->query($adminQuery);

        if($adminResult && $adminResult->num_rows > 0){
            $admin = $adminResult->fetch_assoc();

            if(password_verify($password, $admin['password'])){
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['success'] = 'Login successful! Welcome, Admin.';

                header("Location: admin/dashboard.php");
                exit;
            } else {
                $_SESSION['error'] = 'Invalid Email or Password';
            }
        }
    }

    // ---------- IF NOTHING MATCH ----------
    if(!isset($_SESSION['error'])){
        $_SESSION['error'] = 'Invalid Email or Password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to HireX - Access your account">
    <title>Login — HireX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Green / Mint Theme */
            --mint-50: #f0fdf7;
            --mint-100: #dcfce7;
            --mint-200: #bbf7d0;
            --mint-300: #86efac;
            --mint-400: #4ade80;
            --mint-500: #22c55e;
            --mint-600: #16a34a;
            --mint-700: #15803d;
            
            --teal-50: #f0fdfa;
            --teal-100: #ccfbf1;
            --teal-200: #99f6e4;
            --teal-500: #14b8a6;
            --teal-600: #0d9488;
            
            /* Theme Variables */
            --bg: #f8faf9;
            --bg-secondary: #ffffff;
            --primary: var(--mint-600);
            --primary-hover: var(--mint-700);
            --primary-light: var(--mint-100);
            --secondary: var(--teal-500);
            --secondary-hover: var(--teal-600);
            --text-primary: #1a2f24;
            --text-secondary: #4a5d55;
            --text-gray: #789085;
            --border: #d1e8dd;
            --shadow: rgba(22, 163, 74, 0.08);
            --shadow-lg: rgba(22, 163, 74, 0.15);
            --danger: #ef4444;
            --success: var(--mint-500);
            --warning: #f59e0b;
            --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg: #0d1411;
            --bg-secondary: #141c18;
            --text-primary: #e0f2e8;
            --text-secondary: #9dbfa8;
            --text-gray: #789085;
            --border: #2d3d33;
            --shadow: rgba(0,0,0,0.4);
            --shadow-lg: rgba(0,0,0,0.6);
            --primary: var(--mint-500);
            --primary-hover: var(--mint-400);
            --primary-light: rgba(34, 197, 94, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text-primary);
            transition: var(--transition);
            overflow-x: hidden;
            line-height: 1.6;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(ellipse at top right, rgba(34, 197, 94, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(20, 184, 166, 0.08) 0%, transparent 50%);
        }

        svg {
            display: block;
        }

        /* worker-link */
        .contractor-link{
            font-size:13px;
            color:var(--text-gray);
            text-decoration:none;
            border-left:1px solid var(--border);
            padding-left:10px;
            margin-left:10px;
        }

        .contractor-link:hover {
            color: var(--primary);
        }

        /* ==================== NAVIGATION ==================== */
        .navbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        .logo {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            margin-left: 35px;
        }

        .logo .x {
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 11px 22px;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-lg);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-primary);
            padding: 8px;
        }

        /* ==================== LOGIN CONTAINER ==================== */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            min-height: calc(100vh - 73px);
        }

        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 1100px;
            width: 100%;
            background: var(--bg-secondary);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px var(--shadow-lg);
            border: 1px solid var(--border);
        }

        /* Left Side - Form */
        .login-form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .login-header p a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-header p a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-primary);
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-input::placeholder {
            color: var(--text-gray);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .remember-me label {
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .forgot-password {
            font-size: 14px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn-submit {
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            width: 100%;
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            border: none;
            color: white;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-lg);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 28px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            font-size: 13px;
            color: var(--text-gray);
            font-weight: 500;
        }

        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn-social {
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
        }

        .btn-social:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-social svg {
            width: 20px;
            height: 20px;
        }

        /* Right Side - Image/Info */
        .login-info-section {
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-info-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .login-info-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -30%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .info-content {
            position: relative;
            z-index: 1;
        }

        .info-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            backdrop-filter: blur(10px);
        }

        .info-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        .info-content h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .info-content p {
            font-size: 15px;
            opacity: 0.95;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .info-features {
            list-style: none;
        }

        .info-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .info-features li svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 900px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .login-info-section {
                display: none;
            }

            .login-form-section {
                padding: 50px 40px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 16px 20px;
            }
            .nav-links, .nav-buttons {
                display: none;
            }
            .mobile-menu-btn {
                display: block;
            }
            .logo {
                margin-left: 0;
            }
        }

        @media (max-width: 480px) {
            .login-form-section {
                padding: 40px 24px;
            }

            .login-header h1 {
                font-size: 26px;
            }

            .social-login {
                grid-template-columns: 1fr;
            }
        }

        /* ==================== ANIMATIONS ==================== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-wrapper {
            animation: fadeInUp 0.6s ease;
        }

        /* Alert Styles */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #f0fdf7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar">
    <a href="index.php" class="logo">
        Hire<span class="x">X</span>
    </a>
    
    <div class="nav-links">
        <a href="index.php#features">Features</a>
        <a href="index.php#categories">Categories</a>
        <a href="#">Help</a>
        <a href="#">Contact</a>
    </div>
    
    <div class="nav-buttons">
        <a href="register.php" class="btn btn-primary">Register</a>
        <a href="login.php" class="btn btn-outline">Login</a>
        
        <a href="worker_register.php" class="contractor-link">
            For Workers ↗
        </a>
    </div>
    
    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
</nav>

<!-- Login Container -->
<div class="login-container">
    <div class="login-wrapper">
        <!-- Left Side - Form -->
        <div class="login-form-section">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Don't have an account? <a href="register.php">Sign up free</a></p>
            </div>

            <?php
            // Display error messages
            if(isset($_SESSION['error'])){
                echo '<div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    ' . htmlspecialchars($_SESSION['error']) . '
                </div>';
                unset($_SESSION['error']);
            }
            
            // Display success messages
            if(isset($_SESSION['success'])){
                echo '<div class="alert alert-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    ' . htmlspecialchars($_SESSION['success']) . '
                </div>';
                unset($_SESSION['success']);
            }
            ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input 
                        class="form-input" 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="you@example.com" 
                        required
                        autocomplete="email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input 
                        class="form-input" 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="btn-submit">
                    Sign In
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </form>

        </div>

        <!-- Right Side - Info -->
        <div class="login-info-section">
            <div class="info-content">
                <div class="info-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <h2>Join Thousands of Happy Customers</h2>
                <p>Access your account to book skilled workers, manage appointments, and track your service history.</p>
                
                <ul class="info-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Book workers instantly
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Track service history
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Save favorite professionals
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Get exclusive deals
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleMobileMenu() {
        alert('Mobile menu coming soon!');
    }

    // Add focus effects
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
</script>

</body>
</html>
