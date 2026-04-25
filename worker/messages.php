<?php
session_start();
include('../database/db.php');
include('../includes/worker-functions.php');
if (!isset($_SESSION['worker_id'])) { header('Location: ../login.php'); exit; }
$worker_id=(int)$_SESSION['worker_id'];
$stmt=$conn->prepare('SELECT * FROM workers WHERE id=?'); $stmt->bind_param('i',$worker_id); $stmt->execute();
$worker=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$worker) { session_destroy(); header('Location: ../login.php'); exit; }

$activeUserId=(int)($_GET['user_id']??0);

// Fetch conversation list
$conversations=[];
$r=$conn->query("SELECT DISTINCT CASE WHEN sender_type='user' THEN sender_id ELSE receiver_id END as uid FROM messages WHERE (receiver_id=$worker_id AND receiver_type='worker') OR (sender_id=$worker_id AND sender_type='worker') ORDER BY (SELECT MAX(created_at) FROM messages m2 WHERE (m2.sender_id=uid AND m2.sender_type='user') OR (m2.receiver_id=uid AND m2.receiver_type='user')) DESC LIMIT 30");
if ($r) {
    while ($row=$r->fetch_assoc()) {
        $uid=(int)$row['uid'];
        if (!$uid) continue;
        $u=$conn->query("SELECT id,name,photo,phone FROM users WHERE id=$uid")->fetch_assoc();
        if (!$u) continue;
        $last=$conn->query("SELECT message,created_at FROM messages WHERE ((sender_id=$uid AND sender_type='user' AND receiver_id=$worker_id AND receiver_type='worker') OR (sender_id=$worker_id AND sender_type='worker' AND receiver_id=$uid AND receiver_type='user')) ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
        $unread=(int)$conn->query("SELECT COUNT(*) as c FROM messages WHERE sender_id=$uid AND sender_type='user' AND receiver_id=$worker_id AND receiver_type='worker' AND is_read=0")->fetch_assoc()['c'];
        $conversations[]=['user'=>$u,'last'=>$last,'unread'=>$unread];
    }
}

// Load active chat
$chatMessages=[];
if ($activeUserId) {
    $conn->query("UPDATE messages SET is_read=1 WHERE sender_id=$activeUserId AND sender_type='user' AND receiver_id=$worker_id AND receiver_type='worker'");
    $r=$conn->query("SELECT m.*,u.name,u.photo FROM messages m LEFT JOIN users u ON m.sender_id=u.id AND m.sender_type='user' WHERE ((m.sender_id=$worker_id AND m.sender_type='worker' AND m.receiver_id=$activeUserId AND m.receiver_type='user') OR (m.sender_id=$activeUserId AND m.sender_type='user' AND m.receiver_id=$worker_id AND m.receiver_type='worker')) ORDER BY m.created_at ASC");
    if ($r) while ($row=$r->fetch_assoc()) $chatMessages[]=$row;
    $activeUser=$conn->query("SELECT id,name,photo,phone FROM users WHERE id=$activeUserId")->fetch_assoc();
}

// Send message
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['message'])) {
    $msg=trim($_POST['message']??'');
    $uid=(int)($_POST['user_id']??0);
    if ($msg && $uid) {
        $ins=$conn->prepare("INSERT INTO messages(sender_id,sender_type,receiver_id,receiver_type,message) VALUES(?,?,?,?,?)");
        $st='worker'; $rt='user';
        $ins->bind_param('isiss',$worker_id,$st,$uid,$rt,$msg);
        $ins->execute(); $ins->close();
    }
    header('Location: messages.php?user_id='.$uid); exit;
}

$pageTitle='Messages'; $pageSubtitle='Chat with your clients.';
$extraCSS='
.msg-layout{display:grid;grid-template-columns:300px 1fr;gap:0;height:calc(100vh - 200px);border:1px solid var(--border);border-radius:15px;overflow:hidden;background:var(--bg-secondary)}
.conv-list{border-right:1px solid var(--border);overflow-y:auto}
.conv-item{display:flex;align-items:center;gap:12px;padding:14px 16px;cursor:pointer;transition:background .2s;border-bottom:1px solid var(--border);text-decoration:none}
.conv-item:hover{background:var(--primary-light)}
.conv-item.active{background:var(--primary-light);border-right:3px solid var(--primary)}
.conv-av{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden}
.conv-av img{width:100%;height:100%;object-fit:cover}
.conv-name{font-size:13px;font-weight:600;color:var(--text-primary)}
.conv-last{font-size:11px;color:var(--text-gray);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px}
.unread-badge{background:var(--danger);color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:10px;margin-left:auto;flex-shrink:0}
.chat-area{display:flex;flex-direction:column}
.chat-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-shrink:0}
.chat-msgs{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px}
.bubble{max-width:70%;padding:10px 14px;border-radius:14px;font-size:13px;line-height:1.5}
.bubble.mine{align-self:flex-end;background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:#fff;border-bottom-right-radius:4px}
.bubble.theirs{align-self:flex-start;background:var(--bg);border:1px solid var(--border);color:var(--text-primary);border-bottom-left-radius:4px}
.bubble-time{font-size:10px;opacity:.7;margin-top:4px;text-align:right}
.chat-input{padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:10px;flex-shrink:0}
.chat-input textarea{flex:1;border:1px solid var(--border);border-radius:10px;padding:10px 14px;font-size:13px;font-family:"Inter",sans-serif;resize:none;outline:none;background:var(--bg);color:var(--text-primary);height:44px}
.chat-input textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(22,163,74,.12)}
.no-chat{display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;flex:1;color:var(--text-gray)}
';
include('../includes/worker-page-start.php');
?>

