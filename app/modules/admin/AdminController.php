<?php
require_once __DIR__.'/../../core/BaseController.php';

class AdminController extends BaseController {
  
  public function createSociety() {
    try {
      // Only super admins can create societies
      $user = $this->auth->authorize('super_admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
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
      
      // Validate email format
      if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        Response::error("Invalid email format");
      }
      
      // Validate phone number format
      if (!empty($data['contact_phone']) && !preg_match('/^[0-9]{10,15}$/', $data['contact_phone'])) {
        Response::error("Phone number must be 10-15 digits");
      }
      
      // Validate pincode format
      if (!empty($data['pincode']) && !preg_match('/^[0-9]{6}$/', $data['pincode'])) {
        Response::error("Pincode must be 6 digits");
      }
      
      // Check if contact person details are already used
      if (!empty($data['contact_email'])) {
        $stmt = $this->db->prepare("SELECT id FROM societies WHERE contact_email = ?");
        $stmt->execute([$data['contact_email']]);
        if ($stmt->fetch()) {
          Response::error("A society with this contact email already exists", 409);
        }
      }
      
      if (!empty($data['contact_phone'])) {
        $stmt = $this->db->prepare("SELECT id FROM societies WHERE contact_phone = ?");
        $stmt->execute([$data['contact_phone']]);
        if ($stmt->fetch()) {
          Response::error("A society with this contact phone already exists", 409);
        }
      }
      
      // Insert society
      $societyId = $this->insert('societies', [
        'name' => $data['name'],
        'address' => $data['address'],
        'city' => $data['city'] ?? '',
        'state' => $data['state'] ?? '',
        'country' => $data['country'] ?? '',
        'pincode' => $data['pincode'] ?? '',
        'contact_person' => $data['contact_person'] ?? '',
        'contact_phone' => $data['contact_phone'] ?? '',
        'contact_email' => $data['contact_email'] ?? ''
      ]);
      
      Response::success("Society created successfully", ['society_id' => $societyId], 201);
      
    } catch(Exception $e) {
      error_log("Create society error: " . $e->getMessage());
      Response::error("Failed to create society: " . $e->getMessage(), 500);
    }
  }
  
  public function getSocieties() {
    try {
      // Only super admins can list all societies
      // $user = $this->auth->authorize('super_admin');
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
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
      if ($params) {
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
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($query);
      
      // Bind filter parameters
      $paramIndex = 1;
      foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param);
      }
      
      // Bind pagination parameters
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $societies = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($societies, $total, $pagination, "Societies retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get societies error: " . $e->getMessage());
      Response::error("Failed to retrieve societies: " . $e->getMessage(), 500);
    }
  }
  
  public function getSocietyById($id) {
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
      
    } catch(Exception $e) {
      error_log("Get society error: " . $e->getMessage());
      Response::error("Failed to retrieve society: " . $e->getMessage(), 500);
    }
  }
  
  public function updateSociety($id) {
    try {
      // Only super admins or society admins can update society details
      $user = $this->auth->authorizeAny(['super_admin', 'admin']);
      
      if ($user['role'] !== 'super_admin') {
        $this->auth->authorizeWithSociety($id);
      }
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Prepare update data
      $updateData = [];
      $allowedFields = ['name', 'address', 'city', 'state', 'country', 'pincode', 
                        'contact_person', 'contact_phone', 'contact_email'];
      
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
      
      Response::success("Society updated successfully");
      
    } catch(Exception $e) {
      error_log("Update society error: " . $e->getMessage());
      Response::error("Failed to update society: " . $e->getMessage(), 500);
    }
  }
  
  public function deleteSociety($id) {
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
      
    } catch(Exception $e) {
      error_log("Delete society error: " . $e->getMessage());
      Response::error("Failed to delete society: " . $e->getMessage(), 500);
    }
  }
  
  public function searchSocieties() {
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
      $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 10; // Max 50 results
      
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
      
    } catch(Exception $e) {
      error_log("Search societies error: " . $e->getMessage());
      Response::error("Failed to search societies: " . $e->getMessage(), 500);
    }
  }
  
  public function createBuilding() {
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
      
    } catch(Exception $e) {
      error_log("Create building error: " . $e->getMessage());
      Response::error("Failed to create building: " . $e->getMessage(), 500);
    }
  }
  
  public function getBuildingsBySociety($societyId) {
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
      
    } catch(Exception $e) {
      error_log("Get buildings error: " . $e->getMessage());
      Response::error("Failed to retrieve buildings: " . $e->getMessage(), 500);
    }
  }
  
  public function getFlatsByBuilding($buildingId) {
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
      
    } catch(Exception $e) {
      error_log("Get flats error: " . $e->getMessage());
      Response::error("Failed to retrieve flats: " . $e->getMessage(), 500);
    }
  }
  
  public function createFlatsForBuilding() {
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
      
    } catch(Exception $e) {
      error_log("Create flats error: " . $e->getMessage());
      Response::error("Failed to create flats: " . $e->getMessage(), 500);
    }
  }
  
  public function assignUserRole() {
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
      
    } catch(Exception $e) {
      error_log("Assign role error: " . $e->getMessage());
      Response::error("Failed to assign role: " . $e->getMessage(), 500);
    }
  }
}