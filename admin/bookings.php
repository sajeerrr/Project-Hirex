<?php
require_once(__DIR__ . '/includes/functions.php');

$status = trim($_GET['status'] ?? '');
$where = $status !== '' ? " WHERE b.status = ?" : "";
$stmt = $conn->prepare("
    SELECT b.*, u.name AS user_name, w.name AS worker_name, w.role AS worker_role
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN workers w ON b.worker_id = w.id
    $where
    ORDER BY b.created_at DESC
");
if ($status !== '') {
    $stmt->bind_param("s", $status);
}
$stmt->execute();
$bookings = $stmt->get_result();

admin_page_start('Bookings', 'bookings', 'Track booking requests, confirmations, completions, and cancellations.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">All Bookings</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM bookings"); ?></div></div>
    <div class="card"><div class="stat-label">Pending</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM bookings WHERE status='pending'"); ?></div></div>
    <div class="card"><div class="stat-label">Completed</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM bookings WHERE status='completed'"); ?></div></div>
    <div class="card"><div class="stat-label">Value</div><div class="stat-value"><?php echo admin_money(admin_scalar($conn, "SELECT SUM(total_amount) FROM bookings")); ?></div></div>
</div>
<section class="table-card">
    <form class="toolbar" method="get">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $option): ?>
                <option value="<?php echo e($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo e(ucwords($option)); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <table>
        <thead><tr><th>ID</th><th>User</th><th>Worker</th><th>Schedule</th><th>Address</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($bookings->num_rows): while ($booking = $bookings->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo (int) $booking['id']; ?></td>
                <td><?php echo e($booking['user_name']); ?></td>
                <td><?php echo e($booking['worker_name']); ?><br><span class="subtitle"><?php echo e($booking['worker_role']); ?></span></td>
                <td><?php echo admin_date($booking['booking_date'], 'M d, Y'); ?><br><span class="subtitle"><?php echo e($booking['booking_time']); ?></span></td>
                <td><?php echo e($booking['address']); ?></td>
                <td><?php echo admin_money($booking['total_amount']); ?></td>
                <td><span class="status <?php echo admin_status_class($booking['status']); ?>"><?php echo e($booking['status']); ?></span></td>
                <td><?php admin_status_form('bookings', $booking['id'], $booking['status'], ['pending', 'confirmed', 'completed', 'cancelled']); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="8">No bookings found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

