<?php

class AIService
{
    private $baseUrl;
    public function __construct()
    {
        //FastAPI
        $this->baseUrl = "http://127.0.0.1:8000/api";
    }

    public function verifyWorker(
        $governmentId,
        $selfie,
        $certificate = null
    ) {

        $postData = [
            "government_id" => new CURLFile(
                $governmentId["tmp_name"],
                $governmentId["type"],
                $governmentId["name"]
            ),
            "selfie" => new CURLFile(
                $selfie["tmp_name"],
                $selfie["type"],
                $selfie["name"]
            )
        ];

        if (
            !empty($certificate["tmp_name"])
        ) {
            $postData["certificate"] = new CURLFile(
                $certificate["tmp_name"],
                $certificate["type"],
                $certificate["name"]
            );
        }

        $curl = curl_init();

        curl_setopt_array($curl,[

            CURLOPT_URL => $this->baseUrl . "/verify-worker",
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_TIMEOUT => 300
        ]);

        $response = curl_exec($curl);
        if(curl_errno($curl))
        {
            throw new Exception(curl_error($curl));
        }
        curl_close($curl);
        return json_decode($response,true);
    }
}