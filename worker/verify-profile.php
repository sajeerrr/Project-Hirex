<?php

$result = null;
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $gov = new CURLFile(
        $_FILES["government_id"]["tmp_name"],
        $_FILES["government_id"]["type"],
        $_FILES["government_id"]["name"]
    );

    $postData = [
        "government_id" => $gov
    ];

    // Add selfie only if uploaded
    if (
        isset($_FILES["selfie"]) &&
        $_FILES["selfie"]["error"] == UPLOAD_ERR_OK
    ) {

        $postData["selfie"] = new CURLFile(
            $_FILES["selfie"]["tmp_name"],
            $_FILES["selfie"]["type"],
            $_FILES["selfie"]["name"]
        );
    }

    // Add certificate only if uploaded
    if (
        isset($_FILES["certificate"]) &&
        $_FILES["certificate"]["error"] == UPLOAD_ERR_OK
    ) {

        $postData["certificate"] = new CURLFile(
            $_FILES["certificate"]["tmp_name"],
            $_FILES["certificate"]["type"],
            $_FILES["certificate"]["name"]
        );
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "http://127.0.0.1:8000/api/verify-worker",
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $postData
    ]);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error = curl_error($curl);
    }

    curl_close($curl);

    if (!$error) {
        $result = json_decode($response, true);
    }
}
?>

<form method="POST" enctype="multipart/form-data">

    <label>Government ID</label><br>
    <input type="file" name="government_id" required><br><br>

    <label>Selfie</label><br>
    <input type="file" name="selfie"><br><br>

    <label>Certificate</label><br>
    <input type="file" name="certificate"><br><br>

    <button type="submit">
        Submit Verification
    </button>

</form>

<?php if ($error): ?>

    <h3>Error</h3>
    <pre><?= htmlspecialchars($error) ?></pre>

<?php endif; ?>

<?php if ($result): ?>

    <h3>API Response</h3>

    <pre><?php print_r($result); ?></pre>

<?php endif; ?>