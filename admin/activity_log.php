<?php
require_once(__DIR__ . '/includes/functions.php');

if (admin_table_exists($conn, 'admin_logs')) {
    $logs = $conn->query("
        SELECT l.*, a.name AS admin_name
        FROM admin_logs l
        LEFT JOIN admin a ON l.admin_id = a.id
        ORDER BY l.created_at DESC
        LIMIT 100
    ");
} else {
    $logs = false;
}

admin_page_start('Activity Log', 'activity_log', 'Recent admin actions and audit events.');
?>
<section class="table-card">
    <table>
        <thead><tr><th>Admin</th><th>Action</th><th>Details</th><th>Date</th></tr></thead>
        <tbody>
        <?php if ($logs && $logs->num_rows): while ($log = $logs->fetch_assoc()): ?>
            <tr>
                <td><?php echo e($log['admin_name'] ?? 'System'); ?></td>
                <td><strong><?php echo e($log['action']); ?></strong></td>
                <td><?php echo e($log['details'] ?? ''); ?></td>
                <td><?php echo admin_date($log['created_at'], 'M d, Y h:i A'); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="4">No admin activity found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

