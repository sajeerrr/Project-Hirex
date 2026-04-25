<?php
require_once(__DIR__ . '/includes/functions.php');

global $conn;
$admin = admin_get_admin($conn);
$admin_id = (int) $_SESSION['admin_id'];

// ============================================
// FETCH / ENSURE admin_settings ROW
// ============================================
$hasTable = admin_table_exists($conn, 'admin_settings');

if ($hasTable) {
    $stmt = $conn->prepare("SELECT * FROM admin_settings WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $settingsResult = $stmt->get_result();

    if ($settingsResult->num_rows === 0) {
        $conn->query("INSERT IGNORE INTO admin_settings (admin_id, email_notifications, booking_alerts, worker_approvals, complaint_alerts, maintenance_mode, allow_registration, max_booking_days, default_currency) VALUES ($admin_id, 1, 1, 1, 1, 0, 1, 30, 'INR')");
        $stmt2 = $conn->prepare("SELECT * FROM admin_settings WHERE admin_id = ?");
        $stmt2->bind_param("i", $admin_id);
        $stmt2->execute();
        $settingsResult = $stmt2->get_result();
        $stmt2->close();
    }
    $settings = $settingsResult->fetch_assoc();
    $stmt->close();
} else {
    // Table does not exist — use defaults, disable saving
    $settings = [
        'email_notifications'  => 1,
        'booking_alerts'       => 1,
        'worker_approvals'     => 1,
        'complaint_alerts'     => 1,
        'maintenance_mode'     => 0,
        'allow_registration'   => 1,
        'max_booking_days'     => 30,
        'default_currency'     => 'INR',
    ];
}

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================
$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasTable) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_notifications') {
        $emailNotif      = isset($_POST['email_notifications']) ? 1 : 0;
        $bookingAlerts   = isset($_POST['booking_alerts'])      ? 1 : 0;
        $workerApprovals = isset($_POST['worker_approvals'])    ? 1 : 0;
        $complaintAlerts = isset($_POST['complaint_alerts'])    ? 1 : 0;

        $stmt = $conn->prepare("UPDATE admin_settings SET email_notifications=?, booking_alerts=?, worker_approvals=?, complaint_alerts=? WHERE admin_id=?");
        $stmt->bind_param("iiiii", $emailNotif, $bookingAlerts, $workerApprovals, $complaintAlerts, $admin_id);
        if ($stmt->execute()) {
            $successMessage = 'Notification settings updated successfully!';
            $settings['email_notifications'] = $emailNotif;
            $settings['booking_alerts']      = $bookingAlerts;
            $settings['worker_approvals']    = $workerApprovals;
            $settings['complaint_alerts']    = $complaintAlerts;
        } else {
            $errorMessage = 'Failed to update notification settings.';
        }
        $stmt->close();

    } elseif ($action === 'update_system') {
        $maintenanceMode   = isset($_POST['maintenance_mode'])   ? 1 : 0;
        $allowRegistration = isset($_POST['allow_registration']) ? 1 : 0;
        $maxBookingDays    = max(1, min(365, (int) ($_POST['max_booking_days'] ?? 30)));
        $defaultCurrency   = in_array($_POST['default_currency'] ?? 'INR', ['INR', 'USD', 'EUR', 'GBP']) ? $_POST['default_currency'] : 'INR';

        $stmt = $conn->prepare("UPDATE admin_settings SET maintenance_mode=?, allow_registration=?, max_booking_days=?, default_currency=? WHERE admin_id=?");
        $stmt->bind_param("iiisi", $maintenanceMode, $allowRegistration, $maxBookingDays, $defaultCurrency, $admin_id);
        if ($stmt->execute()) {
            $successMessage = 'System settings updated successfully!';
            $settings['maintenance_mode']   = $maintenanceMode;
            $settings['allow_registration'] = $allowRegistration;
            $settings['max_booking_days']   = $maxBookingDays;
            $settings['default_currency']   = $defaultCurrency;
        } else {
            $errorMessage = 'Failed to update system settings.';
        }
        $stmt->close();
    }
}

// ============================================
// SYSTEM DIAGNOSTICS
// ============================================
$diagnostics = [
    ['Database',        admin_table_exists($conn, 'users')    ? 'Connected'       : 'Connection issue', admin_table_exists($conn, 'users') ? 'good' : 'bad'],
    ['Workers Table',   admin_table_exists($conn, 'workers')  ? 'Present'         : 'Missing',          admin_table_exists($conn, 'workers')  ? 'good' : 'bad'],
    ['Bookings Table',  admin_table_exists($conn, 'bookings') ? 'Present'         : 'Missing',          admin_table_exists($conn, 'bookings') ? 'good' : 'bad'],
    ['Admin Settings',  $hasTable                             ? 'Table exists'    : 'Table missing — run migration', $hasTable ? 'good' : 'warn'],
    ['Worker Approval', admin_column_exists($conn, 'workers', 'status') ? 'Enabled' : 'Disabled',       admin_column_exists($conn, 'workers', 'status') ? 'good' : 'warn'],
    ['User Status',     admin_column_exists($conn, 'users', 'status')   ? 'Enabled' : 'Disabled',       admin_column_exists($conn, 'users', 'status')   ? 'good' : 'warn'],
    ['Support Inbox',   admin_table_exists($conn, 'contacts')           ? 'Active'  : 'Table missing',  admin_table_exists($conn, 'contacts')           ? 'good' : 'warn'],
    ['Activity Logs',   admin_table_exists($conn, 'admin_logs')         ? 'Active'  : 'Table missing',  admin_table_exists($conn, 'admin_logs')         ? 'good' : 'warn'],
];

