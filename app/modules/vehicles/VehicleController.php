<?php
require_once __DIR__ . '/../../core/BaseController.php';

class VehicleController extends BaseController
{

  public function addVehicleType()
  {
    try {
      $this->auth->authenticate();
      $data = json_decode(file_get_contents("php://input"), true);
      // Validate required fields
      $errors = $this->validateRequiredFields($data, ['name']);
      if (!empty($errors)) {
        Response::validationError($errors);
        return;
      }
      // Insert new pet type
      $petTypeId = $this->insert('vehicle_types', [
        'name' => $data['name'],
        'description' => $data['description'] ?? '',
        'monthly_charge' => $data['monthly_charge'] ?? ''
      ]);
      Response::success("Vehicle type added successfully", ['vehicle_type_id' => $petTypeId], 201);
    } catch (Exception $e) {
      error_log("Add vehicle type error: " . $e->getMessage());
      Response::error("Failed to add vehicle type: " . $e->getMessage(), 500);
    }
  }

  public function getVehicleTypes()
    {
        try {
            // Ensure the user is authenticated (optional, can be public)
            $this->auth->authenticate();

            $stmt = $this->db->query("SELECT * FROM vehicle_types");
            Response::success("Vehicle types retrieved", $stmt->fetchAll());
        } catch (Exception $e) {
            Response::error("Failed to retrieve types: " . $e->getMessage(), 500);
        }
    }

  public function addVehicle()
  {
    try {
      // Residents can add their vehicles
      $user = $this->auth->authorize('resident');

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      $errors = $this->validateRequiredFields($data, ['registration_number', 'vehicle_type_id']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }

      // Validate registration number format (alphanumeric, 5-15 characters)
      if (!preg_match('/^[A-Z0-9]{5,15}$/', strtoupper($data['registration_number']))) {
        Response::error("Registration number must be 5-15 alphanumeric characters");
      }

      // Check if vehicle type exists
      $stmt = $this->db->prepare("SELECT id FROM vehicle_types WHERE id = ?");
      $stmt->execute([$data['vehicle_type_id']]);
      if (!$stmt->fetch()) {
        Response::notFound("Vehicle type not found");
      }

      // Check if resident exists
      $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND society_id = ?");
      $stmt->execute([$user['uid'], $user['society_id']]);
      if (!$stmt->fetch()) {
        Response::notFound("Resident not found");
      }

      // Check if registration number already exists
      $stmt = $this->db->prepare("
        SELECT id 
        FROM vehicles 
        WHERE registration_number = ? AND society_id = ?
      ");
      $stmt->execute([$data['registration_number'], $user['society_id']]);
      if ($stmt->fetch()) {
        Response::error("Vehicle with this registration number already exists", 409);
      }

      // Insert vehicle
      $vehicleId = $this->insert('vehicles', [
        'resident_id' => $user['uid'],
        'vehicle_type_id' => $data['vehicle_type_id'],
        'is_electric' => isset($data['is_electric']) ? (int) $data['is_electric'] : 0,
        'make' => $data['make'] ?? null,
        'model' => $data['model'] ?? null,
        'color' => $data['color'] ?? null,
        'registration_number' => $data['registration_number'],
        'parking_spot' => $data['parking_spot'] ?? null,
        'is_parked' => isset($data['is_parked']) ? (int) $data['is_parked'] : 0,
        'society_id' => $user['society_id']
      ]);

      Response::success("Vehicle added successfully", ['vehicle_id' => $vehicleId], 201);

    } catch (Exception $e) {
      error_log("Add vehicle error: " . $e->getMessage());
      Response::error("Failed to add vehicle: " . $e->getMessage(), 500);
    }
  }

  public function getVehicles()
  {
    try {
      $user = $this->auth->authenticate();

      $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
      $isParked = isset($_GET['is_parked']) ? (int) $_GET['is_parked'] : null;

      $pagination = $this->paginate($page, $limit);

      // Build query based on user role
      $whereClause = "WHERE v.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];

      // Residents can only see their own vehicles
      if ($user['role'] === 'resident') {
        $whereClause .= " AND v.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }

      // Filter by parked status if provided
      if ($isParked !== null) {
        $whereClause .= " AND v.is_parked = :is_parked";
        $params['is_parked'] = $isParked;
      }

      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM vehicles v {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];

      // Get vehicles
      $sql = "
        SELECT v.*, vt.name as vehicle_type_name, u.name as resident_name, ps.spot_number as parking_spot_number
        FROM vehicles v
        JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        LEFT JOIN users u ON v.resident_id = u.id
        LEFT JOIN parking_spots ps ON v.id = ps.vehicle_id
        {$whereClause}
        ORDER BY v.created_at DESC
        LIMIT :limit OFFSET :offset
      ";

      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $vehicles = $stmt->fetchAll();

      $this->sendPaginatedResponse($vehicles, $total, $pagination, "Vehicles retrieved successfully");

    } catch (Exception $e) {
      error_log("Get vehicles error: " . $e->getMessage());
      Response::error("Failed to retrieve vehicles: " . $e->getMessage(), 500);
    }
  }

  public function getVehicleById($id)
  {
    try {
      $user = $this->auth->authenticate();

      // Build query based on user role
      $whereClause = "WHERE v.id = :id AND v.society_id = :society_id";
      $params = ['id' => $id, 'society_id' => $user['society_id']];

      // Residents can only see their own vehicles
      if ($user['role'] === 'resident') {
        $whereClause .= " AND v.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }

      // Get vehicle
      $sql = "
        SELECT v.*, vt.name as vehicle_type_name, u.name as resident_name, ps.spot_number as parking_spot_number
        FROM vehicles v
        JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        LEFT JOIN users u ON v.resident_id = u.id
        LEFT JOIN parking_spots ps ON v.id = ps.vehicle_id
        {$whereClause}
      ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
      $vehicle = $stmt->fetch();

      if (!$vehicle) {
        Response::notFound("Vehicle not found or access denied");
      }

      Response::success("Vehicle retrieved successfully", $vehicle);

    } catch (Exception $e) {
      error_log("Get vehicle error: " . $e->getMessage());
      Response::error("Failed to retrieve vehicle: " . $e->getMessage(), 500);
    }
  }

  public function updateVehicle($id)
  {
    try {
      // Residents can update their own vehicles
      // Admins can update any vehicle
      $user = $this->auth->authorizeAny(['resident', 'admin']);

      $data = json_decode(file_get_contents("php://input"), true);

      // Check if vehicle exists
      $stmt = $this->db->prepare("
        SELECT id, resident_id
        FROM vehicles 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$id, $user['society_id']]);
      $vehicle = $stmt->fetch();

      if (!$vehicle) {
        Response::notFound("Vehicle not found");
      }

      // Check permissions
      if ($user['role'] === 'resident' && $vehicle['resident_id'] != $user['uid']) {
        Response::forbidden("You can only update your own vehicles");
      }

      // Prepare update data
      $updateData = [];
      $allowedFields = ['make', 'model', 'color', 'parking_spot', 'is_electric', 'is_parked'];

      foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
          $updateData[$field] = $data[$field];
        }
      }

      // Validate field lengths
      if (isset($data['make']) && strlen($data['make']) > 50) {
        Response::error("Make must be less than 50 characters");
      }

      if (isset($data['model']) && strlen($data['model']) > 50) {
        Response::error("Model must be less than 50 characters");
      }

      if (isset($data['color']) && strlen($data['color']) > 30) {
        Response::error("Color must be less than 30 characters");
      }

      // Update vehicle type if provided
      if (isset($data['vehicle_type_id'])) {
        // Check if vehicle type exists
        $stmt = $this->db->prepare("SELECT id FROM vehicle_types WHERE id = ?");
        $stmt->execute([$data['vehicle_type_id']]);
        if (!$stmt->fetch()) {
          Response::notFound("Vehicle type not found");
        }
        $updateData['vehicle_type_id'] = $data['vehicle_type_id'];
      }

      if (empty($updateData)) {
        Response::error("No valid fields to update");
      }

      // Update vehicle
      $updated = $this->update('vehicles', $updateData, 'id = :id', ['id' => $id]);

      if ($updated === 0) {
        Response::error("Failed to update vehicle", 500);
      }

      Response::success("Vehicle updated successfully", $updateData);

    } catch (Exception $e) {
      error_log("Update vehicle error: " . $e->getMessage());
      Response::error("Failed to update vehicle: " . $e->getMessage(), 500);
    }
  }

