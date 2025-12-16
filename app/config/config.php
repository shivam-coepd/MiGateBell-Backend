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
    'timezone' => 'UTC'
];

// Environment-specific overrides
if (EnvironmentConfig::isHostinger()) {
    $config['app_env'] = 'production';
    $config['debug'] = false;
} else {
    $config['app_env'] = 'development';
    $config['debug'] = true;
}

return $config;