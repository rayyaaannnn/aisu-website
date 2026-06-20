<?php
// =============================================================
//  routes/icell.php — Innovation Cell (PHP port)
//  Frontend: innovations.html
//  Admin panel: Innovation Cell tab
//  API prefix: /api/icell
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

$REQUIRED = ['title','problem_statement','proposed_solution',
             'implementation_plan','expected_impact','required_funds'];

$VALID_STATUSES = ['submitted','under_review','modification_requested','approved','rejected'];

// ── SUBMIT (Logged-in user) ──────────────────────────────────
if ($path === '/submit' && $method === 'POST') {
    $claims = require_auth();
    $data = $_POST;
    $uid  = $claims['sub'];
    $user = DB::findOne('users', '_id', $uid);

    $missing = validate_required($data, $REQUIRED);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $docFile = save_upload($_FILES['supporting_doc'] ?? null, 'innovations');
    $proposalId = DB::genInnovationId();

    $doc = DB::insert('innovations', [
        'proposal_id'          => $proposalId,
        'user_id'              => $uid,
        'applicant_name'       => $user ? ($user['name'] ?? '') : ($data['name'] ?? ''),
        'applicant_email'      => $user ? ($user['email'] ?? '') : ($data['email'] ?? ''),
        'title'                => $data['title'],
        'problem_statement'    => $data['problem_statement'],
        'proposed_solution'    => $data['proposed_solution'],
        'implementation_plan'  => $data['implementation_plan'],
        'expected_impact'      => $data['expected_impact'],
        'required_funds'       => $data['required_funds'],
        'supporting_doc'       => $docFile,
        'status'               => 'submitted',
        'icell_notes'          => '',
        'investor_status'      => '',
        'fund_disbursed'       => false,
    ]);
    ok(['proposal_id' => $proposalId], "Innovation proposal submitted! Reference: $proposalId", 201);
}

// ── LIST (ICell/Admin) ───────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    require_role('national','vp','secretary','icell','it_cell');
    $proposals = DB::findAll('innovations');
    $status = $_GET['status'] ?? null;
    if ($status) $proposals = array_values(array_filter($proposals, fn($p) => ($p['status'] ?? '') === $status));
    ok($proposals);
}

// ── GET MINE ─────────────────────────────────────────────────
if ($path === '/my-proposals' && $method === 'GET') {
    $claims = require_auth();
    $uid  = $claims['sub'];
    $mine = array_values(array_filter(DB::findAll('innovations'), fn($p) => ($p['user_id'] ?? '') === $uid));
    ok($mine);
}

// ── GET ONE ──────────────────────────────────────────────────
if (preg_match('#^/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $claims = require_auth();
    $_id = $matches[1];
    $p = DB::findOne('innovations', '_id', $_id) ?: DB::findOne('innovations', 'proposal_id', $_id);
    if (!$p) err('Not found', 404);
    if (($p['user_id'] ?? '') !== $claims['sub'] &&
        !in_array($claims['role'] ?? '', ['national','vp','secretary','icell'])) {
        err('Access denied', 403);
    }
    ok($p);
}

// ── UPDATE STATUS ────────────────────────────────────────────
if (preg_match('#^/([^/]+)/status$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary','icell');
    $_id = $matches[1];
    $data = get_request_data();
    $newStatus = $data['status'] ?? '';
    if (!in_array($newStatus, $VALID_STATUSES)) err('Status must be one of: ' . implode(', ', $VALID_STATUSES));

    $p = DB::findOne('innovations', '_id', $_id);
    if (!$p) err('Not found', 404);

    DB::updateOne('innovations', $_id, [
        'status'      => $newStatus,
        'icell_notes' => $data['notes'] ?? ($p['icell_notes'] ?? ''),
    ]);
    ok(null, "Proposal status updated to $newStatus");
}

// ── FUND UPDATE ──────────────────────────────────────────────
if (preg_match('#^/([^/]+)/fund-update$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','icell');
    $_id = $matches[1];
    $data = get_request_data();
    $p = DB::findOne('innovations', '_id', $_id);
    if (!$p) err('Not found', 404);

    DB::updateOne('innovations', $_id, [
        'investor_status'    => $data['investor_status'] ?? '',
        'fund_disbursed'     => $data['fund_disbursed'] ?? false,
        'fund_amount'        => $data['fund_amount'] ?? '',
        'fund_account'       => $data['fund_account'] ?? '',
        'fund_transfer_date' => $data['fund_transfer_date'] ?? '',
    ]);
    ok(null, 'Funding details updated');
}

err('Endpoint not found', 404);
