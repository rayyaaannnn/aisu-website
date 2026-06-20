<?php
// =============================================================
//  routes/affiliation.php — Organization Affiliation (PHP port)
//  Frontend: affiliation.html
//  Admin panel: Affiliations tab
//  API prefix: /api/affiliation
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

$REQUIRED = ['org_name','contact_name','email','mobile','state','district','address','org_type'];

// ── APPLY (Public) ───────────────────────────────────────────
if ($path === '/apply' && $method === 'POST') {
    $data = $_POST;
    $missing = validate_required($data, $REQUIRED);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $email = strtolower(trim($data['email']));
    if (DB::findOne('affiliations', 'email', $email)) err('Application with this email already exists');

    $regDoc = save_upload($_FILES['reg_document'] ?? null, 'govtid');

    $doc = DB::insert('affiliations', [
        'org_name'     => $data['org_name'],
        'contact_name' => $data['contact_name'],
        'email'        => $email,
        'mobile'       => $data['mobile'],
        'state'        => $data['state'],
        'district'     => $data['district'],
        'address'      => $data['address'],
        'org_type'     => $data['org_type'],
        'reg_number'   => $data['reg_number'] ?? '',
        'website'      => $data['website'] ?? '',
        'reg_document' => $regDoc,
        'status'       => 'pending',
    ]);
    ok(['ref' => $doc['_id']], 'Affiliation application submitted!', 201);
}

// ── LIST (Admin) ─────────────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    require_role('national','vp','secretary');
    ok(DB::findAll('affiliations'));
}

// ── APPROVE ──────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/approve$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp');
    DB::updateOne('affiliations', $matches[1], ['status' => 'approved']);
    ok(null, 'Affiliation approved');
}

// ── REJECT ───────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/reject$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp');
    $data = get_request_data();
    DB::updateOne('affiliations', $matches[1], ['status' => 'rejected', 'reason' => $data['reason'] ?? '']);
    ok(null, 'Rejected');
}

err('Endpoint not found', 404);
