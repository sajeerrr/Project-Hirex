<?php
require_once(__DIR__ . '/includes/functions.php');

$categories = $conn->query("
    SELECT role, COUNT(*) AS workers, AVG(rating) AS avg_rating, AVG(price) AS avg_price, SUM(jobs) AS jobs
    FROM workers
    WHERE role IS NOT NULL AND role != ''
    GROUP BY role
    ORDER BY workers DESC, role ASC
");

admin_page_start('Categories', 'categories', 'Service categories are currently derived from worker roles.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Categories</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(DISTINCT role) FROM workers WHERE role IS NOT NULL AND role != ''"); ?></div></div>
    <div class="card"><div class="stat-label">Workers</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM workers"); ?></div></div>
    <div class="card"><div class="stat-label">Avg Price</div><div class="stat-value"><?php echo admin_money(admin_scalar($conn, "SELECT AVG(price) FROM workers")); ?></div></div>
    <div class="card"><div class="stat-label">Total Jobs</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT SUM(jobs) FROM workers"); ?></div></div>
</div>
<section class="table-card">
    <div class="toolbar"><strong>Worker Role Categories</strong><span class="subtitle">Add or edit worker roles from worker profiles.</span></div>
    <table>
        <thead><tr><th>Category</th><th>Workers</th><th>Avg Rating</th><th>Avg Price</th><th>Jobs</th></tr></thead>
        <tbody>
        <?php if ($categories && $categories->num_rows): while ($category = $categories->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo e($category['role']); ?></strong></td>
                <td><?php echo (int) $category['workers']; ?></td>
                <td><?php echo number_format((float) $category['avg_rating'], 1); ?></td>
                <td><?php echo admin_money($category['avg_price']); ?></td>
                <td><?php echo (int) $category['jobs']; ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="5">No categories found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

