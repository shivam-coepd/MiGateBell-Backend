<?php
// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

return [
  'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
  'db'   => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'migate',
  'user' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
  'pass' => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: ''
];