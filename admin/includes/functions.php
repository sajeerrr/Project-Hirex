<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../database/db.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function admin_table_exists($conn, $table) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $exists = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
    $stmt->close();
    return $exists;
}

function admin_column_exists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $exists = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
    $stmt->close();
    return $exists;
}

function admin_scalar($conn, $sql, $fallback = 0) {
    $result = $conn->query($sql);
    if (!$result) return $fallback;
    $row = $result->fetch_assoc();
    if (!$row) return $fallback;
    $value = reset($row);
    return $value === null ? $fallback : $value;
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function admin_date($date, $format = 'M d, Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

function admin_money($amount) {
    return '₹' . number_format((float) $amount, 2);
}

function admin_status_class($status) {
    $status = strtolower((string) $status);
    if (in_array($status, ['active', 'approved', 'completed', 'confirmed', 'resolved'], true)) return 'status-good';
    if (in_array($status, ['rejected', 'suspended', 'banned', 'cancelled', 'inactive'], true)) return 'status-bad';
    return 'status-warn';
}

function admin_get_admin($conn) {
    $adminId = (int) $_SESSION['admin_id'];
    $statusFilter = admin_column_exists($conn, 'admin', 'status') ? " AND status = 'active'" : "";
    $stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?" . $statusFilter);
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$admin) {
        session_destroy();
        header("Location: ../login.php");
        exit;
    }
    return $admin;
}

function admin_icon($name, $size = 18) {
    $icons = [
        'dashboard' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'users'     => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'calendar'  => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'star'      => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'alert'     => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        'layers'    => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
        'map'       => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
        'chart'     => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'activity'  => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
        'settings'  => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.6.85 1 1.51 1H21a2 2 0 0 1 0 4h-.09c-.66 0-1.25.4-1.51 1z"/></svg>',
        'shield'    => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'logout'    => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'menu'      => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'moon'      => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'sun'       => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'bell'      => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'check'     => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'x'         => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function admin_page_start($title, $active, $subtitle = '') {
    global $conn;
    $admin   = admin_get_admin($conn);
    $name    = htmlspecialchars($admin['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
    $role    = htmlspecialchars($admin['role'] ?? 'admin', ENT_QUOTES, 'UTF-8');
    $initial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $admin['name'] ?? 'Admin'), 0, 1)) ?: 'A';

    // nav groups: [file, key, label, icon]
    $groups = [
        'Main' => [
            ['dashboard.php',   'dashboard',    'Dashboard',      'dashboard'],
            ['workers.php',     'workers',      'Workers',        'users'],
            ['users.php',       'users',        'Users',          'users'],
            ['bookings.php',    'bookings',     'Bookings',       'calendar'],
        ],
        'Moderation' => [
            ['reviews.php',     'reviews',      'Reviews',        'star'],
            ['complaints.php',  'complaints',   'Complaints',     'alert'],
        ],
        'Content' => [
            ['categories.php',  'categories',   'Categories',     'layers'],
            ['cities.php',      'cities',       'Cities',         'map'],
        ],
        'Reports' => [
            ['analytics.php',   'analytics',    'Analytics',      'chart'],
            ['activity_log.php','activity_log', 'Activity Log',   'activity'],
        ],
        'Settings' => [
            ['settings.php',    'settings',     'Settings',       'settings'],
            ['admin.php',       'admin',        'Admin Accounts', 'shield'],
        ],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX Admin — <?php echo e($title); ?>">
    <title><?php echo e($title); ?> — HireX Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==================== DESIGN TOKENS ==================== */
        :root {
            --mint-50:  #f0fdf7;
            --mint-100: #dcfce7;
            --mint-200: #bbf7d0;
            --mint-300: #86efac;
            --mint-400: #4ade80;
            --mint-500: #22c55e;
            --mint-600: #16a34a;
            --teal-100: #ccfbf1;
            --teal-500: #14b8a6;
            --teal-600: #0d9488;

            --bg:             #f8faf9;
            --bg-secondary:   #ffffff;
            --sidebar-width:  250px;
            --primary:        var(--mint-600);
            --primary-hover:  #15803d;
            --primary-light:  var(--mint-100);
            --secondary:      var(--teal-500);
            --text-primary:   #1a2f24;
            --text-secondary: #4a5d55;
            --text-gray:      #789085;
            --border:         #d1e8dd;
            --shadow:         rgba(22,163,74,.08);
            --shadow-lg:      rgba(22,163,74,.15);
            --danger:         #ef4444;
            --success:        var(--mint-500);
            --warning:        #f59e0b;
            --transition:     all 0.35s cubic-bezier(.4,0,.2,1);
        }
        [data-theme="dark"] {
            --bg:             #0d1411;
            --bg-secondary:   #141c18;
            --text-primary:   #e0f2e8;
            --text-secondary: #9dbfa8;
            --text-gray:      #789085;
            --border:         #2d3d33;
            --shadow:         rgba(0,0,0,.4);
            --shadow-lg:      rgba(0,0,0,.6);
            --primary:        var(--mint-500);
            --primary-hover:  var(--mint-400);
            --primary-light:  rgba(34,197,94,.15);
        }

        /* ==================== RESET ==================== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        svg { display: block; }

        /* ==================== BODY ==================== */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            display: flex;
            line-height: 1.6;
            overflow-x: hidden;
            transition: var(--transition);
            background-image:
                radial-gradient(ellipse at top right, rgba(34,197,94,.06) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(20,184,166,.08) 0%, transparent 50%);
        }

        /* ==================== SIDEBAR ==================== */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1000;
            transition: var(--transition);
            overflow: hidden;
        }
        .logo {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 32px;
            padding-left: 14px;
            letter-spacing: -.5px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        .logo .x { color: var(--primary); }
        .admin-badge {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: #fff;
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .sidebar nav {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
            margin-right: -4px;
        }
        .nav-group { margin-bottom: 22px; }
        .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-gray);
            margin-bottom: 10px;
            padding-left: 14px;
            font-weight: 700;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 3px;
            transition: var(--transition);
            cursor: pointer;
        }
        .nav-item:hover {
            background: var(--primary-light);
            color: var(--primary);
            transform: translateX(4px);
        }
        .nav-item.active {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: #fff;
            box-shadow: 0 4px 15px var(--shadow-lg);
        }
        .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }

        .signout-container {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }
        .signout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            width: 100%;
            text-decoration: none;
            color: var(--danger);
            background: #fef2f2;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
        }
        [data-theme="dark"] .signout-btn { background: rgba(239,68,68,.15); }
        .signout-btn:hover { background: var(--danger); color: #fff; transform: translateX(4px); }
        .signout-btn svg { width: 18px; height: 18px; }

        /* ==================== MAIN ==================== */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 0 32px 40px;
            min-width: 0;
            transition: var(--transition);
        }

        /* ==================== MOBILE TOGGLE ==================== */
        .mobile-toggle {
            display: none;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            cursor: pointer;
            color: var(--text-primary);
            transition: var(--transition);
        }
        .mobile-toggle:hover { background: var(--primary-light); border-color: var(--primary); }

        /* ==================== OVERLAY ==================== */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(13,20,17,.65);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            transition: var(--transition);
        }
        .overlay.active { display: block; opacity: 1; }

        /* ==================== HEADER ==================== */
        header {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 13px;
            flex: 1;
        }
        .page-title-header h1 {
            font-size: 24px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
            margin: 0;
        }
        .page-title-header p {
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 3px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 11px;
        }
        .theme-toggle {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 10px 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-secondary);
            transition: var(--transition);
            font-weight: 500;
            font-family: 'Inter', sans-serif;
        }
        .theme-toggle:hover { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 5px 15px 5px 5px;
            cursor: default;
            transition: var(--transition);
        }
        .user-pill:hover { border-color: var(--primary); box-shadow: 0 4px 14px var(--shadow); }
        .avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #fff;
            flex-shrink: 0;
        }
        .user-name { font-size: 13px; font-weight: 600; color: var(--text-primary); font-family: 'Plus Jakarta Sans', sans-serif; }
        .user-role { font-size: 10px; color: var(--text-gray); font-weight: 500; }

        /* ==================== STAT GRID ==================== */
        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin: 8px 0 24px;
        }
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 22px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px; height: 100%;
            background: linear-gradient(180deg, var(--mint-500), var(--teal-500));
            border-radius: 14px 0 0 14px;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px var(--shadow); border-color: var(--primary); }
        .stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--text-gray);
            margin-bottom: 6px;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            line-height: 1.1;
        }

        /* ==================== TABLE CARD ==================== */
        .table-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 20px 22px;
            margin-bottom: 22px;
            transition: var(--transition);
        }
        .table-card:hover { box-shadow: 0 8px 28px var(--shadow); }

        /* ==================== TOOLBAR ==================== */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }
        .toolbar strong {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* ==================== SEARCH & INPUTS ==================== */
        .search, select {
            min-width: 220px;
            max-width: 340px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 13px;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            outline: none;
        }
        .search:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(34,197,94,.12); }
        [data-theme="dark"] .search, [data-theme="dark"] select { background: var(--bg); }
        select { cursor: pointer; }

        /* ==================== BUTTONS ==================== */
        .btn {
            cursor: pointer;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 16px;
            font-weight: 700;
            font-size: 12px;
            color: var(--text-primary);
            background: var(--bg-secondary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }
        .btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .btn-primary { background: linear-gradient(135deg, var(--mint-500), var(--mint-600)); color: #fff; border-color: var(--mint-600); }
        .btn-primary:hover { background: linear-gradient(135deg, var(--mint-600), var(--primary-hover)); color: #fff; border-color: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 14px var(--shadow-lg); }
        .btn-danger { color: var(--danger); background: rgba(239,68,68,.1); border-color: rgba(239,68,68,.25); }
        .btn-danger:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

        /* ==================== TABLES ==================== */
        table { width: 100%; border-collapse: collapse; }
        thead tr { border-bottom: 2px solid var(--border); }
        th {
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }
        td {
            padding: 13px 12px;
            font-size: 13px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tbody tr { transition: background .18s; }
        tbody tr:hover td { background: rgba(34,197,94,.04); }
        [data-theme="dark"] tbody tr:hover td { background: rgba(34,197,94,.08); }

        /* ==================== STATUS BADGES ==================== */
        .status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            padding: 4px 11px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .status-good { color: #15803d; background: rgba(34,197,94,.12); }
        .status-warn { color: #b45309; background: rgba(245,158,11,.12); }
        .status-bad  { color: #b91c1c; background: rgba(239,68,68,.12); }

        /* ==================== ROW ACTIONS ==================== */
        .row-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

        /* ==================== SUBTITLE ==================== */
        .subtitle { color: var(--text-gray); font-size: 12px; margin-top: 2px; }

        /* ==================== EMPTY ==================== */
        .empty {
            text-align: center;
            color: var(--text-gray);
            padding: 36px 16px;
            font-size: 13px;
        }

        /* ==================== TOAST ==================== */
        .toast {
            position: fixed;
            bottom: 26px;
            right: 26px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-left: 5px solid var(--success);
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 14px 45px var(--shadow-lg);
            transform: translateX(160%);
            transition: var(--transition);
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
        }
        .toast.show  { transform: translateX(0); }
        .toast.error { border-left-color: var(--danger); }
        .toast-title   { font-weight: 600; color: var(--text-primary); font-size: 13px; }
        .toast-message { font-size: 12px; color: var(--text-gray); margin-top: 2px; }

        /* ==================== SCROLLBAR ==================== */
        ::-webkit-scrollbar       { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--mint-300); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--mint-500); }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1100px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 18px 32px; }
            .mobile-toggle { display: flex; }
        }
        @media (max-width: 560px) {
            .grid { grid-template-columns: 1fr; }
            td, th { padding: 10px 8px; }
            .search, select { min-width: 0; width: 100%; max-width: 100%; }
            .toolbar { flex-direction: column; align-items: flex-start; }
            .toast { left: 16px; right: 16px; bottom: 16px; min-width: 0; }
        }
        @media (max-width: 420px) {
            .user-role { display: none; }
            .theme-toggle span:last-child { display: none; }
        }
    </style>
</head>
<body>

<div class="overlay" id="adminOverlay" onclick="adminToggleSidebar()"></div>

<!-- ==================== SIDEBAR ==================== -->
<aside class="sidebar" id="adminSidebar">
    <div class="logo">
        Hire<span class="x">X</span>
        <span class="admin-badge">Admin</span>
    </div>
    <nav>
        <?php foreach ($groups as $groupLabel => $items): ?>
        <div class="nav-group">
            <div class="nav-label"><?php echo e($groupLabel); ?></div>
            <?php foreach ($items as $item): ?>
            <a href="<?php echo e($item[0]); ?>" class="nav-item <?php echo $active === $item[1] ? 'active' : ''; ?>">
                <?php echo admin_icon($item[3], 18); ?>
                <?php echo e($item[2]); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>
    <div class="signout-container">
        <a href="logout.php" class="signout-btn">
            <?php echo admin_icon('logout', 18); ?> Sign Out
        </a>
    </div>
</aside>

<!-- ==================== MAIN ==================== -->
<main class="main-content" id="adminMain">
    <header>
        <div class="header-left">
            <button class="mobile-toggle" onclick="adminToggleSidebar()" aria-label="Menu">
                <?php echo admin_icon('menu', 20); ?>
            </button>
            <div class="page-title-header">
                <h1><?php echo e($title); ?></h1>
                <?php if ($subtitle): ?>
                <p><?php echo e($subtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-actions">
            <button class="theme-toggle" onclick="adminToggleTheme()" aria-label="Toggle theme">
                <span id="adminThemeIcon"><?php echo admin_icon('moon', 16); ?></span>
                <span id="adminThemeText">Dark</span>
            </button>
            <div class="user-pill">
                <div class="avatar"><?php echo e($initial); ?></div>
                <div>
                    <div class="user-name"><?php echo $name; ?></div>
                    <div class="user-role"><?php echo $role; ?></div>
                </div>
            </div>
        </div>
    </header>
<?php
}

function admin_page_end() {
    ?>
</main>

<!-- Toast -->
<div class="toast" id="adminToast">
    <div class="toast-content">
        <div class="toast-title" id="adminToastTitle">Done</div>
        <div class="toast-message" id="adminToastMsg"></div>
    </div>
</div>

<script>
    /* Sidebar */
    function adminToggleSidebar() {
        const sb = document.getElementById('adminSidebar');
        const ov = document.getElementById('adminOverlay');
        sb.classList.toggle('open');
        ov.classList.toggle('active');
        document.body.style.overflow = sb.classList.contains('open') ? 'hidden' : '';
    }
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') {
            const sb = document.getElementById('adminSidebar');
            if (sb && sb.classList.contains('open')) adminToggleSidebar();
        }
    });

    /* Theme */
    (function(){
        const saved = localStorage.getItem('adminTheme');
        if (saved === 'dark') {
            document.documentElement.setAttribute('data-theme','dark');
            const icon = document.getElementById('adminThemeIcon');
            const txt  = document.getElementById('adminThemeText');
            if (icon) icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
            if (txt) txt.textContent = 'Light';
        }
    })();

    function adminToggleTheme() {
        const html = document.documentElement;
        const icon = document.getElementById('adminThemeIcon');
        const txt  = document.getElementById('adminThemeText');
        const moonSVG = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
        const sunSVG  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
        if (html.getAttribute('data-theme') === 'dark') {
            html.removeAttribute('data-theme');
            if (icon) icon.innerHTML = moonSVG;
            if (txt)  txt.textContent = 'Dark';
            localStorage.setItem('adminTheme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            if (icon) icon.innerHTML = sunSVG;
            if (txt)  txt.textContent = 'Light';
            localStorage.setItem('adminTheme', 'dark');
        }
    }

    /* Toast */
    function adminShowToast(title, msg, success) {
        const t = document.getElementById('adminToast');
        document.getElementById('adminToastTitle').textContent = title;
        document.getElementById('adminToastMsg').textContent   = msg || '';
        t.style.borderLeftColor = success === false ? 'var(--danger)' : 'var(--success)';
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3200);
    }

    /* Auto-show toast on redirect messages */
    (function(){
        const params = new URLSearchParams(window.location.search);
        if (params.get('updated') === '1') adminShowToast('Updated', 'Status updated successfully.', true);
        if (params.get('error')   === '1') adminShowToast('Error',   'Something went wrong.',        false);
    })();
</script>
</body>
</html>
<?php
}

function admin_status_form($table, $id, $status, $statuses) {
    ?>
    <form class="row-actions" method="post" action="actions/update_status.php">
        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="table" value="<?php echo e($table); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <select name="status">
            <?php foreach ($statuses as $option): ?>
                <option value="<?php echo e($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo e(ucwords(str_replace('_', ' ', $option))); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
    <?php
}
