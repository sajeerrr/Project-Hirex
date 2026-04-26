<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX - Find and hire skilled workers easily">
    <title>HireX — Find Skilled Workers Near You</title>
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
        #categories,
        #help,
        #contact {
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

        /* ==================== HELP / FAQ SECTION ==================== */
        .help-section {
            padding: 80px 32px;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border);
        }

        .help-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 40px;
        }

        .faq-item {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            transition: var(--transition);
        }

        .faq-item:hover {
            border-color: var(--primary);
        }

        .faq-question {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            text-align: left;
            gap: 16px;
            transition: var(--transition);
        }

        .faq-question:hover {
            color: var(--primary);
        }

        .faq-question svg {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            color: var(--text-gray);
            transition: transform 0.3s ease;
        }

        .faq-item.open .faq-question svg {
            transform: rotate(45deg);
            color: var(--primary);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
        }

        .faq-item.open .faq-answer {
            max-height: 200px;
        }

        .faq-answer p {
            padding: 0 24px 20px 24px;
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* ==================== CONTACT SECTION ==================== */
        .contact-section {
            padding: 80px 32px;
        }

        .contact-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            align-items: stretch;
        }

        .contact-form-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 10px 40px var(--shadow);
        }

        .contact-form-card h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .contact-form-card > p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            transition: var(--transition);
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .form-group textarea {
            min-height: 110px;
        }

        .contact-submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .contact-submit-btn:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-lg);
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: space-between;
        }

        .contact-info-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: var(--transition);
        }

        .contact-info-card:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
            box-shadow: 0 12px 35px var(--shadow);
        }

        .contact-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-icon.green {
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200));
            color: var(--mint-600);
        }

        .contact-icon.teal {
            background: linear-gradient(135deg, var(--teal-100), var(--teal-200));
            color: var(--teal-600);
        }

        .contact-icon.yellow {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #b45309;
        }

        .contact-info-card h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .contact-info-card p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .contact-info-card a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .contact-info-card a:hover {
            text-decoration: underline;
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
            .contact-grid {
                grid-template-columns: 1fr;
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
        <a href="#help">Help</a>
        <a href="#contact">Contact</a>
    </div>
    
    <div class="nav-right">

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

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">

        <h1>Find <span>Skilled Workers</span> Near You</h1>
        
        <p class="hero-sub">
            Hire trusted electricians, plumbers, carpenters and technicians instantly
        </p>
        
        <form class="search-bar" action="login.php">
            <input class="search-field" type="text" name="search" placeholder="Service (Electrician, Plumber)" required>
            
            <div class="divider"></div>
            
            <select class="search-select" name="experience" required>
                <option value="" disabled selected hidden>Experience</option>
                <option value="1">1+ Years</option>
                <option value="3">3+ Years</option>
                <option value="5">5+ Years</option>
            </select>
            
            <div class="divider"></div>
            
            <input class="search-field" type="text" name="location" placeholder="Enter City" required>
            
            <button class="search-btn" type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Search
            </button>
        </form>
    </div>
    
    <div class="trusted">
        <p class="trusted-label">Trusted by Skilled Professionals</p>
        
        <div class="logos-row">
            <div class="company-logo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
                Electricians
            </div>
            <div class="company-logo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
                Plumbers
            </div>
            <div class="company-logo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 12l-8.5 8.5c-.83.83-2.17.83-3 0"/>
                    <path d="M17.64 15L22 10.64"/>
                </svg>
                Carpenters
            </div>
            <div class="company-logo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18.37 2.63L14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9"/>
                </svg>
                Painters
            </div>
            <div class="company-logo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6"/>
                    <path d="M10.7 14.7l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91"/>
                </svg>
                Technicians
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features" id="features">
    <div class="features-container">
        <div class="section-header">
            <h2>Why Choose HireX?</h2>
            <p>We connect you with verified professionals for all your home service needs</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon green">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h3>Verified Professionals</h3>
                <p>All workers undergo background checks and skill verification before joining our platform</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon teal">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <h3>Instant Booking</h3>
                <p>Book skilled workers instantly with real-time availability and transparent pricing</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon yellow">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h3>Customer Reviews</h3>
                <p>Read genuine reviews and ratings from previous customers before making your choice</p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories" id="categories">
    <div class="categories-container">
        <div class="section-header">
            <h2>Our Service Categories</h2>
            <p>Find the right professional for any job</p>
        </div>
        
        <div class="categories-grid">
            <a href="#dashboard.php?category=Electrician" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                    ⚡
                </div>
                <h4>Electrician</h4>
                <span>120+ Professionals</span>
            </a>
            
            <a href="#dashboard.php?category=Plumber" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe);">
                    💧
                </div>
                <h4>Plumber</h4>
                <span>95+ Professionals</span>
            </a>
            
            <a href="#dashboard.php?category=Carpenter" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #fed7aa, #fdba74);">
                    🪚
                </div>
                <h4>Carpenter</h4>
                <span>80+ Professionals</span>
            </a>
            
            <a href="#dashboard.php?category=Painter" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #e9d5ff, #d8b4fe);">
                    🖌️
                </div>
                <h4>Painter</h4>
                <span>65+ Professionals</span>
            </a>
            
            <a href="#dashboard.php?category=AC Technician" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #ccfbf1, #99f6e4);">
                    ❄️
                </div>
                <h4>AC Technician</h4>
                <span>55+ Professionals</span>
            </a>
            
            <a href="#dashboard.php?category=Mechanic" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #fecaca, #fca5a5);">
                    🔧
                </div>
                <h4>Mechanic</h4>
                <span>70+ Professionals</span>
            </a>
            
            <a href="#dashboard.php?category=Cleaner" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #ddd6fe, #c4b5fd);">
                    🧹
                </div>
                <h4>Cleaner</h4>
                <span>90+ Professionals</span>
            </a>
            
            <a href="#dashboard.php?category=Appliance Repair" class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #fde68a, #fcd34d);">
                    🔌
                </div>
                <h4>Appliance Repair</h4>
                <span>45+ Professionals</span>
            </a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats">
    <div class="stats-container">
        <div class="stat-item">
            <h3>5,000+</h3>
            <p>Verified Workers</p>
        </div>
        <div class="stat-item">
            <h3>50,000+</h3>
            <p>Happy Customers</p>
        </div>
        <div class="stat-item">
            <h3>100+</h3>
            <p>Cities Covered</p>
        </div>
        <div class="stat-item">
            <h3>4.8★</h3>
            <p>Average Rating</p>
        </div>
    </div>
</section>

<!-- Help / FAQ Section -->
<section class="help-section" id="help">
    <div class="help-container">
        <div class="section-header">
            <h2>How Can We Help?</h2>
            <p>Find quick answers to the most commonly asked questions</p>
        </div>

        <div class="faq-list">
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I hire a worker on HireX?
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Simply create an account, search for the service you need, browse verified worker profiles, and book the one that fits your requirements. You can view ratings, reviews, and experience before making your choice.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Are all workers verified?
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Yes! Every worker on HireX undergoes a thorough background check and skill verification process before they can accept jobs on our platform. Your safety is our top priority.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    What if I'm not satisfied with the work?
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>We offer a satisfaction guarantee. If you're not happy with the service, contact our support team within 48 hours and we'll work to resolve the issue, including arranging a re-service or processing a refund.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I register as a worker?
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Click the "For Workers" link in the navigation bar to access the worker registration page. Fill in your details, upload your credentials, and our team will review your application within 24–48 hours.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Is there a fee to use HireX?
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Creating an account and browsing workers is completely free. You only pay for the services you book. Workers set their own rates, which are displayed transparently on their profiles.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section" id="contact">
    <div class="contact-container">
        <div class="section-header">
            <h2>Get In Touch</h2>
            <p>Have a question or feedback? We'd love to hear from you</p>
        </div>

        <div class="contact-grid">
            <div class="contact-form-card">
                <h3>Send us a Message</h3>
                <p>Fill out the form and we'll get back to you within 24 hours.</p>
                <form onsubmit="handleContactSubmit(event)">
                    <div class="form-group">
                        <label for="contact-name">Full Name</label>
                        <input type="text" id="contact-name" placeholder="Your name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Email Address</label>
                        <input type="email" id="contact-email" placeholder="you@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-subject">Subject</label>
                        <input type="text" id="contact-subject" placeholder="How can we help?" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-message">Message</label>
                        <textarea id="contact-message" placeholder="Tell us more..." required></textarea>
                    </div>
                    <button type="submit" class="contact-submit-btn">
                        Send Message
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </form>
            </div>

            <div class="contact-info">
                <div class="contact-info-card">
                    <div class="contact-icon green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Phone Support</h4>
                        <p>Available Mon–Sat, 9 AM – 6 PM</p>
                        <p><a href="tel:+911234567890">+91 123 456 7890</a></p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-icon teal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Email Us</h4>
                        <p>We usually reply within a few hours</p>
                        <p><a href="mailto:support@hirex.com">support@hirex.com</a></p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-icon yellow">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Visit Us</h4>
                        <p>HireX Headquarters</p>
                        <p>123 Tech Park, Kochi, Kerala 682001</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-icon green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Business Hours</h4>
                        <p>Monday – Saturday: 9:00 AM – 6:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="cta-container">
        <h2>Ready to Get Started?</h2>
        <p>Join thousands of satisfied customers and find the perfect professional for your needs</p>
        <div class="cta-buttons">
            <a href="register.php" class="btn btn-primary">
                Create Account
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
            <a href="#contact.php" class="btn btn-outline">
                Contact Support
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
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
                    <li><a href="#about.php">About Us</a></li>
                    <li><a href="#careers.php">Careers</a></li>
                    <li><a href="#blog.php">Blog</a></li>
                    <li><a href="#press.php">Press</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>Support</h4>
                <ul>
                    <li><a href="#help.php">Help Center</a></li>
                    <li><a href="#contact.php">Contact Us</a></li>
                    <li><a href="#faq.php">FAQ</a></li>
                    <li><a href="#status.php">Service Status</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#terms.php">Terms of Service</a></li>
                    <li><a href="#privacy.php">Privacy Policy</a></li>
                    <li><a href="#cookies.php">Cookie Policy</a></li>
                    <li><a href="#security.php">Security</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 HireX. All rights reserved.</p>
            <div class="footer-links">
                <a href="#terms.php">Terms</a>
                <a href="#privacy.php">Privacy</a>
                <a href="#cookies.php">Cookies</a>
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

    // FAQ Accordion
    function toggleFaq(btn) {
        const item = btn.closest('.faq-item');
        const wasOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item.open').forEach(el => el.classList.remove('open'));
        if (!wasOpen) item.classList.add('open');
    }

    // Contact form handler
    function handleContactSubmit(e) {
        e.preventDefault();
        const btn = e.target.querySelector('.contact-submit-btn');
        btn.textContent = 'Sending...';
        btn.disabled = true;
        setTimeout(() => {
            btn.innerHTML = '✓ Message Sent!';
            btn.style.background = 'linear-gradient(135deg, var(--mint-500), var(--teal-500))';
            e.target.reset();
            setTimeout(() => {
                btn.innerHTML = 'Send Message <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
                btn.disabled = false;
                btn.style.background = '';
            }, 2500);
        }, 1000);
    }
</script>

</body>
</html>
