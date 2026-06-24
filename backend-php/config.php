<?php
// =============================================================
//  AISU Backend — config.php (Configuration)
// =============================================================

// ── Load .env file if present (cPanel deployment) ────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            // Remove surrounding quotes
            if ((strpos($val, '"') === 0 && strrpos($val, '"') === strlen($val)-1) ||
                (strpos($val, "'") === 0 && strrpos($val, "'") === strlen($val)-1)) {
                $val = substr($val, 1, -1);
            }
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
define('UPLOAD_DIR', BASE_DIR . '/uploads');

// JWT Configuration
define('JWT_SECRET', getenv('JWT_SECRET_KEY') ?: 'aisu-jwt-2024-CHANGE-ME');
define('JWT_ACCESS_EXPIRY', 12 * 3600);    // 12 hours
define('JWT_REFRESH_EXPIRY', 30 * 86400);  // 30 days

// SMTP Configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'aisujk.itcell@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'elym edjc zdgb jrnr');
define('FROM_NAME', 'All India Students Union (AISU)');

// Max upload size
define('MAX_UPLOAD_SIZE', 16 * 1024 * 1024); // 16MB

// Allowed file extensions
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'webp']);

// National officer emails
define('NATIONAL_EMAILS', [
    'president'       => 'president.aisu@gmail.com',
    'vice_president'  => 'vicepresident.aisu@gmail.com',
    'gen_secretary'   => 'secretary.aisu@gmail.com',
    'joint_secretary' => 'jointsec.aisu@gmail.com',
    'treasurer'       => 'treasurer.aisu@gmail.com',
    'it_cell'         => 'itcell.aisu@gmail.com',
    'admin'           => 'aisu4india@gmail.com',
]);

// Official organization contact number (shown for national team on public page)
define('ORG_OFFICIAL_PHONE', '+91 80748 53717');

// Razorpay Payment Gateway
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_live_T4D9kRtnj5I36Z');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'RXUERYjRnAb5Y8tuSTLc441B');

// Membership Fee
define('PRIMARY_MEMBERSHIP_FEE', 20);
define('STUDENT_MEMBERSHIP_FEE', 10);

// SQLite Database Configuration
define('DB_PATH', DATA_DIR . '/aisu.sqlite');

// Twilio SMS Configuration (for phone OTP delivery)
define('TWILIO_ACCOUNT_SID', getenv('TWILIO_ACCOUNT_SID') ?: '');
define('TWILIO_AUTH_TOKEN',  getenv('TWILIO_AUTH_TOKEN')  ?: '');
define('TWILIO_PHONE_NUMBER', getenv('TWILIO_PHONE_NUMBER') ?: '+1234567890');

// Ensure required directories exist
$dirs = [
    DATA_DIR,
    UPLOAD_DIR . '/govtid',
    UPLOAD_DIR . '/payment',
    UPLOAD_DIR . '/photo',
    UPLOAD_DIR . '/sign',
    UPLOAD_DIR . '/complaint',
    UPLOAD_DIR . '/cert_templates',
    UPLOAD_DIR . '/certificates',
    UPLOAD_DIR . '/resume',
    UPLOAD_DIR . '/submissions',
    UPLOAD_DIR . '/innovations',
    UPLOAD_DIR . '/press',
    UPLOAD_DIR . '/gallery',
];
foreach ($dirs as $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
}
