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
  
  public function sendPushNotification($userId, $title, $message, $data = []) {
    // In a real implementation, this would integrate with a push notification service
    // For now, we'll just log the notification
    
    try {
      // Log notification to database
      $stmt = $this->db->prepare("
        INSERT INTO notifications (user_id, title, message, data, created_at)
        VALUES (?, ?, ?, ?, NOW())
      ");
      $stmt->execute([$userId, $title, $message, json_encode($data)]);
      
      $notificationId = $this->db->lastInsertId();
      
      // In a real implementation, you would send the actual push notification here
      // For example, integrating with Firebase Cloud Messaging (FCM)
      
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
  
  public function sendBulkNotifications($userIds, $title, $message, $data = []) {
    $successCount = 0;
    $failures = [];
    
    foreach ($userIds as $userId) {
      try {
        $result = $this->sendPushNotification($userId, $title, $message, $data);
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
  
  public function sendGroupNotification($groupId, $title, $message, $data = []) {
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
      
      return $this->sendBulkNotifications($userIds, $title, $message, $data);
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