<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../helpers/s3_helper.php';

/**
 * UploadController
 *
 * Handles two upload strategies:
 *  1. GET  /api/upload/presign  → returns a presigned PUT URL so Flutter uploads directly to S3
 *  2. POST /api/upload/file     → accepts multipart file, uploads server-side to S3, returns public URL
 */
class UploadController extends BaseController
{
    private S3Helper $s3;

    // Allowed MIME types
    private array $allowedMimes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif',
    ];

    // Max file size: 10 MB
    private int $maxBytes = 10 * 1024 * 1024;

    public function __construct()
    {
        parent::__construct();
        $this->s3 = new S3Helper();
    }

    // ── 1. Presigned URL ─────────────────────────────────────────────────────

    /**
     * GET /api/upload/presign?folder=profiles&content_type=image/jpeg&ext=jpg
     *
     * Returns a short-lived presigned PUT URL.
     * Flutter uploads the image bytes directly to S3 with a PUT request,
     * then stores the returned public_url in the backend via the normal profile/visitor APIs.
     */
    public function getPresignedUrl(): void
    {
        $user = $this->auth->authenticate();

        $folder      = preg_replace('/[^a-z0-9_\-]/', '', strtolower($_GET['folder'] ?? 'uploads'));
        $contentType = $_GET['content_type'] ?? 'image/jpeg';
        $ext         = preg_replace('/[^a-z0-9]/', '', strtolower($_GET['ext'] ?? 'jpg'));

        if (!in_array($contentType, $this->allowedMimes, true)) {
            Response::error('Content type not allowed. Use: ' . implode(', ', $this->allowedMimes), 400);
        }

        $objectKey = S3Helper::buildObjectKey($folder, 'uid' . $user['uid'], $ext);

        $result = $this->s3->generatePresignedPutUrl($objectKey, $contentType, 3600);

        Response::success('Presigned URL generated', $result);
    }

    // ── 2. Server-side upload ─────────────────────────────────────────────────

    /**
     * POST /api/upload/file
     * multipart/form-data: file=<binary>, folder=profiles
     *
     * Uploads the file from the server to S3 and returns the public URL.
     * Use this as a fallback if direct presigned upload is not feasible.
     */
    public function uploadFile(): void
    {
        $user = $this->auth->authenticate();

        if (empty($_FILES['file'])) {
            Response::error('No file provided', 400);
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('File upload error code: ' . $file['error'], 400);
        }

        if ($file['size'] > $this->maxBytes) {
            Response::error('File exceeds 10 MB limit', 400);
        }

        // Validate MIME via finfo (not just extension)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedMimes, true)) {
            Response::error('File type not allowed', 400);
        }

        $folder    = preg_replace('/[^a-z0-9_\-]/', '', strtolower($_POST['folder'] ?? 'uploads'));
        $ext       = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $objectKey = S3Helper::buildObjectKey($folder, 'uid' . $user['uid'], $ext);

        try {
            $publicUrl = $this->s3->uploadFileToS3($file['tmp_name'], $objectKey, $mimeType);
            Response::success('File uploaded successfully', [
                'url'        => $publicUrl,
                'object_key' => $objectKey,
            ], 201);
        } catch (Exception $e) {
            error_log('S3 upload error: ' . $e->getMessage());
            Response::error('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }
}
