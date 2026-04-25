<?php
require_once(__DIR__ . '/includes/functions.php');

$cities = $conn->query("
    SELECT location, COUNT(*) AS workers, AVG(rating) AS avg_rating, SUM(available = 1) AS available_workers
    FROM workers
    WHERE location IS NOT NULL AND location != ''
    GROUP BY location
    ORDER BY workers DESC, location ASC
");

admin_page_start('Cities', 'cities', 'Coverage by worker location.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Cities</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(DISTINCT location) FROM workers WHERE location IS NOT NULL AND location != ''"); ?></div></div>
    <div class="card"><div class="stat-label">Available Workers</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM workers WHERE available=1"); ?></div></div>
    <div class="card"><div class="stat-label">User Locations</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(DISTINCT location) FROM users WHERE location IS NOT NULL AND location != ''"); ?></div></div>
    <div class="card"><div class="stat-label">Bookings</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM bookings"); ?></div></div>
</div>
<section class="table-card">
    <table>
        <thead><tr><th>City</th><th>Workers</th><th>Available</th><th>Avg Rating</th></tr></thead>
        <tbody>
        <?php if ($cities && $cities->num_rows): while ($city = $cities->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo e($city['location']); ?></strong></td>
                <td><?php echo (int) $city['workers']; ?></td>
                <td><?php echo (int) $city['available_workers']; ?></td>
                <td><?php echo number_format((float) $city['avg_rating'], 1); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="4">No cities found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>

