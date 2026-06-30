<?php
require_once(__DIR__ . '/includes/functions.php');

// Determine which table to query: prefer 'complaints', fall back to 'contacts'
$useComplaints = admin_table_exists($conn, 'complaints');
$useContacts   = admin_table_exists($conn, 'contacts');

if ($useComplaints) {
    // Query from the complaints table (linked to users and optionally workers)
    $rows = $conn->query("
        SELECT c.*, u.name AS user_name, u.email AS user_email, w.name AS worker_name
        FROM complaints c
        LEFT JOIN users u   ON c.user_id   = u.id
        LEFT JOIN workers w ON c.worker_id = w.id
        ORDER BY c.created_at DESC
    ");

    $totalCount    = admin_scalar($conn, "SELECT COUNT(*) FROM complaints");
    $pendingCount  = admin_scalar($conn, "SELECT COUNT(*) FROM complaints WHERE status='pending'");
    $resolvedCount = admin_scalar($conn, "SELECT COUNT(*) FROM complaints WHERE status='resolved'");
    $highPriority  = admin_scalar($conn, "SELECT COUNT(*) FROM complaints WHERE priority='high'");

    $tableName      = 'complaints';
    $statusOptions  = ['pending', 'in_progress', 'resolved'];
    $sourceLabel    = 'complaints';
} elseif ($useContacts) {
    // Fallback: query from the contacts table
    $rows = $conn->query("
        SELECT c.*, u.name AS user_name, u.email AS user_email, NULL AS worker_name
        FROM contacts c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.created_at DESC
    ");

    $totalCount    = admin_scalar($conn, "SELECT COUNT(*) FROM contacts");
    $pendingCount  = admin_scalar($conn, "SELECT COUNT(*) FROM contacts WHERE status='pending'");
    $resolvedCount = admin_scalar($conn, "SELECT COUNT(*) FROM contacts WHERE status='resolved'");
    $highPriority  = 0;

    $tableName      = 'contacts';
    $statusOptions  = ['pending', 'open', 'resolved'];
    $sourceLabel    = 'contacts';
} else {
    $rows           = false;
    $totalCount     = 0;
    $pendingCount   = 0;
    $resolvedCount  = 0;
    $highPriority   = 0;
    $tableName      = '';
    $statusOptions  = [];
    $sourceLabel    = '';
}

admin_page_start('Complaints', 'complaints', 'Handle support messages and customer complaints.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Total</div><div class="stat-value"><?php echo $totalCount; ?></div></div>
    <div class="card"><div class="stat-label">Pending</div><div class="stat-value"><?php echo $pendingCount; ?></div></div>
    <div class="card"><div class="stat-label">Resolved</div><div class="stat-value"><?php echo $resolvedCount; ?></div></div>
    <div class="card"><div class="stat-label">High Priority</div><div class="stat-value"><?php echo $highPriority; ?></div></div>
</div>
<section class="table-card">
    <table>
        <thead><tr><th>From</th><th>Worker</th><th>Subject</th><th>Message</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($rows && $rows->num_rows): while ($row = $rows->fetch_assoc()): ?>
            <tr>
                <td>
                    <strong><?php echo e($row['user_name'] ?? $row['name'] ?? 'Unknown'); ?></strong><br>
                    <span class="subtitle"><?php echo e($row['user_email'] ?? $row['email'] ?? ''); ?></span>
                </td>
                <td><?php echo e($row['worker_name'] ?? '—'); ?></td>
                <td><?php echo e($row['subject'] ?? '—'); ?></td>
                <td><?php echo e($row['message']); ?></td>
                <td>
                    <?php
                    $priority = $row['priority'] ?? 'medium';
                    $prioClass = $priority === 'high' ? 'status-bad' : ($priority === 'medium' ? 'status-warn' : 'status-good');
                    ?>
                    <span class="status <?php echo $prioClass; ?>"><?php echo e(ucfirst($priority)); ?></span>
                </td>
                <td><span class="status <?php echo admin_status_class($row['status']); ?>"><?php echo e(ucwords(str_replace('_', ' ', $row['status']))); ?></span></td>
                <td><?php echo admin_date($row['created_at']); ?></td>
                <td><?php admin_status_form($tableName, $row['id'], $row['status'], $statusOptions); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="8">No complaints found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>
