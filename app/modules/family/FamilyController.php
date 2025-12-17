<?php
// Creating family members table using a PHP script since I cannot use SQL tools directly easily or prefer not to mess with raw sql files if possible, 
// but actually I should create a migration file or run a command.
// Users said "Implement nested functionalities". I should Create the table via SQL command if possible or just assume I can use users table.
// However, standard specific table is better.

require_once __DIR__ . '/../../core/BaseController.php';

class FamilyController extends BaseController
{

    // Helper to ensure table exists (Auto-migration for demo purposes)
    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS family_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            resident_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            relation VARCHAR(50),
            phone VARCHAR(15),
            is_active BOOLEAN DEFAULT TRUE,
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (resident_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $this->db->exec($sql);
    }

    public function getFamilyMembers()
    {
        try {
            $user = $this->auth->authenticate();

            // If resident, show own family. If admin, allow viewing by passing resident_id
            $targetResidentId = $user['uid'];

            if ($user['role'] === 'admin' && isset($_GET['resident_id'])) {
                $targetResidentId = $_GET['resident_id'];
                // Verify resident exists and is in same society
                $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND society_id = ?");
                $stmt->execute([$targetResidentId, $user['society_id']]);
                if (!$stmt->fetch())
                    Response::notFound("Resident not found");
            }

            $stmt = $this->db->prepare("SELECT * FROM family_members WHERE resident_id = ? AND is_active = 1");
            $stmt->execute([$targetResidentId]);
            Response::success("Family members retrieved", $stmt->fetchAll());

        } catch (Exception $e) {
            Response::error("Failed to retrieve family members: " . $e->getMessage(), 500);
        }
    }

    public function addFamilyMember()
    {
        try {
            $user = $this->auth->authorizeAny(['resident', 'admin']);
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name', 'relation']);
            if (!empty($errors))
                Response::validationError($errors);

            $targetResidentId = $user['uid'];
            if ($user['role'] === 'admin') {
                if (empty($data['resident_id']))
                    Response::error("Resident ID required for admin");
                $targetResidentId = $data['resident_id'];
                // Verify
                $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND society_id = ?");
                $stmt->execute([$targetResidentId, $user['society_id']]);
                if (!$stmt->fetch())
                    Response::notFound("Resident not found");
            }

            $memberId = $this->insert('family_members', [
                'resident_id' => $targetResidentId,
                'name' => $data['name'],
                'relation' => $data['relation'],
                'phone' => $data['phone'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'is_active' => 1
            ]);

            Response::success("Family member added", ['id' => $memberId], 201);

        } catch (Exception $e) {
            Response::error("Failed to add family member: " . $e->getMessage(), 500);
        }
    }

    public function deleteFamilyMember($id)
    {
        try {
            $user = $this->auth->authenticate();

            // Allow resident to delete their own, or admin to delete any
            $stmt = $this->db->prepare("
                SELECT fm.id, fm.resident_id, u.society_id 
                FROM family_members fm
                JOIN users u ON fm.resident_id = u.id
                WHERE fm.id = ?
            ");
            $stmt->execute([$id]);
            $member = $stmt->fetch();

            if (!$member)
                Response::notFound("Family member not found");

            if ($user['role'] === 'resident' && $member['resident_id'] != $user['uid']) {
                Response::forbidden("Unauthorized");
            }
            if ($user['role'] === 'admin' && $member['society_id'] != $user['society_id']) {
                Response::forbidden("Unauthorized");
            }

            $this->update('family_members', ['is_active' => 0], 'id = :id', ['id' => $id]);
            Response::success("Family member removed");

        } catch (Exception $e) {
            Response::error("Failed to remove family member: " . $e->getMessage(), 500);
        }
    }
}
