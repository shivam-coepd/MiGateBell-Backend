<?php
require_once __DIR__.'/../../core/BaseController.php';

class VisitorsController extends BaseController {
  
  public function addVisitor() {
    try {
      // Residents, admins, and guards can add visitors
      $user = $this->auth->authorizeAny(['resident', 'admin', 'guard']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['name', 'phone', 'purpose']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate phone number format
      if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
        Response::error("Phone number must be 10-15 digits");
      }
      
      // Validate email format if provided
      if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        Response::error("Invalid email format");
      }
      
      // Set default values
      $residentId = $user['role'] === 'resident' ? $user['uid'] : ($data['resident_id'] ?? null);
      $societyId = $user['society_id'];
      $status = 'pending';
      
      // Validate date and time formats
      if (!empty($data['visit_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['visit_date'])) {
        Response::error("Invalid visit date format. Expected YYYY-MM-DD");
      }
      
      if (!empty($data['visit_time']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['visit_time'])) {
        Response::error("Invalid visit time format. Expected HH:MM:SS");
      }
      
      if (!empty($data['expected_exit_time']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['expected_exit_time'])) {
        Response::error("Invalid expected exit time format. Expected HH:MM:SS");
      }
      
      // Validate text field lengths
      if (!empty($data['purpose']) && strlen($data['purpose']) > 200) {
        Response::error("Purpose must be less than 200 characters");
      }
      
      // Validate visitor_type length
      if (!empty($data['visitor_type']) && strlen($data['visitor_type']) > 50) {
        Response::error("Visitor type must be less than 50 characters");
      }
      
      // Validate visitor_type against allowed ENUM values
      $allowedVisitorTypes = ['guest', 'delivery', 'service', 'other'];
      $visitorType = $data['visitor_type'] ?? 'guest';
      if (!in_array($visitorType, $allowedVisitorTypes)) {
        Response::error("Invalid visitor type. Allowed values: " . implode(', ', $allowedVisitorTypes));
      }
      
      // If user is resident, they can only add visitors for themselves
      if ($user['role'] === 'resident' && isset($data['resident_id']) && $data['resident_id'] != $user['uid']) {
        Response::forbidden("Residents can only add visitors for themselves");
      }
      
      // If user is guard, they need resident_id
      if ($user['role'] === 'guard' && !isset($data['resident_id'])) {
        Response::error("Resident ID is required when adding visitor as guard");
      }
      
      // Insert visitor
      $visitorId = $this->insert('visitors', [
        'name' => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'] ?? null,
        'purpose' => $data['purpose'],
        'visit_date' => $data['visit_date'] ?? date('Y-m-d'),
        'visit_time' => $data['visit_time'] ?? date('H:i:s'),
        'expected_exit_time' => $data['expected_exit_time'] ?? null,
        'status' => $status,
        'visitor_type' => $data['visitor_type'] ?? 'guest',
        'resident_id' => $residentId,
        'guard_id' => $user['role'] === 'guard' ? $user['uid'] : null,
        'society_id' => $societyId,
        'image_url' => $data['image_url'] ?? null
      ]);
      
      Response::success("Visitor added successfully", ['visitor_id' => $visitorId], 201);
      
    } catch(Exception $e) {
      error_log("Add visitor error: " . $e->getMessage());
      Response::error("Failed to add visitor: " . $e->getMessage(), 500);
    }
  }
  
  public function getVisitors() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $status = isset($_GET['status']) ? $_GET['status'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query based on user role
      $whereClause = "WHERE v.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      // Residents can only see their own visitors
      if ($user['role'] === 'resident') {
        $whereClause .= " AND v.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }
      
      // Filter by status if provided
      if ($status) {
        $whereClause .= " AND v.status = :status";
        $params['status'] = $status;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM visitors v {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get visitors
      $sql = "
        SELECT v.*, u.name as resident_name, g.name as guard_name
        FROM visitors v
        LEFT JOIN users u ON v.resident_id = u.id
        LEFT JOIN users g ON v.guard_id = g.id
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
      $visitors = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($visitors, $total, $pagination, "Visitors retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get visitors error: " . $e->getMessage());
      Response::error("Failed to retrieve visitors: " . $e->getMessage(), 500);
    }
  }
  
  public function getVisitorById($id) {
    try {
      $user = $this->auth->authenticate();
      
      // Build query based on user role
      $whereClause = "WHERE v.id = :id AND v.society_id = :society_id";
      $params = ['id' => $id, 'society_id' => $user['society_id']];
      
      // Residents can only see their own visitors
      if ($user['role'] === 'resident') {
        $whereClause .= " AND v.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }
      
      $sql = "
        SELECT v.*, u.name as resident_name, g.name as guard_name
        FROM visitors v
        LEFT JOIN users u ON v.resident_id = u.id
        LEFT JOIN users g ON v.guard_id = g.id
        {$whereClause}
      ";
      
      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
      $visitor = $stmt->fetch();
      
      if (!$visitor) {
        Response::notFound("Visitor not found or access denied");
      }
      
      Response::success("Visitor retrieved successfully", $visitor);
      
    } catch(Exception $e) {
      error_log("Get visitor error: " . $e->getMessage());
      Response::error("Failed to retrieve visitor: " . $e->getMessage(), 500);
    }
  }
  
  public function updateVisitorStatus($id) {
    try {
      // Guards and admins can update visitor status
      $user = $this->auth->authorizeAny(['guard', 'admin']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (empty($data['status'])) {
        Response::error("Status is required");
      }
      
      $allowedStatuses = ['pending', 'approved', 'rejected', 'entered', 'exited'];
      if (!in_array($data['status'], $allowedStatuses)) {
        Response::error("Invalid status. Allowed values: " . implode(', ', $allowedStatuses));
      }
      
      // Check if visitor exists and belongs to the same society
      $stmt = $this->db->prepare("SELECT id, status FROM visitors WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      $visitor = $stmt->fetch();
      
      if (!$visitor) {
        Response::notFound("Visitor not found");
      }
      
      // Update fields based on status
      $updateData = ['status' => $data['status']];
      
      if ($data['status'] === 'entered' && $visitor['status'] !== 'entered') {
        $updateData['actual_exit_time'] = null;
      } elseif ($data['status'] === 'exited' && $visitor['status'] !== 'exited') {
        $updateData['actual_exit_time'] = date('Y-m-d H:i:s');
      }
      
      // Update visitor
      $updated = $this->update('visitors', $updateData, 'id = :id', ['id' => $id]);
      
      if ($updated === 0) {
        Response::error("Failed to update visitor status", 500);
      }
      
      Response::success("Visitor status updated successfully", $updateData);
      
    } catch(Exception $e) {
      error_log("Update visitor status error: " . $e->getMessage());
      Response::error("Failed to update visitor status: " . $e->getMessage(), 500);
    }
  }
  
  public function deleteVisitor($id) {
    try {
      // Only admins can delete visitors
      $user = $this->auth->authorize('admin');
      
      // Check if visitor exists and belongs to the same society
      $stmt = $this->db->prepare("SELECT id FROM visitors WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      $visitor = $stmt->fetch();
      
      if (!$visitor) {
        Response::notFound("Visitor not found");
      }
      
      // Delete visitor
      $deleted = $this->delete('visitors', 'id = ?', [$id]);
      
      if ($deleted === 0) {
        Response::error("Failed to delete visitor", 500);
      }
      
      Response::success("Visitor deleted successfully");
      
    } catch(Exception $e) {
      error_log("Delete visitor error: " . $e->getMessage());
      Response::error("Failed to delete visitor: " . $e->getMessage(), 500);
    }
  }
}