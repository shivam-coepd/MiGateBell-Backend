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

            if ($profile['role'] === 'admin') {
                $linkedSocId = $this->autoLinkAdminSociety($userId);
                if ($linkedSocId) {
                    $profile['society_id'] = $linkedSocId;
                }
            }

            // Fetch society details if applicable
            if ($profile['society_id']) {
                $stmt = $this->db->prepare("SELECT id, name, code, address, city, state FROM societies WHERE id = ?");
                $stmt->execute([$profile['society_id']]);
                $profile['society'] = $stmt->fetch();
                $profile['society_name'] = $profile['society']['name'] ?? null;
                $profile['society_code'] = $profile['society']['code'] ?? null;
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

            /* ===================== FINAL CLEANUP ===================== */

            // Remove null values from main profile
            // $profile = array_filter(
            //     $profile,
            //     fn($value) => $value !== null
            // );

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

    public function downloadData()
    {
        try {
            $user = $this->auth->authenticate();
            $userId = $user['uid'];
            
            $data = [];

            // 1. User Profile Data
            $stmt = $this->db->prepare("SELECT id, app_user_id, name, email, phone, role, society_id, profile_image, cover_image_url, resident_type, bio, profession, hometown, status, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $data['profile'] = $stmt->fetch();

            // 2. Family Members
            $stmt = $this->db->prepare("SELECT id, name, relation, phone, image_url, is_active, created_at FROM family_members WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['family_members'] = $stmt->fetchAll();

            // 3. Flats
            $stmt = $this->db->prepare("SELECT id, society_id, building_id, flat_number, floor_number, area_sqft, is_occupied, created_at FROM flats WHERE owner_id = ? OR tenant_id = ?");
            $stmt->execute([$userId, $userId]);
            $data['flats'] = $stmt->fetchAll();

            // 4. Vehicles
            $stmt = $this->db->prepare("SELECT id, vehicle_type_id, registration_number, make, model, color, is_parked, created_at FROM vehicles WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['vehicles'] = $stmt->fetchAll();

            // 5. Pets
            $stmt = $this->db->prepare("SELECT id, pet_type_id, name, breed, age, weight, vaccination_status, notes, is_active, created_at FROM pets WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['pets'] = $stmt->fetchAll();

            // 6. Visitors
            $stmt = $this->db->prepare("SELECT id, name, phone, purpose, visit_date, visit_time, status, created_at FROM visitors WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['visitors'] = $stmt->fetchAll();

            // 7. Helpdesk Tickets
            $stmt = $this->db->prepare("SELECT id, title, description, category, priority, status, created_at FROM tickets WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['helpdesk_tickets'] = $stmt->fetchAll();

            // 8. Amenity Bookings
            $stmt = $this->db->prepare("SELECT id, amenity_id, booking_date, start_time, end_time, status, total_amount, created_at FROM amenity_bookings WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['amenity_bookings'] = $stmt->fetchAll();

            // 9. Service Bookings
            $stmt = $this->db->prepare("SELECT id, service_id, booking_date, booking_time, notes, status, created_at FROM service_bookings WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['service_bookings'] = $stmt->fetchAll();

            // 10. Orders
            $stmt = $this->db->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE buyer_id = ?");
            $stmt->execute([$userId]);
            $data['marketplace_orders'] = $stmt->fetchAll();

            // 11. Notifications
            $stmt = $this->db->prepare("SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ?");
            $stmt->execute([$userId]);
            $data['notifications'] = $stmt->fetchAll();

            // 12. Payments
            $stmt = $this->db->prepare("SELECT id, invoice_id, amount, payment_method, transaction_id, transaction_status as status, payment_date FROM payments WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['payments'] = $stmt->fetchAll();

            // 13. Invoices
            $stmt = $this->db->prepare("SELECT id, flat_id, invoice_number, total_amount, due_date, status, created_at FROM invoices WHERE resident_id = ?");
            $stmt->execute([$userId]);
            $data['invoices'] = $stmt->fetchAll();

            Response::success("Data downloaded successfully", $data);

        } catch (Exception $e) {
            error_log("Download data error: " . $e->getMessage());
            Response::error("Failed to download data: " . $e->getMessage(), 500);
        }
    }

}
