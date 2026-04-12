<?php
session_start();
include("../database/db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Load current user
$userResult = $conn->query("SELECT * FROM users WHERE id='$user_id'");
$user = $userResult->fetch_assoc();
$userName    = htmlspecialchars($user['name'] ?? '');
$userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $userName), 0, 1)) ?: 'A';

// Active conversation partner (from URL)
$active_id = isset($_GET['with']) ? intval($_GET['with']) : 0;

// Load all conversation partners (people the user has messaged or received messages from)
$convQuery = "
    SELECT DISTINCT
        u.id, u.name, u.photo,
        (SELECT message FROM messages 
         WHERE (sender_id='$user_id' AND receiver_id=u.id) 
            OR (sender_id=u.id AND receiver_id='$user_id')
         ORDER BY created_at DESC LIMIT 1) AS last_msg,
        (SELECT created_at FROM messages 
         WHERE (sender_id='$user_id' AND receiver_id=u.id) 
            OR (sender_id=u.id AND receiver_id='$user_id')
         ORDER BY created_at DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id=u.id AND receiver_id='$user_id' AND is_read=0) AS unread
    FROM users u
    WHERE u.id IN (
        SELECT CASE WHEN sender_id='$user_id' THEN receiver_id ELSE sender_id END
        FROM messages
        WHERE sender_id='$user_id' OR receiver_id='$user_id'
    )
    ORDER BY last_time DESC
";
$convResult = $conn->query($convQuery);
$conversations = [];
if ($convResult) {
    while ($row = $convResult->fetch_assoc()) $conversations[] = $row;
}

// If no active_id yet, use first conversation
if (!$active_id && !empty($conversations)) {
    $active_id = $conversations[0]['id'];
}

// Load active partner info
$activePartner = null;
if ($active_id) {
    $res = $conn->query("SELECT * FROM users WHERE id='$active_id'");
    if ($res) $activePartner = $res->fetch_assoc();
    // Mark messages as read
    $conn->query("UPDATE messages SET is_read=1 WHERE sender_id='$active_id' AND receiver_id='$user_id'");
}

// SVG Icon helper
function getIcon($name, $size = 20, $class = '') {
    $icons = [
        'dashboard' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'user'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'message'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'calendar'  => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'bookmark'  => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>',
        'card'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'settings'  => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'help'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'phone'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'logout'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'menu'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'moon'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'sun'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'bell'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'send'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'search'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'check'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'check-dbl' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 9 16 1 9"/><polyline points="22 6 14 21 10 16"/></svg>',
        'smile'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
        'paperclip' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>',
        'more'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>',
        'workers'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'back'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
        'x'         => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'bubble'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="9" y1="14" x2="13" y2="14"/></svg>',
    ];
    return $icons[$name] ?? '';
}

