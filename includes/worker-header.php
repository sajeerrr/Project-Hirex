<?php
// worker-header.php — must be included AFTER session_start() and worker auth
global $worker, $worker_id;

$workerName    = wE($worker['name'] ?? 'Worker');
$workerInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $worker['name'] ?? 'W'), 0, 1)) ?: 'W';
$workerPhoto   = null;
if (!empty($worker['photo'])) {
    if (filter_var($worker['photo'], FILTER_VALIDATE_URL)) {
        $workerPhoto = $worker['photo'];
    } else {
        $workerPhoto = '../assets/images/workers/' . $worker['photo'];
    }
}

$wid = (int)$worker_id;
$unread = (int)($conn->query("SELECT COUNT(*) as c FROM messages WHERE receiver_id=$wid AND receiver_type='worker' AND is_read=0")->fetch_assoc()['c'] ?? 0);
?>
<header>
    <div class="header-left">
        <button class="mobile-toggle" onclick="wToggleSidebar()" aria-label="Toggle Menu">
            <?php echo wGetIcon('menu', 20); ?>
        </button>
        <div class="search-bar">
            <?php echo wGetIcon('search', 16); ?>
            <form method="GET" style="flex:1;display:flex;">
                <input type="text" name="q" placeholder="Search bookings, messages..."
                       value="<?php echo wE($_GET['q'] ?? ''); ?>">
            </form>
        </div>
    </div>
    <div class="header-actions">
        <button class="theme-toggle" onclick="wToggleTheme()" aria-label="Toggle theme" id="wThemeBtn">
            <span id="wThemeIcon"><?php echo wGetIcon('moon', 16); ?></span>
            <span id="wThemeText">Dark</span>
        </button>
        <button class="icon-btn" aria-label="Notifications" onclick="window.location='messages.php'">
            <?php echo wGetIcon('bell', 18); ?>
            <?php if ($unread > 0): ?>
            <span class="notification-dot"></span>
            <?php endif; ?>
        </button>
        <div class="user-pill">
            <div class="avatar">
                <?php if ($workerPhoto): ?>
                    <img src="<?php echo $workerPhoto; ?>" alt="<?php echo $workerName; ?>">
                <?php else: ?>
                    <?php echo $workerInitial; ?>
                <?php endif; ?>
            </div>
            <span class="user-name"><?php echo $workerName; ?></span>
        </div>
    </div>
</header>
