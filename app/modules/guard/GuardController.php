<?php
require_once __DIR__ . '/../../core/BaseController.php';

/**
 * GuardController
 * Endpoints accessible only by guards (and admins) for gate operations.
 */
class GuardController extends BaseController
{
    /**
     * GET /api/guard/residents
     * Returns residents in the same society — used by guards for visitor entry.
     */
    public function getResidents()
    {
        try {
            $user = $this->auth->authorizeAny(['guard', 'admin']);

            $page   = isset($_GET['page'])   ? (int) $_GET['page']   : 1;
            $limit  = isset($_GET['limit'])  ? (int) $_GET['limit']  : 50;
            $search = isset($_GET['search']) ? trim($_GET['search'])  : null;

            $pagination = $this->paginate($page, $limit);

            $whereClause = "WHERE u.society_id = :society_id AND u.role = 'resident' AND u.status = 'active'";
            $params = ['society_id' => $user['society_id']];

            if ($search) {
                $whereClause .= " AND (u.name LIKE :search OR u.phone LIKE :search)";
                $params['search'] = "%{$search}%";
            }

            $countSql = "SELECT COUNT(*) as count FROM users u {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['count'];

            $sql = "
                SELECT u.id, u.name, u.phone,
                       (SELECT CONCAT(b.name, '-', f.flat_number)
                        FROM flats f
                        LEFT JOIN buildings b ON f.building_id = b.id
                        WHERE (f.owner_id = u.id OR f.tenant_id = u.id)
                        LIMIT 1) AS flat_number
                FROM users u
                {$whereClause}
                ORDER BY u.name ASC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $residents = $stmt->fetchAll();

            $this->sendPaginatedResponse($residents, $total, $pagination, "Residents retrieved successfully");

        } catch (Exception $e) {
            error_log("Guard getResidents error: " . $e->getMessage());
            Response::error("Failed to retrieve residents: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/guard/vehicle-entries
     * Returns vehicle entry logs for the society.
     */
    public function getVehicleEntries()
    {
        try {
            $user = $this->auth->authorizeAny(['guard', 'admin']);

            $page   = isset($_GET['page'])   ? (int) $_GET['page']  : 1;
            $limit  = isset($_GET['limit'])  ? (int) $_GET['limit'] : 50;
            $status = $_GET['status'] ?? null;

            $pagination = $this->paginate($page, $limit);

            $whereClause = "WHERE ve.society_id = :society_id";
            $params = ['society_id' => $user['society_id']];

            if ($status) {
                $whereClause .= " AND ve.status = :status";
                $params['status'] = $status;
            }

            $countSql = "SELECT COUNT(*) as count FROM guard_vehicle_entries ve {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['count'];

            $sql = "
                SELECT ve.*, g.name as guard_name, u.name as resident_name
                FROM guard_vehicle_entries ve
                LEFT JOIN users g ON ve.guard_id = g.id
                LEFT JOIN users u ON ve.resident_id = u.id
                {$whereClause}
                ORDER BY ve.created_at DESC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $entries = $stmt->fetchAll();

            $this->sendPaginatedResponse($entries, $total, $pagination, "Vehicle entries retrieved successfully");

        } catch (Exception $e) {
            error_log("Guard getVehicleEntries error: " . $e->getMessage());
            Response::error("Failed to retrieve vehicle entries: " . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/guard/vehicle-entries
     * Log a utility/service vehicle entry at the gate.
     */
    public function addVehicleEntry()
    {
        try {
            $user = $this->auth->authorizeAny(['guard', 'admin']);

            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['vehicle_type', 'vehicle_number', 'driver_name', 'driver_phone', 'purpose']);
            if (!empty($errors)) {
                Response::validationError($errors);
            }

            // Normalize phone
            $data['driver_phone'] = preg_replace('/\D/', '', (string) $data['driver_phone']);
            if (!preg_match('/^[0-9]{10,15}$/', $data['driver_phone'])) {
                Response::error("Driver phone must be 10-15 digits");
            }

            $entryId = $this->insert('guard_vehicle_entries', [
                'vehicle_type'   => $data['vehicle_type'],
                'vehicle_number' => strtoupper(preg_replace('/\s+/', '', $data['vehicle_number'])),
                'driver_name'    => $data['driver_name'],
                'driver_phone'   => $data['driver_phone'],
                'purpose'        => $data['purpose'],
                'resident_id'    => $data['resident_id'] ?? null,
                'guard_id'       => $user['uid'],
                'society_id'     => $user['society_id'],
                'status'         => 'inside',
                'entry_time'     => date('Y-m-d H:i:s'),
            ]);

            Response::success("Vehicle entry logged successfully", ['entry_id' => $entryId], 201);

        } catch (Exception $e) {
            error_log("Guard addVehicleEntry error: " . $e->getMessage());
            Response::error("Failed to log vehicle entry: " . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/guard/vehicle-entries/:id/status
     * Update vehicle entry status (inside → exited).
     */
    public function updateVehicleEntryStatus($id)
    {
        try {
            $user = $this->auth->authorizeAny(['guard', 'admin']);

            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['status'])) {
                Response::error("Status is required");
            }

            $allowedStatuses = ['inside', 'exited'];
            if (!in_array($data['status'], $allowedStatuses)) {
                Response::error("Invalid status. Allowed: " . implode(', ', $allowedStatuses));
            }

            $stmt = $this->db->prepare("SELECT id, status FROM guard_vehicle_entries WHERE id = ? AND society_id = ?");
            $stmt->execute([$id, $user['society_id']]);
            $entry = $stmt->fetch();

            if (!$entry) {
                Response::notFound("Vehicle entry not found");
            }

            $updateData = ['status' => $data['status']];
            if ($data['status'] === 'exited') {
                $updateData['exit_time'] = date('Y-m-d H:i:s');
            }

            $this->update('guard_vehicle_entries', $updateData, 'id = :id', ['id' => $id]);

            Response::success("Vehicle entry status updated successfully", $updateData);

        } catch (Exception $e) {
            error_log("Guard updateVehicleEntryStatus error: " . $e->getMessage());
            Response::error("Failed to update vehicle entry: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/guard/attendance
     * Returns guard's attendance — today's record + history.
     */
    public function getAttendance()
    {
        try {
            $user = $this->auth->authorizeAny(['guard', 'admin']);

            $today = date('Y-m-d');

            // Today's record
            $stmt = $this->db->prepare("
                SELECT * FROM guard_attendance
                WHERE guard_id = :guard_id AND DATE(date) = :today
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute(['guard_id' => $user['uid'], 'today' => $today]);
            $todayRecord = $stmt->fetch() ?: null;

            // Last 30 days history
            $stmt = $this->db->prepare("
                SELECT * FROM guard_attendance
                WHERE guard_id = :guard_id AND DATE(date) >= DATE_SUB(:today, INTERVAL 30 DAY)
                ORDER BY date DESC
                LIMIT 30
            ");
            $stmt->execute(['guard_id' => $user['uid'], 'today' => $today]);
            $history = $stmt->fetchAll();

            Response::success("Attendance retrieved successfully", [
                'today'   => $todayRecord,
                'history' => $history,
            ]);

        } catch (Exception $e) {
            error_log("Guard getAttendance error: " . $e->getMessage());
            Response::error("Failed to retrieve attendance: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/guards
     * Admin: list all guards in their society with optional search/status filters.
     */
    public function adminListGuards()
    {
        try {
            $user = $this->auth->authorizeAny(['admin']);

            $page   = isset($_GET['page'])   ? (int) $_GET['page']   : 1;
            $limit  = isset($_GET['limit'])  ? (int) $_GET['limit']  : 20;
            $search = isset($_GET['search']) ? trim($_GET['search'])  : null;
            $status = isset($_GET['status']) ? trim($_GET['status'])  : null;

            $pagination = $this->paginate($page, $limit);

            $whereClause = "WHERE u.society_id = :society_id AND u.role = 'guard'";
            $params = ['society_id' => $user['society_id']];

            if ($search) {
                $whereClause .= " AND (u.name LIKE :search OR u.phone LIKE :search OR u.email LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            if ($status) {
                $whereClause .= " AND u.status = :status";
                $params['status'] = $status;
            }

            $countSql = "SELECT COUNT(*) as count FROM users u {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['count'];

            $sql = "
                SELECT u.id, u.app_user_id, u.name, u.phone, u.email, u.status,
                       u.profile_image, u.created_at,
                       (SELECT ga.status FROM guard_attendance ga
                        WHERE ga.guard_id = u.id AND DATE(ga.date) = CURDATE()
                        LIMIT 1) AS today_status,
                       (SELECT ga.in_time FROM guard_attendance ga
                        WHERE ga.guard_id = u.id AND DATE(ga.date) = CURDATE()
                        LIMIT 1) AS today_in_time,
                       (SELECT ga.out_time FROM guard_attendance ga
                        WHERE ga.guard_id = u.id AND DATE(ga.date) = CURDATE()
                        LIMIT 1) AS today_out_time
                FROM users u
                {$whereClause}
                ORDER BY u.name ASC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $guards = $stmt->fetchAll();

            $this->sendPaginatedResponse($guards, $total, $pagination, "Guards retrieved successfully");

        } catch (Exception $e) {
            error_log("adminListGuards error: " . $e->getMessage());
            Response::error("Failed to retrieve guards: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/guards/attendance
     * Admin: get attendance records for all guards in society.
     * Query params: guard_id, date_from, date_to, status, page, limit
     */
    public function adminGetAttendance()
    {
        try {
            $user = $this->auth->authorizeAny(['admin']);

            $page     = isset($_GET['page'])      ? (int) $_GET['page']          : 1;
            $limit    = isset($_GET['limit'])     ? (int) $_GET['limit']         : 30;
            $guardId  = isset($_GET['guard_id'])  ? (int) $_GET['guard_id']      : null;
            $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from'])      : date('Y-m-01'); // default: 1st of month
            $dateTo   = isset($_GET['date_to'])   ? trim($_GET['date_to'])        : date('Y-m-d');
            $status   = isset($_GET['status'])    ? trim($_GET['status'])         : null;

            $pagination = $this->paginate($page, $limit);

            $whereClause = "WHERE u.society_id = :society_id";
            $params = ['society_id' => $user['society_id']];

            if ($guardId) {
                $whereClause .= " AND ga.guard_id = :guard_id";
                $params['guard_id'] = $guardId;
            }
            if ($dateFrom) {
                $whereClause .= " AND ga.date >= :date_from";
                $params['date_from'] = $dateFrom;
            }
            if ($dateTo) {
                $whereClause .= " AND ga.date <= :date_to";
                $params['date_to'] = $dateTo;
            }
            if ($status) {
                $whereClause .= " AND ga.status = :status";
                $params['status'] = $status;
            }

            $countSql = "
                SELECT COUNT(*) as count
                FROM guard_attendance ga
                JOIN users u ON ga.guard_id = u.id
                {$whereClause}
            ";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['count'];

            $sql = "
                SELECT ga.*, u.name as guard_name, u.phone as guard_phone
                FROM guard_attendance ga
                JOIN users u ON ga.guard_id = u.id
                {$whereClause}
                ORDER BY ga.date DESC, u.name ASC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $records = $stmt->fetchAll();

            // Summary counts for the queried range
            $summaryStmt = $this->db->prepare("
                SELECT
                    COUNT(*) as total_records,
                    SUM(CASE WHEN ga.status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN ga.status = 'absent'   THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN ga.status = 'half_day' THEN 1 ELSE 0 END) as half_day_count
                FROM guard_attendance ga
                JOIN users u ON ga.guard_id = u.id
                {$whereClause}
            ");
            $summaryStmt->execute($params);
            $summary = $summaryStmt->fetch();

            $this->sendPaginatedResponse($records, $total, $pagination, "Attendance retrieved successfully", [
                'summary' => $summary,
            ]);

        } catch (Exception $e) {
            error_log("adminGetAttendance error: " . $e->getMessage());
            Response::error("Failed to retrieve attendance: " . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/guard/attendance/mark
     * Mark guard check-in or check-out.
     */
    public function markAttendance()
    {
        try {
            $user = $this->auth->authorizeAny(['guard', 'admin']);

            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['type']) || !in_array($data['type'], ['in', 'out'])) {
                Response::error("Type must be 'in' or 'out'");
            }

            $today = date('Y-m-d');
            $now   = date('Y-m-d H:i:s');

            // Fetch existing record for today
            $stmt = $this->db->prepare("
                SELECT id, in_time, out_time FROM guard_attendance
                WHERE guard_id = :guard_id AND DATE(date) = :today
                LIMIT 1
            ");
            $stmt->execute(['guard_id' => $user['uid'], 'today' => $today]);
            $existing = $stmt->fetch();

            if ($data['type'] === 'in') {
                if ($existing) {
                    Response::error("Already checked in today");
                }
                $this->insert('guard_attendance', [
                    'guard_id'   => $user['uid'],
                    'society_id' => $user['society_id'],
                    'date'       => $today,
                    'in_time'    => $now,
                    'status'     => 'present',
                ]);
                Response::success("Check-in marked successfully", ['in_time' => $now]);
            } else {
                if (!$existing) {
                    Response::error("No check-in found for today");
                }
                if ($existing['out_time']) {
                    Response::error("Already checked out today");
                }
                $this->update('guard_attendance', ['out_time' => $now], 'id = :id', ['id' => $existing['id']]);
                Response::success("Check-out marked successfully", ['out_time' => $now]);
            }

        } catch (Exception $e) {
            error_log("Guard markAttendance error: " . $e->getMessage());
            Response::error("Failed to mark attendance: " . $e->getMessage(), 500);
        }
    }
}
