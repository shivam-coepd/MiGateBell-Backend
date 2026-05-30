<?php
// MyGate Backend Entry Point

// 1. Enable Error Reporting for Debugging (Disable in Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Set Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Content-Type: application/json");

// Handle Pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3. Load the API Router
// The api.php file handles:
// - Loading all dependencies (core, helpers, middlewares)
// - Loading all controllers
// - Database connection (on demand)
// - Routing based on URI
// - Response formatting
require_once __DIR__ . '/app/routes/api.php';