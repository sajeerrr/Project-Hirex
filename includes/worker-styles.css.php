<?php
// worker-styles.css.php — shared CSS for all worker pages
// Output as plain CSS (no PHP tags needed, just echo or include)
?>
:root {
    --mint-50:#f0fdf7;--mint-100:#dcfce7;--mint-200:#bbf7d0;--mint-300:#86efac;
    --mint-400:#4ade80;--mint-500:#22c55e;--mint-600:#16a34a;
    --teal-100:#ccfbf1;--teal-500:#14b8a6;--teal-600:#0d9488;
    --bg:#f8faf9;--bg-secondary:#ffffff;--sidebar-width:250px;
    --primary:var(--mint-600);--primary-hover:#15803d;--primary-light:var(--mint-100);
    --secondary:var(--teal-500);--text-primary:#1a2f24;--text-secondary:#4a5d55;
    --text-gray:#789085;--border:#d1e8dd;--shadow:rgba(22,163,74,.08);
    --shadow-lg:rgba(22,163,74,.15);--danger:#ef4444;--success:var(--mint-500);
    --warning:#f59e0b;--transition:all 0.35s cubic-bezier(.4,0,.2,1);
}
[data-theme="dark"] {
    --bg:#0d1411;--bg-secondary:#141c18;--text-primary:#e0f2e8;--text-secondary:#9dbfa8;
    --text-gray:#789085;--border:#2d3d33;--shadow:rgba(0,0,0,.4);--shadow-lg:rgba(0,0,0,.6);
    --primary:var(--mint-500);--primary-hover:var(--mint-400);--primary-light:rgba(34,197,94,.15);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
svg{display:block}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text-primary);
    display:flex;line-height:1.6;overflow-x:hidden;transition:var(--transition);
    background-image:radial-gradient(ellipse at top right,rgba(34,197,94,.06) 0%,transparent 50%),
    radial-gradient(ellipse at bottom left,rgba(20,184,166,.08) 0%,transparent 50%);}

/* SIDEBAR */
.sidebar{width:var(--sidebar-width);height:100vh;background:var(--bg-secondary);
    border-right:1px solid var(--border);padding:24px 16px;display:flex;flex-direction:column;
    position:fixed;top:0;left:0;bottom:0;z-index:1000;transition:var(--transition);overflow:hidden;}
.logo{font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;font-weight:800;
    margin-bottom:8px;padding-left:14px;letter-spacing:-.5px;color:var(--text-primary);
    display:flex;align-items:center;flex-shrink:0;}
.logo .x{color:var(--primary)}
.worker-badge{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:#fff;
    font-size:9px;padding:3px 8px;border-radius:6px;font-weight:700;text-transform:uppercase;
    letter-spacing:.5px;margin-bottom:20px;display:inline-block;width:fit-content;margin-left:14px;}
.sidebar-nav{flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;padding-right:4px;margin-right:-4px;}
.nav-group{margin-bottom:20px}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-gray);
    margin-bottom:8px;padding-left:14px;font-weight:700;}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
    font-size:13px;font-weight:500;color:var(--text-secondary);text-decoration:none;
    margin-bottom:3px;transition:var(--transition);}
.nav-item:hover{background:var(--primary-light);color:var(--primary);transform:translateX(4px)}
.nav-item.active{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:#fff;
    box-shadow:0 4px 15px var(--shadow-lg)}
.nav-item svg{width:18px;height:18px;flex-shrink:0}
.nav-badge{background:var(--danger);color:#fff;font-size:9px;padding:2px 6px;
    border-radius:6px;margin-left:auto;font-weight:700}
.signout-container{margin-top:auto;padding-top:16px;border-top:1px solid var(--border);flex-shrink:0}
.signout-btn{display:flex;align-items:center;gap:12px;padding:11px 14px;width:100%;
    text-decoration:none;color:var(--danger);background:#fef2f2;border-radius:10px;
    font-weight:600;font-size:13px;transition:var(--transition)}
