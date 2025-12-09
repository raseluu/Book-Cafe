<?php
// api/routes.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// DEBUG: Enable Error Reporting
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Autoload config (quick and dirty)
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Mail.php';

// Parse endpoint
// /api/books -> parts[2] = books
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));

// DEBUG: Log the parsed parts
file_put_contents(__DIR__ . '/debug_log.txt', "URI: $uri\nParts: " . print_r($parts, true) . "\nMethod: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

// Index 0 = api, Index 1 = resource (e.g., 'books')
$resource = isset($parts[1]) ? $parts[1] : null;
$id = isset($parts[2]) ? $parts[2] : null;

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/PublicController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/UserController.php';

// Instantiate Controllers
$auth = new AuthController();
$public = new PublicController();
$admin = new AdminController();
$user = new UserController();

// Input Data
$input = json_decode(file_get_contents('php://input'));

// Method
$method = $_SERVER['REQUEST_METHOD'];

// Dispatch
// Dispatch
try {
    switch ($resource) {
        case 'login':
            if ($method === 'POST') echo json_encode($auth->login($input));
            break;
        case 'register':
            if ($method === 'POST') echo json_encode($auth->register($input));
            break;
        case 'verify':
            if ($method === 'POST') echo json_encode($auth->verify($input));
            break;
        case 'forgot-password':
            if ($method === 'POST') echo json_encode($auth->forgotPassword($input));
            break;
        case 'reset-password':
            if ($method === 'POST') echo json_encode($auth->resetPassword($input));
            break;
        case 'books':
            if ($method === 'GET') echo json_encode($public->getBooks());
            break;
        case 'menu':
            if ($method === 'GET') echo json_encode($public->getMenu());
            break;
        case 'events':
            if ($method === 'GET') echo json_encode($public->getEvents($id));
            break;
            
        case 'register-event':
            if ($method === 'POST') echo json_encode($user->registerEvent($input));
            break;

        case 'my-events':
            if ($method === 'GET') echo json_encode($user->getUserRegistrations($_GET['user_id'] ?? null));
            break;
            
        case 'update-profile':
            if ($method === 'POST') echo json_encode($user->updateProfile($input));
            break;

        case 'request-email-otp':
            if ($method === 'POST') echo json_encode($user->requestEmailChangeOTP($input));
            break;
            
        case 'request-cancellation':
            if ($method === 'POST') echo json_encode($user->requestCancellation($input));
            break;

        // --- ADMIN ROUTES ---
        // ... (Existing Routes) ...
        case 'admin_books':
            if ($method === 'GET') echo json_encode($public->getBooks());
            if ($method === 'POST') echo json_encode($admin->addBook($input));
            if ($method === 'PUT') echo json_encode($admin->updateBook($input));
            if ($method === 'DELETE') echo json_encode($admin->deleteBook($_GET['id']));
            break;

        case 'admin_menu':
            if ($method === 'GET') echo json_encode($public->getMenu()); // Reuse public getter
            if ($method === 'POST') echo json_encode($admin->addMenu($input));
            if ($method === 'PUT') echo json_encode($admin->updateMenu($input));
            if ($method === 'DELETE') echo json_encode($admin->deleteMenu($_GET['id']));
            break;

        case 'admin_cancellations':
            if ($method === 'GET') echo json_encode($admin->getCancellations());
            if ($method === 'POST') echo json_encode($admin->handleCancellation($input));
            break;

        case 'admin_events':
            if ($method === 'GET') echo json_encode($public->getEvents());
            if ($method === 'POST') echo json_encode($admin->addEvent($input));
            if ($method === 'PUT') echo json_encode($admin->updateEvent($input));
            if ($method === 'DELETE') echo json_encode($admin->deleteEvent($_GET['id']));
            break;

        case 'admin_event_registrations':
            if ($method === 'GET') echo json_encode($admin->getEventRegistrations($_GET['id']));
            break;

        case 'admin_cancel_booking':
            if ($method === 'POST') echo json_encode($admin->cancelBookingByAdmin($input));
            break;

        case 'admin_approve_booking':
            if ($method === 'POST') echo json_encode($admin->approveBooking($input));
            break;

        case 'admin_cancel_all_bookings':
            if ($method === 'POST') echo json_encode($admin->adminCancelAllBookings($input));
            break;

        case 'admin_add_booking':
            if ($method === 'POST') echo json_encode($admin->adminAddBooking($input));
            break;

        case 'admin_users':
            if ($method === 'GET') echo json_encode($admin->getUsers());
            if ($method === 'POST') echo json_encode($admin->addUser($input));
            if ($method === 'PUT') echo json_encode($admin->updateUser($input));
            if ($method === 'DELETE') echo json_encode($admin->deleteUser($_GET['id']));
            break;
            
        case 'admin_ban_user':
            if ($method === 'POST') echo json_encode($admin->toggleBan($input));
            break;

        case 'admin_upload':
            if ($method === 'POST') echo json_encode($admin->uploadImage());
            break;
            
        // Debug/Setup
        case 'seed':
            require_once __DIR__ . '/../api/seed.php';
            break;

        default:
            http_response_code(404);
            echo json_encode(['message' => 'API Endpoint Not Found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Server Error: ' . $e->getMessage()]);
}
