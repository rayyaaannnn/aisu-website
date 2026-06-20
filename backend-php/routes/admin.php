<?php
// =============================================================
//  routes/admin.php — Admin Dashboard, User Mgmt (PHP port)
//  Frontend: index.html (announcements), press.html, gallery.html
//  Admin panel: Dashboard, Announcements, Press/Gallery, Admin Users
//  API prefix: /api/admin
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── DASHBOARD STATS ──────────────────────────────────────────
if ($path === '/stats' && $method === 'GET') {
    require_role('national','vp','secretary');
    $members     = DB::findAll('primary_members');
    $students    = DB::findAll('student_members');
    $complaints  = DB::findAll('complaints');
    $contacts    = DB::findAll('contacts');
    $certs       = DB::findAll('certificates');
    $internships = DB::findAll('internships');
    $affiliations= DB::findAll('affiliations');
    ok([
        'primary_members' => [
            'total'    => count($members),
            'pending'  => count(array_filter($members, fn($m) => ($m['status'] ?? '') === 'pending')),
            'approved' => count(array_filter($members, fn($m) => ($m['status'] ?? '') === 'approved')),
            'rejected' => count(array_filter($members, fn($m) => ($m['status'] ?? '') === 'rejected')),
        ],
        'student_members' => [
            'total'    => count($students),
            'pending'  => count(array_filter($students, fn($s) => ($s['status'] ?? '') === 'pending')),
            'approved' => count(array_filter($students, fn($s) => ($s['status'] ?? '') === 'approved')),
        ],
        'complaints' => [
            'total'       => count($complaints),
            'open'        => count(array_filter($complaints, fn($c) => ($c['status'] ?? '') === 'open')),
            'in_progress' => count(array_filter($complaints, fn($c) => ($c['status'] ?? '') === 'in_progress')),
            'resolved'    => count(array_filter($complaints, fn($c) => ($c['status'] ?? '') === 'resolved')),
        ],
        'contacts'     => count($contacts),
        'certificates' => count($certs),
        'internships'  => count($internships),
        'affiliations' => count($affiliations),
    ]);
}

// ── LIST USERS ───────────────────────────────────────────────
if ($path === '/users' && $method === 'GET') {
    require_role('national');
    $users = DB::findAll('users');
    foreach ($users as &$u) unset($u['password']);
    ok($users);
}

// ── CREATE USER ──────────────────────────────────────────────
if ($path === '/users/create' && $method === 'POST') {
    require_role('national');
    $data = get_request_data();
    $missing = validate_required($data, ['name','email','password','role']);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $validRoles = ['national','vp','secretary','treasurer','state','member'];
    if (!in_array($data['role'], $validRoles)) err('Invalid role');
    if (DB::findOne('users', 'email', strtolower(trim($data['email'])))) err('User with this email already exists');

    $doc = DB::insert('users', [
        'name'        => $data['name'],
        'email'       => strtolower(trim($data['email'])),
        'password'    => hash_password($data['password']),
        'role'        => $data['role'],
        'state'       => $data['state'] ?? '',
        'designation' => $data['designation'] ?? '',
        'status'      => 'active',
    ]);
    unset($doc['password']);
    ok($doc, 'User created', 201);
}

// ── UPDATE USER ──────────────────────────────────────────────
if (preg_match('#^/users/([^/]+)$#', $path, $matches) && $method === 'PUT') {
    require_role('national');
    $_id = $matches[1];
    $data = get_request_data();
    $updates = [];
    foreach (['name','role','state','designation','status'] as $f) {
        if (isset($data[$f])) $updates[$f] = $data[$f];
    }
    if (!empty($data['password'])) $updates['password'] = hash_password($data['password']);
    if (!$updates) err('No fields to update');
    $updated = DB::updateOne('users', $_id, $updates);
    if (!$updated) err('User not found', 404);
    unset($updated['password']);
    ok($updated, 'User updated');
}

// ── DEACTIVATE USER ──────────────────────────────────────────
if (preg_match('#^/users/([^/]+)/deactivate$#', $path, $matches) && $method === 'POST') {
    require_role('national');
    $_id = $matches[1];
    $u = DB::findOne('users', '_id', $_id);
    if (!$u) err('User not found', 404);
    DB::updateOne('users', $_id, ['status' => 'inactive']);
    ok(null, 'User deactivated');
}

// ── SEARCH ───────────────────────────────────────────────────
if ($path === '/search' && $method === 'GET') {
    require_role('national','vp','secretary');
    $q = strtolower(trim($_GET['q'] ?? ''));
    if (strlen($q) < 2) err('Search query too short');

    $match = function($doc, $q) {
        foreach ($doc as $v) {
            if (is_string($v) && stripos($v, $q) !== false) return true;
        }
        return false;
    };

    ok([
        'primary_members' => array_values(array_filter(DB::findAll('primary_members'), fn($m) => $match($m, $q))),
        'student_members' => array_values(array_filter(DB::findAll('student_members'), fn($s) => $match($s, $q))),
        'complaints'      => array_values(array_filter(DB::findAll('complaints'), fn($c) => $match($c, $q))),
    ]);
}

// ── ANNOUNCEMENTS ────────────────────────────────────────────
if ($path === '/announcements' && $method === 'GET') {
    $anns = DB::loadCollection('announcements');
    ok($anns, count($anns) . ' announcements');
}

if ($path === '/announcements' && $method === 'POST') {
    $claims = require_auth();
    if (!in_array($claims['role'] ?? '', ['national','state','district'])) err('Unauthorized', 403);
    $data = get_request_data();
    $anns = DB::loadCollection('announcements');
    $ann = [
        '_id'       => DB::makeId(),
        'title'     => $data['title'] ?? '',
        'type'      => $data['type'] ?? 'General',
        'content'   => $data['content'] ?? '',
        'link'      => $data['link'] ?? '',
        'posted_at' => gmdate('Y-m-d\TH:i:s'),
        'posted_by' => $claims['sub'],
    ];
    array_unshift($anns, $ann);
    DB::saveCollection('announcements', $anns);
    ok($ann, 'Announcement created', 201);
}

