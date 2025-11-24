<?php

declare(strict_types=1);

/**
 * Simple Router for the Todo Application
 * Routes requests to either the API or serves static files
 */

$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// API routes
if (str_starts_with($requestPath, '/api/')) {
    require_once __DIR__ . '/api.php';
    exit;
}

// Serve static CSS
if ($requestPath === '/styles.css') {
    header('Content-Type: text/css');
    readfile(__DIR__ . '/styles.css');
    exit;
}

// Serve index.html for all other routes
if (file_exists(__DIR__ . '/index.html')) {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/index.html');
    exit;
}

// 404
http_response_code(404);
echo '404 Not Found';
