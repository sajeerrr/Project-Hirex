<?php
session_start();

include("../database/db.php"); // db connection line

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

// Security: Validate session and sanitize output
$user_id = $_SESSION['user_id'];

$userQuery = "SELECT * FROM users WHERE id='$user_id'";
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();

$userName = htmlspecialchars($user['name']);

$userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $userName), 0, 1));
$userInitial = !empty($userInitial) ? $userInitial : 'A';

// Fetch saved worker IDs for this user
$savedWorkerIds = [];
$savedQuery = "SELECT worker_id FROM saved_workers WHERE user_id='$user_id'";
$savedResult = $conn->query($savedQuery);
if ($savedResult && $savedResult->num_rows > 0) {
    while($row = $savedResult->fetch_assoc()) {
        $savedWorkerIds[] = $row['worker_id'];
    }
}

// Sample Worker Data with Profile Photos
$workers = [];

$sql = "SELECT * FROM workers"; // your table name
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        // image logic
        $photo = $row['photo'];

        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            // If photo is a URL, use it directly
            $photoPath = $photo;
        } else {
            // If photo is a local file
            $photoPath = "../assets/images/workers/" . $photo;
        }

        $workers[] = [
            "id" => $row['id'],
            "name" => $row['name'],
            "role" => $row['role'],
            "rating" => $row['rating'],
            "price" => "₹" . $row['price'] . "/hr",
            "reviews" => $row['reviews'],
            "available" => $row['available'], // 1 or 0
            "photo" => $photoPath,
            "experience" => $row['experience'],
            "jobs" => $row['jobs'],
            "location" => $row['location']
        ];
    }
}

// Job categories with SVG icons
$jobCategories = [
    ["id" => "all", "name" => "All", "icon" => "grid"],
    ["id" => "Electrician", "name" => "Electrician", "icon" => "bolt"],
    ["id" => "Plumber", "name" => "Plumber", "icon" => "drop"],
    ["id" => "Carpenter", "name" => "Carpenter", "icon" => "hammer"],
    ["id" => "Painter", "name" => "Painter", "icon" => "brush"],
    ["id" => "AC Technician", "name" => "AC Tech", "icon" => "snowflake"],
    ["id" => "Mechanic", "name" => "Mechanic", "icon" => "wrench"],
];

// Get filter parameters
$searchQuery = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';
$categoryFilter = isset($_GET['category']) ? htmlspecialchars($_GET['category'], ENT_QUOTES, 'UTF-8') : 'all';
$sortFilter = isset($_GET['sort']) ? htmlspecialchars($_GET['sort'], ENT_QUOTES, 'UTF-8') : 'rating';

// Filter workers
$filteredWorkers = array_filter($workers, function($worker) use ($searchQuery, $categoryFilter) {
    $matchesSearch = empty($searchQuery) || 
                     stripos($worker['name'], $searchQuery) !== false || 
                     stripos($worker['role'], $searchQuery) !== false;
    $matchesCategory = $categoryFilter === 'all' || $worker['role'] === $categoryFilter;
    return $matchesSearch && $matchesCategory;
});

// Sort workers
usort($filteredWorkers, function($a, $b) use ($sortFilter) {
    switch($sortFilter) {
        case 'rating': return floatval($b['rating']) - floatval($a['rating']);
        case 'price_low': return intval(preg_replace('/[^0-9]/', '', $a['price'])) - intval(preg_replace('/[^0-9]/', '', $b['price']));
        case 'price_high': return intval(preg_replace('/[^0-9]/', '', $b['price'])) - intval(preg_replace('/[^0-9]/', '', $a['price']));
        case 'reviews': return $b['reviews'] - $a['reviews'];
        default: return 0;
    }
});

// Photo path
$userPhoto = null;
if (!empty($user['photo'])) {
    if (filter_var($user['photo'], FILTER_VALIDATE_URL)) {
        $userPhoto = $user['photo'];
    } else {
        $userPhoto = '../assets/images/users/' . $user['photo'];
    }
}

// Calculate stats
$totalWorkers = $conn->query("SELECT COUNT(*) as total FROM workers")->fetch_assoc()['total'];
$availableWorkers = $conn->query("SELECT COUNT(*) as total FROM workers WHERE available=1")->fetch_assoc()['total'];
$avgRating = $conn->query("SELECT AVG(rating) as avg FROM workers")->fetch_assoc()['avg'];
$avgRating = $avgRating ? round($avgRating, 1) : 0;

