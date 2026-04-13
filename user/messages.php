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

// Active conversation partner (worker id from URL)
$active_id = isset($_GET['with']) ? intval($_GET['with']) : 0;

// Load all conversation partners from workers table
$convQuery = "
    SELECT DISTINCT
        w.id, w.name, w.photo,
        (SELECT message FROM messages
         WHERE (sender_id='$user_id' AND sender_type='user' AND receiver_id=w.id AND receiver_type='worker')
            OR (sender_id=w.id AND sender_type='worker' AND receiver_id='$user_id' AND receiver_type='user')
         ORDER BY created_at DESC LIMIT 1) AS last_msg,
        (SELECT created_at FROM messages
         WHERE (sender_id='$user_id' AND sender_type='user' AND receiver_id=w.id AND receiver_type='worker')
            OR (sender_id=w.id AND sender_type='worker' AND receiver_id='$user_id' AND receiver_type='user')
         ORDER BY created_at DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) FROM messages
         WHERE sender_id=w.id AND sender_type='worker'
           AND receiver_id='$user_id' AND receiver_type='user'
           AND is_read=0) AS unread
    FROM workers w
    WHERE w.id IN (
        SELECT CASE
            WHEN sender_id='$user_id' AND sender_type='user' THEN receiver_id
            WHEN receiver_id='$user_id' AND receiver_type='user' THEN sender_id
        END
        FROM messages
        WHERE (sender_id='$user_id' AND sender_type='user')
           OR (receiver_id='$user_id' AND receiver_type='user')
    )
    ORDER BY last_time DESC
";
$convResult = $conn->query($convQuery);
$conversations = [];
if ($convResult) {
    while ($row = $convResult->fetch_assoc()) $conversations[] = $row;
}

if (!$active_id && !empty($conversations)) {
    $active_id = $conversations[0]['id'];
}

