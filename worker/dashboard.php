<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');

if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id = (int)$_SESSION['worker_id'];

$stmt = $conn->prepare('SELECT * FROM workers WHERE id=?');
$stmt->bind_param('i', $worker_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

// Handle availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
    $av = (int)$_POST['toggle_avail'];
    $u = $conn->prepare('UPDATE workers SET available=? WHERE id=?');
    $u->bind_param('ii', $av, $worker_id);
    $u->execute(); $u->close();
    $worker['available'] = $av;
    header('Location: dashboard.php?avail=1'); exit;
}

// Stats
$stats = wGetWorkerStats($conn, $worker_id);

// Fallback: pull from requests table if earnings table is empty
$totalEarned = $stats['earned'];
if ($totalEarned == 0) {
    $rq = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM requests WHERE worker_id=$worker_id AND status='completed'");
    if ($rq) $totalEarned = $rq->fetch_assoc()['t'] ?? 0;
}
$pendingExtra = (int)($conn->query("SELECT COUNT(*) as c FROM requests WHERE worker_id=$worker_id AND status='pending'")->fetch_assoc()['c'] ?? 0);
$doneExtra    = (int)($conn->query("SELECT COUNT(*) as c FROM requests WHERE worker_id=$worker_id AND status='completed'")->fetch_assoc()['c'] ?? 0);
$totalPending   = $stats['pending'] + $pendingExtra;
$totalCompleted = $stats['done'] + $doneExtra;
$avgRating = $stats['rating'] ?: ($worker['rating'] ?? 0);

