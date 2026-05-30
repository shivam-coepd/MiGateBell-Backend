<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../helpers/app_userID_helper.php';

class UserManagementController extends BaseController
{

    public function getUsers()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);

            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
            $role = isset($_GET['role']) ? $_GET['role'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $search = isset($_GET['search']) ? $_GET['search'] : null;

            $pagination = $this->paginate($page, $limit);

            $whereClause = "WHERE 1=1";
            $params = [];

            // Society filter for admins
            if ($user['role'] === 'admin') {
                $whereClause .= " AND u.society_id = :society_id";
                $params['society_id'] = $user['society_id'];
            } elseif ($user['role'] === 'super_admin' && isset($_GET['society_id'])) {
                $whereClause .= " AND u.society_id = :society_id";
                $params['society_id'] = $_GET['society_id'];
            }

            // Role filter
            if ($role) {
                $whereClause .= " AND u.role = :role";
                $params['role'] = $role;
            }

            // Status filter
            if ($status) {
                $whereClause .= " AND u.status = :status";
                $params['status'] = $status;
            }

            // Search filter
            if ($search) {
                $whereClause .= " AND (u.name LIKE :search OR u.phone LIKE :search OR u.email LIKE :search)";
                $params['search'] = "%$search%";
            }

            // Get total count
            $countSql = "SELECT COUNT(*) as count FROM users u $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['count'];

            // Get users with society info
            $sql = "
                SELECT u.*, s.name as society_name,
                       (SELECT COUNT(*) FROM flats WHERE owner_id = u.id OR tenant_id = u.id) as flat_count,
                       (SELECT COUNT(*) FROM vehicles WHERE resident_id = u.id) as vehicle_count
                FROM users u
                LEFT JOIN societies s ON u.society_id = s.id
                $whereClause
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll();

            $this->sendPaginatedResponse($users, $total, $pagination, "Users retrieved successfully");

        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            Response::error("Failed to retrieve users: " . $e->getMessage(), 500);
        }
    }

    public function getUserById($id)
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);

            $whereClause = "WHERE u.id = :id";
            $params = ['id' => $id];

            if ($user['role'] === 'admin') {
                $whereClause .= " AND u.society_id = :society_id";
                $params['society_id'] = $user['society_id'];
            }

            $sql = "
                SELECT u.*, s.name as society_name
                FROM users u
                LEFT JOIN societies s ON u.society_id = s.id
                $whereClause
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $targetUser = $stmt->fetch();

            if (!$targetUser)
                Response::notFound("User not found");

            // Get additional details
            // Flats
            $stmt = $this->db->prepare("
                SELECT f.*, b.name as building_name
                FROM flats f
                LEFT JOIN buildings b ON f.building_id = b.id
                WHERE f.owner_id = ? OR f.tenant_id = ?
            ");
            $stmt->execute([$id, $id]);
            $targetUser['flats'] = $stmt->fetchAll();

            // Vehicles
            $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE resident_id = ?");
            $stmt->execute([$id]);
            $targetUser['vehicles'] = $stmt->fetchAll();

            // Family members
            $stmt = $this->db->prepare("SELECT * FROM family_members WHERE resident_id = ? AND is_active = 1");
            $stmt->execute([$id]);
            $targetUser['family_members'] = $stmt->fetchAll();

            // Recent visitors (if resident)
            if ($targetUser['role'] === 'resident') {
                $stmt = $this->db->prepare("
                    SELECT * FROM visitors 
                    WHERE resident_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ");
                $stmt->execute([$id]);
                $targetUser['recent_visitors'] = $stmt->fetchAll();
            }

            Response::success("User details retrieved", $targetUser);

        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            Response::error("Failed to retrieve user: " . $e->getMessage(), 500);
        }
    }

    public function createUser()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name', 'phone', 'password', 'role']);
            if (!empty($errors))
                Response::validationError($errors);

            // Validate phone
            if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
                Response::error("Phone number must be 10-15 digits");
            }

            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$data['phone']]);
            if ($stmt->fetch())
                Response::error("User with this phone already exists", 409);

            // Validate role
            $allowedRoles = ['admin', 'resident', 'guard', 'staff'];
            if ($user['role'] === 'super_admin')
                $allowedRoles[] = 'super_admin';

            if (!in_array($data['role'], $allowedRoles)) {
                Response::error("Invalid role");
            }

            // Society validation
            $societyId = $user['role'] === 'admin' ? $user['society_id'] : ($data['society_id'] ?? null);

            if ($data['role'] !== 'super_admin' && !$societyId) {
                Response::error("Society ID required for this role");
            }

            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $appUserId = AppUserIdHelper::generateUnique($this->db);

            $userId = $this->insert('users', [
                'app_user_id' => $appUserId,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $hashedPassword,
                'role' => $data['role'],
                'society_id' => $societyId,
                'status' => $data['status'] ?? 'active',
                'profile_image' => $data['profile_image'] ?? null
            ]);

            Response::success("User created successfully", ['user_id' => $userId], 201);

        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            Response::error("Failed to create user: " . $e->getMessage(), 500);
        }
    }

    public function updateUser($id)
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);
            $data = json_decode(file_get_contents("php://input"), true);

            // Verify user exists and permissions
            $stmt = $this->db->prepare("SELECT id, society_id FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $targetUser = $stmt->fetch();

            if (!$targetUser)
                Response::notFound("User not found");

            if ($user['role'] === 'admin' && $targetUser['society_id'] != $user['society_id']) {
                Response::forbidden("Cannot update users from other societies");
            }

            $updateData = [];
            $allowedFields = ['name', 'email', 'status', 'profile_image'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData))
                Response::error("No valid fields to update");

            $this->update('users', $updateData, 'id = :id', ['id' => $id]);
            Response::success("User updated successfully", $updateData);

        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            Response::error("Failed to update user: " . $e->getMessage(), 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);

            $stmt = $this->db->prepare("SELECT id, society_id FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $targetUser = $stmt->fetch();

            if (!$targetUser)
                Response::notFound("User not found");

            if ($user['role'] === 'admin' && $targetUser['society_id'] != $user['society_id']) {
                Response::forbidden("Cannot delete users from other societies");
            }

            // Soft delete by setting status to inactive
            $this->update('users', ['status' => 'inactive'], 'id = :id', ['id' => $id]);
            Response::success("User deleted successfully");

        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            Response::error("Failed to delete user: " . $e->getMessage(), 500);
        }
    }
}
