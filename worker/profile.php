<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id = (int)$_SESSION['worker_id'];
$stmt = $conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

$success=''; $error='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $tab = $_POST['tab'] ?? 'personal';

    if ($tab==='personal') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');

        if (!$name || !$email) { $error='Name and email are required.'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error='Invalid email format.'; }
        else {
            $photoCol=''; $photoVal=''; $photoType='';
            if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error']===0) {
                $ext=strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) { $error='Invalid image type.'; }
                else {
                    $fname='worker_'.$worker_id.'_'.time().'.'.$ext;
                    $dest='../assets/images/workers/'.$fname;
                    if (!is_dir('../assets/images/workers/')) mkdir('../assets/images/workers/', 0755, true);
                    if (move_uploaded_file($_FILES['photo']['tmp_name'],$dest)) { $photoCol=', photo=?'; $photoVal=$fname; $photoType='s'; }
                }
            }
            if (!$error) {
                if ($photoCol) {
                    $u=$conn->prepare("UPDATE workers SET name=?,email=?,phone=?,location=?,city=?,bio=?$photoCol WHERE id=?");
                    $u->bind_param('ssssss'.$photoType.'i',$name,$email,$phone,$location,$city,$bio,$photoVal,$worker_id);
                } else {
                    $u=$conn->prepare("UPDATE workers SET name=?,email=?,phone=?,location=?,city=?,bio=? WHERE id=?");
                    $u->bind_param('ssssssi',$name,$email,$phone,$location,$city,$bio,$worker_id);
                }
                $u->execute(); $u->close();
                $worker['name']=$name; $worker['email']=$email; $worker['phone']=$phone;
                $worker['location']=$location; $worker['city']=$city; $worker['bio']=$bio;
                if ($photoVal) $worker['photo']=$photoVal;
                $success='Profile updated successfully!';
            }
        }
    }

    if ($tab==='professional') {
        $role  = $_POST['role'] ?? $worker['role'];
        $exp   = trim($_POST['experience'] ?? '');
        $price = (int)($_POST['price'] ?? 0);
        $u=$conn->prepare("UPDATE workers SET role=?,experience=?,price=? WHERE id=?");
        $u->bind_param('ssii',$role,$exp,$price,$worker_id); $u->execute(); $u->close();
        $worker['role']=$role; $worker['experience']=$exp; $worker['price']=$price;
        $success='Professional info updated!';
    }

    if ($tab==='security') {
        $current=trim($_POST['current_pw']??'');
        $newpw  =trim($_POST['new_pw']??'');
        $confirm=trim($_POST['confirm_pw']??'');
        if (!$current||!$newpw||!$confirm) { $error='All fields required.'; }
        elseif ($newpw!==$confirm) { $error='Passwords do not match.'; }
        elseif (strlen($newpw)<6) { $error='Password must be at least 6 characters.'; }
        elseif (!password_verify($current,$worker['password']??'')) { $error='Current password is incorrect.'; }
        else {
            $hash=password_hash($newpw,PASSWORD_DEFAULT);
            $u=$conn->prepare("UPDATE workers SET password=? WHERE id=?"); $u->bind_param('si',$hash,$worker_id); $u->execute(); $u->close();
            $success='Password changed successfully!';
        }
    }
}

$workerPhoto=null;
if (!empty($worker['photo'])) {
    $workerPhoto=filter_var($worker['photo'],FILTER_VALIDATE_URL)?$worker['photo']:'../assets/images/workers/'.$worker['photo'];
}
$initial=strtoupper(substr(preg_replace('/[^a-zA-Z]/','', $worker['name']??'W'),0,1))?:'W';

$totalJobs=$worker['jobs']??0;
$avgRating=$worker['rating']??0;

$pageTitle='My Profile'; $pageSubtitle='Manage your personal and professional information.';
include('../includes/worker-page-start.php');
?>
<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">
<!-- PROFILE CARD -->
<div class="card-box" style="text-align:center;position:sticky;top:24px">
    <div style="position:relative;width:120px;height:120px;margin:0 auto 16px">
        <div style="width:120px;height:120px;border-radius:50%;overflow:hidden;border:4px solid var(--mint-100);background:linear-gradient(135deg,var(--mint-500),var(--teal-500));display:flex;align-items:center;justify-content:center;font-size:42px;font-weight:800;color:#fff">
            <?php if($workerPhoto):?><img src="<?php echo $workerPhoto;?>" alt="" style="width:100%;height:100%;object-fit:cover"><?php else:echo $initial;endif;?>
        </div>
    </div>
    <h3 style="font-size:18px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary)"><?php echo wE($worker['name']??'');?></h3>
    <div style="background:var(--primary-light);color:var(--primary);padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;display:inline-block;margin:6px 0"><?php echo wE($worker['role']??'Worker');?></div>
    <?php $loc=$worker['city']??$worker['location']??''; if($loc):?><div style="font-size:12px;color:var(--text-gray);margin-bottom:8px"><?php echo wGetIcon('location',13);?> <?php echo wE($loc);?></div><?php endif;?>
    <div style="display:flex;justify-content:center;gap:4px;margin:8px 0">
        <?php for($s=1;$s<=5;$s++) echo '<span style="color:'.($s<=$avgRating?'#f59e0b':'var(--border)').'">★</span>';?>
        <span style="font-size:12px;color:var(--text-gray);margin-left:4px">(<?php echo number_format((float)$avgRating,1);?>)</span>
    </div>
    <div style="background:<?php echo $worker['available']?'rgba(34,197,94,.1)':'rgba(239,68,68,.1)';?>;color:<?php echo $worker['available']?'var(--mint-600)':'var(--danger)';?>;padding:5px 16px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;margin-bottom:14px"><?php echo $worker['available']?'● Available':'● Busy';?></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px;border-top:1px solid var(--border);padding-top:16px">
        <div style="text-align:center"><div style="font-size:20px;font-weight:800;color:var(--text-primary);font-family:'Plus Jakarta Sans',sans-serif"><?php echo $totalJobs;?></div><div style="font-size:11px;color:var(--text-gray)">Total Jobs</div></div>
        <div style="text-align:center"><div style="font-size:20px;font-weight:800;color:var(--text-primary);font-family:'Plus Jakarta Sans',sans-serif">₹<?php echo $worker['price']??0;?></div><div style="font-size:11px;color:var(--text-gray)">/hr</div></div>
    </div>
    <div style="font-size:11px;color:var(--text-gray);margin-top:12px">Member since <?php echo date('M Y',strtotime($worker['created_at']??'now'));?></div>
