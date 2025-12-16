<?php
class Uploader
{
  private $uploadDir;
  private $allowedTypes;
  private $maxFileSize;

  public function __construct($uploadDir = '../uploads/')
  {
    $this->uploadDir = $uploadDir;
    $this->allowedTypes = [
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/webp',
      'application/pdf',
      'text/plain',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $this->maxFileSize = 10 * 1024 * 1024; // 10MB
  }

  public function uploadFile($file, $subDir = '')
  {
    try {
      // Check if file was uploaded
      if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $this->getUploadErrorMessage($file['error'] ?? 0));
      }

      // Validate file size
      if ($file['size'] > $this->maxFileSize) {
        throw new Exception("File size exceeds maximum allowed size of " . $this->formatBytes($this->maxFileSize));
      }

      // Validate file type
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $file['tmp_name']);
      finfo_close($finfo);

      if (!in_array($mimeType, $this->allowedTypes)) {
        throw new Exception("File type not allowed. Allowed types: " . implode(', ', $this->allowedTypes));
      }

      // Create upload directory if it doesn't exist
      $targetDir = $this->uploadDir . $subDir;
      if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
      }

      // Generate unique filename
      $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = uniqid() . '_' . time() . '.' . $extension;
      $targetPath = $targetDir . '/' . $filename;

      // Move uploaded file
      if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to move uploaded file");
      }

      // Return file info
      return [
        'filename' => $filename,
        'path' => $targetPath,
        'url' => self::getBaseUrl() . '/uploads/' . $subDir . '/' . $filename,
        'size' => $file['size'],
        'type' => $mimeType
      ];
    } catch (Exception $e) {
      error_log("File upload error: " . $e->getMessage());
      throw $e;
    }
  }

  public function uploadBase64Image($base64String, $subDir = '', $filenamePrefix = 'img')
  {
    try {
      // Remove data URL prefix if present
      if (strpos($base64String, 'data:image') === 0) {
        $base64String = substr($base64String, strpos($base64String, ',') + 1);
      }

      // Decode base64 string
      $imageData = base64_decode($base64String);

      if ($imageData === false) {
        throw new Exception("Invalid base64 string");
      }

      // Validate file size
      if (strlen($imageData) > $this->maxFileSize) {
        throw new Exception("File size exceeds maximum allowed size of " . $this->formatBytes($this->maxFileSize));
      }

      // Detect image type
      $finfo = finfo_open();
      $mimeType = finfo_buffer($finfo, $imageData, FILEINFO_MIME_TYPE);
      finfo_close($finfo);

      $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      if (!in_array($mimeType, $allowedImageTypes)) {
        throw new Exception("File type not allowed. Allowed image types: " . implode(', ', $allowedImageTypes));
      }

      // Get file extension
      $extension = $this->getExtensionFromMimeType($mimeType);

      // Create upload directory if it doesn't exist
      $targetDir = $this->uploadDir . $subDir;
      if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
      }

      // Generate unique filename
      $filename = $filenamePrefix . '_' . uniqid() . '_' . time() . '.' . $extension;
      $targetPath = $targetDir . '/' . $filename;

      // Save image file
      if (file_put_contents($targetPath, $imageData) === false) {
        throw new Exception("Failed to save image file");
      }

      // Return file info
      return [
        'filename' => $filename,
        'path' => $targetPath,
        'url' => self::getBaseUrl() . '/uploads/' . $subDir . '/' . $filename,
        'size' => strlen($imageData),
        'type' => $mimeType
      ];
    } catch (Exception $e) {
      error_log("Base64 image upload error: " . $e->getMessage());
      throw $e;
    }
  }

  public function deleteFile($filePath)
  {
    try {
      if (file_exists($filePath)) {
        return unlink($filePath);
      }
      return true; // File doesn't exist, consider it deleted
    } catch (Exception $e) {
      error_log("File deletion error: " . $e->getMessage());
      return false;
    }
  }

  private function getUploadErrorMessage($errorCode)
  {
    switch ($errorCode) {
      case UPLOAD_ERR_INI_SIZE:
        return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
      case UPLOAD_ERR_FORM_SIZE:
        return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
      case UPLOAD_ERR_PARTIAL:
        return 'The uploaded file was only partially uploaded';
      case UPLOAD_ERR_NO_FILE:
        return 'No file was uploaded';
      case UPLOAD_ERR_NO_TMP_DIR:
        return 'Missing a temporary folder';
      case UPLOAD_ERR_CANT_WRITE:
        return 'Failed to write file to disk';
      case UPLOAD_ERR_EXTENSION:
        return 'File upload stopped by extension';
      default:
        return 'Unknown upload error';
    }
  }

  private function formatBytes($size, $precision = 2)
  {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
      $size /= 1024;
    }

    return round($size, $precision) . ' ' . $units[$i];
  }

  private function getExtensionFromMimeType($mimeType)
  {
    $mimeToExt = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/gif' => 'gif',
      'image/webp' => 'webp'
    ];

    return isset($mimeToExt[$mimeType]) ? $mimeToExt[$mimeType] : 'bin';
  }

  public function validateAndResizeImage($filePath, $maxWidth = 1920, $maxHeight = 1080)
  {
    try {
      // Get image info
      $imageInfo = getimagesize($filePath);
      if ($imageInfo === false) {
        throw new Exception("Invalid image file");
      }

      $width = $imageInfo[0];
      $height = $imageInfo[1];
      $mimeType = $imageInfo['mime'];

      // Check if resizing is needed
      if ($width <= $maxWidth && $height <= $maxHeight) {
        return true; // No resizing needed
      }

      // Calculate new dimensions
      $ratio = min($maxWidth / $width, $maxHeight / $height);
      $newWidth = intval($width * $ratio);
      $newHeight = intval($height * $ratio);

      // Create image resource based on mime type
      switch ($mimeType) {
        case 'image/jpeg':
          $source = imagecreatefromjpeg($filePath);
          break;
        case 'image/png':
          $source = imagecreatefrompng($filePath);
          break;
        case 'image/gif':
          $source = imagecreatefromgif($filePath);
          break;
        default:
          throw new Exception("Unsupported image type for resizing");
      }

      if ($source === false) {
        throw new Exception("Failed to create image resource");
      }

      // Create resized image
      $resized = imagecreatetruecolor($newWidth, $newHeight);

      // Preserve transparency for PNG and GIF
      if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
      }

      // Resize image
      imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

      // Save resized image
      switch ($mimeType) {
        case 'image/jpeg':
          $result = imagejpeg($resized, $filePath, 85);
          break;
        case 'image/png':
          $result = imagepng($resized, $filePath, 6);
          break;
        case 'image/gif':
          $result = imagegif($resized, $filePath);
          break;
        default:
          $result = false;
      }

      // Free memory
      imagedestroy($source);
      imagedestroy($resized);

      if (!$result) {
        throw new Exception("Failed to save resized image");
      }

      return true;
    } catch (Exception $e) {
      error_log("Image resize error: " . $e->getMessage());
      throw $e;
    }
  }

  private static function getBaseUrl()
  {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);

    // Normalize slashes
    $basePath = str_replace('\\', '/', $basePath);

    // Remove trailing slash
    $basePath = rtrim($basePath, '/');

    // If we are at root, return empty string
    if ($basePath === '' || $basePath === '.') {
      return '';
    }

    return $basePath;
  }
}