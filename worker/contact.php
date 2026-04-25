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
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $subject=trim($_POST['subject']??'');
    $message=trim($_POST['message']??'');
    $category=$_POST['category']??'general';
    if (!$subject||!$message) { $error='All fields required.'; }
    else {
        // contacts table has FK: user_id -> users(id). Use complaints table instead (no FK on user_id)
        $subj='[WORKER #'.$worker_id.'] '.$subject;
        $stmt2=$conn->prepare("INSERT INTO complaints(user_id,worker_id,subject,message,status,priority) VALUES(1,?,?,?,'pending','medium')");
        $stmt2->bind_param('iss',$worker_id,$subj,$message);
        if ($stmt2->execute()) { $success='Message sent! We\'ll respond within 24 hours.'; }
        else { $error='Failed to send. Please try again.'; }
        $stmt2->close();
    }
}

$pageTitle='Contact Admin'; $pageSubtitle='Get help from our support team.';
include('../includes/worker-page-start.php');
?>
<div style="max-width:640px;margin:0 auto">
    <?php if($success):?><div class="alert alert-success"><?php echo wGetIcon('check',18);?> <?php echo wE($success);?></div><?php endif;?>
    <?php if($error):?><div class="alert alert-error"><?php echo wGetIcon('x',18);?> <?php echo wE($error);?></div><?php endif;?>

    <div class="card-box">
        <div class="card-box-header"><span class="box-title"><?php echo wGetIcon('phone',16);?> Contact Support</span></div>
        <form method="POST">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group"><label class="form-label">Your Name</label><input type="text" class="form-input" value="<?php echo wE($worker['name']??'');?>" disabled></div>
                <div class="form-group"><label class="form-label">Your Email</label><input type="email" class="form-input" value="<?php echo wE($worker['email']??'');?>" disabled></div>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select class="form-select" name="category">
                    <option value="booking">Booking Problem</option>
                    <option value="account">Account Issue</option>
                    <option value="technical">Technical Problem</option>
                    <option value="general" selected>General Enquiry</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Subject</label><input type="text" class="form-input" name="subject" required placeholder="Brief description of your issue"></div>
            <div class="form-group"><label class="form-label">Message</label><textarea class="form-textarea" name="message" rows="5" required placeholder="Describe your issue in detail..."></textarea></div>
            <button type="submit" class="btn-primary"><?php echo wGetIcon('phone',16);?> Send Message</button>
        </form>
    </div>

    <div class="card-box" style="margin-top:0">
        <div class="card-box-header"><span class="box-title"><?php echo wGetIcon('clock',16);?> Support Hours</span></div>
        <div style="display:grid;gap:10px">
            <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:var(--text-secondary)">Mon – Fri</span><span style="font-weight:600;color:var(--text-primary)">9:00 AM – 6:00 PM</span></div>
            <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:var(--text-secondary)">Saturday</span><span style="font-weight:600;color:var(--text-primary)">10:00 AM – 4:00 PM</span></div>
            <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:var(--text-secondary)">Sunday</span><span style="font-weight:600;color:var(--danger)">Closed</span></div>
            <div style="padding-top:10px;border-top:1px solid var(--border);font-size:12px;color:var(--text-gray)">Average response time: <strong>under 4 hours</strong></div>
        </div>
    </div>
</div>
<?php include('../includes/worker-page-end.php');?>