// SVG Icon Function
function getIcon($name, $size = 20, $class = '') {
    $icons = [
        'grid' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'bolt' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
        'drop' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
        'hammer' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12l-8.5 8.5c-.83.83-2.17.83-3 0 0 0 0 0 0a2.12 2.12 0 0 1 0-3L12 9"/><path d="M17.64 15L22 10.64"/><path d="M20.91 11.26a2 2 0 0 0 2.83-.25l.5-.5a2 2 0 0 0-.25-2.83L22 5.64l-6.36-6.36a2 2 0 0 0-2.83.25l-.5.5a2 2 0 0 0 .25 2.83L14.36 5"/></svg>',
        'brush' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.37 2.63L14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3z"/><path d="M9 8c-2 3-4 3.5-7 4l8 10c2-1 6-5 6-7"/><path d="M14.5 17.5L4.5 15"/></svg>',
        'snowflake' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/><path d="M20 16l-4-4 4-4"/><path d="M4 8l4 4-4 4"/><path d="M16 4l-4 4-4-4"/><path d="M8 20l4-4 4 4"/></svg>',
        'wrench' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'dashboard' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'user' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'message' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'calendar' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'bookmark' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>',
        'card' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'settings' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'help' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'phone' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'logout' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'search' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'bell' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'moon' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'sun' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'star' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'clock' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'briefcase' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'check' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'x' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'menu' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'workers' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'arrow-right' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
        'location' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX - Find and hire skilled workers easily">
    <title>Dashboard — HireX</title>
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
            
            --teal-50: #f0fdfa;
            --teal-100: #ccfbf1;
            --teal-500: #14b8a6;
            --teal-600: #0d9488;
            
            /* Theme Variables */
            --bg: #f8faf9;
            --bg-secondary: #ffffff;
            --sidebar-width: 250px;
            --primary: var(--mint-600);
            --primary-hover: #15803d;
            --primary-light: var(--mint-100);
            --secondary: var(--teal-500);
            --secondary-hover: #0f766e;
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

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            display: flex;
            color: var(--text-primary);
            transition: var(--transition);
            overflow-x: hidden;
            line-height: 1.6;
            background-image: 
                radial-gradient(ellipse at top right, rgba(34, 197, 94, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(20, 184, 166, 0.08) 0%, transparent 50%);
        }

        svg { display: block; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-secondary);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            position: fixed;
            border-right: 1px solid var(--border);
            z-index: 1000;
            transition: var(--transition);
        }

        .logo {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 32px;
            padding-left: 14px;
            letter-spacing: -0.5px;
            color: var(--text-primary);
        }

        .logo .x { 
            color: var(--primary);
        }

        .nav-group { margin-bottom: 24px; }

        .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-gray);
            margin-bottom: 12px;
            padding-left: 14px;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 11px 14px;
            text-decoration: none;
            color: var(--text-secondary);
            border-radius: 10px;
            margin-bottom: 4px;
            transition: var(--transition);
            font-weight: 500;
            cursor: pointer;
            font-size: 13px;
            gap: 12px;
        }

        .nav-item:hover {
            background: var(--primary-light);
            color: var(--primary);
            transform: translateX(4px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            box-shadow: 0 4px 15px var(--shadow-lg);
        }

        .nav-item svg { width: 18px; height: 18px; }

        .badge {
            background: var(--danger);
            color: white;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 6px;
            margin-left: auto;
            font-weight: 700;
        }

        /* Sign Out - Bottom Left */
        .signout-container {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border);
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
            justify-content: flex-start;
        }

        [data-theme="dark"] .signout-btn { background: rgba(239, 68, 68, 0.15); }

        .signout-btn:hover {
            background: var(--danger);
            color: white;
            transform: translateX(4px);
        }

        .signout-btn svg { width: 18px; height: 18px; }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 0 32px 32px 32px;
            transition: var(--transition);
        }

        .mobile-toggle {
            display: none;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            cursor: pointer;
            color: var(--text-primary);
            padding: 10px 12px;
            border-radius: 10px;
            transition: var(--transition);
        }

        .mobile-toggle:hover { background: var(--primary-light); border-color: var(--primary); }

        /* --- STATS BAR --- */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 26px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--shadow);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon.green { 
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); 
            color: var(--mint-600);
        }
        .stat-icon.teal { 
            background: linear-gradient(135deg, var(--teal-100), #99f6e4); 
            color: var(--teal-600);
        }
        .stat-icon.yellow { 
            background: linear-gradient(135deg, #fef3c7, #fde68a); 
            color: #b45309;
        }

        .stat-info h4 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .stat-info p {
            font-size: 11px;
            color: var(--text-gray);
            margin-top: 2px;
            font-weight: 500;
        }

        /* --- HEADER --- */
        header {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 13px;
            flex: 1;
            min-width: 270px;
        }

        .search-bar {
            background: var(--bg-secondary);
            padding: 11px 15px;
            border-radius: 11px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            flex: 1;
            max-width: 480px;
            transition: var(--transition);
        }

        .search-bar:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        .search-bar input {
            border: none;
            outline: none;
            width: 100%;
            margin-left: 11px;
            background: transparent;
            color: var(--text-primary);
            font-size: 13px;
            font-weight: 500;
        }

        .search-bar input::placeholder { color: var(--text-gray); }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .icon-btn {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 11px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .icon-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .icon-btn .notification-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-secondary);
            padding: 5px 5px 5px 5px;
            border-radius: 30px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            padding-right: 15px;
        }

        .user-pill:hover { border-color: var(--primary); box-shadow: 0 4px 14px var(--shadow); }

         .avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--mint-500), var(--teal-500)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: white; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
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
        }

        .theme-toggle:hover { border-color: var(--primary); background: var(--primary-light); }

        /* --- PAGE TITLE --- */
        .page-title { 
            margin: 8px 0 20px 0;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .page-title h2 { 
            margin: 0; 
            font-size: 23px; 
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
        }
        
        .page-title p { 
            color: var(--text-gray); 
            margin-top: 6px; 
            font-size: 13px;
        }

        /* --- FILTER BAR (One Line) --- */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 22px 0 24px 0;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-right: 6px;
        }

        .job-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 26px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            white-space: nowrap;
        }

        .job-chip:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .job-chip.active {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border-color: var(--mint-500);
            box-shadow: 0 4px 14px var(--shadow-lg);
        }

        .job-chip svg { width: 14px; height: 14px; }

        /* Sort Select - Right Side of Job Filters */
        .sort-select {
            padding: 9px 15px;
            border-radius: 26px;
            border: 1px solid var(--border);
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            min-width: 165px;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            margin-left: auto;
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }

        .results-count {
            color: var(--text-gray);
            font-size: 12px;
            font-weight: 500;
            margin-left: 12px;
        }

        /* --- GRID - 3 Cards Per Row --- */
        .worker-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        /* --- CARD --- */
        .card {
            background: var(--bg-secondary);
            border-radius: 15px;
            border: 1px solid var(--border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 10px var(--shadow);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--mint-500), var(--teal-500));
            transform: scaleX(0);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px var(--shadow-lg);
            border-color: var(--primary);
        }

        .card:hover::before { transform: scaleX(1); }

        /* Card Photo - Round */
        .card-photo-wrapper {
            position: relative;
            padding: 24px 20px 0 20px;
            display: flex;
            justify-content: center;
        }

        .card-photo {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--mint-100);
            transition: var(--transition);
        }

        .card:hover .card-photo {
            border-color: var(--primary);
            transform: scale(1.06);
        }

        .photo-overlay {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            z-index: 10;
        }

        .availability-badge {
            font-size: 10px;
            padding: 5px 11px;
            border-radius: 18px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .available {
            background: rgba(34, 197, 94, 0.95);
            color: white;
        }

        .busy {
            background: rgba(239, 68, 68, 0.95);
            color: white;
        }

        .availability-badge svg { width: 10px; height: 10px; }

        .bookmark-btn {
            background: rgba(255,255,255,0.95);
            border: 1px solid var(--border);
            width: 34px;
            height: 34px;
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.12);
        }

        .bookmark-btn:hover {
            transform: scale(1.1);
            border-color: var(--primary);
        }

        .bookmark-btn.active {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
        }

        .bookmark-btn svg { width: 16px; height: 16px; }

        /* Card Body */
        .card-body {
            padding: 16px;
            text-align: center;
        }

        .card h4 {
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .worker-role {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--mint-50);
            padding: 4px 10px;
            border-radius: 13px;
            margin-bottom: 10px;
        }

        .worker-role svg { width: 12px; height: 12px; }

        .location-info {
            font-size: 11px;
            color: var(--text-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-bottom: 12px;
        }

        .location-info svg { width: 12px; height: 12px; }

        .card-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            padding: 5px 10px;
            border-radius: 14px;
            font-weight: 700;
            color: #92400e;
            font-size: 11px;
        }

        .rating-badge svg { width: 12px; height: 12px; fill: currentColor; }

        .meta-item {
            font-size: 11px;
            color: var(--text-gray);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .meta-item svg { width: 11px; height: 11px; }

        /* Card Footer */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px dashed var(--border);
        }

        .price {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .price span {
            font-size: 11px;
            color: var(--text-gray);
            font-weight: 500;
        }

        .btn-hire {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-hire:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: scale(1.05);
            box-shadow: 0 5px 18px var(--shadow-lg);
        }

        .btn-hire svg { width: 13px; height: 13px; }

        /* --- EMPTY STATE --- */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--text-gray);
            background: var(--bg-secondary);
            border-radius: 15px;
            border: 1px dashed var(--border);
        }

        .empty-state-icon { margin-bottom: 18px; opacity: 0.6; }
        .empty-state-icon svg { width: 55px; height: 55px; margin: 0 auto; }
        .empty-state h3 { margin: 0 0 8px 0; color: var(--text-primary); font-size: 18px; }
        .empty-state p { font-size: 13px; }

        /* --- TOAST --- */
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
            transform: translateX(150%);
            transition: var(--transition);
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
        }

        .toast.show { transform: translateX(0); }
        .toast.error { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }

        .toast-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast.success .toast-icon { background: rgba(34, 197, 94, 0.15); color: var(--success); }
        .toast.error .toast-icon { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
        .toast-icon svg { width: 17px; height: 17px; }

        .toast-content { flex: 1; }
        .toast-title { font-weight: 600; color: var(--text-primary); font-size: 13px; }
        .toast-message { font-size: 12px; color: var(--text-gray); margin-top: 2px; }

        /* --- OVERLAY --- */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 20, 17, 0.65);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            transition: var(--transition);
        }

        .overlay.active { display: block; opacity: 1; }

        /* --- RESPONSIVE --- */
        @media (max-width: 1200px) {
            .worker-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 1024px) {
            .main-content { padding: 0 26px 26px 26px; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 18px 18px 18px; }
            .mobile-toggle { display: flex; }
            .header-left { width: 100%; justify-content: space-between; }
            .search-bar { order: 3; width: 100%; max-width: none; margin-top: 13px; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .job-chip { width: 100%; justify-content: center; }
            .sort-select { margin-left: 0; width: 100%; }
            .results-count { margin-left: 0; text-align: center; margin-top: 8px; }
            .worker-grid { grid-template-columns: 1fr; }
            .stats-bar { grid-template-columns: 1fr; }
            .toast { left: 18px; right: 18px; bottom: 18px; min-width: auto; }
            .page-title h2 { font-size: 20px; }
        }

        @media (max-width: 480px) {
            .user-name { display: none; }
            .theme-toggle span:last-child { display: none; }
            .job-chip { padding: 7px 13px; font-size: 11px; }
            .stats-bar { gap: 12px; }
            .stat-card { padding: 15px; }
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--mint-300); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--mint-500); }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="logo">Hire<span class="x">X</span></div>

    <nav>
        <div class="nav-group">
            <div class="nav-label">Main Menu</div>
            <a href="#" class="nav-item active">
                <?php echo getIcon('dashboard', 18); ?> Dashboard
            </a>
            <a href="profile.php" class="nav-item">
                <?php echo getIcon('user', 18); ?> My Profile
            </a>
            <a href="messages.php" class="nav-item">
                <?php echo getIcon('message', 18); ?> Messages
            </a>
            <a href="booking.php" class="nav-item">
                <?php echo getIcon('calendar', 18); ?> My Bookings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Preferences</div>
            <a href="saved-worker.php" class="nav-item">
                <?php echo getIcon('bookmark', 18); ?> Saved Workers
            </a>
            <a href="payment.php" class="nav-item">
                <?php echo getIcon('card', 18); ?> Payments
            </a>
            <a href="settings.php" class="nav-item">
                <?php echo getIcon('settings', 18); ?> Settings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="help.php" class="nav-item">
                <?php echo getIcon('help', 18); ?> Help Center
            </a>
            <a href="contact.php" class="nav-item">
                <?php echo getIcon('phone', 18); ?> Contact Us
            </a>
        </div>
    </nav>

    <!-- Sign Out - Bottom Left -->
    <div class="signout-container">
        <a href="logout.php" class="signout-btn">
            <?php echo getIcon('logout', 18); ?> Sign Out
        </a>
    </div>
