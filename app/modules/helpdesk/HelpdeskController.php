<?php
require_once __DIR__.'/../../core/BaseController.php';

class HelpdeskController extends BaseController {
  
  public function createTicket() {
    try {
      $user = $this->auth->authorizeAny(['resident', 'admin']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      $errors = $this->validateRequiredFields($data, ['title', 'description']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      $allowedCategories = ['general', 'maintenance', 'security', 'billing', 'other', 'plumbing', 'electrical', 'carpentry', 'cleaning'];
      $category = $data['category'] ?? 'general';
      if (!in_array($category, $allowedCategories)) {
        Response::error("Invalid category. Allowed values: " . implode(', ', $allowedCategories));
      }
      
      $allowedPriorities = ['low', 'medium', 'high', 'urgent'];
      $priority = $data['priority'] ?? 'medium';
      if (!in_array($priority, $allowedPriorities)) {
        Response::error("Invalid priority. Allowed values: " . implode(', ', $allowedPriorities));
      }

      $residentId = $user['uid'];
      if ($user['role'] === 'admin') {
        if (empty($data['resident_id'])) {
          Response::error("resident_id is required when creating a ticket as society admin");
        }
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND society_id = ? AND role = 'resident'");
        $stmt->execute([$data['resident_id'], $user['society_id']]);
        if (!$stmt->fetch()) {
          Response::error("Invalid resident for this society", 400);
        }
        $residentId = (int) $data['resident_id'];
      }
      
      $ticketNumber = $this->generateTicketNumber($user['society_id']);
      
      $ticketId = $this->insert('tickets', [
        'ticket_number' => $ticketNumber,
        'title'         => $data['title'],
        'description'   => $data['description'],
        'category'      => $category,
        'priority'      => $priority,
        'status'        => 'open',
        'resident_id'   => $residentId,
        'society_id'    => $user['society_id']
      ]);
      
      Response::success("Ticket created successfully", ['ticket_id' => $ticketId], 201);
      
    } catch(Exception $e) {
      error_log("Create ticket error: " . $e->getMessage());
      Response::error("Failed to create ticket: " . $e->getMessage(), 500);
    }
  }
  
  private function generateTicketNumber($societyId) {
    try {
      $stmt = $this->db->prepare("
        SELECT ticket_number FROM tickets WHERE society_id = ? ORDER BY created_at DESC LIMIT 1
      ");
      $stmt->execute([$societyId]);
      $lastTicket = $stmt->fetch();
      
      if ($lastTicket) {
        $lastNumber = intval(substr($lastTicket['ticket_number'], -6));
        $newNumber  = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
      } else {
        $newNumber = '000001';
      }
      
      return "TK-{$societyId}-{$newNumber}";
    } catch(Exception $e) {
      return "TK-" . time();
    }
  }
  
  public function getTickets() {
    try {
      $user = $this->auth->authenticate();
      
      $page     = isset($_GET['page'])     ? (int)$_GET['page']     : 1;
      $limit    = isset($_GET['limit'])    ? (int)$_GET['limit']    : 10;
      $status   = $_GET['status']   ?? null;
      $category = $_GET['category'] ?? null;
      $priority = $_GET['priority'] ?? null;
      
      $pagination = $this->paginate($page, $limit);
      
      $whereClause = "WHERE t.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      if ($user['role'] === 'resident') {
        $whereClause .= " AND t.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }
      if ($status) {
        $whereClause .= " AND t.status = :status";
        $params['status'] = $status;
      }
      if ($category) {
        $whereClause .= " AND t.category = :category";
        $params['category'] = $category;
      }
      if ($priority) {
        $whereClause .= " AND t.priority = :priority";
        $params['priority'] = $priority;
      }
      
      $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM tickets t {$whereClause}");
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      $sql = "
        SELECT t.*, u.name as resident_name, a.name as assigned_to_name,
               (
                 SELECT f.flat_number FROM flats f
                 WHERE f.society_id = t.society_id
                   AND (f.owner_id = t.resident_id OR f.tenant_id = t.resident_id)
                 ORDER BY f.id ASC LIMIT 1
               ) AS flat_number,
               (SELECT COUNT(*) FROM ticket_comments tc WHERE tc.ticket_id = t.id) AS comment_count
        FROM tickets t
        LEFT JOIN users u ON t.resident_id = u.id
        LEFT JOIN users a ON t.assigned_to  = a.id
        {$whereClause}
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit',  $pagination['limit'],  PDO::PARAM_INT);
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
      
      $whereClause = "WHERE t.id = :id AND t.society_id = :society_id";
      $params = ['id' => $id, 'society_id' => $user['society_id']];
      
      if ($user['role'] === 'resident') {
        $whereClause .= " AND t.resident_id = :resident_id";
        $params['resident_id'] = $user['uid'];
      }
      
      $sql = "
        SELECT t.*, u.name as resident_name, a.name as assigned_to_name,
               (
                 SELECT f.flat_number FROM flats f
                 WHERE f.society_id = t.society_id
                   AND (f.owner_id = t.resident_id OR f.tenant_id = t.resident_id)
                 ORDER BY f.id ASC LIMIT 1
               ) AS flat_number
        FROM tickets t
        LEFT JOIN users u ON t.resident_id = u.id
        LEFT JOIN users a ON t.assigned_to  = a.id
        {$whereClause}
      ";
      
      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
      $ticket = $stmt->fetch();
      
      if (!$ticket) {
        Response::notFound("Ticket not found or access denied");
      }
      
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
      $user = $this->auth->authorizeAny(['resident', 'admin', 'staff']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (empty($data['status'])) {
        Response::error("Status is required");
      }
      
      $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];
      if (!in_array($data['status'], $allowedStatuses)) {
        Response::error("Invalid status. Allowed values: " . implode(', ', $allowedStatuses));
      }
      
      $stmt = $this->db->prepare("SELECT id, resident_id, status FROM tickets WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      $ticket = $stmt->fetch();
      
      if (!$ticket) {
        Response::notFound("Ticket not found");
      }
      
      if ($user['role'] === 'resident' && $ticket['resident_id'] != $user['uid']) {
        Response::forbidden("You can only update your own tickets");
      }
      
      $updateData = ['status' => $data['status']];
      if ($data['status'] === 'resolved') {
        $updateData['resolved_at'] = date('Y-m-d H:i:s');
      }
      
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

  public function updateTicket($id) {
    try {
      $user = $this->auth->authorizeAny(['resident', 'admin']);

      $data = json_decode(file_get_contents("php://input"), true);

      $stmt = $this->db->prepare("SELECT id, resident_id, status FROM tickets WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      $ticket = $stmt->fetch();

      if (!$ticket) {
        Response::notFound("Ticket not found");
      }

      if ($user['role'] === 'resident') {
        if ($ticket['resident_id'] != $user['uid']) {
          Response::forbidden("You can only edit your own tickets");
        }
        if ($ticket['status'] !== 'open') {
          Response::error("Only open tickets can be edited", 400);
        }
      }

      $updateData = [];

      if (!empty($data['title'])) {
        $updateData['title'] = $data['title'];
      }
      if (!empty($data['description'])) {
        $updateData['description'] = $data['description'];
      }
      if (!empty($data['category'])) {
        $allowedCategories = ['general', 'maintenance', 'security', 'billing', 'other', 'plumbing', 'electrical', 'carpentry', 'cleaning'];
        if (!in_array($data['category'], $allowedCategories)) {
          Response::error("Invalid category. Allowed: " . implode(', ', $allowedCategories));
        }
        $updateData['category'] = $data['category'];
      }
      if (!empty($data['priority'])) {
        $allowedPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($data['priority'], $allowedPriorities)) {
          Response::error("Invalid priority. Allowed: " . implode(', ', $allowedPriorities));
        }
        $updateData['priority'] = $data['priority'];
      }

      if (empty($updateData)) {
        Response::error("No valid fields provided to update", 400);
      }

      $this->update('tickets', $updateData, 'id = :id', ['id' => $id]);

      Response::success("Ticket updated successfully");

    } catch(Exception $e) {
      error_log("Update ticket error: " . $e->getMessage());
      Response::error("Failed to update ticket: " . $e->getMessage(), 500);
    }
  }

  public function deleteTicket($id) {
    try {
      $user = $this->auth->authorizeAny(['resident', 'admin']);

      $stmt = $this->db->prepare("SELECT id, resident_id, status FROM tickets WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      $ticket = $stmt->fetch();

      if (!$ticket) {
        Response::notFound("Ticket not found");
      }

      if ($user['role'] === 'resident') {
        if ($ticket['resident_id'] != $user['uid']) {
          Response::forbidden("You can only delete your own tickets");
        }
        if ($ticket['status'] !== 'open') {
          Response::error("Only open tickets can be deleted", 400);
        }
      }

      $stmt = $this->db->prepare("DELETE FROM ticket_comments WHERE ticket_id = ?");
      $stmt->execute([$id]);

      $stmt = $this->db->prepare("DELETE FROM tickets WHERE id = ?");
      $stmt->execute([$id]);

      Response::success("Ticket deleted successfully");

    } catch(Exception $e) {
      error_log("Delete ticket error: " . $e->getMessage());
      Response::error("Failed to delete ticket: " . $e->getMessage(), 500);
    }
  }

  public function assignTicket($id) {
    try {
      $user = $this->auth->authorizeAny(['admin', 'staff']);
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (empty($data['assigned_to'])) {
        Response::error("Assigned to user ID is required");
      }
      
      $stmt = $this->db->prepare("SELECT id FROM tickets WHERE id = ? AND society_id = ?");
      $stmt->execute([$id, $user['society_id']]);
      if (!$stmt->fetch()) {
        Response::notFound("Ticket not found");
      }
      
      $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND society_id = ? AND role IN ('admin', 'staff')");
      $stmt->execute([$data['assigned_to'], $user['society_id']]);
      if (!$stmt->fetch()) {
        Response::notFound("Assigned user not found or not authorized");
      }
      
      $updated = $this->update('tickets', [
        'assigned_to' => $data['assigned_to'],
        'status'      => 'in_progress'
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
      
      if (empty($data['comment'])) {
        Response::error("Comment is required");
      }
      
      $stmt = $this->db->prepare("SELECT id, resident_id FROM tickets WHERE id = ? AND society_id = ?");
      $stmt->execute([$ticketId, $user['society_id']]);
      $ticket = $stmt->fetch();
      
      if (!$ticket) {
        Response::notFound("Ticket not found");
      }
      
      if ($user['role'] === 'resident' && $ticket['resident_id'] != $user['uid']) {
        Response::forbidden("You can only comment on your own tickets");
      }
      
      $commentId = $this->insert('ticket_comments', [
        'ticket_id' => $ticketId,
        'user_id'   => $user['uid'],
        'comment'   => $data['comment']
      ]);
      
      Response::success("Comment added successfully", ['comment_id' => $commentId], 201);
      
    } catch(Exception $e) {
      error_log("Add comment error: " . $e->getMessage());
      Response::error("Failed to add comment: " . $e->getMessage(), 500);
    }
  }
}
