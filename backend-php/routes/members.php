<?php
// =============================================================
//  routes/members.php — Primary Membership (PHP port)
//  Frontend: primary-membership.html, team.html (/directory)
//  Admin panel: Primary Members tab
//  API prefix: /api/members
// =============================================================

require_once __DIR__ . '/../email_service.php';

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

$REQUIRED = ['fullname','parent_name','dob','age','gender','address','pin',
             'institution','state','district','city','mobile','email',
             'govtid_type','govtid_number','heard_about','contribution','mode_of_submission'];

// ── SUBMIT (Public) ──────────────────────────────────────────
if ($path === '/apply' && $method === 'POST') {
    $data = $_POST;
    $missing = validate_required($data, $REQUIRED);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $mobile = trim($data['mobile']);
    if (!ctype_digit($mobile) || strlen($mobile) !== 10) err('Mobile must be 10 digits');

    $email = strtolower(trim($data['email']));
    if (DB::findOne('primary_members', 'email', $email)) err('Application with this email already exists');
    if (DB::findOne('primary_members', 'mobile', $mobile)) err('Application with this mobile already exists');

    $govtidFile  = save_upload($_FILES['govtid_file'] ?? null, 'govtid');
    $paymentFile = save_upload($_FILES['payment_proof'] ?? null, 'payment');
    $photoFile   = save_upload($_FILES['photo'] ?? null, 'photo');
    $signFile    = save_upload($_FILES['sign'] ?? null, 'sign');

    $memberId = DB::genMemberId($data['state']);

    $doc = DB::insert('primary_members', [
        'member_id'          => $memberId,
        'fullname'           => strtoupper($data['fullname']),
        'parent_name'        => $data['parent_name'] ?? '',
        'dob'                => $data['dob'] ?? '',
        'age'                => $data['age'] ?? '',
        'gender'             => $data['gender'] ?? '',
        'address'            => $data['address'] ?? '',
        'pin'                => $data['pin'] ?? '',
        'institution'        => $data['institution'] ?? '',
        'state'              => $data['state'] ?? '',
        'district'           => $data['district'] ?? '',
        'city'               => $data['city'] ?? '',
        'mobile'             => $mobile,
        'email'              => $email,
        'govtid_type'        => $data['govtid_type'] ?? '',
        'govtid_number'      => $data['govtid_number'] ?? '',
        'govtid_file'        => $govtidFile,
        'payment_proof'      => $paymentFile,
        'photo'              => $photoFile,
        'sign'               => $signFile,
        'heard_about'        => $data['heard_about'] ?? '',
        'contribution'       => $data['contribution'] ?? '',
        'justify_answers'    => $data['justify_answers'] ?? '{}',
        'mode_of_submission'  => $data['mode_of_submission'] ?? '',
        'razorpay_payment_id' => $data['razorpay_payment_id'] ?? '',
        'designation'         => '',
        'role_status'         => 'pending',
        'approved_by'         => '',
        'approved_at'         => '',
        'expiry_date'         => '',
        'reports'             => [],
    ]);

    try { send_primary_application_received($doc); } catch (Exception $e) {}

    ok(['application_ref' => $doc['_id'], 'member_id' => $memberId],
       'Application submitted! Your AISU Member ID will be emailed after approval.', 201);
}

// ── CSV EXPORT (Admin) ─────────────────────────────────────
if ($path === '/export/csv' && $method === 'GET') {
    require_role('national','vp','secretary');
    $allM = DB::findAll('primary_members');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="primary_members_' . gmdate('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM for Excel

    $headers = ['Member ID', 'Full Name', 'Parent Name', 'DOB', 'Age', 'Gender', 'Address', 'PIN',
                'Institution', 'State', 'District', 'City', 'Mobile', 'Email',
                'Govt ID Type', 'Heard About', 'Contribution', 'Designation', 'Level',
                'Status', 'Role Status', 'Razorpay Payment ID', 'Approved At', 'Expiry Date', 'Created At'];
    fputcsv($output, $headers);

    foreach ($allM as $m) {
        fputcsv($output, [
            $m['member_id'] ?? '',
            $m['fullname'] ?? '',
            $m['parent_name'] ?? '',
            $m['dob'] ?? '',
            $m['age'] ?? '',
            $m['gender'] ?? '',
            $m['address'] ?? '',
            $m['pin'] ?? '',
            $m['institution'] ?? '',
            $m['state'] ?? '',
            $m['district'] ?? '',
            $m['city'] ?? '',
            $m['mobile'] ?? '',
            $m['email'] ?? '',
            $m['govtid_type'] ?? '',
            $m['heard_about'] ?? '',
            $m['contribution'] ?? '',
            $m['designation'] ?? '',
            $m['level'] ?? '',
            $m['status'] ?? '',
            $m['role_status'] ?? '',
            $m['razorpay_payment_id'] ?? '',
            $m['approved_at'] ?? '',
            $m['expiry_date'] ?? '',
            $m['created_at'] ?? '',
        ]);
    }
    fclose($output);
    exit;
}

