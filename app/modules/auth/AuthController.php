<?php
require_once __DIR__ . '/../../helpers/jwt_helper.php';
require_once __DIR__ . '/../../helpers/app_userID_helper.php';
require_once __DIR__ . '/../../core/BaseController.php';

class AuthController extends BaseController
{

  // Strip Postman line comments before json_decode.
  private function sanitizeJsonString($raw)
  {
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $lines = preg_split('/\R/', $raw);
    $out = [];
    foreach ($lines as $line) {
      $trimmed = ltrim($line);
      if ($trimmed === '' || strpos($trimmed, '//') === 0) {
        continue;
      }
      $out[] = $line;
    }
    $json = implode("\n", $out);
    $json = preg_replace('#/\*.*?\*/#s', '', $json);
    $json = preg_replace('/,\s*([\]}])/', '$1', $json);
    return trim($json);
  }

  // Parse JSON body; tolerates Postman line-comment lines above the JSON object.
  private function parseJsonBody()
  {
    $raw = file_get_contents("php://input");
    if ($raw === false || trim($raw) === '') {
      Response::error(
        "Request body is empty. In Postman: Body → raw → JSON, header Content-Type: application/json.",
        400
      );
    }

    $attempts = [trim($raw), $this->sanitizeJsonString($raw)];
    $data = null;
    $lastError = '';

    foreach (array_unique($attempts) as $candidate) {
      if ($candidate === '') {
        continue;
      }
      $decoded = json_decode($candidate, true);
      if (is_array($decoded)) {
        $data = $decoded;
        break;
      }
      $lastError = json_last_error_msg();
    }

    if (!is_array($data)) {
      Response::error(
        'Invalid JSON body: ' . $lastError
          . '. Use Body → raw → JSON with a single object (no // comments). Example: {"name":"Test","phone":"9012345678","password":"Pass@123","society_id":1,"role":"resident"}',
        400
      );
    }

    return $data;
  }

