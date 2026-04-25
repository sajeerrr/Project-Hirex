<?php
session_start();
include("../database/db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$worker_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$worker_id) {
    header("Location: dashboard.php");
    exit;
}

// Load current user
$userResult = $conn->query("SELECT * FROM users WHERE id='$user_id'");
$user = $userResult->fetch_assoc();
$userName    = htmlspecialchars($user['name'] ?? '');
$userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $userName), 0, 1)) ?: 'A';

// Load worker
$workerResult = $conn->query("SELECT * FROM workers WHERE id='$worker_id'");
if (!$workerResult || $workerResult->num_rows === 0) {
    header("Location: dashboard.php");
    exit;
}
$worker = $workerResult->fetch_assoc();

// Photo path
$photo = $worker['photo'];
$photoPath = filter_var($photo, FILTER_VALIDATE_URL)
    ? $photo
    : "../assets/images/workers/" . $photo;
$fallback = "https://ui-avatars.com/api/?name=" . urlencode($worker['name']) . "&size=200&background=16a34a&color=fff&rounded=true";

// Reviews for this worker
$reviewsResult = $conn->query("
    SELECT r.*, u.name as user_name, u.photo as user_photo
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.worker_id = '$worker_id'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviews = [];
if ($reviewsResult) {
    while ($row = $reviewsResult->fetch_assoc()) $reviews[] = $row;
}

// Check if already booked (pending/confirmed)
$alreadyBooked = false;
$bookingCheck = $conn->query("SELECT id FROM bookings WHERE user_id='$user_id' AND worker_id='$worker_id' AND status IN ('pending','confirmed') LIMIT 1");
if ($bookingCheck && $bookingCheck->num_rows > 0) $alreadyBooked = true;

// SVG icons
function getIcon($name, $size = 20, $class = '') {
    $icons = [
        'dashboard'  => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'user'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'message'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'calendar'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'bookmark'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>',
        'card'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'settings'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'help'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'phone'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'logout'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'menu'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'moon'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'sun'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'bell'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'star'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'star-empty' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'check'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'x'          => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'location'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'briefcase'  => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'clock'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'back'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
        'bolt'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
        'drop'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
        'hammer'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12l-8.5 8.5c-.83.83-2.17.83-3 0a2.12 2.12 0 0 1 0-3L12 9"/><path d="M17.64 15L22 10.64"/><path d="M20.91 11.26a2 2 0 0 0 2.83-.25l.5-.5a2 2 0 0 0-.25-2.83L22 5.64l-6.36-6.36a2 2 0 0 0-2.83.25l-.5.5a2 2 0 0 0 .25 2.83L14.36 5"/></svg>',
        'brush'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.37 2.63L14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3z"/><path d="M9 8c-2 3-4 3.5-7 4l8 10c2-1 6-5 6-7"/><path d="M14.5 17.5L4.5 15"/></svg>',
        'snowflake'  => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/><path d="M20 16l-4-4 4-4"/><path d="M4 8l4 4-4 4"/><path d="M16 4l-4 4-4-4"/><path d="M8 20l4-4 4 4"/></svg>',
        'wrench'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'shield'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'workers'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'close'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'info'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function roleIcon($role) {
    $map = ['Electrician'=>'bolt','Plumber'=>'drop','Carpenter'=>'hammer','Painter'=>'brush','AC Technician'=>'snowflake','Mechanic'=>'wrench'];
    return $map[$role] ?? 'briefcase';
}

function stars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round($rating)
            ? '<span style="color:#f59e0b;">'.getIcon('star', 14).'</span>'
            : '<span style="color:#d1e8dd;">'.getIcon('star-empty', 14).'</span>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($worker['name']); ?> — HireX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --mint-50:#f0fdf7;--mint-100:#dcfce7;--mint-200:#bbf7d0;--mint-300:#86efac;
            --mint-400:#4ade80;--mint-500:#22c55e;--mint-600:#16a34a;
            --teal-100:#ccfbf1;--teal-500:#14b8a6;--teal-600:#0d9488;
            --bg:#f8faf9;--bg-secondary:#ffffff;--sidebar-width:250px;
            --primary:var(--mint-600);--primary-hover:#15803d;--primary-light:var(--mint-100);
            --text-primary:#1a2f24;--text-secondary:#4a5d55;--text-gray:#789085;
            --border:#d1e8dd;--shadow:rgba(22,163,74,0.08);--shadow-lg:rgba(22,163,74,0.15);
            --danger:#ef4444;--success:var(--mint-500);--warning:#f59e0b;
            --transition:all 0.35s cubic-bezier(0.4,0,0.2,1);
        }
        [data-theme="dark"] {
            --bg:#0d1411;--bg-secondary:#141c18;--text-primary:#e0f2e8;
            --text-secondary:#9dbfa8;--text-gray:#789085;--border:#2d3d33;
            --shadow:rgba(0,0,0,0.4);--shadow-lg:rgba(0,0,0,0.6);
            --primary:var(--mint-500);--primary-hover:var(--mint-400);
            --primary-light:rgba(34,197,94,0.15);
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{
            font-family:'Inter',sans-serif;background:var(--bg);display:flex;
            color:var(--text-primary);transition:var(--transition);overflow-x:hidden;line-height:1.6;
            background-image:radial-gradient(ellipse at top right,rgba(34,197,94,0.06) 0%,transparent 50%),
                             radial-gradient(ellipse at bottom left,rgba(20,184,166,0.08) 0%,transparent 50%);
        }
        svg{display:block;}

        /* ── SIDEBAR ── */
        .sidebar{width:var(--sidebar-width);height:100vh;background:var(--bg-secondary);padding:24px 16px;display:flex;flex-direction:column;position:fixed;border-right:1px solid var(--border);z-index:1000;transition:var(--transition);}
        .logo{font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;font-weight:800;margin-bottom:32px;padding-left:14px;letter-spacing:-0.5px;color:var(--text-primary);}
        .logo .x{color:var(--primary);}
        .nav-group{margin-bottom:24px;}
        .nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-gray);margin-bottom:12px;padding-left:14px;font-weight:700;}
        .nav-item{display:flex;align-items:center;padding:11px 14px;text-decoration:none;color:var(--text-secondary);border-radius:10px;margin-bottom:4px;transition:var(--transition);font-weight:500;font-size:13px;gap:12px;}
        .nav-item:hover{background:var(--primary-light);color:var(--primary);transform:translateX(4px);}
        .nav-item.active{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;box-shadow:0 4px 15px var(--shadow-lg);}
        .nav-item svg{width:18px;height:18px;}
        .badge{background:var(--danger);color:white;font-size:9px;padding:2px 6px;border-radius:6px;margin-left:auto;font-weight:700;}
        .signout-container{margin-top:auto;padding-top:16px;border-top:1px solid var(--border);}
        .signout-btn{display:flex;align-items:center;gap:12px;padding:11px 14px;width:100%;text-decoration:none;color:var(--danger);background:#fef2f2;border-radius:10px;font-weight:600;font-size:13px;transition:var(--transition);}
        [data-theme="dark"] .signout-btn{background:rgba(239,68,68,0.15);}
        .signout-btn:hover{background:var(--danger);color:white;transform:translateX(4px);}

        /* ── MAIN ── */
        .main-content{margin-left:var(--sidebar-width);flex:1;padding:0 32px 48px;transition:var(--transition);}
        .mobile-toggle{display:none;background:var(--bg-secondary);border:1px solid var(--border);cursor:pointer;color:var(--text-primary);padding:10px 12px;border-radius:10px;transition:var(--transition);}

        /* ── HEADER ── */
        header{min-height:70px;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:22px;}
        .header-left{display:flex;align-items:center;gap:13px;}
        .header-actions{display:flex;align-items:center;gap:11px;}
        .icon-btn{background:var(--bg-secondary);border:1px solid var(--border);border-radius:11px;width:42px;height:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);position:relative;}
        .icon-btn:hover{background:var(--primary);color:white;border-color:var(--primary);transform:translateY(-2px);}
        .notification-dot{position:absolute;top:8px;right:8px;width:8px;height:8px;background:var(--danger);border-radius:50%;border:2px solid var(--bg-secondary);}
        .user-pill{display:flex;align-items:center;gap:10px;background:var(--bg-secondary);padding:5px 15px 5px 5px;border-radius:30px;border:1px solid var(--border);cursor:pointer;transition:var(--transition);text-decoration:none;}
        .user-pill:hover{border-color:var(--primary);}
        .avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}
        .user-name{font-size:13px;font-weight:600;color:var(--text-primary);font-family:'Plus Jakarta Sans',sans-serif;}
        .theme-toggle{background:var(--bg-secondary);border:1px solid var(--border);border-radius:11px;padding:10px 13px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-secondary);transition:var(--transition);font-weight:500;}
        .theme-toggle:hover{border-color:var(--primary);background:var(--primary-light);}

        /* ── BREADCRUMB ── */
        .breadcrumb{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-gray);margin-bottom:20px;}
        .breadcrumb a{color:var(--text-gray);text-decoration:none;transition:var(--transition);}
        .breadcrumb a:hover{color:var(--primary);}
        .breadcrumb span{color:var(--text-secondary);font-weight:500;}
        .breadcrumb-sep{opacity:0.4;}

        /* ── DETAILS LAYOUT ── */
        .details-layout{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;}

        /* ── LEFT COLUMN ── */
        .left-col{display:flex;flex-direction:column;gap:22px;}

        /* Hero Card */
        .hero-card{
            background:var(--bg-secondary);border:1px solid var(--border);border-radius:20px;
            overflow:hidden;box-shadow:0 4px 20px var(--shadow);position:relative;
        }
        .hero-banner{
            height:120px;
            background:linear-gradient(135deg,var(--mint-500) 0%,var(--teal-500) 50%,var(--mint-600) 100%);
            position:relative;
            overflow:hidden;
        }
        .hero-banner::after{
            content:'';position:absolute;inset:0;
            background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-body{padding:0 28px 28px;}
        .hero-photo-row{display:flex;align-items:flex-end;justify-content:space-between;margin-top:-52px;margin-bottom:16px;position:relative;z-index:2;}
        .hero-photo-wrap{position:relative;}
        .hero-photo{width:104px;height:104px;border-radius:50%;object-fit:cover;border:4px solid var(--bg-secondary);box-shadow:0 4px 20px var(--shadow-lg);}
        .avail-badge{
            position:absolute;bottom:4px;right:4px;
            font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;
            display:flex;align-items:center;gap:4px;
        }
        .avail-badge.available{background:var(--mint-500);color:white;}
        .avail-badge.busy{background:var(--danger);color:white;}
        .avail-badge svg{width:9px;height:9px;}

        .hero-quick-stats{display:flex;gap:10px;}
        .quick-stat{background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:10px 16px;text-align:center;}
        .quick-stat-val{font-size:17px;font-weight:700;color:var(--primary);font-family:'Plus Jakarta Sans',sans-serif;}
        .quick-stat-label{font-size:10px;color:var(--text-gray);font-weight:600;text-transform:uppercase;letter-spacing:0.4px;margin-top:1px;}

        .hero-name{font-size:22px;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary);margin-bottom:4px;}
        .hero-role{
            display:inline-flex;align-items:center;gap:6px;
            color:var(--primary);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;
            background:var(--primary-light);padding:5px 12px;border-radius:20px;margin-bottom:12px;
        }
        .hero-role svg{width:13px;height:13px;}
        .hero-meta-row{display:flex;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:14px;}
        .hero-meta-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);}
        .hero-meta-item svg{width:14px;height:14px;color:var(--text-gray);}
        .hero-bio{font-size:13px;color:var(--text-secondary);line-height:1.7;}

        /* Info Cards */
        .info-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px var(--shadow);}
        .info-card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;background:linear-gradient(135deg,var(--mint-50),var(--bg-secondary));}
        [data-theme="dark"] .info-card-header{background:linear-gradient(135deg,rgba(34,197,94,0.05),transparent);}
        .info-card-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:var(--primary-light);color:var(--primary);}
        [data-theme="dark"] .info-card-icon{background:rgba(34,197,94,0.15);color:var(--mint-400);}
        .info-card-icon svg{width:18px;height:18px;}
        .info-card-title{font-size:14px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;}
        .info-card-body{padding:20px;}

        /* Skills */
        .skills-wrap{display:flex;flex-wrap:wrap;gap:8px;}
        .skill-chip{padding:6px 14px;background:var(--mint-50);border:1px solid var(--mint-200);color:var(--primary);border-radius:20px;font-size:12px;font-weight:600;}
        [data-theme="dark"] .skill-chip{background:rgba(34,197,94,0.1);border-color:rgba(34,197,94,0.25);color:var(--mint-400);}

        /* Reviews */
        .review-item{padding:14px 0;border-bottom:1px solid var(--border);}
        .review-item:last-child{border-bottom:none;padding-bottom:0;}
        .review-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
        .review-user{display:flex;align-items:center;gap:9px;}
        .review-avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
        .review-name{font-size:13px;font-weight:600;}
        .review-date{font-size:11px;color:var(--text-gray);}
        .review-stars{display:flex;gap:2px;align-items:center;}
        .review-text{font-size:13px;color:var(--text-secondary);line-height:1.6;margin-top:4px;}
        .no-reviews{text-align:center;padding:28px;color:var(--text-gray);}
        .no-reviews svg{margin:0 auto 10px;opacity:0.35;}
        .no-reviews p{font-size:13px;}

        /* ── RIGHT COLUMN (sticky booking card) ── */
        .right-col{position:sticky;top:24px;display:flex;flex-direction:column;gap:16px;}

        .booking-card{
            background:var(--bg-secondary);border:1px solid var(--border);border-radius:18px;
            overflow:hidden;box-shadow:0 6px 24px var(--shadow);
        }
        .booking-card-top{
            background:linear-gradient(135deg,var(--mint-500),var(--teal-500));
            padding:22px 22px 18px;color:white;
        }
        .booking-price{font-size:32px;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif;line-height:1;}
        .booking-price-unit{font-size:13px;opacity:0.85;margin-top:4px;font-weight:500;}
        .booking-card-body{padding:20px;}

        /* CTA Buttons */
        .btn-book{
            display:flex;align-items:center;justify-content:center;gap:9px;
            width:100%;padding:13px;border-radius:12px;
            background:linear-gradient(135deg,var(--mint-500),var(--mint-600));
            color:white;border:none;font-size:14px;font-weight:700;
            cursor:pointer;transition:var(--transition);font-family:'Plus Jakarta Sans',sans-serif;
            margin-bottom:10px;
        }
        .btn-book:hover{transform:translateY(-2px);box-shadow:0 8px 24px var(--shadow-lg);}
        .btn-book:disabled{opacity:0.55;cursor:not-allowed;transform:none;}
        .btn-book svg{width:16px;height:16px;}

        .btn-message{
            display:flex;align-items:center;justify-content:center;gap:9px;
            width:100%;padding:12px;border-radius:12px;
            background:transparent;color:var(--primary);
            border:2px solid var(--primary);
            font-size:14px;font-weight:700;cursor:pointer;transition:var(--transition);
            font-family:'Plus Jakarta Sans',sans-serif;text-decoration:none;
        }
        .btn-message:hover{background:var(--primary-light);}
        .btn-message svg{width:16px;height:16px;}

        /* Divider */
        .card-divider{border:none;border-top:1px dashed var(--border);margin:16px 0;}

        /* Info rows in booking card */
        .bk-row{display:flex;align-items:center;justify-content:space-between;font-size:12px;margin-bottom:10px;}
        .bk-row:last-child{margin-bottom:0;}
        .bk-row .label{color:var(--text-gray);display:flex;align-items:center;gap:6px;}
        .bk-row .label svg{width:13px;height:13px;}
        .bk-row .value{font-weight:600;color:var(--text-primary);}
        .bk-row .value.green{color:var(--primary);}
        .bk-row .value.red{color:var(--danger);}

        /* Guarantee badge */
        .guarantee{
            display:flex;align-items:center;gap:10px;
            background:var(--mint-50);border:1px solid var(--mint-200);
            border-radius:10px;padding:12px 14px;font-size:12px;color:var(--text-secondary);
        }
        [data-theme="dark"] .guarantee{background:rgba(34,197,94,0.08);border-color:rgba(34,197,94,0.2);}
        .guarantee svg{color:var(--primary);flex-shrink:0;}

        /* ── BOOKING MODAL ── */
        .modal-overlay{
            display:none;position:fixed;inset:0;
            background:rgba(13,20,17,0.7);backdrop-filter:blur(6px);
            z-index:2000;align-items:center;justify-content:center;padding:20px;
        }
        .modal-overlay.open{display:flex;}
        .modal{
            background:var(--bg-secondary);border-radius:20px;width:100%;max-width:460px;
            box-shadow:0 24px 60px rgba(0,0,0,0.3);animation:modalIn 0.3s ease;
            overflow:hidden;
        }
        @keyframes modalIn{from{opacity:0;transform:scale(0.94) translateY(10px);}to{opacity:1;transform:none;}}
        .modal-header{
            background:linear-gradient(135deg,var(--mint-500),var(--teal-500));
            padding:20px 24px;display:flex;align-items:center;justify-content:space-between;
        }
        .modal-header h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:17px;font-weight:700;color:white;}
        .modal-close{background:rgba(255,255,255,0.2);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;display:flex;align-items:center;justify-content:center;transition:var(--transition);}
        .modal-close:hover{background:rgba(255,255,255,0.35);}
        .modal-body{padding:24px;}
        .modal-worker-row{display:flex;align-items:center;gap:13px;padding:14px;background:var(--bg);border:1px solid var(--border);border-radius:12px;margin-bottom:20px;}
        .modal-worker-photo{width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);}
        .modal-worker-name{font-weight:700;font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;}
        .modal-worker-role{font-size:12px;color:var(--text-gray);}

        .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:14px;}
        .form-group label{font-size:12px;font-weight:600;color:var(--text-secondary);}
        .form-group input, .form-group textarea, .form-group select{
            padding:10px 13px;border:1px solid var(--border);border-radius:10px;
            background:var(--bg);color:var(--text-primary);font-size:13px;
            font-family:'Inter',sans-serif;outline:none;transition:var(--transition);
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(22,163,74,0.12);}
        .form-group textarea{min-height:80px;resize:vertical;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .modal-footer{padding:0 24px 24px;display:flex;gap:10px;}
        .btn-cancel{flex:1;padding:11px;border-radius:10px;background:var(--bg);border:1px solid var(--border);color:var(--text-secondary);font-size:13px;font-weight:600;cursor:pointer;transition:var(--transition);}
        .btn-cancel:hover{border-color:var(--danger);color:var(--danger);}
        .btn-confirm{flex:2;padding:11px;border-radius:10px;background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;border:none;font-size:13px;font-weight:700;cursor:pointer;transition:var(--transition);font-family:'Plus Jakarta Sans',sans-serif;}
        .btn-confirm:hover{transform:translateY(-1px);box-shadow:0 5px 16px var(--shadow-lg);}

        /* Toast */
        .toast{position:fixed;bottom:26px;right:26px;background:var(--bg-secondary);border:1px solid var(--border);border-left:5px solid var(--success);padding:15px 20px;border-radius:12px;box-shadow:0 14px 45px var(--shadow-lg);transform:translateX(150%);transition:var(--transition);z-index:3000;display:flex;align-items:center;gap:12px;min-width:280px;}
        .toast.show{transform:translateX(0);}
        .toast.error{border-left-color:var(--danger);}
        .toast-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .toast.success .toast-icon{background:rgba(34,197,94,0.15);color:var(--success);}
        .toast.error .toast-icon{background:rgba(239,68,68,0.15);color:var(--danger);}
        .toast-icon svg{width:17px;height:17px;}
        .toast-content{flex:1;}
        .toast-title{font-weight:600;color:var(--text-primary);font-size:13px;}
        .toast-msg{font-size:12px;color:var(--text-gray);margin-top:2px;}

        /* Overlay */
        .overlay{display:none;position:fixed;inset:0;background:rgba(13,20,17,0.65);backdrop-filter:blur(4px);z-index:999;opacity:0;transition:var(--transition);}
        .overlay.active{display:block;opacity:1;}

        /* ── RESPONSIVE ── */
        @media(max-width:1024px){.details-layout{grid-template-columns:1fr 300px;}}
        @media(max-width:860px){.details-layout{grid-template-columns:1fr;}.right-col{position:static;}}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);}.sidebar.active{transform:translateX(0);}
            .main-content{margin-left:0;padding:0 18px 32px;}
            .mobile-toggle{display:flex;}
            .hero-quick-stats{flex-wrap:wrap;}
            .toast{left:18px;right:18px;bottom:18px;min-width:auto;}
        }
        @media(max-width:480px){.user-name{display:none;}.form-row{grid-template-columns:1fr;}}
        ::-webkit-scrollbar{width:6px;}
        ::-webkit-scrollbar-track{background:var(--bg);}
        ::-webkit-scrollbar-thumb{background:var(--mint-300);border-radius:3px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--mint-500);}
    </style>
