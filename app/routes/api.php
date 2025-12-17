<?php
// Load all required files
require_once __DIR__ . '/../helpers/jwt_helper.php';
require_once __DIR__ . '/../helpers/uploader.php';
require_once __DIR__ . '/../helpers/notification_helper.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/BaseController.php';

// Load all controllers
require_once __DIR__ . '/../modules/auth/AuthController.php';
require_once __DIR__ . '/../modules/visitors/VisitorsController.php';
require_once __DIR__ . '/../modules/admin/AdminController.php';
require_once __DIR__ . '/../modules/accounting/AccountingController.php';
require_once __DIR__ . '/../modules/communications/CommunicationsController.php';
require_once __DIR__ . '/../modules/amenities/AmenitiesController.php';
require_once __DIR__ . '/../modules/helpdesk/HelpdeskController.php';
require_once __DIR__ . '/../modules/security/SecurityController.php';
require_once __DIR__ . '/../modules/vehicles/VehicleController.php';
require_once __DIR__ . '/../modules/users/UserController.php';
require_once __DIR__ . '/../modules/marketplace/MarketplaceController.php';
require_once __DIR__ . '/../modules/services/ServicesController.php';
require_once __DIR__ . '/../modules/pets/PetController.php';
require_once __DIR__ . '/../modules/assets/AssetController.php';
require_once __DIR__ . '/../modules/notifications/NotificationController.php';
require_once __DIR__ . '/../modules/family/FamilyController.php';
require_once __DIR__ . '/../modules/admin/UserManagementController.php';

// Get the request URI and method
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// DEBUG: Log the original request for troubleshooting
// error_log("Original URI: " . $uri . " | Method: " . $method);

// Dynamically determine base path for both localhost and production environments
$basePath = '';
// Check if we're in a subdirectory by examining the script name
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($scriptName, '/', 1) !== false) {
    $basePath = dirname($scriptName);
    // Remove leading slash if present and not at root
    if ($basePath !== '/' && $basePath !== '\\') {
        $basePath = rtrim($basePath, '/');
    } else {
        $basePath = '';
    }
}

// DEBUG: Log base path calculation
// error_log("Calculated base path: '" . $basePath . "'");

// Remove the base path to match routes correctly
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// DEBUG: Log URI after base path removal
// error_log("URI after base path removal: '" . $uri . "'");

// Also handle the case where index.php is in the path
if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, 10); // Remove '/index.php'
}

// DEBUG: Log URI after index.php removal
// error_log("URI after index.php removal: '" . $uri . "'");

// Handle API prefix - ensure all routes start with /api/
if (strpos($uri, '/api/') !== 0) {
    // If it doesn't start with /api/, check if it should
    if (preg_match('/^\/(buildings|auth|visitors|admin|accounting|communications|amenities|helpdesk|security|vehicles|test)/', $uri, $matches)) {
        $uri = '/api' . $uri;
    }
}

// DEBUG: Log final processed URI
// error_log("Final processed URI: '" . $uri . "' | Method: " . $method);

