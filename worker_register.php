<?php
session_start();
include("database/db.php");

if(isset($_POST['register_worker'])){

    $name     = $conn->real_escape_string($_POST['name']);
    $email    = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $city     = $conn->real_escape_string($_POST['city']);
    $category = $conn->real_escape_string($_POST['category']);
    $phone    = $conn->real_escape_string($_POST['phone']);

    // Default values for dashboard fields
    $rating = 0;
    $price = 0;
    $reviews = 0;
    $available = 1;
    $experience = "0 yrs";
    $jobs = 0;

    // Image upload
    $photo = $_FILES['photo']['name'];
    $tmp = $_FILES['photo']['tmp_name'];

    if(!empty($photo)){
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            $new_photo = uniqid() . '.' . $ext;
            move_uploaded_file($tmp, "assets/images/workers/" . $new_photo);
            $photo = $new_photo;
        } else {
            $photo = "default.png";
        }
    } else {
        $photo = "default.png";
    }

    // Check if email already exists
    $check = $conn->query("SELECT id FROM workers WHERE email='$email'");
    if($check->num_rows > 0){
        $_SESSION['error'] = 'Email already exists. Please use a different email or login.';
    } else {
        // Insert into database
        $sql = "INSERT INTO workers 
        (name, role, rating, price, reviews, available, photo, experience, jobs, location, email, password, phone)
        VALUES 
        ('$name', '$category', '$rating', '$price', '$reviews', '$available', '$photo', '$experience', '$jobs', '$city', '$email', '$password', '$phone')";

        if($conn->query($sql)){
            $_SESSION['success'] = 'Worker registration successful! Please login to continue.';
            header("Location: worker_login.php");
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
    <meta name="description" content="Register as a Worker on HireX - Join our community">
    <title>Worker Register — HireX</title>
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

        /* Left Side - Info */
        .register-info-section {
            background: linear-gradient(135deg, var(--teal-500), var(--mint-500));
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

        /* Right Side - Form */
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

        .form-input,
        .form-select {
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

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-input::placeholder {
            color: var(--text-gray);
        }

        .form-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23789085' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 50px;
        }

        .form-input.error,
        .form-select.error {
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
            background: linear-gradient(135deg, var(--teal-500), var(--mint-500));
            border: none;
            color: white;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, var(--mint-500), var(--teal-600));
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

        /* File Upload Styling */
        .file-upload-container {
            position: relative;
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: var(--transition);
            background: var(--bg);
            cursor: pointer;
        }

        .file-upload-container:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .file-upload-container input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            color: var(--text-gray);
        }

        .file-upload-text {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .file-upload-hint {
            font-size: 12px;
            color: var(--text-gray);
        }

        .file-preview {
            margin-top: 12px;
            display: none;
        }

        .file-preview img {
            max-width: 120px;
            max-height: 120px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--border);
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
        <!-- Left Side - Info -->
        <div class="register-info-section">
            <div class="info-content">
                <div class="info-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h2>Become a HireX Worker</h2>
                <p>Join our network of skilled professionals and grow your business with thousands of potential customers.</p>
                
                <ul class="info-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Free profile creation
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Get discovered by local customers
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Secure payment processing
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Build your reputation with reviews
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Flexible schedule & availability
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="register-form-section">
            <div class="register-header">
                <h1>Worker Registration</h1>
                <p>Already have an account? <a href="worker_login.php">Sign in</a></p>
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

            <form action="worker_register.php" method="POST" enctype="multipart/form-data" id="registerForm">
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
                        <label class="form-label" for="city">City/Location</label>
                        <input 
                            class="form-input" 
                            type="text" 
                            id="city" 
                            name="city" 
                            placeholder="e.g. Kozhikode" 
                            required
                            autocomplete="address-level2"
                            value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Service Category</label>
                    <select 
                        class="form-select" 
                        id="category" 
                        name="category" 
                        required
                    >
                        <option value="">Select Service Category</option>
                        <option value="Electrician" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Electrician') ? 'selected' : ''; ?>>Electrician</option>
                        <option value="Plumber" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Plumber') ? 'selected' : ''; ?>>Plumber</option>
                        <option value="Carpenter" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Carpenter') ? 'selected' : ''; ?>>Carpenter</option>
                        <option value="Painter" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Painter') ? 'selected' : ''; ?>>Painter</option>
                        <option value="AC Technician" <?php echo (isset($_POST['category']) && $_POST['category'] == 'AC Technician') ? 'selected' : ''; ?>>AC Technician</option>
                        <option value="Mechanic" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Mechanic') ? 'selected' : ''; ?>>Mechanic</option>
                        <option value="Cleaner" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Cleaner') ? 'selected' : ''; ?>>Cleaner</option>
                        <option value="Appliance Repair" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Appliance Repair') ? 'selected' : ''; ?>>Appliance Repair</option>
                        <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
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

                <div class="form-group">
                    <label class="form-label">Profile Photo</label>
                    <div class="file-upload-container" id="fileUploadContainer">
                        <input type="file" name="photo" id="photo" accept="image/*">
                        <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <p class="file-upload-text">Click to upload or drag and drop</p>
                        <p class="file-upload-hint">PNG, JPG, GIF (Max 5MB)</p>
                        <div class="file-preview" id="filePreview"></div>
                    </div>
                </div>

                <div class="form-options">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" name="register_worker" class="btn-submit">
                    Create Worker Account
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

    // File upload preview
    const photoInput = document.getElementById('photo');
    const filePreview = document.getElementById('filePreview');
    const fileUploadText = document.querySelector('.file-upload-text');

    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                filePreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                filePreview.style.display = 'block';
                fileUploadText.textContent = file.name;
            };
            reader.readAsDataURL(file);
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

        // Validate file size
        const photoFile = photoInput.files[0];
        if (photoFile && photoFile.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('File size must be less than 5MB!');
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
    document.querySelectorAll('.form-input, .form-select').forEach(input => {
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
