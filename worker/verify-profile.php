<?php
session_start();

require_once "../database/db.php";
require_once "../includes/AIService.php";
require_once "../includes/worker-functions.php";

if (!isset($_SESSION["worker_id"])) {
    header("Location: ../login.php");
    exit;
}

$worker_id = (int) $_SESSION["worker_id"];
$message = "";
$messageType = "";
$verification = null;

$workerStmt = $conn->prepare("SELECT * FROM workers WHERE id = ? LIMIT 1");
$workerStmt->bind_param("i", $worker_id);
$workerStmt->execute();
$worker = $workerStmt->get_result()->fetch_assoc();
$workerStmt->close();

if (!$worker) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION["verification_csrf_token"])) {
    $_SESSION["verification_csrf_token"] = bin2hex(random_bytes(32));
}

function getWorkerVerification(mysqli $conn, int $workerId): ?array
{
    $stmt = $conn->prepare("
        SELECT *
        FROM worker_verifications
        WHERE worker_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $workerId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result ?: null;
}

function validateVerificationUpload(array &$file, string $label, bool $required = true): ?string
{
    if (
        !isset($file["error"]) ||
        $file["error"] === UPLOAD_ERR_NO_FILE
    ) {
        return $required ? "{$label} is required." : null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        return "The {$label} upload failed. Please try again.";
    }

    if (($file["size"] ?? 0) <= 0 || $file["size"] > 8 * 1024 * 1024) {
        return "{$label} must be smaller than 8 MB.";
    }

    if (
        empty($file["tmp_name"]) ||
        !is_uploaded_file($file["tmp_name"])
    ) {
        return "The {$label} upload is invalid.";
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);
    $allowedTypes = ["image/jpeg", "image/png"];

    if (!in_array($mime, $allowedTypes, true)) {
        return "{$label} must be a JPG or PNG image.";
    }

    if (@getimagesize($file["tmp_name"]) === false) {
        return "{$label} is not a readable image.";
    }

    // Pass the MIME type detected by the server to CURLFile.
    $file["type"] = $mime;

    return null;
}

$verification = getWorkerVerification($conn, $worker_id);
$canSubmit = !$verification || ($verification["status"] ?? "") === "rejected";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["verification_csrf_token"],
            (string) $_POST["csrf_token"]
        )
    ) {
        $message = "Your session expired. Refresh the page and try again.";
        $messageType = "error";
    } elseif (!$canSubmit) {
        $message = "Your verification has already been submitted.";
        $messageType = "warning";
    } else {
        $governmentId = $_FILES["government_id"] ?? [];
        $selfie = $_FILES["selfie"] ?? [];
        $certificate = $_FILES["certificate"] ?? [];

        $errors = array_filter([
            validateVerificationUpload($governmentId, "government ID"),
            validateVerificationUpload($selfie, "selfie"),
            validateVerificationUpload($certificate, "certificate", false)
        ]);

        if ($errors) {
            $message = reset($errors);
            $messageType = "error";
        } else {
            try {
                $aiService = new AIService();
                $result = $aiService->verifyWorker(
                    $governmentId,
                    $selfie,
                    !empty($certificate["tmp_name"]) ? $certificate : null
                );

                if (!is_array($result)) {
                    throw new RuntimeException(
                        "The verification service returned an invalid response."
                    );
                }

                if (($result["success"] ?? false) !== true) {
                    $reason = $result["message"] ?? $result["reason"] ?? "";
                    throw new RuntimeException(
                        $reason ?: "The documents could not be verified."
                    );
                }

                $data = $result["data"] ?? [];
                $files = $data["files"] ?? [];
                $aiVerification = $data["verification"] ?? [];

                if (
                    empty($files["government_id"]) ||
                    empty($files["selfie"])
                ) {
                    throw new RuntimeException(
                        "The verification service did not save the required files."
                    );
                }

                $governmentIdPath = (string) $files["government_id"];
                $selfiePath = (string) $files["selfie"];
                $certificatePath = !empty($files["certificate"])
                    ? (string) $files["certificate"]
                    : null;
                $faceMatchScore = (float) (
                    $aiVerification["face_match_score"] ?? 0
                );
                $verificationScore = (int) (
                    $aiVerification["score"] ?? 0
                );
                $ocrStatus = in_array(
                    $aiVerification["ocr_status"] ?? "",
                    ["pending", "success", "failed"],
                    true
                ) ? $aiVerification["ocr_status"] : "failed";
                $status = "pending";

                $conn->begin_transaction();

                if ($verification && $verification["status"] === "rejected") {
                    $stmt = $conn->prepare("
                        UPDATE worker_verifications
                        SET government_id = ?,
                            selfie = ?,
                            certificate = ?,
                            face_match_score = ?,
                            verification_score = ?,
                            ocr_status = ?,
                            portfolio_status = 'pending',
                            status = ?,
                            admin_remark = NULL,
                            submitted_at = CURRENT_TIMESTAMP,
                            verified_at = NULL
                        WHERE id = ? AND worker_id = ?
                    ");
                    $stmt->bind_param(
                        "sssdissii",
                        $governmentIdPath,
                        $selfiePath,
                        $certificatePath,
                        $faceMatchScore,
                        $verificationScore,
                        $ocrStatus,
                        $status,
                        $verification["id"],
                        $worker_id
                    );
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO worker_verifications (
                            worker_id,
                            government_id,
                            selfie,
                            certificate,
                            face_match_score,
                            verification_score,
                            ocr_status,
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param(
                        "isssdiss",
                        $worker_id,
                        $governmentIdPath,
                        $selfiePath,
                        $certificatePath,
                        $faceMatchScore,
                        $verificationScore,
                        $ocrStatus,
                        $status
                    );
                }

                if (!$stmt->execute()) {
                    throw new RuntimeException(
                        "Your verification result could not be saved."
                    );
                }

                $stmt->close();
                $conn->commit();

                $verification = getWorkerVerification($conn, $worker_id);
                $canSubmit = false;
                $message = "Documents submitted successfully. Your verification is pending review.";
                $messageType = "success";
                $_SESSION["verification_csrf_token"] = bin2hex(random_bytes(32));
            } catch (Throwable $exception) {
                try {
                    $conn->rollback();
                } catch (Throwable $ignored) {
                }

                $message = $exception->getMessage();
                $messageType = "error";
            }
        }
    }
}

if (!$message && $verification) {
    $status = $verification["status"] ?? "pending";
    if ($status === "approved") {
        $message = "Your profile verification has been approved.";
        $messageType = "success";
    } elseif ($status === "rejected") {
        $message = $verification["admin_remark"]
            ? "Verification rejected: " . $verification["admin_remark"]
            : "Your verification was rejected. You may submit new documents.";
        $messageType = "error";
    } else {
        $message = "You have already submitted your documents.";
        $messageType = "warning";
    }
}

$pageTitle = "Profile Verification";
$pageSubtitle = "Submit your identity documents for review.";
$extraCSS = "
.verify-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr);gap:24px;align-items:start}
.verify-upload{border:1px dashed var(--border);border-radius:12px;padding:16px;background:var(--bg-secondary)}
.verify-upload input{margin-top:8px}
.verify-help{font-size:12px;color:var(--text-gray);margin-top:6px;line-height:1.5}
.verify-status{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid var(--border)}
.verify-status:last-child{border-bottom:0}
.verify-score{font-size:32px;font-weight:800;color:var(--primary);font-family:'Plus Jakarta Sans',sans-serif}
.verify-note{display:flex;gap:10px;align-items:flex-start;padding:14px;border-radius:10px;background:var(--primary-light);color:var(--text-secondary);font-size:13px;line-height:1.6}
@media(max-width:850px){.verify-grid{grid-template-columns:1fr}}
";

