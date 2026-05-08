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
require_once __DIR__ . '/../modules/flats/FlatController.php';
require_once __DIR__ . '/../modules/locations/LocationController.php';
require_once __DIR__ . '/../modules/admin/SuperAdminController.php';

// Get the request URI and method
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Dynamically determine base path
$basePath = '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($scriptName, '/', 1) !== false) {
    $basePath = dirname($scriptName);
    if ($basePath !== '/' && $basePath !== '\\') {
        $basePath = rtrim($basePath, '/');
    } else {
        $basePath = '';
    }
}

// Remove base path
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Handle index.php
if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, 10);
}

// Ensure /api prefix
if (strpos($uri, '/api/') !== 0 && $uri !== '/api') {
    $uri = '/api' . $uri;
}

$locationController = new LocationController();

try {
    // Health check
    if ($uri === '/api/health' && $method === 'GET') {
        Response::success("API is healthy");
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
    if ($uri === '/api/auth/logout' && $method === 'POST') {
        (new AuthController)->logout();
    }
    if (preg_match('/^\/api\/auth\/users\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new AuthController)->updateUserStatus($matches[1]);
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

    // Location endpoints
    if ($uri === '/api/locations/countries' && $method === 'GET') {
        $locationController->getCountries();
    }

    if ($uri === '/api/locations/cities' && $method === 'GET') {
        $locationController->getCities();
    }

    if (preg_match('/^\/api\/locations\/cities\/by-country\/([^\/]+)$/', $uri, $matches) && $method === 'GET') {
        $locationController->getCitiesByCountry($matches[1]);
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

    // Get complete society information with all related data
    if (preg_match('/^\/api\/admin\/societies\/(\d+)\/complete$/', $uri, $matches) && $method === 'GET') {
        (new AdminController)->getCompleteSocietyById($matches[1]);
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
    if ($uri === '/api/vehicles/types' && $method === 'POST') {
        (new VehicleController)->addVehicleType();
    }

    if ($uri === '/api/vehicles/types' && $method === 'GET') {
        (new VehicleController)->getVehicleTypes();
    }

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

    // Update family member details
    if (preg_match('/^\/api\/family\/(\d+)$/', $uri, $matches) && $method === 'PUT') {
        (new FamilyController)->updateFamilyMember($matches[1]);
    }

    // Soft delete family member
    if (preg_match('/^\/api\/family\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
        (new FamilyController)->deleteFamilyMember($matches[1]);
    }

    // Activate / Deactivate family member
    if (preg_match('/^\/api\/family\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
        (new FamilyController)->changeFamilyMemberStatus($matches[1]);
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

    // Flat Routes
    if ($uri === '/api/flat/add-home' && $method === 'POST') {
        (new FlatController)->addHome();
    }
    if (preg_match('/^\/api\/flats\/buildings\/by-society\/([0-9]+)$/', $uri, $matches) && $method === 'GET') {
        (new FlatController)->getBuildingsBySociety($matches[1]);
    }
    if (preg_match('/^\/api\/flats\/by-building\/([0-9]+)$/', $uri, $matches) && $method === 'GET') {
        (new FlatController)->getFlatsByBuilding($matches[1]);
    }

    // Super Admin Stats
    if ($uri === '/api/superadmin/stats' && $method === 'GET') {
        (new SuperAdminController())->getStats();
    }

    // Registrations
    if ($uri === '/api/superadmin/registrations' && $method === 'GET') {
        (new SuperAdminController())->getRegistrations();
    }
    if (preg_match('/^\/api\/superadmin\/registrations\/([0-9]+)$/', $uri, $matches) && $method === 'PUT') {
        (new SuperAdminController())->updateRegistration($matches[1]);
    }

    // Societies
    if ($uri === '/api/superadmin/societies' && $method === 'GET') {
        (new SuperAdminController())->getSocieties();
    }
    if ($uri === '/api/superadmin/societies' && $method === 'POST') {
        (new SuperAdminController())->createSociety();
    }
    if (preg_match('/^\/api\/superadmin\/societies\/([0-9]+)$/', $uri, $matches)) {
        $controller = new SuperAdminController();
        if ($method === 'GET') $controller->getSocietyById($matches[1]);
        if ($method === 'DELETE') $controller->deleteSociety($matches[1]);
    }
    if (preg_match('/^\/api\/superadmin\/societies\/([0-9]+)\/admin$/', $uri, $matches) && $method === 'POST') {
        (new SuperAdminController())->createSocietyAdmin($matches[1]);
    }
    if (preg_match('/^\/api\/superadmin\/societies\/([0-9]+)\/approve$/', $uri, $matches) && $method === 'PUT') {
        (new SuperAdminController())->approveSociety($matches[1]);
    }
    if (preg_match('/^\/api\/superadmin\/societies\/([0-9]+)\/suspend$/', $uri, $matches) && $method === 'PUT') {
        (new SuperAdminController())->suspendSociety($matches[1]);
    }

    // Admins
    if ($uri === '/api/superadmin/admins' && $method === 'GET') {
        (new SuperAdminController())->getAdmins();
    }
    if (preg_match('/^\/api\/superadmin\/admins\/([0-9]+)\/toggle$/', $uri, $matches) && $method === 'PUT') {
        (new SuperAdminController())->toggleAdmin($matches[1]);
    }

    // If no route matched, return 404
    Response::notFound("API endpoint not found: " . $uri);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    Response::error("Internal server error: " . $e->getMessage(), 500);
}