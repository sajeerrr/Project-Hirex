<?php
require_once(__DIR__ . '/includes/functions.php');

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ? OR role LIKE ? OR location LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

if ($status !== '' && admin_column_exists($conn, 'workers', 'status')) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql = "SELECT * FROM workers" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$workers = $stmt->get_result();

admin_page_start('Workers', 'workers', 'Review worker profiles, availability, and account status.');
?>
<div class="grid">
    <div class="card"><div class="stat-label">Total Workers</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM workers"); ?></div></div>
    <div class="card"><div class="stat-label">Available</div><div class="stat-value"><?php echo admin_scalar($conn, "SELECT COUNT(*) FROM workers WHERE available=1"); ?></div></div>
    <div class="card"><div class="stat-label">Pending</div><div class="stat-value"><?php echo admin_column_exists($conn, 'workers', 'status') ? admin_scalar($conn, "SELECT COUNT(*) FROM workers WHERE status='pending'") : 0; ?></div></div>
    <div class="card"><div class="stat-label">Avg Rating</div><div class="stat-value"><?php echo number_format((float) admin_scalar($conn, "SELECT AVG(rating) FROM workers"), 1); ?></div></div>
</div>
<section class="table-card">
    <form class="toolbar" method="get">
        <input class="search" type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search workers">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['active', 'pending', 'approved', 'rejected', 'suspended'] as $option): ?>
                <option value="<?php echo e($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo e(ucwords($option)); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <table>
        <thead><tr><th>Name</th><th>Role</th><th>Contact</th><th>Rate</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($workers->num_rows): while ($worker = $workers->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo e($worker['name']); ?></strong><br><span class="subtitle"><?php echo e($worker['location']); ?></span></td>
                <td><?php echo e($worker['role']); ?><br><span class="subtitle"><?php echo e($worker['experience']); ?></span></td>
                <td><?php echo e($worker['email']); ?><br><span class="subtitle"><?php echo e($worker['phone'] ?? ''); ?></span></td>
                <td><?php echo admin_money($worker['price'] ?? 0); ?>/hr</td>
                <td><?php echo e($worker['rating'] ?? 0); ?> (<?php echo e($worker['reviews'] ?? 0); ?>)</td>
                <td><span class="status <?php echo admin_status_class($worker['status'] ?? 'approved'); ?>"><?php echo e($worker['status'] ?? 'approved'); ?></span></td>
                <td><?php admin_status_form('workers', $worker['id'], $worker['status'] ?? 'active', ['active', 'pending', 'approved', 'rejected', 'suspended']); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td class="empty" colspan="7">No workers found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_page_end(); ?>
