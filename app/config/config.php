<?php
/**
 * Application Configuration
 * This file loads configuration based on the environment
 */

// Load environment detection
require_once __DIR__ . '/environment.php';

// Default configuration
$config = [
    'app_env' => 'development',
    'debug' => true,
    'api_prefix' => '/api',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Kolkata'
];

// Environment-specific overrides
if (EnvironmentConfig::isHostinger()) {
    $config['app_env'] = 'production';
    $config['debug'] = false;
} else {
    $config['app_env'] = 'development';
    $config['debug'] = true;
}

// Automatically apply the timezone across the PHP runtime
if (!empty($config['timezone'])) {
    date_default_timezone_set($config['timezone']);
}

return $config;