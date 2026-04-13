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

// Photo path
$userPhoto = null;
if (!empty($user['photo'])) {
    if (filter_var($user['photo'], FILTER_VALIDATE_URL)) {
        $userPhoto = $user['photo'];
    } else {
        $userPhoto = '../assets/images/users/' . $user['photo'];
    }
}

// FAQ Categories
$faqCategories = [
    [
        "category" => "General",
        "icon" => "grid",
        "faqs" => [
            ["q" => "How do I create an account?", "a" => "Click on 'Sign Up' on the homepage, fill in your details, and verify your email address."],
            ["q" => "Is HireX free to use?", "a" => "Yes! Creating an account and browsing workers is completely free. You only pay when you hire a worker."],
            ["q" => "How do I reset my password?", "a" => "Go to the login page, click 'Forgot Password', and follow the instructions sent to your email."],
        ]
    ],
    [
        "category" => "Booking & Payments",
        "icon" => "calendar",
        "faqs" => [
            ["q" => "How do I book a worker?", "a" => "Search for workers, select one that fits your needs, click 'View Profile', and then 'Book Now'."],
            ["q" => "What payment methods are accepted?", "a" => "We accept credit/debit cards, UPI, net banking, and digital wallets."],
            ["q" => "Can I cancel a booking?", "a" => "Yes, you can cancel up to 24 hours before the scheduled time for a full refund."],
            ["q" => "When will I be charged?", "a" => "You'll be charged after the work is completed and you confirm satisfaction."],
        ]
    ],
    [
        "category" => "Workers & Services",
        "icon" => "workers",
        "faqs" => [
            ["q" => "Are workers verified?", "a" => "Yes, all workers undergo identity verification and background checks before joining HireX."],
            ["q" => "How are worker ratings calculated?", "a" => "Ratings are based on customer reviews, completed jobs, and response time."],
            ["q" => "Can I request a specific worker?", "a" => "Yes! Save your favorite workers and book them directly for future jobs."],
            ["q" => "What if I'm not satisfied with the service?", "a" => "Contact our support team within 24 hours. We'll investigate and offer a refund or replacement."],
        ]
    ],
    [
        "category" => "Safety & Security",
        "icon" => "check",
        "faqs" => [
            ["q" => "Is my personal information safe?", "a" => "Yes, we use industry-standard encryption and never share your data with third parties."],
            ["q" => "What safety measures are in place?", "a" => "All workers are verified, bookings are tracked, and we have 24/7 support for emergencies."],
            ["q" => "Can I report a worker?", "a" => "Yes, use the 'Report' option on any worker profile or contact support directly."],
        ]
    ],
];

// Contact info
$supportEmail = "support@hirex.com";
$supportPhone = "+91 1800-123-4567";
$supportHours = "24/7";