<div class="msg-layout">
    <!-- CONVERSATIONS -->
    <div class="conv-list">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border)"><input type="text" placeholder="Search contacts..." style="width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:12px;outline:none;background:var(--bg);color:var(--text-primary)"></div>
        <?php if(empty($conversations)):?>
        <div style="padding:30px;text-align:center;color:var(--text-gray);font-size:13px">No conversations yet</div>
        <?php else:?>
        <?php foreach($conversations as $cv):
            $u=$cv['user']; $uid=$u['id'];
            $up=$u['photo']??'';
            $ini=strtoupper(substr($u['name']??'U',0,1));
        ?>
        <a href="messages.php?user_id=<?php echo $uid;?>" class="conv-item <?php echo $activeUserId===$uid?'active':'';?>">
            <div class="conv-av"><?php if($up):?><img src="<?php echo filter_var($up,FILTER_VALIDATE_URL)?$up:'../assets/images/users/'.$up;?>" alt=""><?php else:echo $ini;endif;?></div>
            <div style="flex:1;min-width:0">
                <div class="conv-name"><?php echo wE($u['name']??'—');?></div>
                <div class="conv-last"><?php echo wE(mb_substr($cv['last']['message']??'No messages',0,38));?></div>
            </div>
            <?php if($cv['unread']>0):?><span class="unread-badge"><?php echo $cv['unread'];?></span><?php endif;?>
        </a>
        <?php endforeach;?>
        <?php endif;?>
    </div>

    <!-- CHAT WINDOW -->
    <div class="chat-area">
        <?php if($activeUserId && isset($activeUser)): ?>
        <div class="chat-header">
            <div class="conv-av" style="width:40px;height:40px">
                <?php $up=$activeUser['photo']??''; if($up):?><img src="<?php echo filter_var($up,FILTER_VALIDATE_URL)?$up:'../assets/images/users/'.$up;?>" alt=""><?php else:echo strtoupper(substr($activeUser['name']??'U',0,1));endif;?>
            </div>
            <div>
                <div style="font-size:14px;font-weight:700;color:var(--text-primary)"><?php echo wE($activeUser['name']??'');?></div>
                <?php if($activeUser['phone']??''):?><div style="font-size:12px;color:var(--text-gray)"><?php echo wE($activeUser['phone']);?></div><?php endif;?>
            </div>
        </div>
        <div class="chat-msgs" id="chatMsgs">
            <?php foreach($chatMessages as $msg): $mine=$msg['sender_type']==='worker';?>
            <div class="bubble <?php echo $mine?'mine':'theirs';?>">
                <?php echo nl2br(wE($msg['message']));?>
                <div class="bubble-time"><?php echo date('h:i A',strtotime($msg['created_at']));?></div>
            </div>
            <?php endforeach;?>
        </div>
        <div class="chat-input">
            <form method="POST" style="display:flex;gap:10px;flex:1">
                <input type="hidden" name="user_id" value="<?php echo $activeUserId;?>">
                <textarea name="message" placeholder="Type a message..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit()}"></textarea>
                <button type="submit" class="btn-primary" style="padding:0 18px;flex-shrink:0"><?php echo wGetIcon('arrow-right',18);?></button>
            </form>
        </div>
        <?php else:?>
        <div class="no-chat"><?php echo wGetIcon('message',48);?><p>Select a conversation to start chatting</p></div>
        <?php endif;?>
    </div>
</div>

<script>
// Scroll to bottom of chat
const cm=document.getElementById('chatMsgs');
if(cm) cm.scrollTop=cm.scrollHeight;

// Auto-poll for new messages every 5s
<?php if($activeUserId):?>
let lastId=<?php echo !empty($chatMessages)?end($chatMessages)['id']:0;?>;
const activeUserId=<?php echo $activeUserId;?>;
setInterval(()=>{
    fetch('actions/get_messages.php?user_id='+activeUserId+'&last_id='+lastId)
    .then(r=>r.json()).then(msgs=>{
        if(!msgs||!msgs.length)return;
        const c=document.getElementById('chatMsgs');
        msgs.forEach(m=>{
            const d=document.createElement('div');
            d.className='bubble '+(m.mine?'mine':'theirs');
            d.innerHTML=m.message+'<div class="bubble-time">'+m.time+'</div>';
            c.appendChild(d);
            lastId=m.id;
        });
        c.scrollTop=c.scrollHeight;
    }).catch(()=>{});
},5000);
<?php endif;?>
</script>

<?php include('../includes/worker-page-end.php');?>