</div>

<!-- EDIT FORM -->
<div class="card-box">
    <?php if($success):?><div class="alert alert-success"><?php echo wGetIcon('check',18);?> <?php echo wE($success);?></div><?php endif;?>
    <?php if($error):?><div class="alert alert-error"><?php echo wGetIcon('x',18);?> <?php echo wE($error);?></div><?php endif;?>

    <div class="tabs">
        <button class="tab-btn active" onclick="wTab(this,'personal')">Personal Info</button>
        <button class="tab-btn" onclick="wTab(this,'professional')">Professional</button>
        <button class="tab-btn" onclick="wTab(this,'security')">Security</button>
    </div>

    <!-- PERSONAL -->
    <div class="tab-panel active" id="tab-personal">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="tab" value="personal">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group"><label class="form-label">Full Name *</label><input type="text" class="form-input" name="name" value="<?php echo wE($worker['name']??'');?>" required></div>
                <div class="form-group"><label class="form-label">Email *</label><input type="email" class="form-input" name="email" value="<?php echo wE($worker['email']??'');?>" required></div>
                <div class="form-group"><label class="form-label">Phone</label><input type="tel" class="form-input" name="phone" value="<?php echo wE($worker['phone']??'');?>"></div>
                <div class="form-group"><label class="form-label">Location</label><input type="text" class="form-input" name="location" value="<?php echo wE($worker['location']??'');?>"></div>
                <div class="form-group"><label class="form-label">City</label><input type="text" class="form-input" name="city" value="<?php echo wE($worker['city']??'');?>"></div>
            </div>
            <div class="form-group"><label class="form-label">Bio</label><textarea class="form-textarea" name="bio" rows="3"><?php echo wE($worker['bio']??'');?></textarea></div>
            <div class="form-group"><label class="form-label">Profile Photo</label><input type="file" class="form-input" name="photo" accept="image/*"></div>
            <button type="submit" class="btn-primary"><?php echo wGetIcon('check',16);?> Save Changes</button>
        </form>
    </div>

    <!-- PROFESSIONAL -->
    <div class="tab-panel" id="tab-professional">
        <form method="POST">
            <input type="hidden" name="tab" value="professional">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group"><label class="form-label">Specialization</label>
                    <select class="form-select" name="role">
                        <?php foreach(['Electrician','Plumber','Carpenter','Painter','AC Technician','Mechanic','Cleaner'] as $r):?>
                        <option value="<?php echo $r;?>" <?php echo ($worker['role']??'')===$r?'selected':'';?>><?php echo $r;?></option>
                        <?php endforeach;?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Experience</label><input type="text" class="form-input" name="experience" placeholder="e.g. 5 yrs" value="<?php echo wE($worker['experience']??'');?>"></div>
                <div class="form-group"><label class="form-label">Hourly Rate (₹)</label><input type="number" class="form-input" name="price" min="0" value="<?php echo (int)($worker['price']??0);?>"></div>
            </div>
            <button type="submit" class="btn-primary"><?php echo wGetIcon('check',16);?> Save Professional Info</button>
        </form>
    </div>

    <!-- SECURITY -->
    <div class="tab-panel" id="tab-security">
        <form method="POST">
            <input type="hidden" name="tab" value="security">
            <div class="form-group"><label class="form-label">Current Password</label><input type="password" class="form-input" name="current_pw" required></div>
            <div class="form-group"><label class="form-label">New Password</label><input type="password" class="form-input" name="new_pw" required minlength="6"></div>
            <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" class="form-input" name="confirm_pw" required></div>
            <button type="submit" class="btn-primary"><?php echo wGetIcon('shield',16);?> Update Password</button>
        </form>
    </div>
</div>
</div>

<?php
$extraJS = "
function wTab(btn,id){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-'+id).classList.add('active');
}
";
include('../includes/worker-page-end.php');?>