if (preg_match('#^/announcements/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
    require_auth();
    $annId = $matches[1];
    $anns = DB::loadCollection('announcements');
    $anns = array_values(array_filter($anns, fn($a) => ($a['_id'] ?? '') !== $annId));
    DB::saveCollection('announcements', $anns);
    ok(null, 'Announcement deleted');
}

// ── PRESS / GALLERY ──────────────────────────────────────────
if ($path === '/press' && $method === 'GET') {
    $items = DB::loadCollection('press_releases');
    ok($items, count($items) . ' press releases');
}

if ($path === '/press' && $method === 'POST') {
    require_auth();
    // Support both JSON and multipart form-data (for PDF uploads)
    $data = !empty($_POST) ? $_POST : get_request_data();
    $pdfFile = save_upload($_FILES['pdf_file'] ?? null, 'press');
    $items = DB::loadCollection('press_releases');
    $item = [
        '_id'        => DB::makeId(),
        'title'      => $data['title'] ?? '',
        'type'       => $data['type'] ?? 'Press Release',
        'source'     => $data['source'] ?? '',
        'content'    => $data['content'] ?? '',
        'event_date' => $data['event_date'] ?? '',
        'location'   => $data['location'] ?? '',
        'pdf_file'   => $pdfFile,
        'posted_at'  => gmdate('Y-m-d\TH:i:s'),
    ];
    array_unshift($items, $item);
    DB::saveCollection('press_releases', $items);
    ok($item, 'Press release created', 201);
}

// ── GALLERY ──────────────────────────────────────────────────
if ($path === '/gallery' && $method === 'GET') {
    $items = DB::loadCollection('gallery');
    ok($items, count($items) . ' gallery items');
}

if ($path === '/gallery' && $method === 'POST') {
    require_auth();
    $data = !empty($_POST) ? $_POST : get_request_data();
    $imageFile = save_upload($_FILES['image'] ?? null, 'gallery');
    $items = DB::loadCollection('gallery');
    $item = [
        '_id'        => DB::makeId(),
        'title'      => $data['title'] ?? '',
        'category'   => $data['category'] ?? 'General',
        'event_name' => $data['event_name'] ?? '',
        'event_date' => $data['event_date'] ?? '',
        'image'      => $imageFile,
        'uploaded_at' => gmdate('Y-m-d\TH:i:s'),
    ];
    array_unshift($items, $item);
    DB::saveCollection('gallery', $items);
    ok($item, 'Gallery item added', 201);
}

if (preg_match('#^/gallery/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
    require_auth();
    $gid = $matches[1];
    $items = DB::loadCollection('gallery');
    $items = array_values(array_filter($items, fn($g) => ($g['_id'] ?? '') !== $gid));
    DB::saveCollection('gallery', $items);
    ok(null, 'Gallery item deleted');
}

// ── RENEWALS ─────────────────────────────────────────────────
if ($path === '/renewals/primary' && $method === 'GET') {
    require_auth();
    $members = DB::loadCollection('primary_members');
    $expiring = [];
    $now = new DateTime();
    foreach ($members as $m) {
        if (($m['status'] ?? '') === 'approved' && !empty($m['expiry_date'])) {
            try {
                $exp = new DateTime($m['expiry_date']);
                $days = (int) $now->diff($exp)->format('%r%a');
                if ($days <= 90) {
                    $m['days_to_expiry'] = $days;
                    $expiring[] = $m;
                }
            } catch (Exception $e) {}
        }
    }
    ok($expiring, count($expiring) . ' expiring memberships');
}

if (preg_match('#^/renewals/([^/]+)/process$#', $path, $matches) && $method === 'POST') {
    require_auth();
    $memberId = $matches[1];
    $data = get_request_data();
    $mtype = $data['type'] ?? 'primary';
    $coll  = $mtype === 'primary' ? 'primary_members' : 'student_members';
    $members = DB::loadCollection($coll);
    foreach ($members as &$m) {
        if (($m['_id'] ?? '') === $memberId || ($m['member_id'] ?? '') === $memberId) {
            $m['status'] = 'active';
            $m['renewed_at'] = gmdate('Y-m-d\TH:i:s');
            $years = $mtype === 'primary' ? 3 : 1;
            $expiry = new DateTime();
            $expiry->modify("+{$years} years");
            $m['expiry_date'] = $expiry->format('Y-m-d\TH:i:s');
            DB::saveCollection($coll, $members);
            ok($m, 'Renewal processed');
        }
    }
    err('Member not found', 404);
}

// ── APPOINTMENT ORDERS ───────────────────────────────────────
if (preg_match('#^/appointment/([^/]+)$#', $path, $matches) && $method === 'POST') {
    require_auth();
    $memberId = $matches[1];
    $data = get_request_data();
    $members = DB::loadCollection('primary_members');
    foreach ($members as &$m) {
        if (($m['_id'] ?? '') === $memberId || ($m['member_id'] ?? '') === $memberId) {
            $m['designation']       = $data['designation'] ?? ($m['designation'] ?? '');
            $m['department']        = $data['department'] ?? ($m['department'] ?? '');
            $m['appointment_date']  = gmdate('Y-m-d\TH:i:s');
            $m['appointment_order'] = $data['order_text'] ?? '';
            DB::saveCollection('primary_members', $members);
            ok($m, 'Appointment order issued');
        }
    }
    err('Member not found', 404);
}

err('Endpoint not found', 404);