[data-theme="dark"] .signout-btn{background:rgba(239,68,68,.15)}
.signout-btn:hover{background:var(--danger);color:#fff;transform:translateX(4px)}
.signout-btn svg{width:18px;height:18px}

/* MAIN */
.main-content{margin-left:var(--sidebar-width);flex:1;padding:0 32px 40px;min-width:0;transition:var(--transition)}

/* OVERLAY */
.overlay{display:none;position:fixed;inset:0;background:rgba(13,20,17,.65);
    backdrop-filter:blur(4px);z-index:999;opacity:0;transition:var(--transition)}
.overlay.active{display:block;opacity:1}

/* MOBILE TOGGLE */
.mobile-toggle{display:none;background:var(--bg-secondary);border:1px solid var(--border);
    border-radius:10px;padding:10px 12px;cursor:pointer;color:var(--text-primary);transition:var(--transition)}
.mobile-toggle:hover{background:var(--primary-light);border-color:var(--primary)}

/* HEADER */
header{min-height:70px;display:flex;align-items:center;justify-content:space-between;
    gap:16px;flex-wrap:wrap;margin-bottom:22px;}
.header-left{display:flex;align-items:center;gap:13px;flex:1}
.search-bar{background:var(--bg-secondary);padding:11px 15px;border-radius:11px;
    border:1px solid var(--border);display:flex;align-items:center;flex:1;max-width:420px;transition:var(--transition);}
.search-bar:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px rgba(22,163,74,.12)}
.search-bar input{border:none;outline:none;width:100%;margin-left:10px;background:transparent;
    color:var(--text-primary);font-size:13px;font-family:'Inter',sans-serif}
.search-bar input::placeholder{color:var(--text-gray)}
.header-actions{display:flex;align-items:center;gap:11px}
.theme-toggle{background:var(--bg-secondary);border:1px solid var(--border);border-radius:11px;
    padding:10px 13px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:12px;
    color:var(--text-secondary);transition:var(--transition);font-weight:500;font-family:'Inter',sans-serif}
.theme-toggle:hover{border-color:var(--primary);background:var(--primary-light);color:var(--primary)}
.icon-btn{background:var(--bg-secondary);border:1px solid var(--border);border-radius:11px;
    width:42px;height:42px;display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:var(--transition);position:relative}
.icon-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary);transform:translateY(-2px)}
.notification-dot{position:absolute;top:8px;right:8px;width:8px;height:8px;background:var(--danger);
    border-radius:50%;border:2px solid var(--bg-secondary)}
.user-pill{display:flex;align-items:center;gap:10px;background:var(--bg-secondary);
    padding:5px 15px 5px 5px;border-radius:30px;border:1px solid var(--border);cursor:pointer;transition:var(--transition)}
.user-pill:hover{border-color:var(--primary);box-shadow:0 4px 14px var(--shadow)}
.avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));
    border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;
    font-size:14px;color:#fff;flex-shrink:0;overflow:hidden}
.avatar img{width:100%;height:100%;object-fit:cover}
.user-name{font-size:13px;font-weight:600;color:var(--text-primary);font-family:'Plus Jakarta Sans',sans-serif}

/* PAGE TITLE */
.page-title{margin:4px 0 22px;padding-bottom:16px;border-bottom:1px solid var(--border)}
.page-title h1{font-size:24px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary);margin:0}
.page-title p{color:var(--text-gray);margin-top:5px;font-size:13px}

/* STATS GRID */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:14px;
    padding:20px;display:flex;align-items:center;gap:15px;transition:var(--transition);
    position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;
    background:linear-gradient(180deg,var(--mint-500),var(--teal-500));border-radius:14px 0 0 14px}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px var(--shadow);border-color:var(--primary)}
