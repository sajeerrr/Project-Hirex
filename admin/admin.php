<?php
require_once(__DIR__ . '/includes/functions.php');

$admins = $conn->query("SELECT * FROM admin ORDER BY id DESC");

admin_page_start('Admin Accounts', 'admin', 'Manage platform administrator accounts.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Admins</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM admin"); ?></div></div>
    <div class="card"><div class="stat-label">Active</div><div class="stat-value"><?php echo admin_column_exists($conn, 'admin', 'status') ? admin_scalar($conn, "SELECT COUNT(*) FROM admin WHERE status='active'") : admin_scalar($conn, "SELECT COUNT(*) FROM admin"); ?></div></div>
    <div class="card"><div class="stat-label">Role</div><div class="stat-value">Admin</div></div>
    <div class="card"><div class="stat-label">Current ID</div><div class="stat-value">#<?php echo (int) $_SESSION['admin_id']; ?></div></div>
</div>
<section class="table-card">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($admins && $admins->num_rows): while ($admin = $admins->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo e($admin['name']); ?></strong><br><span class="subtitle">#<?php echo (int) $admin['id']; ?></span></td>
                <td><?php echo e($admin['email']); ?></td>
                <td><?php echo e($admin['role']); ?></td>
                <td><span class="status <?php echo admin_status_class($admin['status'] ?? 'active'); ?>"><?php echo e($admin['status'] ?? 'active'); ?></span></td>
                <td><?php admin_status_form('admin', $admin['id'], $admin['status'] ?? 'active', ['active', 'inactive']); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="5">No admin accounts found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

