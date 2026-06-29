<?php
require_once(__DIR__ . '/includes/functions.php');

if (!admin_table_exists($conn, 'worker_verifications')) {
    admin_page_start('Verifications', 'verifications', 'Review worker identity verification submissions.');
    ?>
    <section class="table-card">
        <div class="empty">The worker_verifications table is not available.</div>
    </section>
    <?php
    admin_page_end();
    exit;
}

$allowedStatuses = ['all', 'pending', 'processing', 'approved', 'rejected'];
$status = $_GET['status'] ?? 'pending';
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'pending';
}

$sql = "
    SELECT
        v.*,
        w.name AS worker_name,
        w.email AS worker_email,
        w.phone AS worker_phone,
        w.role AS worker_role,
        w.photo AS worker_photo,
        w.status AS worker_status
    FROM worker_verifications v
    INNER JOIN workers w ON w.id = v.worker_id
";

if ($status !== 'all') {
    $sql .= " WHERE v.status = ?";
}

$sql .= " ORDER BY
    CASE v.status
        WHEN 'pending' THEN 1
        WHEN 'processing' THEN 2
        WHEN 'rejected' THEN 3
        WHEN 'approved' THEN 4
        ELSE 5
    END,
    v.submitted_at ASC
";

$stmt = $conn->prepare($sql);
if ($status !== 'all') {
    $stmt->bind_param('s', $status);
}
$stmt->execute();
$verifications = $stmt->get_result();

admin_page_start(
    'Worker Verifications',
    'verifications',
    'Review AI-screened documents and make the final verification decision.'
);
?>

<div class="grid">
    <div class="card">
        <div class="stat-label">Awaiting Review</div>
        <div class="stat-value"><?php echo (int) admin_scalar($conn, "SELECT COUNT(*) FROM worker_verifications WHERE status='pending'"); ?></div>
    </div>
    <div class="card">
        <div class="stat-label">Processing</div>
        <div class="stat-value"><?php echo (int) admin_scalar($conn, "SELECT COUNT(*) FROM worker_verifications WHERE status='processing'"); ?></div>
    </div>
    <div class="card">
        <div class="stat-label">Approved</div>
        <div class="stat-value"><?php echo (int) admin_scalar($conn, "SELECT COUNT(*) FROM worker_verifications WHERE status='approved'"); ?></div>
    </div>
    <div class="card">
        <div class="stat-label">Rejected</div>
        <div class="stat-value"><?php echo (int) admin_scalar($conn, "SELECT COUNT(*) FROM worker_verifications WHERE status='rejected'"); ?></div>
    </div>
</div>

