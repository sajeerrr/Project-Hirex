<?php
session_start();
include("../database/db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Update Profile Info ---
    if (isset($_POST['update_profile'])) {
        $name     = trim($conn->real_escape_string($_POST['name']));
        $email    = trim($conn->real_escape_string($_POST['email']));
        $phone    = trim($conn->real_escape_string($_POST['phone']));
        $location = trim($conn->real_escape_string($_POST['location']));
        $bio      = trim($conn->real_escape_string($_POST['bio']));

        // Handle photo upload
        $photoUpdate = '';
        if (!empty($_FILES['photo']['name'])) {
            $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            if (in_array($mime, $allowed) && $_FILES['photo']['size'] < 2 * 1024 * 1024) {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $dest = '../assets/images/users/' . $filename;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    $photoUpdate = ", photo='$filename'";
                } else {
                    $error = 'Failed to upload photo. Check directory permissions.';
                }
            } else {
                $error = 'Invalid file type or size exceeds 2MB.';
            }
        }

        if (empty($error)) {
            $sql = "UPDATE users SET name='$name', email='$email', phone='$phone', location='$location', bio='$bio'$photoUpdate WHERE id='$user_id'";
            if ($conn->query($sql)) {
                $_SESSION['user_name'] = $name;
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Could not update profile. Please try again.';
            }
        }
    }

    // --- Update Password ---
    if (isset($_POST['update_password'])) {
        $current  = $_POST['current_password'];
        $new      = $_POST['new_password'];
        $confirm  = $_POST['confirm_password'];

        $res = $conn->query("SELECT password FROM users WHERE id='$user_id'");
        $row = $res->fetch_assoc();

        if (!password_verify($current, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $safeHash = $conn->real_escape_string($hashed);
            if ($conn->query("UPDATE users SET password='$safeHash' WHERE id='$user_id'")) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Could not update password. Please try again.';
            }
        }
    }
}

// Load user data
$userResult = $conn->query("SELECT * FROM users WHERE id='$user_id'");
$user = $userResult->fetch_assoc();

$userName    = htmlspecialchars($user['name'] ?? '');
$userEmail   = htmlspecialchars($user['email'] ?? '');
$userPhone   = htmlspecialchars($user['phone'] ?? '');
$userLocation= htmlspecialchars($user['location'] ?? '');
$userBio     = htmlspecialchars($user['bio'] ?? '');
$userJoined  = isset($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : 'N/A';

$userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $userName), 0, 1));
$userInitial = !empty($userInitial) ? $userInitial : 'A';

// Photo path
$userPhoto = null;
if (!empty($user['photo'])) {
    if (filter_var($user['photo'], FILTER_VALIDATE_URL)) {
        $userPhoto = $user['photo'];
    } else {
        $userPhoto = '../assets/images/users/' . $user['photo'];
    }
}

