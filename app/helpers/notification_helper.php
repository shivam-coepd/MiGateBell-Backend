<?php
require_once __DIR__.'/../core/Database.php';

class NotificationHelper {
  private $db;
  
  public function __construct() {
    $this->db = Database::getConnection();
    if (!$this->db) {
      $this->db = Database::connect(require __DIR__.'/../config/database.php');
    }
  }
  
  private function getFcmAccessToken() {
    // Requires a service account JSON file from Firebase Console
    $credentialsPath = __DIR__ . '/../config/firebase_credentials.json';
    
    if (!file_exists($credentialsPath)) {
      error_log("FCM error: Firebase credentials file not found at $credentialsPath");
      return null;
    }

    try {
      $client = new \Google\Auth\Credentials\ServiceAccountCredentials(
        'https://www.googleapis.com/auth/firebase.messaging',
        $credentialsPath
      );
      
      $token = $client->fetchAuthToken();
      return $token['access_token'] ?? null;
    } catch (\Exception $e) {
      error_log("FCM get auth token error: " . $e->getMessage());
      return null;
    }
  }

  public function sendPushNotification($userId, $title, $message, $data = [], $type = 'general', $referenceId = null, $actionUrl = null) {
    try {
      // 1. Log notification to database
      $stmt = $this->db->prepare("
        INSERT INTO notifications (user_id, title, message, type, reference_id, action_url, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
      ");
      $stmt->execute([$userId, $title, $message, $type, $referenceId, $actionUrl]);
      
      $notificationId = $this->db->lastInsertId();
      
      // 2. Send actual push notification via FCM HTTP v1 API
      // Get user's device tokens
      $stmt = $this->db->prepare("SELECT device_token FROM device_tokens WHERE user_id = ?");
      $stmt->execute([$userId]);
      $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

      if (empty($tokens)) {
        return $notificationId; // User has no registered devices, but notification is logged
      }

      $accessToken = $this->getFcmAccessToken();
      if (!$accessToken) {
        return $notificationId; // Could not get access token, but logged locally
      }

      // We need the Project ID from the credentials file
      $credentials = json_decode(file_get_contents(__DIR__ . '/../config/firebase_credentials.json'), true);
      $projectId = $credentials['project_id'] ?? null;
      
      if (!$projectId) {
        error_log("FCM error: Project ID not found in credentials file.");
        return $notificationId;
      }

      $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

      // Include additional metadata in data payload for the Flutter app
      $payloadData = array_merge($data, [
          'notification_id' => (string) $notificationId,
          'type' => (string) $type,
          'reference_id' => (string) $referenceId,
          'action_url' => (string) $actionUrl
      ]);

      // Remove nulls from payloadData and convert everything to strings as FCM requires string values in data payload
      foreach ($payloadData as $key => $val) {
        if ($val === null || $val === '') {
          unset($payloadData[$key]);
        } else {
          $payloadData[$key] = (string) $val;
        }
      }

      foreach ($tokens as $deviceToken) {
        $messageData = [
          'message' => [
            'token' => $deviceToken,
            'notification' => [
              'title' => $title,
              'body' => $message
            ],
            'data' => $payloadData
          ]
        ];

        // Send via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Authorization: Bearer ' . $accessToken,
          'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
        
        $result = curl_exec($ch);
        
        if ($result === FALSE) {
          error_log('FCM curl Error: ' . curl_error($ch));
        } else {
          $response = json_decode($result, true);
          if (isset($response['error'])) {
             error_log('FCM API Error for token ' . $deviceToken . ': ' . json_encode($response['error']));
             // Optional: If error is UNREGISTERED, remove token from DB
             if ($response['error']['status'] === 'NOT_FOUND' || (isset($response['error']['details'][0]['errorCode']) && $response['error']['details'][0]['errorCode'] === 'UNREGISTERED')) {
                $delStmt = $this->db->prepare("DELETE FROM device_tokens WHERE device_token = ?");
                $delStmt->execute([$deviceToken]);
             }
          }
        }
        curl_close($ch);
      }
      
      return $notificationId;
    } catch(Exception $e) {
      error_log("Push notification error: " . $e->getMessage());
      return false;
    }
  }
  
  public function sendSms($phoneNumber, $message) {
    // In a real implementation, this would integrate with an SMS service provider
    // For now, we'll just log the SMS
    
    try {
      // Log SMS to database
      $stmt = $this->db->prepare("
        INSERT INTO sms_logs (phone_number, message, status, created_at)
        VALUES (?, ?, 'sent', NOW())
      ");
      $stmt->execute([$phoneNumber, $message]);
      
      $smsId = $this->db->lastInsertId();
      
      // In a real implementation, you would send the actual SMS here
      // For example, integrating with Twilio or other SMS providers
      
      return $smsId;
    } catch(Exception $e) {
      error_log("SMS sending error: " . $e->getMessage());
      return false;
    }
  }
  
  public function sendEmail($email, $subject, $body, $attachments = []) {
    // In a real implementation, this would integrate with an email service
    // For now, we'll just log the email
    
    try {
      // Log email to database
      $stmt = $this->db->prepare("
        INSERT INTO email_logs (recipient_email, subject, body, status, created_at)
        VALUES (?, ?, ?, 'sent', NOW())
      ");
      $stmt->execute([$email, $subject, $body]);
      
      $emailId = $this->db->lastInsertId();
      
      // In a real implementation, you would send the actual email here
      // For example, integrating with PHPMailer, SendGrid, or other email services
      
      return $emailId;
    } catch(Exception $e) {
      error_log("Email sending error: " . $e->getMessage());
      return false;
    }
  }
  
  public function sendBulkNotifications($userIds, $title, $message, $data = [], $type = 'general', $referenceId = null, $actionUrl = null) {
    $successCount = 0;
    $failures = [];
    
    foreach ($userIds as $userId) {
      try {
        $result = $this->sendPushNotification($userId, $title, $message, $data, $type, $referenceId, $actionUrl);
        if ($result) {
          $successCount++;
        } else {
          $failures[] = $userId;
        }
      } catch(Exception $e) {
        $failures[] = $userId;
        error_log("Bulk notification error for user {$userId}: " . $e->getMessage());
      }
    }
    
    return [
      'success_count' => $successCount,
      'failures' => $failures
    ];
  }
  
  public function sendGroupNotification($groupId, $title, $message, $data = [], $type = 'general', $referenceId = null, $actionUrl = null) {
    try {
      // Get all members of the group
      $stmt = $this->db->prepare("
        SELECT user_id 
        FROM group_members 
        WHERE group_id = ?
      ");
      $stmt->execute([$groupId]);
      $members = $stmt->fetchAll();
      
      $userIds = array_column($members, 'user_id');
      
      return $this->sendBulkNotifications($userIds, $title, $message, $data, $type, $referenceId, $actionUrl);
    } catch(Exception $e) {
      error_log("Group notification error: " . $e->getMessage());
      return false;
    }
  }
  
  public function getUnreadNotificationsCount($userId) {
    try {
      $stmt = $this->db->prepare("
        SELECT COUNT(*) as count
        FROM notifications
        WHERE user_id = ? AND is_read = 0
      ");
      $stmt->execute([$userId]);
      $result = $stmt->fetch();
      
      return $result['count'];
    } catch(Exception $e) {
      error_log("Get unread notifications count error: " . $e->getMessage());
      return 0;
    }
  }
  
  public function markNotificationAsRead($notificationId, $userId) {
    try {
      $stmt = $this->db->prepare("
        UPDATE notifications
        SET is_read = 1, read_at = NOW()
        WHERE id = ? AND user_id = ?
      ");
      $stmt->execute([$notificationId, $userId]);
      
      return $stmt->rowCount() > 0;
    } catch(Exception $e) {
      error_log("Mark notification as read error: " . $e->getMessage());
      return false;
    }
  }
}