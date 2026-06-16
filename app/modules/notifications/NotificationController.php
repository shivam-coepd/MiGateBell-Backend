<?php
require_once __DIR__ . '/../../core/BaseController.php';

class NotificationController extends BaseController
{
    // ─── GET /api/notifications ──────────────────────────────────────────────
    public function getNotifications()
    {
        try {
            $user  = $this->auth->authenticate();
            $uid   = $user['uid'];
            $page  = isset($_GET['page'])  ? max(1, (int) $_GET['page'])        : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;

            // Total count
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
            $stmt->execute([$uid]);
            $total = (int) $stmt->fetch()['count'];

            // Unread count
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$uid]);
            $unreadCount = (int) $stmt->fetch()['count'];

            // Paginated notifications
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim OFFSET :off"
            );
            $stmt->bindValue(':uid', $uid,    PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success("Notifications retrieved", [
                'notifications'  => $notifications,
                'unread_count'   => $unreadCount,
                'total'          => $total,
                'page'           => $page,
                'limit'          => $limit,
                'has_more'       => ($offset + $limit) < $total,
            ]);
        } catch (Exception $e) {
            Response::error("Failed to retrieve notifications: " . $e->getMessage(), 500);
        }
    }

    // ─── PUT /api/notifications/{id}/read ────────────────────────────────────
    public function markAsRead($id)
    {
        try {
            $user = $this->auth->authenticate();

            $stmt = $this->db->prepare(
                "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$id, $user['uid']]);

            if ($stmt->rowCount() === 0) {
                Response::error("Notification not found or already read", 404);
                return;
            }

            Response::success("Notification marked as read");
        } catch (Exception $e) {
            Response::error("Failed to mark notification as read: " . $e->getMessage(), 500);
        }
    }

    // ─── PUT /api/notifications/read-all ─────────────────────────────────────
    public function markAllAsRead()
    {
        try {
            $user = $this->auth->authenticate();

            $stmt = $this->db->prepare(
                "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0"
            );
            $stmt->execute([$user['uid']]);

            Response::success("All notifications marked as read", ['updated' => $stmt->rowCount()]);
        } catch (Exception $e) {
            Response::error("Failed to mark all as read: " . $e->getMessage(), 500);
        }
    }

    // ─── GET /api/notifications/unread-count ─────────────────────────────────
    public function getUnreadCount()
    {
        try {
            $user = $this->auth->authenticate();

            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0"
            );
            $stmt->execute([$user['uid']]);
            $count = (int) $stmt->fetch()['count'];

            Response::success("Unread count retrieved", ['count' => $count]);
        } catch (Exception $e) {
            Response::error("Failed to retrieve unread count: " . $e->getMessage(), 500);
        }
    }

    // ─── POST /api/notifications/tokens ──────────────────────────────────────
    public function registerDeviceToken()
    {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            if (empty($data['device_token']) || empty($data['device_type'])) {
                Response::error("device_token and device_type are required", 400);
                return;
            }

            $token = trim($data['device_token']);
            $type  = in_array($data['device_type'], ['android', 'ios', 'web'])
                     ? $data['device_type'] : 'android';

            // Upsert: update timestamp if already exists, otherwise insert
            $stmt = $this->db->prepare(
                "INSERT INTO device_tokens (user_id, device_token, device_type)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE device_type = VALUES(device_type), updated_at = NOW()"
            );
            $stmt->execute([$user['uid'], $token, $type]);

            Response::success("Device token registered successfully");
        } catch (Exception $e) {
            Response::error("Failed to register device token: " . $e->getMessage(), 500);
        }
    }

    // ─── POST /api/notifications/tokens/unregister ───────────────────────────
    public function unregisterDeviceToken()
    {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            if (empty($data['device_token'])) {
                Response::error("device_token is required", 400);
                return;
            }

            $stmt = $this->db->prepare(
                "DELETE FROM device_tokens WHERE user_id = ? AND device_token = ?"
            );
            $stmt->execute([$user['uid'], $data['device_token']]);

            Response::success("Device token unregistered successfully");
        } catch (Exception $e) {
            Response::error("Failed to unregister device token: " . $e->getMessage(), 500);
        }
    }
}
