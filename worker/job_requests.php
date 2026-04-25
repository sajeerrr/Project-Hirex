<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id = (int)$_SESSION['worker_id'];
$stmt = $conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

$filter = $_GET['filter'] ?? 'all';
$where = "b.worker_id=$worker_id AND b.status='pending'";
if ($filter==='today') $where .= " AND DATE(b.booking_date)=CURDATE()";
if ($filter==='week')  $where .= " AND b.booking_date BETWEEN NOW() AND DATE_ADD(NOW(),INTERVAL 7 DAY)";
if ($filter==='high')  $where .= " AND b.total_amount>1000";

$requests = [];
$r = $conn->query("SELECT b.*,u.name as user_name,u.photo as user_photo,u.phone as user_phone FROM bookings b LEFT JOIN users u ON b.user_id=u.id WHERE $where ORDER BY b.created_at DESC");
if ($r) while ($row=$r->fetch_assoc()) $requests[]=$row;

$totalReqs   = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status='pending'")->fetch_assoc()['c'];
$acceptToday = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status IN ('confirmed','in_progress','completed') AND DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$rejectToday = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE worker_id=$worker_id AND status='cancelled' AND DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$total2 = $acceptToday+$rejectToday;
$rate = $total2>0?round($acceptToday/$total2*100):0;

$pageTitle='Job Requests'; $pageSubtitle='Review and respond to incoming job requests.';
include('../includes/worker-page-start.php');
?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
<?php foreach([['Total Pending',$totalReqs,'briefcase','teal'],['Accepted Today',$acceptToday,'check','green'],['Rejected Today',$rejectToday,'x','yellow'],['Acceptance Rate',$rate.'%','star','purple']] as [$l,$v,$ic,$cl]): ?>
<div class="stat-card" style="padding:16px"><div class="stat-icon <?php echo $cl;?>" style="width:38px;height:38px"><?php echo wGetIcon($ic,18);?></div><div class="stat-info"><h4 style="font-size:20px"><?php echo $v;?></h4><p><?php echo $l;?></p></div></div>
<?php endforeach;?>
</div>

<div class="filter-bar">
<?php foreach([['all','All'],['today','Today'],['week','This Week'],['high','High Value']] as [$v,$l]):?>
<a href="?filter=<?php echo $v;?>" class="chip <?php echo $filter===$v?'active':'';?>"><?php echo $l;?></a>
<?php endforeach;?>
</div>

<?php if(empty($requests)):?>
<div class="card-box"><div class="empty-state" style="padding:60px 20px"><?php echo wGetIcon('briefcase',52);?><p style="font-size:14px;margin-top:8px;font-weight:600">No pending requests right now</p><p style="font-size:12px;margin-top:4px">Keep your profile updated to attract more clients!</p></div></div>
<?php else:?>
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px">
<?php foreach($requests as $req):
$hrs=(strtotime($req['booking_date'])-time())/3600;
$urg=$hrs<24?['Urgent','var(--danger)','rgba(239,68,68,.1)']:($hrs<72?['Normal','#f59e0b','rgba(245,158,11,.1)']:['Flexible','var(--mint-600)','rgba(34,197,94,.1)']);
?>
<div class="card-box" id="req-<?php echo $req['id'];?>" style="margin-bottom:0">
    <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px">
        <div class="booking-avatar" style="width:52px;height:52px;font-size:18px;flex-shrink:0">
            <?php $up=$req['user_photo']??'';if($up):?><img src="<?php echo filter_var($up,FILTER_VALIDATE_URL)?$up:'../assets/images/users/'.$up;?>" alt=""><?php else:echo strtoupper(substr($req['user_name']??'U',0,1));endif;?>
        </div>
        <div style="flex:1">
            <div style="font-size:15px;font-weight:700;color:var(--text-primary)"><?php echo wE($req['user_name']??'—');?></div>
            <div style="font-size:12px;color:var(--text-gray);margin-top:2px"><?php echo wE($req['user_phone']??'');?></div>
            <span style="background:<?php echo $urg[2];?>;color:<?php echo $urg[1];?>;padding:3px 10px;border-radius:12px;font-size:10px;font-weight:700;margin-top:6px;display:inline-block"><?php echo $urg[0];?></span>
        </div>
        <div style="text-align:right">
            <div style="font-size:22px;font-weight:800;color:var(--primary);font-family:'Plus Jakarta Sans',sans-serif"><?php echo wMoney($req['total_amount']??0);?></div>
            <div style="font-size:10px;color:var(--text-gray);margin-top:2px"><?php echo wTimeAgo($req['created_at']);?></div>
        </div>
    </div>
    <div style="background:var(--bg);border-radius:10px;padding:12px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px"><?php echo wGetIcon('calendar',14);?><span style="font-size:12px;color:var(--text-secondary)"><?php echo date('D, M d Y',strtotime($req['booking_date']));?><?php if($req['booking_time']):?> · <?php echo date('h:i A',strtotime($req['booking_time']));?><?php endif;?></span></div>
        <?php if($req['address']):?><div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:6px"><?php echo wGetIcon('location',14);?><span style="font-size:12px;color:var(--text-secondary)"><?php echo wE($req['address']);?></span></div><?php endif;?>
        <?php if($req['notes']):?><div style="font-size:12px;color:var(--text-gray);font-style:italic;border-top:1px solid var(--border);padding-top:8px;margin-top:8px">"<?php echo wE(mb_substr($req['notes'],0,100));?>"</div><?php endif;?>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <button onclick="wAcceptJob(<?php echo $req['id'];?>)" class="btn-accept" style="padding:12px"><?php echo wGetIcon('check',16);?> Accept Job</button>
        <button onclick="wDeclineJob(<?php echo $req['id'];?>)" class="btn-decline" style="padding:12px"><?php echo wGetIcon('x',16);?> Decline</button>
    </div>
</div>
<?php endforeach;?>
</div>
<?php endif;?>
<?php include('../includes/worker-page-end.php');?>
