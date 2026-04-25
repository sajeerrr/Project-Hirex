<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id=(int)$_SESSION['worker_id'];
$stmt=$conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

$pageTitle='Help Center'; $pageSubtitle='Find answers to common questions.';
include('../includes/worker-page-start.php');

$faqs=[
    ['How do I accept a job request?','Go to Job Requests, find the pending request, and click Accept. The client will be notified immediately.'],
    ['How do I get paid?','After completing a job, earnings (minus the 10% platform fee) are credited to your wallet. You can withdraw from the Withdraw page.'],
    ['What is the minimum withdrawal amount?','The minimum withdrawal amount is ₹200. Withdrawals are processed within 2–3 business days.'],
    ['How can I improve my rating?','Complete jobs on time, communicate clearly with clients, and go the extra mile. Happy clients leave great reviews!'],
    ['How do I set my availability?','Go to Availability and toggle per-day on/off. You can also set working hours for each day of the week.'],
    ['Can I reject a job request?','Yes. On any pending request, click Decline. The client will be notified that you\'re unavailable.'],
    ['How do I update my services?','Go to My Services to add, edit, or remove service offerings with your pricing.'],
    ['I received a bad review, what can I do?','You can reply to any review publicly to address the client\'s concern. Reviews cannot be deleted.'],
];
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
<?php foreach($faqs as $i=>[$q,$a]):?>
<div class="card-box" style="margin-bottom:0">
    <div style="display:flex;align-items:flex-start;gap:12px;cursor:pointer" onclick="wFaqToggle(<?php echo $i;?>)">
        <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0"><?php echo $i+1;?></div>
        <div style="flex:1">
            <div style="font-size:14px;font-weight:700;color:var(--text-primary)"><?php echo wE($q);?></div>
            <div id="faq-<?php echo $i;?>" style="display:none;font-size:13px;color:var(--text-secondary);margin-top:8px;line-height:1.6"><?php echo wE($a);?></div>
        </div>
        <span id="faq-ico-<?php echo $i;?>" style="color:var(--primary);font-size:18px;flex-shrink:0">+</span>
    </div>
</div>
<?php endforeach;?>
</div>

<!-- CONTACT CARD -->
<div class="card-box" style="text-align:center;padding:36px 28px">
    <?php echo wGetIcon('phone',40);?>
    <h3 style="font-size:18px;font-weight:700;margin:14px 0 6px;font-family:'Plus Jakarta Sans',sans-serif">Still need help?</h3>
    <p style="font-size:13px;color:var(--text-gray);margin-bottom:20px">Contact our support team — we respond within 24 hours.</p>
    <a href="contact.php" class="btn-primary"><?php echo wGetIcon('phone',16);?> Contact Support</a>
</div>

<?php
$extraJS="
function wFaqToggle(i){
    const el=document.getElementById('faq-'+i);
    const ico=document.getElementById('faq-ico-'+i);
    const open=el.style.display==='block';
    el.style.display=open?'none':'block';
    ico.textContent=open?'+':'−';
}
";
include('../includes/worker-page-end.php');?>