// Load active worker info
$activePartner = null;
if ($active_id) {
    $res = $conn->query("SELECT * FROM workers WHERE id='$active_id'");
    if ($res) $activePartner = $res->fetch_assoc();
    $conn->query("UPDATE messages SET is_read=1
        WHERE sender_id='$active_id' AND sender_type='worker'
          AND receiver_id='$user_id' AND receiver_type='user'");
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

// Get worker role for context-aware quick replies
$workerRole = '';
if ($active_id) {
    $roleRes = $conn->query("SELECT role FROM workers WHERE id='$active_id' LIMIT 1");
    if ($roleRes && $roleRes->num_rows > 0) {
        $workerRole = $roleRes->fetch_assoc()['role'];
    }
}

// Total unread
$totalUnread = array_sum(array_column($conversations, 'unread'));

// Quick replies
$quickReplies = [
    'greeting'     => ['label'=>'Greetings','emoji'=>'👋','messages'=>['Hi! I came across your profile on HireX.','Hello, I need help with some work at my place.','Good morning! Are you available today?','Hey! I saw your profile and would like to hire you.']],
    'availability' => ['label'=>'Availability','emoji'=>'📅','messages'=>['Are you available this weekend?','Can you come tomorrow morning?','What time slots are you free this week?','I need someone urgently today — are you free?']],
    'pricing'      => ['label'=>'Pricing','emoji'=>'💰','messages'=>['What is your rate per hour?','Can you give me a quote for the work?','Do you offer any discounts for long jobs?','Is the price negotiable?']],
    'job'          => ['label'=>'Job Details','emoji'=>'🔧','messages'=>['The work is at my home address.','It\'s a small repair job, shouldn\'t take long.','Can you bring your own tools and materials?','I\'ll send you the exact address once confirmed.','The job might take around 2–3 hours.']],
    'booking'      => ['label'=>'Booking','emoji'=>'✅','messages'=>['I\'d like to book you for this job.','Let\'s confirm the booking — are we set?','Can you confirm your availability for my booking?','I\'ve placed the booking — please check it.']],
    'followup'     => ['label'=>'Follow-up','emoji'=>'🔔','messages'=>['Just checking — are you on your way?','How much longer will the job take?','Great work, thank you so much!','I\'ll leave a 5-star review for you!','When can you come for the follow-up visit?']],
];
$roleMessages = [
    'Electrician'   => ['Can you fix a power outage in my house?','I need new wiring installed in a room.','The circuit breaker keeps tripping — can you check?'],
    'Plumber'       => ['There\'s a leaking pipe under the sink.','My bathroom drain is completely blocked.','I need a new water heater installed.'],
    'Carpenter'     => ['I need custom shelves built in my bedroom.','Can you fix a broken door frame?','I need a wardrobe assembled.'],
    'Painter'       => ['I need my living room painted.','Can you do texture work on walls?','What paint brands do you use?'],
    'AC Technician' => ['My AC isn\'t cooling properly.','I need an AC gas refill.','Can you service my split AC unit?'],
    'Mechanic'      => ['My car won\'t start — can you check it?','I need an oil change and brake check.','Strange noise coming from the engine.'],
];

$jsQuickData = [];
foreach ($quickReplies as $key => $cat) {
    $jsQuickData[$key] = ['emoji'=>$cat['emoji'],'label'=>$cat['label'],'messages'=>$cat['messages']];
}
if (!empty($workerRole) && isset($roleMessages[$workerRole])) {
    $jsQuickData['role'] = ['emoji'=>'⚡','label'=>'For '.$workerRole,'messages'=>$roleMessages[$workerRole]];
}

// ── SVG ICON FUNCTION (identical to dashboard) ──
function getIcon($name, $size = 20, $class = '') {
    $icons = [
        'grid'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'dashboard'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'user'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'message'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'calendar'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'bookmark'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>',
        'card'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'settings'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'help'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'phone'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'logout'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'search'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'bell'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'moon'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'sun'         => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'menu'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'star'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'check'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'check-dbl'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 9 16 1 9"/><polyline points="22 6 14 21 10 16"/></svg>',
        'x'           => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'workers'     => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'send'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'smile'       => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
        'paperclip'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>',
        'more'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>',
        'back'        => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
        'bubble'      => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="9" y1="14" x2="13" y2="14"/></svg>',
        'lightning'   => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
        'location'    => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'arrow-right' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function avatarUrl($photo, $name, $type = 'worker') {

    if (!empty($photo)) {

        // If full URL
        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return htmlspecialchars($photo);
        }
        // Local images
        if ($type === 'user') {
            return '../assets/images/users/' . htmlspecialchars($photo);
        } else {
            return '../assets/images/workers/' . htmlspecialchars($photo);
        }
    }
    // Default avatar
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=16a34a&color=fff';
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
        /* ── IDENTICAL CSS VARIABLES TO DASHBOARD ── */
        :root {
            --mint-50:#f0fdf7;--mint-100:#dcfce7;--mint-200:#bbf7d0;--mint-300:#86efac;
            --mint-400:#4ade80;--mint-500:#22c55e;--mint-600:#16a34a;
            --teal-50:#f0fdfa;--teal-100:#ccfbf1;--teal-500:#14b8a6;--teal-600:#0d9488;
            --bg:#f8faf9;--bg-secondary:#ffffff;--sidebar-width:250px;
            --primary:var(--mint-600);--primary-hover:#15803d;--primary-light:var(--mint-100);
            --secondary:var(--teal-500);--secondary-hover:#0f766e;
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
            font-family:'Inter',system-ui,-apple-system,sans-serif;
            background-color:var(--bg);display:flex;color:var(--text-primary);
            transition:var(--transition);overflow:hidden;height:100vh;line-height:1.6;
            background-image:radial-gradient(ellipse at top right,rgba(34,197,94,0.06) 0%,transparent 50%),
                             radial-gradient(ellipse at bottom left,rgba(20,184,166,0.08) 0%,transparent 50%);
        }
        svg{display:block;}

        /* ── SIDEBAR — identical to dashboard ── */
        .sidebar{width:var(--sidebar-width);height:100vh;background:var(--bg-secondary);padding:24px 16px;display:flex;flex-direction:column;position:fixed;border-right:1px solid var(--border);z-index:1000;transition:var(--transition);}
        .logo{font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;font-weight:800;margin-bottom:32px;padding-left:14px;letter-spacing:-0.5px;color:var(--text-primary);}
        .logo .x{color:var(--primary);}
        .nav-group{margin-bottom:24px;}
        .nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-gray);margin-bottom:12px;padding-left:14px;font-weight:700;}
        .nav-item{display:flex;align-items:center;padding:11px 14px;text-decoration:none;color:var(--text-secondary);border-radius:10px;margin-bottom:4px;transition:var(--transition);font-weight:500;cursor:pointer;font-size:13px;gap:12px;}
        .nav-item:hover{background:var(--primary-light);color:var(--primary);transform:translateX(4px);}
        .nav-item.active{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;box-shadow:0 4px 15px var(--shadow-lg);}
        .nav-item svg{width:18px;height:18px;}
        .badge{background:var(--danger);color:white;font-size:9px;padding:2px 6px;border-radius:6px;margin-left:auto;font-weight:700;}
        .signout-container{margin-top:auto;padding-top:16px;border-top:1px solid var(--border);}
        .signout-btn{display:flex;align-items:center;gap:12px;padding:11px 14px;width:100%;text-decoration:none;color:var(--danger);background:#fef2f2;border-radius:10px;font-weight:600;font-size:13px;transition:var(--transition);justify-content:flex-start;}
        [data-theme="dark"] .signout-btn{background:rgba(239,68,68,0.15);}
        .signout-btn:hover{background:var(--danger);color:white;transform:translateX(4px);}
        .signout-btn svg{width:18px;height:18px;}

        /* ── MAIN CONTENT ── */
        .main-content{margin-left:var(--sidebar-width);flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden;transition:var(--transition);}

        /* ── HEADER — identical sizing/layout to dashboard ── */
        header{
            min-height:70px;display:flex;align-items:center;justify-content:space-between;
            gap:18px;flex-wrap:wrap;padding:0 32px;
            border-bottom:1px solid var(--border);background:var(--bg-secondary);flex-shrink:0;
        }
        .header-left{display:flex;align-items:center;gap:13px;flex:1;min-width:270px;}
        .mobile-toggle{display:none;background:var(--bg-secondary);border:1px solid var(--border);cursor:pointer;color:var(--text-primary);padding:10px 12px;border-radius:10px;transition:var(--transition);}
        .mobile-toggle:hover{background:var(--primary-light);border-color:var(--primary);}
        .page-heading{font-family:'Plus Jakarta Sans',sans-serif;font-size:23px;font-weight:700;color:var(--text-primary);}
        .header-actions{display:flex;align-items:center;gap:11px;}
        .icon-btn{background:var(--bg-secondary);border:1px solid var(--border);border-radius:11px;width:42px;height:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);position:relative;}
        .icon-btn:hover{background:var(--primary);color:white;border-color:var(--primary);transform:translateY(-2px);}
        .icon-btn .notification-dot{position:absolute;top:8px;right:8px;width:8px;height:8px;background:var(--danger);border-radius:50%;border:2px solid var(--bg-secondary);}
        .user-pill{display:flex;align-items:center;gap:10px;background:var(--bg-secondary);padding:5px 15px 5px 5px;border-radius:30px;border:1px solid var(--border);cursor:pointer;transition:var(--transition);text-decoration:none;}
        .user-pill:hover{border-color:var(--primary);box-shadow:0 4px 14px var(--shadow);}
        .avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;overflow:hidden;flex-shrink:0;}
        .avatar img{width:100%;height:100%;object-fit:cover;}
        .user-name{font-size:13px;font-weight:600;color:var(--text-primary);font-family:'Plus Jakarta Sans',sans-serif;}
        .theme-toggle{background:var(--bg-secondary);border:1px solid var(--border);border-radius:11px;padding:10px 13px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-secondary);transition:var(--transition);font-weight:500;}
        .theme-toggle:hover{border-color:var(--primary);background:var(--primary-light);}

        /* ── CHAT LAYOUT ── */
        .chat-layout{display:flex;flex:1;overflow:hidden;}

        /* ── CONVERSATION LIST ── */
        .conv-panel{width:300px;flex-shrink:0;border-right:1px solid var(--border);display:flex;flex-direction:column;background:var(--bg-secondary);overflow:hidden;}
        .conv-header{padding:16px 18px 12px;border-bottom:1px solid var(--border);flex-shrink:0;}
        .conv-title{font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;margin-bottom:12px;color:var(--text-primary);}
        .conv-search{display:flex;align-items:center;gap:9px;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:9px 13px;transition:var(--transition);}
        .conv-search:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px rgba(22,163,74,0.1);}
        .conv-search input{border:none;outline:none;background:transparent;color:var(--text-primary);font-size:12px;width:100%;font-family:'Inter',sans-serif;}
        .conv-search input::placeholder{color:var(--text-gray);}
        .conv-search svg{width:14px;height:14px;color:var(--text-gray);flex-shrink:0;}
        .conv-list{flex:1;overflow-y:auto;padding:8px;}
        .conv-list::-webkit-scrollbar{width:4px;}
        .conv-list::-webkit-scrollbar-thumb{background:var(--mint-300);border-radius:2px;}
        .conv-item{display:flex;align-items:center;gap:12px;padding:12px 11px;border-radius:12px;cursor:pointer;transition:var(--transition);text-decoration:none;}
        .conv-item:hover{background:var(--primary-light);}
        .conv-item.active{background:linear-gradient(135deg,rgba(34,197,94,0.12),rgba(20,184,166,0.08));border:1px solid rgba(34,197,94,0.2);}
        .conv-item.active .conv-name{color:var(--primary);}
        .conv-avatar{position:relative;flex-shrink:0;}
        .conv-avatar img{width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
        .conv-item.active .conv-avatar img{border-color:var(--primary);}
        .online-dot{position:absolute;bottom:1px;right:1px;width:11px;height:11px;background:var(--success);border-radius:50%;border:2px solid var(--bg-secondary);}
        .conv-info{flex:1;min-width:0;}
        .conv-name{font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .conv-preview{font-size:11px;color:var(--text-gray);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;}
        .conv-meta{display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0;}
        .conv-time{font-size:10px;color:var(--text-gray);}
        .unread-badge{background:var(--primary);color:white;font-size:9px;font-weight:700;padding:2px 6px;border-radius:8px;min-width:18px;text-align:center;}
        .no-conversations{text-align:center;padding:40px 20px;color:var(--text-gray);}
        .no-conversations svg{margin:0 auto 12px;opacity:0.4;}
        .no-conversations p{font-size:13px;}

        /* ── CHAT WINDOW ── */
        .chat-window{flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--bg);}
        .chat-header{height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 22px;border-bottom:1px solid var(--border);background:var(--bg-secondary);flex-shrink:0;}
        .chat-partner{display:flex;align-items:center;gap:12px;}
        .chat-partner-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);}
        .chat-partner-name{font-size:14px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary);}
        .chat-partner-status{font-size:11px;color:var(--success);font-weight:500;margin-top:1px;}
        .chat-partner-role{font-size:10px;color:var(--text-gray);margin-top:1px;}
        .chat-actions{display:flex;gap:8px;}
        .chat-action-btn{background:var(--bg);border:1px solid var(--border);border-radius:9px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);color:var(--text-secondary);}
        .chat-action-btn:hover{border-color:var(--primary);color:var(--primary);}
        .chat-action-btn svg{width:15px;height:15px;}
        .back-btn{display:none;background:var(--bg);border:1px solid var(--border);border-radius:9px;width:36px;height:36px;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);color:var(--text-secondary);margin-right:4px;}
        .back-btn:hover{border-color:var(--primary);color:var(--primary);}

        /* Messages */
        .messages-area{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:10px;scroll-behavior:smooth;}
        .messages-area::-webkit-scrollbar{width:5px;}
        .messages-area::-webkit-scrollbar-thumb{background:var(--mint-300);border-radius:3px;}
        .date-divider{display:flex;align-items:center;gap:12px;margin:8px 0;}
        .date-divider::before,.date-divider::after{content:'';flex:1;height:1px;background:var(--border);}
        .date-divider span{font-size:11px;color:var(--text-gray);font-weight:600;white-space:nowrap;padding:3px 10px;background:var(--bg);border:1px solid var(--border);border-radius:20px;}
        .msg-row{display:flex;gap:10px;max-width:72%;animation:msgIn 0.25s ease;}
        @keyframes msgIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
        .msg-row.sent{align-self:flex-end;flex-direction:row-reverse;margin:auto;}
        .msg-row.received{align-self:flex-start;}
        .msg-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;align-self:flex-end;border:2px solid var(--border);}
        .msg-bubble{padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.55;word-break:break-word;}
        .msg-row.received .msg-bubble{background:var(--bg-secondary);border:1px solid var(--border);border-bottom-left-radius:4px;color:var(--text-primary);}
        .msg-row.sent .msg-bubble{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;border-bottom-right-radius:4px;}
        .msg-meta{display:flex;align-items:center;gap:5px;margin-top:4px;}
        .msg-time{font-size:10px;color:var(--text-gray);}
        .msg-row.sent .msg-time{color:rgba(255,255,255,0.7);}
        .msg-status svg{width:12px;height:12px;color:rgba(255,255,255,0.7);}
        .typing-indicator{display:flex;align-items:center;gap:10px;padding:4px 0;}
        .typing-dots{display:flex;gap:4px;padding:10px 14px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;border-bottom-left-radius:4px;}
        .typing-dots span{width:7px;height:7px;background:var(--text-gray);border-radius:50%;animation:bounce 1.2s infinite;}
        .typing-dots span:nth-child(2){animation-delay:0.2s;}
        .typing-dots span:nth-child(3){animation-delay:0.4s;}
        @keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}

        /* Quick replies */
        .quick-panel{border-top:1px solid var(--border);background:var(--bg-secondary);flex-shrink:0;max-height:0;overflow:hidden;transition:max-height 0.35s cubic-bezier(0.4,0,0.2,1);}
        .quick-panel.open{max-height:260px;}
        .quick-panel-inner{padding:14px 18px 12px;}
        .quick-tabs{display:flex;gap:6px;overflow-x:auto;padding-bottom:10px;scrollbar-width:none;}
        .quick-tabs::-webkit-scrollbar{display:none;}
        .quick-tab{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:20px;font-size:11px;font-weight:600;background:var(--bg);border:1px solid var(--border);cursor:pointer;white-space:nowrap;transition:var(--transition);color:var(--text-secondary);flex-shrink:0;}
        .quick-tab:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light);}
        .quick-tab.active{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;border-color:var(--mint-500);box-shadow:0 3px 10px var(--shadow-lg);}
        .quick-messages{display:flex;flex-wrap:wrap;gap:7px;padding:2px 0 4px;max-height:120px;overflow-y:auto;scrollbar-width:thin;}
        .quick-messages::-webkit-scrollbar{width:3px;}
        .quick-messages::-webkit-scrollbar-thumb{background:var(--mint-300);border-radius:2px;}
        .quick-msg-btn{display:inline-flex;align-items:center;padding:7px 14px;border-radius:18px;font-size:12px;font-weight:500;background:var(--bg);border:1px solid var(--border);cursor:pointer;transition:var(--transition);color:var(--text-secondary);text-align:left;line-height:1.4;animation:chipIn 0.18s ease both;}
        @keyframes chipIn{from{opacity:0;transform:scale(0.88);}to{opacity:1;transform:scale(1);}}
        .quick-msg-btn:hover{background:var(--primary-light);border-color:var(--primary);color:var(--primary);transform:translateY(-1px);box-shadow:0 3px 10px var(--shadow);}
        .quick-msg-btn:active{transform:scale(0.97);}
        .quick-msg-btn.flash{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;border-color:var(--mint-500);}

        /* Input area */
        .chat-input-area{border-top:1px solid var(--border);background:var(--bg-secondary);flex-shrink:0;}
        .quick-toggle-bar{padding:8px 18px 0;display:flex;align-items:center;gap:8px;}
        .quick-toggle-btn{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:16px;font-size:11px;font-weight:600;background:var(--bg);border:1px solid var(--border);cursor:pointer;color:var(--text-secondary);transition:var(--transition);}
        .quick-toggle-btn:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light);}
        .quick-toggle-btn.active{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;border-color:var(--mint-500);}
        .quick-toggle-btn svg{width:12px;height:12px;}
        .quick-hint{font-size:11px;color:var(--text-gray);}
        .input-row{display:flex;align-items:flex-end;gap:10px;background:var(--bg);border:1px solid var(--border);border-radius:16px;padding:10px 12px;transition:var(--transition);margin:8px 18px 14px;}
        .input-row:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px rgba(22,163,74,0.12);}
        .input-side-btn{background:none;border:none;cursor:pointer;color:var(--text-gray);padding:4px;border-radius:7px;transition:var(--transition);display:flex;align-items:center;}
        .input-side-btn:hover{color:var(--primary);background:var(--primary-light);}
        .input-side-btn svg{width:18px;height:18px;}
        #msgInput{flex:1;border:none;outline:none;background:transparent;color:var(--text-primary);font-size:13px;font-family:'Inter',sans-serif;resize:none;line-height:1.5;max-height:120px;overflow-y:auto;scrollbar-width:none;}
        #msgInput::placeholder{color:var(--text-gray);}
        #msgInput::-webkit-scrollbar{display:none;}
        .send-btn{width:38px;height:38px;border-radius:11px;border:none;background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--transition);flex-shrink:0;}
        .send-btn:hover{transform:scale(1.08);box-shadow:0 4px 14px var(--shadow-lg);}
        .send-btn:disabled{opacity:0.4;cursor:not-allowed;transform:none;}
        .send-btn svg{width:16px;height:16px;}

        /* No selection */
        .no-chat-selected{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;color:var(--text-gray);}
        .no-chat-selected .big-icon{width:80px;height:80px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;color:var(--primary);}
        .no-chat-selected .big-icon svg{width:36px;height:36px;}
        .no-chat-selected h3{font-size:18px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-secondary);}
        .no-chat-selected p{font-size:13px;text-align:center;max-width:260px;}

        /* Toast — identical to dashboard */
        .toast{position:fixed;bottom:26px;right:26px;background:var(--bg-secondary);border:1px solid var(--border);border-left:5px solid var(--success);padding:15px 20px;border-radius:12px;box-shadow:0 14px 45px var(--shadow-lg);transform:translateX(150%);transition:var(--transition);z-index:2000;display:flex;align-items:center;gap:12px;min-width:280px;}
        .toast.show{transform:translateX(0);}
        .toast.error{border-left-color:var(--danger);}
        .toast-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .toast.success .toast-icon{background:rgba(34,197,94,0.15);color:var(--success);}
        .toast.error .toast-icon{background:rgba(239,68,68,0.15);color:var(--danger);}
        .toast-icon svg{width:17px;height:17px;}
        .toast-content{flex:1;}
        .toast-title{font-weight:600;color:var(--text-primary);font-size:13px;}
        .toast-message{font-size:12px;color:var(--text-gray);margin-top:2px;}

        /* Overlay — identical to dashboard */
        .overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(13,20,17,0.65);backdrop-filter:blur(4px);z-index:999;opacity:0;transition:var(--transition);}
        .overlay.active{display:block;opacity:1;}

        /* ── RESPONSIVE — identical breakpoints to dashboard ── */
        @media(max-width:1024px){ header{padding:0 26px;} }
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);}
            .sidebar.active{transform:translateX(0);}
            .main-content{margin-left:0;}
            header{padding:0 18px;flex-wrap:nowrap;}
            .mobile-toggle{display:flex;}
            .conv-panel{width:100%;position:absolute;inset:70px 0 0 0;z-index:50;border-right:none;}
            .conv-panel.hidden{display:none;}
            .back-btn{display:flex;}
            .user-name{display:none;}
            .toast{left:18px;right:18px;bottom:18px;min-width:auto;}
            .quick-panel.open{max-height:200px;}
        }
        @media(max-width:480px){
            .user-name{display:none;}
            .theme-toggle span:last-child{display:none;}
            .messages-area{padding:14px;}
            .msg-row{max-width:88%;}
            .input-row{margin:8px 12px 12px;}
            .quick-toggle-bar{padding:8px 12px 0;}
            .quick-hint{display:none;}
        }
        ::-webkit-scrollbar{width:6px;}
        ::-webkit-scrollbar-track{background:var(--bg);}
        ::-webkit-scrollbar-thumb{background:var(--mint-300);border-radius:3px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--mint-500);}
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ── SIDEBAR — exact copy from dashboard ── -->
<aside class="sidebar" id="sidebar">
    <div class="logo">Hire<span class="x">X</span></div>
    <nav>
        <div class="nav-group">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php" class="nav-item"><?php echo getIcon('dashboard',18); ?> Dashboard</a>
            <a href="profile.php" class="nav-item"><?php echo getIcon('user',18); ?> My Profile</a>
            <a href="messages.php" class="nav-item active">
                <?php echo getIcon('message',18); ?> Messages
                <?php if($totalUnread > 0): ?><span class="badge"><?php echo $totalUnread; ?></span><?php endif; ?>
            </a>
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