<section class="table-card" style="margin-bottom:20px">
    <form class="toolbar" method="get">
        <strong>Review queue</strong>
        <select name="status">
            <?php foreach ($allowedStatuses as $option): ?>
                <option value="<?php echo e($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>>
                    <?php echo e($option === 'all' ? 'All submissions' : ucwords($option)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
</section>

<?php if ($verifications->num_rows === 0): ?>
    <section class="table-card">
        <div class="empty">No verification submissions found for this filter.</div>
    </section>
<?php else: ?>
    <div style="display:grid;gap:20px">
        <?php while ($verification = $verifications->fetch_assoc()): ?>
            <section class="table-card" style="padding:22px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap;margin-bottom:20px">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <h2 style="font-size:18px"><?php echo e($verification['worker_name']); ?></h2>
                            <span class="status <?php echo admin_status_class($verification['status']); ?>">
                                <?php echo e(ucwords($verification['status'])); ?>
                            </span>
                        </div>
                        <div class="subtitle" style="margin-top:5px">
                            <?php echo e($verification['worker_role']); ?> ·
                            <?php echo e($verification['worker_email']); ?>
                            <?php if (!empty($verification['worker_phone'])): ?>
                                · <?php echo e($verification['worker_phone']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="subtitle" style="margin-top:3px">
                            Submitted <?php echo e(admin_date($verification['submitted_at'], 'M d, Y g:i A')); ?>
                        </div>
                    </div>

                    <div style="display:flex;gap:22px;text-align:center">
                        <div>
                            <div class="stat-label">AI Score</div>
                            <div style="font-size:25px;font-weight:800;color:var(--primary)">
                                <?php echo (int) $verification['verification_score']; ?><small style="font-size:12px;color:var(--text-gray)">/100</small>
                            </div>
                        </div>
                        <div>
                            <div class="stat-label">Face Match</div>
                            <div style="font-size:25px;font-weight:800;color:var(--text-primary)">
                                <?php echo number_format((float) $verification['face_match_score'], 1); ?><small style="font-size:12px;color:var(--text-gray)">%</small>
                            </div>
                        </div>
                        <div>
                            <div class="stat-label">OCR</div>
                            <span class="status <?php echo admin_status_class($verification['ocr_status'] === 'success' ? 'approved' : $verification['ocr_status']); ?>">
                                <?php echo e(ucwords($verification['ocr_status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:20px">
                    <?php
                    $documents = [
                        'government_id' => 'Government ID',
                        'selfie' => 'Selfie',
                        'certificate' => 'Certificate'
                    ];
                    foreach ($documents as $type => $label):
                        if (empty($verification[$type])) continue;
                        $fileUrl = 'actions/view_verification_file.php?id=' . (int) $verification['id'] . '&type=' . urlencode($type);
                    ?>
                        <a href="<?php echo e($fileUrl); ?>" target="_blank" rel="noopener" style="display:block;text-decoration:none;color:inherit;border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--bg)">
                            <img
                                src="<?php echo e($fileUrl); ?>"
                                alt="<?php echo e($label); ?>"
                                loading="lazy"
                                style="display:block;width:100%;height:180px;object-fit:contain;background:#111"
                            >
                            <div style="padding:10px 12px;font-size:13px;font-weight:700"><?php echo e($label); ?> · Open</div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($verification['admin_remark'])): ?>
                    <div style="padding:12px 14px;border-radius:10px;background:rgba(239,68,68,.08);color:var(--text-secondary);margin-bottom:18px">
                        <strong>Remark:</strong> <?php echo e($verification['admin_remark']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="actions/review_verification.php" style="border-top:1px solid var(--border);padding-top:18px">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="verification_id" value="<?php echo (int) $verification['id']; ?>">

                        <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px" for="remark-<?php echo (int) $verification['id']; ?>">
                            Admin remark <span class="subtitle">(required for reject or resubmission)</span>
                        </label>
                        <textarea
                            id="remark-<?php echo (int) $verification['id']; ?>"
                            name="admin_remark"
                            rows="2"
                            maxlength="1000"
                            placeholder="Explain why the submission is rejected or what the worker must upload again."
                            style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:9px;background:var(--bg-secondary);color:var(--text-primary);resize:vertical;margin-bottom:12px"
                        ></textarea>

                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <?php if ($verification['status'] !== 'approved'): ?>
                                <button class="btn btn-primary" type="submit" name="action" value="approve">Approve</button>
                            <?php endif; ?>
                            <button class="btn" type="submit" name="action" value="resubmit" style="background:#f59e0b;color:#fff">Request Resubmission</button>
                            <?php if ($verification['status'] !== 'rejected'): ?>
                                <button class="btn" type="submit" name="action" value="reject" style="background:var(--danger);color:#fff">Reject</button>
                            <?php endif; ?>
                            <button
                                class="btn"
                                type="submit"
                                name="action"
                                value="delete"
                                style="margin-left:auto;background:#475569;color:#fff"
                                onclick="return confirm('Delete this verification and its uploaded documents? The worker will be able to submit again.');"
                            >Delete Submission</button>
                        </div>
                </form>
            </section>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php
$stmt->close();
admin_page_end();
?>
