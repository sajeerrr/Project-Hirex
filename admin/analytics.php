<?php
require_once(__DIR__ . '/includes/functions.php');

$bookingStatuses = $conn->query("SELECT status, COUNT(*) AS total, SUM(total_amount) AS amount FROM bookings GROUP BY status ORDER BY total DESC");
$requestStatuses = admin_table_exists($conn, 'requests') ? $conn->query("SELECT status, COUNT(*) AS total, SUM(amount) AS amount FROM requests GROUP BY status ORDER BY total DESC") : false;
$topWorkers = $conn->query("SELECT name, role, rating, jobs, reviews FROM workers ORDER BY jobs DESC, rating DESC LIMIT 8");

admin_page_start('Analytics', 'analytics', 'Platform health, revenue, and worker performance.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Users</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM users"); ?></div></div>
    <div class="card"><div class="stat-label">Workers</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM workers"); ?></div></div>
    <div class="card"><div class="stat-label">Booking Value</div><div class="stat-value"><?php echo admin_money(admin_scalar($conn, "SELECT SUM(total_amount) FROM bookings")); ?></div></div>
    <div class="card"><div class="stat-label">Completed Requests</div><div class="stat-value"><?php echo admin_table_exists($conn, 'requests') ? admin_money(admin_scalar($conn, "SELECT SUM(amount) FROM requests WHERE status='completed'")) : admin_money(0); ?></div></div>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr;">
    <section class="table-card">
        <div class="toolbar"><strong>Booking Status</strong></div>
        <table><thead><tr><th>Status</th><th>Total</th><th>Amount</th></tr></thead><tbody>
        <?php if ($bookingStatuses && $bookingStatuses->num_rows): while ($row = $bookingStatuses->fetch_assoc()): ?>
            <tr><td><span class="status <?php echo admin_status_class($row['status']); ?>"><?php echo e($row['status']); ?></span></td><td><?php echo (int) $row['total']; ?></td><td><?php echo admin_money($row['amount']); ?></td></tr>
        <?php endwhile; else: ?><tr><td class="empty" colspan="3">No booking data.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
    <section class="table-card">
        <div class="toolbar"><strong>Request Status</strong></div>
        <table><thead><tr><th>Status</th><th>Total</th><th>Amount</th></tr></thead><tbody>
        <?php if ($requestStatuses && $requestStatuses->num_rows): while ($row = $requestStatuses->fetch_assoc()): ?>
            <tr><td><span class="status <?php echo admin_status_class($row['status']); ?>"><?php echo e($row['status']); ?></span></td><td><?php echo (int) $row['total']; ?></td><td><?php echo admin_money($row['amount']); ?></td></tr>
        <?php endwhile; else: ?><tr><td class="empty" colspan="3">No request data.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
</div>
<section class="table-card">
    <div class="toolbar"><strong>Top Workers</strong></div>
    <table><thead><tr><th>Name</th><th>Role</th><th>Rating</th><th>Jobs</th><th>Reviews</th></tr></thead><tbody>
    <?php if ($topWorkers && $topWorkers->num_rows): while ($worker = $topWorkers->fetch_assoc()): ?>
        <tr><td><?php echo e($worker['name']); ?></td><td><?php echo e($worker['role']); ?></td><td><?php echo e($worker['rating']); ?></td><td><?php echo e($worker['jobs']); ?></td><td><?php echo e($worker['reviews']); ?></td></tr>
    <?php endwhile; else: ?><tr><td class="empty" colspan="5">No worker data.</td></tr><?php endif; ?>
    </tbody></table>
</section>
<?php admin_page_end(); ?>

