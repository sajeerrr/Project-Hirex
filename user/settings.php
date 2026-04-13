<?php
session_start();

include("../database/db.php"); // db connection line

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

// Security: Validate session and sanitize output
$user_id = $_SESSION['user_id'];

// Fetch user data
$userQuery = "SELECT * FROM users WHERE id=?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

$userName = htmlspecialchars($user['name']);
$userEmail = htmlspecialchars($user['email']);
$userPhone = htmlspecialchars($user['phone'] ?? '');

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

// ============================================
// SETTINGS TABLE MANAGEMENT
// ============================================

// Ensure user_settings table exists and get settings
$settingsQuery = "SELECT * FROM user_settings WHERE user_id=?";
$stmt = $conn->prepare($settingsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settingsResult = $stmt->get_result();

if ($settingsResult->num_rows === 0) {
    // Create default settings
    $defaultSettings = [
        'email_notifications' => 1,
        'booking_alerts' => 1,
        'message_alerts' => 1,
        'theme' => 'light',
        'default_location' => '',
        'preferred_category' => 'all',
        'show_profile' => 1,
        'hide_contact' => 0,
        'language' => 'en'
    ];
    
    $insertQuery = "INSERT INTO user_settings (user_id, email_notifications, booking_alerts, message_alerts, theme, default_location, preferred_category, show_profile, hide_contact, language) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iiissssiis", $user_id, $defaultSettings['email_notifications'], $defaultSettings['booking_alerts'], $defaultSettings['message_alerts'], $defaultSettings['theme'], $defaultSettings['default_location'], $defaultSettings['preferred_category'], $defaultSettings['show_profile'], $defaultSettings['hide_contact'], $defaultSettings['language']);
    $stmt->execute();
    $stmt->close();
    
    $settings = $defaultSettings;
} else {
    $settings = $settingsResult->fetch_assoc();
    $stmt->close();
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_notifications') {
        $emailNotif = isset($_POST['email_notifications']) ? 1 : 0;
        $bookingAlerts = isset($_POST['booking_alerts']) ? 1 : 0;
        $messageAlerts = isset($_POST['message_alerts']) ? 1 : 0;
        
        $updateQuery = "UPDATE user_settings SET email_notifications=?, booking_alerts=?, message_alerts=? WHERE user_id=?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("iiii", $emailNotif, $bookingAlerts, $messageAlerts, $user_id);
        
        if ($stmt->execute()) {
            $successMessage = 'Notification settings updated successfully!';
            $settings['email_notifications'] = $emailNotif;
            $settings['booking_alerts'] = $bookingAlerts;
            $settings['message_alerts'] = $messageAlerts;
            
            // Log activity
            $activityQuery = "INSERT INTO user_activity (user_id, action, details) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($activityQuery);
            $actionDetails = 'Updated notification preferences';
            $stmt->bind_param("iss", $user_id, $actionDetails, $actionDetails);
            $stmt->execute();
            $stmt->close();
        } else {
            $errorMessage = 'Failed to update notification settings.';
        }
        $stmt->close();
        
    } elseif ($action === 'update_preferences') {
        $theme = $_POST['theme'] ?? 'light';
        $defaultLocation = htmlspecialchars($_POST['default_location'] ?? '');
        $preferredCategory = $_POST['preferred_category'] ?? 'all';
        
        $updateQuery = "UPDATE user_settings SET theme=?, default_location=?, preferred_category=? WHERE user_id=?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssi", $theme, $defaultLocation, $preferredCategory, $user_id);
        
        if ($stmt->execute()) {
            $successMessage = 'Preferences updated successfully!';
            $settings['theme'] = $theme;
            $settings['default_location'] = $defaultLocation;
            $settings['preferred_category'] = $preferredCategory;
            
            // Log activity
            $activityQuery = "INSERT INTO user_activity (user_id, action, details) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($activityQuery);
            $actionDetails = 'Updated preferences';
            $stmt->bind_param("iss", $user_id, $actionDetails, $actionDetails);
            $stmt->execute();
            $stmt->close();
        } else {
            $errorMessage = 'Failed to update preferences.';
        }
        $stmt->close();
        
    } elseif ($action === 'update_privacy') {
        $showProfile = isset($_POST['show_profile']) ? 1 : 0;
        $hideContact = isset($_POST['hide_contact']) ? 1 : 0;
        
        $updateQuery = "UPDATE user_settings SET show_profile=?, hide_contact=? WHERE user_id=?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("iii", $showProfile, $hideContact, $user_id);
        
        if ($stmt->execute()) {
            $successMessage = 'Privacy settings updated successfully!';
            $settings['show_profile'] = $showProfile;
            $settings['hide_contact'] = $hideContact;
            
            // Log activity
            $activityQuery = "INSERT INTO user_activity (user_id, action, details) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($activityQuery);
            $actionDetails = 'Updated privacy settings';
            $stmt->bind_param("iss", $user_id, $actionDetails, $actionDetails);
            $stmt->execute();
            $stmt->close();
        } else {
            $errorMessage = 'Failed to update privacy settings.';
        }
        $stmt->close();
        
    } elseif ($action === 'download_data') {
        // Export user data as JSON
        $userData = [
            'user_id' => $user_id,
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'settings' => $settings,
            'export_date' => date('Y-m-d H:i:s')
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="hirex_user_data_' . $user_id . '.json"');
        echo json_encode($userData, JSON_PRETTY_PRINT);
        exit;
    }
}