// SVG Icon Function (same as dashboard)
function getIcon($name, $size = 20, $class = '') {
    $icons = [
        'grid'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
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
        'edit'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'lock'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'camera'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
        'check'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'x'          => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'eye'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'eye-off'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
        'location'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'mail'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'workers'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'star'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'briefcase'  => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'upload'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>',
        'shield'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — HireX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
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
            --bg: #f8faf9;
            --bg-secondary: #ffffff;
            --sidebar-width: 250px;
            --primary: var(--mint-600);
            --primary-hover: #15803d;
            --primary-light: var(--mint-100);
            --secondary: var(--teal-500);
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
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg);
            display: flex;
            color: var(--text-primary);
            transition: var(--transition);
            overflow-x: hidden;
            line-height: 1.6;
            background-image:
                radial-gradient(ellipse at top right, rgba(34,197,94,0.06) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(20,184,166,0.08) 0%, transparent 50%);
        }
        svg { display: block; }

        /* ── SIDEBAR (identical to dashboard) ── */
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
        .logo { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; font-weight: 800; margin-bottom: 32px; padding-left: 14px; letter-spacing: -0.5px; color: var(--text-primary); }
        .logo .x { color: var(--primary); }
        .nav-group { margin-bottom: 24px; }
        .nav-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-gray); margin-bottom: 12px; padding-left: 14px; font-weight: 700; }
        .nav-item { display: flex; align-items: center; padding: 11px 14px; text-decoration: none; color: var(--text-secondary); border-radius: 10px; margin-bottom: 4px; transition: var(--transition); font-weight: 500; cursor: pointer; font-size: 13px; gap: 12px; }
        .nav-item:hover { background: var(--primary-light); color: var(--primary); transform: translateX(4px); }
        .nav-item.active { background: linear-gradient(135deg, var(--mint-500), var(--mint-600)); color: white; box-shadow: 0 4px 15px var(--shadow-lg); }
        .nav-item svg { width: 18px; height: 18px; }
        .badge { background: var(--danger); color: white; font-size: 9px; padding: 2px 6px; border-radius: 6px; margin-left: auto; font-weight: 700; }
        .signout-container { margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border); }
        .signout-btn { display: flex; align-items: center; gap: 12px; padding: 11px 14px; width: 100%; text-decoration: none; color: var(--danger); background: #fef2f2; border-radius: 10px; font-weight: 600; font-size: 13px; transition: var(--transition); justify-content: flex-start; }
        [data-theme="dark"] .signout-btn { background: rgba(239,68,68,0.15); }
        .signout-btn:hover { background: var(--danger); color: white; transform: translateX(4px); }
        .signout-btn svg { width: 18px; height: 18px; }

        /* ── MAIN ── */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 0 32px 48px 32px; transition: var(--transition); }
        .mobile-toggle { display: none; background: var(--bg-secondary); border: 1px solid var(--border); cursor: pointer; color: var(--text-primary); padding: 10px 12px; border-radius: 10px; transition: var(--transition); }
        .mobile-toggle:hover { background: var(--primary-light); border-color: var(--primary); }

        /* ── HEADER ── */
        header { min-height: 70px; display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; margin-bottom: 22px; }
        .header-left { display: flex; align-items: center; gap: 13px; }
        .header-actions { display: flex; align-items: center; gap: 11px; }
        .icon-btn { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 11px; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); position: relative; }
        .icon-btn:hover { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-2px); }
        .notification-dot { position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; background: var(--danger); border-radius: 50%; border: 2px solid var(--bg-secondary); }
        .user-pill { display: flex; align-items: center; gap: 10px; background: var(--bg-secondary); padding: 5px 15px 5px 5px; border-radius: 30px; border: 1px solid var(--border); cursor: pointer; transition: var(--transition); }
        .user-pill:hover { border-color: var(--primary); box-shadow: 0 4px 14px var(--shadow); }
        .avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--mint-500), var(--teal-500)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: white; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name { font-size: 13px; font-weight: 600; color: var(--text-primary); font-family: 'Plus Jakarta Sans', sans-serif; }
        .theme-toggle { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 11px; padding: 10px 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary); transition: var(--transition); font-weight: 500; }
        .theme-toggle:hover { border-color: var(--primary); background: var(--primary-light); }

        /* ── PAGE TITLE ── */
        .page-title { margin: 8px 0 28px 0; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .page-title h2 { font-size: 23px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif; }
        .page-title p { color: var(--text-gray); margin-top: 6px; font-size: 13px; }

        /* ── ALERT BANNERS ── */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 24px;
            animation: slideDown 0.35s ease;
        }
        .alert-success { background: var(--mint-50); border: 1px solid var(--mint-200); color: var(--mint-600); }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        [data-theme="dark"] .alert-success { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: var(--mint-400); }
        [data-theme="dark"] .alert-error   { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: #fca5a5; }
        .alert svg { width: 18px; height: 18px; flex-shrink: 0; }
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

        /* ── PROFILE LAYOUT ── */
        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* ── PROFILE CARD (Left) ── */
        .profile-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 32px 24px;
            text-align: center;
            position: sticky;
            top: 24px;
            box-shadow: 0 4px 20px var(--shadow);
        }
        .profile-photo-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--mint-200);
            display: block;
            transition: var(--transition);
        }
        .profile-photo:hover { border-color: var(--primary); transform: scale(1.03); }
        .profile-photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 800;
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            border: 4px solid var(--mint-200);
        }
        .photo-upload-btn {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 34px;
            height: 34px;
            background: var(--primary);
            border: 3px solid var(--bg-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: var(--transition);
        }
        .photo-upload-btn:hover { background: var(--primary-hover); transform: scale(1.1); }
        .photo-upload-btn svg { width: 14px; height: 14px; }
        #photoInput { display: none; }

        .profile-name {
            font-size: 20px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .profile-email {
            font-size: 12px;
            color: var(--text-gray);
            margin-bottom: 20px;
        }

        .profile-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-align: left;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed var(--border);
        }
        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        .meta-row svg { width: 14px; height: 14px; color: var(--primary); flex-shrink: 0; }
        .meta-row span { font-weight: 500; }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed var(--border);
        }
        .p-stat { text-align: center; }
        .p-stat-val { font-size: 18px; font-weight: 700; color: var(--primary); font-family: 'Plus Jakarta Sans', sans-serif; }
        .p-stat-label { font-size: 10px; color: var(--text-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── FORMS COLUMN (Right) ── */
        .forms-column { display: flex; flex-direction: column; gap: 24px; }

        .form-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 20px var(--shadow);
            transition: var(--transition);
        }
        .form-card:hover { box-shadow: 0 8px 30px var(--shadow-lg); }

        .form-card-header {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--mint-50) 0%, var(--bg-secondary) 100%);
        }
        [data-theme="dark"] .form-card-header { background: linear-gradient(135deg, rgba(34,197,94,0.05) 0%, transparent 100%); }

        .form-card-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200));
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--mint-600);
            flex-shrink: 0;
        }
        [data-theme="dark"] .form-card-icon { background: rgba(34,197,94,0.15); color: var(--mint-400); }
        .form-card-icon svg { width: 20px; height: 20px; }

        .form-card-title { font-size: 15px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-primary); }
        .form-card-subtitle { font-size: 12px; color: var(--text-gray); margin-top: 2px; }

        .form-card-body { padding: 24px; }

        /* ── FORM ELEMENTS ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid .span-2 { grid-column: 1 / -1; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: 0.3px;
        }

        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            pointer-events: none;
            display: flex;
        }
        .input-icon svg { width: 15px; height: 15px; }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            outline: none;
        }
        .form-group textarea { padding: 11px 14px; min-height: 90px; resize: vertical; line-height: 1.6; }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            background: var(--bg-secondary);
            box-shadow: 0 0 0 3px rgba(22,163,74,0.12);
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder { color: var(--text-gray); }

        /* Password eye toggle */
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-right: 44px; }
        .eye-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-gray);
            display: flex;
            padding: 0;
            transition: var(--transition);
        }
        .eye-toggle:hover { color: var(--primary); }
        .eye-toggle svg { width: 16px; height: 16px; }

        /* Password strength */
        .password-strength { margin-top: 8px; }
        .strength-bar { height: 4px; border-radius: 4px; background: var(--border); overflow: hidden; margin-bottom: 4px; }
        .strength-fill { height: 100%; border-radius: 4px; transition: var(--transition); width: 0; }
        .strength-fill.weak   { width: 33%; background: var(--danger); }
        .strength-fill.fair   { width: 66%; background: var(--warning); }
        .strength-fill.strong { width: 100%; background: var(--success); }
        .strength-text { font-size: 11px; color: var(--text-gray); }

        /* ── SAVE BUTTON ── */
        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin-top: 8px;
        }
        .btn-save:hover { background: linear-gradient(135deg, var(--primary-hover), var(--mint-500)); transform: translateY(-2px); box-shadow: 0 6px 20px var(--shadow-lg); }
        .btn-save svg { width: 15px; height: 15px; }

        .btn-save.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .btn-save.danger:hover { background: linear-gradient(135deg, #dc2626, #b91c1c); box-shadow: 0 6px 20px rgba(239,68,68,0.3); }

        /* ── DANGER ZONE ── */
        .danger-zone { border-color: #fecaca; }
        [data-theme="dark"] .danger-zone { border-color: rgba(239,68,68,0.3); }
        .danger-zone .form-card-header { background: linear-gradient(135deg, #fff5f5, var(--bg-secondary)); }
        [data-theme="dark"] .danger-zone .form-card-header { background: linear-gradient(135deg, rgba(239,68,68,0.05), transparent); }
        .danger-zone .form-card-icon { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
        [data-theme="dark"] .danger-zone .form-card-icon { background: rgba(239,68,68,0.15); color: #fca5a5; }

        /* ── OVERLAY & TOAST ── */
        .overlay { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(13,20,17,0.65); backdrop-filter: blur(4px); z-index: 999; opacity:0; transition: var(--transition); }
        .overlay.active { display: block; opacity: 1; }
        .toast { position: fixed; bottom: 26px; right: 26px; background: var(--bg-secondary); border: 1px solid var(--border); border-left: 5px solid var(--success); padding: 15px 20px; border-radius: 12px; box-shadow: 0 14px 45px var(--shadow-lg); transform: translateX(150%); transition: var(--transition); z-index: 2000; display: flex; align-items: center; gap: 12px; min-width: 280px; }
        .toast.show { transform: translateX(0); }
        .toast.error { border-left-color: var(--danger); }
        .toast-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .toast.success .toast-icon { background: rgba(34,197,94,0.15); color: var(--success); }
        .toast.error .toast-icon { background: rgba(239,68,68,0.15); color: var(--danger); }
        .toast-icon svg { width: 17px; height: 17px; }
        .toast-content { flex: 1; }
        .toast-title { font-weight: 600; color: var(--text-primary); font-size: 13px; }
        .toast-message { font-size: 12px; color: var(--text-gray); margin-top: 2px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1100px) { .profile-layout { grid-template-columns: 260px 1fr; } }
        @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } .profile-card { position: static; } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 18px 32px 18px; }
            .mobile-toggle { display: flex; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .span-2 { grid-column: auto; }
            .toast { left: 18px; right: 18px; bottom: 18px; min-width: auto; }
        }
        @media (max-width: 480px) { .user-name { display: none; } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--mint-300); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--mint-500); }
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
            <a href="dashboard.php" class="nav-item"><?php echo getIcon('dashboard', 18); ?> Dashboard</a>
            <a href="profile.php" class="nav-item active"><?php echo getIcon('user', 18); ?> My Profile</a>
            <a href="messages.php" class="nav-item"><?php echo getIcon('message', 18); ?> Messages</a>
            <a href="#" class="nav-item"><?php echo getIcon('calendar', 18); ?> My Bookings</a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Preferences</div>
            <a href="#" class="nav-item"><?php echo getIcon('bookmark', 18); ?> Saved Workers</a>
            <a href="#" class="nav-item"><?php echo getIcon('card', 18); ?> Payments</a>
            <a href="#" class="nav-item"><?php echo getIcon('settings', 18); ?> Settings</a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="#" class="nav-item"><?php echo getIcon('help', 18); ?> Help Center</a>
            <a href="#" class="nav-item"><?php echo getIcon('phone', 18); ?> Contact Us</a>
        </div>
    </nav>
    <div class="signout-container">
        <a href="logout.php" class="signout-btn"><?php echo getIcon('logout', 18); ?> Sign Out</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main-content" id="mainContent">
    <header>
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()"><?php echo getIcon('menu', 20); ?></button>
        </div>
        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()">
                <span id="themeIcon"><?php echo getIcon('moon', 16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            <button class="icon-btn" aria-label="Notifications">
                <?php echo getIcon('bell', 18); ?>
                <span class="notification-dot"></span>
            </button>
            <div class="user-pill">
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

    <div class="page-title">
        <h2>My Profile</h2>
        <p>Manage your personal information and account security.</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo getIcon('check', 18); ?>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo getIcon('x', 18); ?>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">

        <!-- LEFT: Profile Card -->
        <div class="profile-card">
            <!-- Photo with upload trigger -->
            <div class="profile-photo-wrapper">
                <?php if ($userPhoto): ?>
                    <img src="<?php echo $userPhoto; ?>" alt="<?php echo $userName; ?>" class="profile-photo" id="previewImg">
                <?php else: ?>
                    <div class="profile-photo-placeholder" id="previewPlaceholder"><?php echo $userInitial; ?></div>
                    <img src="" alt="" class="profile-photo" id="previewImg" style="display:none;">
                <?php endif; ?>

                <!-- clicking the camera icon opens the file input inside the form -->
                <label for="photoInput" class="photo-upload-btn" title="Change photo">
                    <?php echo getIcon('camera', 14); ?>
                </label>
            </div>

            <div class="profile-name"><?php echo $userName; ?></div>
            <div class="profile-email"><?php echo $userEmail; ?></div>

            <div class="profile-meta">
                <?php if ($userPhone): ?>
                <div class="meta-row"><?php echo getIcon('phone', 14); ?><span><?php echo $userPhone; ?></span></div>
                <?php endif; ?>
                <?php if ($userLocation): ?>
                <div class="meta-row"><?php echo getIcon('location', 14); ?><span><?php echo $userLocation; ?></span></div>
                <?php endif; ?>
                <div class="meta-row"><?php echo getIcon('calendar', 14); ?><span>Member since <?php echo $userJoined; ?></span></div>
            </div>

            <div class="profile-stats">
                <div class="p-stat">
                    <div class="p-stat-val"><?php echo $conn->query("SELECT COUNT(*) as c FROM bookings WHERE user_id='$user_id'")->fetch_assoc()['c'] ?? 0; ?></div>
                    <div class="p-stat-label">Bookings</div>
                </div>
                <div class="p-stat">
                    <div class="p-stat-val"><?php echo $conn->query("SELECT COUNT(*) as c FROM saved_workers WHERE user_id='$user_id'")->fetch_assoc()['c'] ?? 0; ?></div>
                    <div class="p-stat-label">Saved</div>
                </div>
                <div class="p-stat">
                    <div class="p-stat-val"><?php echo $conn->query("SELECT COUNT(*) as c FROM reviews WHERE user_id='$user_id'")->fetch_assoc()['c'] ?? 0; ?></div>
                    <div class="p-stat-label">Reviews</div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Forms -->
        <div class="forms-column">

            <!-- ── Edit Profile Form ── -->
            <div class="form-card">
                <div class="form-card-header">
                    <div class="form-card-icon"><?php echo getIcon('edit', 20); ?></div>
                    <div>
                        <div class="form-card-title">Personal Information</div>
                        <div class="form-card-subtitle">Update your name, contact, and bio</div>
                    </div>
                </div>
                <div class="form-card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Hidden photo input (label in sidebar triggers it) -->
                        <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <div class="input-wrapper">
                                    <span class="input-icon"><?php echo getIcon('user', 15); ?></span>
                                    <input type="text" name="name" value="<?php echo $userName; ?>" placeholder="Your full name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <div class="input-wrapper">
                                    <span class="input-icon"><?php echo getIcon('mail', 15); ?></span>
                                    <input type="email" name="email" value="<?php echo $userEmail; ?>" placeholder="your@email.com" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <div class="input-wrapper">
                                    <span class="input-icon"><?php echo getIcon('phone', 15); ?></span>
                                    <input type="tel" name="phone" value="<?php echo $userPhone; ?>" placeholder="+91 98765 43210">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <div class="input-wrapper">
                                    <span class="input-icon"><?php echo getIcon('location', 15); ?></span>
                                    <input type="text" name="location" value="<?php echo $userLocation; ?>" placeholder="City, State">
                                </div>
                            </div>
                            <div class="form-group span-2">
                                <label>Bio</label>
                                <textarea name="bio" placeholder="Tell us a bit about yourself..."><?php echo $userBio; ?></textarea>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn-save">
                            <?php echo getIcon('check', 15); ?> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── Change Password Form ── -->
            <div class="form-card">
                <div class="form-card-header">
                    <div class="form-card-icon"><?php echo getIcon('shield', 20); ?></div>
                    <div>
                        <div class="form-card-title">Change Password</div>
                        <div class="form-card-subtitle">Keep your account secure with a strong password</div>
                    </div>
                </div>
                <div class="form-card-body">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group span-2">
                                <label>Current Password</label>
                                <div class="input-wrapper password-wrapper">
                                    <span class="input-icon"><?php echo getIcon('lock', 15); ?></span>
                                    <input type="password" name="current_password" id="currentPwd" placeholder="Enter current password" required>
                                    <button type="button" class="eye-toggle" onclick="togglePwd('currentPwd', 'eyeIcon0')">
                                        <span id="eyeIcon0"><?php echo getIcon('eye', 16); ?></span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="input-wrapper password-wrapper">
                                    <span class="input-icon"><?php echo getIcon('lock', 15); ?></span>
                                    <input type="password" name="new_password" id="newPwd" placeholder="Min. 8 characters" oninput="checkStrength(this.value)" required>
                                    <button type="button" class="eye-toggle" onclick="togglePwd('newPwd', 'eyeIcon1')">
                                        <span id="eyeIcon1"><?php echo getIcon('eye', 16); ?></span>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                                    <span class="strength-text" id="strengthText">Enter a password</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="input-wrapper password-wrapper">
                                    <span class="input-icon"><?php echo getIcon('lock', 15); ?></span>
                                    <input type="password" name="confirm_password" id="confirmPwd" placeholder="Re-enter new password" required>
                                    <button type="button" class="eye-toggle" onclick="togglePwd('confirmPwd', 'eyeIcon2')">
                                        <span id="eyeIcon2"><?php echo getIcon('eye', 16); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_password" class="btn-save">
                            <?php echo getIcon('shield', 15); ?> Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── Danger Zone ── -->
            <div class="form-card danger-zone">
                <div class="form-card-header">
                    <div class="form-card-icon"><?php echo getIcon('x', 20); ?></div>
                    <div>
                        <div class="form-card-title">Danger Zone</div>
                        <div class="form-card-subtitle">Irreversible account actions</div>
                    </div>
                </div>
                <div class="form-card-body">
                    <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
                        Deleting your account is permanent. All your bookings, saved workers, and review history will be erased and cannot be recovered.
                    </p>
                    <button type="button" class="btn-save danger" onclick="confirmDelete()">
                        <?php echo getIcon('x', 15); ?> Delete My Account
                    </button>
                </div>
            </div>

        </div><!-- /forms-column -->
    </div><!-- /profile-layout -->
</main>

<!-- Toast -->
<div class="toast" id="toast">
    <div class="toast-icon" id="toastIconBox"><?php echo getIcon('check', 17); ?></div>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Success</div>
        <div class="toast-message" id="toastMessage">Action completed!</div>
    </div>
</div>

<script>
    /* ── Sidebar ── */
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('overlay').classList.toggle('active');
        document.body.style.overflow = document.getElementById('sidebar').classList.contains('active') ? 'hidden' : '';
    }

    /* ── Theme ── */
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-theme') === 'dark';
        if (isDark) {
            html.removeAttribute('data-theme');
            document.getElementById('themeIcon').innerHTML = '<?php echo addslashes(getIcon("moon", 16)); ?>';
            document.getElementById('themeText').textContent = 'Dark';
            localStorage.setItem('theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            document.getElementById('themeIcon').innerHTML = '<?php echo addslashes(getIcon("sun", 16)); ?>';
            document.getElementById('themeText').textContent = 'Light';
            localStorage.setItem('theme', 'dark');
        }
    }
    (function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('themeIcon').innerHTML = '<?php echo addslashes(getIcon("sun", 16)); ?>';
            document.getElementById('themeText').textContent = 'Light';
        }
    })();

    /* ── Photo Preview ── */
    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('previewImg');
                const placeholder = document.getElementById('previewPlaceholder');
                img.src = e.target.result;
                img.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    /* ── Password Toggle ── */
    function togglePwd(inputId, iconId) {
        const input = document.getElementById(inputId);
        const iconSpan = document.getElementById(iconId);
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        iconSpan.innerHTML = isText
            ? '<?php echo addslashes(getIcon("eye", 16)); ?>'
            : '<?php echo addslashes(getIcon("eye-off", 16)); ?>';
    }

    /* ── Password Strength ── */
    function checkStrength(val) {
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        fill.className = 'strength-fill';
        if (!val) { text.textContent = 'Enter a password'; return; }
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        if (score <= 1) { fill.classList.add('weak');   text.textContent = 'Weak'; }
        else if (score <= 2) { fill.classList.add('fair');   text.textContent = 'Fair'; }
        else { fill.classList.add('strong'); text.textContent = 'Strong'; }
    }

    /* ── Delete Confirm ── */
    function confirmDelete() {
        if (confirm('Are you sure you want to permanently delete your account? This cannot be undone.')) {
            window.location.href = 'delete-account.php';
        }
    }

    /* ── Toast ── */
    function showToast(title, message, success = true) {
        const toast = document.getElementById('toast');
        document.getElementById('toastTitle').textContent = title;
        document.getElementById('toastMessage').textContent = message;
        toast.className = 'toast ' + (success ? 'success' : 'error') + ' show';
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    /* ── Auto-show toast if PHP success/error set ── */
    <?php if (!empty($success)): ?>
    window.addEventListener('DOMContentLoaded', () => showToast('Success', <?php echo json_encode($success); ?>, true));
    <?php elseif (!empty($error)): ?>
    window.addEventListener('DOMContentLoaded', () => showToast('Error', <?php echo json_encode($error); ?>, false));
    <?php endif; ?>

    /* ── Keyboard ── */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && document.getElementById('sidebar').classList.contains('active')) toggleSidebar();
    });
</script>
</body>
</html>