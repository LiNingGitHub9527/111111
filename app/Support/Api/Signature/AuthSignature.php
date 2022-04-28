<?php


namespace App\Support\Api\Signature;

use Illuminate\Support\Str;

class AuthSignature
{
    /**
     * @var array Verify signature information
     */
    private $signature;

    /**
     * @var string Encryption method
     */
    private $signatureType = 'sha256';

    public function __construct()
    {
        $this->signature = config('signature.signatures');
    }

    /**
     * @Notes: Generate symmetrical encryption key
     *
     * @param string $timestamp //time stamp
     * @param string $nonce //16 bit random string
     * @param string $payload //request body
     * @param string $signatureSecret //signatures key
     * @return string
     * @author: aron
     * @Date: 2021-03-01
     * @Time: 14:48
     */
    public function generateSignature(
        string $timestamp,
        string $nonce,
        string $payload,
        string $signatureSecret
    ): string
    {
        $data = $timestamp . $nonce . $payload;
        $hmac = hash_hmac($this->signatureType, $data, $signatureSecret);
        return base64_encode($hmac);
    }

    /**
     * @Notes: Generate random string
     *
     * @return string
     * @author: aron
     * @Date: 2021-03-01
     * @Time: 14:56
     */
    public function generateNonce(): string
    {
        return Str::random(16);
    }


    /**
     * @Notes: Verify that the key is correct
     *
     * @param string $timestamp //time stamp
     * @param string $nonce //16 bit random string
     * @param string $payload // Request load
     * @param string $signatureApiKey //admin api key
     * @param string $clientSignature //client generate key
     * @return bool
     * @author: aron
     * @Date: 2021-03-01
     * @Time: 15:12
     */
    public function verifySignature(
        string $timestamp,
        string $nonce,
        string $payload,
        string $signatureApiKey,
        string $clientSignature
    ): bool
    {
        $arguments = func_get_args();
        foreach ($arguments as $v) {
            if (empty($v)) {
                return false;
            }
        }
        $apiSecret = $this->getSignatureApiSecret($signatureApiKey);
        if (empty($apiSecret) || !$this->verifiedTimestamp($timestamp, $signatureApiKey)) {
            return false;
        }
        $arg = [
            $timestamp,
            $nonce,
            $payload,
            $apiSecret
        ];
		$generateSignature = $this->generateSignature(...$arg);
        if ($generateSignature !== $clientSignature) {
            return false;
        }
        return true;
    }


    /**
     * @Notes: According to api //get api //secret
     *
     * @param string $signatureApiKey
     * @return string
     * @author: aron
     * @Date: 2021/3/1
     * @Time: 5:23 afternoon
     */
    public function getSignatureApiSecret(string $signatureApiKey): string
    {
        $apiInfo = $this->getSignatureApiInfo($signatureApiKey);
        if (empty($apiInfo)) {
            return '';
        }
        return $apiInfo[0]['signatureSecret'];
    }

    /**
     * @Notes: According to api key //get api information
     *
     * @param string $signatureApiKey
     * @return array
     * @author: Aron
     * @Date: 2021/3/19
     * @Time: 4:22 afternoon
     */
    protected function getSignatureApiInfo(string $signatureApiKey = ""): array
    {
        $apiInfo = array_filter($this->signature, function ($item) use ($signatureApiKey) {
            return $item['signatureApiKey'] === $signatureApiKey;
        });
        if (empty($apiInfo)) {
            return [];
        }
        return array_values($apiInfo);
    }

    /**
     * @Notes: Verify that the timestamp is valid
     *
     * @param string $timestamp
     * @param string $signatureApiKey
     * @return bool
     * @author: Aron
     * @Date: 2021/3/19
     * @Time: 4:29 afternoon
     */
    protected function verifiedTimestamp(string $timestamp = "", string $signatureApiKey = ""): bool
    {
        $apiInfo = $this->getSignatureApiInfo($signatureApiKey);
        if (empty($apiInfo)) {
            return false;
        }
        $timestamp = (int)$timestamp;
        $timestampValidity = (int)$apiInfo[0]['timestampValidity'];
        if (time() - $timestamp > $timestampValidity) {
            return false;
        }
        return true;
    }
}
