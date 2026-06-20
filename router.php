<?php
// =============================================================
//  AISU Root Router — Serves static files + routes API to backend
//  Usage: php -S localhost:8000 router.php
// =============================================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. If it's an API request, route to backend
if (strpos($uri, '/api/') === 0 || $uri === '/api') {
    require __DIR__ . '/backend-php/index.php';
    return true;
}

// 1b. Root path — serve index.html
if ($uri === '/' || $uri === '') {
    return false; // PHP built-in server serves index.html by default
}

// 2. Try to serve as a static file (HTML, CSS, JS, images, etc.)
$filePath = __DIR__ . $uri;
if (file_exists($filePath) && is_file($filePath)) {
    return false; // Let PHP built-in server handle it
}

// 3. 404
http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Not found']);
return true;
