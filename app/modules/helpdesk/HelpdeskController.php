<?php
require_once __DIR__.'/../../core/BaseController.php';

class HelpdeskController extends BaseController {
  
  public function createTicket() {
    try {
      // Residents can create tickets
      $user = $this->auth->authorize('resident');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['title', 'description']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate category
      $allowedCategories = ['general', 'maintenance', 'security', 'billing', 'other'];
      $category = $data['category'] ?? 'general';
      if (!in_array($category, $allowedCategories)) {
        Response::error("Invalid category. Allowed values: " . implode(', ', $allowedCategories));
      }
      
      // Validate priority
      $allowedPriorities = ['low', 'medium', 'high', 'urgent'];
      $priority = $data['priority'] ?? 'medium';
      if (!in_array($priority, $allowedPriorities)) {
        Response::error("Invalid priority. Allowed values: " . implode(', ', $allowedPriorities));
      }
      
      // Generate ticket number
      $ticketNumber = $this->generateTicketNumber($user['society_id']);
      
      // Insert ticket
      $ticketId = $this->insert('tickets', [
        'ticket_number' => $ticketNumber,
        'title' => $data['title'],
        'description' => $data['description'],
        'category' => $data['category'] ?? 'general',
        'priority' => $data['priority'] ?? 'medium',
        'status' => 'open',
        'resident_id' => $user['uid'],
        'society_id' => $user['society_id']
      ]);
      
      Response::success("Ticket created successfully", ['ticket_id' => $ticketId], 201);
      
    } catch(Exception $e) {
      error_log("Create ticket error: " . $e->getMessage());
      Response::error("Failed to create ticket: " . $e->getMessage(), 500);
    }
  }
  
