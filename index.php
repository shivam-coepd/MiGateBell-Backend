<?php
// MyGate Backend Entry Point

// 1. Error handling — log errors but NEVER display them (would corrupt JSON)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Buffer all output so stray warnings/notices don't corrupt JSON responses
ob_start();

// 2. Set Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Content-Type: application/json");

// Handle Pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// 3. Load the API Router
require_once __DIR__ . '/app/routes/api.php';

// Flush clean output (only reached if router doesn't exit)
ob_end_flush();