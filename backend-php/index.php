<?php
// =============================================================
//  AISU Backend — index.php (PHP Router / Entry Point)
//  Mirrors Flask app.py with all blueprint routes
// =============================================================

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/jwt_handler.php';

// ── Parse request ────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path if running in subdirectory
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/' && $basePath !== '\\') {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . ltrim($uri, '/');

// ── Route map ────────────────────────────────────────────────
// Root
if ($uri === '/' && $method === 'GET') {
    echo json_encode([
        'status'  => 'AISU Backend Running (PHP)',
        'version' => '2.1.0-php',
        'docs'    => '/api/health',
    ]);
    exit;
}

// Health + Dashboard Stats
if ($uri === '/api/health' && $method === 'GET') {
    $pm  = DB::findAll('primary_members');
    $sm  = DB::findAll('student_members');
    $cmp = DB::findAll('complaints');
    $crt = DB::findAll('certificates');

    // Status breakdowns
    $pmPending  = count(array_filter($pm, fn($m) => ($m['status'] ?? '') === 'pending'));
    $pmApproved = count(array_filter($pm, fn($m) => ($m['status'] ?? '') === 'approved'));
    $pmRejected = count(array_filter($pm, fn($m) => ($m['status'] ?? '') === 'rejected'));
    $smPending  = count(array_filter($sm, fn($s) => ($s['status'] ?? '') === 'pending'));
    $smApproved = count(array_filter($sm, fn($s) => ($s['status'] ?? '') === 'approved'));
    $cmpPending = count(array_filter($cmp, fn($c) => in_array($c['status'] ?? '', ['pending', 'under_review'])));
    $cmpResolved= count(array_filter($cmp, fn($c) => in_array($c['status'] ?? '', ['resolved', 'closed'])));

    // Payment stats
    $razorpayPayments = 0;
    $qrPayments = 0;
    $pmRazorpay = 0; $pmQr = 0;
    $smRazorpay = 0; $smQr = 0;
    foreach ($pm as $m) {
        if (!empty($m['razorpay_payment_id'])) { $razorpayPayments++; $pmRazorpay++; }
        if (!empty($m['payment_proof'])) { $qrPayments++; $pmQr++; }
    }
    foreach ($sm as $s) {
        if (!empty($s['razorpay_payment_id'])) { $razorpayPayments++; $smRazorpay++; }
        if (!empty($s['payment_proof'])) { $qrPayments++; $smQr++; }
    }
    $totalRevenue = ($pmRazorpay + $pmQr) * PRIMARY_MEMBERSHIP_FEE + ($smRazorpay + $smQr) * STUDENT_MEMBERSHIP_FEE;

    // Server info
    $phpVersion = phpversion();
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Built-in PHP Server';
    $serverTime = gmdate('Y-m-d\TH:i:s\Z');

    // Disk / storage info
    $dataDir = DATA_DIR;
    $diskFree = @disk_free_space($dataDir) ?: 0;
    $diskTotal = @disk_total_space($dataDir) ?: 0;
    $diskUsedPercent = $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100, 1) : 0;

    // Count total JSON data files and their sizes
    $dataFiles = glob($dataDir . '/*.json');
    $dataFileCount = count($dataFiles);
    $dataSizeBytes = 0;
    foreach ($dataFiles as $df) {
        $dataSizeBytes += filesize($df);
    }

    // Expiring memberships (within 90 days)
    $expiringCount = 0;
    $now = new DateTime();
    foreach ($pm as $m) {
        if (($m['status'] ?? '') === 'approved' && !empty($m['expiry_date'])) {
            try {
                $exp = new DateTime($m['expiry_date']);
                $days = (int) $now->diff($exp)->format('%r%a');
                if ($days >= 0 && $days <= 90) $expiringCount++;
            } catch (Exception $e) {}
        }
    }
    foreach ($sm as $s) {
        if (($s['status'] ?? '') === 'approved' && !empty($s['expiry_date'])) {
            try {
                $exp = new DateTime($s['expiry_date']);
                $days = (int) $now->diff($exp)->format('%r%a');
                if ($days >= 0 && $days <= 90) $expiringCount++;
            } catch (Exception $e) {}
        }
    }

    // Admin user count
    $allUsers = DB::findAll('users');
    $adminUsers = count(array_filter($allUsers, fn($u) => in_array($u['role'] ?? '', ['national','vp','secretary','treasurer','state','district','mandal','institutional'])));

    echo json_encode([
        'status'      => 'ok',
        'service'     => 'AISU API v2.1 (PHP)',
        'collections' => [
            'primary_members' => count($pm),
            'student_members' => count($sm),
            'complaints'      => count($cmp),
            'competitions'    => DB::count('competitions'),
            'certificates'    => count($crt),
            'cert_templates'  => DB::count('cert_templates'),
            'internships'     => DB::count('internships'),
            'innovations'     => DB::count('innovations'),
            'affiliations'    => DB::count('affiliations'),
            'users'           => count($allUsers),
        ],
        'breakdown' => [
            'pm_pending'   => $pmPending,
            'pm_approved'  => $pmApproved,
            'pm_rejected'  => $pmRejected,
            'sm_pending'   => $smPending,
            'sm_approved'  => $smApproved,
            'cmp_pending'  => $cmpPending,
            'cmp_resolved' => $cmpResolved,
        ],
        'server' => [
            'php_version'     => $phpVersion,
            'server_software' => $serverSoftware,
            'server_time'     => $serverTime,
            'status'          => 'online',
        ],
        'database' => [
            'collections'    => $dataFileCount,
            'total_records'  => array_sum([
                count($pm), count($sm), count($cmp), count($crt),
                DB::count('competitions'), DB::count('internships'),
                DB::count('innovations'), DB::count('affiliations'),
                count($allUsers), DB::count('cert_templates'),
                DB::count('contacts'), DB::count('newsletter'),
            ]),
            'data_size_kb' => round($dataSizeBytes / 1024, 1),
            'status'       => 'connected',
        ],
        'storage' => [
            'disk_free_kb'     => round($diskFree / 1024, 0),
            'disk_total_kb'    => round($diskTotal / 1024, 0),
            'disk_used_pct'    => $diskUsedPercent,
            'data_files_count' => $dataFileCount,
        ],
        'payments' => [
            'razorpay_count' => $razorpayPayments,
            'qr_count'       => $qrPayments,
            'total_payments' => $razorpayPayments + $qrPayments,
            'estimated_revenue' => $totalRevenue,
        ],
        'system' => [
            'expiring_memberships' => $expiringCount,
            'admin_users'           => $adminUsers,
            'upload_dirs'           => count(glob(UPLOAD_DIR . '/*', GLOB_ONLYDIR)),
        ],
    ]);
    exit;
}

