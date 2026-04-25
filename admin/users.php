<?php
require_once(__DIR__ . '/includes/functions.php');

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = " WHERE name LIKE ? OR email LIKE ? OR location LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$stmt = $conn->prepare("SELECT * FROM users" . $where . " ORDER BY id DESC");
if ($params) {
    $stmt->bind_param("sss", ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

admin_page_start('Users', 'users', 'Manage customer accounts and basic profile information.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Total Users</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM users"); ?></div></div>
    <div class="card"><div class="stat-label">Active</div><div class="stat-value"><?php echo admin_column_exists($conn, 'users', 'status') ? admin_scalar($conn, "SELECT COUNT(*) FROM users WHERE status='active'") : admin_scalar($conn, "SELECT COUNT(*) FROM users"); ?></div></div>
    <div class="card"><div class="stat-label">Bookings</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM bookings"); ?></div></div>
    <div class="card"><div class="stat-label">Messages</div><div class="stat-value"><?php echo admin_table_exists($conn, 'messages') ? admin_scalar($conn, "SELECT COUNT(*) FROM messages") : 0; ?></div></div>
</div>
<section class="table-card">
    <form class="toolbar" method="get">
        <input class="search" type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search users">
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
    <table>
        <thead><tr><th>User</th><th>Contact</th><th>Location</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($users->num_rows): while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo e($user['name']); ?></strong><br><span class="subtitle">#<?php echo (int) $user['id']; ?></span></td>
                <td><?php echo e($user['email']); ?><br><span class="subtitle"><?php echo e($user['phone']); ?></span></td>
                <td><?php echo e($user['location']); ?></td>
                <td><?php echo admin_date($user['created_at']); ?></td>
                <td><span class="status <?php echo admin_status_class($user['status'] ?? 'active'); ?>"><?php echo e($user['status'] ?? 'active'); ?></span></td>
                <td><?php admin_status_form('users', $user['id'], $user['status'] ?? 'active', ['active', 'inactive', 'banned']); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="6">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

