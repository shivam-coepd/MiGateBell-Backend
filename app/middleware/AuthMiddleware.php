<?php
require_once __DIR__.'/../helpers/jwt_helper.php';

class AuthMiddleware {
  private $db;
  
  public function __construct($database) {
    $this->db = $database;
  }
  
  public function authenticate() {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
      Response::unauthorized("Authorization header missing or invalid");
    }
    
    $token = substr($authHeader, 7);
    
    try {
      $payload = jwt_decode_token($token);
      return $payload;
    } catch(Exception $e) {
      Response::unauthorized("Invalid token: " . $e->getMessage());
    }
  }
  
  public function authorize($requiredRole) {
    $user = $this->authenticate();
    
    if ($user['role'] !== $requiredRole) {
      Response::forbidden("Access denied. Required role: " . $requiredRole);
    }
    
    return $user;
  }
  
  public function authorizeAny($allowedRoles) {
    $user = $this->authenticate();
    
    if (!in_array($user['role'], $allowedRoles)) {
      Response::forbidden("Access denied. Your role is not authorized for this action.");
    }
    
    return $user;
  }
  
  public function authorizeWithSociety($societyId) {
    $user = $this->authenticate();
    
    if ($user['society_id'] != $societyId && $user['role'] !== 'super_admin') {
      Response::forbidden("Access denied. You don't have permission for this society.");
    }
    
    return $user;
  }
  
  public function validateToken($token) {
    try {
      $payload = jwt_decode_token($token);
      return $payload;
    } catch(Exception $e) {
      throw $e;
    }
  }
}