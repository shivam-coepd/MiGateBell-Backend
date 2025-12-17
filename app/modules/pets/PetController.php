<?php
require_once __DIR__ . '/../../core/BaseController.php';

class PetController extends BaseController
{

    public function getPetTypes()
    {
        try {
            $this->auth->authenticate();
            $stmt = $this->db->query("SELECT * FROM pet_types");
            Response::success("Pet types retrieved", $stmt->fetchAll());
        } catch (Exception $e) {
            Response::error("Failed to retrieve types: " . $e->getMessage(), 500);
        }
    }

    public function addPet()
    {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name', 'pet_type_id']);
            if (!empty($errors))
                Response::validationError($errors);

            $petId = $this->insert('pets', [
                'resident_id' => $user['uid'],
                'pet_type_id' => $data['pet_type_id'],
                'name' => $data['name'],
                'breed' => $data['breed'] ?? '',
                'age' => $data['age'] ?? null,
                'weight' => $data['weight'] ?? null,
                'vaccination_status' => $data['vaccination_status'] ?? 'pending',
                'society_id' => $user['society_id'],
                'image_url' => $data['image_url'] ?? '',
                'notes' => $data['notes'] ?? ''
            ]);

            Response::success("Pet added successfully", ['pet_id' => $petId], 201);

        } catch (Exception $e) {
            error_log("Add pet error: " . $e->getMessage());
            Response::error("Failed to add pet: " . $e->getMessage(), 500);
        }
    }

    public function getPets()
    {
        try {
            $user = $this->auth->authenticate();

            // If admin/guard, showing all pets in society might be useful. 
            // If resident, usually own pets.

            $sql = "
                SELECT p.*, pt.name as type_name, u.name as owner_name, u.phone as owner_phone
                FROM pets p
                LEFT JOIN pet_types pt ON p.pet_type_id = pt.id
                LEFT JOIN users u ON p.resident_id = u.id
                WHERE p.society_id = ? AND p.is_active = 1
            ";
            $params = [$user['society_id']];

            // Filter for resident unless admin/guard
            if ($user['role'] === 'resident') {
                $sql .= " AND p.resident_id = ?";
                $params[] = $user['uid'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            Response::success("Pets retrieved", $stmt->fetchAll());

        } catch (Exception $e) {
            Response::error("Failed to retrieve pets: " . $e->getMessage(), 500);
        }
    }

    public function deletePet($id)
    {
        try {
            $user = $this->auth->authenticate();

            // Check ownership
            $stmt = $this->db->prepare("SELECT id FROM pets WHERE id = ? AND resident_id = ?");
            $stmt->execute([$id, $user['uid']]);
            if (!$stmt->fetch()) {
                Response::notFound("Pet not found or unauthorized");
            }

            $this->update('pets', ['is_active' => 0], 'id = :id', ['id' => $id]);
            Response::success("Pet removed successfully");

        } catch (Exception $e) {
            Response::error("Failed to delete pet: " . $e->getMessage(), 500);
        }
    }
}
