<?php
/**
 * Environment detection and configuration
 * This file helps the application work in different environments (local, staging, production)
 */

class EnvironmentConfig {
    private static $isHostinger = null;
    
    /**
     * Detect if we're running on Hostinger
     */
    public static function isHostinger() {
        if (self::$isHostinger === null) {
            // Check for Hostinger-specific indicators
            $host = $_SERVER['HTTP_HOST'] ?? '';
            self::$isHostinger = (
                strpos($host, 'hostinger') !== false || 
                strpos($host, 'hstgr') !== false ||
                defined('HOSTINGER_ENV')
            );
        }
        return self::$isHostinger;
    }
    
    /**
     * Get the base URL for the application
     */
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Get the directory path
        $path = dirname($scriptName);
        if ($path === '/' || $path === '\\') {
            $path = '';
        }
        
        return $protocol . '://' . $host . $path;
    }
    
    /**
     * Get environment-specific configuration
     */
    public static function getConfig() {
        $config = [];
        
        if (self::isHostinger()) {
            // Hostinger-specific configuration would go here
            $config['environment'] = 'hostinger';
        } else {
            // Local development configuration
            $config['environment'] = 'local';
        }
        
        return $config;
    }
}