// Handle ticket submission
$ticketSubmitted = false;
$ticketError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = htmlspecialchars($_POST['subject'], ENT_QUOTES, 'UTF-8');
    $category = htmlspecialchars($_POST['category'], ENT_QUOTES, 'UTF-8');
    $priority = htmlspecialchars($_POST['priority'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    
    if (empty($subject) || empty($description)) {
        $ticketError = "Please fill in all required fields.";
    } else {
        // Insert ticket into database
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, category, priority, description, status, created_at) VALUES (?, ?, ?, ?, ?, 'open', NOW())");
        $stmt->bind_param("issss", $user_id, $subject, $category, $priority, $description);
        
        if ($stmt->execute()) {
            $ticketSubmitted = true;
            $ticketId = $stmt->insert_id;
        } else {
            $ticketError = "Failed to submit ticket. Please try again.";
        }
        $stmt->close();
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
        'mail' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>',
        'chevron-down' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>',
        'alert-circle' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        'file-text' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'send' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX - Help Center & Support">
    <title>Help Center — HireX</title>
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

        /* --- HELP CENTER SPECIFIC STYLES --- */
        
        /* Hero Section */
        .help-hero {
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 32px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .help-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .help-hero h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .help-hero p {
            font-size: 15px;
            opacity: 0.95;
            max-width: 500px;
            margin: 0 auto 24px auto;
        }

        .help-search {
            background: white;
            padding: 8px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            max-width: 550px;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .help-search input {
            flex: 1;
            border: none;
            outline: none;
            padding: 14px 18px;
            font-size: 15px;
            color: var(--text-primary);
            background: transparent;
        }

        .help-search button {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .help-search button:hover {
            transform: scale(1.03);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        /* Quick Links */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 40px;
        }

        .quick-link-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-primary);
        }

        .quick-link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px var(--shadow-lg);
            border-color: var(--primary);
        }

        .quick-link-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
        }

        .quick-link-icon.green { background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); color: var(--mint-600); }
        .quick-link-icon.teal { background: linear-gradient(135deg, var(--teal-100), #99f6e4); color: var(--teal-600); }
        .quick-link-icon.yellow { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
        .quick-link-icon.blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }

        .quick-link-card h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .quick-link-card p {
            font-size: 12px;
            color: var(--text-gray);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        /* FAQ Section */
        .section-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .section-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200));
            color: var(--mint-600);
        }

        .section-header h3 {
            font-size: 18px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
        }

        /* FAQ Accordion */
        .faq-category {
            margin-bottom: 28px;
        }

        .faq-category-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--border);
        }

        .faq-category-title svg { width: 18px; height: 18px; }

        .faq-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: var(--transition);
        }

        .faq-item:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 15px var(--shadow);
        }

        .faq-question {
            width: 100%;
            padding: 16px 18px;
            background: transparent;
            border: none;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }

        .faq-question:hover {
            background: var(--primary-light);
        }

        .faq-question.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .faq-question svg {
            transition: var(--transition);
        }

        .faq-question.active svg {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
        }

        .faq-answer-content {
            padding: 0 18px 18px 18px;
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* Contact Info Cards */
        .contact-cards {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .contact-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }

        .contact-card:hover {
            border-color: var(--primary);
            transform: translateX(5px);
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

        .contact-icon.green { background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); color: var(--mint-600); }
        .contact-icon.teal { background: linear-gradient(135deg, var(--teal-100), #99f6e4); color: var(--teal-600); }
        .contact-icon.blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }

        .contact-info h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .contact-info p {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
        }

        .contact-info span {
            font-size: 11px;
            color: var(--text-gray);
        }

        /* Ticket Form */
        .ticket-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .form-group label .required {
            color: var(--danger);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 13px 16px;
            border: 1px solid var(--border);
            border-radius: 11px;
            font-size: 14px;
            color: var(--text-primary);
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 140px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            padding: 15px 24px;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow-lg);
        }

        .btn-submit svg { width: 18px; height: 18px; }

        /* Success Message */
        .success-banner {
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200));
            border: 1px solid var(--mint-300);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .success-banner-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--success);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .success-banner-content h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--mint-600);
            margin-bottom: 4px;
        }

        .success-banner-content p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Error Message */
        .error-banner {
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            border: 1px solid #fca5a5;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .error-banner-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--danger);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .error-banner-content h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--danger);
            margin-bottom: 4px;
        }

        .error-banner-content p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Sidebar Info */
        .help-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }

        .info-card h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card h4 svg { width: 20px; height: 20px; color: var(--primary); }

        .info-list {
            list-style: none;
        }

        .info-list li {
            padding: 12px 0;
            border-bottom: 1px dashed var(--border);
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list li svg {
            width: 16px;
            height: 16px;
            color: var(--success);
            flex-shrink: 0;
        }

        .response-time {
            background: var(--primary-light);
            border-radius: 10px;
            padding: 14px;
            margin-top: 16px;
            text-align: center;
        }

        .response-time p {
            font-size: 12px;
            color: var(--text-gray);
            margin-bottom: 6px;
        }

        .response-time strong {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
        }

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
            .content-grid { grid-template-columns: 1fr; }
            .quick-links { grid-template-columns: repeat(2, 1fr); }
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
            .help-hero { padding: 28px; }
            .help-hero h1 { font-size: 24px; }
            .quick-links { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .toast { left: 18px; right: 18px; bottom: 18px; min-width: auto; }
            .page-title h2 { font-size: 20px; }
        }

        @media (max-width: 480px) {
            .user-name { display: none; }
            .theme-toggle span:last-child { display: none; }
            .help-search { flex-direction: column; gap: 10px; }
            .help-search button { width: 100%; }
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
            <a href="settings.php" class="nav-item">
                <?php echo getIcon('settings', 18); ?> Settings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="help.php" class="nav-item active">
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
            <form class="search-bar" method="GET" action="dashboard.php">
                <?php echo getIcon('search', 18); ?>
                <input type="text" name="search" placeholder="Search workers..." aria-label="Search workers">
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

    <div class="page-title">
        <h2>Help Center</h2>
        <p>Find answers to your questions or get in touch with our support team.</p>
    </div>

    <!-- Hero Section -->
    <div class="help-hero">
        <h1>How can we help you?</h1>
        <p>Search our knowledge base or submit a ticket to get assistance from our support team.</p>
        <form class="help-search" onsubmit="searchFAQs(event)">
            <?php echo getIcon('search', 20); ?>
            <input type="text" id="faqSearch" placeholder="Search for answers (e.g., booking, payment, refund)...">
            <button type="submit">
                <?php echo getIcon('search', 18); ?> Search
            </button>
        </form>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
        <a href="#faqs" class="quick-link-card">
            <div class="quick-link-icon green">
                <?php echo getIcon('help', 26); ?>
            </div>
            <h3>FAQs</h3>
            <p>Common questions answered</p>
        </a>
        <a href="#ticket" class="quick-link-card">
            <div class="quick-link-icon teal">
                <?php echo getIcon('file-text', 26); ?>
            </div>
            <h3>Submit Ticket</h3>
            <p>Get personalized help</p>
        </a>
        <a href="#contact" class="quick-link-card">
            <div class="quick-link-icon yellow">
                <?php echo getIcon('phone', 26); ?>
            </div>
            <h3>Contact Us</h3>
            <p>Call or email support</p>
        </a>
        <a href="dashboard.php" class="quick-link-card">
            <div class="quick-link-icon blue">
                <?php echo getIcon('dashboard', 26); ?>
            </div>
            <h3>My Bookings</h3>
            <p>View your appointments</p>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Left Column -->
        <div class="main-column">
            <!-- FAQs Section -->
            <div class="section-card" id="faqs">
                <div class="section-header">
                    <div class="section-icon">
                        <?php echo getIcon('help', 22); ?>
                    </div>
                    <h3>Frequently Asked Questions</h3>
                </div>

                <?php foreach($faqCategories as $category): ?>
                    <div class="faq-category">
                        <div class="faq-category-title">
                            <?php echo getIcon($category['icon'], 18); ?>
                            <span><?php echo $category['category']; ?></span>
                        </div>
                        
                        <?php foreach($category['faqs'] as $index => $faq): ?>
                            <div class="faq-item">
                                <button class="faq-question" onclick="toggleFAQ(this)">
                                    <span><?php echo $faq['q']; ?></span>
                                    <?php echo getIcon('chevron-down', 18); ?>
                                </button>
                                <div class="faq-answer">
                                    <div class="faq-answer-content">
                                        <?php echo $faq['a']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Ticket Submission Form -->
            <div class="section-card" id="ticket">
                <div class="section-header">
                    <div class="section-icon">
                        <?php echo getIcon('file-text', 22); ?>
                    </div>
                    <h3>Submit a Support Ticket</h3>
                </div>

                <?php if ($ticketSubmitted): ?>
                    <div class="success-banner">
                        <div class="success-banner-icon">
                            <?php echo getIcon('check', 22); ?>
                        </div>
                        <div class="success-banner-content">
                            <h4>Ticket Submitted Successfully!</h4>
                            <p>Your ticket ID is #<?php echo $ticketId; ?>. We'll respond within 24 hours.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($ticketError): ?>
                    <div class="error-banner">
                        <div class="error-banner-icon">
                            <?php echo getIcon('alert-circle', 22); ?>
                        </div>
                        <div class="error-banner-content">
                            <h4>Submission Failed</h4>
                            <p><?php echo $ticketError; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="ticket-form" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <input type="text" name="subject" placeholder="Brief summary of your issue" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="general">General Inquiry</option>
                                <option value="booking">Booking Issue</option>
                                <option value="payment">Payment Problem</option>
                                <option value="worker">Worker Related</option>
                                <option value="account">Account Issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Your Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="your@email.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description <span class="required">*</span></label>
                        <textarea name="description" placeholder="Describe your issue in detail..." required></textarea>
                    </div>

                    <button type="submit" name="submit_ticket" class="btn-submit">
                        <?php echo getIcon('send', 18); ?> Submit Ticket
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column (Sidebar) -->
        <div class="help-sidebar">
            <!-- Contact Info -->
            <div class="info-card" id="contact">
                <h4><?php echo getIcon('phone', 20); ?> Contact Support</h4>
                <div class="contact-cards">
                    <div class="contact-card">
                        <div class="contact-icon green">
                            <?php echo getIcon('mail', 22); ?>
                        </div>
                        <div class="contact-info">
                            <h4>Email Us</h4>
                            <p><?php echo $supportEmail; ?></p>
                            <span>Response within 24 hours</span>
                        </div>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon teal">
                            <?php echo getIcon('phone', 22); ?>
                        </div>
                        <div class="contact-info">
                            <h4>Call Us</h4>
                            <p><?php echo $supportPhone; ?></p>
                            <span>Available <?php echo $supportHours; ?></span>
                        </div>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon blue">
                            <?php echo getIcon('message', 22); ?>
                        </div>
                        <div class="contact-info">
                            <h4>Live Chat</h4>
                            <p>Start Chat</p>
                            <span>Available 9 AM - 9 PM</span>
                        </div>
                    </div>
                </div>

                <div class="response-time">
                    <p>Average Response Time</p>
                    <strong>2-4 Hours</strong>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="info-card">
                <h4><?php echo getIcon('alert-circle', 20); ?> Quick Tips</h4>
                <ul class="info-list">
                    <li>
                        <?php echo getIcon('check', 16); ?>
                        Check FAQ before submitting a ticket
                    </li>
                    <li>
                        <?php echo getIcon('check', 16); ?>
                        Include booking ID for faster resolution
                    </li>
                    <li>
                        <?php echo getIcon('check', 16); ?>
                        Attach screenshots if applicable
                    </li>
                    <li>
                        <?php echo getIcon('check', 16); ?>
                        Keep your contact info updated
                    </li>
                </ul>
            </div>

            <!-- Still Need Help -->
            <div class="info-card" style="background: linear-gradient(135deg, var(--mint-500), var(--teal-500)); border: none;">
                <h4 style="color: white;">
                    <?php echo getIcon('help', 20); ?> Still Need Help?
                </h4>
                <p style="font-size: 13px; color: rgba(255,255,255,0.9); margin-bottom: 16px;">
                    Our support team is here to assist you with any questions or concerns.
                </p>
                <button onclick="scrollToTicket()" style="width: 100%; background: white; color: var(--mint-600); border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: var(--transition);">
                    Submit a Ticket
                </button>
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

    function toggleFAQ(button) {
        button.classList.toggle('active');
        const answer = button.nextElementSibling;
        
        if (button.classList.contains('active')) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
        } else {
            answer.style.maxHeight = '0';
        }
    }

    function searchFAQs(event) {
        event.preventDefault();
        const searchTerm = document.getElementById('faqSearch').value.toLowerCase();
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question span').textContent.toLowerCase();
            const answer = item.querySelector('.faq-answer-content').textContent.toLowerCase();
            
            if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                item.style.display = 'block';
                // Auto-expand matching FAQs
                const button = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');
                if (!button.classList.contains('active')) {
                    button.classList.add('active');
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                }
            } else {
                item.style.display = 'none';
            }
        });

        if (searchTerm) {
            showToast('Search Results', `Found ${document.querySelectorAll('.faq-item[style="display: block;"]').length} matching results`, true);
        }
    }

    function scrollToTicket() {
        document.getElementById('ticket').scrollIntoView({ behavior: 'smooth' });
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
    });

    // Auto-hide success banner after 5 seconds
    <?php if ($ticketSubmitted): ?>
    setTimeout(() => {
        const banner = document.querySelector('.success-banner');
        if (banner) {
            banner.style.transition = 'opacity 0.5s';
            banner.style.opacity = '0';
            setTimeout(() => banner.remove(), 500);
        }
    }, 5000);
    <?php endif; ?>
</script>

</body>
</html>
