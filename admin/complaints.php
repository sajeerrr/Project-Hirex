<?php
require_once(__DIR__ . '/includes/functions.php');

$contacts = $conn->query("
    SELECT c.*, u.name AS user_name
    FROM contacts c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");

admin_page_start('Complaints', 'complaints', 'Handle support messages and customer complaints.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Total Messages</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM contacts"); ?></div></div>
    <div class="card"><div class="stat-label">Pending</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM contacts WHERE status='pending'"); ?></div></div>
    <div class="card"><div class="stat-label">Resolved</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM contacts WHERE status='resolved'"); ?></div></div>
    <div class="card"><div class="stat-label">Categories</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(DISTINCT category) FROM contacts"); ?></div></div>
</div>
<section class="table-card">
    <table>
        <thead><tr><th>From</th><th>Subject</th><th>Category</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($contacts && $contacts->num_rows): while ($contact = $contacts->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo e($contact['name']); ?></strong><br><span class="subtitle"><?php echo e($contact['email']); ?></span></td>
                <td><?php echo e($contact['subject']); ?></td>
                <td><?php echo e($contact['category']); ?></td>
                <td><?php echo e($contact['message']); ?></td>
                <td><span class="status <?php echo admin_status_class($contact['status']); ?>"><?php echo e($contact['status']); ?></span></td>
                <td><?php echo admin_date($contact['created_at']); ?></td>
                <td><?php admin_status_form('contacts', $contact['id'], $contact['status'], ['pending', 'open', 'resolved']); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="7">No complaints found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