</aside>

<main class="main-content" id="mainContent">
    <header>
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle Menu">
                <?php echo getIcon('menu', 20); ?>
            </button>
            <form class="search-bar" method="GET" action="">
                <?php echo getIcon('search', 18); ?>
                <input type="text" name="search" placeholder="Search workers..." value="<?php echo $searchQuery; ?>" aria-label="Search workers">
                <button type="submit" style="background:none; border:none; cursor:pointer; color: var(--primary); font-weight: 600; font-size: 13px;">Search</button>
            </form>
        </div>

        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle Dark Mode">
                <span id="themeIcon"><?php echo getIcon('moon', 16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            
            <button class="icon-btn" aria-label="Notifications" onclick="showToast('Notifications', 'You have 3 new messages', true)">
                <?php echo getIcon('bell', 18); ?>
                <span class="notification-dot"></span>
            </button>
            
            <div class="user-pill" aria-label="User Profile">
                <div class="avatar">
                    <?php if ($userPhoto): ?>
                        <img src="<?php echo $userPhoto; ?>" alt="<?php echo $userName; ?>">
                    <?php else: ?>
                        <?php echo $userInitial; ?>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?php echo $userName; ?></span>
            </div>
        </div>
    </header>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon green"><?php echo getIcon('workers', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo $totalWorkers; ?></h4>
                <p>Total Workers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><?php echo getIcon('check', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo $availableWorkers; ?></h4>
                <p>Available Now</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><?php echo getIcon('star', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo $avgRating; ?></h4>
                <p>Avg Rating</p>
            </div>
        </div>
    </div>

    <div class="page-title">
        <h2>Recommended Professionals</h2>
        <p>Skilled workers based on your preferences and location.</p>
    </div>

    <!-- Filter Bar - Job Filters + Sort on One Line -->
    <form method="GET" action="">
        <input type="hidden" name="search" value="<?php echo $searchQuery; ?>">
        <input type="hidden" name="category" value="<?php echo $categoryFilter; ?>">
        
        <div class="filter-bar">
            <span class="filter-label">Filter:</span>
            <?php foreach($jobCategories as $job): ?>
                <a href="?category=<?php echo urlencode($job['id']); ?>&search=<?php echo urlencode($searchQuery); ?>" 
                   class="job-chip <?php echo $categoryFilter === $job['id'] ? 'active' : ''; ?>">
                    <?php echo getIcon($job['icon'], 14); ?>
                    <span><?php echo $job['name']; ?></span>
                </a>
            <?php endforeach; ?>
            
            <select class="sort-select" name="sort" onchange="this.form.submit()" aria-label="Sort by">
                <option value="rating" <?php echo $sortFilter === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                <option value="price_low" <?php echo $sortFilter === 'price_low' ? 'selected' : ''; ?>>Price: Low-High</option>
                <option value="price_high" <?php echo $sortFilter === 'price_high' ? 'selected' : ''; ?>>Price: High-Low</option>
                <option value="reviews" <?php echo $sortFilter === 'reviews' ? 'selected' : ''; ?>>Most Reviews</option>
            </select>
            
            <span class="results-count">
                <?php echo count($filteredWorkers); ?> found
            </span>
        </div>
    </form>

    <div class="worker-grid">
        <?php if (empty($filteredWorkers)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><?php echo getIcon('search', 55); ?></div>
                <h3>No professionals found</h3>
                <p>Try adjusting your search or filter criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach($filteredWorkers as $worker): ?>
                <div class="card" data-worker-id="<?php echo md5($worker['name']); ?>">
                    <div class="card-photo-wrapper">
                        <img src="<?php echo $worker['photo']; ?>" alt="<?php echo htmlspecialchars($worker['name']); ?>" class="card-photo" onerror="this.src='https://ui-avatars.com/api/<?php echo urlencode($worker['name']); ?>/110/16a34a/ffffff?rounded=true'">
                        
                        <div class="photo-overlay">
                            <span class="availability-badge <?php echo $worker['available'] ? 'available' : 'busy'; ?>">
                                <?php echo $worker['available'] ? getIcon('check', 10) : getIcon('x', 10); ?>
                                <?php echo $worker['available'] ? 'Available' : 'Busy'; ?>
                            </span>
                            <?php $isSaved = in_array($worker['id'], $savedWorkerIds); ?>
                            <button class="bookmark-btn <?php echo $isSaved ? 'active' : ''; ?>" onclick="toggleBookmark(this, <?php echo $worker['id']; ?>, '<?php echo htmlspecialchars(addslashes($worker['name'])); ?>')" aria-label="Save worker">
                                <?php echo getIcon('bookmark', 16); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h4><?php echo htmlspecialchars($worker['name']); ?></h4>
                        <span class="worker-role">
                            <?php 
                            $roleIcon = '';
                            switch($worker['role']) {
                                case 'Electrician': $roleIcon = 'bolt'; break;
                                case 'Plumber': $roleIcon = 'drop'; break;
                                case 'Carpenter': $roleIcon = 'hammer'; break;
                                case 'Painter': $roleIcon = 'brush'; break;
                                case 'AC Technician': $roleIcon = 'snowflake'; break;
                                case 'Mechanic': $roleIcon = 'wrench'; break;
                            }
                            echo getIcon($roleIcon, 12);
                            ?>
                            <?php echo htmlspecialchars($worker['role']); ?>
                        </span>
                        
                        <div class="location-info">
                            <?php echo getIcon('location', 12); ?> <?php echo htmlspecialchars($worker['location']); ?>
                        </div>
                        
                        <div class="card-meta">
                            <span class="rating-badge">
                                <?php echo getIcon('star', 12); ?> <?php echo $worker['rating']; ?>
                            </span>
                            <span class="meta-item">
                                <?php echo getIcon('briefcase', 11); ?> <?php echo $worker['jobs']; ?> jobs
                            </span>
                        </div>
                        
                        <div class="card-footer">
                            <div class="price"><?php echo htmlspecialchars($worker['price']); ?></div>
                            <button class="btn-hire"
                                onclick="window.location.href='worker_details.php?id=<?php echo $worker['id']; ?>'">
                                View <?php echo getIcon('arrow-right', 13); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<div class="toast" id="toast">
    <div class="toast-icon" id="toastIconBox"><?php echo getIcon('check', 17); ?></div>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Success</div>
        <div class="toast-message" id="toastMessage">Action completed!</div>
    </div>
</div>

<script>

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    function toggleTheme() {
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        
        if (html.getAttribute('data-theme') === 'dark') {
            html.removeAttribute('data-theme');
            themeIcon.innerHTML = '<?php echo getIcon("moon", 16); ?>';
            themeText.textContent = 'Dark';
            localStorage.setItem('theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            themeIcon.innerHTML = '<?php echo getIcon("sun", 16); ?>';
            themeText.textContent = 'Light';
            localStorage.setItem('theme', 'dark');
        }
    }

    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('themeIcon').innerHTML = '<?php echo getIcon("sun", 16); ?>';
            document.getElementById('themeText').textContent = 'Light';
        }
    })();

    function toggleBookmark(btn, workerId, workerName) {
        // Optimistic UI update
        btn.classList.toggle('active');
        const isBookmarked = btn.classList.contains('active');
        
        // Send request
        const formData = new FormData();
        formData.append('worker_id', workerId);
        
        fetch('ajax/save_worker.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.action === 'saved' ? 'Saved' : 'Removed', `${workerName} ${data.action === 'saved' ? 'added to' : 'removed from'} bookmarks`, true);
            } else {
                // Revert UI on failure
                btn.classList.toggle('active');
                showToast('Error', data.error || 'Failed to save worker', false);
            }
        })
        .catch(err => {
            // Revert UI on failure
            btn.classList.toggle('active');
            showToast('Error', 'Network error occurred', false);
        });
    }

    function hireWorker(workerName, workerRole) {
        showToast('Opening Profile', `Viewing ${workerName}'s profile...`, true);
    }

    function showToast(title, message, success = true) {
        const toast = document.getElementById('toast');
        const toastIconBox = document.getElementById('toastIconBox');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        
        toastIconBox.innerHTML = success ? '<?php echo getIcon("check", 17); ?>' : '<?php echo getIcon("x", 17); ?>';
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        toast.className = 'toast' + (success ? ' success' : ' warning') + ' show';
        
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.mobile-toggle');
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('sidebar').classList.contains('active')) toggleSidebar();
        if (e.key === '/' && e.target.tagName !== 'INPUT') { e.preventDefault(); document.querySelector('.search-bar input').focus(); }
    });
</script>

</body>
</html>
