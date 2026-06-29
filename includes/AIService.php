<?php

class AIService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $configuredUrl = getenv("HIREX_AI_BASE_URL");
        $this->baseUrl = rtrim(
            $configuredUrl !== false && $configuredUrl !== ""
                ? $configuredUrl
                : "http://127.0.0.1:8000/api",
            "/"
        );

        $configuredKey = getenv("HIREX_AI_API_KEY");
        $this->apiKey = $configuredKey !== false ? $configuredKey : "";
    }

    public function verifyWorker(
        $governmentId,
        $selfie,
        $certificate = null
    ) {
        $this->validateUpload($governmentId, "government ID");
        $this->validateUpload($selfie, "selfie");

        if ($certificate !== null) {
            $this->validateUpload($certificate, "certificate");
        }

        $postData = [
            "government_id" => new CURLFile(
                $governmentId["tmp_name"],
                $governmentId["type"] ?? "application/octet-stream",
                $governmentId["name"]
            ),
            "selfie" => new CURLFile(
                $selfie["tmp_name"],
                $selfie["type"] ?? "application/octet-stream",
                $selfie["name"]
            )
        ];

        if ($certificate !== null) {
            $postData["certificate"] = new CURLFile(
                $certificate["tmp_name"],
                $certificate["type"] ?? "application/octet-stream",
                $certificate["name"]
            );
        }

        $curl = curl_init();

        if ($curl === false) {
            throw new RuntimeException(
                "Could not initialize the verification request."
            );
        }

        $headers = ["Accept: application/json"];

        if ($this->apiKey !== "") {
            $headers[] = "X-API-Key: " . $this->apiKey;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . "/verify-worker",
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTPHEADER => $headers
        ]);

        try {
            $response = curl_exec($curl);

            if ($response === false) {
                throw new RuntimeException(
                    "The verification service is unavailable. Please try again later."
                );
            }

            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $data = json_decode($response, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(
                    "The verification service returned an invalid response."
                );
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                $apiMessage = $data["message"]
                    ?? $data["detail"]
                    ?? "The verification service rejected the request.";

                if (is_array($apiMessage)) {
                    $apiMessage = "The uploaded documents were not accepted.";
                }

                throw new RuntimeException((string) $apiMessage);
            }

            return $data;
        } finally {
            curl_close($curl);
        }
    }

    private function validateUpload($file, $label)
    {
        if (
            !is_array($file) ||
            empty($file["tmp_name"]) ||
            empty($file["name"]) ||
            !is_file($file["tmp_name"]) ||
            !is_readable($file["tmp_name"])
        ) {
            throw new InvalidArgumentException(
                "The {$label} file is missing or unreadable."
            );
        }
    }
}
