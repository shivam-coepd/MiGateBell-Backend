<?php
require_once __DIR__.'/../../core/BaseController.php';

class EventController extends BaseController {

    public function getEvents() {
        try {
            $user = $this->auth->authenticate();
            
            $sql = "
                SELECT e.*, u.name as organizer_name
                FROM events e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.society_id = ?
                ORDER BY e.event_date DESC, e.event_time DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user['society_id']]);
            $events = $stmt->fetchAll();
            
            foreach ($events as &$event) {
                // Fetch user attendance status
                $attStmt = $this->db->prepare("SELECT status FROM event_attendees WHERE event_id = ? AND user_id = ?");
                $attStmt->execute([$event['id'], $user['uid']]);
                $attendance = $attStmt->fetch();
                $event['my_status'] = $attendance ? $attendance['status'] : null;
                
                // Fetch recent attendees for avatar bubbles
                $attListStmt = $this->db->prepare("SELECT u.profile_image FROM event_attendees ea JOIN users u ON ea.user_id = u.id WHERE ea.event_id = ? AND ea.status = 'going' LIMIT 8");
                $attListStmt->execute([$event['id']]);
                $recentAttendees = $attListStmt->fetchAll();
                $event['recent_attendees'] = array_column($recentAttendees, 'profile_image');
                
                // Set default organizer name if null
                if (empty($event['organizer'])) {
                    $event['organizer'] = $event['organizer_name'] ?? 'Society Admin';
                }
            }
            
            Response::success("Events retrieved successfully", ['events' => $events]);
            
        } catch(Exception $e) {
            error_log("Get events error: " . $e->getMessage());
            Response::error("Failed to retrieve events: " . $e->getMessage(), 500);
        }
    }

    public function createEvent() {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true);
            
            $errors = $this->validateRequiredFields($data, ['title', 'event_date']);
            if (!empty($errors)) {
                Response::validationError($errors);
            }
            
            // Only admins might be allowed, but we'll let any authenticated user create for now or check role
            // if ($user['role'] !== 'admin') {
            //     Response::error("Only admins can create events", 403);
            // }

            $eventId = $this->insert('events', [
                'society_id' => $user['society_id'],
                'user_id' => $user['uid'],
                'title' => $data['title'],
                'category' => $data['category'] ?? 'Event',
                'event_date' => $data['event_date'],
                'event_time' => $data['event_time'] ?? null,
                'location' => $data['location'] ?? null,
                'organizer' => $data['organizer'] ?? null,
                'price' => $data['price'] ?? 'Free',
                'cover_image' => $data['cover_image'] ?? null,
                'description' => $data['description'] ?? null,
                'tags' => $data['tags'] ?? null,
                'attendees' => 0,
                'rating' => 0.0
            ]);
            
            Response::success("Event created successfully", ['event_id' => $eventId], 201);
            
        } catch(Exception $e) {
            error_log("Create event error: " . $e->getMessage());
            Response::error("Failed to create event: " . $e->getMessage(), 500);
        }
    }

    public function updateEvent($eventId) {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true);
            
            $stmt = $this->db->prepare("SELECT user_id FROM events WHERE id = ? AND society_id = ?");
            $stmt->execute([$eventId, $user['society_id']]);
            $event = $stmt->fetch();
            
            if (!$event) {
                Response::notFound("Event not found");
            }
            
            if ($user['role'] !== 'admin' && $event['user_id'] != $user['uid']) {
                Response::error("Unauthorized to update this event", 403);
            }
            
            $updateData = [];
            $allowedFields = ['title', 'category', 'event_date', 'event_time', 'location', 'organizer', 'price', 'cover_image', 'description', 'tags'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (!empty($updateData)) {
                $this->update('events', $updateData, 'id = :id', ['id' => $eventId]);
            }
            
            Response::success("Event updated successfully");
            
        } catch(Exception $e) {
            error_log("Update event error: " . $e->getMessage());
            Response::error("Failed to update event: " . $e->getMessage(), 500);
        }
    }

    public function deleteEvent($eventId) {
        try {
            $user = $this->auth->authenticate();
            
            $stmt = $this->db->prepare("SELECT user_id FROM events WHERE id = ? AND society_id = ?");
            $stmt->execute([$eventId, $user['society_id']]);
            $event = $stmt->fetch();
            
            if (!$event) {
                Response::notFound("Event not found");
            }
            
            if ($user['role'] !== 'admin' && $event['user_id'] != $user['uid']) {
                Response::error("Unauthorized to delete this event", 403);
            }
            
            $this->delete('events', 'id = ?', [$eventId]);
            
            Response::success("Event deleted successfully");
            
        } catch(Exception $e) {
            error_log("Delete event error: " . $e->getMessage());
            Response::error("Failed to delete event: " . $e->getMessage(), 500);
        }
    }

    public function rsvpEvent($eventId) {
        try {
            $user = $this->auth->authenticate();
            $data = json_decode(file_get_contents("php://input"), true);
            
            $status = $data['status'] ?? 'going'; // 'going', 'maybe', 'not_going'
            
            $stmt = $this->db->prepare("SELECT id FROM event_attendees WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$eventId, $user['uid']]);
            $attendee = $stmt->fetch();
            
            if ($attendee) {
                $this->update('event_attendees', ['status' => $status], 'id = :id', ['id' => $attendee['id']]);
            } else {
                $this->insert('event_attendees', [
                    'event_id' => $eventId,
                    'user_id' => $user['uid'],
                    'status' => $status
                ]);
            }
            
            // Recalculate attendees count (only 'going' count)
            $countStmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM event_attendees WHERE event_id = ? AND status = 'going'");
            $countStmt->execute([$eventId]);
            $goingCount = $countStmt->fetch()['cnt'];
            $this->update('events', ['attendees' => $goingCount], 'id = :id', ['id' => $eventId]);
            // Fetch recent attendees
            $attListStmt = $this->db->prepare("SELECT u.profile_image FROM event_attendees ea JOIN users u ON ea.user_id = u.id WHERE ea.event_id = ? AND ea.status = 'going' LIMIT 8");
            $attListStmt->execute([$eventId]);
            $recentAttendees = $attListStmt->fetchAll();
            $recentAttendeesList = array_column($recentAttendees, 'profile_image');
            
            Response::success("RSVP updated successfully", [
                'attendees' => $goingCount,
                'recent_attendees' => $recentAttendeesList
            ]);
            
        } catch(Exception $e) {
            error_log("RSVP event error: " . $e->getMessage());
            Response::error("Failed to update RSVP: " . $e->getMessage(), 500);
        }
    }
}