admin_page_start('Settings', 'settings', 'Manage system configuration, notifications, and preferences.');
?>

<style>
    /* ===== SETTINGS-SPECIFIC STYLES ===== */
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
        margin-bottom: 22px;
    }
    .settings-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border);
        border-radius: 15px;
        padding: 24px;
        transition: var(--transition);
    }
    .settings-card:hover {
        box-shadow: 0 10px 30px var(--shadow);
        border-color: var(--primary);
    }
    .settings-card-header {
        display: flex;
        align-items: center;
        gap: 13px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
    }
    .settings-card-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .settings-card-icon.notifications { background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); color: var(--mint-600); }
    .settings-card-icon.system        { background: linear-gradient(135deg, var(--teal-100), #99f6e4); color: var(--teal-600); }
    .settings-card-icon.diagnostics   { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5; }
    .settings-card-icon.security      { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
    .settings-card-icon svg { width: 22px; height: 22px; }
    .settings-card-title { font-size: 16px; font-weight: 700; color: var(--text-primary); font-family: 'Plus Jakarta Sans', sans-serif; }
    .settings-card-desc  { font-size: 12px; color: var(--text-gray); margin-top: 2px; }

    /* Toggle switches */
    .toggle-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px dashed var(--border);
    }
    .toggle-group:last-child { border-bottom: none; }
    .toggle-label { display: flex; flex-direction: column; gap: 4px; }
    .toggle-label-title { font-size: 13px; font-weight: 600; color: var(--text-primary); }
    .toggle-label-desc  { font-size: 11px; color: var(--text-gray); }
    .toggle-switch { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--border);
        transition: var(--transition);
        border-radius: 26px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 20px; width: 20px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: var(--transition);
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .toggle-switch input:checked + .toggle-slider { background: linear-gradient(135deg, var(--mint-500), var(--mint-600)); }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }
    .toggle-switch:hover .toggle-slider { box-shadow: 0 0 0 3px var(--primary-light); }

    /* Form elements */
    .form-group { margin-bottom: 18px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; }
    .form-input, .form-select {
        width: 100%;
        padding: 11px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--bg);
        color: var(--text-primary);
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        transition: var(--transition);
        outline: none;
    }
    .form-input:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
    }
    .form-select { cursor: pointer; }

    /* Save button row */
    .card-footer {
        margin-top: 20px;
        text-align: right;
    }

    /* Alerts */
    .alert {
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        font-weight: 500;
    }
    .alert-success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.25); color: var(--mint-600); }
    .alert-error   { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: var(--danger); }
    [data-theme="dark"] .alert-success { background: rgba(34,197,94,.15); border-color: rgba(34,197,94,.3); }
    [data-theme="dark"] .alert-error   { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.3); }
    .alert svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* Diagnostic table */
    .diag-row { display: flex; align-items: center; justify-content: space-between; padding: 11px 0; border-bottom: 1px dashed var(--border); gap: 12px; }
    .diag-row:last-child { border-bottom: none; }
    .diag-name { font-size: 13px; font-weight: 500; color: var(--text-primary); }
    .diag-badge {
        display: inline-flex; align-items: center; gap: 5px;
        border-radius: 999px; padding: 4px 12px;
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
        white-space: nowrap;
    }
    .diag-good    { color: #15803d; background: rgba(34,197,94,.12); }
    .diag-warn    { color: #b45309; background: rgba(245,158,11,.12); }
    .diag-bad     { color: #b91c1c; background: rgba(239,68,68,.12); }

    /* Full-width card */
    .settings-card-full { grid-column: 1 / -1; }

    /* Notice banner */
    .notice-banner {
        padding: 12px 18px;
        border-radius: 10px;
        background: rgba(245,158,11,.1);
        border: 1px solid rgba(245,158,11,.25);
        color: #b45309;
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    [data-theme="dark"] .notice-banner { background: rgba(245,158,11,.15); border-color: rgba(245,158,11,.3); }

    @media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }
</style>

<?php if ($successMessage): ?>
<div class="alert alert-success">
    <?php echo admin_icon('check', 18); ?>
    <span><?php echo e($successMessage); ?></span>
</div>
<?php endif; ?>

<?php if ($errorMessage): ?>
<div class="alert alert-error">
    <?php echo admin_icon('x', 18); ?>
    <span><?php echo e($errorMessage); ?></span>
</div>
<?php endif; ?>

<?php if (!$hasTable): ?>
<div class="notice-banner">
    <?php echo admin_icon('alert', 16); ?>
    <span>The <strong>admin_settings</strong> table is missing. Settings cannot be saved until the table is created. Run the migration SQL below.</span>
</div>
<?php endif; ?>

<div class="settings-grid">

    <!-- ===== NOTIFICATIONS ===== -->
    <div class="settings-card">
        <div class="settings-card-header">
            <div class="settings-card-icon notifications"><?php echo admin_icon('bell', 22); ?></div>
            <div>
                <div class="settings-card-title">Notifications</div>
                <div class="settings-card-desc">Control which alerts you receive</div>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_notifications">

            <div class="toggle-group">
                <div class="toggle-label">
                    <span class="toggle-label-title">Email Notifications</span>
                    <span class="toggle-label-desc">Receive platform updates via email</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-group">
                <div class="toggle-label">
                    <span class="toggle-label-title">Booking Alerts</span>
                    <span class="toggle-label-desc">New, cancelled, or completed bookings</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="booking_alerts" <?php echo $settings['booking_alerts'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-group">
                <div class="toggle-label">
                    <span class="toggle-label-title">Worker Approval Requests</span>
                    <span class="toggle-label-desc">Pending worker registrations</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="worker_approvals" <?php echo $settings['worker_approvals'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-group">
                <div class="toggle-label">
                    <span class="toggle-label-title">Complaint Alerts</span>
                    <span class="toggle-label-desc">New or escalated complaints</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="complaint_alerts" <?php echo $settings['complaint_alerts'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary" <?php echo !$hasTable ? 'disabled title="admin_settings table missing"' : ''; ?>>
                    <?php echo admin_icon('check', 15); ?> Save Notifications
                </button>
            </div>
        </form>
    </div>

    <!-- ===== SYSTEM SETTINGS ===== -->
    <div class="settings-card">
        <div class="settings-card-header">
            <div class="settings-card-icon system"><?php echo admin_icon('settings', 22); ?></div>
            <div>
                <div class="settings-card-title">System Settings</div>
                <div class="settings-card-desc">Platform behaviour and configuration</div>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_system">

            <div class="toggle-group">
                <div class="toggle-label">
                    <span class="toggle-label-title">Maintenance Mode</span>
                    <span class="toggle-label-desc">Temporarily disable user access</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-group" style="margin-bottom: 18px;">
                <div class="toggle-label">
                    <span class="toggle-label-title">Allow New Registrations</span>
                    <span class="toggle-label-desc">Let new users & workers sign up</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="allow_registration" <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Max Booking Days Ahead</label>
                <input type="number" class="form-input" name="max_booking_days" min="1" max="365"
                       value="<?php echo (int) $settings['max_booking_days']; ?>" placeholder="30">
            </div>

            <div class="form-group">
                <label class="form-label">Default Currency</label>
                <select class="form-select" name="default_currency">
                    <?php foreach (['INR' => '₹ Indian Rupee', 'USD' => '$ US Dollar', 'EUR' => '€ Euro', 'GBP' => '£ British Pound'] as $code => $label): ?>
                        <option value="<?php echo $code; ?>" <?php echo ($settings['default_currency'] ?? 'INR') === $code ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary" <?php echo !$hasTable ? 'disabled title="admin_settings table missing"' : ''; ?>>
                    <?php echo admin_icon('check', 15); ?> Save System Settings
                </button>
            </div>
        </form>
    </div>

    <!-- ===== SYSTEM DIAGNOSTICS (full width) ===== -->
    <div class="settings-card settings-card-full">
        <div class="settings-card-header">
            <div class="settings-card-icon diagnostics"><?php echo admin_icon('shield', 22); ?></div>
            <div>
                <div class="settings-card-title">System Diagnostics</div>
                <div class="settings-card-desc">Live status of database tables and feature flags</div>
            </div>
        </div>

        <?php foreach ($diagnostics as [$label, $status, $type]): ?>
        <div class="diag-row">
            <span class="diag-name"><?php echo e($label); ?></span>
            <span class="diag-badge diag-<?php echo $type; ?>"><?php echo e($status); ?></span>
        </div>
        <?php endforeach; ?>

        <?php if (!$hasTable): ?>
        <div style="margin-top: 20px; padding: 16px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px;">
            <p style="font-size: 12px; font-weight: 600; color: var(--text-primary); margin-bottom: 10px;">
                <?php echo admin_icon('activity', 14); ?>
                Run this SQL to enable admin settings:
            </p>
            <pre style="font-size: 11px; color: var(--text-secondary); white-space: pre-wrap; line-height: 1.7; margin: 0;">CREATE TABLE IF NOT EXISTS admin_settings (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  admin_id            INT NOT NULL UNIQUE,
  email_notifications TINYINT(1) DEFAULT 1,
  booking_alerts      TINYINT(1) DEFAULT 1,
  worker_approvals    TINYINT(1) DEFAULT 1,
  complaint_alerts    TINYINT(1) DEFAULT 1,
  maintenance_mode    TINYINT(1) DEFAULT 0,
  allow_registration  TINYINT(1) DEFAULT 1,
  max_booking_days    INT DEFAULT 30,
  default_currency    VARCHAR(10) DEFAULT 'INR',
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</pre>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php admin_page_end(); ?>
