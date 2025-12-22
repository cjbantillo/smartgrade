<?php
/**
 * System Configuration
 * Global settings and constants for SmartGrade System
 */

// Environment Configuration
define('ENV', 'development'); // development or production

// Base URL Configuration
define('BASE_URL', 'http://localhost/smartgrade-v/');
define('BASE_PATH', dirname(dirname(__DIR__)));

// Directory Paths
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Session Configuration (only if session not started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1); // Force cookies for session
    ini_set('session.cookie_secure', 0);     // Set to 1 if using HTTPS
    session_save_path(sys_get_temp_dir());   // Use system temp directory
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting
if (ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// Security Headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Application Settings
define('APP_NAME', 'SmartGrade');
define('APP_VERSION', '1.0.0');
define('SCHOOL_NAME', 'Ampayon Senior High School');
define('SCHOOL_YEAR', '2024-2025');

// Pagination
define('ITEMS_PER_PAGE', 20);

// File Upload Limits (in bytes)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);

// Autoloader for classes
spl_autoload_register(function ($class_name) {
    $directories = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/middleware/',
        APP_PATH . '/helpers/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include database configuration
require_once APP_PATH . '/config/database.php';
