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
            //     $stmt = $this->db->prepare("
            //     SELECT id, name, email, phone, role, society_id, profile_image, status, created_at
            //     FROM users 
            //     WHERE id = ?
            // ");
            // Fetch basic user details
            $stmt = $this->db->prepare("
                SELECT id, app_user_id, name, email, phone, role, society_id, profile_image, cover_image_url, resident_type, bio, profession, hometown, status, created_at
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

        // Family Members
        $stmt = $this->db->prepare("
      SELECT fm.id, fm.name, fm.relation, fm.phone, fm.image_url, fm.is_active, 
      u.name AS resident_name, u.email AS resident_email
      FROM family_members fm
      LEFT JOIN users u ON fm.resident_id = u.id
      WHERE fm.resident_id = ?
    ");
        $stmt->execute([$userId]);
        $data['family_members'] = $stmt->fetchAll();


        // Flats
        $stmt = $this->db->prepare("
      SELECT f.id, f.flat_number, f.floor_number, f.area_sqft, f.is_occupied, 
      b.name as building_name
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

        // // Pets
        // $stmt = $this->db->prepare("SELECT * FROM pets WHERE resident_id = ?");
        // $stmt->execute([$userId]);
        // $data['pets'] = $stmt->fetchAll();
        // Pets (with Pet Type data)
        $stmt = $this->db->prepare("
      SELECT p.id, p.resident_id, p.name, p.breed, p.age, p.weight, p.vaccination_status, p.image_url, p.notes, p.society_id, p.is_active, p.created_at,
      pt.id AS pet_type_id, pt.name AS pet_type_name, pt.description AS pet_type_description
      FROM pets p
      LEFT JOIN pet_types pt ON p.pet_type_id = pt.id
      WHERE p.resident_id = ? AND p.is_active = 1
      ORDER BY p.created_at DESC
    ");
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

    // public function updateProfile()
    // {
    //     try {
    //         $user = $this->auth->authenticate();
    //         $userId = $user['uid'];

    //         $data = json_decode(file_get_contents("php://input"), true);

    //         // Allowed fields to update
    //         $allowedFields = ['name', 'email', 'profile_image'];
    //         $updateData = [];

    //         foreach ($allowedFields as $field) {
    //             if (isset($data[$field])) {
    //                 $updateData[$field] = $data[$field];
    //             }
    //         }

    //         if (empty($updateData)) {
    //             Response::error("No valid fields to update");
    //         }

    //         // Validate email if present
    //         if (isset($updateData['email']) && !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
    //             Response::error("Invalid email format");
    //         }

    //         // Update user
    //         $updated = $this->update('users', $updateData, 'id = :id', ['id' => $userId]);

    //         if ($updated === 0) {
    //             // It might be 0 if data is same
    //             // Response::success("Profile is already up to date");
    //         }

    //         Response::success("Profile updated successfully");

    //     } catch (Exception $e) {
    //         error_log("Update profile error: " . $e->getMessage());
    //         Response::error("Failed to update profile: " . $e->getMessage(), 500);
    //     }
    // }

    public function updateProfile()
    {
        try {
            $user = $this->auth->authenticate();
            $userId = $user['uid'];

            $data = json_decode(file_get_contents("php://input"), true);

            // Allowed fields to update
            $allowedFields = [
                'name',
                'email',
                'profile_image',
                'cover_image_url',
                'resident_type',
                'bio',
                'profession',
                'hometown'
            ];

            $updateData = [];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                Response::error("No valid fields to update");
            }

            /* ===================== VALIDATIONS ===================== */

            // Name validation
            if (isset($updateData['name']) && strlen($updateData['name']) > 100) {
                Response::error("Name must be less than 100 characters");
            }

            // Email validation
            if (
                isset($updateData['email']) &&
                !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)
            ) {
                Response::error("Invalid email format");
            }

            // Resident type validation
            if (isset($updateData['resident_type'])) {
                $allowedResidentTypes = ['owner', 'tenant', 'family_member', 'other'];
                if (!in_array($updateData['resident_type'], $allowedResidentTypes)) {
                    Response::error(
                        "Invalid resident_type. Allowed values: " .
                        implode(', ', $allowedResidentTypes)
                    );
                }
            }

            // Bio length (optional but recommended)
            if (isset($updateData['bio']) && strlen($updateData['bio']) > 500) {
                Response::error("Bio must be less than 500 characters");
            }

            // Profession length
            if (isset($updateData['profession']) && strlen($updateData['profession']) > 150) {
                Response::error("Profession must be less than 150 characters");
            }

            // Hometown length
            if (isset($updateData['hometown']) && strlen($updateData['hometown']) > 150) {
                Response::error("Hometown must be less than 150 characters");
            }

            // Image URL length (basic sanity check)
            // if (isset($updateData['profile_image']) && strlen($updateData['profile_image']) > 255) {
            //     Response::error("Profile image URL is too long");
            // }

            // if (isset($updateData['cover_image_url']) && strlen($updateData['cover_image_url']) > 255) {
            //     Response::error("Cover image URL is too long");
            // }

            /* ===================== UPDATE ===================== */

            $updated = $this->update(
                'users',
                $updateData,
                'id = :id',
                ['id' => $userId]
            );

            Response::success("Profile updated successfully", $updateData);

        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            Response::error("Failed to update profile: " . $e->getMessage(), 500);
        }
    }

}
