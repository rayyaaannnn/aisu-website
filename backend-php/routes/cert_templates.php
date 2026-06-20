<?php
// =============================================================
//  routes/cert_templates.php — Certificate Template Mgmt (PHP)
//  Frontend: (admin only)
//  Admin panel: Templates tab, Generate / Issue tab
//  API prefix: /api/cert-templates
// =============================================================

require_once __DIR__ . '/../email_service.php';

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

$UPLOAD_DIR_TPL = UPLOAD_DIR . '/cert_templates';
$CERT_OUT       = UPLOAD_DIR . '/certificates';
$ALLOWED_EXT    = ['.docx','.pdf','.pptx','.png','.jpg','.jpeg'];

// ── Upload a certificate template ────────────────────────────
if ($path === '/templates' && $method === 'POST') {
    $claims = require_auth();
    if (!in_array($claims['role'] ?? '', ['national','admin'])) err('Unauthorized', 403);

    $name      = trim($_POST['name'] ?? '');
    $progCode  = strtoupper(trim($_POST['prog_code'] ?? 'COMP'));
    $desc      = $_POST['description'] ?? '';
    $file      = $_FILES['template_file'] ?? null;

    if (!$file || !$name) err('Template name and file are required');

    $ext = strtolower('.' . pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXT)) err('Unsupported format. Allowed: ' . implode(', ', $ALLOWED_EXT));

    $filename = bin2hex(random_bytes(16)) . $ext;
    $filepath = $UPLOAD_DIR_TPL . '/' . $filename;
    move_uploaded_file($file['tmp_name'], $filepath);

    $tpl = DB::insert('cert_templates', [
        'name'         => $name,
        'prog_code'    => $progCode,
        'description'  => $desc,
        'filename'     => $filename,
        'ext'          => $ext,
        'uploaded_by'  => $claims['email'] ?? '',
        'placeholders' => [],
    ]);
    ok($tpl, 'Template uploaded successfully', 201);
}

// ── List templates ───────────────────────────────────────────
if ($path === '/templates' && $method === 'GET') {
    require_auth();
    ok(DB::findAll('cert_templates'));
}

// ── Get single template ──────────────────────────────────────
if (preg_match('#^/templates/([^/]+)$#', $path, $matches) && $method === 'GET') {
    require_auth();
    $tpl = DB::findOne('cert_templates', '_id', $matches[1]);
    if (!$tpl) err('Template not found', 404);
    ok($tpl);
}

