<?php
/**
 * S3Helper - AWS S3 integration using presigned URLs (no SDK required)
 * Uses AWS Signature Version 4 to generate presigned PUT URLs.
 */
class S3Helper
{
    private $accessKey;
    private $secretKey;
    private $bucket;
    private $region;
    private $host;

    public function __construct()
    {
        $this->accessKey = $_ENV['AWS_ACCESS_KEY_ID']     ?? getenv('AWS_ACCESS_KEY_ID');
        $this->secretKey = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? getenv('AWS_SECRET_ACCESS_KEY');
        $this->bucket    = $_ENV['AWS_S3_BUCKET']         ?? getenv('AWS_S3_BUCKET');
        $this->region    = $_ENV['AWS_REGION']            ?? getenv('AWS_REGION') ?? 'us-east-1';
        $this->host      = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';
    }

    /**
     * Generate a presigned PUT URL valid for $expiresIn seconds.
     *
     * @param string $objectKey  e.g. "profiles/user_123_avatar.jpg"
     * @param string $contentType e.g. "image/jpeg"
     * @param int    $expiresIn  seconds until URL expires (max 604800 = 7 days)
     * @return array ['url' => presigned_url, 'public_url' => final_object_url]
     */
    public function generatePresignedPutUrl(string $objectKey, string $contentType = 'image/jpeg', int $expiresIn = 3600): array
    {
        $datetime   = gmdate('Ymd\THis\Z');
        $date       = gmdate('Ymd');
        $credential = $this->accessKey . '/' . $date . '/' . $this->region . '/s3/aws4_request';

        $queryParams = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $credential,
            'X-Amz-Date'          => $datetime,
            'X-Amz-Expires'       => (string) $expiresIn,
            'X-Amz-SignedHeaders' => 'host',
        ];

        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        // Canonical request
        $canonicalUri     = '/' . ltrim($objectKey, '/');
        $canonicalHeaders = 'host:' . $this->host . "\n";
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
        $credentialScope = $date . '/' . $this->region . '/s3/aws4_request';
        $stringToSign    = implode("\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        // Signing key
        $signingKey = $this->getSigningKey($date);
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);

        $presignedUrl = 'https://' . $this->host . $canonicalUri
            . '?' . $queryString
            . '&X-Amz-Signature=' . $signature;

        $publicUrl = 'https://' . $this->host . $canonicalUri;

        return [
            'presigned_url' => $presignedUrl,
            'public_url'    => $publicUrl,
            'object_key'    => $objectKey,
            'expires_in'    => $expiresIn,
        ];
    }

    /**
     * Upload a file directly from the server to S3 (for multipart/form-data uploads).
     * Returns the public URL on success.
     */
    public function uploadFileToS3(string $filePath, string $objectKey, string $contentType = 'image/jpeg'): string
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new Exception("Cannot read file: $filePath");
        }

        $datetime        = gmdate('Ymd\THis\Z');
        $date            = gmdate('Ymd');
        $payloadHash     = hash('sha256', $fileContent);
        $contentLength   = strlen($fileContent);

        $headers = [
            'content-length' => (string) $contentLength,
            'content-type'   => $contentType,
            'host'           => $this->host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'     => $datetime,
        ];

        ksort($headers);

        $canonicalHeaders = '';
        $signedHeadersList = [];
        foreach ($headers as $k => $v) {
            $canonicalHeaders   .= $k . ':' . $v . "\n";
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

        $credentialScope = $date . '/' . $this->region . '/s3/aws4_request';
        $stringToSign    = implode("\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->getSigningKey($date);
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);

        $authHeader = 'AWS4-HMAC-SHA256 Credential=' . $this->accessKey . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaders
            . ', Signature=' . $signature;

        $curlHeaders = [
            'Authorization: '       . $authHeader,
            'Content-Length: '      . $contentLength,
            'Content-Type: '        . $contentType,
            'Host: '                . $this->host,
            'x-amz-content-sha256: ' . $payloadHash,
            'x-amz-date: '         . $datetime,
        ];

        $url = 'https://' . $this->host . $canonicalUri;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $fileContent,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("cURL error uploading to S3: $curlError");
        }
        if ($httpCode !== 200) {
            throw new Exception("S3 upload failed (HTTP $httpCode): $response");
        }

        return 'https://' . $this->host . $canonicalUri;
    }

    /**
     * Delete an object from S3 by its key.
     */
    public function deleteObject(string $objectKey): bool
    {
        $datetime    = gmdate('Ymd\THis\Z');
        $date        = gmdate('Ymd');
        $payloadHash = hash('sha256', '');

        $headers = [
            'host'                 => $this->host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $datetime,
        ];

        ksort($headers);
        $canonicalHeaders  = '';
        $signedHeadersList = [];
        foreach ($headers as $k => $v) {
            $canonicalHeaders   .= $k . ':' . $v . "\n";
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

        $credentialScope = $date . '/' . $this->region . '/s3/aws4_request';
        $stringToSign    = implode("\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->getSigningKey($date);
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);

        $authHeader = 'AWS4-HMAC-SHA256 Credential=' . $this->accessKey . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaders
            . ', Signature=' . $signature;

        $url = 'https://' . $this->host . $canonicalUri;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => [
                'Authorization: '        . $authHeader,
                'Host: '                 . $this->host,
                'x-amz-content-sha256: ' . $payloadHash,
                'x-amz-date: '           . $datetime,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $httpCode = curl_getinfo(curl_exec($ch) ? $ch : $ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 204;
    }

    /**
     * Build a unique S3 object key for a given context.
     * e.g. "profiles/123/avatar_1234567890.jpg"
     */
    public static function buildObjectKey(string $folder, string $prefix, string $extension = 'jpg'): string
    {
        return $folder . '/' . $prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function getSigningKey(string $date): string
    {
        $kDate    = hash_hmac('sha256', $date,           'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $this->region,   $kDate,    true);
        $kService = hash_hmac('sha256', 's3',            $kRegion,  true);
        return     hash_hmac('sha256', 'aws4_request',  $kService, true);
    }
}