// ── LIST (Admin) ─────────────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    $claims = require_role('national','vp','secretary','state','district');
    $allM = DB::findAll('primary_members');
    $role = $claims['role'] ?? '';

    if ($role === 'state') {
        $allM = array_values(array_filter($allM, fn($m) => ($m['state'] ?? '') === ($claims['state'] ?? '')));
    } elseif ($role === 'district') {
        $allM = array_values(array_filter($allM, fn($m) => ($m['district'] ?? '') === ($claims['district'] ?? '')));
    }

    $status = $_GET['status'] ?? null;
    $state  = $_GET['state'] ?? null;
    if ($status) $allM = array_values(array_filter($allM, fn($m) => ($m['status'] ?? '') === $status));
    if ($state)  $allM = array_values(array_filter($allM, fn($m) => ($m['state'] ?? '') === $state));

    foreach ($allM as &$m) {
        unset($m['govtid_number'], $m['sign'], $m['justify_answers']);
    }
    ok($allM, count($allM) . ' records');
}

// ── PUBLIC DIRECTORY ─────────────────────────────────────────
if ($path === '/directory' && $method === 'GET') {
    $allM = DB::findAll('primary_members');
    $visible = array_filter($allM, fn($m) =>
        (($m['status'] ?? '') === 'approved' &&
        !in_array($m['role_status'] ?? '', ['expired','suspended','dismissed']))
        || in_array($m['role_status'] ?? '', ['resigned','terminated'])
    );
    $level = $_GET['level'] ?? null;
    $state = $_GET['state'] ?? null;
    if ($level) $visible = array_filter($visible, fn($m) => ($m['level'] ?? '') === $level);
    if ($state) $visible = array_filter($visible, fn($m) => ($m['state'] ?? '') === $state);

    $fullContactKeywords = ['president', 'general secretary'];
    $result = array_values(array_map(function($m) use ($fullContactKeywords) {
        $designation = $m['designation'] ?? '';
        $roleStatus = $m['role_status'] ?? 'active';
        $isInactive = in_array($roleStatus, ['resigned', 'terminated']);
        $desigLower = strtolower($designation);
        $showFull = false;
        foreach ($fullContactKeywords as $kw) {
            if ($kw === 'president') {
                if (preg_match('/\bpresident\b/i', $desigLower) && !preg_match('/vice/i', $desigLower)) { $showFull = true; break; }
            } else {
                if (strpos($desigLower, $kw) !== false) { $showFull = true; break; }
            }
        }
        $memberLevel = $m['level'] ?? 'member';
        $mobileVal = '';
        if (!$isInactive && $showFull) {
            $mobileVal = ($memberLevel === 'national') ? ORG_OFFICIAL_PHONE : ($m['mobile'] ?? '');
        }
        return [
            'name'        => $m['fullname'] ?? '',
            'designation' => $designation,
            'state'       => $m['state'] ?? '',
            'district'    => $m['district'] ?? '',
            'institution' => $m['institution'] ?? '',
            'email'       => $isInactive ? '' : ($m['email'] ?? ''),
            'mobile'      => $mobileVal,
            'photo'       => $m['photo'] ?? '',
            'level'       => $m['level'] ?? '',
            'role_status' => $roleStatus,
        ];
    }, $visible));
    ok($result);
}

// ── APPROVE ──────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/approve$#', $path, $matches) && $method === 'POST') {
    $claims = require_role('national','vp','secretary','state','district');
    $_id = $matches[1];
    $m = DB::findOne('primary_members', '_id', $_id);
    if (!$m) err('Not found', 404);

    // Upon approval: member becomes "Primary Member" at "member" level only.
    // Higher designations are assigned later via the Appoint action in RBAC.
    $designation = 'Primary Member';
    $level       = 'member';
    $role        = 'member';

    $now = gmdate('Y-m-d\TH:i:s\Z');
    DB::updateOne('primary_members', $_id, [
        'status'      => 'approved',
        'role_status' => 'active',
        'designation' => $designation,
        'level'       => $level,
        'approved_by' => $claims['sub'],
        'approved_at' => $now,
        'expiry_date' => DB::getExpiryDate($now, 3),
    ]);

    // Create portal login with member role
    $defaultPw = substr($m['mobile'], -4) . '@AISU';
    if (!DB::findOne('users', 'email', $m['email'])) {
        DB::insert('users', [
            'name'        => $m['fullname'],
            'email'       => $m['email'],
            'password'    => hash_password($defaultPw),
            'role'        => $role,
            'level'       => $level,
            'designation' => $designation,
            'state'       => $m['state'],
            'district'    => $m['district'] ?? '',
            'member_id'   => $m['member_id'],
            'status'      => 'active',
        ]);
    } else {
        $existingUser = DB::findOne('users', 'email', $m['email']);
        DB::updateOne('users', $existingUser['_id'], [
            'role'        => $role,
            'level'       => $level,
            'designation' => $designation,
            'status'      => 'active',
        ]);
    }

    // Record in promotion_history
    require_once __DIR__ . '/../rbac.php';
    $history = DB::loadCollection('promotion_history');
    $historyEntry = [
        '_id'              => DB::makeId(),
        'member_id'        => $m['member_id'],
        'member_name'      => $m['fullname'],
        'action'           => 'membership_approved',
        'old_role'         => 'none',
        'new_role'         => $role,
        'old_level'        => 'none',
        'new_level'        => $level,
        'old_designation'  => 'None',
        'new_designation'  => $designation,
        'promoted_by'      => $claims['sub'],
        'promoted_by_name' => $claims['name'] ?? '',
        'note'             => 'Primary membership approved. Designation to be assigned via Appoint.',
        'effective_date'   => gmdate('Y-m-d'),
        'created_at'       => $now,
    ];
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    try {
        $m2 = DB::findOne('primary_members', '_id', $_id);
        send_primary_approved($m2, $defaultPw);
        send_account_created($m['email'], $m['fullname'], $m['member_id'], $defaultPw, 'Primary');
    } catch (Exception $e) {}

    ok(['member_id' => $m['member_id'], 'role' => $role, 'designation' => $designation], 'Member approved as Primary Member. Use Designations panel to appoint a higher designation.');
}

