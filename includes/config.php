<?php
// Application Configuration

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'security_management');

// Application settings
define('SITE_NAME', 'SecureConnect Kenya');
define('SITE_URL', 'http://localhost:8080/SecurityFirm');
define('ADMIN_EMAIL', 'admin@secureconnect.co.ke');

// Session settings
define('SESSION_LIFETIME', 86400); // 24 hours

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Security settings
define('HASH_COST', 12); // Password hashing cost

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}