// ── Route to blueprints ──────────────────────────────────────
$routes = [
    '/api/auth'           => 'routes/auth.php',
    '/api/members'        => 'routes/members.php',
    '/api/students'       => 'routes/students.php',
    '/api/complaints'     => 'routes/complaint.php',
    '/api/contact'        => 'routes/contact.php',
    '/api/internship'     => 'routes/internship.php',
    '/api/affiliation'    => 'routes/affiliation.php',
    '/api/certs'          => 'routes/certs.php',
    '/api/admin'          => 'routes/admin.php',
    '/api/competitions'   => 'routes/competition.php',
    '/api/icell'          => 'routes/icell.php',
    '/api/quiz'           => 'routes/quiz.php',
    '/api/newsletter'     => 'routes/newsletter.php',
    '/api/cert-templates' => 'routes/cert_templates.php',
    '/api/rbac'           => 'routes/rbac.php',
    '/api/payment'        => 'routes/payment.php',
];

foreach ($routes as $prefix => $file) {
    if (strpos($uri, $prefix) === 0) {
        // Extract sub-path after prefix
        $subPath = substr($uri, strlen($prefix)) ?: '/';
        if ($subPath === '') $subPath = '/';

        $routeFile = __DIR__ . '/' . $file;
        if (file_exists($routeFile)) {
            // Make $subPath and $method available to route files
            $GLOBALS['SUB_PATH'] = $subPath;
            $GLOBALS['METHOD']   = $method;
            require $routeFile;
        } else {
            err('Route handler not found', 500);
        }
        exit;
    }
}

// ── 404 ──────────────────────────────────────────────────────
http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
