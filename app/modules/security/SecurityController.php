<?php
require_once __DIR__.'/../../core/BaseController.php';

class SecurityController extends BaseController {
  
  public function reportAlert() {
    try {
      // Residents, guards, and admins can report alerts
      $user = $this->auth->authorizeAny(['resident', 'guard', 'admin']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['alert_type', 'description']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate alert type
      $allowedTypes = ['suspicious_activity', 'unauthorized_access', 'emergency', 'other'];
      if (!in_array($data['alert_type'], $allowedTypes)) {
        Response::error("Invalid alert type. Allowed values: " . implode(', ', $allowedTypes));
      }
      
      // Validate severity
      $allowedSeverities = ['low', 'medium', 'high', 'critical'];
      $severity = $data['severity'] ?? 'medium';
      if (!in_array($severity, $allowedSeverities)) {
        Response::error("Invalid severity. Allowed values: " . implode(', ', $allowedSeverities));
      }
      
      // Insert alert
      $alertId = $this->insert('security_alerts', [
        'alert_type' => $data['alert_type'],
        'description' => $data['description'],
        'severity' => $severity,
        'reported_by' => $user['uid'],
        'society_id' => $user['society_id'],
        'image_url' => $data['image_url'] ?? null,
        'location' => $data['location'] ?? null,
        'status' => 'open'
      ]);
      
      Response::success("Security alert reported successfully", ['alert_id' => $alertId], 201);
      
    } catch(Exception $e) {
      error_log("Report alert error: " . $e->getMessage());
      Response::error("Failed to report alert: " . $e->getMessage(), 500);
    }
  }
  
  public function getAlerts() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $status = isset($_GET['status']) ? $_GET['status'] : null;
      $severity = isset($_GET['severity']) ? $_GET['severity'] : null;
      $alertType = isset($_GET['alert_type']) ? $_GET['alert_type'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query based on user role
      $whereClause = "WHERE sa.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      // Filter by status if provided
      if ($status) {
        $whereClause .= " AND sa.status = :status";
        $params['status'] = $status;
      }
      
      // Filter by severity if provided
      if ($severity) {
        $whereClause .= " AND sa.severity = :severity";
        $params['severity'] = $severity;
      }
      
      // Filter by alert type if provided
      if ($alertType) {
        $whereClause .= " AND sa.alert_type = :alert_type";
        $params['alert_type'] = $alertType;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM security_alerts sa {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get alerts
      $sql = "
        SELECT sa.*, u.name as reported_by_name, r.name as resolved_by_name
        FROM security_alerts sa
        LEFT JOIN users u ON sa.reported_by = u.id
        LEFT JOIN users r ON sa.resolved_by = r.id
        {$whereClause}
        ORDER BY sa.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $alerts = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($alerts, $total, $pagination, "Security alerts retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get alerts error: " . $e->getMessage());
      Response::error("Failed to retrieve alerts: " . $e->getMessage(), 500);
    }
  }
  
  public function getAlertById($id) {
    try {
      $user = $this->auth->authenticate();
      
      // Get alert
      $sql = "
        SELECT sa.*, u.name as reported_by_name, r.name as resolved_by_name
        FROM security_alerts sa
        LEFT JOIN users u ON sa.reported_by = u.id
        LEFT JOIN users r ON sa.resolved_by = r.id
        WHERE sa.id = :id AND sa.society_id = :society_id
      ";
      
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['id' => $id, 'society_id' => $user['society_id']]);
      $alert = $stmt->fetch();
      
      if (!$alert) {
        Response::notFound("Security alert not found");
      }
      
      Response::success("Security alert retrieved successfully", $alert);
      
    } catch(Exception $e) {
      error_log("Get alert error: " . $e->getMessage());
      Response::error("Failed to retrieve alert: " . $e->getMessage(), 500);
    }
  }
  
  public function updateAlertStatus($id) {
    try {
      // Only guards and admins can update alert status
      $user = $this->auth->authorizeAny(['guard', 'admin']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (empty($data['status'])) {
        Response::error("Status is required");
      }
      
      $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];
      if (!in_array($data['status'], $allowedStatuses)) {
        Response::error("Invalid status. Allowed values: " . implode(', ', $allowedStatuses));
      }
      
      // Check if alert exists
      $stmt = $this->db->prepare("
        SELECT id, status
        FROM security_alerts 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$id, $user['society_id']]);
      $alert = $stmt->fetch();
      
      if (!$alert) {
        Response::notFound("Security alert not found");
      }
      
      // Prepare update data
      $updateData = ['status' => $data['status']];
      
      // Set resolved info if status is resolved or closed
      if (in_array($data['status'], ['resolved', 'closed'])) {
        $updateData['resolved_by'] = $user['uid'];
        $updateData['resolved_at'] = date('Y-m-d H:i:s');
      }
      
      // Update alert
      $updated = $this->update('security_alerts', $updateData, 'id = :id', ['id' => $id]);
      
      if ($updated === 0) {
        Response::error("Failed to update alert status", 500);
      }
      
      Response::success("Alert status updated successfully");
      
    } catch(Exception $e) {
      error_log("Update alert status error: " . $e->getMessage());
      Response::error("Failed to update alert status: " . $e->getMessage(), 500);
    }
  }
  
  public function getEmergencyContacts() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : 1;
      $contactType = isset($_GET['contact_type']) ? $_GET['contact_type'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query
      $whereClause = "WHERE ec.society_id = :society_id AND ec.is_active = :is_active";
      $params = ['society_id' => $user['society_id'], 'is_active' => $isActive];
      
      // Filter by contact type if provided
      if ($contactType) {
        $whereClause .= " AND ec.contact_type = :contact_type";
        $params['contact_type'] = $contactType;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM emergency_contacts ec {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get contacts
      $sql = "
        SELECT ec.*
        FROM emergency_contacts ec
        {$whereClause}
        ORDER BY ec.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $contacts = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($contacts, $total, $pagination, "Emergency contacts retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get emergency contacts error: " . $e->getMessage());
      Response::error("Failed to retrieve emergency contacts: " . $e->getMessage(), 500);
    }
  }
  
  public function addEmergencyContact() {
    try {
      // Only admins can add emergency contacts
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['name', 'phone', 'contact_type']);
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
      
      // Validate contact type
      $allowedTypes = ['police', 'fire', 'ambulance', 'hospital', 'other'];
      if (!in_array($data['contact_type'], $allowedTypes)) {
        Response::error("Invalid contact type. Allowed values: " . implode(', ', $allowedTypes));
      }
      
      // Insert contact
      $contactId = $this->insert('emergency_contacts', [
        'name' => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'] ?? null,
        'contact_type' => $data['contact_type'],
        'society_id' => $user['society_id'],
        'is_active' => $data['is_active'] ?? 1
      ]);
      
      Response::success("Emergency contact added successfully", ['contact_id' => $contactId], 201);
      
    } catch(Exception $e) {
      error_log("Add emergency contact error: " . $e->getMessage());
      Response::error("Failed to add emergency contact: " . $e->getMessage(), 500);
    }
  }
}