<!-- ── MAIN ── -->
<main class="main-content" id="mainContent">

    <!-- HEADER — exact same structure as dashboard -->
    <header>
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle Menu">
                <?php echo getIcon('menu',20); ?>
            </button>
            <span class="page-heading">Messages</span>
        </div>
        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle Dark Mode">
                <span id="themeIcon"><?php echo getIcon('moon',16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            <button class="icon-btn" aria-label="Notifications">
                <?php echo getIcon('bell',18); ?>
                <span class="notification-dot"></span>
            </button>
            <a href="profile.php" class="user-pill" aria-label="User Profile">
                <div class="avatar">
                    <?php if ($userPhoto): ?>
                        <img src="<?php echo $userPhoto; ?>" alt="<?php echo $userName; ?>">
                    <?php else: ?>
                        <?php echo $userInitial; ?>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?php echo $userName; ?></span>
            </a>
        </div>
    </header>

    <!-- CHAT LAYOUT -->
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
                        <?php echo getIcon('bubble',36); ?>
                        <p>No conversations yet.<br>Book a worker to start chatting!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($conversations as $conv):
                        $isActive = $conv['id'] == $active_id;
                        $convAvatar = avatarUrl($conv['photo'],$conv['name']);
                        $preview = $conv['last_msg']
                            ? htmlspecialchars(mb_substr($conv['last_msg'],0,38)).(mb_strlen($conv['last_msg'])>38?'…':'')
                            : 'No messages yet';
                        $timeAgo='';
                        if($conv['last_time']){
                            $diff=time()-strtotime($conv['last_time']);
                            if($diff<60) $timeAgo='now';
                            elseif($diff<3600) $timeAgo=floor($diff/60).'m';
                            elseif($diff<86400) $timeAgo=floor($diff/3600).'h';
                            else $timeAgo=date('M j',strtotime($conv['last_time']));
                        }
                    ?>
                    <a href="?with=<?php echo $conv['id']; ?>"
                       class="conv-item <?php echo $isActive?'active':''; ?>"
                       data-name="<?php echo strtolower(htmlspecialchars($conv['name'])); ?>"
                       onclick="selectConv(event,<?php echo $conv['id']; ?>)">
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
                            <?php if($conv['unread']>0): ?><span class="unread-badge"><?php echo $conv['unread']; ?></span><?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="chat-window" id="chatWindow">

            <?php if ($activePartner): ?>

            <div class="chat-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <button class="back-btn" onclick="showConvPanel()"><?php echo getIcon('back',16); ?></button>
                    <div class="chat-partner">
                        <img src="<?php echo avatarUrl($activePartner['photo'],$activePartner['name']); ?>"
                             alt="<?php echo htmlspecialchars($activePartner['name']); ?>"
                             class="chat-partner-avatar">
                        <div>
                            <div class="chat-partner-name"><?php echo htmlspecialchars($activePartner['name']); ?></div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="chat-partner-status">● Online</div>
                                <?php if($workerRole): ?><div class="chat-partner-role">· <?php echo htmlspecialchars($workerRole); ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="chat-action-btn" title="Call"><?php echo getIcon('phone',15); ?></button>
                    <button class="chat-action-btn" title="More"><?php echo getIcon('more',15); ?></button>
                </div>
            </div>

            <div class="messages-area" id="messagesArea">
                <div id="msgContainer"></div>
                <div class="typing-indicator" id="typingIndicator" style="display:none;">
                    <img src="<?php echo avatarUrl($activePartner['photo'],$activePartner['name']); ?>" class="msg-avatar" alt="">
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
            </div>

            <!-- Quick Replies -->
            <div class="quick-panel" id="quickPanel">
                <div class="quick-panel-inner">
                    <div class="quick-tabs" id="quickTabs">
                        <?php $first=true; foreach($quickReplies as $key=>$cat): ?>
                        <button class="quick-tab <?php echo $first?'active':''; ?>"
                                onclick="switchTab('<?php echo $key; ?>')"
                                data-tab="<?php echo $key; ?>">
                            <?php echo $cat['emoji']; ?> <?php echo $cat['label']; ?>
                        </button>
                        <?php $first=false; endforeach; ?>
                        <?php if (!empty($workerRole) && isset($roleMessages[$workerRole])): ?>
                        <button class="quick-tab" onclick="switchTab('role')" data-tab="role">
                            ⚡ For <?php echo htmlspecialchars($workerRole); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="quick-messages" id="quickMessages"></div>
                </div>
            </div>

            <!-- Input Bar -->
            <div class="chat-input-area">
                <div class="quick-toggle-bar">
                    <button class="quick-toggle-btn" id="quickToggleBtn" onclick="toggleQuickPanel()">
                        <?php echo getIcon('lightning',12); ?> Quick Replies
                    </button>
                    <span class="quick-hint">Tap a suggestion to fill the input</span>
                </div>
                <div class="input-row">
                    <button class="input-side-btn" title="Emoji"><?php echo getIcon('smile',18); ?></button>
                    <textarea id="msgInput" placeholder="Type a message…" rows="1"
                        onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
                    <button class="input-side-btn" title="Attach"><?php echo getIcon('paperclip',18); ?></button>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()" title="Send">
                        <?php echo getIcon('send',16); ?>
                    </button>
                </div>
            </div>

            <?php else: ?>
            <div class="no-chat-selected">
                <div class="big-icon"><?php echo getIcon('message',36); ?></div>
                <h3>Your Messages</h3>
                <p>Select a conversation from the list to start chatting with a worker.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<!-- Toast — identical to dashboard -->
<div class="toast" id="toast">
    <div class="toast-icon" id="toastIconBox"><?php echo getIcon('check',17); ?></div>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Success</div>
        <div class="toast-message" id="toastMessage">Action completed!</div>
    </div>
</div>

<script>
const CURRENT_USER_ID   = <?php echo $user_id; ?>;
const ACTIVE_PARTNER_ID = <?php echo $active_id ?: 0; ?>;
const QUICK_DATA        = <?php echo json_encode($jsQuickData, JSON_UNESCAPED_UNICODE); ?>;

let lastMessageId  = 0;
let pollTimer      = null;
let activeTab      = Object.keys(QUICK_DATA)[0] || '';
let quickPanelOpen = false;

document.addEventListener('DOMContentLoaded', () => {
    applyTheme();
    if (ACTIVE_PARTNER_ID) { loadMessages(true, true); startPolling(); }
    if (activeTab) renderQuickMessages(activeTab);
});

/* ── QUICK REPLIES ── */
function toggleQuickPanel() {
    quickPanelOpen = !quickPanelOpen;
    document.getElementById('quickPanel').classList.toggle('open', quickPanelOpen);
    document.getElementById('quickToggleBtn').classList.toggle('active', quickPanelOpen);
}
function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.quick-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    renderQuickMessages(tab);
}
function renderQuickMessages(tab) {
    const container = document.getElementById('quickMessages');
    const cat = QUICK_DATA[tab];
    if (!cat) { container.innerHTML = ''; return; }
    container.innerHTML = '';
    cat.messages.forEach((msg, i) => {
        const btn = document.createElement('button');
        btn.className = 'quick-msg-btn';
        btn.style.animationDelay = (i * 0.05) + 's';
        btn.textContent = msg;
        btn.onclick = () => useQuickMessage(msg, btn);
        container.appendChild(btn);
    });
}
function useQuickMessage(msg, btn) {
    const input = document.getElementById('msgInput');
    input.value = msg;
    autoResize(input);
    input.focus();
    input.setSelectionRange(msg.length, msg.length);
    btn.classList.add('flash');
    setTimeout(() => btn.classList.remove('flash'), 500);
    if (window.innerWidth <= 768) {
        quickPanelOpen = false;
        document.getElementById('quickPanel').classList.remove('open');
        document.getElementById('quickToggleBtn').classList.remove('active');
    }
}

