<?php
// =============================================================
//  routes/newsletter.php — Newsletter Subscriptions (PHP port)
//  Frontend: footer newsletter forms, press subscription form
//  API prefix: /api/newsletter
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── SUBSCRIBE (Public) ───────────────────────────────────────
if ($path === '/subscribe' && $method === 'POST') {
    $data = get_request_data();
    $missing = validate_required($data, ['email']);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $email = strtolower(trim($data['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) err('Invalid email address');

    $existing = DB::findOne('newsletter_subscriptions', 'email', $email);
    if ($existing) {
        ok(['ref' => $existing['_id']], 'Already subscribed');
    }

    $doc = DB::insert('newsletter_subscriptions', [
        'email'   => $email,
        'name'    => trim($data['name'] ?? ''),
        'source'  => trim($data['source'] ?? 'website'),
        'status'  => 'subscribed',
    ]);

    ok(['ref' => $doc['_id']], 'Subscribed successfully!', 201);
}

// ── LIST (Admin) ─────────────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    require_role('national','vp','secretary');
    ok(DB::findAll('newsletter_subscriptions'));
}

err('Endpoint not found', 404);
