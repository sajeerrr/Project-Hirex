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
        $remark = trim((string) ($verification["admin_remark"] ?? ""));
        $isResubmission = stripos(
            $remark,
            "Resubmission requested:"
        ) === 0;

        if ($isResubmission) {
            $instructions = trim(substr(
                $remark,
                strlen("Resubmission requested:")
            ));
            $message = "Admin requested new documents";
            if ($instructions !== "") {
                $message .= ": " . $instructions;
            }
            $messageType = "warning";
        } else {
            $message = $remark !== ""
                ? "Verification rejected: " . $remark
                : "Your verification was rejected. You may submit new documents.";
            $messageType = "error";
        }
    } else {
        $message = "You have already submitted your documents.";
        $messageType = "warning";
    }
}

$pageTitle = "Profile Verification";
$pageSubtitle = "Submit your identity documents for review.";
$extraCSS = "
.verify-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr);gap:24px;align-items:start}
.verify-upload{border:2px dotted var(--mint-400);border-radius:14px;padding:18px;background:var(--mint-50);transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease}
.verify-upload:hover{border-color:var(--mint-600);box-shadow:0 10px 24px rgba(34,197,94,.12);transform:translateY(-1px)}
.verify-upload:focus-within{border-color:var(--mint-600);box-shadow:0 0 0 4px rgba(34,197,94,.12)}
.verify-upload .form-label{color:var(--mint-600);font-weight:800}
.verify-upload input[type='file']{width:100%;margin-top:9px;padding:8px;border:1px solid var(--mint-200);border-radius:10px;background:rgba(255,255,255,.82);color:var(--text-secondary);font-size:12px;cursor:pointer}
.verify-upload input[type='file']::file-selector-button{margin-right:12px;padding:9px 15px;border:0;border-radius:8px;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));color:#fff;font-family:inherit;font-size:12px;font-weight:800;cursor:pointer}
[data-theme='dark'] .verify-upload{background:rgba(34,197,94,.10);border-color:var(--mint-400)}
[data-theme='dark'] .verify-upload input[type='file']{background:rgba(13,20,17,.55);border-color:rgba(74,222,128,.3)}
.verify-help{font-size:12px;color:var(--text-gray);margin-top:6px;line-height:1.5}
.verify-status{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid var(--border)}
.verify-status:last-child{border-bottom:0}
.verify-score{font-size:32px;font-weight:800;color:var(--primary);font-family:'Plus Jakarta Sans',sans-serif}
.verify-note{display:flex;gap:10px;align-items:flex-start;padding:14px;border-radius:10px;background:var(--primary-light);color:var(--text-secondary);font-size:13px;line-height:1.6}
.verification-loader{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(8,24,16,.72);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);opacity:0;visibility:hidden;transition:opacity .25s ease,visibility .25s ease}
.verification-loader.active{opacity:1;visibility:visible}
.verification-loader-card{position:relative;width:min(420px,100%);padding:38px 30px 32px;text-align:center;border:1px solid rgba(34,197,94,.28);border-radius:22px;background:var(--bg-secondary);box-shadow:0 24px 70px rgba(0,0,0,.3);overflow:hidden}
.verification-loader-card:before{content:'';position:absolute;inset:0 0 auto;height:4px;background:linear-gradient(90deg,var(--mint-400),var(--teal-500),var(--mint-400));background-size:200% 100%;animation:verifyGradient 1.8s linear infinite}
.verification-loader-icon{position:relative;width:92px;height:92px;margin:0 auto 22px;display:flex;align-items:center;justify-content:center;color:#fff;border-radius:50%;background:linear-gradient(135deg,var(--mint-500),var(--teal-500));box-shadow:0 12px 32px rgba(34,197,94,.28)}
.verification-loader-icon:before,.verification-loader-icon:after{content:'';position:absolute;border-radius:50%;border:2px solid rgba(34,197,94,.3);animation:verifyPulse 1.8s ease-out infinite}
.verification-loader-icon:before{inset:-10px}
.verification-loader-icon:after{inset:-20px;animation-delay:.45s}
.verification-spinner{position:absolute;inset:-10px;border-radius:50%;border:3px solid transparent;border-top-color:var(--mint-400);border-right-color:var(--teal-500);animation:verifySpin 1.1s linear infinite}
.verification-loader h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;color:var(--text-primary);margin-bottom:8px}
.verification-loader-stage{min-height:42px;font-size:13px;line-height:1.6;color:var(--text-secondary);transition:opacity .18s ease}
.verification-loader-note{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:18px;font-size:11px;color:var(--text-gray)}
.verification-loader-dots{display:inline-flex;gap:5px;margin-top:18px}
.verification-loader-dots span{width:7px;height:7px;border-radius:50%;background:var(--mint-500);animation:verifyDot 1.2s ease-in-out infinite}
.verification-loader-dots span:nth-child(2){animation-delay:.15s}
.verification-loader-dots span:nth-child(3){animation-delay:.3s}
body.verification-loading{overflow:hidden}
@keyframes verifySpin{to{transform:rotate(360deg)}}
@keyframes verifyPulse{0%{transform:scale(.9);opacity:.8}100%{transform:scale(1.25);opacity:0}}
@keyframes verifyDot{0%,60%,100%{transform:translateY(0);opacity:.35}30%{transform:translateY(-6px);opacity:1}}
@keyframes verifyGradient{to{background-position:-200% 0}}
@media(prefers-reduced-motion:reduce){.verification-loader-card:before,.verification-loader-icon:before,.verification-loader-icon:after,.verification-spinner,.verification-loader-dots span{animation-duration:3s}}
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
            <form method="post" enctype="multipart/form-data" id="verificationForm">
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

                <button type="submit" class="btn-primary" id="verificationSubmitButton">
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

