<?php
session_start();
require_once(__DIR__ . '/../../database/db.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../verifications.php');
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    header('Location: ../verifications.php?error=csrf');
    exit;
}

$verificationId = (int) ($_POST['verification_id'] ?? 0);
$action = $_POST['action'] ?? '';
$remark = trim($_POST['admin_remark'] ?? '');
$allowedActions = ['approve', 'reject', 'resubmit', 'delete'];

if ($verificationId <= 0 || !in_array($action, $allowedActions, true)) {
    header('Location: ../verifications.php?error=invalid');
    exit;
}

if (in_array($action, ['reject', 'resubmit'], true) && $remark === '') {
    header('Location: ../verifications.php?error=remark_required');
    exit;
}

if (mb_strlen($remark) > 1000) {
    header('Location: ../verifications.php?error=remark_too_long');
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT worker_id, status, government_id, selfie, certificate
        FROM worker_verifications
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->bind_param('i', $verificationId);
    $stmt->execute();
    $verification = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$verification) {
        throw new RuntimeException('Verification submission not found.');
    }

    $workerId = (int) $verification['worker_id'];
    $filesToDelete = [];

    if ($action === 'delete') {
        $filesToDelete = array_filter([
            $verification['government_id'],
            $verification['selfie'],
            $verification['certificate']
        ]);

        $stmt = $conn->prepare(
            "DELETE FROM worker_verifications WHERE id = ?"
        );
        $stmt->bind_param('i', $verificationId);
        $stmt->execute();
        $stmt->close();

        $workerStatus = 'pending';
    } elseif ($action === 'approve') {
        $verificationStatus = 'approved';
        $workerStatus = 'approved';
        $savedRemark = null;

        $stmt = $conn->prepare("
            UPDATE worker_verifications
            SET status = ?, admin_remark = ?, verified_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
    } else {
        // The current schema has no separate resubmission status. Rejected
        // submissions are resubmittable on the worker verification page.
        $verificationStatus = 'rejected';
        $workerStatus = $action === 'resubmit' ? 'pending' : 'rejected';
        $savedRemark = $action === 'resubmit'
            ? 'Resubmission requested: ' . $remark
            : $remark;

        $stmt = $conn->prepare("
            UPDATE worker_verifications
            SET status = ?, admin_remark = ?, verified_at = NULL
            WHERE id = ?
        ");
    }

    if ($action !== 'delete') {
        $stmt->bind_param(
            'ssi',
            $verificationStatus,
            $savedRemark,
            $verificationId
        );
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("UPDATE workers SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $workerStatus, $workerId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    if ($action === 'delete') {
        $uploadRoot = realpath(__DIR__ . '/../../hirex-ai/uploads');

        if ($uploadRoot !== false) {
            foreach ($filesToDelete as $storedFile) {
                $storedFile = str_replace(
                    ['/', '\\'],
                    DIRECTORY_SEPARATOR,
                    $storedFile
                );
                $candidate = realpath(
                    __DIR__ .
                    '/../../hirex-ai/' .
                    ltrim($storedFile, DIRECTORY_SEPARATOR)
                );

                if (
                    $candidate !== false &&
                    is_file($candidate) &&
                    strpos(
                        $candidate,
                        $uploadRoot . DIRECTORY_SEPARATOR
                    ) === 0
                ) {
                    @unlink($candidate);
                }
            }
        }
    }

    header('Location: ../verifications.php?updated=1&status=pending');
    exit;
} catch (Throwable $exception) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    error_log(
        'Verification review failed for ID ' .
        $verificationId .
        ': ' .
        $exception->getMessage()
    );
    header('Location: ../verifications.php?error=1');
    exit;
}
