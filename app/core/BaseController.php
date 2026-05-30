<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

class BaseController extends Model
{
  protected $auth;
  protected $role;

  public function __construct()
  {
    parent::__construct();
    $this->auth = new AuthMiddleware($this->db);
    $this->role = new RoleMiddleware($this->db);
  }

  protected function validateRequiredFields($data, $requiredFields)
  {
    $errors = [];
    foreach ($requiredFields as $field) {
      if (!isset($data[$field]) || empty($data[$field])) {
        $errors[] = "{$field} is required";
      }
    }
    return $errors;
  }

  protected function sanitizeInput($data)
  {
    if (is_array($data)) {
      return array_map([$this, 'sanitizeInput'], $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
  }

  protected function getCurrentUserId()
  {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
      return null;
    }

    $token = substr($authHeader, 7);

    try {
      $payload = jwt_decode_token($token);
      return $payload['uid'];
    } catch (Exception $e) {
      return null;
    }
  }

  protected function paginate($page = 1, $limit = 10)
  {
    $page = max(1, (int) $page);
    $limit = max(1, min(100, (int) $limit)); // Limit between 1 and 100
    $offset = ($page - 1) * $limit;

    return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
  }

  protected function sendPaginatedResponse($data, $total, $pagination, $message = "Success", $extraData = [])
  {
    $response = [
      'data' => $data,
      'pagination' => [
        'page' => $pagination['page'],
        'limit' => $pagination['limit'],
        'total' => $total,
        'pages' => ceil($total / $pagination['limit'])
      ]
    ];

    if (!empty($extraData)) {
      $response = array_merge($response, $extraData);
    }

    Response::success($message, $response);
  }

  protected function autoLinkAdminSociety($userId)
  {
    try {
      $stmt = $this->db->prepare("SELECT id, email, phone, role, society_id FROM users WHERE id = ?");
      $stmt->execute([$userId]);
      $user = $stmt->fetch();

      if (!$user || $user['role'] !== 'admin') {
        return $user ? $user['society_id'] : null;
      }

      if (!empty($user['society_id'])) {
        return $user['society_id'];
      }

      // Find matching society
      $email = trim((string) ($user['email'] ?? ''));
      $phone = trim((string) ($user['phone'] ?? ''));

      $matchSoc = null;

      // 1. By admin_id
      $stmt = $this->db->prepare("SELECT id, admin_id FROM societies WHERE admin_id = ? ORDER BY id ASC LIMIT 1");
      $stmt->execute([$userId]);
      $matchSoc = $stmt->fetch();

      // 2. By email
      if (!$matchSoc && $email !== '') {
        $stmt = $this->db->prepare("SELECT id, admin_id FROM societies WHERE contact_email = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$email]);
        $matchSoc = $stmt->fetch();
      }

      // 3. By phone
      if (!$matchSoc && $phone !== '') {
        $stmt = $this->db->prepare("SELECT id, admin_id FROM societies WHERE contact_phone = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$phone]);
        $matchSoc = $stmt->fetch();
      }

      // 4. Fallback: Any society where admin_id IS NULL or 0
      if (!$matchSoc) {
        $stmt = $this->db->query("SELECT id, admin_id FROM societies WHERE admin_id IS NULL OR admin_id = 0 ORDER BY id ASC LIMIT 1");
        $matchSoc = $stmt->fetch();
      }

      if ($matchSoc) {
        $socId = $matchSoc['id'];
        $this->update('users', ['society_id' => $socId], 'id = :id', ['id' => $userId]);

        if (empty($matchSoc['admin_id'])) {
          $this->update('societies', ['admin_id' => $userId], 'id = :id', ['id' => $socId]);
        }

        return $socId;
      }

      return null;
    } catch (Exception $e) {
      error_log("autoLinkAdminSociety error: " . $e->getMessage());
      return null;
    }
  }
}