<div
    class="verification-loader"
    id="verificationLoader"
    role="dialog"
    aria-modal="true"
    aria-labelledby="verificationLoaderTitle"
    aria-describedby="verificationLoaderStage"
    aria-hidden="true"
>
    <div class="verification-loader-card">
        <div class="verification-loader-icon">
            <div class="verification-spinner"></div>
            <?php echo wGetIcon("shield", 38); ?>
        </div>
        <h2 id="verificationLoaderTitle">Verifying your documents</h2>
        <p class="verification-loader-stage" id="verificationLoaderStage">
            Securely uploading your documents…
        </p>
        <div class="verification-loader-dots" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <div class="verification-loader-note">
            <?php echo wGetIcon("clock", 13); ?>
            <span>This can take a few minutes. Please keep this page open.</span>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('verificationForm');
    const loader = document.getElementById('verificationLoader');
    const stage = document.getElementById('verificationLoaderStage');
    const button = document.getElementById('verificationSubmitButton');

    if (!form || !loader || !stage || !button) return;

    const stages = [
        'Securely uploading your documents…',
        'Checking image clarity and quality…',
        'Reading your government ID…',
        'Comparing your selfie with the ID photo…',
        'Preparing your verification result…'
    ];
    let stageIndex = 0;
    let stageTimer = null;
    let submitted = false;

    form.addEventListener('submit', function (event) {
        if (submitted) {
            event.preventDefault();
            return;
        }

        submitted = true;
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        loader.classList.add('active');
        loader.setAttribute('aria-hidden', 'false');
        document.body.classList.add('verification-loading');

        stageTimer = window.setInterval(function () {
            stage.style.opacity = '0';
            window.setTimeout(function () {
                stageIndex = Math.min(stageIndex + 1, stages.length - 1);
                stage.textContent = stages[stageIndex];
                stage.style.opacity = '1';

                if (stageIndex === stages.length - 1) {
                    window.clearInterval(stageTimer);
                }
            }, 180);
        }, 6500);
    });

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        submitted = false;
        button.disabled = false;
        button.removeAttribute('aria-disabled');
        loader.classList.remove('active');
        loader.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('verification-loading');
        if (stageTimer) window.clearInterval(stageTimer);
    });
})();
</script>

<?php include "../includes/worker-page-end.php"; ?>