// Upcoming bookings
$upcoming = [];
$r = $conn->query("SELECT b.*, u.name as user_name, u.photo as user_photo FROM bookings b
    LEFT JOIN users u ON b.user_id=u.id
    WHERE b.worker_id=$worker_id AND b.status IN ('confirmed','in_progress')
    AND DATE(b.booking_date) >= CURDATE() ORDER BY b.booking_date ASC LIMIT 5");
if ($r) while ($row = $r->fetch_assoc()) $upcoming[] = $row;

// Pending booking requests
$pendingBkgs = [];
$r = $conn->query("SELECT b.*, u.name as user_name FROM bookings b
    LEFT JOIN users u ON b.user_id=u.id
    WHERE b.worker_id=$worker_id AND b.status='pending' ORDER BY b.created_at DESC LIMIT 4");
if ($r) while ($row = $r->fetch_assoc()) $pendingBkgs[] = $row;

// Recent activity (requests table)
$recentReqs = [];
$r = $conn->query("SELECT r.*, u.name as user_name FROM requests r LEFT JOIN users u ON r.user_id=u.id WHERE r.worker_id=$worker_id ORDER BY r.created_at DESC LIMIT 5");
if ($r) while ($row = $r->fetch_assoc()) $recentReqs[] = $row;

// Performance
$totalBkgs    = (int)($conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id")->fetch_assoc()['c'] ?? 0);
$doneBkgs     = (int)($conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status='completed'")->fetch_assoc()['c'] ?? 0);
$acceptBkgs   = (int)($conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status IN ('confirmed','in_progress','completed')")->fetch_assoc()['c'] ?? 0);
$cancelBkgs   = (int)($conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status='cancelled'")->fetch_assoc()['c'] ?? 0);
$completionPct = $totalBkgs > 0 ? round($doneBkgs / $totalBkgs * 100) : 0;
$responsePct   = ($acceptBkgs + $cancelBkgs) > 0 ? round($acceptBkgs / ($acceptBkgs + $cancelBkgs) * 100) : 0;
$ratingPct     = min((float)$avgRating * 20, 100);

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . wE($worker['name']) . '! Here\'s your overview.';
include('../includes/worker-page-start.php');
?>

<!-- STATS BAR -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon teal"><?php echo wGetIcon('card',22); ?></div>
        <div class="stat-info"><h4><?php echo wMoney($totalEarned); ?></h4><p>Total Earnings</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><?php echo wGetIcon('check',22); ?></div>
        <div class="stat-info"><h4><?php echo $totalCompleted; ?></h4><p>Jobs Completed</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><?php echo wGetIcon('star',22); ?></div>
        <div class="stat-info"><h4><?php echo number_format($avgRating,1); ?></h4><p>Average Rating</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><?php echo wGetIcon('briefcase',22); ?></div>
        <div class="stat-info"><h4><?php echo $totalPending; ?></h4><p>Pending Requests</p></div>
    </div>
</div>

<!-- AVAILABILITY TOGGLE -->
<div class="avail-card <?php echo $worker['available'] ? 'avail-on' : 'avail-off'; ?>">
    <div class="avail-info">
        <div class="avail-status-dot"></div>
        <div>
            <div class="avail-title">You are currently: <strong><?php echo $worker['available'] ? 'AVAILABLE' : 'BUSY'; ?></strong></div>
            <div class="avail-sub"><?php echo $worker['available'] ? 'Clients can see and book you.' : 'You are hidden from search results.'; ?></div>
        </div>
    </div>
    <form method="POST" style="margin:0">
        <input type="hidden" name="toggle_avail" value="<?php echo $worker['available'] ? 0 : 1; ?>">
        <button type="submit" class="avail-btn"><?php echo $worker['available'] ? 'Set as Busy' : 'Set as Available'; ?></button>
    </form>
</div>

<div class="two-col-grid">
    <!-- UPCOMING BOOKINGS -->
    <div class="card-box">
        <div class="card-box-header">
            <span class="box-title"><?php echo wGetIcon('calendar',16); ?> Upcoming Bookings</span>
            <a href="bookings.php" class="box-link">View all →</a>
        </div>
        <?php if (empty($upcoming)): ?>
        <div class="empty-state"><?php echo wGetIcon('calendar',36); ?><p>No upcoming bookings</p></div>
        <?php else: foreach ($upcoming as $b): ?>
        <div class="booking-row">
            <div class="booking-avatar">
                <?php
                $up = $b['user_photo'] ?? '';
                if ($up): ?><img src="<?php echo filter_var($up, FILTER_VALIDATE_URL) ? $up : '../assets/images/users/'.$up; ?>" alt=""><?php
                else: echo strtoupper(substr($b['user_name'] ?? 'U', 0, 1)); endif; ?>
            </div>
            <div class="booking-info">
                <div class="booking-name"><?php echo wE($b['user_name'] ?? '—'); ?></div>
                <div class="booking-meta"><?php echo wGetIcon('calendar',12); ?> <?php echo date('M d, Y', strtotime($b['booking_date'])); ?><?php if ($b['booking_time']): ?> · <?php echo date('h:i A', strtotime($b['booking_time'])); ?><?php endif; ?></div>
                <?php if ($b['address']): ?><div class="booking-meta"><?php echo wGetIcon('location',12); ?> <?php echo wE(mb_substr($b['address'], 0, 40)); ?>...</div><?php endif; ?>
            </div>
            <div class="booking-right">
                <div class="booking-amount"><?php echo wMoney($b['total_amount'] ?? 0); ?></div>
                <?php echo wStatusBadge($b['status']); ?>
                <a href="bookings.php" class="btn-sm">View</a>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
        <!-- PENDING REQUESTS -->
        <div class="card-box" style="margin-bottom:0">
            <div class="card-box-header">
                <span class="box-title"><?php echo wGetIcon('briefcase',16); ?> Pending Requests</span>
                <a href="job_requests.php" class="box-link">All →</a>
            </div>
            <?php if (empty($pendingBkgs)): ?>
            <div class="empty-state" style="padding:16px"><?php echo wGetIcon('briefcase',28); ?><p style="font-size:12px">No pending requests</p></div>
            <?php else: foreach ($pendingBkgs as $pb): ?>
            <div class="request-card" id="req-<?php echo $pb['id']; ?>">
                <div class="request-top">
                    <div class="request-avatar"><?php echo strtoupper(substr($pb['user_name'] ?? 'U', 0, 1)); ?></div>
                    <div class="request-info">
                        <div class="request-name"><?php echo wE($pb['user_name'] ?? '—'); ?></div>
                        <div class="request-date"><?php echo date('M d, Y', strtotime($pb['booking_date'])); ?></div>
                    </div>
                    <div class="request-amount"><?php echo wMoney($pb['total_amount'] ?? 0); ?></div>
                </div>
                <?php if ($pb['notes']): ?><div class="request-notes"><?php echo wE(mb_substr($pb['notes'], 0, 60)); ?></div><?php endif; ?>
                <div class="request-actions">
                    <button class="btn-accept" onclick="wAcceptJob(<?php echo $pb['id']; ?>)"><?php echo wGetIcon('check',14); ?> Accept</button>
                    <button class="btn-decline" onclick="wDeclineJob(<?php echo $pb['id']; ?>)"><?php echo wGetIcon('x',14); ?> Decline</button>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- PERFORMANCE -->
        <div class="card-box" style="margin-bottom:0">
            <div class="card-box-header"><span class="box-title"><?php echo wGetIcon('activity',16); ?> Performance</span></div>
            <div class="perf-item">
                <div class="perf-label"><span>Completion Rate</span><span class="perf-pct"><?php echo $completionPct; ?>%</span></div>
                <div class="perf-bar"><div class="perf-fill green" style="width:<?php echo $completionPct; ?>%"></div></div>
            </div>
            <div class="perf-item">
                <div class="perf-label"><span>Response Rate</span><span class="perf-pct"><?php echo $responsePct; ?>%</span></div>
                <div class="perf-bar"><div class="perf-fill teal" style="width:<?php echo $responsePct; ?>%"></div></div>
            </div>
            <div class="perf-item">
                <div class="perf-label"><span>Rating Score</span><span class="perf-pct"><?php echo number_format($ratingPct, 0); ?>%</span></div>
                <div class="perf-bar"><div class="perf-fill yellow" style="width:<?php echo $ratingPct; ?>%"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- RECENT ACTIVITY -->
<?php if (!empty($recentReqs)): ?>
<div class="card-box">
    <div class="card-box-header"><span class="box-title"><?php echo wGetIcon('activity',16); ?> Recent Activity</span></div>
    <table class="w-table">
        <thead><tr><th>#</th><th>Client</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recentReqs as $req): ?>
        <tr>
            <td>#<?php echo $req['id']; ?></td>
            <td><?php echo wE($req['user_name'] ?? '—'); ?></td>
            <td><?php echo wMoney($req['amount'] ?? 0); ?></td>
            <td><?php echo wStatusBadge($req['status']); ?></td>
            <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include('../includes/worker-page-end.php'); ?>