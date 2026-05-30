<?php
class RoleMiddleware {
  private $db;
  
  public function __construct($database) {
    $this->db = $database;
  }
  
  public function checkPermission($userId, $permission) {
    try {
      // Check if user has specific permission
      $stmt = $this->db->prepare("
        SELECT COUNT(*) as count 
        FROM user_roles ur
        JOIN role_permissions rp ON ur.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE ur.user_id = ? AND p.name = ?
      ");
      $stmt->execute([$userId, $permission]);
      $result = $stmt->fetch();
      
      return $result['count'] > 0;
    } catch(Exception $e) {
      error_log("Permission check error: " . $e->getMessage());
      return false;
    }
  }
  
  public function requirePermission($userId, $permission) {
    if (!$this->checkPermission($userId, $permission)) {
      Response::forbidden("You don't have permission to perform this action: " . $permission);
    }
  }
  
  public function getUserPermissions($userId) {
    try {
      $stmt = $this->db->prepare("
        SELECT DISTINCT p.name 
        FROM user_roles ur
        JOIN role_permissions rp ON ur.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE ur.user_id = ?
      ");
      $stmt->execute([$userId]);
      $permissions = $stmt->fetchAll();
      
      return array_column($permissions, 'name');
    } catch(Exception $e) {
      error_log("Get user permissions error: " . $e->getMessage());
      return [];
    }
  }
  
  public function hasRole($userId, $roleName) {
    try {
      $stmt = $this->db->prepare("
        SELECT COUNT(*) as count 
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ? AND r.name = ?
      ");
      $stmt->execute([$userId, $roleName]);
      $result = $stmt->fetch();
      
      return $result['count'] > 0;
    } catch(Exception $e) {
      error_log("Role check error: " . $e->getMessage());
      return false;
    }
  }
}