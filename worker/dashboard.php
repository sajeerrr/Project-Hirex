<?php
session_start();
include("../database/db.php");

/* ─── SECURITY ─────────────────────────────────────────── */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'worker') {
    header("Location:../login.php");
    exit;
}

$workerId = $_SESSION['worker_id'];

/* ─── WORKER INFO ─────────────────────────────────────────── */
$worker = [];
$r = $conn->query("SELECT * FROM workers WHERE id='$workerId'");
if ($r && $r->num_rows > 0) {
    $worker = $r->fetch_assoc();
}

$workerName    = $worker['name']     ?? 'Worker';
$workerInitial = strtoupper(substr($workerName, 0, 1));
$category      = $worker['category'] ?? 'Worker';
$rating        = $worker['rating']   ?? 0;
$status        = $worker['status']   ?? 'active';
$location      = $worker['city']     ?? '—';

/* ─── COUNTS ─────────────────────────────────────────── */
$total_requests  = 0;
$pending_requests = 0;
$completed_jobs  = 0;
$total_earned    = 0;

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE worker_id='$workerId'");
if ($r) $total_requests = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE worker_id='$workerId' AND status='pending'");
if ($r) $pending_requests = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) as total FROM requests WHERE worker_id='$workerId' AND status='completed'");
if ($r) $completed_jobs = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT SUM(amount) as total FROM requests WHERE worker_id='$workerId' AND status='completed'");
if ($r) {
    $row = $r->fetch_assoc();
    $total_earned = $row['total'] ?? 0;
}