// ── REJECT ───────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/reject$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    $_id = $matches[1];
    $data = get_request_data();
    $m = DB::findOne('primary_members', '_id', $_id);
    if (!$m) err('Not found', 404);
    DB::updateOne('primary_members', $_id, ['status' => 'rejected', 'rejection_reason' => $data['reason'] ?? '']);
    ok(null, 'Rejected');
}

// ── ROLE STATUS UPDATE ───────────────────────────────────────
if (preg_match('#^/([^/]+)/role-status$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    $_id = $matches[1];
    $data = get_request_data();
    $valid = ['active','promoted','demoted','transferred','additional_responsibility','resigned','terminated'];
    $newStatus = $data['role_status'] ?? '';
    if (!in_array($newStatus, $valid)) err('Invalid status. Must be one of: ' . implode(', ', $valid));

    $updates = ['role_status' => $newStatus];
    if (in_array($newStatus, ['resigned','terminated'])) {
        $updates['status'] = 'inactive';
        $member = DB::findOne('primary_members', '_id', $_id);
        if ($member) {
            $user = DB::findOne('users', 'email', $member['email']);
            if ($user) DB::updateOne('users', $user['_id'], ['status' => 'inactive']);
        }
    }
    if (isset($data['designation'])) $updates['designation'] = $data['designation'];
    if (isset($data['note']))        $updates['role_note']   = $data['note'];
    DB::updateOne('primary_members', $_id, $updates);
    ok(null, "Role status updated to $newStatus");
}

// ── RENEW ────────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/renew$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    $_id = $matches[1];
    $m = DB::findOne('primary_members', '_id', $_id);
    if (!$m) err('Not found', 404);
    $now = gmdate('Y-m-d\TH:i:s\Z');
    DB::updateOne('primary_members', $_id, [
        'status'      => 'approved',
        'role_status' => 'active',
        'approved_at' => $now,
        'expiry_date' => DB::getExpiryDate($now, 3),
    ]);
    $user = DB::findOne('users', 'email', $m['email']);
    if ($user) DB::updateOne('users', $user['_id'], ['status' => 'active']);
    ok(null, 'Membership renewed for 3 years from today');
}

// ── STATS ────────────────────────────────────────────────────
if ($path === '/stats/summary' && $method === 'GET') {
    require_role('national','vp','secretary');
    $allM = DB::findAll('primary_members');
    $byState = [];
    foreach ($allM as $m) {
        $s = $m['state'] ?? 'Unknown';
        $byState[$s] = ($byState[$s] ?? 0) + 1;
    }
    ok([
        'total'    => count($allM),
        'pending'  => count(array_filter($allM, fn($m) => ($m['status'] ?? '') === 'pending')),
        'approved' => count(array_filter($allM, fn($m) => ($m['status'] ?? '') === 'approved')),
        'rejected' => count(array_filter($allM, fn($m) => ($m['status'] ?? '') === 'rejected')),
        'expired'  => count(array_filter($allM, fn($m) => ($m['status'] ?? '') === 'expired')),
        'by_state' => $byState,
    ]);
}

// ── GET ONE ──────────────────────────────────────────────────
if (preg_match('#^/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $claims = require_auth();
    $_id = $matches[1];
    $m = DB::findOne('primary_members', 'member_id', $_id) ?: DB::findOne('primary_members', '_id', $_id);
    if (!$m) err('Not found', 404);
    if (($claims['role'] ?? '') === 'state' && ($claims['state'] ?? '') !== ($m['state'] ?? '')) {
        err('Access denied', 403);
    }
    unset($m['govtid_number']);
    if (!empty($m['approved_at'])) {
        $m['days_to_expiry'] = DB::daysUntilExpiry($m['approved_at'], 3);
        $m['expiry_date']    = DB::getExpiryDate($m['approved_at'], 3);
    }
    ok($m);
}

err('Endpoint not found', 404);
