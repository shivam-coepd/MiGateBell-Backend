<?php
require_once __DIR__.'/../../core/BaseController.php';

class CommunicationsController extends BaseController {
  
  public function createGroup() {
    try {
      // Only admins can create groups
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['name']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate name length
      if (strlen($data['name']) > 100) {
        Response::error("Group name must be less than 100 characters");
      }
      
      // Validate description length
      if (!empty($data['description']) && strlen($data['description']) > 500) {
        Response::error("Description must be less than 500 characters");
      }
      
      // Check if user exists
      $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND society_id = ?");
      $stmt->execute([$user['uid'], $user['society_id']]);
      if (!$stmt->fetch()) {
        Response::notFound("User not found");
      }
      
      // Insert group
      $groupId = $this->insert('groups', [
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'society_id' => $user['society_id'],
        'created_by' => $user['uid'],
        'is_active' => $data['is_active'] ?? 1
      ]);
      
      // Add creator as admin member
      $this->insert('group_members', [
        'group_id' => $groupId,
        'user_id' => $user['uid'],
        'role' => 'admin'
      ]);
      
      Response::success("Group created successfully", ['group_id' => $groupId], 201);
      
    } catch(Exception $e) {
      error_log("Create group error: " . $e->getMessage());
      Response::error("Failed to create group: " . $e->getMessage(), 500);
    }
  }
  
