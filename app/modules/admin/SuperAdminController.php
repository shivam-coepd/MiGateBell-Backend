<?php
require_once __DIR__ . '/../../core/BaseController.php';

class SuperAdminController extends BaseController
{
    public function getStats()
    {
        try {
            $user = $this->auth->authorize('super_admin');

            $stats = [];
            
            // Total Societies
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM societies");
            $stats['totalSocieties'] = $stmt->fetch()['count'];

            // Status counts (assuming status column was added)
            // If the column doesn't exist, these will default to 0
            try {
                $stmt = $this->db->query("SELECT COUNT(*) as count FROM societies WHERE status COLLATE utf8mb4_unicode_ci = 'approved'");
                $stats['approved'] = $stmt->fetch()['count'];
                
                $stmt = $this->db->query("SELECT COUNT(*) as count FROM societies WHERE status COLLATE utf8mb4_unicode_ci = 'pending'");
                $stats['pending'] = $stmt->fetch()['count'];

                $stmt = $this->db->query("SELECT COUNT(*) as count FROM societies WHERE status COLLATE utf8mb4_unicode_ci = 'verified'");
                $stats['verified'] = $stmt->fetch()['count'];
            } catch (Exception $e) {
                $stats['approved'] = 0;
                $stats['pending'] = 0;
                $stats['verified'] = 0;
            }

            // Registrations
            try {
                $stmt = $this->db->query("SELECT COUNT(*) as count FROM society_registrations WHERE status COLLATE utf8mb4_unicode_ci = 'new'");
                $stats['newLeads'] = $stmt->fetch()['count'];

                $stmt = $this->db->query("SELECT COUNT(*) as count FROM society_registrations WHERE status COLLATE utf8mb4_unicode_ci = 'under_review'");
                $stats['underReview'] = $stmt->fetch()['count'];
            } catch (Exception $e) {
                $stats['newLeads'] = 0;
                $stats['underReview'] = 0;
            }

            // Admins & Residents
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $stats['totalAdmins'] = $stmt->fetch()['count'];

            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = 'resident'");
            $stats['totalResidents'] = $stmt->fetch()['count'];

            // Trend
            $stats['trend'] = [];
            for ($i = 5; $i >= 0; $i--) {
                $d = new DateTime("-$i months");
                $month = $d->format('Y-m');
                $display = $d->format('M y');
                
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM societies WHERE DATE_FORMAT(created_at, '%Y-%m') COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci");
                $stmt->execute([$month]);
                
                $stats['trend'][] = [
                    'month' => $display,
                    'count' => $stmt->fetch()['count']
                ];
            }

            // Plan Dist
            $stats['planDist'] = [];
            $plans = ['starter', 'professional', 'enterprise'];
            foreach ($plans as $plan) {
                try {
                    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM societies WHERE plan = ?");
                    $stmt->execute([$plan]);
                    $stats['planDist'][] = [
                        'plan' => $plan,
                        'count' => $stmt->fetch()['count']
                    ];
                } catch (Exception $e) {
                    $stats['planDist'][] = ['plan' => $plan, 'count' => 0];
                }
            }

            Response::success("Stats retrieved", $stats);

        } catch (Exception $e) {
            error_log("Get stats error: " . $e->getMessage());
            Response::error("Failed to retrieve stats: " . $e->getMessage(), 500);
        }
    }

    public function getRegistrations()
    {
        try {
            $this->auth->authorize('super_admin');
            $query = "SELECT id, society_name as societyName, address, city, state, pincode, towers, total_flats as totalFlats, contact_name as contactName, contact_email as contactEmail, contact_phone as contactPhone, gst, pan, message, status, created_at as createdAt FROM society_registrations ORDER BY created_at DESC";
            $stmt = $this->db->query($query);
            $regs = $stmt->fetchAll();
            Response::success("Registrations retrieved", $regs);
        } catch (Exception $e) {
            Response::error("Failed to retrieve registrations: " . $e->getMessage(), 500);
        }
    }

