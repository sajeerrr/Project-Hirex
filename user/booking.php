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

// Fetch bookings from database
$bookings = [];

$bookingQuery = "SELECT b.*, w.name as worker_name, w.role as worker_role, w.photo as worker_photo 
                 FROM bookings b 
                 LEFT JOIN workers w ON b.worker_id = w.id 
                 WHERE b.user_id = '$user_id' 
                 ORDER BY b.booking_date DESC, b.booking_time DESC";

$bookingResult = $conn->query($bookingQuery);

if ($bookingResult && $bookingResult->num_rows > 0) {
    while($row = $bookingResult->fetch_assoc()) {
        // Determine status
        $status = $row['status'];
        $bookingDate = $row['booking_date'];
        $bookingTime = $row['booking_time'];
        $currentDateTime = date('Y-m-d H:i:s');
        $bookingDateTime = $bookingDate . ' ' . $bookingTime;
        
        // Auto-update completed status if booking date/time has passed
        if ($status === 'pending' || $status === 'confirmed') {
            if ($bookingDateTime < $currentDateTime) {
                $status = 'completed';
            }
        }
        
        // Worker photo logic
        $workerPhoto = $row['worker_photo'];
        if (!empty($workerPhoto)) {
            if (filter_var($workerPhoto, FILTER_VALIDATE_URL)) {
                $workerPhotoPath = $workerPhoto;
            } else {
                $workerPhotoPath = "../assets/images/workers/" . $workerPhoto;
            }
        } else {
            $workerPhotoPath = "https://ui-avatars.com/api/" . urlencode($row['worker_name'] ?? 'W') . "/100/16a34a/ffffff?rounded=true";
        }
        
        $bookings[] = [
            "id" => $row['id'],
            "worker_name" => $row['worker_name'] ?? 'Unknown Worker',
            "worker_role" => $row['worker_role'] ?? 'Service Professional',
            "worker_photo" => $workerPhotoPath,
            "booking_date" => $bookingDate,
            "booking_time" => $bookingTime,
            "duration" => $row['duration'] ?? 1,
            "price" => $row['price'] ?? 0,
            "status" => $status,
            "address" => $row['address'] ?? '',
            "phone" => $row['phone'] ?? '',
            "notes" => $row['notes'] ?? '',
            "created_at" => $row['created_at'] ?? ''
        ];
    }
}

// Separate upcoming and completed bookings
$upcomingBookings = array_filter($bookings, function($booking) {
    $bookingDateTime = $booking['booking_date'] . ' ' . $booking['booking_time'];
    $currentDateTime = date('Y-m-d H:i:s');
    return in_array($booking['status'], ['pending', 'confirmed']) && $bookingDateTime >= $currentDateTime;
});

$completedBookings = array_filter($bookings, function($booking) {
    $bookingDateTime = $booking['booking_date'] . ' ' . $booking['booking_time'];
    $currentDateTime = date('Y-m-d H:i:s');
    return $booking['status'] === 'completed' || $booking['status'] === 'cancelled' || $bookingDateTime < $currentDateTime;
});

// Calculate stats
$totalBookings = count($bookings);
$upcomingCount = count($upcomingBookings);
$completedCount = count(array_filter($completedBookings, function($b) { return $b['status'] === 'completed'; }));
$cancelledCount = count(array_filter($completedBookings, function($b) { return $b['status'] === 'cancelled'; }));
$totalSpent = array_sum(array_column(array_filter($bookings, function($b) { return $b['status'] !== 'cancelled'; }), 'price'));

// Handle cancel booking request
if (isset($_POST['cancel_booking'])) {
    $bookingId = intval($_POST['booking_id']);
    $cancelQuery = "UPDATE bookings SET status='cancelled' WHERE id='$bookingId' AND user_id='$user_id'";
    if ($conn->query($cancelQuery)) {
        $cancelSuccess = true;
        header("Location: bookings.php?cancelled=1");
        exit;
    }
}

// Photo path
$userPhoto = null;
if (!empty($user['photo'])) {
    if (filter_var($user['photo'], FILTER_VALIDATE_URL)) {
        $userPhoto = $user['photo'];
    } else {
        $userPhoto = '../assets/images/users/' . $user['photo'];
    }
}

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
        'trash' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
        'rupee' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12"/><path d="M6 8h12"/><path d="m6 13 8.5-10"/><path d="M6 13h3"/><path d="M9 13c6.627 0 7.755 5.373 8.5 10H9V13z"/></svg>',
        'filter' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>',
        'download' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    ];
    return $icons[$name] ?? '';
}

// Format date helper
function formatDate($date) {
    if (empty($date)) return 'N/A';
    $timestamp = strtotime($date);
    return date('M d, Y', $timestamp);
}

