<?php
// =============================================================
//  routes/certs.php — Certificate Issuance & Verification (PHP)
//  Frontend: cert-verify.html
//  Admin panel: Generate / Issue tab
//  API prefix: /api/certs
// =============================================================

require_once __DIR__ . '/../email_service.php';

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── PUBLIC: Verify by Certificate Number ─────────────────────
if (preg_match('#^/verify/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $certNum = $matches[1];
    $c = DB::findOne('certificates', 'cert_number', $certNum)
      ?: DB::findOne('certificates', 'cert_id', $certNum);
    if (!$c) err('Certificate not found or invalid certificate number', 404);

    ok([
        'cert_number'      => $c['cert_number'] ?? $c['cert_id'] ?? '',
        'participant_name' => $c['participant_name'] ?? $c['holder_name'] ?? '',
        'program'          => $c['program'] ?? $c['event'] ?? $c['competition'] ?? '',
        'cert_type'        => $c['cert_type'] ?? $c['type'] ?? 'Participation',
        'issued_at'        => $c['issued_at'] ?? $c['issued_on'] ?? '',
        'status'           => $c['status'] ?? 'valid',
        'issuer'           => 'All India Students Union (AISU)',
        'download_url'     => !empty($c['file']) ? "/api/cert-templates/download/{$c['_id']}" : null,
    ], 'Certificate is VALID');
}

// ── PUBLIC: Search by Mobile / Email ─────────────────────────
if ($path === '/' && $method === 'GET') {
    $identifier = trim($_GET['identifier'] ?? '');
    if ($identifier) {
        $allCerts = DB::findAll('certificates');
        $results = [];
        foreach ($allCerts as $c) {
            $mobile = trim($c['participant_mobile'] ?? $c['mobile'] ?? '');
            $email  = strtolower(trim($c['participant_email'] ?? $c['email'] ?? ''));
            if ($identifier === $mobile || strtolower($identifier) === $email) {
                $results[] = [
                    'cert_number'      => $c['cert_number'] ?? $c['cert_id'] ?? '',
                    'participant_name' => $c['participant_name'] ?? $c['holder_name'] ?? '',
                    'program'          => $c['program'] ?? $c['event'] ?? '',
                    'cert_type'        => $c['cert_type'] ?? 'Participation',
                    'issued_at'        => $c['issued_at'] ?? $c['issued_on'] ?? '',
                    'status'           => $c['status'] ?? 'valid',
                    'download_url'     => !empty($c['file']) ? "/api/cert-templates/download/{$c['_id']}" : null,
                ];
            }
        }
        ok($results, count($results) . ' certificate(s) found');
    }

    // Admin-only: full list
    $claims = JWTHandler::verifyRequest();
    if (!$claims || !in_array($claims['role'] ?? '', ['national','vp','secretary','it'])) {
        err('Use ?identifier=<mobile|email> for public lookup, or authorize as admin', 400);
    }
    ok(DB::findAll('certificates'));
}

// ── ISSUE (Admin) ────────────────────────────────────────────
if ($path === '/issue' && $method === 'POST') {
    $claims = require_role('national','vp','secretary');
    $data = get_request_data();
    $missing = validate_required($data, ['participant_name','cert_type']);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $progCode = strtoupper($data['prog_code'] ?? 'CERT');
    $certNum  = DB::genCertId($progCode);

    $doc = DB::insert('certificates', [
        'cert_number'        => $certNum,
        'participant_name'   => $data['participant_name'],
        'participant_email'  => $data['participant_email'] ?? '',
        'participant_mobile' => $data['participant_mobile'] ?? '',
        'participant_id'     => $data['participant_id'] ?? '',
        'program'            => $data['program'] ?? '',
        'prog_code'          => $progCode,
        'cert_type'          => $data['cert_type'],
        'issued_at'          => gmdate('Y-m-d\TH:i:s\Z'),
        'issued_by'          => $claims['sub'],
        'status'             => 'valid',
        'file'               => $data['file'] ?? '',
    ]);

    try {
        send_certificate_issued(
            $data['participant_email'] ?? '',
            $data['participant_name'],
            $certNum,
            $data['cert_type'],
            $data['program'] ?? ''
        );
    } catch (Exception $e) {}

    ok(['cert_number' => $certNum, '_id' => $doc['_id']], 'Certificate issued', 201);
}

// ── REVOKE (Admin) ───────────────────────────────────────────
if (preg_match('#^/([^/]+)/revoke$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp');
    $_id = $matches[1];
    $c = DB::findOne('certificates', '_id', $_id);
    if (!$c) err('Not found', 404);
    DB::updateOne('certificates', $_id, ['status' => 'revoked']);
    ok(null, 'Certificate revoked');
}

// ── REISSUE (Admin) ──────────────────────────────────────────
if (preg_match('#^/([^/]+)/reissue$#', $path, $matches) && $method === 'POST') {
    $claims = require_role('national','vp','secretary');
    $_id = $matches[1];
    $c = DB::findOne('certificates', '_id', $_id);
    if (!$c) err('Not found', 404);

    $progCode = $c['prog_code'] ?? 'CERT';
    $newNum   = DB::genCertId($progCode);
    DB::updateOne('certificates', $_id, [
        'cert_number' => $newNum,
        'status'      => 'valid',
        'reissued_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'issued_at'   => gmdate('Y-m-d\TH:i:s\Z'),
        'reissued_by' => $claims['sub'],
    ]);
    ok(['new_cert_number' => $newNum], 'Certificate reissued');
}

// ── LIST ALL (Admin) ─────────────────────────────────────────
if ($path === '/all' && $method === 'GET') {
    require_role('national','vp','secretary');
    ok(DB::findAll('certificates'));
}

err('Endpoint not found', 404);
