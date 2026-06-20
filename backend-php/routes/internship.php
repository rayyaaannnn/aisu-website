<?php
// =============================================================
//  routes/internship.php — Internship Applications (PHP port)
//  Frontend: internship.html
//  Admin panel: Internships tab
//  API prefix: /api/internship
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

$REQUIRED = ['fullname','email','mobile','state','institution','course','year','domain','duration'];

// ── APPLY (Public) ───────────────────────────────────────────
if ($path === '/apply' && $method === 'POST') {
    $data = $_POST;
    $missing = validate_required($data, $REQUIRED);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $email = strtolower(trim($data['email']));
    if (DB::findOne('internships', 'email', $email)) err('Application with this email already exists');

    $resume = save_upload($_FILES['resume'] ?? null, 'resume');

    $doc = DB::insert('internships', [
        'fullname'    => $data['fullname'],
        'email'       => $email,
        'mobile'      => $data['mobile'],
        'state'       => $data['state'],
        'institution' => $data['institution'],
        'course'      => $data['course'],
        'year'        => $data['year'],
        'domain'      => $data['domain'],
        'duration'    => $data['duration'],
        'resume'      => $resume,
        'partner'     => $data['partner'] ?? 'SkillChase',
        'status'      => 'pending',
    ]);
    ok(['ref' => $doc['_id']], 'Internship application submitted!', 201);
}

// ── LIST (Admin) ─────────────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    require_role('national','vp','secretary');
    ok(DB::findAll('internships'));
}

// ── APPROVE ──────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/approve$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    DB::updateOne('internships', $matches[1], ['status' => 'approved']);
    ok(null, 'Internship application approved');
}

// ── REJECT ───────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/reject$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    $data = get_request_data();
    DB::updateOne('internships', $matches[1], ['status' => 'rejected', 'reason' => $data['reason'] ?? '']);
    ok(null, 'Rejected');
}

err('Endpoint not found', 404);