// Helper: avatar URL or initials fallback
function avatarUrl($photo, $name) {
    if (!empty($photo)) {
        if (filter_var($photo, FILTER_VALIDATE_URL)) return htmlspecialchars($photo);
        return '../assets/images/users/' . htmlspecialchars($photo);
    }
    $init = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 1)) ?: 'A';
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=80&background=16a34a&color=fff&rounded=true';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — HireX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --mint-50: #f0fdf7; --mint-100: #dcfce7; --mint-200: #bbf7d0;
            --mint-300: #86efac; --mint-400: #4ade80; --mint-500: #22c55e; --mint-600: #16a34a;
            --teal-100: #ccfbf1; --teal-500: #14b8a6; --teal-600: #0d9488;
            --bg: #f8faf9; --bg-secondary: #ffffff; --sidebar-width: 250px;
            --primary: var(--mint-600); --primary-hover: #15803d; --primary-light: var(--mint-100);
            --text-primary: #1a2f24; --text-secondary: #4a5d55; --text-gray: #789085;
            --border: #d1e8dd; --shadow: rgba(22,163,74,0.08); --shadow-lg: rgba(22,163,74,0.15);
            --danger: #ef4444; --success: var(--mint-500); --warning: #f59e0b;
            --transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            --chat-height: calc(100vh - 70px);
        }
        [data-theme="dark"] {
            --bg: #0d1411; --bg-secondary: #141c18; --text-primary: #e0f2e8;
            --text-secondary: #9dbfa8; --text-gray: #789085; --border: #2d3d33;
            --shadow: rgba(0,0,0,0.4); --shadow-lg: rgba(0,0,0,0.6);
            --primary: var(--mint-500); --primary-hover: var(--mint-400);
            --primary-light: rgba(34,197,94,0.15);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif; background: var(--bg); display: flex;
            color: var(--text-primary); transition: var(--transition); overflow: hidden; height: 100vh;
            background-image: radial-gradient(ellipse at top right, rgba(34,197,94,0.05) 0%, transparent 50%);
        }
        svg { display: block; }

        /* ── SIDEBAR ── */
        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--bg-secondary); padding: 24px 16px; display: flex; flex-direction: column; position: fixed; border-right: 1px solid var(--border); z-index: 1000; transition: var(--transition); }
        .logo { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; font-weight: 800; margin-bottom: 32px; padding-left: 14px; letter-spacing: -0.5px; color: var(--text-primary); }
        .logo .x { color: var(--primary); }
        .nav-group { margin-bottom: 24px; }
        .nav-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-gray); margin-bottom: 12px; padding-left: 14px; font-weight: 700; }
        .nav-item { display: flex; align-items: center; padding: 11px 14px; text-decoration: none; color: var(--text-secondary); border-radius: 10px; margin-bottom: 4px; transition: var(--transition); font-weight: 500; font-size: 13px; gap: 12px; }
        .nav-item:hover { background: var(--primary-light); color: var(--primary); transform: translateX(4px); }
        .nav-item.active { background: linear-gradient(135deg, var(--mint-500), var(--mint-600)); color: white; box-shadow: 0 4px 15px var(--shadow-lg); }
        .nav-item svg { width: 18px; height: 18px; }
        .badge { background: var(--danger); color: white; font-size: 9px; padding: 2px 6px; border-radius: 6px; margin-left: auto; font-weight: 700; }
        .signout-container { margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border); }
        .signout-btn { display: flex; align-items: center; gap: 12px; padding: 11px 14px; width: 100%; text-decoration: none; color: var(--danger); background: #fef2f2; border-radius: 10px; font-weight: 600; font-size: 13px; transition: var(--transition); }
        [data-theme="dark"] .signout-btn { background: rgba(239,68,68,0.15); }
        .signout-btn:hover { background: var(--danger); color: white; transform: translateX(4px); }

        /* ── MAIN SHELL ── */
        .main-content { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; transition: var(--transition); }

        /* ── TOP HEADER ── */
        .top-header { height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; border-bottom: 1px solid var(--border); background: var(--bg-secondary); flex-shrink: 0; gap: 14px; }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .mobile-toggle { display: none; background: var(--bg-secondary); border: 1px solid var(--border); cursor: pointer; color: var(--text-primary); padding: 9px 11px; border-radius: 10px; transition: var(--transition); }
        .page-heading { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 17px; font-weight: 700; }
        .header-actions { display: flex; align-items: center; gap: 10px; }
        .icon-btn { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); position: relative; }
        .icon-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .notification-dot { position: absolute; top: 7px; right: 7px; width: 7px; height: 7px; background: var(--danger); border-radius: 50%; border: 2px solid var(--bg-secondary); }
        .user-pill { display: flex; align-items: center; gap: 9px; background: var(--bg-secondary); padding: 4px 14px 4px 4px; border-radius: 30px; border: 1px solid var(--border); cursor: pointer; transition: var(--transition); }
        .user-pill:hover { border-color: var(--primary); }
        .avatar { width: 34px; height: 34px; background: linear-gradient(135deg, var(--mint-500), var(--teal-500)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; color: white; overflow: hidden; flex-shrink: 0; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name { font-size: 13px; font-weight: 600; }
        .theme-toggle { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 10px; padding: 9px 12px; cursor: pointer; display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--text-secondary); transition: var(--transition); font-weight: 500; }
        .theme-toggle:hover { border-color: var(--primary); background: var(--primary-light); }

        /* ── CHAT LAYOUT ── */
        .chat-layout { display: flex; flex: 1; overflow: hidden; }

        /* ── CONVERSATION LIST ── */
        .conv-panel {
            width: 320px;
            flex-shrink: 0;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            background: var(--bg-secondary);
            overflow: hidden;
        }
        .conv-header { padding: 16px 18px 12px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .conv-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--text-primary); }
        .conv-search { display: flex; align-items: center; gap: 9px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 9px 13px; transition: var(--transition); }
        .conv-search:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(22,163,74,0.1); }
        .conv-search input { border: none; outline: none; background: transparent; color: var(--text-primary); font-size: 12px; width: 100%; font-family: 'Inter', sans-serif; }
        .conv-search input::placeholder { color: var(--text-gray); }
        .conv-search svg { width: 14px; height: 14px; color: var(--text-gray); flex-shrink: 0; }

        .conv-list { flex: 1; overflow-y: auto; padding: 8px; }
        .conv-list::-webkit-scrollbar { width: 4px; }
        .conv-list::-webkit-scrollbar-thumb { background: var(--mint-300); border-radius: 2px; }

        .conv-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 11px;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            position: relative;
        }
        .conv-item:hover { background: var(--primary-light); }
        .conv-item.active { background: linear-gradient(135deg, rgba(34,197,94,0.12), rgba(20,184,166,0.08)); border: 1px solid rgba(34,197,94,0.2); }
        .conv-item.active .conv-name { color: var(--primary); }

        .conv-avatar { position: relative; flex-shrink: 0; }
        .conv-avatar img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); }
        .conv-item.active .conv-avatar img { border-color: var(--primary); }
        .online-dot { position: absolute; bottom: 1px; right: 1px; width: 11px; height: 11px; background: var(--success); border-radius: 50%; border: 2px solid var(--bg-secondary); }

        .conv-info { flex: 1; min-width: 0; }
        .conv-name { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-preview { font-size: 11px; color: var(--text-gray); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }

        .conv-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; }
        .conv-time { font-size: 10px; color: var(--text-gray); }
        .unread-badge { background: var(--primary); color: white; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 8px; min-width: 18px; text-align: center; }

        .no-conversations { text-align: center; padding: 40px 20px; color: var(--text-gray); }
        .no-conversations svg { margin: 0 auto 12px; opacity: 0.4; }
        .no-conversations p { font-size: 13px; }

        /* ── CHAT WINDOW ── */
        .chat-window { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--bg); }

        /* Chat Header */
        .chat-header { height: 64px; display: flex; align-items: center; justify-content: space-between; padding: 0 22px; border-bottom: 1px solid var(--border); background: var(--bg-secondary); flex-shrink: 0; }
        .chat-partner { display: flex; align-items: center; gap: 12px; }
        .chat-partner-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .chat-partner-name { font-size: 14px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-primary); }
        .chat-partner-status { font-size: 11px; color: var(--success); font-weight: 500; margin-top: 1px; }
        .chat-actions { display: flex; gap: 8px; }
        .chat-action-btn { background: var(--bg); border: 1px solid var(--border); border-radius: 9px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); color: var(--text-secondary); }
        .chat-action-btn:hover { border-color: var(--primary); color: var(--primary); }
        .chat-action-btn svg { width: 15px; height: 15px; }
        .back-btn { display: none; background: var(--bg); border: 1px solid var(--border); border-radius: 9px; width: 36px; height: 36px; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); color: var(--text-secondary); margin-right: 4px; }
        .back-btn:hover { border-color: var(--primary); color: var(--primary); }

        /* Messages Area */
        .messages-area { flex: 1; overflow-y: auto; padding: 22px 24px; display: flex; flex-direction: column; gap: 10px; scroll-behavior: smooth; }
        .messages-area::-webkit-scrollbar { width: 5px; }
        .messages-area::-webkit-scrollbar-thumb { background: var(--mint-300); border-radius: 3px; }

        /* Date divider */
        .date-divider { display: flex; align-items: center; gap: 12px; margin: 8px 0; }
        .date-divider::before, .date-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .date-divider span { font-size: 11px; color: var(--text-gray); font-weight: 600; white-space: nowrap; padding: 3px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 20px; }

        /* Bubble */
        .msg-row { display: flex; gap: 10px; max-width: 72%; animation: msgIn 0.25s ease; }
        @keyframes msgIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .msg-row.sent { align-self: flex-end; flex-direction: row-reverse; }
        .msg-row.received { align-self: flex-start; }

        .msg-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; align-self: flex-end; border: 2px solid var(--border); }

        .msg-bubble {
            padding: 10px 14px;
            border-radius: 16px;
            font-size: 13px;
            line-height: 1.55;
            word-break: break-word;
            position: relative;
        }
        .msg-row.received .msg-bubble { background: var(--bg-secondary); border: 1px solid var(--border); border-bottom-left-radius: 4px; color: var(--text-primary); }
        .msg-row.sent .msg-bubble { background: linear-gradient(135deg, var(--mint-500), var(--mint-600)); color: white; border-bottom-right-radius: 4px; }

        .msg-meta { display: flex; align-items: center; gap: 5px; margin-top: 4px; }
        .msg-time { font-size: 10px; color: var(--text-gray); }
        .msg-row.sent .msg-time { color: rgba(255,255,255,0.7); }
        .msg-status svg { width: 12px; height: 12px; color: rgba(255,255,255,0.7); }

        /* Typing indicator */
        .typing-indicator { display: flex; align-items: center; gap: 10px; padding: 4px 0; }
        .typing-dots { display: flex; gap: 4px; padding: 10px 14px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 16px; border-bottom-left-radius: 4px; }
        .typing-dots span { width: 7px; height: 7px; background: var(--text-gray); border-radius: 50%; animation: bounce 1.2s infinite; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

        /* Empty chat */
        .empty-chat { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-gray); gap: 14px; }
        .empty-chat svg { opacity: 0.25; }
        .empty-chat h3 { font-size: 17px; font-weight: 700; color: var(--text-secondary); font-family: 'Plus Jakarta Sans', sans-serif; }
        .empty-chat p { font-size: 13px; text-align: center; max-width: 280px; }

        /* No selection state */
        .no-chat-selected { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; color: var(--text-gray); }
        .no-chat-selected .big-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary); }
        .no-chat-selected .big-icon svg { width: 36px; height: 36px; }
        .no-chat-selected h3 { font-size: 18px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-secondary); }
        .no-chat-selected p { font-size: 13px; text-align: center; max-width: 260px; }

        /* Input Bar */
        .chat-input-bar { padding: 14px 20px; border-top: 1px solid var(--border); background: var(--bg-secondary); flex-shrink: 0; }
        .input-row { display: flex; align-items: flex-end; gap: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 16px; padding: 10px 12px; transition: var(--transition); }
        .input-row:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(22,163,74,0.12); }
        .input-side-btn { background: none; border: none; cursor: pointer; color: var(--text-gray); padding: 4px; border-radius: 7px; transition: var(--transition); display: flex; align-items: center; }
        .input-side-btn:hover { color: var(--primary); background: var(--primary-light); }
        .input-side-btn svg { width: 18px; height: 18px; }
        #msgInput {
            flex: 1; border: none; outline: none; background: transparent;
            color: var(--text-primary); font-size: 13px; font-family: 'Inter', sans-serif;
            resize: none; line-height: 1.5; max-height: 120px; overflow-y: auto;
            scrollbar-width: none;
        }
        #msgInput::placeholder { color: var(--text-gray); }
        #msgInput::-webkit-scrollbar { display: none; }
        .send-btn {
            width: 38px; height: 38px; border-radius: 11px; border: none;
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: var(--transition); flex-shrink: 0;
        }
        .send-btn:hover { transform: scale(1.08); box-shadow: 0 4px 14px var(--shadow-lg); }
        .send-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .send-btn svg { width: 16px; height: 16px; }
        .char-hint { font-size: 10px; color: var(--text-gray); text-align: right; margin-top: 6px; }

        /* ── OVERLAY ── */
        .overlay { display: none; position: fixed; inset: 0; background: rgba(13,20,17,0.65); backdrop-filter: blur(4px); z-index: 999; opacity: 0; transition: var(--transition); }
        .overlay.active { display: block; opacity: 1; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: flex; }
            .conv-panel { width: 100%; position: absolute; inset: 70px 0 0 0; z-index: 50; border-right: none; }
            .conv-panel.hidden { display: none; }
            .chat-window { width: 100%; }
            .back-btn { display: flex; }
            .user-name { display: none; }
        }
        @media (max-width: 560px) {
            .conv-panel { width: 100%; }
            .messages-area { padding: 14px 14px; }
            .msg-row { max-width: 88%; }
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="logo">Hire<span class="x">X</span></div>
    <nav>
        <div class="nav-group">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php" class="nav-item"><?php echo getIcon('dashboard',18); ?> Dashboard</a>
            <a href="profile.php" class="nav-item"><?php echo getIcon('user',18); ?> My Profile</a>
            <a href="messages.php" class="nav-item active"><?php echo getIcon('message',18); ?> Messages
                <?php $totalUnread = array_sum(array_column($conversations, 'unread')); if($totalUnread > 0): ?><span class="badge"><?php echo $totalUnread; ?></span><?php endif; ?>
            </a>
            <a href="#" class="nav-item"><?php echo getIcon('calendar',18); ?> My Bookings</a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Preferences</div>
            <a href="#" class="nav-item"><?php echo getIcon('bookmark',18); ?> Saved Workers</a>
            <a href="#" class="nav-item"><?php echo getIcon('card',18); ?> Payments</a>
            <a href="#" class="nav-item"><?php echo getIcon('settings',18); ?> Settings</a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="#" class="nav-item"><?php echo getIcon('help',18); ?> Help Center</a>
            <a href="#" class="nav-item"><?php echo getIcon('phone',18); ?> Contact Us</a>
        </div>
    </nav>
    <div class="signout-container">
        <a href="logout.php" class="signout-btn"><?php echo getIcon('logout',18); ?> Sign Out</a>
    </div>
</aside>

<!-- ── MAIN ── -->
<div class="main-content" id="mainContent">

    <!-- Top Header -->
    <div class="top-header">
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()"><?php echo getIcon('menu',20); ?></button>
            <span class="page-heading">Messages</span>
        </div>
        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()">
                <span id="themeIcon"><?php echo getIcon('moon',16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            <button class="icon-btn"><?php echo getIcon('bell',18); ?><span class="notification-dot"></span></button>
            <a href="user-profile.php" class="user-pill" style="text-decoration:none;">
                <div class="avatar"><?php echo $userInitial; ?></div>
                <span class="user-name"><?php echo $userName; ?></span>
            </a>
        </div>
    </div>

    <!-- Chat Layout -->
    <div class="chat-layout">

        <!-- Conversation List -->
        <div class="conv-panel" id="convPanel">
            <div class="conv-header">
                <div class="conv-title">Chats</div>
                <div class="conv-search">
                    <?php echo getIcon('search',14); ?>
                    <input type="text" id="convSearch" placeholder="Search conversations..." oninput="filterConvs(this.value)">
                </div>
            </div>
            <div class="conv-list" id="convList">
                <?php if (empty($conversations)): ?>
                    <div class="no-conversations">
                        <?php echo getIcon('bubble', 36); ?>
                        <p>No conversations yet.<br>Book a worker to start chatting!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($conversations as $conv):
                        $isActive = $conv['id'] == $active_id;
                        $convAvatar = avatarUrl($conv['photo'], $conv['name']);
                        $preview = $conv['last_msg'] ? htmlspecialchars(mb_substr($conv['last_msg'], 0, 38)) . (mb_strlen($conv['last_msg']) > 38 ? '…' : '') : 'No messages yet';
                        $timeAgo = '';
                        if ($conv['last_time']) {
                            $ts = strtotime($conv['last_time']);
                            $diff = time() - $ts;
                            if ($diff < 60) $timeAgo = 'now';
                            elseif ($diff < 3600) $timeAgo = floor($diff/60).'m';
                            elseif ($diff < 86400) $timeAgo = floor($diff/3600).'h';
                            else $timeAgo = date('M j', $ts);
                        }
                    ?>
                    <a href="?with=<?php echo $conv['id']; ?>" 
                       class="conv-item <?php echo $isActive ? 'active' : ''; ?>"
                       data-name="<?php echo strtolower(htmlspecialchars($conv['name'])); ?>"
                       onclick="selectConv(event, <?php echo $conv['id']; ?>)">
                        <div class="conv-avatar">
                            <img src="<?php echo $convAvatar; ?>" alt="<?php echo htmlspecialchars($conv['name']); ?>">
                            <span class="online-dot"></span>
                        </div>
                        <div class="conv-info">
                            <div class="conv-name"><?php echo htmlspecialchars($conv['name']); ?></div>
                            <div class="conv-preview"><?php echo $preview; ?></div>
                        </div>
                        <div class="conv-meta">
                            <span class="conv-time"><?php echo $timeAgo; ?></span>
                            <?php if ($conv['unread'] > 0): ?>
                            <span class="unread-badge"><?php echo $conv['unread']; ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="chat-window" id="chatWindow">

            <?php if ($activePartner): ?>
            <!-- Chat Header -->
            <div class="chat-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <button class="back-btn" onclick="showConvPanel()"><?php echo getIcon('back',16); ?></button>
                    <div class="chat-partner">
                        <img src="<?php echo avatarUrl($activePartner['photo'], $activePartner['name']); ?>"
                             alt="<?php echo htmlspecialchars($activePartner['name']); ?>"
                             class="chat-partner-avatar">
                        <div>
                            <div class="chat-partner-name"><?php echo htmlspecialchars($activePartner['name']); ?></div>
                            <div class="chat-partner-status">● Online</div>
                        </div>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="chat-action-btn" title="Call"><?php echo getIcon('phone',15); ?></button>
                    <button class="chat-action-btn" title="More options"><?php echo getIcon('more',15); ?></button>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-area" id="messagesArea">
                <div id="msgContainer">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="typing-indicator" id="typingIndicator" style="display:none;">
                    <img src="<?php echo avatarUrl($activePartner['photo'], $activePartner['name']); ?>" class="msg-avatar" alt="">
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
            </div>

            <!-- Input Bar -->
            <div class="chat-input-bar">
                <div class="input-row">

                    <button onclick="openBooking()" class="input-side-btn">📅</button>

                    <textarea id="msgInput" placeholder="Type message..."></textarea>

                    <button onclick="sendMessage()" class="send-btn">Send</button>
                </div>
            </div>

            <?php else: ?>
            <!-- No conversation selected -->
            <div class="no-chat-selected">
                <div class="big-icon"><?php echo getIcon('message',36); ?></div>
                <h3>Your Messages</h3>
                <p>Select a conversation from the list to start chatting with a worker.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<div id="bookingModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999;">
    <div style="background:white; padding:20px; width:320px; margin:100px auto; border-radius:10px;">
        
        <h3>Book Meeting</h3>

        <label>Date:</label><br>
        <input type="date" id="bookingDate"><br><br>

        <label>Time:</label><br>
        <input type="time" id="bookingTime"><br><br>

        <button onclick="confirmBooking()">Confirm</button>
        <button onclick="closeBooking()">Cancel</button>
    </div>
</div>

<script>
const CURRENT_USER_ID = <?php echo $user_id; ?>;
const ACTIVE_PARTNER_ID = <?php echo $active_id ?: 0; ?>;
let lastMessageId = 0;
let pollTimer = null;

/* ── INIT ── */
document.addEventListener('DOMContentLoaded', () => {
    applyTheme();
    if (ACTIVE_PARTNER_ID) {
        loadMessages(true);
        startPolling();
    }
});

// new function
function openBooking(){
    document.getElementById("bookingModal").style.display = "block";
}

function closeBooking(){
    document.getElementById("bookingModal").style.display = "none";
}

function confirmBooking(){
    const date = document.getElementById("bookingDate").value;
    const time = document.getElementById("bookingTime").value;

    if(!date || !time){
        alert("Select date & time");
        return;
    }

    fetch("ajax/book_worker.php", {
        method: "POST",
        body: new URLSearchParams({
            worker_id: ACTIVE_PARTNER_ID,
            date: date,
            time: time
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert("Booking Confirmed!");

            // Send message automatically
            fetch("ajax/send_message.php", {
                method: "POST",
                body: new URLSearchParams({
                    receiver_id: ACTIVE_PARTNER_ID,
                    message: `📅 Booking confirmed on ${date} at ${time}`
                })
            });

            closeBooking();
        }
    });
}


/* ── LOAD MESSAGES (AJAX) ── */
function loadMessages(scrollToBottom = false) {
    if (!ACTIVE_PARTNER_ID) return;
    fetch(`ajax/get_messages.php?with=${ACTIVE_PARTNER_ID}&after=${lastMessageId}`)
        .then(r => r.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => appendMessage(msg));
                lastMessageId = data.messages[data.messages.length - 1].id;
                if (scrollToBottom) scrollDown();
            }
        })
        .catch(console.error);
}

