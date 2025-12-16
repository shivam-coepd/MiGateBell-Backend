<?php
require_once __DIR__.'/../../core/BaseController.php';

class AmenitiesController extends BaseController {
  
  public function createAmenity() {
    try {
      // Only admins can create amenities
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['name']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate capacity
      if (isset($data['capacity']) && (!is_numeric($data['capacity']) || $data['capacity'] < 1)) {
        Response::error("Capacity must be a positive integer");
      }
      
      // Validate numeric fields
      if (isset($data['booking_fee']) && (!is_numeric($data['booking_fee']) || $data['booking_fee'] < 0)) {
        Response::error("Booking fee must be a non-negative number");
      }
      
      if (isset($data['cancellation_fee']) && (!is_numeric($data['cancellation_fee']) || $data['cancellation_fee'] < 0)) {
        Response::error("Cancellation fee must be a non-negative number");
      }
      
      // Validate image URL format
      if (!empty($data['image_url']) && !filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
        Response::error("Invalid image URL format");
      }
      
      // Insert amenity
      $amenityId = $this->insert('amenities', [
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'image_url' => $data['image_url'] ?? null,
        'capacity' => $data['capacity'] ?? 1,
        'booking_fee' => $data['booking_fee'] ?? 0,
        'cancellation_fee' => $data['cancellation_fee'] ?? 0,
        'cancellation_policy' => $data['cancellation_policy'] ?? null,
        'society_id' => $user['society_id'],
        'is_active' => $data['is_active'] ?? 1
      ]);
      
      Response::success("Amenity created successfully", ['amenity_id' => $amenityId], 201);
      
    } catch(Exception $e) {
      error_log("Create amenity error: " . $e->getMessage());
      Response::error("Failed to create amenity: " . $e->getMessage(), 500);
    }
  }
  
  public function getAmenities() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query
      $whereClause = "WHERE a.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      if ($isActive !== null) {
        $whereClause .= " AND a.is_active = :is_active";
        $params['is_active'] = $isActive;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM amenities a {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get amenities
      $sql = "
        SELECT a.*
        FROM amenities a
        {$whereClause}
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $amenities = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($amenities, $total, $pagination, "Amenities retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get amenities error: " . $e->getMessage());
      Response::error("Failed to retrieve amenities: " . $e->getMessage(), 500);
    }
  }
  
  public function bookAmenity($amenityId) {
    try {
      // Residents can book amenities
      $user = $this->auth->authorize('resident');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['booking_date', 'start_time', 'end_time']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate date and time formats
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['booking_date'])) {
        Response::error("Invalid booking date format. Expected YYYY-MM-DD");
      }
      
      if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['start_time'])) {
        Response::error("Invalid start time format. Expected HH:MM:SS");
      }
      
      if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['end_time'])) {
        Response::error("Invalid end time format. Expected HH:MM:SS");
      }
      
      // Validate that start time is before end time
      if ($data['start_time'] >= $data['end_time']) {
        Response::error("Start time must be before end time");
      }
      
      // Check if amenity exists and is active
      $stmt = $this->db->prepare("
        SELECT id, capacity, booking_fee, is_active
        FROM amenities 
        WHERE id = ? AND society_id = ? AND is_active = 1
      ");
      $stmt->execute([$amenityId, $user['society_id']]);
      $amenity = $stmt->fetch();
      
      if (!$amenity) {
        Response::notFound("Amenity not found or inactive");
      }
      
      // Check if booking slot is available
      $stmt = $this->db->prepare("
        SELECT COUNT(*) as booked_count
        FROM amenity_bookings
        WHERE amenity_id = ? 
        AND booking_date = ?
        AND (
          (start_time < ? AND end_time > ?) OR
          (start_time < ? AND end_time > ?) OR
          (start_time >= ? AND end_time <= ?)
        )
        AND status IN ('requested', 'confirmed')
      ");
      $stmt->execute([
        $amenityId,
        $data['booking_date'],
        $data['end_time'], $data['start_time'],
        $data['start_time'], $data['end_time'],
        $data['start_time'], $data['end_time']
      ]);
      $result = $stmt->fetch();
      
      if ($result['booked_count'] >= $amenity['capacity']) {
        Response::error("This time slot is fully booked");
      }
      
      // Insert booking
      $bookingId = $this->insert('amenity_bookings', [
        'amenity_id' => $amenityId,
        'resident_id' => $user['uid'],
        'booking_date' => $data['booking_date'],
        'start_time' => $data['start_time'],
        'end_time' => $data['end_time'],
        'status' => 'requested',
        'total_amount' => $amenity['booking_fee']
      ]);
      
      Response::success("Amenity booking requested successfully", ['booking_id' => $bookingId], 201);
      
    } catch(Exception $e) {
      error_log("Book amenity error: " . $e->getMessage());
      Response::error("Failed to book amenity: " . $e->getMessage(), 500);
    }
  }
  
  public function getBookings() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $status = isset($_GET['status']) ? $_GET['status'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query based on user role
      $whereClause = "WHERE ab.amenity_id IN (SELECT id FROM amenities WHERE society_id = :society_id)";
      $params = ['society_id' => $user['society_id']];
      
      // Residents can only see their own bookings
      if ($user['role'] === 'resident') {
        $whereClause .= " AND ab.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }
      
      // Filter by status if provided
      if ($status) {
        $whereClause .= " AND ab.status = :status";
        $params['status'] = $status;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM amenity_bookings ab {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get bookings
      $sql = "
        SELECT ab.*, a.name as amenity_name, u.name as resident_name
        FROM amenity_bookings ab
        JOIN amenities a ON ab.amenity_id = a.id
        LEFT JOIN users u ON ab.resident_id = u.id
        {$whereClause}
        ORDER BY ab.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $bookings = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($bookings, $total, $pagination, "Bookings retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get bookings error: " . $e->getMessage());
      Response::error("Failed to retrieve bookings: " . $e->getMessage(), 500);
    }
  }
  
  public function updateBookingStatus($bookingId) {
    try {
      // Only admins can update booking status
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (empty($data['status'])) {
        Response::error("Status is required");
      }
      
      $allowedStatuses = ['requested', 'confirmed', 'cancelled', 'completed'];
      if (!in_array($data['status'], $allowedStatuses)) {
        Response::error("Invalid status. Allowed values: " . implode(', ', $allowedStatuses));
      }
      
      // Check if booking exists and belongs to the same society
      $stmt = $this->db->prepare("
        SELECT ab.id, ab.status, a.society_id
        FROM amenity_bookings ab
        JOIN amenities a ON ab.amenity_id = a.id
        WHERE ab.id = ?
      ");
      $stmt->execute([$bookingId]);
      $booking = $stmt->fetch();
      
      if (!$booking || $booking['society_id'] != $user['society_id']) {
        Response::notFound("Booking not found");
      }
      
      // Update booking
      $updated = $this->update('amenity_bookings', [
        'status' => $data['status']
      ], 'id = :id', ['id' => $bookingId]);
      
      if ($updated === 0) {
        Response::error("Failed to update booking status", 500);
      }
      
      Response::success("Booking status updated successfully");
      
    } catch(Exception $e) {
      error_log("Update booking status error: " . $e->getMessage());
      Response::error("Failed to update booking status: " . $e->getMessage(), 500);
    }
  }
}