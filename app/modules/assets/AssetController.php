<?php
require_once __DIR__ . '/../../core/BaseController.php';

class AssetController extends BaseController
{

    public function getAssetCategories()
    {
        try {
            $user = $this->auth->authenticate();
            $stmt = $this->db->prepare("SELECT * FROM asset_categories WHERE society_id = ? OR society_id IS NULL");
            $stmt->execute([$user['society_id']]);
            Response::success("Asset categories retrieved", $stmt->fetchAll());
        } catch (Exception $e) {
            Response::error("Failed to retrieve categories: " . $e->getMessage(), 500);
        }
    }

    public function getAssets()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin', 'staff']);

            $sql = "
                SELECT a.*, ac.name as category_name, u.name as assigned_user
                FROM assets a
                LEFT JOIN asset_categories ac ON a.category_id = ac.id
                LEFT JOIN users u ON a.assigned_to = u.id
                WHERE a.society_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user['society_id']]);
            Response::success("Assets retrieved", $stmt->fetchAll());

        } catch (Exception $e) {
            Response::error("Failed to retrieve assets: " . $e->getMessage(), 500);
        }
    }

    public function addAsset()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name', 'category_id']);
            if (!empty($errors))
                Response::validationError($errors);

            $assetId = $this->insert('assets', [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'category_id' => $data['category_id'],
                'serial_number' => $data['serial_number'] ?? '',
                'purchase_date' => $data['purchase_date'] ?? null,
                'purchase_cost' => $data['purchase_cost'] ?? 0,
                'current_value' => $data['current_value'] ?? 0,
                'location' => $data['location'] ?? '',
                'status' => 'active',
                'society_id' => $user['society_id']
            ]);

            Response::success("Asset added successfully", ['asset_id' => $assetId], 201);
        } catch (Exception $e) {
            error_log("Add asset error: " . $e->getMessage());
            Response::error("Failed to add asset: " . $e->getMessage(), 500);
        }
    }

    public function getInventory()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin', 'staff']);
            $stmt = $this->db->prepare("SELECT * FROM inventory_items WHERE society_id = ?");
            $stmt->execute([$user['society_id']]);
            Response::success("Inventory retrieved", $stmt->fetchAll());
        } catch (Exception $e) {
            Response::error("Failed to retrieve inventory: " . $e->getMessage(), 500);
        }
    }

    public function addInventoryItem()
    {
        try {
            $user = $this->auth->authorizeAny(['admin', 'super_admin']);
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name', 'quantity_in_stock']);
            if (!empty($errors))
                Response::validationError($errors);

            $itemId = $this->insert('inventory_items', [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'category' => $data['category'] ?? 'General',
                'unit' => $data['unit'] ?? 'pcs',
                'quantity_in_stock' => $data['quantity_in_stock'],
                'reorder_level' => $data['reorder_level'] ?? 0,
                'society_id' => $user['society_id']
            ]);

            Response::success("Inventory item added", ['item_id' => $itemId], 201);
        } catch (Exception $e) {
            Response::error("Failed to add item: " . $e->getMessage(), 500);
        }
    }

    public function getInventoryTransactions()
    {
        // Implement if needed for audit
    }
}
