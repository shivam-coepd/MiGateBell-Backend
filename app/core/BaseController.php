<?php
require_once __DIR__.'/../core/Model.php';
require_once __DIR__.'/../core/Response.php';
require_once __DIR__.'/../middleware/AuthMiddleware.php';
require_once __DIR__.'/../middleware/RoleMiddleware.php';

class BaseController extends Model {
  protected $auth;
  protected $role;
  
  public function __construct() {
    parent::__construct();
    $this->auth = new AuthMiddleware($this->db);
    $this->role = new RoleMiddleware($this->db);
  }
  
  protected function validateRequiredFields($data, $requiredFields) {
    $errors = [];
    foreach ($requiredFields as $field) {
      if (!isset($data[$field]) || empty($data[$field])) {
        $errors[] = "{$field} is required";
      }
    }
    return $errors;
  }
  
  protected function sanitizeInput($data) {
    if (is_array($data)) {
      return array_map([$this, 'sanitizeInput'], $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
  }
  
  protected function getCurrentUserId() {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
      return null;
    }
    
    $token = substr($authHeader, 7);
    
    try {
      $payload = jwt_decode_token($token);
      return $payload['uid'];
    } catch(Exception $e) {
      return null;
    }
  }
  
  protected function paginate($page = 1, $limit = 10) {
    $page = max(1, (int)$page);
    $limit = max(1, min(100, (int)$limit)); // Limit between 1 and 100
    $offset = ($page - 1) * $limit;
    
    return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
  }
  
  protected function sendPaginatedResponse($data, $total, $pagination, $message = "Success") {
    $response = [
      'data' => $data,
      'pagination' => [
        'page' => $pagination['page'],
        'limit' => $pagination['limit'],
        'total' => $total,
        'pages' => ceil($total / $pagination['limit'])
      ]
    ];
    
    Response::success($message, $response);
  }
}