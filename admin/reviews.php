<?php
require_once(__DIR__ . '/includes/functions.php');

$reviews = $conn->query("
    SELECT r.*, u.name AS user_name, w.name AS worker_name, w.role AS worker_role
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN workers w ON r.worker_id = w.id
    ORDER BY r.created_at DESC
");

admin_page_start('Reviews', 'reviews', 'Monitor user feedback and worker ratings.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Reviews</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM reviews"); ?></div></div>
    <div class="card"><div class="stat-label">Average Rating</div><div class="stat-value"><?php echo number_format((float) admin_scalar($conn, "SELECT AVG(rating) FROM reviews"), 1); ?></div></div>
    <div class="card"><div class="stat-label">Worker Avg</div><div class="stat-value"><?php echo number_format((float) admin_scalar($conn, "SELECT AVG(rating) FROM workers"), 1); ?></div></div>
    <div class="card"><div class="stat-label">Rated Workers</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM workers WHERE rating > 0"); ?></div></div>
</div>
<section class="table-card">
    <table>
        <thead><tr><th>User</th><th>Worker</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
        <tbody>
        <?php if ($reviews && $reviews->num_rows): while ($review = $reviews->fetch_assoc()): ?>
            <tr>
                <td><?php echo e($review['user_name']); ?></td>
                <td><?php echo e($review['worker_name']); ?><br><span class="subtitle"><?php echo e($review['worker_role']); ?></span></td>
                <td><?php echo e($review['rating']); ?>/5</td>
                <td><?php echo e($review['comment']); ?></td>
                <td><?php echo admin_date($review['created_at']); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="5">No reviews found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

