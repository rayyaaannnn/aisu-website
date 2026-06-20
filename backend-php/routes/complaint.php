<?php
// =============================================================
//  routes/complaint.php — Complaint Portal (PHP port)
//  Frontend: complaint.html
//  Admin panel: Complaints tab
//  API prefix: /api/complaints
// =============================================================

require_once __DIR__ . '/../email_service.php';

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

$CATEGORIES = ['Fee & Scholarship','Ragging','Harassment','Academic Issue',
               'Discrimination','Faculty Misconduct','Infrastructure','Other'];

// ── SUBMIT (Must be logged in) ───────────────────────────────
if ($path === '/submit' && $method === 'POST') {
    $claims = require_auth();
    $uid  = $claims['sub'];
    $user = DB::findOne('users', '_id', $uid);
    if (!$user) err('Authentication required', 401);

    $data = $_POST;
    $missing = validate_required($data, ['category','description']);
    if ($missing) err('Missing: ' . implode(', ', $missing));
    if (!in_array($data['category'], $CATEGORIES)) err('Invalid category');

    $proofFile = save_upload($_FILES['proof'] ?? null, 'complaint');
    $cmpltId   = DB::genComplaintId();
    $isAnon    = strtolower($data['is_anonymous'] ?? 'false') === 'true';
    $cmpltName = $isAnon ? 'Anonymous' : (!empty($data['complaint_name']) ? $data['complaint_name'] : ($user['name'] ?? ''));

    $doc = DB::insert('complaints', [
        'complaint_id'    => $cmpltId,
        'user_id'         => $uid,
        'name'            => $cmpltName,
        'mobile'          => $isAnon ? '' : ($user['mobile'] ?? ''),
        'email'           => $isAnon ? '' : ($user['email'] ?? ''),
        'state'           => $data['state'] ?? ($user['state'] ?? ''),
        'district'        => $data['district'] ?? '',
        'institution'     => $data['institution'] ?? '',
        'category'        => $data['category'],
        'description'     => $data['description'],
        'proof_file'      => $proofFile,
        'is_anonymous'    => $isAnon,
        'status'          => 'open',
        'action_log'      => [],
        'assigned_to'     => '',
        'resolution'      => '',
        'is_confidential' => true,
    ]);
    ok(['complaint_id' => $cmpltId], "Complaint registered. Track using ID: $cmpltId", 201);
}

// ── TRACK BY ID (Public) ─────────────────────────────────────
if (preg_match('#^/track/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $complaintId = $matches[1];
    $c = DB::findOne('complaints', 'complaint_id', $complaintId);
    if (!$c) err('Complaint ID not found', 404);

    $updates = array_map(fn($a) => [
        'action'    => $a['action'] ?? '',
        'timestamp' => $a['timestamp'] ?? '',
        'level'     => $a['level'] ?? '',
    ], $c['action_log'] ?? []);

    ok([
        'complaint_id' => $c['complaint_id'],
        'category'     => $c['category'],
        'status'       => $c['status'],
        'created_at'   => $c['created_at'],
        'resolution'   => $c['resolution'] ?? '',
        'updates'      => $updates,
    ]);
}

// ── LIST — tiered access ─────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    $claims = require_auth();
    $role  = $claims['role'] ?? '';
    $allC  = DB::findAll('complaints');
    $status = $_GET['status'] ?? null;
    if ($status) $allC = array_values(array_filter($allC, fn($c) => ($c['status'] ?? '') === $status));

    if (in_array($role, ['national','vp','secretary'])) {
        $result = $allC;
    } elseif (in_array($role, ['state','district','mandal','institutional'])) {
        $subKeys = ['complaint_id','category','status','created_at','description',
                    'proof_file','action_log','resolution','district','institution'];
        $result = [];
        foreach ($allC as $c) {
            if (($c['state'] ?? '') === ($claims['state'] ?? '')) {
                $item = [];
                foreach ($subKeys as $k) $item[$k] = $c[$k] ?? null;
                $result[] = $item;
            }
        }
    } else {
        err('Access denied', 403);
    }
    ok($result, count($result) . ' complaints');
}

// ── UPDATE / ADD ACTION ──────────────────────────────────────
if (preg_match('#^/([^/]+)/update$#', $path, $matches) && $method === 'POST') {
    $claims = require_auth();
    $role = $claims['role'] ?? '';
    $allowed = ['national','vp','secretary','state','district','mandal','institutional'];
    if (!in_array($role, $allowed)) err('Access denied', 403);

    $_id = $matches[1];
    $data = $_POST;
    $c = DB::findOne('complaints', '_id', $_id);
    if (!$c) err('Not found', 404);

    $docFile = save_upload($_FILES['support_doc'] ?? null, 'complaint');
    $actionEntry = [
        'level'     => $role,
        'action'    => $data['action'] ?? '',
        'updater'   => $claims['name'] ?? $claims['sub'],
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'document'  => $docFile,
    ];
    $actionLog = $c['action_log'] ?? [];
    $actionLog[] = $actionEntry;
    $updates = ['action_log' => $actionLog];

    if (isset($data['status'])) {
        $validS = ['open','in_progress','resolved','disposed','invalid'];
        if (!in_array($data['status'], $validS)) err("Invalid status: {$data['status']}");
        $updates['status'] = $data['status'];
    }
    if (isset($data['resolution']))  $updates['resolution']  = $data['resolution'];
    if (isset($data['assigned_to'])) $updates['assigned_to'] = $data['assigned_to'];

    DB::updateOne('complaints', $_id, $updates);

    try {
        $c2 = DB::findOne('complaints', '_id', $_id);
        if (($data['status'] ?? '') === 'disposed') {
            send_complaint_disposed($c2);
        } else {
            send_complaint_update($c2, $data['action'] ?? '', $actionEntry['updater']);
        }
    } catch (Exception $e) {}

    ok(null, 'Complaint updated');
}

// ── DISPOSE ──────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/dispose$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    $_id = $matches[1];
    $data = get_request_data();
    $c = DB::findOne('complaints', '_id', $_id);
    if (!$c) err('Not found', 404);

    DB::updateOne('complaints', $_id, [
        'status'      => 'disposed',
        'resolution'  => $data['resolution'] ?? 'Resolved and disposed.',
        'disposed_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ]);
    try { send_complaint_disposed(DB::findOne('complaints', '_id', $_id)); } catch (Exception $e) {}
    ok(null, 'Complaint disposed');
}

err('Endpoint not found', 404);
