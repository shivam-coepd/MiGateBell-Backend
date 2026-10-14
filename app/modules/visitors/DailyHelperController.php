<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../helpers/notification_helper.php';

class DailyHelperController extends BaseController {

    private function ensureTablesExist() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `daily_helpers` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `society_id` int(11) NOT NULL,
                    `flat_id` int(11) NOT NULL,
                    `resident_id` int(11) DEFAULT NULL,
                    `name` varchar(100) NOT NULL,
                    `phone` varchar(15) NOT NULL,
                    `service_type` varchar(50) NOT NULL,
                    `qr_code` varchar(100) NOT NULL,
                    `image_url` varchar(255) DEFAULT NULL,
                    `id_proof_url` varchar(255) DEFAULT NULL,
                    `vehicle_number` varchar(50) DEFAULT NULL,
                    `expected_time_in` time DEFAULT NULL,
                    `expected_time_out` time DEFAULT NULL,
                    `working_days` varchar(50) DEFAULT 'All Days',
                    `is_active` tinyint(1) DEFAULT 1,
                    `created_by` int(11) DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_dh_qr_code` (`qr_code`),
                    KEY `idx_dh_society` (`society_id`),
                    KEY `idx_dh_flat` (`flat_id`),
                    KEY `idx_dh_resident` (`resident_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `daily_helper_logs` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `daily_helper_id` int(11) NOT NULL,
                    `society_id` int(11) NOT NULL,
                    `flat_id` int(11) NOT NULL,
                    `entry_time` datetime NOT NULL,
                    `exit_time` datetime DEFAULT NULL,
                    `guard_in_id` int(11) DEFAULT NULL,
                    `guard_out_id` int(11) DEFAULT NULL,
                    `status` enum('inside','exited') NOT NULL DEFAULT 'inside',
                    `entry_method` enum('qr_scan','manual') DEFAULT 'qr_scan',
                    `exit_method` enum('qr_scan','manual') DEFAULT NULL,
                    `notes` text DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_dhl_helper` (`daily_helper_id`),
                    KEY `idx_dhl_society` (`society_id`),
                    KEY `idx_dhl_flat` (`flat_id`),
                    KEY `idx_dhl_status` (`status`),
                    KEY `idx_dhl_entry` (`entry_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Exception $e) {
            error_log("Failed to auto-create daily helper tables: " . $e->getMessage());
        }
    }

    /**
     * POST /api/daily-helpers
     * Create/Register a new Daily Helper & generate their permanent static QR code
     * Allowed: resident, guard, admin
     */
    public function createHelper() {
        try {
            $user = $this->auth->authorizeAny(['resident', 'guard', 'admin']);
            $this->ensureTablesExist();

            $data = json_decode(file_get_contents("php://input"), true);
            $errors = $this->validateRequiredFields($data, ['name', 'phone', 'service_type']);
            if (!empty($errors)) {
                Response::validationError($errors);
            }

            $societyId = $user['society_id'];
            $flatId = null;
            $residentId = null;

            if ($user['role'] === 'resident') {
                $residentId = $user['uid'];
                // Find resident's flat
                $flatStmt = $this->db->prepare("
                    SELECT f.id 
                    FROM flats f 
                    WHERE (f.owner_id = ? OR f.tenant_id = ?) AND f.society_id = ?
                    LIMIT 1
                ");
                $flatStmt->execute([$residentId, $residentId, $societyId]);
                $flat = $flatStmt->fetch();
                if (!$flat) {
                    Response::error("No flat linked to your resident account", 400);
                }
                $flatId = $flat['id'];
            } else {
                // Guard or Admin: flat_id is required
                if (empty($data['flat_id'])) {
                    Response::error("flat_id is required", 400);
                }
                $flatId = $data['flat_id'];
                // Validate flat belongs to society
                $flatStmt = $this->db->prepare("SELECT id, owner_id, tenant_id FROM flats WHERE id = ? AND society_id = ?");
                $flatStmt->execute([$flatId, $societyId]);
                $flat = $flatStmt->fetch();
                if (!$flat) {
                    Response::notFound("Flat not found in your society");
                }
                $residentId = $flat['tenant_id'] ?? $flat['owner_id'] ?? null;
            }

            // Normalize phone
            $phone = preg_replace('/\D/', '', (string)$data['phone']);

            // Generate unique static QR code token
            // Format: DHP-{societyId}-{flatId}-{RandomToken}
            $uniquePart = strtoupper(bin2hex(random_bytes(4)));
            $qrCode = "DHP-S{$societyId}-F{$flatId}-{$uniquePart}";

            $insertData = [
                'society_id' => $societyId,
                'flat_id' => $flatId,
                'resident_id' => $residentId,
                'name' => trim($data['name']),
                'phone' => $phone,
                'service_type' => trim($data['service_type']),
                'qr_code' => $qrCode,
                'image_url' => $data['image_url'] ?? null,
                'id_proof_url' => $data['id_proof_url'] ?? null,
                'vehicle_number' => !empty($data['vehicle_number']) ? strtoupper(trim($data['vehicle_number'])) : null,
                'expected_time_in' => !empty($data['expected_time_in']) ? $data['expected_time_in'] : null,
                'expected_time_out' => !empty($data['expected_time_out']) ? $data['expected_time_out'] : null,
                'working_days' => $data['working_days'] ?? 'All Days',
                'is_active' => 1,
                'created_by' => $user['uid']
            ];

            $helperId = $this->insert('daily_helpers', $insertData);

            // Fetch created helper with flat info
            $helper = $this->getHelperRecordById($helperId);

            Response::success("Daily helper added successfully with static QR code", $helper, 201);

        } catch (Exception $e) {
            error_log("createHelper error: " . $e->getMessage());
            Response::error("Failed to add daily helper: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/daily-helpers
     * Retrieve all helpers.
     * Residents view only their flat helpers.
     * Guards & Admins can filter by flat_id or view society-wide.
     */
    public function getHelpers() {
        try {
            $user = $this->auth->authorizeAny(['resident', 'guard', 'admin']);
            $this->ensureTablesExist();

            $societyId = $user['society_id'];
            $where = "WHERE dh.society_id = :society_id AND dh.is_active = 1";
            $params = [':society_id' => $societyId];

            if ($user['role'] === 'resident') {
                $where .= " AND (dh.resident_id = :resident_id OR f.owner_id = :res_owner OR f.tenant_id = :res_tenant)";
                $params[':resident_id'] = $user['uid'];
                $params[':res_owner'] = $user['uid'];
                $params[':res_tenant'] = $user['uid'];
            } else {
                if (!empty($_GET['flat_id'])) {
                    $where .= " AND dh.flat_id = :flat_id";
                    $params[':flat_id'] = $_GET['flat_id'];
                }
            }

            if (!empty($_GET['service_type'])) {
                $where .= " AND dh.service_type = :service_type";
                $params[':service_type'] = $_GET['service_type'];
            }

            if (!empty($_GET['search'])) {
                $where .= " AND (dh.name LIKE :search OR dh.phone LIKE :search OR dh.qr_code LIKE :search)";
                $params[':search'] = '%' . $_GET['search'] . '%';
            }

            $sql = "
                SELECT dh.*,
                       f.flat_number,
                       b.name as building_name,
                       u.name as resident_name,
                       u.phone as resident_phone,
                       -- Current status (inside / exited)
                       (SELECT dhl.status 
                        FROM daily_helper_logs dhl 
                        WHERE dhl.daily_helper_id = dh.id 
                        ORDER BY dhl.id DESC 
                        LIMIT 1) as current_status,
                       (SELECT dhl.entry_time 
                        FROM daily_helper_logs dhl 
                        WHERE dhl.daily_helper_id = dh.id 
                        ORDER BY dhl.id DESC 
                        LIMIT 1) as last_entry_time,
                       (SELECT dhl.exit_time 
                        FROM daily_helper_logs dhl 
                        WHERE dhl.daily_helper_id = dh.id 
                        ORDER BY dhl.id DESC 
                        LIMIT 1) as last_exit_time
                FROM daily_helpers dh
                LEFT JOIN flats f ON dh.flat_id = f.id
                LEFT JOIN buildings b ON f.building_id = b.id
                LEFT JOIN users u ON dh.resident_id = u.id
                {$where}
                ORDER BY dh.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $helpers = $stmt->fetchAll();

            Response::success("Daily helpers retrieved successfully", $helpers);

        } catch (Exception $e) {
            error_log("getHelpers error: " . $e->getMessage());
            Response::error("Failed to fetch daily helpers: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/daily-helpers/:id
     */
    public function getHelperById($id) {
        try {
            $user = $this->auth->authorizeAny(['resident', 'guard', 'admin']);
            $this->ensureTablesExist();

            $helper = $this->getHelperRecordById($id);
            if (!$helper || $helper['society_id'] != $user['society_id']) {
                Response::notFound("Daily helper not found");
            }

            Response::success("Daily helper details retrieved", $helper);
        } catch (Exception $e) {
            Response::error("Failed to fetch details: " . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/daily-helpers/:id
     */
    public function updateHelper($id) {
        try {
            $user = $this->auth->authorizeAny(['resident', 'guard', 'admin']);
            $this->ensureTablesExist();

            $helper = $this->getHelperRecordById($id);
            if (!$helper || $helper['society_id'] != $user['society_id']) {
                Response::notFound("Daily helper not found");
            }

            $data = json_decode(file_get_contents("php://input"), true);
            $update = [];
            $allowed = ['name', 'phone', 'service_type', 'image_url', 'id_proof_url', 'vehicle_number', 'expected_time_in', 'expected_time_out', 'working_days', 'is_active'];
            foreach ($allowed as $f) {
                if (isset($data[$f])) {
                    $update[$f] = $data[$f];
                }
            }

            if (!empty($update['phone'])) {
                $update['phone'] = preg_replace('/\D/', '', (string)$update['phone']);
            }

            if (!empty($update)) {
                $this->update('daily_helpers', $update, 'id = :id', ['id' => $id]);
            }

            $updatedHelper = $this->getHelperRecordById($id);
            Response::success("Daily helper updated successfully", $updatedHelper);

        } catch (Exception $e) {
            Response::error("Failed to update helper: " . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/daily-helpers/:id
     * Soft delete
     */
    public function deleteHelper($id) {
        try {
            $user = $this->auth->authorizeAny(['resident', 'guard', 'admin']);
            $this->ensureTablesExist();

            $helper = $this->getHelperRecordById($id);
            if (!$helper || $helper['society_id'] != $user['society_id']) {
                Response::notFound("Daily helper not found");
            }

            $this->update('daily_helpers', ['is_active' => 0], 'id = :id', ['id' => $id]);
            Response::success("Daily helper deleted successfully");

        } catch (Exception $e) {
            Response::error("Failed to delete helper: " . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/daily-helpers/scan
     * Guard scans static QR code.
     * Behavior:
     * - Auto-checks current status.
     * - If currently outside/exited: Creates entry log (status = 'inside', entry_time = NOW()).
     * - If currently inside: Updates active log (status = 'exited', exit_time = NOW()).
     * Sends Firebase push notification to flat resident in real-time.
     */
    public function processScan() {
        try {
            $guard = $this->auth->authorizeAny(['guard', 'admin']);
            $this->ensureTablesExist();

            $data = json_decode(file_get_contents("php://input"), true);
            $qrData = trim($data['qr_code'] ?? '');

            if (empty($qrData)) {
                Response::error("QR code data is required", 400);
            }

            // Find helper by qr_code in this society
            $stmt = $this->db->prepare("
                SELECT dh.*, f.flat_number, b.name as building_name, u.name as resident_name, u.id as resident_user_id
                FROM daily_helpers dh
                LEFT JOIN flats f ON dh.flat_id = f.id
                LEFT JOIN buildings b ON f.building_id = b.id
                LEFT JOIN users u ON dh.resident_id = u.id
                WHERE dh.qr_code = ? AND dh.society_id = ? AND dh.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$qrData, $guard['society_id']]);
            $helper = $stmt->fetch();

            if (!$helper) {
                Response::error("Invalid or unrecognized Daily Helper QR code for this society", 404);
            }

            // Check if there is an active 'inside' log
            $logStmt = $this->db->prepare("
                SELECT * FROM daily_helper_logs 
                WHERE daily_helper_id = ? AND society_id = ? AND status = 'inside'
                ORDER BY id DESC LIMIT 1
            ");
            $logStmt->execute([$helper['id'], $guard['society_id']]);
            $activeLog = $logStmt->fetch();

            $notificationHelper = new NotificationHelper();
            $actionType = '';
            $logResult = [];

            if ($activeLog) {
                // MARK EXIT
                $exitTime = date('Y-m-d H:i:s');
                $this->update('daily_helper_logs', [
                    'exit_time' => $exitTime,
                    'guard_out_id' => $guard['uid'],
                    'status' => 'exited',
                    'exit_method' => 'qr_scan',
                    'notes' => $data['notes'] ?? $activeLog['notes']
                ], 'id = :id', ['id' => $activeLog['id']]);

                $actionType = 'exited';
                $logResult = [
                    'log_id' => $activeLog['id'],
                    'action' => 'exited',
                    'entry_time' => $activeLog['entry_time'],
                    'exit_time' => $exitTime
                ];

                // Notify Resident
                $residentToNotify = $helper['resident_user_id'] ?? $helper['resident_id'];
                if ($residentToNotify) {
                    try {
                        $timeStr = date('h:i A', strtotime($exitTime));
                        $notificationHelper->sendPushNotification(
                            $residentToNotify,
                            "Daily Help Exited",
                            "{$helper['name']} ({$helper['service_type']}) has exited at {$timeStr}.",
                            ['helper_id' => $helper['id'], 'type' => 'daily_help_exit'],
                            'daily_help_status',
                            $helper['id'],
                            '/daily-helpers'
                        );
                    } catch (\Exception $ignored) {}
                }

            } else {
                // MARK ENTRY
                $entryTime = date('Y-m-d H:i:s');
                $newLogId = $this->insert('daily_helper_logs', [
                    'daily_helper_id' => $helper['id'],
                    'society_id' => $guard['society_id'],
                    'flat_id' => $helper['flat_id'],
                    'entry_time' => $entryTime,
                    'guard_in_id' => $guard['uid'],
                    'status' => 'inside',
                    'entry_method' => 'qr_scan',
                    'notes' => $data['notes'] ?? null
                ]);

                $actionType = 'entered';
                $logResult = [
                    'log_id' => $newLogId,
                    'action' => 'entered',
                    'entry_time' => $entryTime,
                    'exit_time' => null
                ];

                // Notify Resident
                $residentToNotify = $helper['resident_user_id'] ?? $helper['resident_id'];
                if ($residentToNotify) {
                    try {
                        $timeStr = date('h:i A', strtotime($entryTime));
                        $notificationHelper->sendPushNotification(
                            $residentToNotify,
                            "Daily Help Checked-In",
                            "{$helper['name']} ({$helper['service_type']}) entered at Gate at {$timeStr}.",
                            ['helper_id' => $helper['id'], 'type' => 'daily_help_entry'],
                            'daily_help_status',
                            $helper['id'],
                            '/daily-helpers'
                        );
                    } catch (\Exception $ignored) {}
                }
            }

            Response::success("Helper entry/exit recorded successfully", [
                'action' => $actionType,
                'helper' => $helper,
                'log' => $logResult
            ]);

        } catch (Exception $e) {
            error_log("processScan error: " . $e->getMessage());
            Response::error("Failed to process QR scan: " . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/daily-helpers/manual-exit/:logId
     * Allows guard to manually mark exit for an active inside helper
     */
    public function manualExit($logId) {
        try {
            $guard = $this->auth->authorizeAny(['guard', 'admin']);
            $this->ensureTablesExist();

            $stmt = $this->db->prepare("
                SELECT dhl.*, dh.name, dh.service_type, dh.resident_id, dh.flat_id 
                FROM daily_helper_logs dhl
                JOIN daily_helpers dh ON dhl.daily_helper_id = dh.id
                WHERE dhl.id = ? AND dhl.society_id = ? AND dhl.status = 'inside'
            ");
            $stmt->execute([$logId, $guard['society_id']]);
            $log = $stmt->fetch();

            if (!$log) {
                Response::notFound("Active entry record not found or already exited");
            }

            $exitTime = date('Y-m-d H:i:s');
            $this->update('daily_helper_logs', [
                'exit_time' => $exitTime,
                'guard_out_id' => $guard['uid'],
                'status' => 'exited',
                'exit_method' => 'manual'
            ], 'id = :id', ['id' => $logId]);

            // Notify Resident
            if (!empty($log['resident_id'])) {
                try {
                    $timeStr = date('h:i A', strtotime($exitTime));
                    $notificationHelper = new NotificationHelper();
                    $notificationHelper->sendPushNotification(
                        $log['resident_id'],
                        "Daily Help Exited (Manual)",
                        "{$log['name']} ({$log['service_type']}) marked exit by guard at {$timeStr}.",
                        ['helper_id' => $log['daily_helper_id'], 'type' => 'daily_help_exit'],
                        'daily_help_status',
                        $log['daily_helper_id'],
                        '/daily-helpers'
                    );
                } catch (\Exception $ignored) {}
            }

            Response::success("Helper exit marked successfully");

        } catch (Exception $e) {
            Response::error("Failed to mark manual exit: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/daily-helpers/active-inside
     * Lists helpers currently marked as 'inside' society
     */
    public function getActiveInside() {
        try {
            $user = $this->auth->authorizeAny(['guard', 'admin']);
            $this->ensureTablesExist();

            $sql = "
                SELECT dhl.*,
                       dh.name as helper_name,
                       dh.phone as helper_phone,
                       dh.service_type,
                       dh.image_url as helper_image,
                       dh.qr_code,
                       f.flat_number,
                       b.name as building_name,
                       u.name as resident_name,
                       g.name as guard_in_name
                FROM daily_helper_logs dhl
                JOIN daily_helpers dh ON dhl.daily_helper_id = dh.id
                LEFT JOIN flats f ON dhl.flat_id = f.id
                LEFT JOIN buildings b ON f.building_id = b.id
                LEFT JOIN users u ON dh.resident_id = u.id
                LEFT JOIN users g ON dhl.guard_in_id = g.id
                WHERE dhl.society_id = ? AND dhl.status = 'inside'
                ORDER BY dhl.entry_time DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user['society_id']]);
            $list = $stmt->fetchAll();

            Response::success("Active inside helpers retrieved", $list);

        } catch (Exception $e) {
            Response::error("Failed to fetch active inside helpers: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/daily-helpers/logs
     * Attendance history logs.
     * Residents only see logs for their flat.
     * Guards/Admins can filter by helper_id, flat_id, date range.
     */
    public function getLogs() {
        try {
            $user = $this->auth->authorizeAny(['resident', 'guard', 'admin']);
            $this->ensureTablesExist();

            $societyId = $user['society_id'];
            $where = "WHERE dhl.society_id = :society_id";
            $params = [':society_id' => $societyId];

            if ($user['role'] === 'resident') {
                $where .= " AND (dh.resident_id = :resident_id OR f.owner_id = :res_owner OR f.tenant_id = :res_tenant)";
                $params[':resident_id'] = $user['uid'];
                $params[':res_owner'] = $user['uid'];
                $params[':res_tenant'] = $user['uid'];
            } else {
                if (!empty($_GET['flat_id'])) {
                    $where .= " AND dhl.flat_id = :flat_id";
                    $params[':flat_id'] = $_GET['flat_id'];
                }
                if (!empty($_GET['daily_helper_id'])) {
                    $where .= " AND dhl.daily_helper_id = :daily_helper_id";
                    $params[':daily_helper_id'] = $_GET['daily_helper_id'];
                }
            }

            if (!empty($_GET['date'])) {
                $where .= " AND DATE(dhl.entry_time) = :entry_date";
                $params[':entry_date'] = $_GET['date'];
            }

            $sql = "
                SELECT dhl.*,
                       dh.name as helper_name,
                       dh.phone as helper_phone,
                       dh.service_type,
                       dh.image_url as helper_image,
                       f.flat_number,
                       b.name as building_name,
                       u.name as resident_name,
                       gin.name as guard_in_name,
                       gout.name as guard_out_name
                FROM daily_helper_logs dhl
                JOIN daily_helpers dh ON dhl.daily_helper_id = dh.id
                LEFT JOIN flats f ON dhl.flat_id = f.id
                LEFT JOIN buildings b ON f.building_id = b.id
                LEFT JOIN users u ON dh.resident_id = u.id
                LEFT JOIN users gin ON dhl.guard_in_id = gin.id
                LEFT JOIN users gout ON dhl.guard_out_id = gout.id
                {$where}
                ORDER BY dhl.entry_time DESC
                LIMIT 100
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();

            Response::success("Daily helper logs retrieved successfully", $logs);

        } catch (Exception $e) {
            error_log("getLogs error: " . $e->getMessage());
            Response::error("Failed to retrieve helper logs: " . $e->getMessage(), 500);
        }
    }

    private function getHelperRecordById($id) {
        $stmt = $this->db->prepare("
            SELECT dh.*,
                   f.flat_number,
                   b.name as building_name,
                   u.name as resident_name,
                   u.phone as resident_phone
            FROM daily_helpers dh
            LEFT JOIN flats f ON dh.flat_id = f.id
            LEFT JOIN buildings b ON f.building_id = b.id
            LEFT JOIN users u ON dh.resident_id = u.id
            WHERE dh.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
