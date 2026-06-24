<?php
// =============================================================
//  AISU Root Router — Serves static files + routes API to backend
//  Usage (dev):  php -S localhost:8000 router.php
//  Usage (cPanel): Used by .htaccess to route to backend
// =============================================================

// ── Load .env file if present ────────────────────────────────
$envFile = __DIR__ . '/backend-php/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if ((strpos($val, '"') === 0 && strrpos($val, '"') === strlen($val)-1) ||
                (strpos($val, "'") === 0 && strrpos($val, "'") === strlen($val)-1)) {
                $val = substr($val, 1, -1);
            }
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. If it's an API request, route to backend
if (strpos($uri, '/api/') === 0 || $uri === '/api') {
    // Support both root and subdirectory deployments
    $backendFile = __DIR__ . '/backend-php/index.php';
    if (file_exists($backendFile)) {
        require $backendFile;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Backend not found']);
    }
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

// 3. Try serving .html file for clean URLs (e.g. /about → about.html)
if (!preg_match('/\.\w+$/', $uri)) {
    $htmlPath = __DIR__ . $uri . '.html';
    if (file_exists($htmlPath)) {
        return false; // Let PHP built-in server handle it
    }
}

// 4. 404
http_response_code(404);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['success' => false, 'message' => 'Not found']);
return true;
