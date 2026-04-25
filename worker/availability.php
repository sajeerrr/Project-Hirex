<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id=(int)$_SESSION['worker_id'];
$stmt=$conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }



// Handle availability toggle
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_avail'])) {
    $av=(int)$_POST['toggle_avail'];
    $u=$conn->prepare("UPDATE workers SET available=? WHERE id=?"); $u->bind_param('ii',$av,$worker_id); $u->execute(); $u->close();
    $worker['available']=$av;
    header('Location: availability.php?avail=1'); exit;
}

$days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$times=[];
for ($h=6;$h<=22;$h++) { $times[]=sprintf('%02d:00',$h); $times[]=sprintf('%02d:30',$h); }

// Handle save schedule
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_schedule'])) {
    foreach ($days as $day) {
        $key=strtolower($day);
        $avail=(int)isset($_POST["avail_$key"]);
        $start=$_POST["start_$key"]??'09:00';
        $end  =$_POST["end_$key"]??'17:00';
        $conn->query("INSERT INTO worker_availability(worker_id,day_of_week,start_time,end_time,is_available) VALUES($worker_id,'$day','$start','$end',$avail) ON DUPLICATE KEY UPDATE start_time='$start',end_time='$end',is_available=$avail");
    }
    header('Location: availability.php?saved=1'); exit;
}

// Fetch existing schedule
$schedule=[];
$r=$conn->query("SELECT * FROM worker_availability WHERE worker_id=$worker_id");
if ($r) while ($row=$r->fetch_assoc()) $schedule[$row['day_of_week']]=$row;

$pageTitle='Availability'; $pageSubtitle='Set your working hours and availability status.';
include('../includes/worker-page-start.php');
?>

<!-- AVAILABILITY TOGGLE -->
<div class="avail-card <?php echo $worker['available']?'avail-on':'avail-off';?>" style="margin-bottom:24px">
    <div class="avail-info">
        <div class="avail-status-dot"></div>
        <div>
            <div class="avail-title">Right now you are: <strong><?php echo $worker['available']?'AVAILABLE':'BUSY';?></strong></div>
            <div class="avail-sub"><?php echo $worker['available']?'Clients can see and book you.':'You are hidden from search results.';?></div>
        </div>
    </div>
    <form method="POST" style="margin:0">
        <input type="hidden" name="toggle_avail" value="<?php echo $worker['available']?0:1;?>">
        <button type="submit" class="avail-btn"><?php echo $worker['available']?'Go Busy':'Go Available';?></button>
    </form>
</div>

<!-- WEEKLY SCHEDULE -->
<div class="card-box">
    <div class="card-box-header"><span class="box-title"><?php echo wGetIcon('clock',16);?> Weekly Schedule</span></div>
    <form method="POST">
        <input type="hidden" name="save_schedule" value="1">
        <div style="display:flex;flex-direction:column;gap:0">
        <?php foreach ($days as $day):
            $key=strtolower($day);
            $sc=$schedule[$day]??null;
            $isAvail=$sc?$sc['is_available']:1;
            $startT=$sc?$sc['start_time']:'09:00';
            $endT=$sc?$sc['end_time']:'17:00';
        ?>
        <div style="display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--border)">
            <div style="width:90px;font-size:13px;font-weight:600;color:var(--text-primary)"><?php echo $day;?></div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <div class="ts-wrap">
                    <input type="checkbox" name="avail_<?php echo $key;?>" <?php echo $isAvail?'checked':'';?> onchange="wToggleDay('<?php echo $key;?>',this.checked)" style="display:none" id="chk_<?php echo $key;?>">
                    <div class="ts-track <?php echo $isAvail?'ts-on':'';?>" onclick="document.getElementById('chk_<?php echo $key;?>').click();this.classList.toggle('ts-on')"><div class="ts-thumb"></div></div>
                </div>
                <span style="font-size:12px;color:var(--text-secondary)" id="lbl_<?php echo $key;?>"><?php echo $isAvail?'Available':'Day Off';?></span>
            </label>
            <div id="times_<?php echo $key;?>" style="display:flex;align-items:center;gap:10px;<?php echo $isAvail?'':'display:none';?>">
                <select name="start_<?php echo $key;?>" class="form-select" style="width:120px;padding:7px 10px">
                    <?php foreach($times as $t): ?><option value="<?php echo $t;?>" <?php echo substr($startT,0,5)===$t?'selected':'';?>><?php echo $t;?></option><?php endforeach;?>
                </select>
                <span style="font-size:12px;color:var(--text-gray)">to</span>
                <select name="end_<?php echo $key;?>" class="form-select" style="width:120px;padding:7px 10px">
                    <?php foreach($times as $t): ?><option value="<?php echo $t;?>" <?php echo substr($endT,0,5)===$t?'selected':'';?>><?php echo $t;?></option><?php endforeach;?>
                </select>
            </div>
        </div>
        <?php endforeach;?>
        </div>
        <div style="margin-top:20px"><button type="submit" class="btn-primary"><?php echo wGetIcon('check',16);?> Save All Schedule</button></div>
    </form>
</div>

<?php
$extraCSS='.ts-wrap{display:inline-block}.ts-track{width:44px;height:24px;background:var(--border);border-radius:12px;position:relative;cursor:pointer;transition:var(--transition)}.ts-track.ts-on{background:linear-gradient(135deg,var(--mint-500),var(--mint-600))}.ts-thumb{position:absolute;top:2px;left:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:var(--transition);box-shadow:0 2px 4px rgba(0,0,0,.2)}.ts-track.ts-on .ts-thumb{left:22px}';
$extraJS="
function wToggleDay(key,on){
    const t=document.getElementById('times_'+key);
    const l=document.getElementById('lbl_'+key);
    if(t){if(on){t.style.display='flex';}else{t.style.display='none';}}
    if(l) l.textContent=on?'Available':'Day Off';
}
document.querySelectorAll('[id^=times_]').forEach(el=>{
    const key=el.id.replace('times_','');
    const chk=document.getElementById('chk_'+key);
    if(chk&&!chk.checked) el.style.display='none';
});
";
include('../includes/worker-page-end.php');?>