  public function getGroups() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query
      $whereClause = "WHERE g.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      if ($isActive !== null) {
        $whereClause .= " AND g.is_active = :is_active";
        $params['is_active'] = $isActive;
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM groups g {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get groups with member counts
      $sql = "
        SELECT g.*, COUNT(gm.id) as member_count
        FROM groups g
        LEFT JOIN group_members gm ON g.id = gm.group_id
        {$whereClause}
        GROUP BY g.id
        ORDER BY g.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $groups = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($groups, $total, $pagination, "Groups retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get groups error: " . $e->getMessage());
      Response::error("Failed to retrieve groups: " . $e->getMessage(), 500);
    }
  }
  
  public function joinGroup($groupId) {
    try {
      $user = $this->auth->authenticate();
      
      // Check if group exists and is active
      $stmt = $this->db->prepare("
        SELECT id, is_active 
        FROM groups 
        WHERE id = ? AND society_id = ? AND is_active = 1
      ");
      $stmt->execute([$groupId, $user['society_id']]);
      $group = $stmt->fetch();
      
      if (!$group) {
        Response::notFound("Group not found or inactive");
      }
      
      // Check if already a member
      $stmt = $this->db->prepare("
        SELECT id 
        FROM group_members 
        WHERE group_id = ? AND user_id = ?
      ");
      $stmt->execute([$groupId, $user['uid']]);
      if ($stmt->fetch()) {
        Response::error("You are already a member of this group", 409);
      }
      
      // Add user to group
      $membershipId = $this->insert('group_members', [
        'group_id' => $groupId,
        'user_id' => $user['uid'],
        'role' => 'member'
      ]);
      
      Response::success("Joined group successfully", ['membership_id' => $membershipId], 201);
      
    } catch(Exception $e) {
      error_log("Join group error: " . $e->getMessage());
      Response::error("Failed to join group: " . $e->getMessage(), 500);
    }
  }
  
  public function leaveGroup($groupId) {
    try {
      $user = $this->auth->authenticate();
      
      // Check if group exists
      $stmt = $this->db->prepare("
        SELECT id 
        FROM groups 
        WHERE id = ? AND society_id = ?
      ");
      $stmt->execute([$groupId, $user['society_id']]);
      $group = $stmt->fetch();
      
      if (!$group) {
        Response::notFound("Group not found");
      }
      
      // Check if member
      $stmt = $this->db->prepare("
        SELECT id, role 
        FROM group_members 
        WHERE group_id = ? AND user_id = ?
      ");
      $stmt->execute([$groupId, $user['uid']]);
      $membership = $stmt->fetch();
      
      if (!$membership) {
        Response::error("You are not a member of this group");
      }
      
      // Prevent admins from leaving their own group
      if ($membership['role'] === 'admin') {
        // Check if there are other admins
        $stmt = $this->db->prepare("
          SELECT COUNT(*) as admin_count 
          FROM group_members 
          WHERE group_id = ? AND role = 'admin' AND user_id != ?
        ");
        $stmt->execute([$groupId, $user['uid']]);
        $result = $stmt->fetch();
        
        if ($result['admin_count'] == 0) {
          Response::error("You cannot leave this group as you are the only admin. Please assign another admin first.");
        }
      }
      
      // Remove user from group
      $deleted = $this->delete('group_members', 'group_id = ? AND user_id = ?', [$groupId, $user['uid']]);
      
      if ($deleted === 0) {
        Response::error("Failed to leave group", 500);
      }
      
      Response::success("Left group successfully");
      
    } catch(Exception $e) {
      error_log("Leave group error: " . $e->getMessage());
      Response::error("Failed to leave group: " . $e->getMessage(), 500);
    }
  }
  
  public function createAnnouncement() {
    try {
      // Only admins can create announcements
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['title', 'content']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      // Validate title and content length
      if (strlen($data['title']) > 200) {
        Response::error("Title must be less than 200 characters");
      }
      
      if (strlen($data['content']) > 5000) {
        Response::error("Content must be less than 5000 characters");
      }
      
      // Validate date format for scheduled_at
      if (!empty($data['scheduled_at']) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['scheduled_at'])) {
        Response::error("Invalid scheduled date format. Expected YYYY-MM-DD HH:MM:SS");
      }
      
      // Check if target group exists (if specified)
      if (!empty($data['target_group_id'])) {
        $stmt = $this->db->prepare("
          SELECT id 
          FROM groups 
          WHERE id = ? AND society_id = ?
        ");
        $stmt->execute([$data['target_group_id'], $user['society_id']]);
        if (!$stmt->fetch()) {
          Response::notFound("Target group not found");
        }
      }
      
      // Insert announcement
      $announcementId = $this->insert('announcements', [
        'title' => $data['title'],
        'content' => $data['content'],
        'society_id' => $user['society_id'],
        'created_by' => $user['uid'],
        'target_group_id' => $data['target_group_id'] ?? null,
        'send_via' => $data['send_via'] ?? 'app',
        'scheduled_at' => $data['scheduled_at'] ?? null,
        'is_draft' => $data['is_draft'] ?? 1
      ]);
      
      Response::success("Announcement created successfully", ['announcement_id' => $announcementId], 201);
      
    } catch(Exception $e) {
      error_log("Create announcement error: " . $e->getMessage());
      Response::error("Failed to create announcement: " . $e->getMessage(), 500);
    }
  }
  
  public function getAnnouncements() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $isDraft = isset($_GET['is_draft']) ? (int)$_GET['is_draft'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query
      $whereClause = "WHERE a.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      // Filter drafts
      if ($isDraft !== null) {
        $whereClause .= " AND a.is_draft = :is_draft";
        $params['is_draft'] = $isDraft;
      }
      
      // Residents can only see announcements for their groups or general announcements
      if ($user['role'] === 'resident') {
        $whereClause .= " AND (a.target_group_id IS NULL OR a.target_group_id IN (
          SELECT group_id FROM group_members WHERE user_id = :user_id
        ))";
        $params['user_id'] = $user['uid'];
      }
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM announcements a {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get announcements
      $sql = "
        SELECT a.*, u.name as created_by_name, g.name as target_group_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        LEFT JOIN groups g ON a.target_group_id = g.id
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
      $announcements = $stmt->fetchAll();
      
      $this->sendPaginatedResponse($announcements, $total, $pagination, "Announcements retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get announcements error: " . $e->getMessage());
      Response::error("Failed to retrieve announcements: " . $e->getMessage(), 500);
    }
  }
  
  public function createPoll() {
    try {
      // Only admins can create polls
      $user = $this->auth->authorize('admin');
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      $errors = $this->validateRequiredFields($data, ['question', 'ends_at', 'options']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      if (!is_array($data['options']) || count($data['options']) < 2) {
        Response::error("At least 2 options are required for a poll");
      }
      
      // Validate question length
      if (strlen($data['question']) > 500) {
        Response::error("Question must be less than 500 characters");
      }
      
      // Validate each option length
      foreach ($data['options'] as $option) {
        if (strlen($option) > 200) {
          Response::error("Each option must be less than 200 characters");
        }
      }
      
      // Validate date formats
      if (!empty($data['starts_at']) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['starts_at'])) {
        Response::error("Invalid start date format. Expected YYYY-MM-DD HH:MM:SS");
      }
      
      if (!empty($data['ends_at']) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['ends_at'])) {
        Response::error("Invalid end date format. Expected YYYY-MM-DD HH:MM:SS");
      }
      
      // Validate poll type
      $allowedTypes = ['public', 'secret'];
      $pollType = $data['poll_type'] ?? 'public';
      if (!in_array($pollType, $allowedTypes)) {
        Response::error("Invalid poll type. Allowed values: " . implode(', ', $allowedTypes));
      }
      
      // Insert poll
      $pollId = $this->insert('polls', [
        'question' => $data['question'],
        'poll_type' => $pollType,
        'society_id' => $user['society_id'],
        'created_by' => $user['uid'],
        'starts_at' => $data['starts_at'] ?? date('Y-m-d H:i:s'),
        'ends_at' => $data['ends_at'],
        'is_active' => 1
      ]);
      
      // Insert poll options
      foreach ($data['options'] as $optionText) {
        $this->insert('poll_options', [
          'poll_id' => $pollId,
          'option_text' => $optionText
        ]);
      }
      
      Response::success("Poll created successfully", ['poll_id' => $pollId], 201);
      
    } catch(Exception $e) {
      error_log("Create poll error: " . $e->getMessage());
      Response::error("Failed to create poll: " . $e->getMessage(), 500);
    }
  }
  
  public function getPolls() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
      
      $pagination = $this->paginate($page, $limit);
      
      // Build query
      $whereClause = "WHERE p.society_id = :society_id";
      $params = ['society_id' => $user['society_id']];
      
      // Filter active polls
      if ($isActive !== null) {
        $whereClause .= " AND p.is_active = :is_active";
        $params['is_active'] = $isActive;
      }
      
      // Only show polls that have started and haven't ended
      $whereClause .= " AND p.starts_at <= NOW() AND p.ends_at >= NOW()";
      
      // Get total count
      $countSql = "SELECT COUNT(*) as count FROM polls p {$whereClause}";
      $countStmt = $this->db->prepare($countSql);
      $countStmt->execute($params);
      $total = $countStmt->fetch()['count'];
      
      // Get polls
      $sql = "
        SELECT p.*, u.name as created_by_name
        FROM polls p
        LEFT JOIN users u ON p.created_by = u.id
        {$whereClause}
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
      }
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $polls = $stmt->fetchAll();
      
      // Add options and vote counts to each poll
      foreach ($polls as &$poll) {
        // Get options
        $stmt = $this->db->prepare("
          SELECT po.*, COUNT(pv.id) as vote_count
          FROM poll_options po
          LEFT JOIN poll_votes pv ON po.id = pv.option_id
          WHERE po.poll_id = ?
          GROUP BY po.id
          ORDER BY po.id ASC
        ");
        $stmt->execute([$poll['id']]);
        $poll['options'] = $stmt->fetchAll();
        
        // Check if current user has voted
        $stmt = $this->db->prepare("
          SELECT id 
          FROM poll_votes 
          WHERE poll_id = ? AND user_id = ?
        ");
        $stmt->execute([$poll['id'], $user['uid']]);
        $poll['has_voted'] = !!$stmt->fetch();
      }
      
      $this->sendPaginatedResponse($polls, $total, $pagination, "Polls retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get polls error: " . $e->getMessage());
      Response::error("Failed to retrieve polls: " . $e->getMessage(), 500);
    }
  }
  
  public function voteOnPoll($pollId) {
    try {
      $user = $this->auth->authenticate();
      
      $data = json_decode(file_get_contents("php://input"), true);
      
      // Validation
      if (empty($data['option_id'])) {
        Response::error("Option ID is required");
      }
      
      // Check if poll exists and is active
      $stmt = $this->db->prepare("
        SELECT id, is_active, starts_at, ends_at
        FROM polls 
        WHERE id = ? AND society_id = ? AND is_active = 1
      ");
      $stmt->execute([$pollId, $user['society_id']]);
      $poll = $stmt->fetch();
      
      if (!$poll) {
        Response::notFound("Poll not found or inactive");
      }
      
      // Check if poll is open for voting
      $now = date('Y-m-d H:i:s');
      if ($now < $poll['starts_at'] || $now > $poll['ends_at']) {
        Response::error("Voting is not open for this poll");
      }
      
      // Check if option exists for this poll
      $stmt = $this->db->prepare("
        SELECT id 
        FROM poll_options 
        WHERE id = ? AND poll_id = ?
      ");
      $stmt->execute([$data['option_id'], $pollId]);
      if (!$stmt->fetch()) {
        Response::notFound("Option not found for this poll");
      }
      
      // Check if user has already voted
      $stmt = $this->db->prepare("
        SELECT id 
        FROM poll_votes 
        WHERE poll_id = ? AND user_id = ?
      ");
      $stmt->execute([$pollId, $user['uid']]);
      if ($stmt->fetch()) {
        Response::error("You have already voted in this poll", 409);
      }
      
      // Record vote
      $voteId = $this->insert('poll_votes', [
        'poll_id' => $pollId,
        'option_id' => $data['option_id'],
        'user_id' => $user['uid']
      ]);
      
      Response::success("Vote recorded successfully", ['vote_id' => $voteId], 201);
      
    } catch(Exception $e) {
      error_log("Vote on poll error: " . $e->getMessage());
      Response::error("Failed to record vote: " . $e->getMessage(), 500);
    }
  }
}