<?php
require_once __DIR__ . '/../../core/BaseController.php';

class NotificationController extends BaseController
{

    public function getNotifications()
    {
        try {
            $user = $this->auth->authenticate();
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
            $pagination = $this->paginate($page, $limit);

            // Get total (unread count could be separate)
            $stmt = $this->db->prepare("SELECT count(*) as count FROM notifications WHERE user_id = ?");
            $stmt->execute([$user['uid']]);
            $total = $stmt->fetch()['count'];

            $stmt = $this->db->prepare("SELECT count(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['uid']]);
            $unreadCount = $stmt->fetch()['count'];

            // Get notifications
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', ($page - 1) * $limit, PDO::PARAM_INT);
            // $stmt->bindValue(':uid', $user['uid']); // Cannot reuse parameter name in PDO emulation sometimes? 
            $stmt->execute([':limit' => $limit, ':offset' => 0, 'dummy' => 0]); // Wait, PDO standard binding.

            // Re-doing simple bind
            $sql = "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':uid', $user['uid'], PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', ($page - 1) * $limit, PDO::PARAM_INT);
            $stmt->execute();

            $notifications = $stmt->fetchAll();

            $this->sendPaginatedResponse($notifications, $total, $pagination, "Notifications retrieved", ['unread_count' => $unreadCount]);

        } catch (Exception $e) {
            error_log("Get notifications error: " . $e->getMessage());
            Response::error("Failed to retrieve notifications: " . $e->getMessage(), 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $user = $this->auth->authenticate();
            // Verify ownership
            $stmt = $this->db->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['uid']]);
            if (!$stmt->fetch())
                Response::notFound("Notification not found");

            $this->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
            Response::success("Notification marked as read");
        } catch (Exception $e) {
            Response::error("Failed to mark read: " . $e->getMessage(), 500);
        }
    }

    public function markAllAsRead()
    {
        try {
            $user = $this->auth->authenticate();
            $this->update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'user_id = :user_id AND is_read = 0', ['user_id' => $user['uid']]);
            Response::success("All notifications marked as read");
        } catch (Exception $e) {
            Response::error("Failed to mark all read: " . $e->getMessage(), 500);
        }
    }

    public function getUnreadCount()
    {
        try {
            $user = $this->auth->authenticate();
            $stmt = $this->db->prepare("SELECT count(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['uid']]);
            $unreadCount = $stmt->fetch()['count'];
            Response::success("Unread count retrieved", ['count' => $unreadCount]);
        } catch (Exception $e) {
            Response::error("Failed to retrieve unread count: " . $e->getMessage(), 500);
        }
    }

    public function registerDeviceToken()
    {
        try {
            $user = $this->auth->authenticate();
            $data = $this->getPostData();

            if (!isset($data['device_token']) || !isset($data['device_type'])) {
                Response::error("Device token and type are required", 400);
            }

            $token = $data['device_token'];
            $type = in_array($data['device_type'], ['android', 'ios', 'web']) ? $data['device_type'] : 'android';

            // Check if token already exists for this user
            $stmt = $this->db->prepare("SELECT id FROM device_tokens WHERE user_id = ? AND device_token = ?");
            $stmt->execute([$user['uid'], $token]);
            
            if ($stmt->fetch()) {
                Response::success("Device token already registered");
                return;
            }

            // Insert new token
            $stmt = $this->db->prepare("INSERT INTO device_tokens (user_id, device_token, device_type) VALUES (?, ?, ?)");
            $stmt->execute([$user['uid'], $token, $type]);

            Response::success("Device token registered successfully");
        } catch (Exception $e) {
            Response::error("Failed to register device token: " . $e->getMessage(), 500);
        }
    }

    public function unregisterDeviceToken()
    {
        try {
            $user = $this->auth->authenticate();
            $data = $this->getPostData();

            if (!isset($data['device_token'])) {
                Response::error("Device token is required", 400);
            }

            $stmt = $this->db->prepare("DELETE FROM device_tokens WHERE user_id = ? AND device_token = ?");
            $stmt->execute([$user['uid'], $data['device_token']]);

            Response::success("Device token unregistered successfully");
        } catch (Exception $e) {
            Response::error("Failed to unregister device token: " . $e->getMessage(), 500);
        }
    }
}
