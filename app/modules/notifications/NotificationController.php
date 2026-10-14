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

    // ─── POST /api/notifications/send ────────────────────────────────────────
    // Allows society admin to send:
    // 1) Specific resident/user oriented notification (target_type = 'resident', user_id)
    // 2) Specific wing/building wise notification (target_type = 'building', building_id)
    public function sendCustomNotification()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);
            $societyId = $user['society_id'];

            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            $title = trim($data['title'] ?? '');
            $message = trim($data['message'] ?? '');
            $targetType = trim($data['target_type'] ?? 'resident'); // 'resident' | 'building'
            $type = trim($data['type'] ?? 'general'); // 'alert', 'notice', 'general', 'maintenance'
            $isUrgent = !empty($data['is_urgent']) ? true : false;

            if (empty($title) || empty($message)) {
                Response::error("Title and message are required", 400);
                return;
            }

            // Ensure notifications table has necessary columns
            try {
                $cols = $this->db->query("SHOW COLUMNS FROM notifications LIKE 'type'")->fetch();
                if (!$cols) {
                    $this->db->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'general' AFTER message");
                }
                $colsRef = $this->db->query("SHOW COLUMNS FROM notifications LIKE 'reference_id'")->fetch();
                if (!$colsRef) {
                    $this->db->exec("ALTER TABLE notifications ADD COLUMN reference_id VARCHAR(100) DEFAULT NULL AFTER type");
                }
                $colsAct = $this->db->query("SHOW COLUMNS FROM notifications LIKE 'action_url'")->fetch();
                if (!$colsAct) {
                    $this->db->exec("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(255) DEFAULT NULL AFTER reference_id");
                }
            } catch (\Exception $ignored) {}

            require_once __DIR__ . '/../../helpers/notification_helper.php';
            $notificationHelper = new NotificationHelper();

            $recipientUserIds = [];
            $targetDetails = '';

            if ($targetType === 'resident') {
                $targetUserId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
                if ($targetUserId <= 0) {
                    Response::error("Valid resident user_id is required", 400);
                    return;
                }

                // Verify resident exists and belongs to the admin's society
                $checkStmt = $this->db->prepare("SELECT id, name, phone FROM users WHERE id = ? AND society_id = ? AND status = 'active'");
                $checkStmt->execute([$targetUserId, $societyId]);
                $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$targetUser) {
                    Response::error("Resident user not found in this society or is inactive", 404);
                    return;
                }

                $recipientUserIds = [$targetUserId];
                $targetDetails = "Resident: " . $targetUser['name'];
            } elseif ($targetType === 'building') {
                $buildingId = isset($data['building_id']) ? (int)$data['building_id'] : 0;
                if ($buildingId <= 0) {
                    Response::error("Valid building_id is required", 400);
                    return;
                }

                // Verify building belongs to this society
                $bStmt = $this->db->prepare("SELECT id, name FROM buildings WHERE id = ? AND society_id = ?");
                $bStmt->execute([$buildingId, $societyId]);
                $building = $bStmt->fetch(PDO::FETCH_ASSOC);

                if (!$building) {
                    Response::error("Building / Wing not found in this society", 404);
                    return;
                }

                // Get all owners and tenants of flats in this building
                $fStmt = $this->db->prepare("
                    SELECT DISTINCT u.id
                    FROM users u
                    INNER JOIN flats f ON (f.owner_id = u.id OR f.tenant_id = u.id)
                    WHERE f.building_id = ? AND f.society_id = ? AND u.status = 'active'
                ");
                $fStmt->execute([$buildingId, $societyId]);
                $recipientUserIds = $fStmt->fetchAll(PDO::FETCH_COLUMN);

                $targetDetails = "Wing/Building: " . $building['name'];

                if (empty($recipientUserIds)) {
                    Response::error("No active residents found in Building " . $building['name'], 404);
                    return;
                }
            } else {
                Response::error("Invalid target_type. Expected 'resident' or 'building'", 400);
                return;
            }

            // Prefix title if marked urgent
            $finalTitle = $isUrgent ? "🚨 " . $title : $title;
            $notificationType = $isUrgent ? 'alert' : $type;

            $metaData = [
                'target_type' => $targetType,
                'target_details' => $targetDetails,
                'sent_by_name' => $user['name'] ?? 'Admin',
                'is_urgent' => $isUrgent ? '1' : '0'
            ];

            $result = $notificationHelper->sendBulkNotifications(
                $recipientUserIds,
                $finalTitle,
                $message,
                $metaData,
                $notificationType,
                null,
                '/resident-notifications'
            );

            Response::success("Alert/Notification broadcasted successfully", [
                'target_type' => $targetType,
                'target_details' => $targetDetails,
                'total_recipients' => count($recipientUserIds),
                'success_count' => $result['success_count'] ?? count($recipientUserIds),
            ]);
        } catch (Exception $e) {
            error_log("Send custom notification error: " . $e->getMessage());
            Response::error("Failed to send notification: " . $e->getMessage(), 500);
        }
    }

    // ─── GET /api/notifications/sent-history ─────────────────────────────────
    // History of notifications sent out in this society
    public function getSentNotifications()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);
            $societyId = $user['society_id'];

            $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 30;

            $stmt = $this->db->prepare("
                SELECT n.id, n.title, n.message, n.type, n.created_at, n.is_read,
                       u.id AS user_id, u.name AS recipient_name, u.phone AS recipient_phone,
                       (SELECT CONCAT(b.name, '-', f.flat_number)
                        FROM flats f
                        LEFT JOIN buildings b ON f.building_id = b.id
                        WHERE (f.owner_id = u.id OR f.tenant_id = u.id)
                        LIMIT 1) AS flat_number
                FROM notifications n
                INNER JOIN users u ON n.user_id = u.id
                WHERE u.society_id = ?
                ORDER BY n.created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $societyId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success("Sent notifications history retrieved", $logs);
        } catch (Exception $e) {
            Response::error("Failed to fetch notification history: " . $e->getMessage(), 500);
        }
    }
}
