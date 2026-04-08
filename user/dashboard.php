<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'user'){
    header("Location:../login.php");
    exit;
}

$userName    = $_SESSION['name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));

/* =============================================
   DB CONNECTION
   ============================================= */
include("../database/db.php");
$userId = $_SESSION['user_id'] ?? 0;

/* =============================================
   STAT COUNTS
   ============================================= */
$total_requests   = 0;
$completed_jobs   = 0;
$pending_requests = 0;
$total_spent      = 0;

$r = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE user_id='$userId'");
if($r) $total_requests = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE user_id='$userId' AND status='completed'");
if($r) $completed_jobs = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE user_id='$userId' AND status='pending'");
if($r) $pending_requests = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT SUM(amount) AS total FROM requests WHERE user_id='$userId' AND status='completed'");
if($r){ $row = $r->fetch_assoc(); $total_spent = $row['total'] ?? 0; }

/* =============================================
   RECENT REQUESTS (last 5)
   ============================================= */
$recent_requests = [];
$r = $conn->query("
    SELECT r.id, w.name AS worker_name, w.category, r.status, r.amount, r.created_at
    FROM   requests r
    LEFT JOIN workers w ON r.worker_id = w.id
    WHERE  r.user_id = '$userId'
    ORDER  BY r.created_at DESC
    LIMIT  5
");
if($r){ while($row = $r->fetch_assoc()) $recent_requests[] = $row; }

/* =============================================
   WORKERS LIST (for display; real app: paginate via DB)
   ============================================= */
$workers = [
    ["Rajan Kumar",   "Electrician", "4.8", "₹400/hr", true],
    ["Suresh Menon",  "Plumber",     "5.0", "₹350/hr", true],
    ["Arjun Pillai",  "Carpenter",   "3.9", "₹500/hr", false],
    ["Vijay Nair",    "Painter",     "4.5", "₹300/hr", true],
    ["Manoj Thomas",  "Technician",  "4.8", "₹450/hr", true],
    ["Priya Sharma",  "Cleaner",     "4.2", "₹250/hr", true],
    ["Deepak Verma",  "Electrician", "4.6", "₹420/hr", false],
    ["Anil Yadav",    "Plumber",     "4.3", "₹370/hr", true],
    ["Ramesh Gupta",  "Mason",       "4.7", "₹480/hr", true],
    ["Sanjay Patil",  "Welder",      "4.9", "₹550/hr", true],
    ["Kiran Reddy",   "Carpenter",   "4.1", "₹490/hr", false],
    ["Mohan Das",     "Painter",     "3.8", "₹280/hr", true],
    ["Vinod Kumar",   "AC Repair",   "4.6", "₹500/hr", true],
    ["Prakash Nair",  "Plumber",     "4.4", "₹360/hr", true],
    ["Sunil Sharma",  "Electrician", "4.7", "₹410/hr", false],
    ["Ajay Singh",    "Carpenter",   "4.0", "₹470/hr", true],
    ["Rohit Mehta",   "Technician",  "4.5", "₹430/hr", true],
    ["Ashok Tiwari",  "Mason",       "4.8", "₹460/hr", true],
    ["Naveen Pillai", "Painter",     "4.3", "₹310/hr", false],
    ["Harish Menon",  "Welder",      "4.6", "₹530/hr", true],
    ["Santosh Rao",   "Cleaner",     "4.1", "₹240/hr", true],
    ["Dinesh Patel",  "Electrician", "3.9", "₹390/hr", false],
    ["Mukesh Joshi",  "AC Repair",   "4.7", "₹520/hr", true],
    ["Rajesh Iyer",   "Plumber",     "4.5", "₹340/hr", true],
    ["Ganesh Kumar",  "Carpenter",   "4.2", "₹460/hr", true],
    ["Biju Thomas",   "Mason",       "4.9", "₹490/hr", true],
    ["Sreejith Nair", "Painter",     "4.4", "₹320/hr", false],
    ["Anoop Varma",   "Technician",  "4.6", "₹440/hr", true],
    ["Jithin Raj",    "Electrician", "4.3", "₹415/hr", true],
    ["Vishnu Das",    "Welder",      "4.8", "₹560/hr", true],
];

/* Pagination */
$per_page = 9;
$total_workers = count($workers);
$total_pages   = ceil($total_workers / $per_page);
$current_page  = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset        = ($current_page - 1) * $per_page;
$paged_workers = array_slice($workers, $offset, $per_page);

/* Today's date */
$today = date('l, j F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard — HireX</title>
<link rel="stylesheet" href="../assets/css/user-dashboard.css">
</head>
<body>

<div class="shell">
  <div class="sb">
    <div class="sb-top">
      <div class="sb-brand">Hire<span>X</span></div>
      <div class="sb-tag">Service Platform</div>
    </div>
    <div class="sb-nav">
      <div class="sb-sec">Main</div>
      <div class="ni active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </div>
      <div class="ni">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
        My Requests
      </div>
      <div class="ni">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Workers
      </div>
      <div class="ni">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        Notifications
      </div>
      <div class="sb-sec">Account</div>
      <div class="ni">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        Profile
      </div>
      <div class="ni">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
        Settings
      </div>
    </div>
    <div class="sb-bot">
      <div class="um">
        <div class="um-av">A</div>
        <div><div class="um-name">Arun Sharma</div><div class="um-role">User Account</div></div>
      </div>
      <div class="lg">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </div>
    </div>
  </div>

  <div class="main">
    <div class="tb">
      <div class="tb-l">
        <h2>User Dashboard</h2>
        <p>Sunday, 15 March 2026 &nbsp;·&nbsp; Good morning, Arun</p>
      </div>
      <div class="tb-r">
        <div class="sw">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          Search workers…
        </div>
        <div class="nb">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#567a18" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <div class="nd"></div>
        </div>
        <div class="av">A</div>
      </div>
    </div>

    <div class="body">

      <!-- WELCOME -->
      <div class="wb">
        <div class="wb-stripe"></div>
        <div class="wb-bg"></div>
        <div style="padding-left:14px;">
          <div class="wb-t">Welcome back, Arun 👋</div>
          <div class="wb-s">You have 3 pending requests awaiting action today.</div>
          <div class="wb-pills">
            <div class="wb-pill"><div class="wb-pill-dot"></div>12 Total Requests</div>
            <div class="wb-pill"><div class="wb-pill-dot"></div>7 Completed</div>
            <div class="wb-pill"><div class="wb-pill-dot"></div>₹4,820 Spent</div>
          </div>
        </div>
        <button class="wb-btn">+ New Request</button>
      </div>

      <!-- NEARBY WORKERS -->
      <div>
        <div class="sh"><div class="sh-title">Nearby Workers</div><span class="sh-link">View all →</span></div>
        <div class="fb">
          <div class="fi">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            Search name or skill…
          </div>
          <select class="fsel"><option>All Categories</option><option>Plumber</option><option>Electrician</option><option>Carpenter</option><option>Painter</option><option>Cleaner</option></select>
          <div class="fc active">All</div>
          <div class="fc">Available</div>
          <div class="fc">Top Rated</div>
        </div>
        <div class="wg">
          <div class="wc">
            <div class="wct"><div class="wav v2">S</div><div class="wi"><h4>Suresh Menon</h4><p>Plumber</p></div><div class="wavl ay">Available</div></div>
            <div class="wm"><div class="rat"><span class="star">★</span> 5.0</div><div class="rate">₹350/hr</div></div>
            <div class="wtgs"><span class="wtg">Plumber</span><span class="wtg g">Top Rated</span></div>
            <div class="wca"><button class="hb">Request</button><button class="vb">Profile</button></div>
          </div>
          <div class="wc">
            <div class="wct"><div class="wav v1">R</div><div class="wi"><h4>Rajan Kumar</h4><p>Electrician</p></div><div class="wavl ay">Available</div></div>
            <div class="wm"><div class="rat"><span class="star">★</span> 4.8</div><div class="rate">₹400/hr</div></div>
            <div class="wtgs"><span class="wtg">Electrician</span><span class="wtg g">Top Rated</span></div>
            <div class="wca"><button class="hb">Request</button><button class="vb">Profile</button></div>
          </div>
          <div class="wc">
            <div class="wct"><div class="wav v3">A</div><div class="wi"><h4>Arjun Pillai</h4><p>Carpenter</p></div><div class="wavl bsy">Busy</div></div>
            <div class="wm"><div class="rat"><span class="star">★</span> 3.9</div><div class="rate">₹500/hr</div></div>
            <div class="wtgs"><span class="wtg">Carpenter</span></div>
            <div class="wca"><button class="hb">Request</button><button class="vb">Profile</button></div>
          </div>
          <div class="wc">
            <div class="wct"><div class="wav v4">V</div><div class="wi"><h4>Vijay Nair</h4><p>Painter</p></div><div class="wavl ay">Available</div></div>
            <div class="wm"><div class="rat"><span class="star">★</span> 4.5</div><div class="rate">₹300/hr</div></div>
            <div class="wtgs"><span class="wtg">Painter</span></div>
            <div class="wca"><button class="hb">Request</button><button class="vb">Profile</button></div>
          </div>
          <div class="wc">
            <div class="wct"><div class="wav v1">M</div><div class="wi"><h4>Manoj Thomas</h4><p>Technician</p></div><div class="wavl ay">Available</div></div>
            <div class="wm"><div class="rat"><span class="star">★</span> 4.8</div><div class="rate">₹450/hr</div></div>
            <div class="wtgs"><span class="wtg">Technician</span><span class="wtg g">Top Rated</span></div>
            <div class="wca"><button class="hb">Request</button><button class="vb">Profile</button></div>
          </div>
          <div class="wc">
            <div class="wct"><div class="wav v4">P</div><div class="wi"><h4>Priya Sharma</h4><p>Cleaner</p></div><div class="wavl ay">Available</div></div>
            <div class="wm"><div class="rat"><span class="star">★</span> 4.2</div><div class="rate">₹250/hr</div></div>
            <div class="wtgs"><span class="wtg">Cleaner</span></div>
            <div class="wca"><button class="hb">Request</button><button class="vb">Profile</button></div>
          </div>
        </div>
        <div class="pg">
          <div class="pb">‹</div><div class="pb active">1</div><div class="pb">2</div>
          <div class="pb">3</div><div class="pb">4</div><div class="pb">›</div>
        </div>
      </div>

      <!-- RECENT REQUESTS + RIGHT -->
      <div class="two">
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div>
            <div class="sh"><div class="sh-title">Recent Requests</div><span class="sh-link">View all →</span></div>
            <div class="box">
              <table>
                <thead><tr><th>ID</th><th>Worker</th><th>Category</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                  <tr><td>#1024</td><td>Suresh Menon</td><td>Plumber</td><td>₹850</td><td>15 Mar</td><td><span class="badge bp">Pending</span></td></tr>
                  <tr><td>#1023</td><td>Rajan Kumar</td><td>Electrician</td><td>₹1,200</td><td>14 Mar</td><td><span class="badge bc">Completed</span></td></tr>
                  <tr><td>#1022</td><td>Arjun Pillai</td><td>Carpenter</td><td>₹2,000</td><td>13 Mar</td><td><span class="badge bi">In Progress</span></td></tr>
                  <tr><td>#1021</td><td>Manoj Thomas</td><td>Technician</td><td>₹650</td><td>11 Mar</td><td><span class="badge bx">Cancelled</span></td></tr>
                  <tr><td>#1020</td><td>Priya Sharma</td><td>Cleaner</td><td>₹500</td><td>10 Mar</td><td><span class="badge bc">Completed</span></td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- STATS -->
          <div>
            <div class="sh"><div class="sh-title">Your Overview</div></div>
            <div class="sg">
              <div class="sc c1">
                <div class="sc-stripe"></div>
                <div class="sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3d5c10" stroke-width="2.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg></div>
                <div class="sl">Total Requests</div><div class="sv">12</div><div class="st tu">↑ All time</div>
              </div>
              <div class="sc c2">
                <div class="sc-stripe"></div>
                <div class="sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2a7a5a" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="sl">Completed Jobs</div><div class="sv">7</div><div class="st tu">↑ Well done</div>
              </div>
              <div class="sc c3">
                <div class="sc-stripe"></div>
                <div class="sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="sl">Pending</div><div class="sv">3</div><div class="st td">Awaiting</div>
              </div>
              <div class="sc c4">
                <div class="sc-stripe"></div>
                <div class="sc-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 110 7H6"/></svg></div>
                <div class="sl">Total Spent</div><div class="sv">₹4,820</div><div class="st tu">↑ Payments</div>
              </div>
            </div>
          </div>

          <!-- CHART -->
          <div class="box">
            <div class="bh"><span class="bt">Monthly Spending</span><span class="ba">This year</span></div>
            <div class="mc">
              <div class="ch">Amount spent per month (₹)</div>
              <div class="cb">
                <div class="b" style="height:35%"></div><div class="b" style="height:55%"></div>
                <div class="b" style="height:40%"></div><div class="b" style="height:70%"></div>
                <div class="b h" style="height:90%"></div><div class="b" style="height:60%"></div>
                <div class="b" style="height:45%"></div><div class="b h" style="height:80%"></div>
                <div class="b" style="height:50%"></div><div class="b" style="height:30%"></div>
                <div class="b" style="height:20%"></div><div class="b" style="height:10%"></div>
              </div>
              <div class="bl">
                <div class="bll">Jan</div><div class="bll">Feb</div><div class="bll">Mar</div><div class="bll">Apr</div>
                <div class="bll">May</div><div class="bll">Jun</div><div class="bll">Jul</div><div class="bll">Aug</div>
                <div class="bll">Sep</div><div class="bll">Oct</div><div class="bll">Nov</div><div class="bll">Dec</div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="right-col">
          <div class="box">
            <div class="bh"><span class="bt">Quick Actions</span></div>
            <div class="qa">
              <div class="qb"><span>📋</span>New Request</div>
              <div class="qb"><span>👷</span>Browse Workers</div>
              <div class="qb"><span>📂</span>My Requests</div>
              <div class="qb"><span>⭐</span>My Reviews</div>
            </div>
          </div>
          <div class="box">
            <div class="bh"><span class="bt">Notifications</span><span class="ba">See all</span></div>
            <div class="ni-item">
              <div class="nid new"><svg viewBox="0 0 24 24" fill="none" stroke="#3d5c10" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
              <div><div class="nt"><strong>Suresh Menon</strong> accepted your request</div><div class="ntm">5 mins ago</div></div>
            </div>
            <div class="ni-item">
              <div class="nid new"><svg viewBox="0 0 24 24" fill="none" stroke="#3d5c10" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 110 7H6"/></svg></div>
              <div><div class="nt">Payment <strong>₹850</strong> confirmed for #1023</div><div class="ntm">2 hrs ago</div></div>
            </div>
            <div class="ni-item">
              <div class="nid old"><svg viewBox="0 0 24 24" fill="none" stroke="#8a9e70" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg></div>
              <div><div class="nt">Job #1021 marked <strong>Completed</strong></div><div class="ntm">Yesterday</div></div>
            </div>
          </div>
          <div class="box">
            <div class="bh"><span class="bt">Recent Payments</span><span class="ba">View all →</span></div>
            <div class="er">
              <div class="er-row"><div class="er-av" style="background:linear-gradient(135deg,#2a7a5a,#3aaa7a);">S</div><div><div style="font-size:12.5px;color:var(--t);font-weight:500;">Suresh Menon</div><div style="font-size:10.5px;color:var(--t3);">Pipe fix</div></div></div>
              <span class="era">₹850</span>
            </div>
            <div class="er">
              <div class="er-row"><div class="er-av" style="background:linear-gradient(135deg,#567a18,#7aaa28);">R</div><div><div style="font-size:12.5px;color:var(--t);font-weight:500;">Rajan Kumar</div><div style="font-size:10.5px;color:var(--t3);">Wiring fix</div></div></div>
              <span class="era">₹1,200</span>
            </div>
            <div class="er">
              <div class="er-row"><div class="er-av" style="background:linear-gradient(135deg,#3a5080,#5a78b0);">P</div><div><div style="font-size:12.5px;color:var(--t);font-weight:500;">Priya Sharma</div><div style="font-size:10.5px;color:var(--t3);">Deep cleaning</div></div></div>
              <span class="era">₹500</span>
            </div>
            <div class="er" style="border-top:2px solid var(--bdr);font-weight:700;color:var(--t);">
              <span>This month</span><span class="era" style="font-size:14px;">₹3,200</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>