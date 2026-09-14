<?php
//application configuration file
Define('APP_NAME', 'Supreme Court Case Management System');
define('BASE_URL', '/public');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/../uploads/');
define('SESSION_LIFETIME', 3600);

// Load a repository-level .env file when present.
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Database configuration from environment only. No committed usernames or passwords.
$databaseUrl = getenv('DATABASE_URL');

if (!empty($databaseUrl)) {
    $databaseParts = parse_url($databaseUrl);

    define('DB_HOST', $databaseParts['host'] ?? (getenv('DB_HOST') ?: 'localhost'));
    define('DB_PORT', $databaseParts['port'] ?? (getenv('DB_PORT') ?: '5432'));
    define('DB_NAME', trim($databaseParts['path'] ?? '', '/') ?: (getenv('DB_NAME') ?: 'supreme_court_cms'));
    define('DB_USER', isset($databaseParts['user']) ? urldecode($databaseParts['user']) : (getenv('DB_USER') ?: 'postgres'));
    define('DB_PASS', isset($databaseParts['pass']) ? urldecode($databaseParts['pass']) : (getenv('DB_PASS') ?: 'postgres'));
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '5432');
    define('DB_NAME', getenv('DB_NAME') ?: 'supreme_court_cms');
    define('DB_USER', getenv('DB_USER') ?: 'postgres');
    define('DB_PASS', getenv('DB_PASS') ?: 'postgres');
}

// Safe fallback pair sourced from environment only.
define('DB_FALLBACK_HOST', getenv('DB_FALLBACK_HOST') ?: 'localhost');
define('DB_FALLBACK_PORT', getenv('DB_FALLBACK_PORT') ?: '5432');
define('DB_FALLBACK_NAME', getenv('DB_FALLBACK_NAME') ?: 'supreme_court_cms');
define('DB_FALLBACK_USER', getenv('DB_FALLBACK_USER') ?: 'postgres');
define('DB_FALLBACK_PASS', getenv('DB_FALLBACK_PASS') ?: 'postgres');

// Africa's Talking USSD credentials from environment only.
define('AT_USERNAME', getenv('AT_USERNAME') ?: 'sandbox');
define('AT_API_KEY', getenv('AT_API_KEY') ?: 'replace-with-api-key');

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

//session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Addis_Ababa');
?>