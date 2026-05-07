<?php
require_once __DIR__ . '/../../core/BaseController.php';

class AdminController extends BaseController
{
  public function createSociety()
  {
    try {
      // Only super admins can create societies
      $user = $this->auth->authorize('super_admin');

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation: Required fields
      $errors = $this->validateRequiredFields($data, ['name', 'address']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }

      // Check if society with same name already exists
      $stmt = $this->db->prepare("SELECT id FROM societies WHERE name = ?");
      $stmt->execute([$data['name']]);
      if ($stmt->fetch()) {
        Response::error("A society with this name already exists", 409);
      }

      // Validate and normalize contact_phone (most important change)
      $normalizedPhone = null;
      if (!empty($data['contact_phone'])) {
        $originalPhone = trim($data['contact_phone']);

        // Remove all non-digit characters except leading +
        $cleanPhone = preg_replace('/[^\d+]/', '', $originalPhone);

        // Ensure it starts with +, add if missing and possible
        if (substr($cleanPhone, 0, 1) !== '+' && strlen(preg_replace('/\D/', '', $cleanPhone)) >= 10) {
          // Assume Indian number if 10 digits and no country code
          if (preg_match('/^(\d{10})$/', preg_replace('/\D/', '', $cleanPhone))) {
            $cleanPhone = '+91' . preg_replace('/\D/', '', $cleanPhone);
          } else {
            // For other cases, require + explicitly or reject
            if (substr($originalPhone, 0, 1) !== '+') {
              Response::error("International phone numbers must start with country code (e.g., +1, +44)");
            }
          }
        }

        // Extract only digits after +
        $digitsOnly = ltrim($cleanPhone, '+');
        if (!preg_match('/^\d{8,15}$/', $digitsOnly)) {
          Response::error("Phone number must have 8 to 15 digits after country code");
        }

        // Final E.164 format
        $normalizedPhone = '+' . $digitsOnly;

        // Check uniqueness using normalized format
        $stmt = $this->db->prepare("SELECT id FROM societies WHERE contact_phone = ?");
        $stmt->execute([$normalizedPhone]);
        if ($stmt->fetch()) {
          Response::error("A society with this contact phone already exists", 409);
        }
      }

      // Validate email format
      if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        Response::error("Invalid email format");
      }

      // Email uniqueness
      if (!empty($data['contact_email'])) {
        $stmt = $this->db->prepare("SELECT id FROM societies WHERE contact_email = ?");
        $stmt->execute([$data['contact_email']]);
        if ($stmt->fetch()) {
          Response::error("A society with this contact email already exists", 409);
        }
      }

      // Pincode: Make optional and flexible (international support)
      if (!empty($data['pincode'])) {
        // Allow alphanumeric and spaces (for UK, Canada, etc.), max 12 chars
        if (strlen($data['pincode']) > 12 || !preg_match('/^[A-Za-z0-9\s-]+$/', $data['pincode'])) {
          Response::error("Invalid pincode/postal code format");
        }
      }

      // Insert society — store normalized phone
      $societyId = $this->insert('societies', [
        'name' => $data['name'],
        'address' => $data['address'],
        'city' => $data['city'] ?? '',
        'state' => $data['state'] ?? '',
        'country' => $data['country'] ?? '',
        'pincode' => $data['pincode'] ?? '',
        'contact_person' => $data['contact_person'] ?? '',
        'contact_phone' => $normalizedPhone,  // Stored in E.164 format
        'contact_email' => $data['contact_email'] ?? ''
      ]);

      Response::success("Society created successfully", ['society_id' => $societyId], 201);

    } catch (Exception $e) {
      error_log("Create society error: " . $e->getMessage());
      Response::error("Failed to create society: " . $e->getMessage(), 500);
    }
  }

  public function getSocieties()
  {
    try {
      // Only super admins can list all societies
      // $user = $this->auth->authorize('super_admin');

      $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
      $city = isset($_GET['city']) ? $_GET['city'] : null;

      $pagination = $this->paginate($page, $limit);

      // Build query based on filters
      $whereClause = "";
      $params = [];

      if ($city) {
        $whereClause = "WHERE city = ?";
        $params[] = $city;
      }

      // Get total count
      $countQuery = "SELECT COUNT(*) as count FROM societies " . $whereClause;
      $countStmt = $this->db->prepare($countQuery);
      if (!empty($params)) {
        $countStmt->execute($params);
      } else {
        $countStmt->execute();
      }
      $total = $countStmt->fetch()['count'];

      // Get societies
      $query = "
        SELECT id, name, address, city, state, country, pincode, 
               contact_person, contact_phone, contact_email, created_at
        FROM societies
        " . $whereClause . "
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
      ";

      // Add pagination parameters to the params array
      $params[] = $pagination['limit'];
      $params[] = $pagination['offset'];

      $stmt = $this->db->prepare($query);
      $stmt->execute($params);
      $societies = $stmt->fetchAll();

      $this->sendPaginatedResponse($societies, $total, $pagination, "Societies retrieved successfully");

    } catch (Exception $e) {
      error_log("Get societies error: " . $e->getMessage());
      Response::error("Failed to retrieve societies: " . $e->getMessage(), 500);
    }
  }

  public function getSocietyById($id)
  {
    try {
      // Only super admins or society admins can view society details
      $user = $this->auth->authenticate();

      if ($user['role'] !== 'super_admin' && $user['society_id'] != $id) {
        $this->auth->authorizeWithSociety($id);
      }

      $stmt = $this->db->prepare("
        SELECT id, name, address, city, state, country, pincode, 
               contact_person, contact_phone, contact_email, created_at
        FROM societies WHERE id = ?
      ");
      $stmt->execute([$id]);
      $society = $stmt->fetch();

      if (!$society) {
        Response::notFound("Society not found");
      }

      Response::success("Society retrieved successfully", $society);

    } catch (Exception $e) {
      error_log("Get society error: " . $e->getMessage());
      Response::error("Failed to retrieve society: " . $e->getMessage(), 500);
    }
  }

  /**
   * Get complete society information with all related data
   * @param int $id
   * @return void
   */
  public function getCompleteSocietyById($id)
  {
    try {
      // Authenticate user (allow any authenticated user)
      $user = $this->auth->authenticate();

      // Check if user has access to this society (unless super_admin)
      if ($user['role'] !== 'super_admin' && $user['society_id'] != $id) {
        // For now, allow access if authenticated - can be tightened later
        // $this->auth->authorizeWithSociety($id);
      }

      // Get basic society details
      $stmt = $this->db->prepare("
        SELECT id, name, address, city, state, country, pincode, 
               contact_person, contact_phone, contact_email, created_at, updated_at
        FROM societies WHERE id = ?
      ");
      $stmt->execute([$id]);
      $society = $stmt->fetch();

      if (!$society) {
        Response::notFound("Society not found");
      }

      // Get all related data
      $societyData = $society;

      // 1. Buildings and Flats
      $stmt = $this->db->prepare("
        SELECT b.id, b.name, b.total_floors, b.description, b.created_at,
               COUNT(DISTINCT f.id) as total_flats,
               COUNT(DISTINCT CASE WHEN f.is_occupied = 1 THEN f.id END) as occupied_flats,
               COUNT(DISTINCT CASE WHEN f.is_occupied = 0 OR f.is_occupied IS NULL THEN f.id END) as available_flats
        FROM buildings b
        LEFT JOIN flats f ON b.id = f.building_id
        WHERE b.society_id = ?
        GROUP BY b.id
        ORDER BY b.name
      ");
      $stmt->execute([$id]);
      $societyData['buildings'] = $stmt->fetchAll();

      // 2. Users Statistics
      $stmt = $this->db->prepare("
        SELECT 
          COUNT(*) as total_users,
          SUM(CASE WHEN role = 'resident' THEN 1 ELSE 0 END) as residents,
          SUM(CASE WHEN role = 'guard' THEN 1 ELSE 0 END) as guards,
          SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff,
          SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
          SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as super_admins,
          SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
          SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
          SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,
          SUM(CASE WHEN status = 'pending_verification' THEN 1 ELSE 0 END) as pending_users
        FROM users
        WHERE society_id = ?
      ");
      $stmt->execute([$id]);
      $societyData['user_statistics'] = $stmt->fetch();

      // 3. Recent Users (Last 10)
      $stmt = $this->db->prepare("
        SELECT id, app_user_id, name, email, phone, role, status, profile_image, created_at
        FROM users
        WHERE society_id = ?
        ORDER BY created_at DESC
        LIMIT 10
      ");
      $stmt->execute([$id]);
      $societyData['recent_users'] = $stmt->fetchAll();

      // 4. Visitors Statistics
      $stmt = $this->db->prepare("
        SELECT 
          COUNT(*) as total_visitors,
          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_visitors,
          SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_visitors,
          SUM(CASE WHEN status = 'entered' THEN 1 ELSE 0 END) as entered_visitors,
          SUM(CASE WHEN status = 'exited' THEN 1 ELSE 0 END) as exited_visitors,
          SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_visitors,
          SUM(CASE WHEN visitor_type = 'guest' THEN 1 ELSE 0 END) as guest_visitors,
          SUM(CASE WHEN visitor_type = 'delivery' THEN 1 ELSE 0 END) as delivery_visitors,
          SUM(CASE WHEN visitor_type = 'service' THEN 1 ELSE 0 END) as service_visitors,
          SUM(CASE WHEN visitor_type = 'other' THEN 1 ELSE 0 END) as other_visitors
        FROM visitors
        WHERE society_id = ?
      ");
      $stmt->execute([$id]);
      $societyData['visitor_statistics'] = $stmt->fetch();

      // 5. Today's Visitors
      $stmt = $this->db->prepare("
        SELECT v.id, v.name, v.phone, v.purpose, v.visit_time, v.expected_exit_time, 
               v.actual_exit_time, v.status, v.visitor_type, v.image_url,
               u.name as resident_name, f.flat_number
        FROM visitors v
        LEFT JOIN users u ON v.resident_id = u.id
        LEFT JOIN flats f ON u.id = f.owner_id OR u.id = f.tenant_id
        WHERE v.society_id = ? AND v.visit_date = CURDATE()
        ORDER BY v.visit_time DESC
      ");
      $stmt->execute([$id]);
      $societyData['todays_visitors'] = $stmt->fetchAll();

      // 6. Financial Summary
      $stmt = $this->db->prepare("
        SELECT 
          COUNT(DISTINCT i.id) as total_invoices,
          SUM(i.total_amount) as total_revenue,
          SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END) as paid_revenue,
          SUM(CASE WHEN i.status = 'pending' OR i.status = 'sent' THEN i.total_amount ELSE 0 END) as pending_revenue,
          SUM(CASE WHEN i.status = 'overdue' THEN i.total_amount ELSE 0 END) as overdue_revenue,
          COUNT(DISTINCT CASE WHEN i.status = 'overdue' THEN i.id END) as overdue_invoices,
          COUNT(DISTINCT p.id) as total_payments,
          SUM(DISTINCT p.amount) as total_collected
        FROM invoices i
        LEFT JOIN payments p ON i.id = p.invoice_id AND p.transaction_status = 'success'
        WHERE i.society_id = ?
      ");
      $stmt->execute([$id]);
      $societyData['financial_summary'] = $stmt->fetch();

      // 7. Charge Heads
      $stmt = $this->db->prepare("
        SELECT id, name, charge_type, amount, gst_rate, is_active
        FROM charge_heads
        WHERE society_id = ?
        ORDER BY name
      ");
      $stmt->execute([$id]);
      $societyData['charge_heads'] = $stmt->fetchAll();

      // 8. Amenities
      $stmt = $this->db->prepare("
        SELECT a.id, a.name, a.description, a.image_url, a.capacity, 
               a.booking_fee, a.cancellation_fee, a.is_active,
               COUNT(DISTINCT ab.id) as total_bookings,
               COUNT(DISTINCT CASE WHEN ab.status = 'confirmed' THEN ab.id END) as confirmed_bookings,
               COUNT(DISTINCT CASE WHEN ab.status = 'requested' THEN ab.id END) as pending_bookings
        FROM amenities a
        LEFT JOIN amenity_bookings ab ON a.id = ab.amenity_id
        WHERE a.society_id = ?
        GROUP BY a.id
        ORDER BY a.name
      ");
      $stmt->execute([$id]);
      $societyData['amenities'] = $stmt->fetchAll();

      // 9. Communication Groups
      $stmt = $this->db->prepare("
        SELECT g.id, g.name, g.description, 
               COUNT(DISTINCT gm.id) as member_count,
               u.name as created_by_name
        FROM groups g
        LEFT JOIN group_members gm ON g.id = gm.group_id
        LEFT JOIN users u ON g.created_by = u.id
        WHERE g.society_id = ?
        GROUP BY g.id
        ORDER BY g.name
      ");
      $stmt->execute([$id]);
      $societyData['communication_groups'] = $stmt->fetchAll();

      // 10. Helpdesk/Tickets Summary
      $stmt = $this->db->prepare("
        SELECT 
          COUNT(*) as total_tickets,
          SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
          SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
          SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
          SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
          SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tickets,
          SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_priority_tickets,
          SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_priority_tickets
        FROM tickets
        WHERE society_id = ?
      ");
      $stmt->execute([$id]);
      $societyData['helpdesk_summary'] = $stmt->fetch();

      // 11. Recent Tickets (Last 10)
      $stmt = $this->db->prepare("
        SELECT t.id, t.ticket_number, t.title, t.status, t.priority, t.category, t.created_at,
               u.name as created_by_name
        FROM tickets t
        LEFT JOIN users u ON t.resident_id = u.id
        WHERE t.society_id = ?
        ORDER BY t.created_at DESC
        LIMIT 10
      ");
      $stmt->execute([$id]);
      $societyData['recent_tickets'] = $stmt->fetchAll();

      // 12. Announcements (Last 5)
      $stmt = $this->db->prepare("
        SELECT a.id, a.title, a.content, a.priority, a.created_at,
               u.name as created_by_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.society_id = ?
        ORDER BY a.created_at DESC
        LIMIT 5
      ");
      $stmt->execute([$id]);
      $societyData['recent_announcements'] = $stmt->fetchAll();

      // 13. Assets Summary
      $stmt = $this->db->prepare("
        SELECT 
          COUNT(*) as total_assets,
          SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_assets,
          SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
          SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired_assets
        FROM assets
        WHERE society_id = ?
      ");
      $stmt->execute([$id]);
      $societyData['assets_summary'] = $stmt->fetch();

      // 14. Vehicles Count
      $stmt = $this->db->prepare("
        SELECT COUNT(*) as total_vehicles
        FROM vehicles v
        JOIN users u ON v.resident_id = u.id
        WHERE u.society_id = ?
      ");
      $stmt->execute([$id]);
      $societyData['total_vehicles'] = $stmt->fetch()['total_vehicles'];

      // 15. Pets Count
      $stmt = $this->db->prepare("
        SELECT COUNT(*) as total_pets
        FROM pets p
        WHERE p.society_id = ? AND p.is_active = 1
      ");
      $stmt->execute([$id]);
      $societyData['total_pets'] = $stmt->fetch()['total_pets'];

      Response::success("Complete society information retrieved successfully", $societyData);

    } catch (Exception $e) {
      error_log("Get complete society error: " . $e->getMessage());
      Response::error("Failed to retrieve society information: " . $e->getMessage(), 500);
    }
  }

  public function updateSociety($id)
  {
    try {
      // Only super admins or society admins can update society details
      $user = $this->auth->authorizeAny(['super_admin', 'admin']);

      if ($user['role'] !== 'super_admin') {
        $this->auth->authorizeWithSociety($id);
      }

      $data = json_decode(file_get_contents("php://input"), true);

      // Prepare update data
      $updateData = [];
      $allowedFields = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'contact_person',
        'contact_phone',
        'contact_email'
      ];

      foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
          $updateData[$field] = $data[$field];
        }
      }

      if (empty($updateData)) {
        Response::error("No valid fields to update");
      }

      // Update society
      $updated = $this->update('societies', $updateData, 'id = :id', ['id' => $id]);

      if ($updated === 0) {
        Response::error("Society not found or no changes made", 404);
      }

      Response::success("Society updated successfully", $updateData);

    } catch (Exception $e) {
      error_log("Update society error: " . $e->getMessage());
      Response::error("Failed to update society: " . $e->getMessage(), 500);
    }
  }

  public function deleteSociety($id)
  {
    try {
      // Only super admins can delete societies
      $user = $this->auth->authorize('super_admin');

      // Check if society exists
      $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
      $stmt->execute([$id]);
      if (!$stmt->fetch()) {
        Response::notFound("Society not found");
      }

      // In a real implementation, you would need to handle related data
      // For now, we'll just delete the society
      $deleted = $this->delete('societies', 'id = ?', [$id]);

      if ($deleted === 0) {
        Response::error("Failed to delete society", 500);
      }

      Response::success("Society deleted successfully");

    } catch (Exception $e) {
      error_log("Delete society error: " . $e->getMessage());
      Response::error("Failed to delete society: " . $e->getMessage(), 500);
    }
  }

  public function searchSocieties()
  {
    try {
      // Allow unauthenticated access for society search during registration
      // But still authenticate if token is provided
      $user = null;
      $headers = apache_request_headers();
      $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

      if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        try {
          $user = $this->auth->validateToken($token);
        } catch (Exception $e) {
          // Token invalid, continue without user
          $user = null;
        }
      }

      $query = isset($_GET['q']) ? trim($_GET['q']) : '';
      $limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 50) : 10; // Max 50 results

      if (empty($query)) {
        Response::success("Societies retrieved successfully", []);
        return;
      }

      // Search societies by name with partial match
      $stmt = $this->db->prepare("
        SELECT id, name, address, city, state, country
        FROM societies 
        WHERE name LIKE ? OR address LIKE ? OR city LIKE ?
        ORDER BY name
        LIMIT ?
      ");
      $stmt->execute(["%{$query}%", "%{$query}%", "%{$query}%", $limit]);
      $societies = $stmt->fetchAll();

      Response::success("Societies retrieved successfully", $societies);

    } catch (Exception $e) {
      error_log("Search societies error: " . $e->getMessage());
      Response::error("Failed to search societies: " . $e->getMessage(), 500);
    }
  }

  public function createBuilding()
  {
    try {
      // Only admins can create buildings
      $user = $this->auth->authorizeAny(['super_admin', 'admin']);

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      $errors = $this->validateRequiredFields($data, ['name', 'society_id']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }

      // Validate society_id
      if (!is_numeric($data['society_id']) || $data['society_id'] < 1) {
        Response::error("Society ID must be a positive integer");
      }

      // Validate total_floors
      if (isset($data['total_floors']) && (!is_numeric($data['total_floors']) || $data['total_floors'] < 1)) {
        Response::error("Total floors must be a positive integer");
      }

      // Validate building name length
      if (strlen($data['name']) > 100) {
        Response::error("Building name must be less than 100 characters");
      }

      // Check if society exists and user has permission
      $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
      $stmt->execute([$data['society_id']]);
      if (!$stmt->fetch()) {
        Response::notFound("Society not found");
      }

      if ($user['role'] !== 'super_admin') {
        $this->auth->authorizeWithSociety($data['society_id']);
      }

      // Check if building with same name already exists in this society
      $stmt = $this->db->prepare("SELECT id FROM buildings WHERE name = ? AND society_id = ?");
      $stmt->execute([$data['name'], $data['society_id']]);
      if ($stmt->fetch()) {
        Response::error("A building with this name already exists in this society", 409);
      }

      // Insert building
      $buildingId = $this->insert('buildings', [
        'name' => $data['name'],
        'society_id' => $data['society_id'],
        'total_floors' => $data['total_floors'] ?? 1,
        'description' => $data['description'] ?? null
      ]);

      Response::success("Building created successfully", ['building_id' => $buildingId], 201);

    } catch (Exception $e) {
      error_log("Create building error: " . $e->getMessage());
      Response::error("Failed to create building: " . $e->getMessage(), 500);
    }
  }

  public function getBuildingsBySociety($societyId)
  {
    try {
      // Allow unauthenticated access for building lookup during registration
      // But still authenticate if token is provided
      $user = null;
      $headers = apache_request_headers();
      $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

      if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        try {
          $user = $this->auth->validateToken($token);
        } catch (Exception $e) {
          // Token invalid, continue without user
          $user = null;
        }
      }

      // Validate society exists
      $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
      $stmt->execute([$societyId]);
      if (!$stmt->fetch()) {
        Response::notFound("Society not found");
      }

      // Get buildings for society
      $stmt = $this->db->prepare("
        SELECT id, name, total_floors, description
        FROM buildings 
        WHERE society_id = ?
        ORDER BY name
      ");
      $stmt->execute([$societyId]);
      $buildings = $stmt->fetchAll();

      Response::success("Buildings retrieved successfully", $buildings);

    } catch (Exception $e) {
      error_log("Get buildings error: " . $e->getMessage());
      Response::error("Failed to retrieve buildings: " . $e->getMessage(), 500);
    }
  }

  public function getFlatsByBuilding($buildingId)
  {
    try {
      // Allow unauthenticated access for flat lookup during registration
      // But still authenticate if token is provided
      $user = null;
      $headers = apache_request_headers();
      $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

      if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        try {
          $user = $this->auth->validateToken($token);
        } catch (Exception $e) {
          // Token invalid, continue without user
          $user = null;
        }
      }

      // Validate building exists and get society_id
      $stmt = $this->db->prepare("
        SELECT b.id, b.name, b.society_id, s.name as society_name
        FROM buildings b
        JOIN societies s ON b.society_id = s.id
        WHERE b.id = ?
      ");
      $stmt->execute([$buildingId]);
      $building = $stmt->fetch();

      if (!$building) {
        Response::notFound("Building not found");
      }

      // Get flats for building that are not occupied
      $stmt = $this->db->prepare("
        SELECT id, flat_number, floor_number, area_sqft
        FROM flats 
        WHERE building_id = ? AND (is_occupied = 0 OR is_occupied IS NULL)
        ORDER BY floor_number, flat_number
      ");
      $stmt->execute([$buildingId]);
      $flats = $stmt->fetchAll();

      Response::success("Flats retrieved successfully", [
        'building' => [
          'id' => $building['id'],
          'name' => $building['name'],
          'society_id' => $building['society_id'],
          'society_name' => $building['society_name']
        ],
        'flats' => $flats
      ]);

    } catch (Exception $e) {
      error_log("Get flats error: " . $e->getMessage());
      Response::error("Failed to retrieve flats: " . $e->getMessage(), 500);
    }
  }

  public function createFlatsForBuilding()
  {
    try {
      // Only admins can create flats
      $user = $this->auth->authorizeAny(['super_admin', 'admin']);

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      $errors = $this->validateRequiredFields($data, ['building_id']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }

      // Check if building exists and get society_id
      $stmt = $this->db->prepare("
        SELECT b.id, b.name, b.society_id, b.total_floors, s.name as society_name
        FROM buildings b
        JOIN societies s ON b.society_id = s.id
        WHERE b.id = ?
      ");
      $stmt->execute([$data['building_id']]);
      $building = $stmt->fetch();

      if (!$building) {
        Response::notFound("Building not found");
      }

      // Verify user has permission to add flats to this building
      if ($user['role'] !== 'super_admin') {
        $this->auth->authorizeWithSociety($building['society_id']);
      }

      $createdFlats = [];

      // Handle bulk creation based on floors and flats per floor
      if (isset($data['floors']) && is_array($data['floors'])) {
        // Create flats based on floor configuration
        foreach ($data['floors'] as $floorData) {
          if (empty($floorData['floor_number']) || empty($floorData['flats'])) {
            continue;
          }

          $floorNumber = $floorData['floor_number'];
          $flatsPerFloor = $floorData['flats'];

          for ($i = 1; $i <= $flatsPerFloor; $i++) {
            $flatNumber = $floorNumber . str_pad($i, 2, '0', STR_PAD_LEFT);

            $flatId = $this->insert('flats', [
              'building_id' => $data['building_id'],
              'flat_number' => $flatNumber,
              'floor_number' => $floorNumber,
              'area_sqft' => $floorData['area_sqft'] ?? null,
              'society_id' => $building['society_id']
            ]);

            $createdFlats[] = [
              'id' => $flatId,
              'flat_number' => $flatNumber,
              'floor_number' => $floorNumber,
              'area_sqft' => $floorData['area_sqft'] ?? null
            ];
          }
        }
      }
      // Handle explicit flats list
      else if (isset($data['flats']) && is_array($data['flats'])) {
        if (empty($data['flats'])) {
          Response::error("Flats data must be a non-empty array");
        }

        // Insert flats
        foreach ($data['flats'] as $flatData) {
          // Validate required fields for each flat
          if (empty($flatData['flat_number'])) {
            Response::error("Each flat must have a flat_number");
          }

          $flatId = $this->insert('flats', [
            'building_id' => $data['building_id'],
            'flat_number' => $flatData['flat_number'],
            'floor_number' => $flatData['floor_number'] ?? '',
            'area_sqft' => $flatData['area_sqft'] ?? null,
            'society_id' => $building['society_id']
          ]);

          $createdFlats[] = [
            'id' => $flatId,
            'flat_number' => $flatData['flat_number'],
            'floor_number' => $flatData['floor_number'] ?? '',
            'area_sqft' => $flatData['area_sqft'] ?? null
          ];
        }
      } else {
        Response::error("Either 'flats' or 'floors' configuration must be provided");
      }

      Response::success("Flats created successfully", [
        'building' => [
          'id' => $building['id'],
          'name' => $building['name'],
          'society_id' => $building['society_id'],
          'society_name' => $building['society_name']
        ],
        'flats' => $createdFlats
      ], 201);

    } catch (Exception $e) {
      error_log("Create flats error: " . $e->getMessage());
      Response::error("Failed to create flats: " . $e->getMessage(), 500);
    }
  }

  public function assignUserRole()
  {
    try {
      // Only admins can assign roles
      $user = $this->auth->authorizeAny(['super_admin', 'admin']);

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      $errors = $this->validateRequiredFields($data, ['user_id', 'role_id']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }

      // Check if user exists
      $stmt = $this->db->prepare("SELECT id, society_id FROM users WHERE id = ?");
      $stmt->execute([$data['user_id']]);
      $targetUser = $stmt->fetch();

      if (!$targetUser) {
        Response::notFound("User not found");
      }

      // Check if role exists
      $stmt = $this->db->prepare("SELECT id, society_id FROM roles WHERE id = ?");
      $stmt->execute([$data['role_id']]);
      $role = $stmt->fetch();

      if (!$role) {
        Response::notFound("Role not found");
      }

      // Verify that user and role belong to the same society
      if ($targetUser['society_id'] != $role['society_id']) {
        Response::error("User and role must belong to the same society");
      }

      // Verify permissions
      if ($user['role'] === 'admin' && $user['society_id'] != $targetUser['society_id']) {
        Response::forbidden("You can only assign roles to users in your society");
      }

      if ($user['role'] === 'admin' && $user['society_id'] != $role['society_id']) {
        Response::forbidden("You can only assign roles from your society");
      }

      // Check if assignment already exists
      $stmt = $this->db->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role_id = ?");
      $stmt->execute([$data['user_id'], $data['role_id']]);
      if ($stmt->fetch()) {
        Response::error("User already has this role", 409);
      }

      // Assign role
      $assignmentId = $this->insert('user_roles', [
        'user_id' => $data['user_id'],
        'role_id' => $data['role_id'],
        'society_id' => $targetUser['society_id'],
        'assigned_by' => $user['uid']
      ]);

      Response::success("Role assigned successfully", ['assignment_id' => $assignmentId], 201);

    } catch (Exception $e) {
      error_log("Assign role error: " . $e->getMessage());
      Response::error("Failed to assign role: " . $e->getMessage(), 500);
    }
  }
}