    public function approveRegistrationLead($id)
    {
        try {
            $this->auth->authorize('super_admin');

            // 1. Fetch Lead
            $stmt = $this->db->prepare("SELECT * FROM society_registrations WHERE id = ?");
            $stmt->execute([$id]);
            $lead = $stmt->fetch();

            if (!$lead) {
                Response::notFound("Registration lead not found");
            }

            if ($lead['status'] === 'approved') {
                Response::error("This lead has already been approved", 400);
            }

            // 2. Create Society
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $lead['society_name']), 0, 4)) . rand(100, 999);
            
            $societyId = $this->insert('societies', [
                'name' => $lead['society_name'],
                'code' => $code,
                'address' => $lead['address'] ?? '',
                'city' => $lead['city'],
                'state' => $lead['state'] ?? '',
                'country' => 'India',
                'pincode' => $lead['pincode'] ?? '',
                'contact_person' => $lead['contact_name'],
                'contact_phone' => $lead['contact_phone'],
                'contact_email' => $lead['contact_email'],
                'plan' => 'starter',
                'towers' => $lead['towers'] ?? 1,
                'total_flats' => $lead['total_flats'] ?? 0,
                'status' => 'approved'
            ]);

            // 3. Create Admin User
            $password = 'Admin@' . rand(1000, 9999);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $appUserId = AppUserIdHelper::generateUnique($this->db);

            $adminId = $this->insert('users', [
                'app_user_id' => $appUserId,
                'name' => $lead['contact_name'],
                'email' => $lead['contact_email'],
                'phone' => $lead['contact_phone'],
                'password' => $hashedPassword,
                'role' => 'admin',
                'society_id' => $societyId,
                'status' => 'active'
            ]);

            // 4. Link Admin to Society
            $this->update('societies', ['admin_id' => $adminId], 'id = :id', ['id' => $societyId]);

            // 5. Update Lead Status
            $this->update('society_registrations', [
                'status' => 'approved',
                'reviewed_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $id]);

            Response::success("Lead approved and society activated", [
                'society_id' => $societyId,
                'society_name' => $lead['society_name'],
                'code' => $code,
                'admin_email' => $lead['contact_email'],
                'admin_phone' => $lead['contact_phone'],
                'password' => $password
            ]);

        } catch (Exception $e) {
            error_log("Approve lead error: " . $e->getMessage());
            Response::error("Failed to approve lead: " . $e->getMessage(), 500);
        }
    }

    public function createRegistration()
    {
        try {
            // Public endpoint
            $data = json_decode(file_get_contents("php://input"), true);
            
            $errors = $this->validateRequiredFields($data, ['societyName', 'city', 'contactName', 'contactEmail', 'contactPhone']);
            if (!empty($errors)) {
                Response::validationError($errors);
            }
            
            $id = $this->insert('society_registrations', [
                'society_name' => $data['societyName'],
                'address' => $data['address'] ?? '',
                'city' => $data['city'],
                'state' => $data['state'] ?? '',
                'pincode' => $data['pincode'] ?? '',
                'towers' => $data['towers'] ?? 1,
                'total_flats' => $data['totalFlats'] ?? 10,
                'contact_name' => $data['contactName'],
                'contact_email' => $data['contactEmail'],
                'contact_phone' => $data['contactPhone'],
                'gst' => $data['gst'] ?? null,
                'pan' => $data['pan'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => 'new'
            ]);
            
            Response::success("Registration submitted", ['id' => $id], 201);
        } catch (Exception $e) {
            Response::error("Failed to create registration: " . $e->getMessage(), 500);
        }
    }

    public function updateRegistration($id)
    {
        try {
            $user = $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);
            
            $updateData = [];
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['reviewedBy'])) $updateData['reviewed_by'] = $data['reviewedBy'];
            if (isset($data['rejectionReason'])) $updateData['rejection_reason'] = $data['rejectionReason'];
            
            if (!empty($updateData)) {
                $updateData['reviewed_at'] = date('Y-m-d H:i:s');
                $this->update('society_registrations', $updateData, 'id = :id', ['id' => $id]);
            }
            
            Response::success("Registration updated");
        } catch (Exception $e) {
            Response::error("Failed to update registration: " . $e->getMessage(), 500);
        }
    }
    
    public function createSociety()
    {
        try {
            $user = $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name', 'city', 'contactEmail']);
            if (!empty($errors)) {
                Response::validationError($errors);
            }

            // Check uniqueness
            $stmt = $this->db->prepare("SELECT id FROM societies WHERE name = ?");
            $stmt->execute([$data['name']]);
            if ($stmt->fetch()) {
                Response::error("A society with this name already exists", 409);
            }

            $code = strtoupper(substr($data['name'], 0, 4)) . rand(100, 999);

            $societyId = $this->insert('societies', [
                'name' => $data['name'],
                'code' => $code,
                'address' => $data['address'] ?? '',
                'city' => $data['city'],
                'state' => $data['state'] ?? '',
                'country' => $data['country'] ?? 'India',
                'pincode' => $data['pincode'] ?? '',
                'contact_person' => $data['contactName'] ?? '',
                'contact_phone' => $data['contactPhone'] ?? '',
                'contact_email' => $data['contactEmail'],
                'plan' => $data['plan'] ?? 'starter',
                'towers' => $data['towers'] ?? 1,
                'total_flats' => $data['totalFlats'] ?? 0,
                'status' => 'verified' // Super admins create verified societies
            ]);

            // Update registration status if provided
            if (!empty($data['registrationId'])) {
                try {
                    $this->update('society_registrations', [
                        'status' => 'approved',
                        'reviewed_at' => date('Y-m-d H:i:s')
                    ], 'id = :id', ['id' => $data['registrationId']]);
                } catch (Exception $e) {
                    error_log("Failed to update registration status: " . $e->getMessage());
                }
            }

            $newSoc = [
                'id' => $societyId,
                'name' => $data['name'],
                'code' => $code,
                'city' => $data['city'],
                'plan' => $data['plan'] ?? 'starter',
                'status' => 'verified',
                'inviteLink' => "https://mygatebell.com/join/" . $code
            ];

            Response::success("Society created successfully", $newSoc, 201);

        } catch (Exception $e) {
            error_log("Create society error: " . $e->getMessage());
            Response::error("Failed to create society: " . $e->getMessage(), 500);
        }
    }

    public function getSocieties()
    {
        try {
            $user = $this->auth->authorize('super_admin');
            
            $query = "SELECT s.id, s.name, s.address, s.city, s.state, s.country, s.pincode, 
                             s.contact_person as contactName, s.contact_phone as contactPhone, s.contact_email as contactEmail, 
                             s.plan, s.created_at as createdAt";
            
            // Check if status, code, total_flats, towers, admin_id columns exist and select them
            try {
                $stmt = $this->db->query("SELECT status, code, total_flats, towers, admin_id FROM societies LIMIT 1");
                $query = "SELECT s.id, s.name, s.address, s.city, s.state, s.country, s.pincode, 
                                 s.contact_person as contactName, s.contact_phone as contactPhone, s.contact_email as contactEmail, 
                                 s.plan, s.created_at as createdAt, 
                                 s.status, s.code, s.total_flats as totalFlats, s.towers, s.admin_id as adminId
                          FROM societies s ORDER BY s.created_at DESC";
            } catch (Exception $e) {
                // columns don't exist
                $query .= " FROM societies s ORDER BY s.created_at DESC";
            }
            
            $stmt = $this->db->query($query);
            $societies = $stmt->fetchAll();
            
            // Add defaults for missing fields if not in DB
            foreach ($societies as &$soc) {
                if (!isset($soc['status'])) $soc['status'] = 'approved';
                if (!isset($soc['code'])) $soc['code'] = strtoupper(substr($soc['name'], 0, 4)) . rand(100,999);
                if (!isset($soc['totalFlats'])) $soc['totalFlats'] = 0;
                if (!isset($soc['towers'])) $soc['towers'] = 1;
                if (!isset($soc['adminId'])) $soc['adminId'] = null;
            }
            
            Response::success("Societies retrieved", $societies);
        } catch (Exception $e) {
            Response::error("Failed to retrieve societies: " . $e->getMessage(), 500);
        }
    }

    public function getSocietyById($id)
    {
        try {
            $user = $this->auth->authorize('super_admin');
            
            $query = "SELECT s.id, s.name, s.address, s.city, s.state, s.country, s.pincode, 
                             s.contact_person as contactName, s.contact_phone as contactPhone, s.contact_email as contactEmail, 
                             s.plan, s.created_at as createdAt";
            
            try {
                $stmt = $this->db->query("SELECT status, code, total_flats, towers, admin_id FROM societies LIMIT 1");
                $query = "SELECT s.id, s.name, s.address, s.city, s.state, s.country, s.pincode, 
                                 s.contact_person as contactName, s.contact_phone as contactPhone, s.contact_email as contactEmail, 
                                 s.plan, s.created_at as createdAt, 
                                 s.status, s.code, s.total_flats as totalFlats, s.towers, s.admin_id as adminId
                          FROM societies s WHERE s.id = ?";
            } catch (Exception $e) {
                $query .= " FROM societies s WHERE s.id = ?";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $soc = $stmt->fetch();
            
            if (!$soc) {
                Response::notFound("Society not found");
            }
            
            if (!isset($soc['status'])) $soc['status'] = 'approved';
            if (!isset($soc['code'])) $soc['code'] = strtoupper(substr($soc['name'], 0, 4)) . rand(100,999);
            if (!isset($soc['totalFlats'])) $soc['totalFlats'] = 0;
            if (!isset($soc['towers'])) $soc['towers'] = 1;
            if (!isset($soc['adminId'])) $soc['adminId'] = null;
            
            // Get user count
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE society_id = ?");
            $stmt->execute([$id]);
            $soc['userCount'] = $stmt->fetch()['count'];
            
            // Get admin
            $soc['admin'] = null;
            if ($soc['adminId']) {
                $stmt = $this->db->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
                $stmt->execute([$soc['adminId']]);
                $soc['admin'] = $stmt->fetch() ?: null;
            } else {
                // Try to find the first admin if adminId is not set
                $stmt = $this->db->prepare("SELECT id, name, email, phone, role FROM users WHERE society_id = ? AND role = 'admin' LIMIT 1");
                $stmt->execute([$id]);
                $soc['admin'] = $stmt->fetch() ?: null;
                if ($soc['admin']) {
                    $soc['adminId'] = $soc['admin']['id'];
                }
            }
            
            Response::success("Society retrieved", $soc);
        } catch (Exception $e) {
            Response::error("Failed to retrieve society: " . $e->getMessage(), 500);
        }
    }

    public function createSocietyAdmin($societyId)
    {
        try {
            $user = $this->auth->authorize('super_admin');
            $data = json_decode(file_get_contents("php://input"), true);
            
            $errors = $this->validateRequiredFields($data, ['name', 'email', 'phone', 'password']);
            if (!empty($errors)) Response::validationError($errors);
            
            // Check email
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$data['email'], $data['phone']]);
            if ($stmt->fetch()) Response::error("User with this email or phone already exists", 409);
            
            $userId = $this->insert('users', [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => 'admin',
                'society_id' => $societyId,
                'status' => 'active'
            ]);
            
            try {
                // Update society admin_id and status
                $this->update('societies', ['admin_id' => $userId, 'status' => 'approved'], 'id = :id', ['id' => $societyId]);
            } catch (Exception $e) {}
            
            Response::success("Admin created", ['user_id' => $userId], 201);
            
        } catch (Exception $e) {
            Response::error("Failed to create admin: " . $e->getMessage(), 500);
        }
    }
    
    public function approveSociety($societyId)
    {
        try {
            $user = $this->auth->authorize('super_admin');
            try {
                $this->update('societies', ['status' => 'approved'], 'id = :id', ['id' => $societyId]);
            } catch (Exception $e) {}
            Response::success("Society approved");
        } catch (Exception $e) {
            Response::error("Failed to approve society: " . $e->getMessage(), 500);
        }
    }

    public function suspendSociety($societyId)
    {
        try {
            $user = $this->auth->authorize('super_admin');
            try {
                $this->update('societies', ['status' => 'suspended'], 'id = :id', ['id' => $societyId]);
            } catch (Exception $e) {}
            Response::success("Society suspended");
        } catch (Exception $e) {
            Response::error("Failed to suspend society: " . $e->getMessage(), 500);
        }
    }

    public function toggleAdmin($id)
    {
        try {
            $user = $this->auth->authorize('super_admin');
            
            $stmt = $this->db->prepare("SELECT id, status FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$id]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                Response::notFound("Admin not found");
            }
            
            $newStatus = ($admin['status'] === 'active') ? 'inactive' : 'active';
            $this->update('users', ['status' => $newStatus], 'id = :id', ['id' => $id]);
            
            Response::success("Admin status updated", ['id' => $id, 'status' => $newStatus, 'isActive' => ($newStatus === 'active')]);
        } catch (Exception $e) {
            Response::error("Failed to toggle admin: " . $e->getMessage(), 500);
        }
    }

    public function getAdmins()
    {
        try {
            $user = $this->auth->authorize('super_admin');
            
            $query = "SELECT u.id, u.name, u.email, u.phone, u.role, u.society_id as societyId, u.status, u.created_at as createdAt 
                      FROM users u WHERE u.role = 'admin' ORDER BY u.created_at DESC";
            
            $stmt = $this->db->query($query);
            $admins = $stmt->fetchAll();
            
            foreach ($admins as &$admin) {
                $admin['isActive'] = ($admin['status'] === 'active');
                
                // Get society info
                if ($admin['societyId']) {
                    try {
                        $stmt = $this->db->prepare("SELECT id, name, code, status FROM societies WHERE id = ?");
                        $stmt->execute([$admin['societyId']]);
                        $admin['society'] = $stmt->fetch() ?: null;
                    } catch (Exception $e) {
                        $stmt = $this->db->prepare("SELECT id, name FROM societies WHERE id = ?");
                        $stmt->execute([$admin['societyId']]);
                        $admin['society'] = $stmt->fetch() ?: null;
                        if ($admin['society']) {
                            $admin['society']['code'] = 'N/A';
                            $admin['society']['status'] = 'approved';
                        }
                    }
                } else {
                    $admin['society'] = null;
                }
            }
            
            Response::success("Admins retrieved", $admins);
        } catch (Exception $e) {
            Response::error("Failed to retrieve admins: " . $e->getMessage(), 500);
        }
    }

    public function deleteSociety($id)
    {
        try {
            $user = $this->auth->authorize('super_admin');
            
            // Check if society exists
            $stmt = $this->db->prepare("SELECT id FROM societies WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::notFound("Society not found");
            }
            
            $this->delete('societies', 'id = ?', [$id]);
            Response::success("Society deleted successfully");
        } catch (Exception $e) {
            Response::error("Failed to delete society: " . $e->getMessage(), 500);
        }
    }
}