function appendMessage(msg) {
    const container = document.getElementById('msgContainer');
    const isSent = parseInt(msg.sender_id) === CURRENT_USER_ID;
    const time = formatTime(msg.created_at);

    // Date divider
    const msgDate = msg.created_at.split(' ')[0];
    const lastDivider = container.querySelector('[data-date]:last-of-type');
    if (!lastDivider || lastDivider.dataset.date !== msgDate) {
        const divider = document.createElement('div');
        divider.className = 'date-divider';
        divider.dataset.date = msgDate;
        divider.innerHTML = `<span>${formatDate(msgDate)}</span>`;
        container.appendChild(divider);
    }

    const row = document.createElement('div');
    row.className = `msg-row ${isSent ? 'sent' : 'received'}`;
    row.dataset.id = msg.id;

    const avatarSrc = isSent
        ? '<?php echo avatarUrl($user["photo"] ?? "", $userName); ?>'
        : '<?php echo $activePartner ? avatarUrl($activePartner["photo"] ?? "", htmlspecialchars($activePartner["name"])) : ""; ?>';

    row.innerHTML = `
        <img src="${avatarSrc}" class="msg-avatar" alt="">
        <div>
            <div class="msg-bubble">${escHtml(msg.message)}</div>
            <div class="msg-meta" style="justify-content:${isSent ? 'flex-end' : 'flex-start'}">
                <span class="msg-time">${time}</span>
                ${isSent ? `<span class="msg-status"><?php echo getIcon('check-dbl', 12); ?></span>` : ''}
            </div>
        </div>`;
    container.appendChild(row);
    scrollDown();
}

