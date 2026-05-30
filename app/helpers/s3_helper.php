<?php
/**
 * S3Helper - AWS S3 integration using AWS Signature Version 4.
 * No SDK required. Works on any PHP 7.4+ host.
 */
class S3Helper
{
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $region;
    private string $host;

    public function __construct()
    {
        $this->accessKey = (string)($_ENV['AWS_ACCESS_KEY_ID']     ?? getenv('AWS_ACCESS_KEY_ID')     ?? '');
        $this->secretKey = (string)($_ENV['AWS_SECRET_ACCESS_KEY'] ?? getenv('AWS_SECRET_ACCESS_KEY') ?? '');
        $this->bucket    = (string)($_ENV['AWS_S3_BUCKET']         ?? getenv('AWS_S3_BUCKET')         ?? '');
        $this->region    = (string)($_ENV['AWS_REGION']            ?? getenv('AWS_REGION')            ?? 'us-east-1');
        $this->host      = "{$this->bucket}.s3.{$this->region}.amazonaws.com";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC: Presigned PUT URL (Flutter uploads directly to S3)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate a presigned PUT URL.
     *
     * IMPORTANT: Only 'host' is in X-Amz-SignedHeaders.
     * The Flutter client must NOT send Content-Type or Content-Length headers
     * in the PUT request — only the raw bytes as the body.
     *
     * @param  string $objectKey   e.g. "profiles/uid42_1234567890_abc.jpg"
     * @param  int    $expiresIn   seconds (default 7200 = 2 hours)
     * @return array  ['presigned_url', 'public_url', 'object_key', 'expires_in']
     */
    public function generatePresignedPutUrl(string $objectKey, int $expiresIn = 7200): array
    {
        $datetime        = gmdate('Ymd\THis\Z');
        $date            = gmdate('Ymd');
        $credentialScope = "{$date}/{$this->region}/s3/aws4_request";
        $credential      = "{$this->accessKey}/{$credentialScope}";

        // Query string params — must be sorted
        $queryParams = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $credential,
            'X-Amz-Date'          => $datetime,
            'X-Amz-Expires'       => (string)$expiresIn,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        // Canonical request
        $canonicalUri     = '/' . ltrim($objectKey, '/');
        $canonicalHeaders = "host:{$this->host}\n";
        $signedHeaders    = 'host';
        $payloadHash      = 'UNSIGNED-PAYLOAD';

        $canonicalRequest = implode("\n", [
            'PUT',
            $canonicalUri,
            $queryString,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        // String to sign
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signature    = hash_hmac('sha256', $stringToSign, $this->signingKey($date));
        $presignedUrl = "https://{$this->host}{$canonicalUri}?{$queryString}&X-Amz-Signature={$signature}";
        $publicUrl    = "https://{$this->host}{$canonicalUri}";

        return [
            'presigned_url' => $presignedUrl,
            'public_url'    => $publicUrl,
            'object_key'    => $objectKey,
            'expires_in'    => $expiresIn,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC: Server-side upload (multipart/form-data fallback)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Upload a local file to S3 from the server.
     * Returns the public URL on success.
     */
    public function uploadFileToS3(string $filePath, string $objectKey, string $contentType = 'image/jpeg'): string
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new \Exception("Cannot read file: {$filePath}");
        }

        $datetime        = gmdate('Ymd\THis\Z');
        $date            = gmdate('Ymd');
        $payloadHash     = hash('sha256', $fileContent);
        $contentLength   = strlen($fileContent);
        $credentialScope = "{$date}/{$this->region}/s3/aws4_request";

        // Headers must be sorted alphabetically by key
        $headers = [
            'content-length'       => (string)$contentLength,
            'content-type'         => $contentType,
            'host'                 => $this->host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $datetime,
        ];
        ksort($headers);

        $canonicalHeaders  = '';
        $signedHeadersList = [];
        foreach ($headers as $k => $v) {
            $canonicalHeaders   .= "{$k}:{$v}\n";
            $signedHeadersList[] = $k;
        }
        $signedHeaders = implode(';', $signedHeadersList);

        $canonicalUri     = '/' . ltrim($objectKey, '/');
        $canonicalRequest = implode("\n", [
            'PUT',
            $canonicalUri,
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signature  = hash_hmac('sha256', $stringToSign, $this->signingKey($date));
        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}"
                    . ", SignedHeaders={$signedHeaders}"
                    . ", Signature={$signature}";

        $url = "https://{$this->host}{$canonicalUri}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $fileContent,
            CURLOPT_HTTPHEADER     => [
                "Authorization: {$authHeader}",
                "Content-Length: {$contentLength}",
                "Content-Type: {$contentType}",
                "Host: {$this->host}",
                "x-amz-content-sha256: {$payloadHash}",
                "x-amz-date: {$datetime}",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("cURL error: {$curlError}");
        }
        if ($httpCode !== 200) {
            throw new \Exception("S3 upload failed (HTTP {$httpCode}): {$response}");
        }

        return "https://{$this->host}{$canonicalUri}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC: Delete object
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteObject(string $objectKey): bool
    {
        $datetime        = gmdate('Ymd\THis\Z');
        $date            = gmdate('Ymd');
        $payloadHash     = hash('sha256', '');
        $credentialScope = "{$date}/{$this->region}/s3/aws4_request";

        $headers = [
            'host'                 => $this->host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $datetime,
        ];
        ksort($headers);

        $canonicalHeaders  = '';
        $signedHeadersList = [];
        foreach ($headers as $k => $v) {
            $canonicalHeaders   .= "{$k}:{$v}\n";
            $signedHeadersList[] = $k;
        }
        $signedHeaders = implode(';', $signedHeadersList);

        $canonicalUri     = '/' . ltrim($objectKey, '/');
        $canonicalRequest = implode("\n", [
            'DELETE',
            $canonicalUri,
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signature  = hash_hmac('sha256', $stringToSign, $this->signingKey($date));
        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}"
                    . ", SignedHeaders={$signedHeaders}"
                    . ", Signature={$signature}";

        $url = "https://{$this->host}{$canonicalUri}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => [
                "Authorization: {$authHeader}",
                "Host: {$this->host}",
                "x-amz-content-sha256: {$payloadHash}",
                "x-amz-date: {$datetime}",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);  // execute and store result
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 204;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC STATIC: Build object key
    // ─────────────────────────────────────────────────────────────────────────

    public static function buildObjectKey(string $folder, string $prefix, string $extension = 'jpg'): string
    {
        return "{$folder}/{$prefix}_" . time() . '_' . uniqid() . ".{$extension}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Signing key derivation
    // ─────────────────────────────────────────────────────────────────────────

    private function signingKey(string $date): string
    {
        $kDate    = hash_hmac('sha256', $date,           "AWS4{$this->secretKey}", true);
        $kRegion  = hash_hmac('sha256', $this->region,   $kDate,    true);
        $kService = hash_hmac('sha256', 's3',            $kRegion,  true);
        return      hash_hmac('sha256', 'aws4_request',  $kService, true);
    }
}