// ============================================
// FETCH ACTIVITY LOG
// ============================================

$activityQuery = "SELECT * FROM user_activity WHERE user_id=? ORDER BY timestamp DESC LIMIT 10";
$stmt = $conn->prepare($activityQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activityResult = $stmt->get_result();
$activities = [];
while ($row = $activityResult->fetch_assoc()) {
    $activities[] = $row;
}
$stmt->close();

// ============================================
// SVG Icon Function (Same as Dashboard)
// ============================================

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
        'mail' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="M5 12h14"/><path d="M12 12v9"/></svg>',
        'shield' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'database' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
        'download' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        'activity' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
        'eye' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'eye-off' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
        'lock' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'unlock' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>',
    ];
    return $icons[$name] ?? '';
}

// Worker categories for dropdown
$workerCategories = [
    ['id' => 'all', 'name' => 'All Categories'],
    ['id' => 'Electrician', 'name' => 'Electrician'],
    ['id' => 'Plumber', 'name' => 'Plumber'],
    ['id' => 'Carpenter', 'name' => 'Carpenter'],
    ['id' => 'Painter', 'name' => 'Painter'],
    ['id' => 'AC Technician', 'name' => 'AC Technician'],
    ['id' => 'Mechanic', 'name' => 'Mechanic'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX - User Settings">
    <title>Settings — HireX</title>
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

        .logo .x { color: var(--primary); }

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
            color: white; 
            overflow: hidden; 
        }
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

        /* --- SETTINGS GRID --- */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .settings-grid { grid-template-columns: 1fr; }
        }

        /* --- SETTINGS CARD --- */
        .settings-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 24px;
            transition: var(--transition);
        }

        .settings-card:hover {
            box-shadow: 0 10px 30px var(--shadow);
            border-color: var(--primary);
        }

        .settings-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .settings-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .settings-card-icon.notifications { 
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); 
            color: var(--mint-600);
        }
        .settings-card-icon.preferences { 
            background: linear-gradient(135deg, var(--teal-100), #99f6e4); 
            color: var(--teal-600);
        }
        .settings-card-icon.privacy { 
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe); 
            color: #4f46e5;
        }
        .settings-card-icon.data { 
            background: linear-gradient(135deg, #fef3c7, #fde68a); 
            color: #b45309;
        }

        .settings-card-icon svg { width: 22px; height: 22px; }

        .settings-card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .settings-card-desc {
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 2px;
        }

        /* --- TOGGLE SWITCH --- */
        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px dashed var(--border);
        }

        .toggle-group:last-child { border-bottom: none; }

        .toggle-label {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .toggle-label-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .toggle-label-desc {
            font-size: 11px;
            color: var(--text-gray);
        }

        .toggle-switch {
            position: relative;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border);
            transition: var(--transition);
            border-radius: 26px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle-switch input:checked + .toggle-slider {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }

        .toggle-switch:hover .toggle-slider {
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        /* --- FORM ELEMENTS --- */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group:last-child { margin-bottom: 0; }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        .form-select {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: var(--transition);
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        /* --- BUTTONS --- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: translateY(-2px);
            box-shadow: 0 5px 18px var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--bg);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(239, 68, 68, 0.3);
        }

        .btn svg { width: 16px; height: 16px; }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* --- ACTIVITY LOG --- */
        .activity-list {
            max-height: 280px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border);
        }

        .activity-item:last-child { border-bottom: none; }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--primary-light);
            color: var(--primary);
        }

        .activity-icon svg { width: 16px; height: 16px; }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 3px;
        }

        .activity-time {
            font-size: 11px;
            color: var(--text-gray);
        }

        /* --- ALERTS --- */
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
        }

        .alert-success {
            background: var(--mint-50);
            border: 1px solid var(--mint-200);
            color: var(--mint-600);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
        }

        [data-theme="dark"] .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
        }

        [data-theme="dark"] .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .alert svg { width: 18px; height: 18px; flex-shrink: 0; }

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
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 18px 18px 18px; }
            .mobile-toggle { display: flex; }
            .header-left { width: 100%; justify-content: space-between; }
            .settings-grid { grid-template-columns: 1fr; }
            .toast { left: 18px; right: 18px; bottom: 18px; min-width: auto; }
            .page-title h2 { font-size: 20px; }
            .btn-group { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }

        @media (max-width: 480px) {
            .user-name { display: none; }
            .theme-toggle span:last-child { display: none; }
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
            <a href="dashboard.php" class="nav-item">
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
            <a href="settings.php" class="nav-item active">
                <?php echo getIcon('settings', 18); ?> Settings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="#" class="nav-item">
                <?php echo getIcon('help', 18); ?> Help Center
            </a>
            <a href="#" class="nav-item">
                <?php echo getIcon('phone', 18); ?> Contact Us
            </a>
        </div>
    </nav>

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
            <h1 style="font-size: 20px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif;">Settings</h1>
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

    <div class="page-title">
        <h2>Account Settings</h2>
        <p>Manage your notifications, preferences, privacy, and data.</p>
    </div>

    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?php echo getIcon('check', 18); ?>
            <span><?php echo $successMessage; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error">
            <?php echo getIcon('x', 18); ?>
            <span><?php echo $errorMessage; ?></span>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- ============================================ -->
        <!-- NOTIFICATIONS SECTION -->
        <!-- ============================================ -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon notifications">
                    <?php echo getIcon('bell', 22); ?>
                </div>
                <div>
                    <div class="settings-card-title">Notifications</div>
                    <div class="settings-card-desc">Manage how you receive alerts</div>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_notifications">
                
                <div class="toggle-group">
                    <div class="toggle-label">
                        <span class="toggle-label-title">Email Notifications</span>
                        <span class="toggle-label-desc">Receive updates via email</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-label">
                        <span class="toggle-label-title">Booking Alerts</span>
                        <span class="toggle-label-desc">Get notified about booking status</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="booking_alerts" <?php echo $settings['booking_alerts'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-label">
                        <span class="toggle-label-title">Message Alerts</span>
                        <span class="toggle-label-desc">New message notifications</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="message_alerts" <?php echo $settings['message_alerts'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo getIcon('check', 16); ?> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- PREFERENCES SECTION -->
        <!-- ============================================ -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon preferences">
                    <?php echo getIcon('settings', 22); ?>
                </div>
                <div>
                    <div class="settings-card-title">Preferences</div>
                    <div class="settings-card-desc">Customize your experience</div>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_preferences">
                
                <div class="form-group">
                    <label class="form-label">Theme</label>
                    <select class="form-select" name="theme" id="themeSelect">
                        <option value="light" <?php echo $settings['theme'] === 'light' ? 'selected' : ''; ?>>Light Mode</option>
                        <option value="dark" <?php echo $settings['theme'] === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Location</label>
                    <input type="text" class="form-input" name="default_location" value="<?php echo htmlspecialchars($settings['default_location']); ?>" placeholder="Enter your city">
                </div>

                <div class="form-group">
                    <label class="form-label">Preferred Worker Category</label>
                    <select class="form-select" name="preferred_category">
                        <?php foreach($workerCategories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $settings['preferred_category'] === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo getIcon('check', 16); ?> Save Preferences
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- PRIVACY SECTION -->
        <!-- ============================================ -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon privacy">
                    <?php echo getIcon('shield', 22); ?>
                </div>
                <div>
                    <div class="settings-card-title">Privacy</div>
                    <div class="settings-card-desc">Control your visibility</div>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_privacy">
                
                <div class="toggle-group">
                    <div class="toggle-label">
                        <span class="toggle-label-title">Show Profile</span>
                        <span class="toggle-label-desc">Make your profile visible to workers</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_profile" <?php echo $settings['show_profile'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-label">
                        <span class="toggle-label-title">Hide Contact Info</span>
                        <span class="toggle-label-desc">Keep phone & email private</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="hide_contact" <?php echo $settings['hide_contact'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo getIcon('lock', 16); ?> Save Privacy Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- DATA SECTION -->
        <!-- ============================================ -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon data">
                    <?php echo getIcon('database', 22); ?>
                </div>
                <div>
                    <div class="settings-card-title">Data & Activity</div>
                    <div class="settings-card-desc">Manage your data and view activity</div>
                </div>
            </div>

            <div class="btn-group" style="margin-bottom: 20px;">
                <a href="?action=download_data" class="btn btn-secondary">
                    <?php echo getIcon('download', 16); ?> Download My Data
                </a>
                <button class="btn btn-secondary" onclick="viewActivityLog()">
                    <?php echo getIcon('activity', 16); ?> View Activity Log
                </button>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 16px;">
                <h4 style="font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">Recent Activity</h4>
                
                <div class="activity-list">
                    <?php if (empty($activities)): ?>
                        <p style="font-size: 12px; color: var(--text-gray); text-align: center; padding: 20px;">No recent activity</p>
                    <?php else: ?>
                        <?php foreach($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php echo getIcon('activity', 16); ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['action']); ?></div>
                                    <div class="activity-time">
                                        <?php 
                                        $time = strtotime($activity['timestamp']);
                                        echo date('M d, Y • h:i A', $time);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
        const themeSelect = document.getElementById('themeSelect');
        
        if (html.getAttribute('data-theme') === 'dark') {
            html.removeAttribute('data-theme');
            themeIcon.innerHTML = '<?php echo getIcon("moon", 16); ?>';
            themeText.textContent = 'Dark';
            localStorage.setItem('theme', 'light');
            if (themeSelect) themeSelect.value = 'light';
        } else {
            html.setAttribute('data-theme', 'dark');
            themeIcon.innerHTML = '<?php echo getIcon("sun", 16); ?>';
            themeText.textContent = 'Light';
            localStorage.setItem('theme', 'dark');
            if (themeSelect) themeSelect.value = 'dark';
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

    // Sync theme select with localStorage
    document.getElementById('themeSelect')?.addEventListener('change', function() {
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        
        if (this.value === 'dark') {
            html.setAttribute('data-theme', 'dark');
            themeIcon.innerHTML = '<?php echo getIcon("sun", 16); ?>';
            themeText.textContent = 'Light';
            localStorage.setItem('theme', 'dark');
        } else {
            html.removeAttribute('data-theme');
            themeIcon.innerHTML = '<?php echo getIcon("moon", 16); ?>';
            themeText.textContent = 'Dark';
            localStorage.setItem('theme', 'light');
        }
    });

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

    function viewActivityLog() {
        showToast('Activity Log', 'Showing recent account activity', true);
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
    });
</script>

</body>
</html>