</head>
<body>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="logo">Hire<span class="x">X</span></div>
    <nav>
        <div class="nav-group">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php" class="nav-item active"><?php echo getIcon('dashboard',18); ?> Dashboard</a>
            <a href="profile.php" class="nav-item"><?php echo getIcon('user',18); ?> My Profile</a>
            <a href="messages.php" class="nav-item"><?php echo getIcon('message',18); ?> Messages <span class="badge">3</span></a>
            <a href="booking.php" class="nav-item"><?php echo getIcon('calendar',18); ?> My Bookings</a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Preferences</div>
            <a href="saved-worker.php" class="nav-item"><?php echo getIcon('bookmark',18); ?> Saved Workers</a>
            <a href="payment.php" class="nav-item"><?php echo getIcon('card',18); ?> Payments</a>
            <a href="settings.php" class="nav-item"><?php echo getIcon('settings',18); ?> Settings</a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="help.php" class="nav-item"><?php echo getIcon('help',18); ?> Help Center</a>
            <a href="contact.php" class="nav-item"><?php echo getIcon('phone',18); ?> Contact Us</a>
        </div>
    </nav>
    <div class="signout-container">
        <a href="logout.php" class="signout-btn"><?php echo getIcon('logout',18); ?> Sign Out</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main-content" id="mainContent">
    <header>
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()"><?php echo getIcon('menu',20); ?></button>
        </div>
        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()">
                <span id="themeIcon"><?php echo getIcon('moon',16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            <button class="icon-btn"><?php echo getIcon('bell',18); ?><span class="notification-dot"></span></button>
            <a href="user-profile.php" class="user-pill">
                <div class="avatar"><?php echo $userInitial; ?></div>
                <span class="user-name"><?php echo $userName; ?></span>
            </a>
        </div>
    </header>

    <!-- Breadcrumb -->
    

    <div class="details-layout">

        <!-- ── LEFT COLUMN ── -->
        <div class="left-col">

            <!-- Hero Card -->
            <div class="hero-card">
                <div class="hero-banner"></div>
                <div class="hero-body">
                    <div class="hero-photo-row">
                        <div class="hero-photo-wrap">
                            <img src="<?php echo $photoPath; ?>"
                                 onerror="this.src='<?php echo $fallback; ?>'"
                                 alt="<?php echo htmlspecialchars($worker['name']); ?>"
                                 class="hero-photo">
                            <span class="avail-badge <?php echo $worker['available'] ? 'available' : 'busy'; ?>">
                                <?php echo $worker['available'] ? getIcon('check',9).' Available' : getIcon('x',9).' Busy'; ?>
                            </span>
                        </div>
                        <div class="hero-quick-stats">
                            <div class="quick-stat">
                                <div class="quick-stat-val"><?php echo $worker['rating']; ?></div>
                                <div class="quick-stat-label">Rating</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-val"><?php echo $worker['jobs']; ?></div>
                                <div class="quick-stat-label">Jobs</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-val"><?php echo $worker['reviews']; ?></div>
                                <div class="quick-stat-label">Reviews</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-val"><?php echo $worker['experience']; ?>yr</div>
                                <div class="quick-stat-label">Exp.</div>
                            </div>
                        </div>
                    </div>

                    <div class="hero-name"><?php echo htmlspecialchars($worker['name']); ?></div>
                    <div class="hero-role">
                        <?php echo getIcon(roleIcon($worker['role']), 13); ?>
                        <?php echo htmlspecialchars($worker['role']); ?>
                    </div>
                    <div class="hero-meta-row">
                        <span class="hero-meta-item"><?php echo getIcon('location',14); ?> <?php echo htmlspecialchars($worker['location']); ?></span>
                        <span class="hero-meta-item"><?php echo getIcon('clock',14); ?> <?php echo $worker['experience']; ?> years exp.</span>
                        <span class="hero-meta-item">
                            <span style="display:flex;gap:2px;"><?php echo stars($worker['rating']); ?></span>
                            &nbsp;<?php echo $worker['rating']; ?>/5
                        </span>
                    </div>
                    <?php if (!empty($worker['bio'])): ?>
                    <p class="hero-bio"><?php echo nl2br(htmlspecialchars($worker['bio'])); ?></p>
                    <?php else: ?>
                    <p class="hero-bio">Experienced <?php echo htmlspecialchars($worker['role']); ?> with <?php echo $worker['experience']; ?> years of hands-on expertise. Delivers quality work on time with <?php echo $worker['jobs']; ?>+ completed jobs and a strong track record of customer satisfaction.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Skills -->
            <?php
            $skillMap = [
                'Electrician'   => ['Wiring','Circuit Breakers','Panel Upgrades','Lighting Install','Safety Inspection','EV Charger Install'],
                'Plumber'       => ['Pipe Fitting','Leak Repair','Drain Cleaning','Water Heater','Bathroom Plumbing','Emergency Plumbing'],
                'Carpenter'     => ['Furniture Making','Cabinet Install','Door Fitting','Flooring','Custom Woodwork','Repairs'],
                'Painter'       => ['Interior Painting','Exterior Painting','Texture Work','Waterproofing','Wall Prep','Spray Painting'],
                'AC Technician' => ['AC Installation','Gas Refilling','AC Servicing','Duct Cleaning','Thermostat Repair','Cooling Diagnosis'],
                'Mechanic'      => ['Engine Repair','Oil Change','Brake Service','Diagnostics','Transmission','Electrical Systems'],
            ];
            $skills = $skillMap[$worker['role']] ?? ['Professional Service','Quality Work','Timely Delivery'];
            ?>
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon"><?php echo getIcon('briefcase',18); ?></div>
                    <span class="info-card-title">Skills & Expertise</span>
                </div>
                <div class="info-card-body">
                    <div class="skills-wrap">
                        <?php foreach($skills as $skill): ?>
                            <span class="skill-chip"><?php echo $skill; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Reviews -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon"><?php echo getIcon('star',18); ?></div>
                    <span class="info-card-title">Reviews (<?php echo count($reviews); ?>)</span>
                </div>
                <div class="info-card-body" style="padding-top:8px;">
                    <?php if (empty($reviews)): ?>
                        <div class="no-reviews">
                            <?php echo getIcon('star-empty',36); ?>
                            <p>No reviews yet. Be the first to review!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($reviews as $r):
                            $rAvatar = !empty($r['user_photo'])
                                ? (filter_var($r['user_photo'],FILTER_VALIDATE_URL) ? $r['user_photo'] : '../assets/images/users/'.$r['user_photo'])
                                : 'https://ui-avatars.com/api/?name='.urlencode($r['user_name']).'&size=60&background=16a34a&color=fff&rounded=true';
                        ?>
                        <div class="review-item">
                            <div class="review-top">
                                <div class="review-user">
                                    <img src="<?php echo $rAvatar; ?>" class="review-avatar" alt="<?php echo htmlspecialchars($r['user_name']); ?>">
                                    <div>
                                        <div class="review-name"><?php echo htmlspecialchars($r['user_name']); ?></div>
                                        <div class="review-date"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="review-stars"><?php echo stars($r['rating'] ?? 5); ?></div>
                            </div>
                            <?php if (!empty($r['review'])): ?>
                            <p class="review-text"><?php echo htmlspecialchars($r['review']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /left-col -->

        <!-- ── RIGHT COLUMN ── -->
        <div class="right-col">

            <!-- Booking Card -->
            <div class="booking-card">
                <div class="booking-card-top">
                    <div class="booking-price">₹<?php echo $worker['price']; ?></div>
                    <div class="booking-price-unit">per hour · <?php echo htmlspecialchars($worker['role']); ?></div>
                </div>
                <div class="booking-card-body">

                    <!-- Book Button -->
                    <?php if ($alreadyBooked): ?>
                    <button class="btn-book" disabled>
                        <?php echo getIcon('check',16); ?> Already Booked
                    </button>
                    <?php else: ?>
                    <button class="btn-book" id="bookBtn" onclick="openModal()" <?php echo $worker['available'] ? '' : 'disabled'; ?>>
                        <?php echo getIcon('calendar',16); ?>
                        <?php echo $worker['available'] ? 'Book Now' : 'Currently Unavailable'; ?>
                    </button>
                    <?php endif; ?>

                    <!-- Message Button -->
                    <a href="messages.php?with=<?php echo $worker['id']; ?>" class="btn-message">
                        <?php echo getIcon('message',16); ?> Send Message
                    </a>

                    <hr class="card-divider">

                    <div class="bk-row">
                        <span class="label"><?php echo getIcon('clock',13); ?> Availability</span>
                        <span class="value <?php echo $worker['available'] ? 'green' : 'red'; ?>">
                            <?php echo $worker['available'] ? '● Available' : '● Busy'; ?>
                        </span>
                    </div>
                    <div class="bk-row">
                        <span class="label"><?php echo getIcon('location',13); ?> Location</span>
                        <span class="value"><?php echo htmlspecialchars($worker['location']); ?></span>
                    </div>
                    <div class="bk-row">
                        <span class="label"><?php echo getIcon('briefcase',13); ?> Experience</span>
                        <span class="value"><?php echo $worker['experience']; ?> years</span>
                    </div>
                    <div class="bk-row">
                        <span class="label"><?php echo getIcon('workers',13); ?> Jobs Done</span>
                        <span class="value"><?php echo $worker['jobs']; ?>+</span>
                    </div>

                    <hr class="card-divider">

                    <div class="guarantee">
                        <?php echo getIcon('shield',18); ?>
                        <span>HireX Guarantee: Verified professional, satisfaction assured.</span>
                    </div>
                </div>
            </div>

        </div><!-- /right-col -->
    </div><!-- /details-layout -->
</main>

<!-- ── BOOKING MODAL ── -->
<div class="modal-overlay" id="bookingModal">
    <div class="modal">
        <div class="modal-header">
            <h3><?php echo getIcon('calendar',18); ?> &nbsp;Book <?php echo htmlspecialchars($worker['name']); ?></h3>
            <button class="modal-close" onclick="closeModal()"><?php echo getIcon('close',16); ?></button>
        </div>
        <form method="POST" action="ajax/book_worker.php" id="bookingForm">
            <input type="hidden" name="worker_id" value="<?php echo $worker_id; ?>">
            <div class="modal-body">
                <div class="modal-worker-row">
                    <img src="<?php echo $photoPath; ?>"
                         onerror="this.src='<?php echo $fallback; ?>'"
                         alt="" class="modal-worker-photo">
                    <div>
                        <div class="modal-worker-name"><?php echo htmlspecialchars($worker['name']); ?></div>
                        <div class="modal-worker-role"><?php echo htmlspecialchars($worker['role']); ?> · ₹<?php echo $worker['price']; ?>/hr</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date" id="bookDate" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Time</label>
                        <input type="time" name="time" id="bookTime" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Duration (hours)</label>
                    <select name="duration">
                        <option value="1">1 hour — ₹<?php echo $worker['price']; ?></option>
                        <option value="2" selected>2 hours — ₹<?php echo $worker['price']*2; ?></option>
                        <option value="3">3 hours — ₹<?php echo $worker['price']*3; ?></option>
                        <option value="4">4 hours — ₹<?php echo $worker['price']*4; ?></option>
                        <option value="8">Full day (8h) — ₹<?php echo $worker['price']*8; ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" placeholder="Your full address" required value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Notes (optional)</label>
                    <textarea name="notes" placeholder="Describe the work needed…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-confirm">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
    <div class="toast-icon" id="toastIcon"><?php echo getIcon('check',17); ?></div>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Success</div>
        <div class="toast-msg" id="toastMsg">Done!</div>
    </div>
</div>

<script>
/* ── Theme ── */
(function(){
    if(localStorage.getItem('theme')==='dark'){
        document.documentElement.setAttribute('data-theme','dark');
        document.getElementById('themeIcon').innerHTML='<?php echo addslashes(getIcon("sun",16)); ?>';
        document.getElementById('themeText').textContent='Light';
    }
})();
function toggleTheme(){
    const html=document.documentElement, isDark=html.getAttribute('data-theme')==='dark';
    if(isDark){html.removeAttribute('data-theme');document.getElementById('themeIcon').innerHTML='<?php echo addslashes(getIcon("moon",16)); ?>';document.getElementById('themeText').textContent='Dark';localStorage.setItem('theme','light');}
    else{html.setAttribute('data-theme','dark');document.getElementById('themeIcon').innerHTML='<?php echo addslashes(getIcon("sun",16)); ?>';document.getElementById('themeText').textContent='Light';localStorage.setItem('theme','dark');}
}

/* ── Sidebar ── */
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
}

/* ── Modal ── */
function openModal(){
    document.getElementById('bookingModal').classList.add('open');
    document.body.style.overflow='hidden';
    // Set min date & sensible default time
    const now=new Date();
    document.getElementById('bookDate').value=now.toISOString().split('T')[0];
    const h=String(now.getHours()+1).padStart(2,'0');
    document.getElementById('bookTime').value=h+':00';
}
function closeModal(){
    document.getElementById('bookingModal').classList.remove('open');
    document.body.style.overflow='';
}
document.getElementById('bookingModal').addEventListener('click',function(e){
    if(e.target===this) closeModal();
});

/* ── Book form via AJAX ── */
document.getElementById('bookingForm').addEventListener('submit',function(e){
    e.preventDefault();
    const btn=this.querySelector('.btn-confirm');
    btn.disabled=true; btn.textContent='Booking…';
    fetch(this.action,{method:'POST',body:new FormData(this)})
        .then(r=>r.json())
        .then(data=>{
            closeModal();
            if(data.success){
                showToast('Booking Confirmed!','Your booking has been placed successfully.',true);
                setTimeout(()=>location.reload(),2000);
            } else {
                showToast('Booking Failed',data.error||'Please try again.',false);
            }
        })
        .catch(()=>showToast('Error','Something went wrong.',false))
        .finally(()=>{btn.disabled=false;btn.textContent='Confirm Booking';});
});

/* ── Toast ── */
function showToast(title,msg,success=true){
    const t=document.getElementById('toast');
    document.getElementById('toastIcon').innerHTML=success?'<?php echo addslashes(getIcon("check",17)); ?>':'<?php echo addslashes(getIcon("x",17)); ?>';
    document.getElementById('toastTitle').textContent=title;
    document.getElementById('toastMsg').textContent=msg;
    t.className='toast '+(success?'success':'error')+' show';
    setTimeout(()=>t.classList.remove('show'),3500);
}

document.addEventListener('keydown',e=>{
    if(e.key==='Escape') closeModal();
});
</script>
</body>
</html>