// Route matching
try {
    // Test endpoint
    if ($uri === '/api/test' && $method === 'GET') {
        Response::success("MyGate API is running successfully!", [
            'version' => '1.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]);
    }

    // Health check endpoint
    if ($uri === '/api/health' && $method === 'GET') {
        try {
            // Test database connection
            $dbConfig = require __DIR__ . '/../config/database.php';
            $db = Database::connect($dbConfig);

            // Simple query to test connection
            $stmt = $db->query("SELECT 1 as connected");
            $result = $stmt->fetch();

            Response::success("API is healthy", [
                'database' => $result ? 'connected' : 'disconnected',
                'php_version' => phpversion(),
                'server_time' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            Response::error("Database connection failed: " . $e->getMessage(), 500);
        }
    }

    // Auth routes
    if ($uri === '/api/auth/register' && $method === 'POST') {
        (new AuthController)->register();
    }

    if ($uri === '/api/auth/login' && $method === 'POST') {
        (new AuthController)->login();
    }

    if ($uri === '/api/auth/refresh' && $method === 'POST') {
        (new AuthController)->refreshToken();
    }

    if ($uri === '/api/auth/change-password' && $method === 'POST') {
        (new AuthController)->changePassword();
    }

    if ($uri === '/api/auth/forgot-password' && $method === 'POST') {
        (new AuthController)->forgotPassword();
    }

    if ($uri === '/api/auth/logout' && $method === 'POST') {
        (new AuthController)->logout();
    }

    // User status update route
    if (preg_match('/^\/api\/auth\/users\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new AuthController)->updateUserStatus($matches[1]);
    }

    // Visitor routes
    if ($uri === '/api/visitors' && $method === 'POST') {
        (new VisitorsController)->addVisitor();
    }

    if ($uri === '/api/visitors' && $method === 'GET') {
        (new VisitorsController)->getVisitors();
    }

    if (preg_match('/^\/api\/visitors\/(\d+)$/', $uri, $matches) && $method === 'GET') {
        (new VisitorsController)->getVisitorById($matches[1]);
    }

    if (preg_match('/^\/api\/visitors\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new VisitorsController)->updateVisitorStatus($matches[1]);
    }

    if (preg_match('/^\/api\/visitors\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
        (new VisitorsController)->deleteVisitor($matches[1]);
    }

    // Admin routes
    if ($uri === '/api/admin/societies' && $method === 'POST') {
        (new AdminController)->createSociety();
    }

    if ($uri === '/api/admin/societies' && $method === 'GET') {
        (new AdminController)->getSocieties();
    }

    // Live search societies endpoint
    if ($uri === '/api/societies/search' && $method === 'GET') {
        (new AdminController)->searchSocieties();
    }

    // Buildings endpoints
    if ($uri === '/api/buildings' && $method === 'POST') {
        (new AdminController)->createBuilding();
    }

    if (preg_match('/^\/api\/buildings\/by-society\/([0-9]+)$/', $uri, $matches) && $method === 'GET') {
        (new AdminController)->getBuildingsBySociety($matches[1]);
    }

    // Flats endpoints
    if (preg_match('/^\/api\/flats\/by-building\/([0-9]+)$/', $uri, $matches) && $method === 'GET') {
        (new AdminController)->getFlatsByBuilding($matches[1]);
    }

    if ($uri === '/api/flats' && $method === 'POST') {
        (new AdminController)->createFlatsForBuilding();
    }

    if (preg_match('/^\/api\/admin\/societies\/(\d+)$/', $uri, $matches) && $method === 'GET') {
        (new AdminController)->getSocietyById($matches[1]);
    }

    if (preg_match('/^\/api\/admin\/societies\/(\d+)$/', $uri, $matches) && $method === 'PUT') {
        (new AdminController)->updateSociety($matches[1]);
    }

    if (preg_match('/^\/api\/admin\/societies\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
        (new AdminController)->deleteSociety($matches[1]);
    }

    if ($uri === '/api/admin/user-role' && $method === 'POST') {
        (new AdminController)->assignUserRole();
    }

    // Accounting routes
    if ($uri === '/api/accounting/charge-heads' && $method === 'POST') {
        (new AccountingController)->createChargeHead();
    }

    if ($uri === '/api/accounting/charge-heads' && $method === 'GET') {
        (new AccountingController)->getChargeHeads();
    }

    if ($uri === '/api/accounting/invoices' && $method === 'POST') {
        (new AccountingController)->createInvoice();
    }

    if ($uri === '/api/accounting/invoices' && $method === 'GET') {
        (new AccountingController)->getInvoices();
    }

    if (preg_match('/^\/api\/accounting\/invoices\/(\d+)$/', $uri, $matches) && $method === 'GET') {
        (new AccountingController)->getInvoiceById($matches[1]);
    }

    // Invoice status update route
    if (preg_match('/^\/api\/accounting\/invoices\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new AccountingController)->updateInvoiceStatus($matches[1]);
    }

    if ($uri === '/api/accounting/payments' && $method === 'POST') {
        (new AccountingController)->processPayment();
    }

    // Payment transaction status update route
    if (preg_match('/^\/api\/accounting\/payments\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new AccountingController)->updatePaymentTransactionStatus($matches[1]);
    }

    // Communications routes
    if ($uri === '/api/communications/groups' && $method === 'POST') {
        (new CommunicationsController)->createGroup();
    }

    if ($uri === '/api/communications/groups' && $method === 'GET') {
        (new CommunicationsController)->getGroups();
    }

    if (preg_match('/^\/api\/communications\/groups\/(\d+)\/join$/', $uri, $matches) && $method === 'POST') {
        (new CommunicationsController)->joinGroup($matches[1]);
    }

    if (preg_match('/^\/api\/communications\/groups\/(\d+)\/leave$/', $uri, $matches) && $method === 'POST') {
        (new CommunicationsController)->leaveGroup($matches[1]);
    }

    if ($uri === '/api/communications/announcements' && $method === 'POST') {
        (new CommunicationsController)->createAnnouncement();
    }

    if ($uri === '/api/communications/announcements' && $method === 'GET') {
        (new CommunicationsController)->getAnnouncements();
    }

    if ($uri === '/api/communications/polls' && $method === 'POST') {
        (new CommunicationsController)->createPoll();
    }

    if ($uri === '/api/communications/polls' && $method === 'GET') {
        (new CommunicationsController)->getPolls();
    }

    if (preg_match('/^\/api\/communications\/polls\/(\d+)\/vote$/', $uri, $matches) && $method === 'POST') {
        (new CommunicationsController)->voteOnPoll($matches[1]);
    }

    // Amenities routes
    if ($uri === '/api/amenities' && $method === 'POST') {
        (new AmenitiesController)->createAmenity();
    }

    if ($uri === '/api/amenities' && $method === 'GET') {
        (new AmenitiesController)->getAmenities();
    }

    if (preg_match('/^\/api\/amenities\/(\d+)\/book$/', $uri, $matches) && $method === 'POST') {
        (new AmenitiesController)->bookAmenity($matches[1]);
    }

    if ($uri === '/api/amenities/bookings' && $method === 'GET') {
        (new AmenitiesController)->getBookings();
    }

    if (preg_match('/^\/api\/amenities\/bookings\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new AmenitiesController)->updateBookingStatus($matches[1]);
    }

    // Helpdesk routes
    if ($uri === '/api/helpdesk/tickets' && $method === 'POST') {
        (new HelpdeskController)->createTicket();
    }

    if ($uri === '/api/helpdesk/tickets' && $method === 'GET') {
        (new HelpdeskController)->getTickets();
    }

    if (preg_match('/^\/api\/helpdesk\/tickets\/(\d+)$/', $uri, $matches) && $method === 'GET') {
        (new HelpdeskController)->getTicketById($matches[1]);
    }

    if (preg_match('/^\/api\/helpdesk\/tickets\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new HelpdeskController)->updateTicketStatus($matches[1]);
    }

    if (preg_match('/^\/api\/helpdesk\/tickets\/(\d+)\/assign$/', $uri, $matches) && $method === 'PUT') {
        (new HelpdeskController)->assignTicket($matches[1]);
    }

    if (preg_match('/^\/api\/helpdesk\/tickets\/(\d+)\/comments$/', $uri, $matches) && $method === 'POST') {
        (new HelpdeskController)->addComment($matches[1]);
    }

    // Security routes
    if ($uri === '/api/security/alerts' && $method === 'POST') {
        (new SecurityController)->reportAlert();
    }

    if ($uri === '/api/security/alerts' && $method === 'GET') {
        (new SecurityController)->getAlerts();
    }

    if (preg_match('/^\/api\/security\/alerts\/(\d+)$/', $uri, $matches) && $method === 'GET') {
        (new SecurityController)->getAlertById($matches[1]);
    }

    if (preg_match('/^\/api\/security\/alerts\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new SecurityController)->updateAlertStatus($matches[1]);
    }

    if ($uri === '/api/security/emergency-contacts' && $method === 'GET') {
        (new SecurityController)->getEmergencyContacts();
    }

    if ($uri === '/api/security/emergency-contacts' && $method === 'POST') {
        (new SecurityController)->addEmergencyContact();
    }

    // Vehicle routes
    if ($uri === '/api/vehicles' && $method === 'POST') {
        (new VehicleController)->addVehicle();
    }

    if ($uri === '/api/vehicles' && $method === 'GET') {
        (new VehicleController)->getVehicles();
    }

    if (preg_match('/^\/api\/vehicles\/(\d+)$/', $uri, $matches) && $method === 'GET') {
        (new VehicleController)->getVehicleById($matches[1]);
    }

    if (preg_match('/^\/api\/vehicles\/(\d+)$/', $uri, $matches) && $method === 'PUT') {
        (new VehicleController)->updateVehicle($matches[1]);
    }

    if (preg_match('/^\/api\/vehicles\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
        (new VehicleController)->deleteVehicle($matches[1]);
    }

    if ($uri === '/api/vehicles/parking-spots' && $method === 'GET') {
        (new VehicleController)->getParkingSpots();
    }

    if (preg_match('/^\/api\/vehicles\/parking-spots\/(\d+)\/assign$/', $uri, $matches) && $method === 'POST') {
        (new VehicleController)->assignParkingSpot($matches[1]);
    }

    if (preg_match('/^\/api\/vehicles\/parking-spots\/(\d+)\/release$/', $uri, $matches) && $method === 'POST') {
        (new VehicleController)->releaseParkingSpot($matches[1]);
    }

    // User Profile routes
    if ($uri === '/api/users/profile' && $method === 'GET') {
        (new UserController)->getProfile();
    }

    if ($uri === '/api/users/profile' && $method === 'PUT') {
        (new UserController)->updateProfile();
    }

    // Marketplace Routes
    if ($uri === '/api/marketplace/categories' && $method === 'GET') {
        (new MarketplaceController)->getCategories();
    }
    if ($uri === '/api/marketplace/products' && $method === 'GET') {
        (new MarketplaceController)->getProducts();
    }
    if ($uri === '/api/marketplace/products' && $method === 'POST') {
        (new MarketplaceController)->addProduct();
    }
    if ($uri === '/api/marketplace/orders' && $method === 'POST') {
        (new MarketplaceController)->createOrder();
    }
    if ($uri === '/api/marketplace/orders/my' && $method === 'GET') {
        (new MarketplaceController)->getMyOrders();
    }

    // Services Routes
    if ($uri === '/api/services/categories' && $method === 'GET') {
        (new ServicesController)->getCategories();
    }
    if ($uri === '/api/services' && $method === 'GET') {
        (new ServicesController)->getServices();
    }
    if ($uri === '/api/services/book' && $method === 'POST') {
        (new ServicesController)->bookingService();
    }
    if ($uri === '/api/services/bookings/my' && $method === 'GET') {
        (new ServicesController)->getMyBookings();
    }

    // Pet Routes
    if ($uri === '/api/pets/types' && $method === 'GET') {
        (new PetController)->getPetTypes();
    }
    if ($uri === '/api/pets/types' && $method === 'POST') {
        (new PetController)->addPetType();
    }
    if ($uri === '/api/pets' && $method === 'POST') {
        (new PetController)->addPet();
    }
    if ($uri === '/api/pets' && $method === 'GET') {
        (new PetController)->getPets();
    }
    if (preg_match('/^\/api\/pets\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
        (new PetController)->deletePet($matches[1]);
    }

    // Asset Routes
    if ($uri === '/api/assets/categories' && $method === 'GET') {
        (new AssetController)->getAssetCategories();
    }
    if ($uri === '/api/assets' && $method === 'GET') {
        (new AssetController)->getAssets();
    }
    if ($uri === '/api/assets' && $method === 'POST') {
        (new AssetController)->addAsset();
    }
    if ($uri === '/api/assets/inventory' && $method === 'GET') {
        (new AssetController)->getInventory();
    }
    if ($uri === '/api/assets/inventory' && $method === 'POST') {
        (new AssetController)->addInventoryItem();
    }

    // Notification Routes
    if ($uri === '/api/notifications' && $method === 'GET') {
        (new NotificationController)->getNotifications();
    }
    if (preg_match('/^\/api\/notifications\/(\d+)\/read$/', $uri, $matches) && $method === 'PUT') {
        (new NotificationController)->markAsRead($matches[1]);
    }
    if ($uri === '/api/notifications/read-all' && $method === 'PUT') {
        (new NotificationController)->markAllAsRead();
    }

    // Family Routes
    if ($uri === '/api/family' && $method === 'GET') {
        (new FamilyController)->getFamilyMembers();
    }
    if ($uri === '/api/family' && $method === 'POST') {
        (new FamilyController)->addFamilyMember();
    }
    if (preg_match('/^\/api\/family\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
        (new FamilyController)->deleteFamilyMember($matches[1]);
    }

    // User Management Routes (Admin)
    if ($uri === '/api/admin/users' && $method === 'GET') {
        (new UserManagementController)->getUsers();
    }
    if ($uri === '/api/admin/users' && $method === 'POST') {
        (new UserManagementController)->createUser();
    }
    if (preg_match('/^\/api\/admin\/users\/(\d+)$/', $uri, $matches) && $method === 'GET') {
        (new UserManagementController)->getUserById($matches[1]);
    }
    if (preg_match('/^\/api\/admin\/users\/(\d+)$/', $uri, $matches) && $method === 'PUT') {
        (new UserManagementController)->updateUser($matches[1]);
    }
    if (preg_match('/^\/api\/admin\/users\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
        (new UserManagementController)->deleteUser($matches[1]);
    }

    // If no route matched, return 404
    Response::notFound("API endpoint not found: " . $uri);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    Response::error("Internal server error: " . $e->getMessage(), 500);
}