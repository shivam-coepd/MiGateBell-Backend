<?php
require_once __DIR__ . '/../../helpers/jwt_helper.php';
require_once __DIR__ . '/../../helpers/app_userID_helper.php';
require_once __DIR__ . '/../../core/BaseController.php';

class AuthController extends BaseController
{

  public function register()
  {
    try {
      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      $errors = [];
      if (empty($data['name']))
        $errors[] = 'Name is required';
      if (empty($data['phone']))
        $errors[] = 'Phone is required';
      if (empty($data['password']))
        $errors[] = 'Password is required';

      // Only require society_id for non-super_admin roles
      if (empty($data['society_id']) && (!isset($data['role']) || $data['role'] !== 'super_admin')) {
        $errors[] = 'Society ID is required for this role';
      }

      if (!empty($errors)) {
        Response::validationError($errors);
      }

      // Validate phone number format
      if (!empty($data['phone']) && !preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
        Response::error("Phone number must be 10-15 digits");
      }

      // Validate password strength
      if (!empty($data['password']) && strlen($data['password']) < 8) {
        Response::error("Password must be at least 8 characters long");
      }

      if (!empty($data['password']) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/', $data['password'])) {
        Response::error("Password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character");
      }

      // Validate name length
      if (!empty($data['name']) && strlen($data['name']) > 100) {
        Response::error("Name must be less than 100 characters");
      }

      // Check if society exists when provided
      if (!empty($data['society_id'])) {
        $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
        $stmt->execute([$data['society_id']]);
        if (!$stmt->fetch()) {
          Response::error("Society not found", 404);
        }
      }

      // Check if user already exists
      $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
      $stmt->execute([$data['phone']]);
      if ($stmt->fetch()) {
        Response::error("User with this phone already exists", 409);
      }

      // Hash password
      $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

      // Insert user (society_id can be NULL for super_admin)
      $societyId = isset($data['society_id']) ? $data['society_id'] : null;
      $role = $data['role'] ?? 'resident';

      // Validate role against allowed ENUM values
      $allowedRoles = ['admin', 'resident', 'guard', 'staff', 'super_admin'];
      if (!in_array($role, $allowedRoles)) {
        Response::error("Invalid role. Allowed values: " . implode(', ', $allowedRoles));
      }

      $appUserId = AppUserIdHelper::generateUnique($this->db);

      $fields = [
        'app_user_id' => $appUserId,
        'name' => $data['name'],
        'phone' => $data['phone'],
        'password' => $hashedPassword,
        'role' => $role,
        'society_id' => $societyId
      ];

      if ($role === 'super_admin') {
        $fields['status'] = 'active';
      }

      $columns = implode(', ', array_keys($fields));
      $placeholders = implode(', ', array_fill(0, count($fields), '?')); // Create ?, ?, ... string

      $stmt = $this->db->prepare("INSERT INTO users ($columns) VALUES ($placeholders)");
      $stmt->execute(array_values($fields));

      $userId = $this->db->lastInsertId();

      // Generate token
      $tokenData = [
        'uid' => $userId,
        'role' => $role,
        'exp' => time() + 86400
      ];

      // Only include society_id in token if it exists
      if ($societyId !== null) {
        $tokenData['society_id'] = $societyId;
      }

      $token = jwt_encode($tokenData);

      Response::success("Registration successful", [
        'user_id' => $userId,
        'app_user_id' => $appUserId,
        'token' => $token
      ], 201);

    } catch (Exception $e) {
      error_log("Registration error: " . $e->getMessage());
      Response::error("Registration failed: " . $e->getMessage(), 500);
    }
  }

  public function login()
  {
    try {
      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      if (empty($data['phone']) || empty($data['password'])) {
        Response::error("Phone and password are required");
      }

      // Get user
      $stmt = $this->db->prepare(
        "SELECT id, app_user_id, name, phone, password, role, society_id 
     FROM users WHERE phone = ?"
      );

      $stmt->execute([$data['phone']]);
      $user = $stmt->fetch();

      if (!$user || !password_verify($data['password'], $user['password'])) {
        Response::error("Invalid credentials", 401);
      }

      // Generate token
      $tokenData = [
        'uid' => $user['id'],
        'role' => $user['role'],
        'exp' => time() + 86400
      ];

      // Only include society_id in token if it exists
      if ($user['society_id'] !== null) {
        $tokenData['society_id'] = $user['society_id'];
      }

      $token = jwt_encode($tokenData);

      Response::success("Login successful", [
        'user' => [
          'id' => $user['id'],
          'app_user_id' => $user['app_user_id'],
          'name' => $user['name'],
          'phone' => $user['phone'],
          'role' => $user['role'],
          'society_id' => $user['society_id']
        ],
        'token' => $token
      ]);

    } catch (Exception $e) {
      error_log("Login error: " . $e->getMessage());
      Response::error("Login failed: " . $e->getMessage(), 500);
    }
  }

