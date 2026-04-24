<?php
session_start();

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
        $_SESSION['error'] = 'Email already exists. Please use a different email or login.';
    } else {
        $sql = "INSERT INTO users (name,email,password,location,phone,bio,photo)
                VALUES ('$name','$email','$password','$location','$phone','','')";

        if($conn->query($sql)){
            $_SESSION['success'] = 'Registration successful! Please login to continue.';
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['error'] = 'Error: Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for HireX - Create your account">
    <title>Register — HireX</title>
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
            min-height: 100vh;
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
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar.scrolled {
            box-shadow: 0 4px 20px var(--shadow);
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

        /* ==================== REGISTER CONTAINER ==================== */
        .register-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 113px 20px 40px 20px;
            min-height: 100vh;
        }

        .register-wrapper {
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

        /* Left Side - Info (Swapped) */
        .register-info-section {
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .register-info-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .register-info-section::after {
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

        /* Right Side - Form (Swapped) */
        .register-form-section {
            padding: 50px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 100%;
            overflow-y: auto;
        }

        .register-header {
            margin-bottom: 30px;
        }

        .register-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .register-header p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .register-header p a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .register-header p a:hover {
            text-decoration: underline;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .form-input.error {
            border-color: var(--danger);
        }

        .form-input.success {
            border-color: var(--success);
        }

        .error-text {
            font-size: 12px;
            color: var(--danger);
            margin-top: 6px;
            display: none;
        }

        .form-options {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 24px;
        }

        .form-options input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
            margin-top: 2px;
        }

        .form-options label {
            font-size: 13px;
            color: var(--text-secondary);
            cursor: pointer;
            line-height: 1.5;
        }

        .form-options a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .form-options a:hover {
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
            margin: 24px 0;
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

        .social-register {
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

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 900px) {
            .register-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .register-info-section {
                display: none;
            }

            .register-form-section {
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
            .register-container {
                padding-top: 93px;
            }
        }

        @media (max-width: 480px) {
            .register-form-section {
                padding: 40px 24px;
            }

            .register-header h1 {
                font-size: 26px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .social-register {
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

        .register-wrapper {
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

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
            display: flex;
            gap: 4px;
        }

        .strength-bar {
            flex: 1;
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            transition: var(--transition);
        }

        .strength-bar.weak {
            background: var(--danger);
        }

        .strength-bar.medium {
            background: var(--warning);
        }

        .strength-bar.strong {
            background: var(--success);
        }

        .strength-text {
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 4px;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar" id="navbar">
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

<!-- Register Container -->
<div class="register-container">
    <div class="register-wrapper">
        <!-- Left Side - Info (SWAPPED) -->
        <div class="register-info-section">
            <div class="info-content">
                <div class="info-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                </div>
                <h2>Start Your Journey with HireX</h2>
                <p>Join our community and get access to trusted skilled workers for all your home service needs.</p>
                
                <ul class="info-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Free account creation
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Access to 5000+ verified workers
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Secure payment protection
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        24/7 customer support
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Side - Form (SWAPPED) -->
        <div class="register-form-section">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Already have an account? <a href="login.php">Sign in</a></p>
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

            <form action="register.php" method="POST" id="registerForm">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input 
                        class="form-input" 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="John Doe" 
                        required
                        autocomplete="name"
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                    >
                </div>

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

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input 
                            class="form-input" 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            placeholder="+1 234 567 8900" 
                            required
                            autocomplete="tel"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input 
                            class="form-input" 
                            type="text" 
                            id="location" 
                            name="location" 
                            placeholder="City, State" 
                            required
                            autocomplete="address-level2"
                            value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input 
                        class="form-input" 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Create a strong password" 
                        required
                        autocomplete="new-password"
                        minlength="8"
                    >
                    <div class="password-strength">
                        <div class="strength-bar" id="bar1"></div>
                        <div class="strength-bar" id="bar2"></div>
                        <div class="strength-bar" id="bar3"></div>
                        <div class="strength-bar" id="bar4"></div>
                    </div>
                    <p class="strength-text" id="strengthText">Password strength</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input 
                        class="form-input" 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm your password" 
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-options">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" name="register" class="btn-submit">
                    Create Account
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleMobileMenu() {
        alert('Mobile menu coming soon!');
    }

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 10) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Password strength checker
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const bars = [
        document.getElementById('bar1'),
        document.getElementById('bar2'),
        document.getElementById('bar3'),
        document.getElementById('bar4')
    ];
    const strengthText = document.getElementById('strengthText');

    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        // Reset all bars
        bars.forEach(bar => {
            bar.className = 'strength-bar';
        });

        // Update bars based on strength
        if (strength >= 1) bars[0].classList.add('weak');
        if (strength >= 2) {
            bars[1].classList.add('medium');
            bars[0].classList.add('medium');
        }
        if (strength >= 3) {
            bars[2].classList.add('strong');
            bars[1].classList.add('strong');
            bars[0].classList.add('strong');
        }
        if (strength >= 4) {
            bars[3].classList.add('strong');
        }

        // Update text
        if (strength === 0) {
            strengthText.textContent = 'Password strength';
        } else if (strength <= 2) {
            strengthText.textContent = 'Weak password';
            strengthText.style.color = 'var(--danger)';
        } else if (strength === 3) {
            strengthText.textContent = 'Medium password';
            strengthText.style.color = 'var(--warning)';
        } else {
            strengthText.textContent = 'Strong password';
            strengthText.style.color = 'var(--success)';
        }
    });

    // Confirm password validation
    const form = document.getElementById('registerForm');
    form.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (password !== confirm) {
            e.preventDefault();
            confirmInput.classList.add('error');
            alert('Passwords do not match!');
        }

        if (password.length < 8) {
            e.preventDefault();
            passwordInput.classList.add('error');
            alert('Password must be at least 8 characters long!');
        }
    });

    // Remove error class on input
    confirmInput.addEventListener('input', function() {
        this.classList.remove('error');
    });

    passwordInput.addEventListener('input', function() {
        this.classList.remove('error');
    });

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