include "../includes/worker-page-start.php";
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo wE($messageType); ?>">
        <?php echo wGetIcon($messageType === "success" ? "check" : ($messageType === "error" ? "x" : "shield"), 18); ?>
        <span><?php echo wE($message); ?></span>
    </div>
<?php endif; ?>

<div class="verify-grid">
    <section class="card-box">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
            <div style="width:42px;height:42px;border-radius:10px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center">
                <?php echo wGetIcon("shield", 22); ?>
            </div>
            <div>
                <h2 style="font-size:17px;font-weight:700;color:var(--text-primary)">Identity documents</h2>
                <p style="font-size:12px;color:var(--text-gray);margin-top:3px">JPG or PNG only, maximum 8 MB per file.</p>
            </div>
        </div>

        <?php if ($canSubmit): ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo wE($_SESSION["verification_csrf_token"]); ?>">

                <div class="form-group verify-upload">
                    <label class="form-label" for="government_id">Government ID *</label>
                    <input class="form-input" id="government_id" type="file" name="government_id" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                    <div class="verify-help">Upload a clear Aadhaar, PAN, driving licence, voter ID, or passport image. The photo and text must be readable.</div>
                </div>

                <div class="form-group verify-upload">
                    <label class="form-label" for="selfie">Recent selfie *</label>
                    <input class="form-input" id="selfie" type="file" name="selfie" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                    <div class="verify-help">Use a well-lit, front-facing photo without sunglasses, filters, or other people.</div>
                </div>

                <div class="form-group verify-upload">
                    <label class="form-label" for="certificate">Professional certificate <span style="font-weight:400">(optional)</span></label>
                    <input class="form-input" id="certificate" type="file" name="certificate" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                    <div class="verify-help">Upload a clear image of a licence, course certificate, or trade qualification.</div>
                </div>

                <label style="display:flex;gap:10px;align-items:flex-start;font-size:12px;color:var(--text-secondary);margin:18px 0">
                    <input type="checkbox" required style="margin-top:3px">
                    <span>I confirm that these documents belong to me and may be processed for identity verification.</span>
                </label>

                <button type="submit" class="btn-primary">
                    <?php echo wGetIcon("shield", 16); ?>
                    Submit for verification
                </button>
            </form>
        <?php else: ?>
            <div style="text-align:center;padding:34px 16px">
                <div style="width:64px;height:64px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                    <?php echo wGetIcon(($verification["status"] ?? "") === "approved" ? "check" : "clock", 30); ?>
                </div>
                <h3 style="font-size:18px;font-weight:700;color:var(--text-primary)">
                    <?php echo ($verification["status"] ?? "") === "approved" ? "Verification approved" : "Review in progress"; ?>
                </h3>
                <p style="font-size:13px;color:var(--text-gray);margin-top:8px">
                    Submitted <?php echo wE(date("d M Y, g:i A", strtotime($verification["submitted_at"]))); ?>
                </p>
            </div>
        <?php endif; ?>
    </section>

    <aside class="card-box">
        <h3 style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:8px">Verification status</h3>

        <?php if ($verification): ?>
            <div class="verify-status">
                <span style="font-size:13px;color:var(--text-secondary)">Current status</span>
                <?php echo wStatusBadge($verification["status"]); ?>
            </div>
            <div class="verify-status">
                <span style="font-size:13px;color:var(--text-secondary)">Document scan</span>
                <?php echo wStatusBadge($verification["ocr_status"]); ?>
            </div>
            <div class="verify-status">
                <span style="font-size:13px;color:var(--text-secondary)">Verification score</span>
                <span class="verify-score"><?php echo (int) $verification["verification_score"]; ?><small style="font-size:13px;color:var(--text-gray)">/100</small></span>
            </div>
            <?php if ($verification["face_match_score"] !== null): ?>
                <div class="verify-status">
                    <span style="font-size:13px;color:var(--text-secondary)">Face match</span>
                    <strong style="font-size:14px;color:var(--text-primary)"><?php echo wE(number_format((float) $verification["face_match_score"], 1)); ?>%</strong>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="font-size:13px;color:var(--text-gray);line-height:1.6;margin:12px 0 20px">No verification request has been submitted yet.</p>
        <?php endif; ?>

        <div class="verify-note" style="margin-top:18px">
            <?php echo wGetIcon("shield", 18); ?>
            <span>Your documents are used only for verification. Make sure every image is clear before submitting.</span>
        </div>
    </aside>
</div>

<?php include "../includes/worker-page-end.php"; ?>