.stat-icon{width:48px;height:48px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.stat-icon.green{background:linear-gradient(135deg,var(--mint-100),var(--mint-200));color:var(--mint-600)}
.stat-icon.teal{background:linear-gradient(135deg,var(--teal-100),#99f6e4);color:var(--teal-600)}
.stat-icon.yellow{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#b45309}
.stat-icon.purple{background:linear-gradient(135deg,#ede9fe,#ddd6fe);color:#7c3aed}
.stat-icon svg{width:22px;height:22px}
.stat-info h4{font-size:24px;font-weight:800;color:var(--text-primary);font-family:'Plus Jakarta Sans',sans-serif;line-height:1.1}
.stat-info p{font-size:11px;color:var(--text-gray);margin-top:3px;font-weight:500}

/* AVAILABILITY CARD */
.avail-card{border-radius:15px;padding:22px 28px;margin-bottom:22px;display:flex;
    align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;transition:var(--transition)}
.avail-on{background:linear-gradient(135deg,rgba(34,197,94,.12),rgba(20,184,166,.08));border:1.5px solid rgba(34,197,94,.3)}
.avail-off{background:linear-gradient(135deg,rgba(239,68,68,.08),rgba(245,158,11,.06));border:1.5px solid rgba(239,68,68,.25)}
.avail-info{display:flex;align-items:center;gap:16px}
.avail-status-dot{width:16px;height:16px;border-radius:50%;flex-shrink:0;animation:pulse 2s infinite}
.avail-on .avail-status-dot{background:var(--mint-500);box-shadow:0 0 0 4px rgba(34,197,94,.2)}
.avail-off .avail-status-dot{background:var(--danger);box-shadow:0 0 0 4px rgba(239,68,68,.2)}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
.avail-title{font-size:15px;font-weight:600;color:var(--text-primary)}
.avail-sub{font-size:12px;color:var(--text-gray);margin-top:3px}
.avail-btn{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:#fff;
    border:none;padding:11px 22px;border-radius:10px;font-weight:600;font-size:13px;
    cursor:pointer;transition:var(--transition);font-family:'Inter',sans-serif}
.avail-btn:hover{transform:translateY(-2px);box-shadow:0 5px 18px var(--shadow-lg)}
.avail-off .avail-btn{background:linear-gradient(135deg,#f97316,#ea580c)}

/* TWO COL */
.two-col-grid{display:grid;grid-template-columns:1fr 370px;gap:20px;margin-bottom:20px}

/* CARD BOX */
.card-box{background:var(--bg-secondary);border:1px solid var(--border);border-radius:15px;
    padding:20px;transition:var(--transition);margin-bottom:20px}
.card-box:hover{box-shadow:0 8px 28px var(--shadow)}
.card-box-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;
    padding-bottom:14px;border-bottom:1px solid var(--border)}
.box-title{font-size:14px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px;font-family:'Plus Jakarta Sans',sans-serif}
.box-link{font-size:12px;color:var(--primary);text-decoration:none;font-weight:500}
.box-link:hover{text-decoration:underline}

/* BOOKING ROW */
.booking-row{display:flex;align-items:flex-start;gap:13px;padding:13px 0;border-bottom:1px dashed var(--border)}
.booking-row:last-child{border-bottom:none}
.booking-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));
    display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:15px;flex-shrink:0;overflow:hidden}
.booking-avatar img{width:100%;height:100%;object-fit:cover}
.booking-info{flex:1;min-width:0}
.booking-name{font-size:13px;font-weight:600;color:var(--text-primary)}
.booking-meta{font-size:11px;color:var(--text-gray);margin-top:3px;display:flex;align-items:center;gap:5px}
.booking-right{display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0}
.booking-amount{font-size:14px;font-weight:700;color:var(--primary);font-family:'Plus Jakarta Sans',sans-serif}

/* REQUEST CARD */
.request-card{background:var(--bg);border:1px solid var(--border);border-radius:11px;padding:14px;margin-bottom:10px;transition:var(--transition)}
.request-card:hover{border-color:var(--primary);box-shadow:0 4px 14px var(--shadow)}
.request-top{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.request-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));
    display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:13px;flex-shrink:0}
.request-info{flex:1}
.request-name{font-size:13px;font-weight:600;color:var(--text-primary)}
.request-date{font-size:11px;color:var(--text-gray);margin-top:2px}
.request-amount{font-size:14px;font-weight:700;color:var(--primary);font-family:'Plus Jakarta Sans',sans-serif}
.request-notes{font-size:11px;color:var(--text-secondary);margin-bottom:10px;padding:8px 10px;
    background:var(--bg-secondary);border-radius:7px;border:1px solid var(--border)}
.request-actions{display:flex;gap:8px}
.btn-accept{flex:1;background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:#fff;border:none;
    padding:8px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:5px;transition:var(--transition);font-family:'Inter',sans-serif}
.btn-accept:hover{transform:translateY(-1px);box-shadow:0 4px 12px var(--shadow-lg)}
.btn-decline{flex:1;background:transparent;color:var(--danger);border:1.5px solid rgba(239,68,68,.3);
    padding:8px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:5px;transition:var(--transition);font-family:'Inter',sans-serif}
.btn-decline:hover{background:var(--danger);color:#fff}

/* PERFORMANCE */
.perf-item{margin-bottom:16px}
.perf-label{display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;color:var(--text-secondary)}
.perf-pct{font-weight:700;color:var(--text-primary)}
.perf-bar{height:8px;background:var(--border);border-radius:4px;overflow:hidden}
.perf-fill{height:100%;border-radius:4px;transition:width .8s ease}
.perf-fill.green{background:linear-gradient(90deg,var(--mint-400),var(--mint-600))}
.perf-fill.teal{background:linear-gradient(90deg,var(--teal-500),var(--teal-600))}
.perf-fill.yellow{background:linear-gradient(90deg,#fbbf24,#f59e0b)}

/* TABLE */
.w-table{width:100%;border-collapse:collapse}
.w-table thead tr{border-bottom:2px solid var(--border)}
.w-table th{text-align:left;padding:10px 12px;font-size:11px;font-weight:700;color:var(--text-gray);text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
.w-table td{padding:13px 12px;font-size:13px;color:var(--text-secondary);border-bottom:1px solid var(--border);vertical-align:middle}
.w-table tr:last-child td{border-bottom:none}
.w-table tbody tr:hover td{background:rgba(34,197,94,.04)}

/* BUTTONS */
.btn-sm{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:7px;
    font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;transition:var(--transition);
    border:1px solid var(--border);color:var(--text-secondary);background:var(--bg-secondary)}
.btn-sm:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
.btn-primary{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:#fff;border:none;
    padding:11px 20px;border-radius:10px;font-weight:600;font-size:13px;cursor:pointer;
    display:inline-flex;align-items:center;gap:8px;transition:var(--transition);font-family:'Inter',sans-serif}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 5px 18px var(--shadow-lg)}
.btn-secondary{background:var(--bg-secondary);color:var(--text-primary);border:1px solid var(--border);
    padding:11px 20px;border-radius:10px;font-weight:600;font-size:13px;cursor:pointer;
    display:inline-flex;align-items:center;gap:8px;transition:var(--transition);font-family:'Inter',sans-serif}
.btn-secondary:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
.btn-danger{background:var(--danger);color:#fff;border:none;padding:11px 20px;border-radius:10px;
    font-weight:600;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;
    transition:var(--transition);font-family:'Inter',sans-serif}
.btn-danger:hover{transform:translateY(-2px);box-shadow:0 5px 18px rgba(239,68,68,.3)}

/* FORM ELEMENTS */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:8px}
.form-input,.form-select,.form-textarea{width:100%;padding:11px 14px;border:1px solid var(--border);
    border-radius:10px;background:var(--bg);color:var(--text-primary);font-size:13px;
    font-family:'Inter',sans-serif;transition:var(--transition);outline:none}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(22,163,74,.12)}
.form-select{cursor:pointer}
.form-textarea{resize:vertical;min-height:100px}

/* ALERT */
.alert{padding:13px 16px;border-radius:10px;margin-bottom:18px;display:flex;align-items:center;gap:12px;font-size:13px;font-weight:500}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:var(--mint-600)}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:var(--danger)}
[data-theme="dark"] .alert-success{background:rgba(34,197,94,.15)}
[data-theme="dark"] .alert-error{background:rgba(239,68,68,.15)}
.alert svg{width:18px;height:18px;flex-shrink:0}

/* TOAST */
.toast{position:fixed;bottom:26px;right:26px;background:var(--bg-secondary);border:1px solid var(--border);
    border-left:5px solid var(--success);padding:15px 20px;border-radius:12px;
    box-shadow:0 14px 45px var(--shadow-lg);transform:translateX(160%);transition:var(--transition);
    z-index:2000;display:flex;align-items:center;gap:12px;min-width:280px}
.toast.show{transform:translateX(0)}
.toast-title{font-weight:600;color:var(--text-primary);font-size:13px}
.toast-message{font-size:12px;color:var(--text-gray);margin-top:2px}

/* EMPTY STATE */
.empty-state{text-align:center;padding:36px 16px;color:var(--text-gray)}
.empty-state svg{margin:0 auto 12px;opacity:.4;width:40px;height:40px}
.empty-state p{font-size:13px}

/* FILTER CHIPS */
.filter-bar{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.chip{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--bg-secondary);
    border:1px solid var(--border);border-radius:20px;font-size:12px;font-weight:500;
    color:var(--text-secondary);cursor:pointer;transition:var(--transition);text-decoration:none}
.chip:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
.chip.active{background:linear-gradient(135deg,var(--mint-500),var(--mint-600));color:#fff;border-color:var(--mint-500);box-shadow:0 4px 14px var(--shadow-lg)}

/* SCROLLBAR */
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--mint-300);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--mint-500)}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(13,20,17,.7);
    backdrop-filter:blur(6px);z-index:3000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--bg-secondary);border-radius:18px;padding:28px;width:90%;max-width:560px;
    max-height:90vh;overflow-y:auto;border:1px solid var(--border);box-shadow:0 24px 60px var(--shadow-lg)}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;
    padding-bottom:16px;border-bottom:1px solid var(--border)}