/* ── SEND MESSAGE (AJAX) ── */
function sendMessage() {
    const input = document.getElementById('msgInput');
    const text = input.value.trim();
    if (!text || !ACTIVE_PARTNER_ID) return;

    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    input.value = '';
    autoResize(input);

    const formData = new FormData();
    formData.append('receiver_id', ACTIVE_PARTNER_ID);
    formData.append('message', text);

    fetch('ajax/send_message.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                appendMessage(data.message);
                scrollDown();
            }
        })
        .catch(console.error)
        .finally(() => { btn.disabled = false; input.focus(); });
}

/* ── POLLING ── */
function startPolling() {
    loadMessages(true);
    pollTimer = setInterval(() => loadMessages(false), 3000);
}

/* ── UI HELPERS ── */
function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function scrollDown() {
    const area = document.getElementById('messagesArea');
    if (area) area.scrollTop = area.scrollHeight;
}

function filterConvs(q) {
    const items = document.querySelectorAll('.conv-item');
    items.forEach(item => {
        const name = item.dataset.name || '';
        item.style.display = name.includes(q.toLowerCase()) ? '' : 'none';
    });
}

function selectConv(e, id) {
    e.preventDefault();
    window.location.href = `?with=${id}`;
}

function showConvPanel() {
    document.getElementById('convPanel').classList.remove('hidden');
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatTime(dt) {
    const d = new Date(dt.replace(' ', 'T'));
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dateStr) {
    const today = new Date(); today.setHours(0,0,0,0);
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate()-1);
    const d = new Date(dateStr);
    if (d.toDateString() === today.toDateString()) return 'Today';
    if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}

/* ── SIDEBAR ── */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
}

/* ── THEME ── */
function applyTheme() {
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme','dark');
        document.getElementById('themeIcon').innerHTML = '<?php echo addslashes(getIcon("sun",16)); ?>';
        document.getElementById('themeText').textContent = 'Light';
    }
}
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    if (isDark) {
        html.removeAttribute('data-theme');
        document.getElementById('themeIcon').innerHTML = '<?php echo addslashes(getIcon("moon",16)); ?>';
        document.getElementById('themeText').textContent = 'Dark';
        localStorage.setItem('theme','light');
    } else {
        html.setAttribute('data-theme','dark');
        document.getElementById('themeIcon').innerHTML = '<?php echo addslashes(getIcon("sun",16)); ?>';
        document.getElementById('themeText').textContent = 'Light';
        localStorage.setItem('theme','dark');
    }
}

/* ── CLEANUP ── */
window.addEventListener('beforeunload', () => { if(pollTimer) clearInterval(pollTimer); });
document.addEventListener('keydown', e => {
    if (e.key==='Escape' && document.getElementById('sidebar').classList.contains('active')) toggleSidebar();
});
</script>
</body>
</html>