/* ─── RECENT REQUESTS ─────────────────────────────────────────── */
$recent_requests = [];
$r = $conn->query("
    SELECT r.id,
           u.name  AS user_name,
           r.status,
           r.amount,
           r.created_at
    FROM requests r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.worker_id = '$workerId'
    ORDER BY r.created_at DESC
    LIMIT 6
");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $recent_requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Worker Dashboard — HireX</title>
  <link rel="stylesheet" href="../assets/css/worker-dashboard.css">
</head>
<body>

<div class="shell">
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="logo-bar">
      <div class="brand">Hire<span>X</span></div>
    </div>
    <div class="nav">
      <div class="nav-section">Menu</div>
      <a class="nav-item active" href="#">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </a>
      <a class="nav-item" href="#">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
        My Requests
      </a>
      <a class="nav-item" href="#">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        Job History
      </a>
      <a class="nav-item" href="#">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 110 7H6"/></svg>
        Earnings
      </a>
      <div class="nav-section">Account</div>
      <a class="nav-item" href="#">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        Profile
      </a>
      <a class="nav-item" href="#">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
        Settings
      </a>
    </div>
    <div class="sidebar-bottom">
      <div class="worker-mini">
        <div class="w-av">R</div>
        <div class="w-meta">
          <div class="wname">Ravi Kumar</div>
          <div class="wrole">Plumber · Mumbai</div>
        </div>
      </div>
      <div class="logout-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </div>
    </div>
  </div>

  <!-- MAIN -->
  <div class="main">
    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <h2>Worker Dashboard</h2>
        <p>Sunday, 15 March 2026</p>
      </div>
      <div class="topbar-right">
        <div class="search-bar">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          Search jobs…
        </div>
        <div class="status-pill">
          <div class="status-dot"></div>
          Available
        </div>
        <div class="notif-btn" onclick="toggleNotif(this)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <div class="notif-dot"></div>
        </div>
      </div>
    </div>

    <!-- SCROLL CONTENT -->
    <div class="content">

      <!-- PROFILE HERO -->
      <div class="profile-hero">
        <div class="p-avatar">R</div>
        <div class="p-info">
          <div class="p-name">Ravi Kumar</div>
          <div class="p-sub">📍 Mumbai, Maharashtra &nbsp;·&nbsp; Member since Jan 2024</div>
          <div class="p-tags">
            <span class="p-tag">Plumber</span>
            <span class="p-tag gold">⭐ 4.8 Rating</span>
            <span class="p-tag">68 Jobs Done</span>
            <span class="p-tag">Active</span>
          </div>
        </div>
        <div class="p-actions">
          <button class="p-btn outline">View Profile</button>
          <button class="p-btn solid">Edit Details</button>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats">
        <div class="stat-card c1">
          <div class="stat-label">Total Requests</div>
          <div class="stat-val">84</div>
          <div class="stat-trend trend-up">↑ 12% this month</div>
        </div>
        <div class="stat-card c2">
          <div class="stat-label">Pending Jobs</div>
          <div class="stat-val">6</div>
          <div class="stat-trend trend-down">↑ 2 new today</div>
        </div>
        <div class="stat-card c3">
          <div class="stat-label">Completed Jobs</div>
          <div class="stat-val">68</div>
          <div class="stat-trend trend-up">↑ 8 this week</div>
        </div>
        <div class="stat-card c4">
          <div class="stat-label">Total Earnings</div>
          <div class="stat-val">₹48,200</div>
          <div class="stat-trend trend-up">↑ ₹3,400 this week</div>
        </div>
      </div>

      <!-- TWO COL -->
      <div class="two-col">
        <!-- REQUESTS TABLE -->
        <div class="box">
          <div class="box-header">
            <span class="box-title">Recent Requests</span>
            <span class="box-action">View all →</span>
          </div>
          <table>
            <tr><th>ID</th><th>Customer</th><th>Amount</th><th>Date</th><th>Status</th></tr>
            <tr><td>#1024</td><td>Anil Sharma</td><td>₹1,200</td><td>15 Mar</td><td><span class="badge pending">Pending</span></td></tr>
            <tr><td>#1023</td><td>Priya Nair</td><td>₹850</td><td>14 Mar</td><td><span class="badge completed">Completed</span></td></tr>
            <tr><td>#1022</td><td>Suresh Babu</td><td>₹2,000</td><td>14 Mar</td><td><span class="badge inprogress">In Progress</span></td></tr>
            <tr><td>#1021</td><td>Kavya Reddy</td><td>₹650</td><td>13 Mar</td><td><span class="badge cancelled">Cancelled</span></td></tr>
            <tr><td>#1020</td><td>Mohan Das</td><td>₹1,500</td><td>12 Mar</td><td><span class="badge completed">Completed</span></td></tr>
            <tr><td>#1019</td><td>Lakshmi Rao</td><td>₹900</td><td>11 Mar</td><td><span class="badge completed">Completed</span></td></tr>
          </table>
        </div>

        <!-- RIGHT PANEL -->
        <div style="display:flex;flex-direction:column;gap:14px;">
          <!-- AVAILABILITY -->
          <div class="box">
            <div class="box-header"><span class="box-title">Availability</span></div>
            <div class="avail-row">
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--text)">Accept new jobs</div>
                <div style="font-size:11px;color:var(--text-3);margin-top:2px;">Toggle your availability</div>
              </div>
              <div class="toggle-track on" onclick="this.classList.toggle('on')">
                <div class="toggle-thumb"></div>
              </div>
            </div>
            <div class="avail-row" style="border-top:1px solid #f0f3e4;padding-top:12px;">
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--text)">Away mode</div>
                <div style="font-size:11px;color:var(--text-3);margin-top:2px;">Temporarily unavailable</div>
              </div>
              <div class="toggle-track" onclick="this.classList.toggle('on')">
                <div class="toggle-thumb"></div>
              </div>
            </div>
          </div>

          <!-- QUICK ACTIONS -->
          <div class="box">
            <div class="box-header"><span class="box-title">Quick Actions</span></div>
            <div class="quick-actions">
              <div class="qa-btn">📋 New Request</div>
              <div class="qa-btn">💬 Messages</div>
              <div class="qa-btn">📊 View Report</div>
              <div class="qa-btn">⭐ My Reviews</div>
            </div>
          </div>

          <!-- NOTIFICATIONS -->
          <div class="box">
            <div class="box-header"><span class="box-title">Notifications</span><span class="box-action">Mark all read</span></div>
            <div class="notif-item">
              <div class="notif-dot-big new"></div>
              <div>
                <div class="notif-text">New job request from <strong>Anil Sharma</strong> — Pipe repair</div>
                <div class="notif-time">2 mins ago</div>
              </div>
            </div>
            <div class="notif-item">
              <div class="notif-dot-big new"></div>
              <div>
                <div class="notif-text">Payment of <strong>₹850</strong> received for job #1023</div>
                <div class="notif-time">1 hr ago</div>
              </div>
            </div>
            <div class="notif-item">
              <div class="notif-dot-big old"></div>
              <div>
                <div class="notif-text">Priya Nair left a 5-star review</div>
                <div class="notif-time">Yesterday</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- WEEKLY EARNINGS + RECENT PAYMENTS -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="box">
          <div class="box-header"><span class="box-title">Weekly Earnings</span><span class="box-action">This week</span></div>
          <div class="mini-chart">
            <div style="font-size:11px;color:var(--text-3);margin-bottom:8px;">₹ earnings per day</div>
            <div class="chart-bars">
              <div class="bar" style="height:40%"></div>
              <div class="bar" style="height:60%"></div>
              <div class="bar" style="height:30%"></div>
              <div class="bar hi" style="height:80%"></div>
              <div class="bar" style="height:55%"></div>
              <div class="bar hi" style="height:90%"></div>
              <div class="bar" style="height:20%"></div>
            </div>
            <div class="bar-labels">
              <div class="bar-label">Mon</div><div class="bar-label">Tue</div><div class="bar-label">Wed</div>
              <div class="bar-label">Thu</div><div class="bar-label">Fri</div><div class="bar-label">Sat</div>
              <div class="bar-label">Sun</div>
            </div>
          </div>
        </div>

        <div class="box">
          <div class="box-header"><span class="box-title">Recent Payments</span><span class="box-action">View all →</span></div>
          <div class="earn-row"><span>Anil Sharma — Pipe fix</span><span class="er-amt">+ ₹1,200</span></div>
          <div class="earn-row"><span>Priya Nair — Tap install</span><span class="er-amt">+ ₹850</span></div>
          <div class="earn-row"><span>Mohan Das — Drainage</span><span class="er-amt">+ ₹1,500</span></div>
          <div class="earn-row"><span>Lakshmi Rao — Boiler check</span><span class="er-amt">+ ₹900</span></div>
          <div class="earn-row" style="border-top:2px solid var(--border);margin-top:4px;">
            <span style="font-weight:600;color:var(--text)">This week</span>
            <span class="er-amt" style="font-size:15px;">₹4,450</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>