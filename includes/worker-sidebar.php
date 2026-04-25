<?php
// worker-sidebar.php — must be included AFTER session_start() and worker auth
global $conn, $worker_id, $worker;

$currentPage = basename($_SERVER['PHP_SELF']);

// Badge counts
$wid = (int)$worker_id;
$unreadMsgs  = (int)($conn->query("SELECT COUNT(*) as c FROM messages WHERE receiver_id=$wid AND receiver_type='worker' AND is_read=0")->fetch_assoc()['c'] ?? 0);
$pendingJobs = (int)($conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$wid AND status='pending'")->fetch_assoc()['c'] ?? 0);

$workerName    = wE($worker['name'] ?? 'Worker');
$workerInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $worker['name'] ?? 'W'), 0, 1)) ?: 'W';
$workerRole    = wE($worker['role'] ?? 'Worker');

$navItems = [
    'Main Menu' => [
        ['dashboard.php', 'Dashboard',   'dashboard'],
        ['profile.php',   'My Profile',  'user'],
        ['bookings.php',  'My Bookings', 'calendar'],
        ['messages.php',  'Messages',    'message', $unreadMsgs],
    ],
    'Work' => [
        ['job_requests.php', 'Job Requests',  'briefcase', $pendingJobs],
        ['services.php',     'My Services',   'wrench'],
        ['availability.php', 'Availability',  'clock'],
    ],
    
    'Reviews' => [
        ['reviews.php', 'My Reviews', 'star'],
    ],
    'Support' => [
        ['help.php',    'Help Center',    'help'],
        ['contact.php', 'Contact Admin',  'phone'],
    ],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="logo">
        Hire<span class="x">X</span>
        <span class="worker-badge">Worker</span>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $group => $items): ?>
        <div class="nav-group">
            <div class="nav-label"><?php echo wE($group); ?></div>
            <?php foreach ($items as $item): ?>
            <?php
                $file   = $item[0];
                $label  = $item[1];
                $icon   = $item[2];
                $badge  = isset($item[3]) && $item[3] > 0 ? $item[3] : 0;
                $active = $currentPage === $file ? ' active' : '';
            ?>
            <a href="<?php echo $file; ?>" class="nav-item<?php echo $active; ?>">
                <?php echo wGetIcon($icon, 18); ?>
                <span><?php echo $label; ?></span>
                <?php if ($badge): ?>
                <span class="nav-badge"><?php echo $badge; ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="signout-container">
        <a href="logout.php" class="signout-btn">
            <?php echo wGetIcon('logout', 18); ?> Sign Out
        </a>
    </div>
</aside>