// Format time helper
function formatTime($time) {
    if (empty($time)) return 'N/A';
    $timestamp = strtotime($time);
    return date('g:i A', $timestamp);
}

// Get status badge class
function getStatusClass($status) {
    switch($status) {
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'completed': return 'status-completed';
        case 'cancelled': return 'status-cancelled';
        default: return 'status-pending';
    }
}

// Get status label
function getStatusLabel($status) {
    switch($status) {
        case 'pending': return 'Pending';
        case 'confirmed': return 'Confirmed';
        case 'completed': return 'Completed';
        case 'cancelled': return 'Cancelled';
        default: return 'Unknown';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX - Manage Your Bookings">
    <title>My Bookings — HireX</title>
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
            grid-template-columns: repeat(4, 1fr);
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
        .stat-icon.red { 
            background: linear-gradient(135deg, #fee2e2, #fecaca); 
            color: var(--danger);
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

        /* --- TABS --- */
        .tabs-container {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: var(--primary);
            background: var(--primary-light);
            border-radius: 8px 8px 0 0;
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: var(--primary-light);
            border-radius: 8px 8px 0 0;
        }

        .tab-btn svg { width: 16px; height: 16px; }

        .tab-count {
            background: var(--primary);
            color: white;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: 700;
        }

        /* --- BOOKING CARDS --- */
        .bookings-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .booking-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 20px;
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 20px;
            align-items: center;
            transition: var(--transition);
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow);
            border-color: var(--primary);
        }

        .booking-worker-photo {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid var(--mint-100);
            transition: var(--transition);
        }

        .booking-card:hover .booking-worker-photo {
            border-color: var(--primary);
        }

        .booking-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .booking-worker-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .booking-worker-role {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .booking-details {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 4px;
        }

        .booking-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .booking-detail svg { width: 14px; height: 14px; color: var(--text-gray); }

        .booking-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge svg { width: 12px; height: 12px; }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: var(--mint-100);
            color: var(--mint-600);
        }

        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-cancelled {
            background: #fee2e2;
            color: var(--danger);
        }

        .booking-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .booking-price span {
            font-size: 11px;
            color: var(--text-gray);
            font-weight: 500;
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        .btn-cancel svg { width: 14px; height: 14px; }

        .btn-view {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-view svg { width: 14px; height: 14px; }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* --- EMPTY STATE --- */
        .empty-state {
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
        .empty-state p { font-size: 13px; margin-bottom: 20px; }

        .btn-primary {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            padding: 11px 24px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: scale(1.05);
            box-shadow: 0 5px 18px var(--shadow-lg);
        }

        .btn-primary svg { width: 16px; height: 16px; }

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

        /* --- CONFIRM MODAL --- */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 20, 17, 0.65);
            backdrop-filter: blur(4px);
            z-index: 2001;
            align-items: center;
            justify-content: center;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 32px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px var(--shadow-lg);
        }

        .modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .modal-icon svg { width: 32px; height: 32px; }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .modal-message {
            font-size: 13px;
            color: var(--text-gray);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn-modal-cancel {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 11px 24px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-modal-cancel:hover {
            border-color: var(--text-gray);
            color: var(--text-primary);
        }

        .btn-modal-confirm {
            background: var(--danger);
            border: 1px solid var(--danger);
            color: white;
            padding: 11px 24px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-modal-confirm:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) {
            .main-content { padding: 0 26px 26px 26px; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
            .booking-card { grid-template-columns: 70px 1fr; }
            .booking-actions { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; align-items: center; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 18px 18px 18px; }
            .mobile-toggle { display: flex; }
            .header-left { width: 100%; justify-content: space-between; }
            .search-bar { order: 3; width: 100%; max-width: none; margin-top: 13px; }
            .stats-bar { grid-template-columns: 1fr; }
            .booking-card { grid-template-columns: 1fr; text-align: center; }
            .booking-worker-photo { margin: 0 auto; }
            .booking-details { justify-content: center; }
            .booking-actions { align-items: center; }
            .action-buttons { justify-content: center; }
            .tabs-container { overflow-x: auto; }
            .tab-btn { white-space: nowrap; }
            .toast { left: 18px; right: 18px; bottom: 18px; min-width: auto; }
            .page-title h2 { font-size: 20px; }
        }

        @media (max-width: 480px) {
            .user-name { display: none; }
            .theme-toggle span:last-child { display: none; }
            .stats-bar { gap: 12px; }
            .stat-card { padding: 15px; }
            .booking-actions { flex-direction: column; }
            .action-buttons { width: 100%; justify-content: center; }
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
            <a href="bookings.php" class="nav-item active">
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
            <a href="#" class="nav-item">
                <?php echo getIcon('help', 18); ?> Help Center
            </a>
            <a href="#" class="nav-item">
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
                <input type="text" name="search" placeholder="Search bookings..." value="" aria-label="Search bookings">
                <button type="submit" style="background:none; border:none; cursor:pointer; color: var(--primary); font-weight: 600; font-size: 13px;">Search</button>
            </form>
        </div>

        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle Dark Mode">
                <span id="themeIcon"><?php echo getIcon('moon', 16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            
            <button class="icon-btn" aria-label="Notifications" onclick="showToast('Notifications', 'You have 2 booking updates', true)">
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
            <div class="stat-icon green"><?php echo getIcon('calendar', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo $totalBookings; ?></h4>
                <p>Total Bookings</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><?php echo getIcon('clock', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo $upcomingCount; ?></h4>
                <p>Upcoming</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><?php echo getIcon('check', 22); ?></div>
            <div class="stat-info">
                <h4><?php echo $completedCount; ?></h4>
                <p>Completed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><?php echo getIcon('rupee', 22); ?></div>
            <div class="stat-info">
                <h4>₹<?php echo number_format($totalSpent); ?></h4>
                <p>Total Spent</p>
            </div>
        </div>
    </div>

    <div class="page-title">
        <h2>My Bookings</h2>
        <p>Manage and track all your service appointments.</p>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <button class="tab-btn active" onclick="switchTab('upcoming')" id="tab-upcoming">
            <?php echo getIcon('clock', 16); ?>
            Upcoming
            <span class="tab-count"><?php echo $upcomingCount; ?></span>
        </button>
        <button class="tab-btn" onclick="switchTab('completed')" id="tab-completed">
            <?php echo getIcon('check', 16); ?>
            Completed
            <span class="tab-count"><?php echo $completedCount + $cancelledCount; ?></span>
        </button>
    </div>

    <!-- Upcoming Bookings -->
    <div class="bookings-container" id="upcoming-bookings">
        <?php if (empty($upcomingBookings)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><?php echo getIcon('calendar', 55); ?></div>
                <h3>No Upcoming Bookings</h3>
                <p>You don't have any upcoming appointments scheduled.</p>
                <a href="dashboard.php" class="btn-primary">
                    <?php echo getIcon('workers', 16); ?> Find Workers
                </a>
            </div>
        <?php else: ?>
            <?php foreach($upcomingBookings as $booking): ?>
                <div class="booking-card">
                    <img src="<?php echo $booking['worker_photo']; ?>" alt="<?php echo htmlspecialchars($booking['worker_name']); ?>" class="booking-worker-photo">
                    
                    <div class="booking-info">
                        <div class="booking-worker-name"><?php echo htmlspecialchars($booking['worker_name']); ?></div>
                        <div class="booking-worker-role"><?php echo htmlspecialchars($booking['worker_role']); ?></div>
                        
                        <div class="booking-details">
                            <div class="booking-detail">
                                <?php echo getIcon('calendar', 14); ?>
                                <?php echo formatDate($booking['booking_date']); ?>
                            </div>
                            <div class="booking-detail">
                                <?php echo getIcon('clock', 14); ?>
                                <?php echo formatTime($booking['booking_time']); ?>
                            </div>
                            <div class="booking-detail">
                                <?php echo getIcon('clock', 14); ?>
                                <?php echo $booking['duration']; ?> hour(s)
                            </div>
                            <?php if (!empty($booking['address'])): ?>
                            <div class="booking-detail">
                                <?php echo getIcon('location', 14); ?>
                                <?php echo htmlspecialchars($booking['address']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="booking-actions">
                        <span class="status-badge <?php echo getStatusClass($booking['status']); ?>">
                            <?php 
                            switch($booking['status']) {
                                case 'pending': echo getIcon('clock', 12); break;
                                case 'confirmed': echo getIcon('check', 12); break;
                                default: echo getIcon('clock', 12);
                            }
                            ?>
                            <?php echo getStatusLabel($booking['status']); ?>
                        </span>
                        <div class="booking-price">₹<?php echo number_format($booking['price']); ?> <span>total</span></div>
                        <div class="action-buttons">
                            <?php if ($booking['status'] !== 'cancelled'): ?>
                            <button class="btn-cancel" onclick="openCancelModal(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['worker_name']); ?>')">
                                <?php echo getIcon('trash', 14); ?> Cancel
                            </button>
                            <?php endif; ?>
                            <button class="btn-view" onclick="showToast('Contact Worker', 'Calling <?php echo htmlspecialchars($booking['worker_name']); ?>...', true)">
                                <?php echo getIcon('phone', 14); ?> Contact
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Completed Bookings -->
    <div class="bookings-container" id="completed-bookings" style="display: none;">
        <?php if (empty($completedBookings)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><?php echo getIcon('check', 55); ?></div>
                <h3>No Completed Bookings</h3>
                <p>You haven't completed any service appointments yet.</p>
                <a href="dashboard.php" class="btn-primary">
                    <?php echo getIcon('workers', 16); ?> Book a Worker
                </a>
            </div>
        <?php else: ?>
            <?php foreach($completedBookings as $booking): ?>
                <div class="booking-card">
                    <img src="<?php echo $booking['worker_photo']; ?>" alt="<?php echo htmlspecialchars($booking['worker_name']); ?>" class="booking-worker-photo">
                    
                    <div class="booking-info">
                        <div class="booking-worker-name"><?php echo htmlspecialchars($booking['worker_name']); ?></div>
                        <div class="booking-worker-role"><?php echo htmlspecialchars($booking['worker_role']); ?></div>
                        
                        <div class="booking-details">
                            <div class="booking-detail">
                                <?php echo getIcon('calendar', 14); ?>
                                <?php echo formatDate($booking['booking_date']); ?>
                            </div>
                            <div class="booking-detail">
                                <?php echo getIcon('clock', 14); ?>
                                <?php echo formatTime($booking['booking_time']); ?>
                            </div>
                            <div class="booking-detail">
                                <?php echo getIcon('clock', 14); ?>
                                <?php echo $booking['duration']; ?> hour(s)
                            </div>
                        </div>
                    </div>
                    
                    <div class="booking-actions">
                        <span class="status-badge <?php echo getStatusClass($booking['status']); ?>">
                            <?php 
                            switch($booking['status']) {
                                case 'completed': echo getIcon('check', 12); break;
                                case 'cancelled': echo getIcon('x', 12); break;
                                default: echo getIcon('clock', 12);
                            }
                            ?>
                            <?php echo getStatusLabel($booking['status']); ?>
                        </span>
                        <div class="booking-price">₹<?php echo number_format($booking['price']); ?> <span>total</span></div>
                        <div class="action-buttons">
                            <?php if ($booking['status'] === 'completed'): ?>
                            <button class="btn-view" onclick="showToast('Review Submitted', 'Thank you for your feedback!', true)">
                                <?php echo getIcon('star', 14); ?> Rate
                            </button>
                            <?php endif; ?>
                            <button class="btn-view" onclick="showToast('Invoice Downloaded', 'Booking receipt sent to your email', true)">
                                <?php echo getIcon('download', 14); ?> Invoice
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Cancel Confirmation Modal -->
<div class="modal" id="cancelModal">
    <div class="modal-content">
        <div class="modal-icon">
            <?php echo getIcon('trash', 32); ?>
        </div>
        <h3 class="modal-title">Cancel Booking?</h3>
        <p class="modal-message">Are you sure you want to cancel your booking with <strong id="cancelWorkerName"></strong>? This action cannot be undone.</p>
        <form method="POST" action="">
            <input type="hidden" name="booking_id" id="cancelBookingId">
            <input type="hidden" name="cancel_booking" value="1">
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeCancelModal()">Keep Booking</button>
                <button type="submit" class="btn-modal-confirm">Yes, Cancel</button>
            </div>
        </form>
    </div>
</div>

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

    function switchTab(tab) {
        const upcomingTab = document.getElementById('tab-upcoming');
        const completedTab = document.getElementById('tab-completed');
        const upcomingBookings = document.getElementById('upcoming-bookings');
        const completedBookings = document.getElementById('completed-bookings');
        
        if (tab === 'upcoming') {
            upcomingTab.classList.add('active');
            completedTab.classList.remove('active');
            upcomingBookings.style.display = 'flex';
            completedBookings.style.display = 'none';
        } else {
            completedTab.classList.add('active');
            upcomingTab.classList.remove('active');
            upcomingBookings.style.display = 'none';
            completedBookings.style.display = 'flex';
        }
    }

    function openCancelModal(bookingId, workerName) {
        document.getElementById('cancelBookingId').value = bookingId;
        document.getElementById('cancelWorkerName').textContent = workerName;
        document.getElementById('cancelModal').classList.add('active');
        document.getElementById('overlay').classList.add('active');
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.remove('active');
        document.getElementById('overlay').classList.remove('active');
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

    // Check for cancel success message
    <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] == 1): ?>
    showToast('Booking Cancelled', 'Your appointment has been cancelled successfully', false);
    <?php endif; ?>

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.mobile-toggle');
        const modal = document.getElementById('cancelModal');
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('active') && !modal.contains(e.target)) {
            toggleSidebar();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('sidebar').classList.contains('active')) toggleSidebar();
            if (document.getElementById('cancelModal').classList.contains('active')) closeCancelModal();
        }
    });
</script>

</body>
</html>
