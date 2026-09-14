<?php
//application configuration file
Define('APP_NAME', 'Supreme Court Case Management System');
define('BASE_URL', '/public');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/../uploads/');
define('SESSION_LIFETIME', 3600);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'supreme_court_cms');
define('DB_USER', 'abe');
define('DB_PASS', 'Abebe%401212');

// Africa's Talking sandbox USSD credentials
define('AT_USERNAME', getenv('AT_USERNAME') ?: 'sandbox');
define('AT_API_KEY', getenv('AT_API_KEY') ?: 'atsk_ad1a660b2f26f8200eb99ff43c159836dba9b5738814cd6cfa46442ffdec25a80b805f98');

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
//session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Addis_Ababa');
?>