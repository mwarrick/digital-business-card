<?php
/**
 * Secure Configuration Manager
 * 
 * This class handles loading configuration from environment variables
 * and provides fallbacks for development.
 */

class Config {
    private static $config = [];
    private static $loaded = false;
    
    /**
     * Load configuration from environment variables
     */
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        // Load from .env file if it exists
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            self::loadEnvFile($envFile);
        }
        
        // Load from environment variables
        self::loadFromEnvironment();
        
        // Set defaults for development
        self::setDefaults();
        
        self::$loaded = true;
    }
    
    /**
     * Load configuration from .env file
     */
    private static function loadEnvFile($file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');
                $_ENV[$key] = $value;
            }
        }
    }
    
    /**
     * Load configuration from environment variables
     */
    private static function loadFromEnvironment() {
        $configKeys = [
            // Database
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET',
            
            // SSH
            'SSH_HOST', 'SSH_PORT', 'SSH_USER', 'SSH_KEY',
            
            // Gmail API
            'GMAIL_CLIENT_ID', 'GMAIL_CLIENT_SECRET', 'GMAIL_REDIRECT_URI',
            
            // JWT
            'JWT_SECRET', 'JWT_ALGORITHM', 'JWT_EXPIRY',
            
            // Security
            'SESSION_TIMEOUT', 'VERIFICATION_CODE_LENGTH', 'VERIFICATION_CODE_EXPIRY',
            
            // File uploads
            'MAX_FILE_SIZE', 'UPLOAD_PATH', 'ALLOWED_IMAGE_TYPES',
            
            // Email
            'EMAIL_FROM_NAME', 'EMAIL_FROM_ADDRESS',
            
            // App settings
            'DEBUG_MODE', 'LOG_ERRORS', 'CORS_ORIGINS'
        ];
        
        foreach ($configKeys as $key) {
            if (isset($_ENV[$key])) {
                self::$config[$key] = $_ENV[$key];
            }
        }
    }
    
    /**
     * Set default values for development
     */
    private static function setDefaults() {
        $defaults = [
            'DB_HOST' => 'localhost',
            'DB_CHARSET' => 'utf8mb4',
            'SSH_PORT' => '22',
            'JWT_ALGORITHM' => 'HS256',
            'JWT_EXPIRY' => 3600,
            'SESSION_TIMEOUT' => 3600,
            'VERIFICATION_CODE_LENGTH' => 6,
            'VERIFICATION_CODE_EXPIRY' => 300,
            'MAX_FILE_SIZE' => 25 * 1024 * 1024,
            'UPLOAD_PATH' => __DIR__ . '/../storage/media/',
            'ALLOWED_IMAGE_TYPES' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'EMAIL_FROM_NAME' => 'ShareMyCard',
            'EMAIL_FROM_ADDRESS' => 'noreply@sharemycard.app',
            'DEBUG_MODE' => false,
            'LOG_ERRORS' => true,
            'CORS_ORIGINS' => ['https://sharemycard.app', 'https://www.sharemycard.app']
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = $value;
            }
        }
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Set configuration value
     */
    public static function set($key, $value) {
        self::$config[$key] = $value;
    }
    
    /**
     * Check if configuration key exists
     */
    public static function has($key) {
        self::load();
        return isset(self::$config[$key]);
    }
    
    /**
     * Get all configuration
     */
    public static function all() {
        self::load();
        return self::$config;
    }
    
    /**
     * Validate required configuration
     */
    public static function validate() {
        self::load();
        
        $required = [
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
            'GMAIL_CLIENT_ID', 'GMAIL_CLIENT_SECRET',
            'JWT_SECRET'
        ];
        
        $missing = [];
        foreach ($required as $key) {
            if (!self::has($key) || empty(self::get($key))) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception('Missing required configuration: ' . implode(', ', $missing));
        }
    }
}
