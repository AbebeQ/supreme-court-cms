<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if ($uri === '/ussd' || $uri === '/ussd.php') {
    require __DIR__ . '/public/ussd.php';
    return;
}

if ($uri === '/public' || $uri === '/public/') {
    require __DIR__ . '/public/index.php';
    return;
}

if (preg_match('#^/public/(.+\.php)$#', $uri, $matches)) {
    $target = __DIR__ . '/public/' . $matches[1];
    if (file_exists($target)) {
        require $target;
        return;
    }
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/public/index.php';
    return;
}

return false;
