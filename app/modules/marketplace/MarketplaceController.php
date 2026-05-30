<?php
require_once __DIR__ . '/../../core/BaseController.php';

class MarketplaceController extends BaseController
{

    public function getCategories()
    {
        try {
            $user = $this->auth->authenticate();
            $stmt = $this->db->query("SELECT * FROM marketplace_categories WHERE is_active = 1");
            $categories = $stmt->fetchAll();
            Response::success("Categories retrieved successfully", $categories);
        } catch (Exception $e) {
            error_log("Get categories error: " . $e->getMessage());
            Response::error("Failed to retrieve categories: " . $e->getMessage(), 500);
        }
    }

    public function getProducts()
    {
        try {
            $user = $this->auth->authenticate();
            $societyId = $user['society_id'];

            $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;
            $search = isset($_GET['search']) ? $_GET['search'] : null;

            $sql = "
            SELECT p.*, u.name as seller_name, c.name as category_name
            FROM products p
            JOIN users u ON p.seller_id = u.id
            LEFT JOIN marketplace_categories c ON p.category_id = c.id
            WHERE p.society_id = ? AND p.is_active = 1
        ";
            $params = [$societyId];

            if ($categoryId) {
                $sql .= " AND p.category_id = ?";
                $params[] = $categoryId;
            }

            if ($search) {
                $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $sql .= " ORDER BY p.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();

            Response::success("Products retrieved successfully", $products);

        } catch (Exception $e) {
            error_log("Get products error: " . $e->getMessage());
            Response::error("Failed to retrieve products: " . $e->getMessage(), 500);
        }
    }

    public function addProduct()
    {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['name', 'price', 'category_id']);
            if (!empty($errors))
                Response::validationError($errors);

            $productId = $this->insert('products', [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'category_id' => $data['category_id'],
                'price' => $data['price'],
                'seller_id' => $user['uid'],
                'society_id' => $user['society_id'],
                'image_urls' => isset($data['image_urls']) ? json_encode($data['image_urls']) : null,
                'is_active' => 1
            ]);

            Response::success("Product added successfully", ['product_id' => $productId], 201);

        } catch (Exception $e) {
            error_log("Add product error: " . $e->getMessage());
            Response::error("Failed to add product: " . $e->getMessage(), 500);
        }
    }

    public function createOrder()
    {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true);

            // Simple order creation with single product for now or multiple items
            // Assuming structure: items: [{product_id, quantity}]

            if (empty($data['items']) || !is_array($data['items'])) {
                Response::error("Items are required");
            }

            $totalAmount = 0;
            $orderItems = [];

            // Validate items and calculate total
            foreach ($data['items'] as $item) {
                $stmt = $this->db->prepare("SELECT price, seller_id FROM products WHERE id = ?");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch();

                if (!$product)
                    Response::error("Product not found: " . $item['product_id']);

                $quantity = $item['quantity'] ?? 1;
                $totalPrice = $product['price'] * $quantity;
                $totalAmount += $totalPrice;

                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $product['price'],
                    'total_price' => $totalPrice
                ];
            }

            // Create Order
            $orderNumber = 'ORD-' . time() . '-' . rand(1000, 9999);
            $orderId = $this->insert('orders', [
                'order_number' => $orderNumber,
                'buyer_id' => $user['uid'],
                'society_id' => $user['society_id'],
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'shipping_address' => $data['address'] ?? ''
            ]);

            // Create Order Items
            foreach ($orderItems as $item) {
                $this->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price']
                ]);
            }

            Response::success("Order placed successfully", ['order_id' => $orderId, 'order_number' => $orderNumber], 201);

        } catch (Exception $e) {
            error_log("Create order error: " . $e->getMessage());
            Response::error("Failed to create order: " . $e->getMessage(), 500);
        }
    }

    public function getMyOrders()
    {
        try {
            $user = $this->auth->authenticate();

            $stmt = $this->db->prepare("
              SELECT * FROM orders WHERE buyer_id = ? ORDER BY created_at DESC
          ");
            $stmt->execute([$user['uid']]);
            $orders = $stmt->fetchAll();

            // Fetch items for each order
            foreach ($orders as &$order) {
                $stmt = $this->db->prepare("
                  SELECT oi.*, p.name as product_name 
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?
              ");
                $stmt->execute([$order['id']]);
                $order['items'] = $stmt->fetchAll();
            }

            Response::success("Orders retrieved successfully", $orders);

        } catch (Exception $e) {
            error_log("Get orders error: " . $e->getMessage());
            Response::error("Failed to retrieve orders: " . $e->getMessage(), 500);
        }
    }
}
