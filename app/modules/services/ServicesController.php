<?php
require_once __DIR__ . '/../../core/BaseController.php';

class ServicesController extends BaseController
{

    public function getCategories()
    {
        try {
            $this->auth->authenticate();
            $stmt = $this->db->query("SELECT * FROM service_categories");
            Response::success("Service categories retrieved", $stmt->fetchAll());
        } catch (Exception $e) {
            Response::error("Failed to retrieve categories: " . $e->getMessage(), 500);
        }
    }

    public function getServices()
    {
        try {
            $user = $this->auth->authenticate();
            $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;

            $sql = "
            SELECT s.*, sc.name as category_name 
            FROM services s
            LEFT JOIN service_categories sc ON s.category_id = sc.id
            WHERE s.society_id = ? AND s.is_active = 1
        ";
            $params = [$user['society_id']];

            if ($categoryId) {
                $sql .= " AND s.category_id = ?";
                $params[] = $categoryId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            Response::success("Services retrieved", $stmt->fetchAll());

        } catch (Exception $e) {
            Response::error("Failed to retrieve services: " . $e->getMessage(), 500);
        }
    }

    public function bookingService()
    {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true);

            $errors = $this->validateRequiredFields($data, ['service_id', 'booking_date', 'booking_time']);
            if (!empty($errors))
                Response::validationError($errors);

            // Verify service exists
            $stmt = $this->db->prepare("SELECT id FROM services WHERE id = ? AND society_id = ?");
            $stmt->execute([$data['service_id'], $user['society_id']]);
            if (!$stmt->fetch())
                Response::notFound("Service not found");

            $bookingId = $this->insert('service_bookings', [
                'service_id' => $data['service_id'],
                'resident_id' => $user['uid'],
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'status' => 'requested',
                'notes' => $data['notes'] ?? ''
            ]);

            Response::success("Service booked successfully", ['booking_id' => $bookingId], 201);

        } catch (Exception $e) {
            error_log("Book service error: " . $e->getMessage());
            Response::error("Failed to book service: " . $e->getMessage(), 500);
        }
    }

    public function getMyBookings()
    {
        try {
            $user = $this->auth->authenticate();

            $stmt = $this->db->prepare("
              SELECT sb.*, s.name as service_name, s.provider_name, s.contact_info
              FROM service_bookings sb
              JOIN services s ON sb.service_id = s.id
              WHERE sb.resident_id = ?
              ORDER BY sb.booking_date DESC
          ");
            $stmt->execute([$user['uid']]);
            Response::success("Bookings retrieved", $stmt->fetchAll());

        } catch (Exception $e) {
            Response::error("Failed to retrieve bookings: " . $e->getMessage(), 500);
        }
    }
}
