<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX - Find and hire skilled workers easily">
    <title>HireX â€” Find Skilled Workers Near You</title>
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
            background-image: 
                radial-gradient(ellipse at top right, rgba(34, 197, 94, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(20, 184, 166, 0.08) 0%, transparent 50%);
            min-height: 100vh;
        }

        svg {
            display: block;
        }

        /* worker-link */
        .contractor-link{
        font-size:13px;
        color:var(--muted);
        text-decoration:none;
        border-left:1px solid var(--border);
        padding-left:10px;
        }

        /* top-line adjust */
        #features,
        #categories {
            scroll-margin-top: 80px;
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

        /* ==================== HERO SECTION ==================== */
        .hero {
            min-height: calc(100vh - 70px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 32px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(20, 184, 166, 0.1));
            border-radius: 50%;
            filter: blur(60px);
            z-index: -1;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(34, 197, 94, 0.08));
            border-radius: 50%;
            filter: blur(60px);
            z-index: -1;
        }

        .hero-content {
            text-align: center;
            max-width: 900px;
            width: 100%;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 26px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
            border: 1px solid var(--mint-200);
        }

        .hero-badge svg {
            width: 16px;
            height: 16px;
        }

        .hero h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 52px;
            font-weight: 800;
            line-height: 1.15;
            color: var(--text-primary);
            margin-bottom: 18px;
            letter-spacing: -1px;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        /* ==================== SEARCH BAR ==================== */
        .search-bar {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 850px;
            margin: 0 auto 48px auto;
            box-shadow: 0 10px 40px var(--shadow);
            transition: var(--transition);
        }

        .search-bar:focus-within {
            border-color: var(--primary);
            box-shadow: 0 15px 50px var(--shadow-lg);
            transform: translateY(-2px);
        }

        .search-field {
            flex: 1;
            border: none;
            outline: none;
            padding: 14px 18px;
            font-size: 15px;
            color: var(--text-primary);
            background: transparent;
            font-family: 'Inter', sans-serif;
            min-width: 180px;
        }

        .search-field::placeholder {
            color: var(--text-gray);
        }

        .search-select {
            border: none;
            outline: none;
            padding: 14px 18px;
            font-size: 15px;
            color: var(--text-gray);
            background: transparent;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            min-width: 160px;
        }

        .search-select:valid {
            color: var(--text-primary); /* selected value color */
        }

        .search-select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .divider {
            width: 1px;
            height: 32px;
            background: var(--border);
        }

        .search-btn {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: scale(1.03);
            box-shadow: 0 8px 25px var(--shadow-lg);
        }

        .search-btn svg {
            width: 18px;
            height: 18px;
        }

        /* ==================== TRUSTED SECTION ==================== */
        .trusted {
            text-align: center;
            max-width: 800px;
            width: 100%;
        }

        .trusted-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 24px;
        }

        .logos-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .company-logo {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .company-logo:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px var(--shadow);
        }

        /* ==================== FEATURES SECTION ==================== */
        .features {
            padding: 80px 32px;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
        }

        .section-header p {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 550px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            transition: var(--transition);
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px var(--shadow-lg);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
        }

        .feature-icon.green {
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200));
            color: var(--mint-600);
        }

        .feature-icon.teal {
            background: linear-gradient(135deg, var(--teal-100), var(--teal-200));
            color: var(--teal-600);
        }

        .feature-icon.yellow {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #b45309;
        }

        .feature-card h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* ==================== CATEGORIES SECTION ==================== */
        .categories {
            padding: 80px 32px;
        }

        .categories-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 40px;
        }

        .category-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-primary);
        }

        .category-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 40px var(--shadow-lg);
            border-color: var(--primary);
        }

        .category-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            font-size: 28px;
        }

        .category-card h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .category-card span {
            font-size: 12px;
            color: var(--text-gray);
        }

        /* ==================== STATS SECTION ==================== */
        .stats {
            padding: 60px 32px;
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            color: white;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
            text-align: center;
        }

        .stat-item h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-item p {
            font-size: 14px;
            opacity: 0.95;
        }

        /* ==================== CTA SECTION ==================== */
        .cta {
            padding: 80px 32px;
            text-align: center;
        }

        .cta-container {
            max-width: 700px;
            margin: 0 auto;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 60px 40px;
            box-shadow: 0 20px 60px var(--shadow-lg);
        }

        .cta h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 34px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
        }

        .cta p {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }

        .cta-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* ==================== FOOTER ==================== */
        .footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border);
            padding: 60px 32px 32px 32px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        .footer-brand .logo {
            margin-bottom: 16px;
        }

        .footer-brand p {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: var(--transition);
            text-decoration: none;
        }

        .social-link:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .footer-column h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 12px;
        }

        .footer-column a {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            transition: var(--transition);
        }

        .footer-column a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            padding-top: 32px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom p {
            font-size: 13px;
            color: var(--text-gray);
        }

        .footer-links {
            display: flex;
            gap: 24px;
        }

        .footer-links a {
            text-decoration: none;
            color: var(--text-gray);
            font-size: 13px;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .categories-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
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
            .hero {
                padding: 40px 20px;
            }
            .hero h1 {
                font-size: 36px;
            }
            .hero-sub {
                font-size: 16px;
            }
            .search-bar {
                flex-direction: column;
                padding: 16px;
            }
            .search-field, .search-select {
                width: 100%;
                min-width: auto;
            }
            .divider {
                width: 100%;
                height: 1px;
            }
            .search-btn {
                width: 100%;
                justify-content: center;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stats-container {
                grid-template-columns: 1fr 1fr;
                gap: 24px;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            .cta-container {
                padding: 40px 24px;
            }
            .cta h2 {
                font-size: 26px;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 28px;
            }
            .categories-grid {
                grid-template-columns: 1fr;
            }
            .logos-row {
                flex-direction: column;
            }
            .company-logo {
                width: 100%;
                justify-content: center;
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

        .hero-content {
            animation: fadeInUp 0.8s ease;
        }

        .search-bar {
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .trusted {
            animation: fadeInUp 0.8s ease 0.4s both;
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
        <a href="#features">Features</a>
        <a href="#categories">Categories</a>
        <a href="help.php">Help</a>
        <a href="contact.php">Contact</a>
    </div>
    
    <div class="nav-right">

    <a href="register.php" class="btn btn-primary">Register</a>
    <a href="login.php" class="btn btn-outline">Login</a>

    <a href="worker_register.php" class="contractor-link">
    For Workers â†—
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

<!-- Hero Section -->
<main style="padding: 100px 32px; max-width: 800px; margin: 0 auto; min-height: 60vh;">
    <h1 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 36px; margin-bottom: 24px;">Contact Us</h1>
    <p style="font-size: 16px; color: var(--text-secondary); margin-bottom: 32px;">Have a question or need support? Send us a message and our team will get back to you shortly.</p>
    
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px var(--shadow);">
        <form action="#" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Your Name</label>
                <input type="text" placeholder="John Doe" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--bg); color: var(--text-primary); font-family: 'Inter', sans-serif; outline: none;" required>
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Email Address</label>
                <input type="email" placeholder="john@example.com" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--bg); color: var(--text-primary); font-family: 'Inter', sans-serif; outline: none;" required>
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Message</label>
                <textarea placeholder="How can we help?" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--bg); color: var(--text-primary); font-family: 'Inter', sans-serif; outline: none; min-height: 120px; resize: vertical;" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="justify-content: center; margin-top: 10px; padding: 11px 22px; border-radius: 11px; background: linear-gradient(135deg, var(--mint-500), var(--mint-600)); color: white; border: none; font-weight: 600; cursor: pointer;">Send Message</button>
        </form>
    </div>
</main><footer class="footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="index.php" class="logo">
                    Hire<span class="x">X</span>
                </a>
                <p>Connecting skilled professionals with customers who need their services. Trusted, verified, and ready to help.</p>
                <div class="social-links">
                    <a href="#" class="social-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <div class="footer-column">
                <h4>Company</h4>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="careers.php">Careers</a></li>
                    <li><a href="blog.php">Blog</a></li>
                    <li><a href="press.php">Press</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>Support</h4>
                <ul>
                    <li><a href="help.php">Help Center</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="status.php">Service Status</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>Legal</h4>
                <ul>
                    <li><a href="terms.php">Terms of Service</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="cookies.php">Cookie Policy</a></li>
                    <li><a href="security.php">Security</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 HireX. All rights reserved.</p>
            <div class="footer-links">
                <a href="terms.php">Terms</a>
                <a href="privacy.php">Privacy</a>
                <a href="cookies.php">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<script>

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    window.onload = function () {
        window.scrollTo(0, 0);
    };

    function toggleMobileMenu() {
        // Mobile menu toggle functionality
        alert('Mobile menu coming soon!');
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add scroll effect to navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.boxShadow = '0 4px 20px rgba(22, 163, 74, 0.1)';
        } else {
            navbar.style.boxShadow = 'none';
        }
    });
</script>

</body>
</html>