  private function normalizePhone($phone)
  {
    $digits = preg_replace('/\D/', '', (string) $phone);
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
      $digits = substr($digits, 2);
    }
    return $digits;
  }

  private function resolveRegistrationStatus($role, $requestedStatus)
  {
    $allowed = ['active', 'inactive', 'blocked', 'pending_verification'];
    if (!empty($requestedStatus) && in_array($requestedStatus, $allowed, true)) {
      return $requestedStatus;
    }
    if (in_array($role, ['super_admin', 'admin', 'guard', 'staff'], true)) {
      return 'active';
    }
    return 'pending_verification';
  }

  // public function register()
  // {
  //   try {
  //     $data = $this->parseJsonBody();

  //     // Validation
  //     $errors = [];
  //     if (empty($data['name']))
  //       $errors[] = 'Name is required';
  //     if (empty($data['phone']))
  //       $errors[] = 'Phone is required';
  //     if (empty($data['password']))
  //       $errors[] = 'Password is required';

  //     // Only require society_id for non-super_admin roles
  //     if (empty($data['society_id']) && (!isset($data['role']) || $data['role'] !== 'super_admin')) {
  //       $errors[] = 'Society ID is required for this role';
  //     }

  //     if (!empty($errors)) {
  //       Response::validationError($errors);
  //     }

  //     $data['phone'] = $this->normalizePhone($data['phone']);

  //     // Email optional in Postman — generate unique placeholder from phone if omitted
  //     if (empty($data['email'])) {
  //       $data['email'] = $data['phone'] . '@users.mygatebell.local';
  //     } else {
  //       $data['email'] = trim($data['email']);
  //     }

  //     // Validate phone number format
  //     if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
  //       Response::error("Phone number must be 10-15 digits (no spaces or country code prefix)");
  //     }

  //     // Validate password strength
  //     if (!empty($data['password']) && strlen($data['password']) < 8) {
  //       Response::error("Password must be at least 8 characters long");
  //     }

  //     if (!empty($data['password']) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/', $data['password'])) {
  //       Response::error("Password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character");
  //     }

  //     // Validate name length
  //     if (!empty($data['name']) && strlen($data['name']) > 100) {
  //       Response::error("Name must be less than 100 characters");
  //     }

  //     // Validate email format
  //     if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
  //       Response::error("Invalid email format");
  //     }

  //     // Check if email already exists
  //     if (!empty($data['email'])) {
  //       $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
  //       $stmt->execute([$data['email']]);
  //       if ($stmt->fetch()) {
  //         Response::error("User with this email already exists", 409);
  //       }
  //     }

  //     // Check if society exists when provided
  //     if (!empty($data['society_id'])) {
  //       $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
  //       $stmt->execute([$data['society_id']]);
  //       if (!$stmt->fetch()) {
  //         Response::error("Society not found", 404);
  //       }
  //     }

  //     // Check if user already exists
  //     $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
  //     $stmt->execute([$data['phone']]);
  //     if ($stmt->fetch()) {
  //       Response::error("User with this phone already exists", 409);
  //     }

  //     // Hash password
  //     $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

  //     // Insert user (society_id can be NULL for super_admin)
  //     $societyId = isset($data['society_id']) ? (int) $data['society_id'] : null;
  //     if ($societyId === 0) {
  //       $societyId = null;
  //     }
  //     $role = $data['role'] ?? 'resident';

  //     // Validate role against allowed ENUM values
  //     $allowedRoles = ['admin', 'resident', 'guard', 'staff', 'super_admin'];
  //     if (!in_array($role, $allowedRoles, true)) {
  //       Response::error("Invalid role. Allowed values: " . implode(', ', $allowedRoles));
  //     }

  //     $appUserId = AppUserIdHelper::generateUnique($this->db);

  //     $fields = [
  //       'app_user_id' => $appUserId,
  //       'name' => trim($data['name']),
  //       'email' => $data['email'],
  //       'phone' => $data['phone'],
  //       'password' => $hashedPassword,
  //       'role' => $role,
  //       'society_id' => $societyId,
  //       'status' => $this->resolveRegistrationStatus($role, $data['status'] ?? null),
  //     ];

  //     $columns = implode(', ', array_keys($fields));
  //     $placeholders = implode(', ', array_fill(0, count($fields), '?')); // Create ?, ?, ... string

  //     $stmt = $this->db->prepare("INSERT INTO users ($columns) VALUES ($placeholders)");
  //     $stmt->execute(array_values($fields));

  //     $userId = $this->db->lastInsertId();

  //     // Generate token
  //     $tokenData = [
  //       'uid' => $userId,
  //       'role' => $role,
  //       'exp' => time() + 86400
  //     ];

  //     // Only include society_id in token if it exists
  //     if ($societyId !== null) {
  //       $tokenData['society_id'] = $societyId;
  //     }

  //     $token = jwt_encode($tokenData);

  //     // Fetch complete user profile for response
  //     $userProfile = $this->getCompleteUserProfile($userId);

  //     Response::success("Registration successful", [
  //       'user' => $userProfile,
  //       'token' => $token
  //     ], 201);

  //   } catch (Exception $e) {
  //     error_log("Registration error: " . $e->getMessage());
  //     Response::error("Registration failed: " . $e->getMessage(), 500);
  //   }
  // }
  public function register()
  {
    try {
      $data = $this->parseJsonBody();

      // Validation
      $errors = [];
      if (empty($data['name']))
        $errors[] = 'Name is required';
      if (empty($data['phone']))
        $errors[] = 'Phone is required';
      if (empty($data['password']))
        $errors[] = 'Password is required';
      if (empty($data['email']))
        $errors[] = 'Email is required';

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

      // Validate email format
      if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        Response::error("Invalid email format");
      }

      // Check if email already exists
      if (!empty($data['email'])) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
          Response::error("User with this email already exists", 409);
        }
      }

      // Check if society exists when provided
      if (!empty($data['society_id'])) {
        $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
        $stmt->execute([$data['society_id']]);
        if (!$stmt->fetch()) {
          Response::error("Society not found", 404);
        }
      }

      // Normalize phone number before storing (consistent with login)
      $data['phone'] = $this->normalizePhone($data['phone']);

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
        'email' => $data['email'],
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

      $responseData = [
        'user_id' => $userId,
        'token' => $token
      ];
      if (isset($hasAppUserId) && $hasAppUserId && isset($appUserId)) {
        $responseData['app_user_id'] = $appUserId;
      }
      Response::success("Registration successful", $responseData, 201);
    } catch (Exception $e) {
      error_log("Registration error: " . $e->getMessage());
      Response::error("Registration failed: " . $e->getMessage(), 500);
    }
  }

  public function login()
  {
    try {
      $data = $this->parseJsonBody();

      // Validation
      if (empty($data['phone']) || empty($data['password'])) {
        Response::error("Phone and password are required");
      }

      $phone = $this->normalizePhone($data['phone']);

      // Get user - handle missing app_user_id column gracefully
      try {
        $stmt = $this->db->prepare(
          "SELECT id, app_user_id, name, phone, password, role, society_id 
       FROM users WHERE phone = ?"
        );
      } catch (Exception $e) {
        // Fallback if app_user_id column doesn't exist
        $stmt = $this->db->prepare(
          "SELECT id, name, phone, password, role, society_id 
       FROM users WHERE phone = ?"
        );
      }

      $stmt->execute([$phone]);
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

      // Fetch complete user profile for response
      $userProfile = $this->getCompleteUserProfile($user['id']);

      Response::success("Login successful", [
        'user' => $userProfile,
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

      Response::success("Password changed successfully",);
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

  /**
   * Get complete user profile details
   * @param int $userId
   * @return array
   */
  private function getCompleteUserProfile($userId)
  {
    // Fetch basic user details - handle missing app_user_id column gracefully
    try {
      $stmt = $this->db->prepare("
        SELECT id, app_user_id, name, email, phone, role, society_id, profile_image, 
               cover_image_url, resident_type, bio, profession, hometown, status, created_at, updated_at
        FROM users 
        WHERE id = ?
      ");
    } catch (Exception $e) {
      // Fallback if app_user_id column doesn't exist
      $stmt = $this->db->prepare("
        SELECT id, name, email, phone, role, society_id, profile_image, 
               cover_image_url, resident_type, bio, profession, hometown, status, created_at, updated_at
        FROM users 
        WHERE id = ?
      ");
    }
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    if (!$profile) {
      return [];
    }

    // Fetch society details if applicable
    if ($profile['society_id']) {
      $stmt = $this->db->prepare("SELECT id, name, address, city, state, pincode FROM societies WHERE id = ?");
      $stmt->execute([$profile['society_id']]);
      $profile['society'] = $stmt->fetch();
    }

    // Fetch role-specific data based on user role
    $role = $profile['role'];

    switch ($role) {
      case 'resident':
        $profile['resident_data'] = $this->getResidentData($userId);
        break;
      case 'guard':
        $profile['guard_data'] = $this->getGuardData($userId);
        break;
      case 'staff':
        $profile['staff_data'] = $this->getStaffData($userId);
        break;
    }

    return $profile;
  }

  /**
   * Get resident-specific data
   * @param int $userId
   * @return array
   */
  private function getResidentData($userId)
  {
    $data = [];

    // Family Members
    $stmt = $this->db->prepare("
      SELECT fm.id, fm.name, fm.relation, fm.phone, fm.image_url, fm.is_active, 
             u.name AS resident_name, u.email AS resident_email
      FROM family_members fm
      LEFT JOIN users u ON fm.resident_id = u.id
      WHERE fm.resident_id = ?
    ");
    $stmt->execute([$userId]);
    $data['family_members'] = $stmt->fetchAll();

    // Flats
    $stmt = $this->db->prepare("
      SELECT f.id, f.flat_number, f.floor_number, f.area_sqft, f.is_occupied, 
             b.name as building_name
      FROM flats f
      LEFT JOIN buildings b ON f.building_id = b.id
      WHERE f.owner_id = ? OR f.tenant_id = ?
    ");
    $stmt->execute([$userId, $userId]);
    $data['flats'] = $stmt->fetchAll();

    // Vehicles
    $stmt = $this->db->prepare("
      SELECT v.*, vt.name as type_name
      FROM vehicles v
      LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
      WHERE v.resident_id = ?
    ");
    $stmt->execute([$userId]);
    $data['vehicles'] = $stmt->fetchAll();

    // Pets
    $stmt = $this->db->prepare("
      SELECT p.id, p.resident_id, p.name, p.breed, p.age, p.weight, p.vaccination_status, 
             p.image_url, p.notes, p.society_id, p.is_active, p.created_at,
             pt.id AS pet_type_id, pt.name AS pet_type_name, pt.description AS pet_type_description
      FROM pets p
      LEFT JOIN pet_types pt ON p.pet_type_id = pt.id
      WHERE p.resident_id = ? AND p.is_active = 1
      ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId]);
    $data['pets'] = $stmt->fetchAll();

    return $data;
  }

  /**
   * Get guard-specific data
   * @param int $userId
   * @return array
   */
  private function getGuardData($userId)
  {
    $data = [];

    // Today's visitor statistics
    $stmt = $this->db->prepare("
      SELECT count(*) as today_visitors 
      FROM visitors 
      WHERE guard_id = ? AND DATE(created_at) = CURRENT_DATE
    ");
    $stmt->execute([$userId]);
    $data['today_stats'] = $stmt->fetch();

    return $data;
  }

  /**
   * Get staff-specific data
   * @param int $userId
   * @return array
   */
  private function getStaffData($userId)
  {
    $data = [];

    // Assigned tickets/tasks
    $stmt = $this->db->prepare("
      SELECT count(*) as open_tickets
      FROM tickets
      WHERE assigned_to = ? AND status IN ('open', 'in_progress')
    ");
    $stmt->execute([$userId]);
    $data['tasks'] = $stmt->fetch();

    return $data;
  }
}
