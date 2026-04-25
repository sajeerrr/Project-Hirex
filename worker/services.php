<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id=(int)$_SESSION['worker_id'];
$stmt=$conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

$success=''; $error='';
$editService=null;
if (isset($_GET['edit'])) { $eid=(int)$_GET['edit']; $r=$conn->query("SELECT * FROM worker_services WHERE id=$eid AND worker_id=$worker_id"); if($r) $editService=$r->fetch_assoc(); }

// Handle add/update service
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    $a=$_POST['action'];
    if ($a==='save') {
        $sname=trim($_POST['service_name']??'');
        $desc =trim($_POST['description']??'');
        $price=(float)($_POST['price']??0);
        $dur  =(float)($_POST['duration']??1);
        $active=(int)isset($_POST['is_active']);
        $sid  =(int)($_POST['service_id']??0);
        if (!$sname) { $error='Service name required.'; }
        elseif ($sid) {
            $u=$conn->prepare("UPDATE worker_services SET service_name=?,description=?,price=?,duration_hours=?,is_active=? WHERE id=? AND worker_id=?");
            $u->bind_param('ssddiid',$sname,$desc,$price,$dur,$active,$sid,$worker_id); $u->execute(); $u->close();
            $success='Service updated!';
        } else {
            $ins=$conn->prepare("INSERT INTO worker_services(worker_id,service_name,description,price,duration_hours,is_active) VALUES(?,?,?,?,?,?)");
            $ins->bind_param('issddi',$worker_id,$sname,$desc,$price,$dur,$active); $ins->execute(); $ins->close();
            $success='Service added!';
        }
        $editService=null;
    }
    if ($a==='delete') {
        $sid=(int)($_POST['service_id']??0);
        $conn->prepare("DELETE FROM worker_services WHERE id=? AND worker_id=?")->execute() || true;
        $d=$conn->prepare("DELETE FROM worker_services WHERE id=? AND worker_id=?"); $d->bind_param('ii',$sid,$worker_id); $d->execute(); $d->close();
        header('Location: services.php?saved=1'); exit;
    }
    if ($a==='toggle') {
        $sid=(int)($_POST['service_id']??0); $val=(int)$_POST['val']??0;
        $u=$conn->prepare("UPDATE worker_services SET is_active=? WHERE id=? AND worker_id=?"); $u->bind_param('iii',$val,$sid,$worker_id); $u->execute(); $u->close();
        header('Location: services.php?saved=1'); exit;
    }
}

// Fetch services
$services=[];
$r=$conn->query("SELECT * FROM worker_services WHERE worker_id=$worker_id ORDER BY created_at DESC");
if ($r) while ($row=$r->fetch_assoc()) $services[]=$row;

$pageTitle='My Services'; $pageSubtitle='Add, edit or remove your service offerings.';
include('../includes/worker-page-start.php');
?>
<div style="display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start">
<!-- ADD/EDIT FORM -->
<div class="card-box" style="position:sticky;top:24px">
    <div class="card-box-header"><span class="box-title"><?php echo $editService?wGetIcon('wrench',16).' Edit Service':wGetIcon('wrench',16).' Add Service';?></span><?php if($editService):?><a href="services.php" class="box-link">Cancel</a><?php endif;?></div>
    <?php if($success):?><div class="alert alert-success"><?php echo wGetIcon('check',16);?> <?php echo wE($success);?></div><?php endif;?>
    <?php if($error):?><div class="alert alert-error"><?php echo wGetIcon('x',16);?> <?php echo wE($error);?></div><?php endif;?>
    <form method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="service_id" value="<?php echo $editService?$editService['id']:0;?>">
        <div class="form-group"><label class="form-label">Service Name *</label><input type="text" class="form-input" name="service_name" value="<?php echo wE($editService['service_name']??'');?>" required></div>
        <div class="form-group"><label class="form-label">Description (max 200 chars)</label><textarea class="form-textarea" name="description" rows="3" maxlength="200"><?php echo wE($editService['description']??'');?></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group"><label class="form-label">Price/hr (₹)</label><input type="number" class="form-input" name="price" step="0.01" min="0" value="<?php echo $editService['price']??'';?>"></div>
            <div class="form-group"><label class="form-label">Duration (hrs)</label><input type="number" class="form-input" name="duration" step="0.5" min="0.5" value="<?php echo $editService['duration_hours']??'1';?>"></div>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:10px">
            <input type="checkbox" name="is_active" id="chkActive" <?php echo ($editService['is_active']??1)?'checked':'';?> style="width:16px;height:16px;accent-color:var(--primary)">
            <label for="chkActive" style="font-size:13px;font-weight:500;color:var(--text-primary)">Active (visible to clients)</label>
        </div>
        <button type="submit" class="btn-primary" style="width:100%"><?php echo wGetIcon('check',16);?> <?php echo $editService?'Update Service':'Add Service';?></button>
    </form>
</div>

<!-- SERVICES LIST -->
<div>
    <?php if(empty($services)):?>
    <div class="card-box"><div class="empty-state"><?php echo wGetIcon('wrench',40);?><p>No services yet. Add your first service!</p></div></div>
    <?php else:?>
    <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach($services as $svc):?>
    <div class="card-box" style="margin-bottom:0">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                    <span style="font-size:15px;font-weight:700;color:var(--text-primary)"><?php echo wE($svc['service_name']);?></span>
                    <?php echo $svc['is_active']?'<span style="background:rgba(34,197,94,.1);color:var(--mint-600);padding:3px 10px;border-radius:12px;font-size:10px;font-weight:700">Active</span>':'<span style="background:rgba(239,68,68,.1);color:var(--danger);padding:3px 10px;border-radius:12px;font-size:10px;font-weight:700">Inactive</span>';?>
                </div>
                <?php if($svc['description']):?><p style="font-size:12px;color:var(--text-gray);margin-bottom:8px"><?php echo wE($svc['description']);?></p><?php endif;?>
                <div style="display:flex;gap:16px">
                    <span style="font-size:13px;font-weight:600;color:var(--primary)">₹<?php echo number_format($svc['price'],0);?>/hr</span>
                    <span style="font-size:12px;color:var(--text-gray)"><?php echo wGetIcon('clock',13);?> <?php echo $svc['duration_hours'];?> hr<?php echo $svc['duration_hours']!=1?'s':'';?></span>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                <a href="services.php?edit=<?php echo $svc['id'];?>" class="btn-sm"><?php echo wGetIcon('eye',14);?> Edit</a>
                <form method="POST" style="margin:0">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="service_id" value="<?php echo $svc['id'];?>">
                    <input type="hidden" name="val" value="<?php echo $svc['is_active']?0:1;?>">
                    <button type="submit" class="btn-sm"><?php echo $svc['is_active']?'Deactivate':'Activate';?></button>
                </form>
                <form method="POST" style="margin:0" onsubmit="return confirm('Delete this service?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="service_id" value="<?php echo $svc['id'];?>">
                    <button type="submit" class="btn-sm" style="color:var(--danger);border-color:rgba(239,68,68,.3)"><?php echo wGetIcon('x',14);?></button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach;?>
    </div>
    <?php endif;?>
</div>
</div>
<?php include('../includes/worker-page-end.php');?>