/* ── MESSAGES ── */
function loadMessages(scrollToBottom = false, initialLoad = false) {
    if (!ACTIVE_PARTNER_ID) return;
    const url = initialLoad
        ? `ajax/get_messages.php?with=${ACTIVE_PARTNER_ID}`
        : `ajax/get_messages.php?with=${ACTIVE_PARTNER_ID}&after=${lastMessageId}`;
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                if (initialLoad) document.getElementById('msgContainer').innerHTML = '';
                data.messages.forEach(msg => appendMessage(msg));
                lastMessageId = data.messages[data.messages.length - 1].id;
                if (scrollToBottom) scrollDown();
            }
        }).catch(console.error);
}

function appendMessage(msg) {
    const container = document.getElementById('msgContainer');
    if (container.querySelector(`[data-id='${msg.id}']`)) return; // deduplicate
    const isSent = parseInt(msg.sender_id) === CURRENT_USER_ID && msg.sender_type === 'user';
    const msgDate = msg.created_at.split(' ')[0];
    const lastDiv = container.querySelector('[data-date]:last-of-type');
    if (!lastDiv || lastDiv.dataset.date !== msgDate) {
        const d = document.createElement('div');
        d.className = 'date-divider'; d.dataset.date = msgDate;
        d.innerHTML = `<span>${formatDate(msgDate)}</span>`;
        container.appendChild(d);
    }
    const row = document.createElement('div');
    row.className = `msg-row ${isSent ? 'sent' : 'received'}`;
    row.dataset.id = msg.id;
    const avatarSrc = isSent
        ? '<?php echo avatarUrl($user["photo"] ?? "", $userName, "user"); ?>'
        : '<?php echo $activePartner ? avatarUrl($activePartner["photo"] ?? "", htmlspecialchars($activePartner["name"]), "worker") : ""; ?>';
    row.innerHTML = `
        <img src="${avatarSrc}" class="msg-avatar" alt="">
        <div>
            <div class="msg-bubble">${escHtml(msg.message)}</div>
            <div class="msg-meta" style="justify-content:${isSent?'flex-end':'flex-start'}">
                <span class="msg-time">${formatTime(msg.created_at)}</span>
                ${isSent ? `<span class="msg-status"><?php echo getIcon('check-dbl',12); ?></span>` : ''}
            </div>
        </div>`;
    container.appendChild(row);
    scrollDown();
}

