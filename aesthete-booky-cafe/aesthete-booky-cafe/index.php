<?php
// Simple Router for PHP Built-in Server

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Serve API requests
if (strpos($uri, '/api') === 0) {
    // Basic API Router
    require_once __DIR__ . '/api/routes.php';
    exit;
}

// Serve Static files from public directory
$file = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    // Get Mime Type
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'html' => 'text/html',
        'htm' => 'text/html',
        'json' => 'application/json',
        'ico' => 'image/x-icon'
    ];
    
    $mime = isset($mimes[$ext]) ? $mimes[$ext] : 'text/plain';
    header("Content-Type: $mime");
    readfile($file);
    exit;
}

// Serve Index for root
if ($uri === '/' || $uri === '/index.html' || $uri === '/aesthete_book_cafe/') {
    header("Content-Type: text/html");
    readfile(__DIR__ . '/public/index.html');
    exit;
}

// Handle non-extension HTML requests (e.g. /login -> public/login.html)
// Or existing .html requests handled above if they match file exactly.
// If user requests /login.html, it's caught above. 
// If user requests /login, we check login.html
$possible_html = __DIR__ . '/public' . $uri . '.html';
if (file_exists($possible_html)) {
    header("Content-Type: text/html");
    readfile($possible_html);
    exit;
}

// 404
http_response_code(404);
echo "404 Not Found";
