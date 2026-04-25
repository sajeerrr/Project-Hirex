<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id=(int)$_SESSION['worker_id'];
$stmt=$conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

// Rating summary
$avgRating=(float)($conn->query("SELECT ROUND(COALESCE(AVG(rating),0),1) as r FROM reviews WHERE worker_id=$worker_id")->fetch_assoc()['r']??0);
$totalReviews=(int)($conn->query("SELECT COUNT(*) as c FROM reviews WHERE worker_id=$worker_id")->fetch_assoc()['c']??0);
$ratingDist=[];
for ($s=5;$s>=1;$s--) {
    $cnt=(int)($conn->query("SELECT COUNT(*) as c FROM reviews WHERE worker_id=$worker_id AND rating=$s")->fetch_assoc()['c']??0);
    $ratingDist[$s]=$cnt;
}

$filter=$_GET['filter']??'all';
$where="r.worker_id=$worker_id";
if ($filter==='5') $where.=" AND r.rating=5";
if ($filter==='4') $where.=" AND r.rating=4";
if ($filter==='3') $where.=" AND r.rating<=3";
if ($filter==='replied') $where.=" AND r.reply IS NOT NULL";
if ($filter==='pending') $where.=" AND (r.reply IS NULL OR r.reply='')";

// Handle reply
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['review_id'])) {
    $rid=(int)$_POST['review_id'];
    $reply=trim($_POST['reply_text']??'');
    $conn->query("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS reply TEXT AFTER comment");
    $conn->query("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS replied_at TIMESTAMP NULL AFTER reply");
    if ($reply) {
        $u=$conn->prepare("UPDATE reviews SET reply=?,replied_at=NOW() WHERE id=? AND worker_id=?");
        $u->bind_param('sii',$reply,$rid,$worker_id); $u->execute(); $u->close();
    }
    header('Location: reviews.php?saved=1'); exit;
}

$reviews=[];
$r=$conn->query("SELECT r.*,u.name as user_name,u.photo as user_photo FROM reviews r LEFT JOIN users u ON r.user_id=u.id WHERE $where ORDER BY r.created_at DESC");
if ($r) while ($row=$r->fetch_assoc()) $reviews[]=$row;

$pageTitle='My Reviews'; $pageSubtitle='See what clients say about your work.';
include('../includes/worker-page-start.php');
?>

<!-- RATING SUMMARY -->
<div class="card-box" style="margin-bottom:22px">
    <div style="display:grid;grid-template-columns:200px 1fr;gap:32px;align-items:center">
        <div style="text-align:center">
            <div style="font-size:56px;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary);line-height:1"><?php echo number_format($avgRating,1);?></div>
            <div style="display:flex;justify-content:center;gap:3px;margin:8px 0">
                <?php for($s=1;$s<=5;$s++) echo '<span style="color:'.($s<=$avgRating?'#f59e0b':'var(--border)').'">★</span>';?>
            </div>
            <div style="font-size:13px;color:var(--text-gray)"><?php echo $totalReviews;?> reviews</div>
        </div>
        <div>
            <?php for($s=5;$s>=1;$s--):
                $pct=$totalReviews>0?round($ratingDist[$s]/$totalReviews*100):0;?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="font-size:12px;color:var(--text-secondary);width:12px"><?php echo $s;?></span>
                <span style="color:#f59e0b">★</span>
                <div style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden"><div style="height:100%;background:#f59e0b;width:<?php echo $pct;?>%;border-radius:4px;transition:width .5s"></div></div>
                <span style="font-size:12px;color:var(--text-gray);width:40px"><?php echo $ratingDist[$s];?></span>
            </div>
            <?php endfor;?>
        </div>
    </div>
</div>

<!-- FILTER -->
<div class="filter-bar">
<?php foreach([['all','All Reviews'],['5','5 Stars'],['4','4 Stars'],['3','≤ 3 Stars'],['pending','Awaiting Reply'],['replied','Replied']] as [$v,$l]):?>
<a href="?filter=<?php echo $v;?>" class="chip <?php echo $filter===$v?'active':'';?>"><?php echo $l;?></a>
<?php endforeach;?>
</div>

<!-- REVIEW CARDS -->
<?php if(empty($reviews)):?>
<div class="card-box"><div class="empty-state"><?php echo wGetIcon('star',40);?><p>No reviews to show</p></div></div>
<?php else:?>
<div style="display:flex;flex-direction:column;gap:16px">
<?php foreach($reviews as $rv):
    $up=$rv['user_photo']??'';
    $ini=strtoupper(substr($rv['user_name']??'U',0,1));
?>
<div class="card-box" style="margin-bottom:0">
    <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px">
        <div class="booking-avatar" style="width:44px;height:44px;flex-shrink:0">
            <?php if($up):?><img src="<?php echo filter_var($up,FILTER_VALIDATE_URL)?$up:'../assets/images/users/'.$up;?>" alt=""><?php else:echo $ini;endif;?>
        </div>
        <div style="flex:1">
            <div style="font-size:14px;font-weight:700;color:var(--text-primary)"><?php echo wE($rv['user_name']??'Anonymous');?></div>
            <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <div><?php for($s=1;$s<=5;$s++) echo '<span style="color:'.($s<=$rv['rating']?'#f59e0b':'var(--border)').'">★</span>';?></div>
                <span style="font-size:11px;color:var(--text-gray)"><?php echo wTimeAgo($rv['created_at']);?></span>
            </div>
        </div>
        <span style="font-size:20px;font-weight:800;color:#f59e0b"><?php echo $rv['rating'];?>/5</span>
    </div>
    <?php if($rv['comment']):?>
    <p style="font-size:13px;color:var(--text-secondary);line-height:1.7;margin-bottom:14px">"<?php echo wE($rv['comment']);?>"</p>
    <?php endif;?>

    <!-- REPLY -->
    <?php if(!empty($rv['reply'])):?>
    <div style="background:var(--primary-light);border-radius:10px;padding:12px 14px;border-left:3px solid var(--primary)">
        <div style="font-size:11px;font-weight:700;color:var(--primary);margin-bottom:4px">Your Reply <?php echo !empty($rv['replied_at'])?'· '.date('M d',strtotime($rv['replied_at'])):'';?></div>
        <p style="font-size:13px;color:var(--text-secondary)"><?php echo wE($rv['reply']);?></p>
    </div>
    <?php else:?>
    <div id="replyBox-<?php echo $rv['id'];?>" style="display:none">
        <form method="POST" style="margin-top:12px">
            <input type="hidden" name="review_id" value="<?php echo $rv['id'];?>">
            <textarea class="form-textarea" name="reply_text" rows="2" placeholder="Write a public reply..." style="min-height:70px"></textarea>
            <div style="display:flex;gap:8px;margin-top:8px">
                <button type="submit" class="btn-primary" style="padding:9px 18px"><?php echo wGetIcon('check',14);?> Post Reply</button>
                <button type="button" class="btn-secondary" style="padding:9px 18px" onclick="document.getElementById('replyBox-<?php echo $rv['id'];?>').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
    <button class="btn-sm" style="margin-top:10px" onclick="document.getElementById('replyBox-<?php echo $rv['id'];?>').style.display='block'">Reply to Review</button>
    <?php endif;?>
</div>
<?php endforeach;?>
</div>
<?php endif;?>
<?php include('../includes/worker-page-end.php');?>