  public function deleteVehicle($id)
  {
    try {
      // Residents can delete their own vehicles
      // Admins can delete any vehicle
      $user = $this->auth->authorizeAny(['resident', 'admin']);

      // Check if vehicle exists
      $stmt = $this->db->prepare("
        SELECT id, resident_id
        FROM vehicles 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$id, $user['society_id']]);
      $vehicle = $stmt->fetch();

      if (!$vehicle) {
        Response::notFound("Vehicle not found");
      }

      // Check permissions
      if ($user['role'] === 'resident' && $vehicle['resident_id'] != $user['uid']) {
        Response::forbidden("You can only delete your own vehicles");
      }

      // Delete vehicle
      $deleted = $this->delete('vehicles', 'id = ?', [$id]);

      if ($deleted === 0) {
        Response::error("Failed to delete vehicle", 500);
      }

      Response::success("Vehicle deleted successfully");

    } catch (Exception $e) {
      error_log("Delete vehicle error: " . $e->getMessage());
      Response::error("Failed to delete vehicle: " . $e->getMessage(), 500);
    }
  }

  public function getParkingSpots()
  {
    try {
      $user = $this->auth->authenticate();

      $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
      $isOccupied = isset($_GET['is_occupied']) ? (int) $_GET['is_occupied'] : null;
      $spotType = isset($_GET['spot_type']) ? $_GET['spot_type'] : null;

      $pagination = $this->paginate($page, $limit);

      // Build query
      $whereClause = "WHERE ps.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];

      // Filter by occupied status if provided
      if ($isOccupied !== null) {
        $whereClause .= " AND ps.is_occupied = :is_occupied";
        $params['is_occupied'] = $isOccupied;
      }

      // Filter by spot type if provided
      if ($spotType) {
        $whereClause .= " AND ps.spot_type = :spot_type";
        $params['spot_type'] = $spotType;
      }

      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM parking_spots ps {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];

      // Get parking spots
      $sql = "
        SELECT ps.*, v.registration_number, v.make, v.model, u.name as resident_name
        FROM parking_spots ps
        LEFT JOIN vehicles v ON ps.vehicle_id = v.id
        LEFT JOIN users u ON v.resident_id = u.id
        {$whereClause}
        ORDER BY ps.spot_number ASC
        LIMIT :limit OFFSET :offset
      ";

      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $spots = $stmt->fetchAll();

      $this->sendPaginatedResponse($spots, $total, $pagination, "Parking spots retrieved successfully");

    } catch (Exception $e) {
      error_log("Get parking spots error: " . $e->getMessage());
      Response::error("Failed to retrieve parking spots: " . $e->getMessage(), 500);
    }
  }

  public function assignParkingSpot($spotId)
  {
    try {
      // Residents can assign spots to their own vehicles
      // Admins can assign any spot to any vehicle
      $user = $this->auth->authorizeAny(['resident', 'admin']);

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      if (empty($data['vehicle_id'])) {
        Response::error("Vehicle ID is required");
      }

      // Check if parking spot exists and is available
      $stmt = $this->db->prepare("
        SELECT id, is_occupied
        FROM parking_spots 
        WHERE id = ? AND society_id = ? AND is_occupied = 0
      ");
      $stmt->execute([$spotId, $user['society_id']]);
      $spot = $stmt->fetch();

      if (!$spot) {
        Response::error("Parking spot not found or already occupied");
      }

      // Check if vehicle exists and belongs to the same society
      $stmt = $this->db->prepare("
        SELECT id, resident_id
        FROM vehicles 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$data['vehicle_id'], $user['society_id']]);
      $vehicle = $stmt->fetch();

      if (!$vehicle) {
        Response::notFound("Vehicle not found");
      }

      // Check permissions
      if ($user['role'] === 'resident' && $vehicle['resident_id'] != $user['uid']) {
        Response::forbidden("You can only assign spots to your own vehicles");
      }

      // Start transaction
      $this->beginTransaction();

      try {
        // Update parking spot
        $this->update('parking_spots', [
          'is_occupied' => 1,
          'vehicle_id' => $data['vehicle_id']
        ], 'id = :id', ['id' => $spotId]);

        // Update vehicle
        $this->update('vehicles', [
          'is_parked' => 1,
          'parking_spot' => $spotId
        ], 'id = :id', ['id' => $data['vehicle_id']]);

        // Commit transaction
        $this->commit();

        Response::success("Parking spot assigned successfully");
      } catch (Exception $e) {
        // Rollback transaction
        $this->rollback();
        throw $e;
      }

    } catch (Exception $e) {
      error_log("Assign parking spot error: " . $e->getMessage());
      Response::error("Failed to assign parking spot: " . $e->getMessage(), 500);
    }
  }

  public function releaseParkingSpot($spotId)
  {
    try {
      // Residents can release spots from their own vehicles
      // Admins can release any spot
      $user = $this->auth->authorizeAny(['resident', 'admin']);

      // Check if parking spot exists and is occupied
      $stmt = $this->db->prepare("
        SELECT ps.id, ps.vehicle_id, v.resident_id
        FROM parking_spots ps
        JOIN vehicles v ON ps.vehicle_id = v.id
        WHERE ps.id = ? AND ps.society_id = ?
      ");
      $stmt->execute([$spotId, $user['society_id']]);
      $spot = $stmt->fetch();

      if (!$spot) {
        Response::error("Parking spot not found or not occupied");
      }

      // Check permissions
      if ($user['role'] === 'resident' && $spot['resident_id'] != $user['uid']) {
        Response::forbidden("You can only release spots from your own vehicles");
      }

      // Start transaction
      $this->beginTransaction();

      try {
        // Update parking spot
        $this->update('parking_spots', [
          'is_occupied' => 0,
          'vehicle_id' => null
        ], 'id = :id', ['id' => $spotId]);

        // Update vehicle
        $this->update('vehicles', [
          'is_parked' => 0,
          'parking_spot' => null
        ], 'id = :id', ['id' => $spot['vehicle_id']]);

        // Commit transaction
        $this->commit();

        Response::success("Parking spot released successfully");
      } catch (Exception $e) {
        // Rollback transaction
        $this->rollback();
        throw $e;
      }

    } catch (Exception $e) {
      error_log("Release parking spot error: " . $e->getMessage());
      Response::error("Failed to release parking spot: " . $e->getMessage(), 500);
    }
  }
}