function sendMessage() {
    const input = document.getElementById('msgInput');
    const text = input.value.trim();
    if (!text || !ACTIVE_PARTNER_ID) return;
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    input.value = ''; autoResize(input);
    const fd = new FormData();
    fd.append('receiver_id', ACTIVE_PARTNER_ID);
    fd.append('message', text);
    fetch('ajax/send_messages.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => { if (data.success) { appendMessage(data.message); scrollDown(); } })
        .catch(console.error)
        .finally(() => { btn.disabled = false; input.focus(); });
}

function startPolling() {
    pollTimer = setInterval(() => loadMessages(false, false), 3000);
}

/* ── HELPERS (identical to dashboard) ── */
function handleKey(e) { if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } }
function autoResize(el) { el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,120)+'px'; }
function scrollDown() { const a=document.getElementById('messagesArea'); if(a) a.scrollTop=a.scrollHeight; }
function filterConvs(q) { document.querySelectorAll('.conv-item').forEach(i=>{i.style.display=i.dataset.name.includes(q.toLowerCase())?'':'none';}); }
function selectConv(e,id) { e.preventDefault(); window.location.href=`?with=${id}`; }
function showConvPanel() { document.getElementById('convPanel').classList.remove('hidden'); }
function escHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function formatTime(dt) { const d=new Date(dt.replace(' ','T')); return d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}); }
function formatDate(ds) {
    const today=new Date(); today.setHours(0,0,0,0);
    const yest=new Date(today); yest.setDate(yest.getDate()-1);
    const d=new Date(ds);
    if(d.toDateString()===today.toDateString()) return 'Today';
    if(d.toDateString()===yest.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([],{month:'short',day:'numeric',year:'numeric'});
}

/* ── SIDEBAR + THEME (identical to dashboard) ── */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
}
function applyTheme() {
    if(localStorage.getItem('theme')==='dark'){
        document.documentElement.setAttribute('data-theme','dark');
        document.getElementById('themeIcon').innerHTML='<?php echo addslashes(getIcon("sun",16)); ?>';
        document.getElementById('themeText').textContent='Light';
    }
}
function toggleTheme() {
    const html=document.documentElement, isDark=html.getAttribute('data-theme')==='dark';
    if(isDark){html.removeAttribute('data-theme');document.getElementById('themeIcon').innerHTML='<?php echo addslashes(getIcon("moon",16)); ?>';document.getElementById('themeText').textContent='Dark';localStorage.setItem('theme','light');}
    else{html.setAttribute('data-theme','dark');document.getElementById('themeIcon').innerHTML='<?php echo addslashes(getIcon("sun",16)); ?>';document.getElementById('themeText').textContent='Light';localStorage.setItem('theme','dark');}
}
function showToast(title, message, success = true) {
    const toast = document.getElementById('toast');
    document.getElementById('toastIconBox').innerHTML = success ? '<?php echo addslashes(getIcon("check",17)); ?>' : '<?php echo addslashes(getIcon("x",17)); ?>';
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMessage').textContent = message;
    toast.className = 'toast' + (success ? ' success' : ' error') + ' show';
    setTimeout(() => toast.classList.remove('show'), 3000);
}
window.addEventListener('beforeunload',()=>{if(pollTimer)clearInterval(pollTimer);});
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