  private function generateTicketNumber($societyId) {
    try {
      // Get the last ticket number for this society
      $stmt = $this->db->prepare("
        SELECT ticket_number 
        FROM tickets 
        WHERE society_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
      ");
      $stmt->execute([$societyId]);
      $lastTicket = $stmt->fetch();
      
      if ($lastTicket) {
        // Extract number part and increment
        $lastNumber = intval(substr($lastTicket['ticket_number'], -6));
        $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
      } else {
        // First ticket
        $newNumber = '000001';
      }
      
      // Format: TK-[SOCIETY_ID]-[NUMBER]
      return "TK-{$societyId}-{$newNumber}";
    } catch(Exception $e) {
      // Fallback to timestamp if there's an error
      return "TK-" . time();
    }
  }
  
  public function getTickets() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $status = isset($_GET['status']) ? $_GET['status'] : null;
      $category = isset($_GET['category']) ? $_GET['category'] : null;
      $priority = isset($_GET['priority']) ? $_GET['priority'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query based on user role
      $whereClause = "WHERE t.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      // Residents can only see their own tickets
      if ($user['role'] === 'resident') {
        $whereClause .= " AND t.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }
      
      // Filter by status if provided
      if ($status) {
        $whereClause .= " AND t.status = :status";
        $params['status'] = $status;
      }
      
      // Filter by category if provided
      if ($category) {
        $whereClause .= " AND t.category = :category";
        $params['category'] = $category;
      }
      
      // Filter by priority if provided
      if ($priority) {
        $whereClause .= " AND t.priority = :priority";
        $params['priority'] = $priority;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM tickets t {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get tickets
      $sql = "
        SELECT t.*, u.name as resident_name, a.name as assigned_to_name
        FROM tickets t
        LEFT JOIN users u ON t.resident_id = u.id
        LEFT JOIN users a ON t.assigned_to = a.id
        {$whereClause}
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $tickets = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($tickets, $total, $pagination, "Tickets retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get tickets error: " . $e->getMessage());
      Response::error("Failed to retrieve tickets: " . $e->getMessage(), 500);
    }
  }
  
  public function getTicketById($id) {
    try {
      $user = $this->auth->authenticate();
      
      // Build query based on user role
      $whereClause = "WHERE t.id = :id AND t.society_id = :society_id";
      $params = ['id' => $id, 'society_id' => $user['society_id']];
      
      // Residents can only see their own tickets
      if ($user['role'] === 'resident') {
        $whereClause .= " AND t.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }
      
      // Get ticket
      $sql = "
        SELECT t.*, u.name as resident_name, a.name as assigned_to_name
        FROM tickets t
        LEFT JOIN users u ON t.resident_id = u.id
        LEFT JOIN users a ON t.assigned_to = a.id
        {$whereClause}
      ";
      
      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
      $ticket = $stmt->fetch();
      
      if (!$ticket) {
        Response::notFound("Ticket not found or access denied");
      }
      
      // Get comments
      $stmt = $this->db->prepare("
        SELECT tc.*, u.name as commenter_name
        FROM ticket_comments tc
        JOIN users u ON tc.user_id = u.id
        WHERE tc.ticket_id = ?
        ORDER BY tc.created_at ASC
      ");
      $stmt->execute([$id]);
      $ticket['comments'] = $stmt->fetchAll();
      
      Response::success("Ticket retrieved successfully", $ticket);
      
    } catch(Exception $e) {
      error_log("Get ticket error: " . $e->getMessage());
      Response::error("Failed to retrieve ticket: " . $e->getMessage(), 500);
    }
  }
  
  public function updateTicketStatus($id) {
    try {
      // Residents can update their own tickets
      // Admins and staff can update any ticket
      $user = $this->auth->authorizeAny(['resident', 'admin', 'staff']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (empty($data['status'])) {
        Response::error("Status is required");
      }
      
      $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];
      if (!in_array($data['status'], $allowedStatuses)) {
        Response::error("Invalid status. Allowed values: " . implode(', ', $allowedStatuses));
      }
      
      // Check if ticket exists
      $stmt = $this->db->prepare("
        SELECT id, resident_id, status
        FROM tickets 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$id, $user['society_id']]);
      $ticket = $stmt->fetch();
      
      if (!$ticket) {
        Response::notFound("Ticket not found");
      }
      
      // Check permissions
      if ($user['role'] === 'resident' && $ticket['resident_id'] != $user['uid']) {
        Response::forbidden("You can only update your own tickets");
      }
      
      // Prepare update data
      $updateData = ['status' => $data['status']];
      
      // Set resolved_at if status is resolved
      if ($data['status'] === 'resolved') {
        $updateData['resolved_at'] = date('Y-m-d H:i:s');
      }
      
      // Update ticket
      $updated = $this->update('tickets', $updateData, 'id = :id', ['id' => $id]);
      
      if ($updated === 0) {
        Response::error("Failed to update ticket status", 500);
      }
      
      Response::success("Ticket status updated successfully");
      
    } catch(Exception $e) {
      error_log("Update ticket status error: " . $e->getMessage());
      Response::error("Failed to update ticket status: " . $e->getMessage(), 500);
    }
  }
  
  public function assignTicket($id) {
    try {
      // Only admins and staff can assign tickets
      $user = $this->auth->authorizeAny(['admin', 'staff']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (empty($data['assigned_to'])) {
        Response::error("Assigned to user ID is required");
      }
      
      // Check if ticket exists
      $stmt = $this->db->prepare("
        SELECT id
        FROM tickets 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$id, $user['society_id']]);
      $ticket = $stmt->fetch();
      
      if (!$ticket) {
        Response::notFound("Ticket not found");
      }
      
      // Check if assigned user exists and belongs to the same society
      $stmt = $this->db->prepare("
        SELECT id
        FROM users 
        WHERE id = ? AND society_id = ? AND role IN ('admin', 'staff')
      ");
      $stmt->execute([$data['assigned_to'], $user['society_id']]);
      $assignedUser = $stmt->fetch();
      
      if (!$assignedUser) {
        Response::notFound("Assigned user not found or not authorized");
      }
      
      // Update ticket
      $updated = $this->update('tickets', [
        'assigned_to' => $data['assigned_to'],
        'status' => 'in_progress'
      ], 'id = :id', ['id' => $id]);
      
      if ($updated === 0) {
        Response::error("Failed to assign ticket", 500);
      }
      
      Response::success("Ticket assigned successfully");
      
    } catch(Exception $e) {
      error_log("Assign ticket error: " . $e->getMessage());
      Response::error("Failed to assign ticket: " . $e->getMessage(), 500);
    }
  }
  
  public function addComment($ticketId) {
    try {
      $user = $this->auth->authenticate();
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      if (empty($data['comment'])) {
        Response::error("Comment is required");
      }
      
      // Check if ticket exists
      $stmt = $this->db->prepare("
        SELECT id, resident_id
        FROM tickets 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$ticketId, $user['society_id']]);
      $ticket = $stmt->fetch();
      
      if (!$ticket) {
        Response::notFound("Ticket not found");
      }
      
      // Check permissions (residents can only comment on their own tickets)
      if ($user['role'] === 'resident' && $ticket['resident_id'] != $user['uid']) {
        Response::forbidden("You can only comment on your own tickets");
      }
      
      // Insert comment
      $commentId = $this->insert('ticket_comments', [
        'ticket_id' => $ticketId,
        'user_id' => $user['uid'],
        'comment' => $data['comment']
      ]);
      
      Response::success("Comment added successfully", ['comment_id' => $commentId], 201);
      
    } catch(Exception $e) {
      error_log("Add comment error: " . $e->getMessage());
      Response::error("Failed to add comment: " . $e->getMessage(), 500);
    }
  }
}