// ── Delete template ──────────────────────────────────────────
if (preg_match('#^/templates/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
    $claims = require_auth();
    if (!in_array($claims['role'] ?? '', ['national','admin'])) err('Unauthorized', 403);
    $tid = $matches[1];
    $tpl = DB::findOne('cert_templates', '_id', $tid);
    if (!$tpl) err('Template not found', 404);
    $fpath = $UPLOAD_DIR_TPL . '/' . ($tpl['filename'] ?? '');
    if (file_exists($fpath)) unlink($fpath);
    DB::deleteOne('cert_templates', $tid);
    ok(null, 'Template deleted');
}

// ── Generate certificates ────────────────────────────────────
if ($path === '/generate' && $method === 'POST') {
    $claims = require_auth();
    if (!in_array($claims['role'] ?? '', ['national','admin'])) err('Unauthorized', 403);

    $data         = get_request_data();
    $tid          = $data['template_id'] ?? null;
    $progCode     = strtoupper($data['prog_code'] ?? 'COMP');
    $mode         = $data['mode'] ?? 'manual';
    $participants = $data['participants'] ?? [];

    // Automatic mode
    if ($mode === 'automatic' && !empty($data['competition_id'])) {
        $comp = DB::findOne('competitions', '_id', $data['competition_id']);
        if (!$comp) err('Competition not found', 404);
        $registrations = DB::findMany('competition_registrations', 'comp_id', $data['competition_id']);
        $participants = array_map(fn($r) => [
            'name'    => $r['name'] ?? '',
            'email'   => $r['email'] ?? '',
            'program' => $comp['title'] ?? '',
        ], $registrations);
    }

    if (empty($participants)) err('No participants provided');

    $templateDoc = DB::findOne('cert_templates', '_id', $tid);
    
    $generated = [];
    foreach ($participants as $p) {
        $certId  = DB::genCertId($progCode);
        $certNum = $certId;

        $replacements = [
            '{{CertificateNo}}'   => $certNum,
            '{{ParticipantName}}' => $p['name'] ?? '',
            '{{Program}}'         => $p['program'] ?? '',
            '{{Date}}'            => gmdate('d F Y'),
            '{{Email}}'           => $p['email'] ?? '',
        ];
        foreach (($p['extra'] ?? []) as $k => $v) {
            $replacements["{{{$k}}}"] = (string) $v;
        }

        $genFilename = null;
        if ($templateDoc && !empty($templateDoc['filename'])) {
            $templatePath = $UPLOAD_DIR_TPL . '/' . $templateDoc['filename'];
            $genFilename = generate_certificate_file($templatePath, $replacements, $templateDoc['ext']);
        }

        $certRecord = DB::insert('certificates', [
            'cert_number'        => $certNum,
            'participant_name'   => $p['name'] ?? '',
            'participant_email'  => $p['email'] ?? '',
            'program'            => $p['program'] ?? '',
            'prog_code'          => $progCode,
            'cert_type'          => $p['cert_type'] ?? 'Participation',
            'template_id'        => $tid,
            'filename'           => $genFilename,
            'replacements'       => $replacements,
            'status'             => 'issued',
            'issued_at'          => gmdate('Y-m-d\TH:i:s\Z'),
        ]);

        $generated[] = [
            'cert_number' => $certNum,
            'name'        => $p['name'] ?? '',
            'id'          => $certRecord['_id'],
        ];

        try {
            send_certificate_issued($p['email'] ?? '', $p['name'] ?? '', $certNum, $progCode, $p['program'] ?? '');
        } catch (Exception $e) {}
    }

    ok([
        'generated'    => count($generated),
        'certificates' => $generated,
    ], count($generated) . ' certificates generated successfully');
}

// ── Download certificate ─────────────────────────────────────
if (preg_match('#^/download/([^/]+)$#', $path, $matches) && $method === 'GET') {
    require_auth();
    $cert = DB::findOne('certificates', '_id', $matches[1]);
    if (!$cert) err('Certificate not found', 404);
    if (!empty($cert['filename'])) {
        $fpath = $CERT_OUT . '/' . $cert['filename'];
        if (file_exists($fpath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $cert['filename'] . '"');
            readfile($fpath);
            exit;
        }
    }
    err('Certificate file not yet generated. Contact admin.', 404);
}

// ── Upload participants via Excel ────────────────────────────
if ($path === '/participants/upload' && $method === 'POST') {
    $claims = require_auth();
    if (!in_array($claims['role'] ?? '', ['national','admin'])) err('Unauthorized', 403);

    $file = $_FILES['excel_file'] ?? null;
    if (!$file) err('Excel file required');

    $ext = strtolower('.' . pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['.xlsx','.xls','.csv'])) {
        err('Only .xlsx, .xls, .csv files supported');
    }

    // CSV parsing (basic — for xlsx, use PhpSpreadsheet)
    if ($ext === '.csv') {
        $handle = fopen($file['tmp_name'], 'r');
        $headers = fgetcsv($handle);
        $participants = [];
        while (($row = fgetcsv($handle)) !== false) {
            $p = [];
            foreach ($headers as $i => $h) {
                $p[trim($h)] = trim($row[$i] ?? '');
            }
            if (array_filter($p)) $participants[] = $p;
        }
        fclose($handle);
        ok(['count' => count($participants), 'participants' => $participants, 'headers' => $headers],
           count($participants) . ' participants loaded');
    }

    err('For .xlsx/.xls files, install PhpSpreadsheet. CSV files are supported out of the box.');
}

err('Endpoint not found', 404);
