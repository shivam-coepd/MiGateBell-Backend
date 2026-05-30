<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../helpers/s3_helper.php';

/**
 * UploadController
 *
 * Two endpoints:
 *  GET  /api/upload/presign  → presigned PUT URL (Flutter uploads directly to S3)
 *  POST /api/upload/file     → server-side multipart upload to S3 (fallback)
 */
class UploadController extends BaseController
{
    private S3Helper $s3;

    private array $allowedMimes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif',
    ];

    private array $extToMime = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];

    private int $maxBytes = 10 * 1024 * 1024; // 10 MB

    public function __construct()
    {
        parent::__construct();
        $this->s3 = new S3Helper();
    }

    // ── 1. Presigned URL ─────────────────────────────────────────────────────

    /**
     * GET /api/upload/presign?folder=profiles&ext=jpg
     *
     * Returns a presigned PUT URL. The Flutter client PUTs raw bytes to S3
     * with NO extra headers (no Content-Type, no Content-Length).
     */
    public function getPresignedUrl(): void
    {
        $user = $this->auth->authenticate();

        $folder = preg_replace('/[^a-z0-9_\-]/', '', strtolower($_GET['folder'] ?? 'uploads'));
        $ext    = preg_replace('/[^a-z0-9]/',    '', strtolower($_GET['ext']    ?? 'jpg'));

        if (!array_key_exists($ext, $this->extToMime)) {
            $ext = 'jpg';
        }

        $objectKey = S3Helper::buildObjectKey($folder, 'uid' . $user['uid'], $ext);
        $result    = $this->s3->generatePresignedPutUrl($objectKey, 7200);

        Response::success('Presigned URL generated', $result);
    }

    // ── 2. Server-side upload ─────────────────────────────────────────────────

    /**
     * POST /api/upload/file
     * multipart/form-data: file=<binary>, folder=profiles
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
        } catch (\Exception $e) {
            error_log('S3 upload error: ' . $e->getMessage());
            Response::error('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }
}
