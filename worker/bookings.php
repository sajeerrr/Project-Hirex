<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id = (int)$_SESSION['worker_id'];
$stmt = $conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

// Handle status filter
$status = $_GET['status'] ?? 'all';
$validStatuses = ['all','pending','confirmed','in_progress','completed','cancelled'];
if (!in_array($status, $validStatuses)) $status = 'all';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE
$where = "b.worker_id=$worker_id";
$params = [];
$types = '';
if ($status !== 'all') { $where .= " AND b.status=?"; $params[] = $status; $types .= 's'; }

// Count
$countSql = "SELECT COUNT(*) as c FROM bookings b WHERE $where";
$countStmt = $conn->prepare($countSql);
if ($params) { $countStmt->bind_param($types, ...$params); }
$countStmt->execute();
$totalRows = (int)$countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch bookings
$sql = "SELECT b.*, u.name as user_name, u.photo as user_photo, u.email as user_email, u.phone as user_phone
    FROM bookings b LEFT JOIN users u ON b.user_id=u.id WHERE $where
    ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset";
$bStmt = $conn->prepare($sql);
if ($params) { $bStmt->bind_param($types, ...$params); }
$bStmt->execute();
$bookings = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$bStmt->close();

// Mini stats
$miniStats = [
    'total'     => (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id")->fetch_assoc()['c'],
    'pending'   => (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status='pending'")->fetch_assoc()['c'],
    'completed' => (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status='completed'")->fetch_assoc()['c'],
    'cancelled' => (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status='cancelled'")->fetch_assoc()['c'],
];

// Handle complete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_id'])) {
    $bid = (int)$_POST['complete_id'];
    $check = $conn->prepare("SELECT id, total_amount FROM bookings WHERE id=? AND worker_id=? AND status='in_progress'");
    $check->bind_param('ii', $bid, $worker_id); $check->execute();
    $brow = $check->get_result()->fetch_assoc(); $check->close();
    if ($brow) {
        $conn->prepare("UPDATE bookings SET status='completed' WHERE id=?")->execute() || true;
        $upd = $conn->prepare("UPDATE bookings SET status='completed' WHERE id=?"); $upd->bind_param('i',$bid); $upd->execute(); $upd->close();
        $gross = (float)$brow['total_amount'];
        $fee   = round($gross * 0.10, 2);
        $net   = $gross - $fee;
        // Insert earnings if table exists
        $conn->query("INSERT IGNORE INTO earnings(worker_id,booking_id,gross_amount,platform_fee,total_amount,status,credited_at)
            VALUES($worker_id,$bid,$gross,$fee,$net,'credited',NOW())");
    }
    header('Location: bookings.php?status='.$status.'&saved=1'); exit;
}

// Handle start_job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_id'])) {
    $bid = (int)$_POST['start_id'];
    $upd = $conn->prepare("UPDATE bookings SET status='in_progress' WHERE id=? AND worker_id=?");
    $upd->bind_param('ii',$bid,$worker_id); $upd->execute(); $upd->close();
    header('Location: bookings.php?status='.$status.'&saved=1'); exit;
}

$pageTitle = 'My Bookings';
$pageSubtitle = "Manage your service appointments and job requests.";
include('../includes/worker-page-start.php');
?>

<!-- MINI STATS -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
<?php foreach ([['Total','total','teal'],['Pending','pending','yellow'],['Completed','completed','green'],['Cancelled','cancelled','purple']] as [$lbl,$key,$clr]): ?>
<div class="stat-card" style="padding:16px">
    <div class="stat-icon <?php echo $clr; ?>" style="width:38px;height:38px"><?php echo wGetIcon('calendar',18); ?></div>
    <div class="stat-info"><h4 style="font-size:20px"><?php echo $miniStats[$key]; ?></h4><p><?php echo $lbl; ?></p></div>
</div>
<?php endforeach; ?>
</div>

<!-- FILTER CHIPS -->
<div class="filter-bar">
<?php foreach ([['all','All'],['pending','Pending'],['confirmed','Confirmed'],['in_progress','In Progress'],['completed','Completed'],['cancelled','Cancelled']] as [$v,$l]): ?>
<a href="?status=<?php echo $v; ?>" class="chip <?php echo $status===$v?'active':''; ?>"><?php echo $l; ?></a>
<?php endforeach; ?>
<span style="margin-left:auto;font-size:12px;color:var(--text-gray)"><?php echo $totalRows; ?> bookings</span>
</div>

<!-- BOOKINGS TABLE -->
<div class="card-box">
    <?php if (empty($bookings)): ?>
    <div class="empty-state"><?php echo wGetIcon('calendar',40); ?><p>No bookings found</p></div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="w-table">
        <thead><tr><th>#</th><th>Client</th><th>Date & Time</th><th>Address</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr>
            <td style="font-weight:600;color:var(--text-primary)">#<?php echo $b['id']; ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="booking-avatar" style="width:34px;height:34px;font-size:12px">
                        <?php $up=$b['user_photo']??''; if($up): ?><img src="<?php echo filter_var($up,FILTER_VALIDATE_URL)?$up:'../assets/images/users/'.$up; ?>" alt=""><?php else: echo strtoupper(substr($b['user_name']??'U',0,1)); endif; ?>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:13px"><?php echo wE($b['user_name']??'—'); ?></div>
                        <div style="font-size:11px;color:var(--text-gray)"><?php echo wE($b['user_phone']??''); ?></div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-size:13px"><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></div>
                <?php if ($b['booking_time']): ?><div style="font-size:11px;color:var(--text-gray)"><?php echo date('h:i A', strtotime($b['booking_time'])); ?></div><?php endif; ?>
            </td>
            <td style="max-width:150px;font-size:12px;color:var(--text-secondary)"><?php echo wE(mb_substr($b['address']??'—',0,40)); ?></td>
            <td style="font-weight:700;color:var(--primary)"><?php echo wMoney($b['total_amount']??0); ?></td>
            <td><?php echo wStatusBadge($b['status']); ?></td>
            <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <?php if ($b['status']==='pending'): ?>
                    <button class="btn-sm" style="background:var(--mint-500);color:#fff;border-color:var(--mint-500)" onclick="wAcceptJob(<?php echo $b['id']; ?>)">Accept</button>
                    <button class="btn-sm" style="color:var(--danger);border-color:rgba(239,68,68,.3)" onclick="wDeclineJob(<?php echo $b['id']; ?>)">Decline</button>
                    <?php endif; ?>
                    <?php if ($b['status']==='confirmed'): ?>
                    <form method="POST" style="margin:0"><input type="hidden" name="start_id" value="<?php echo $b['id']; ?>"><button type="submit" class="btn-sm" style="background:#3b82f6;color:#fff;border-color:#3b82f6">Start Job</button></form>
                    <?php endif; ?>
                    <?php if ($b['status']==='in_progress'): ?>
                    <form method="POST" style="margin:0" onsubmit="return confirm('Mark this job as completed?')"><input type="hidden" name="complete_id" value="<?php echo $b['id']; ?>"><button type="submit" class="btn-sm" style="background:var(--mint-600);color:#fff;border-color:var(--mint-600)">Complete</button></form>
                    <?php endif; ?>
                    <a href="messages.php?user_id=<?php echo $b['user_id']; ?>" class="btn-sm"><?php echo wGetIcon('message',12); ?></a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:6px;margin-top:18px;padding-top:16px;border-top:1px solid var(--border)">
        <?php for ($p=1;$p<=$totalPages;$p++): ?>
        <a href="?status=<?php echo $status; ?>&page=<?php echo $p; ?>" style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;text-decoration:none;border:1px solid var(--border);<?php echo $p===$page?'background:var(--primary);color:#fff;border-color:var(--primary)':'color:var(--text-secondary);background:var(--bg-secondary)'; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include('../includes/worker-page-end.php'); ?>