  public function refreshToken()
  {
    try {
      $headers = apache_request_headers();
      $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

      if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
        Response::unauthorized("Authorization header missing or invalid");
      }

      $token = substr($authHeader, 7);

      try {
        $payload = jwt_decode_token($token);

        // Generate new token with extended expiration
        $tokenData = [
          'uid' => $payload['uid'],
          'role' => $payload['role'],
          'exp' => time() + 86400
        ];

        // Only include society_id in token if it exists
        if (isset($payload['society_id'])) {
          $tokenData['society_id'] = $payload['society_id'];
        }

        $newToken = jwt_encode($tokenData);

        Response::success("Token refreshed", [
          'token' => $newToken
        ]);
      } catch (Exception $e) {
        Response::unauthorized("Invalid token: " . $e->getMessage());
      }
    } catch (Exception $e) {
      error_log("Token refresh error: " . $e->getMessage());
      Response::error("Token refresh failed: " . $e->getMessage(), 500);
    }
  }

  public function changePassword()
  {
    try {
      $headers = apache_request_headers();
      $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

      if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
        Response::unauthorized("Authorization header missing or invalid");
      }

      $token = substr($authHeader, 7);
      $payload = jwt_decode_token($token);

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      if (empty($data['current_password']) || empty($data['new_password'])) {
        Response::error("Current password and new password are required");
      }

      if (strlen($data['new_password']) < 8) {
        Response::error("New password must be at least 8 characters long");
      }

      // Validate password strength
      if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/', $data['new_password'])) {
        Response::error("New password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character");
      }

      // Get current user
      $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
      $stmt->execute([$payload['uid']]);
      $user = $stmt->fetch();

      if (!$user || !password_verify($data['current_password'], $user['password'])) {
        Response::error("Current password is incorrect", 400);
      }

      // Update password
      $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
      $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
      $stmt->execute([$hashedPassword, $payload['uid']]);

      Response::success("Password changed successfully", );

    } catch (Exception $e) {
      error_log("Password change error: " . $e->getMessage());
      Response::error("Password change failed: " . $e->getMessage(), 500);
    }
  }

  public function forgotPassword()
  {
    try {
      $data = json_decode(file_get_contents("php://input"), true);

      if (empty($data['phone'])) {
        Response::error("Phone number is required");
      }

      // Validate phone number format
      if (!empty($data['phone']) && !preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
        Response::error("Phone number must be 10-15 digits");
      }

      // Check if user exists
      $stmt = $this->db->prepare("SELECT id, name, phone FROM users WHERE phone = ?");
      $stmt->execute([$data['phone']]);
      $user = $stmt->fetch();

      if (!$user) {
        // We don't reveal if user exists or not for security reasons
        Response::success("If account exists, password reset instructions have been sent");
      }

      // In a real implementation, we would send an OTP or password reset link
      // For now, we'll just simulate the response
      Response::success("Password reset instructions sent to your phone");

    } catch (Exception $e) {
      error_log("Forgot password error: " . $e->getMessage());
      Response::error("Password reset request failed: " . $e->getMessage(), 500);
    }
  }

  public function logout()
  {
    // For JWT, logout is typically handled client-side by deleting the token
    // Server-side we could implement a token blacklist, but that's not implemented here
    Response::success("Logged out successfully");
  }

  public function updateUserStatus($userId)
  {
    try {
      // Only admins can update user status
      $user = $this->auth->authorizeAny(['admin', 'super_admin']);

      $data = json_decode(file_get_contents("php://input"), true);

      // Validation
      if (empty($data['status'])) {
        Response::error("Status is required");
      }

      // Validate status against allowed ENUM values
      $allowedStatuses = ['active', 'inactive', 'blocked', 'pending_verification'];
      if (!in_array($data['status'], $allowedStatuses)) {
        Response::error("Invalid status. Allowed values: " . implode(', ', $allowedStatuses));
      }

      // Check if user exists and belongs to the same society (unless super_admin)
      $stmt = $this->db->prepare("SELECT id, society_id FROM users WHERE id = ?");
      $stmt->execute([$userId]);
      $targetUser = $stmt->fetch();

      if (!$targetUser) {
        Response::notFound("User not found");
      }

      // Verify permissions
      if ($user['role'] === 'admin' && $user['society_id'] != $targetUser['society_id']) {
        Response::forbidden("You can only update users in your society");
      }

      // Update user status
      $updated = $this->update('users', [
        'status' => $data['status']
      ], 'id = :id', ['id' => $userId]);

      if ($updated === 0) {
        Response::error("Failed to update user status", 500);
      }

      Response::success("User status updated successfully", [
        'status' => $data['status']
      ]);

    } catch (Exception $e) {
      error_log("Update user status error: " . $e->getMessage());
      Response::error("Failed to update user status: " . $e->getMessage(), 500);
    }
  }
}