<?php
// =============================================================
//  routes/contact.php — Contact Form (PHP port)
//  Frontend: contact.html
//  Admin panel: (none — contact messages go to email)
//  API prefix: /api/contact
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── SEND (Public) ────────────────────────────────────────────
if ($path === '/send' && $method === 'POST') {
    $data = get_request_data();
    $missing = validate_required($data, ['name','email','subject','message']);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $doc = DB::insert('contacts', [
        'name'    => $data['name'],
        'email'   => strtolower(trim($data['email'])),
        'mobile'  => $data['mobile'] ?? '',
        'subject' => $data['subject'],
        'message' => $data['message'],
        'state'   => $data['state'] ?? '',
        'status'  => 'unread',
    ]);
    ok(['ref' => $doc['_id']], 'Message received! We will get back to you within 48 hours.', 201);
}

// ── LIST (Admin) ─────────────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    $claims = JWTHandler::verifyRequest();
    if (!$claims || !in_array($claims['role'] ?? '', ['national','vp','secretary'])) {
        err('Authentication required', 401);
    }
    ok(DB::findAll('contacts'));
}

// ── MARK READ ────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/read$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    DB::updateOne('contacts', $matches[1], ['status' => 'read']);
    ok(null, 'Marked as read');
}

err('Endpoint not found', 404);
