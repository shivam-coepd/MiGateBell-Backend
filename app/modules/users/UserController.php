<?php
require_once __DIR__ . '/../../core/BaseController.php';

class UserController extends BaseController
{

    public function getProfile()
    {
        try {
            $user = $this->auth->authenticate();
            $userId = $user['uid'];
            $role = $user['role'];

            // Fetch basic user details
            $stmt = $this->db->prepare("
        SELECT id, name, email, phone, role, society_id, profile_image, status, created_at
        FROM users 
        WHERE id = ?
      ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();

            if (!$profile) {
                Response::notFound("User not found");
            }

            // Fetch society details if applicable
            if ($profile['society_id']) {
                $stmt = $this->db->prepare("SELECT name, address, city, state FROM societies WHERE id = ?");
                $stmt->execute([$profile['society_id']]);
                $profile['society'] = $stmt->fetch();
            }

            // Role specific data
            switch ($role) {
                case 'resident':
                    $profile['resident_data'] = $this->getResidentData($userId);
                    break;
                case 'guard':
                    $profile['guard_data'] = $this->getGuardData($userId);
                    break;
                case 'admin':
                    // Admin specific data (maybe stats?)
                    break;
                case 'staff':
                    $profile['staff_data'] = $this->getStaffData($userId);
                    break;
            }

            Response::success("Profile retrieved successfully", $profile);

        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            Response::error("Failed to retrieve profile: " . $e->getMessage(), 500);
        }
    }

    private function getResidentData($userId)
    {
        $data = [];

        // Flats
        //     $stmt = $this->db->prepare("
        //   SELECT f.id, f.flat_number, f.block, f.floor_number, b.name as building_name
        //   FROM flats f
        //   LEFT JOIN buildings b ON f.building_id = b.id
        //   WHERE f.owner_id = ? OR f.tenant_id = ?
        // ");
        // Check if 'block' column exists in flats, earlier schema didn't show it but 'building_name' via join.
        // Re-checking schema: flats has building_id. buildings has name.
        // I will use valid schema columns.

        $stmt = $this->db->prepare("
      SELECT f.id, f.flat_number, f.floor_number, f.area_sqft, f.is_occupied, b.name as building_name
      FROM flats f
      LEFT JOIN buildings b ON f.building_id = b.id
      WHERE f.owner_id = ? OR f.tenant_id = ?
    ");
        $stmt->execute([$userId, $userId]);
        $data['flats'] = $stmt->fetchAll();

        // Vehicles
        $stmt = $this->db->prepare("
      SELECT v.*, vt.name as type_name
      FROM vehicles v
      LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
      WHERE v.resident_id = ?
    ");
        $stmt->execute([$userId]);
        $data['vehicles'] = $stmt->fetchAll();

        // Pets
        $stmt = $this->db->prepare("SELECT * FROM pets WHERE resident_id = ?");
        $stmt->execute([$userId]);
        $data['pets'] = $stmt->fetchAll();

        // Family Members / Co-residents (Users in same flats)
        if (!empty($data['flats'])) {
            $flatIds = array_column($data['flats'], 'id');
            if (!empty($flatIds)) {
                $placeholders = str_repeat('?,', count($flatIds) - 1) . '?';

                // Find other users who are owners or tenants of these flats
                // This is a bit simplistic, usually family members are separate entities linked to the primary resident or flat.
                // But based on schema, we only have owners and tenants.
                // If there's no "family_members" table, maybe we just list co-owners/tenants?
                // Actually, let's just stick to what we know. If there is no family table, we can't invent one.
                // Assuming for now we just return main assets.
            }
        }

        return $data;
    }

    private function getGuardData($userId)
    {
        $data = [];
        // Maybe recent visitors handled?
        $stmt = $this->db->prepare("
      SELECT count(*) as today_visitors 
      FROM visitors 
      WHERE guard_id = ? AND DATE(created_at) = CURRENT_DATE
    ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $data['today_stats'] = $result;

        return $data;
    }

    private function getStaffData($userId)
    {
        $data = [];

        // Assigned Tickets
        $stmt = $this->db->prepare("
          SELECT count(*) as open_tickets
          FROM tickets
          WHERE assigned_to = ? AND status IN ('open', 'in_progress')
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $data['tasks'] = $result;

        return $data;
    }

    public function updateProfile()
    {
        try {
            $user = $this->auth->authenticate();
            $userId = $user['uid'];

            $data = json_decode(file_get_contents("php://input"), true);

            // Allowed fields to update
            $allowedFields = ['name', 'email', 'profile_image'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                Response::error("No valid fields to update");
            }

            // Validate email if present
            if (isset($updateData['email']) && !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                Response::error("Invalid email format");
            }

            // Update user
            $updated = $this->update('users', $updateData, 'id = :id', ['id' => $userId]);

            if ($updated === 0) {
                // It might be 0 if data is same
                // Response::success("Profile is already up to date");
            }

            Response::success("Profile updated successfully");

        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            Response::error("Failed to update profile: " . $e->getMessage(), 500);
        }
    }
}
