<?php
// =============================================================
//  utils.php — Shared helpers (PHP port of utils.py)
// =============================================================

require_once __DIR__ . '/config.php';

/**
 * Check if file extension is allowed.
 */
function allowed_file(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS);
}

/**
 * Save an uploaded file safely. Returns stored filename or null.
 */
function save_upload(?array $fileObj, string $subfolder): ?string {
    if (!$fileObj || $fileObj['error'] !== UPLOAD_ERR_OK) return null;
    if (!allowed_file($fileObj['name'])) return null;

    $ext = strtolower(pathinfo($fileObj['name'], PATHINFO_EXTENSION));
    $fname = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $subfolder . '/' . $fname;

    $dir = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (move_uploaded_file($fileObj['tmp_name'], $dest)) {
        return $fname;
    }
    return null;
}

/**
 * Hash a password using bcrypt.
 */
function hash_password(string $pw): string {
    return password_hash($pw, PASSWORD_BCRYPT);
}

/**
 * Verify a password against a hash.
 */
function check_password(string $pw, string $hashed): bool {
    return password_verify($pw, $hashed);
}

/**
 * Return a success JSON response.
 */
function ok($data = null, string $msg = 'success', int $code = 200): void {
    http_response_code($code);
    $resp = ['success' => true, 'message' => $msg];
    if ($data !== null) $resp['data'] = $data;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Return an error JSON response.
 */
function err(string $msg = 'error', int $code = 400, $details = null): void {
    http_response_code($code);
    $resp = ['success' => false, 'message' => $msg];
    if ($details) $resp['details'] = $details;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validate required fields. Returns array of missing field names.
 */
function validate_required(array $data, array $fields): array {
    $missing = [];
    foreach ($fields as $f) {
        if (empty($data[$f])) $missing[] = $f;
    }
    return $missing;
}

/**
 * Require specific role(s) from JWT. Returns claims or exits with 401/403.
 */
function require_role(string ...$roles): array {
    require_once __DIR__ . '/jwt_handler.php';
    $claims = JWTHandler::verifyRequest();
    if (!$claims) err('Authentication required', 401);
    if (!in_array($claims['role'] ?? '', $roles)) err('Access denied', 403);
    return $claims;
}

/**
 * Verify JWT is present and valid. Returns claims or exits with 401.
 */
function require_auth(): array {
    require_once __DIR__ . '/jwt_handler.php';
    $claims = JWTHandler::verifyRequest();
    if (!$claims) err('Authentication required', 401);
    return $claims;
}

/**
 * Get request body as JSON or form data.
 */
function get_request_data(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?: [];
    }
    return array_merge($_POST, $_GET);
}

/**
 * Get state code from state name.
 */
function state_code(string $stateName): string {
    return DB::stateCode($stateName);
}

/**
 * Generate a certificate file from a template by replacing placeholders.
 * Returns the generated filename or null.
 */
function generate_certificate_file(string $templatePath, array $replacements, string $ext): ?string {
    if (!file_exists($templatePath)) return null;

    $outFilename = bin2hex(random_bytes(16)) . $ext;
    $outPath = UPLOAD_DIR . '/certificates/' . $outFilename;

    $dir = dirname($outPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Copy template to output path
    if (!copy($templatePath, $outPath)) return null;

    // Only attempt string replacement for .docx
    if ($ext === '.docx' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($outPath) === true) {
            $docXml = $zip->getFromName('word/document.xml');
            if ($docXml !== false) {
                // Word often splits text into multiple XML tags.
                // We do a simple string replace. Ensure placeholders are typed without mid-formatting.
                foreach ($replacements as $key => $val) {
                    $docXml = str_replace($key, htmlspecialchars($val, ENT_XML1, 'UTF-8'), $docXml);
                }
                $zip->addFromString('word/document.xml', $docXml);
            }
            $zip->close();
        }
    }

    return $outFilename;
}