.modal-title{font-size:18px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text-primary)}
.modal-close{background:none;border:1px solid var(--border);border-radius:8px;width:34px;height:34px;
    cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-gray);transition:var(--transition)}
.modal-close:hover{background:var(--danger);color:#fff;border-color:var(--danger)}

/* TABS */
.tabs{display:flex;gap:4px;margin-bottom:22px;background:var(--bg);border-radius:12px;padding:4px}
.tab-btn{flex:1;padding:9px 14px;border-radius:9px;border:none;font-size:13px;font-weight:500;
    cursor:pointer;transition:var(--transition);background:transparent;color:var(--text-secondary);font-family:'Inter',sans-serif}
.tab-btn.active{background:var(--bg-secondary);color:var(--text-primary);font-weight:600;
    box-shadow:0 2px 8px var(--shadow)}
.tab-panel{display:none}
.tab-panel.active{display:block}

/* RESPONSIVE */
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.active{transform:translateX(0)}
    .main-content{margin-left:0;padding:0 18px 32px}
    .mobile-toggle{display:flex}
    .two-col-grid{grid-template-columns:1fr}
}
@media(max-width:560px){
    .stats-grid{grid-template-columns:1fr 1fr}
    .toast{left:16px;right:16px;bottom:16px;min-width:0}
    .user-name{display:none}
}
