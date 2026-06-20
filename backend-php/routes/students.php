<?php
// =============================================================
//  routes/students.php — Student Membership (PHP port)
//  Frontend: student-membership.html
//  Admin panel: Student Members tab
//  API prefix: /api/students
// =============================================================

require_once __DIR__ . '/../email_service.php';

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

$REQUIRED = ['fullname','parent_name','dob','age','gender','address','pin',
             'institution','state','district','city','mobile','email',
             'heard_about','mode_of_submission'];

// ── SUBMIT (Public) ──────────────────────────────────────────
if ($path === '/apply' && $method === 'POST') {
    $data = $_POST;
    $missing = validate_required($data, $REQUIRED);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $mobile = trim($data['mobile']);
    if (!ctype_digit($mobile) || strlen($mobile) !== 10) err('Mobile must be 10 digits');

    $email = strtolower(trim($data['email']));
    if (DB::findOne('student_members', 'email', $email)) err('An application with this email already exists');

    $studentId = DB::genStudentId($data['state']);
    $payment = save_upload($_FILES['payment_proof'] ?? null, 'payment');

    $doc = DB::insert('student_members', [
        'student_id'        => $studentId,
        'fullname'          => strtoupper($data['fullname']),
        'parent_name'       => $data['parent_name'] ?? '',
        'dob'               => $data['dob'] ?? '',
        'age'               => $data['age'] ?? '',
        'gender'            => $data['gender'] ?? '',
        'address'           => $data['address'] ?? '',
        'pin'               => $data['pin'] ?? '',
        'institution'       => $data['institution'] ?? '',
        'state'             => $data['state'] ?? '',
        'district'          => $data['district'] ?? '',
        'city'              => $data['city'] ?? '',
        'mobile'            => $mobile,
        'email'             => $email,
        'heard_about'       => $data['heard_about'] ?? '',
        'payment_proof'     => $payment,
        'mode_of_submission'  => $data['mode_of_submission'] ?? '',
        'razorpay_payment_id' => $data['razorpay_payment_id'] ?? '',
        'status'              => 'pending',
    ]);

    try { send_student_application_received($doc); } catch (Exception $e) {}

    ok(['application_ref' => $doc['_id']],
       'Student membership application submitted! Your AISU Student ID will be emailed after approval.', 201);
}

// ── CSV EXPORT (Admin) ─────────────────────────────────────
if ($path === '/export/csv' && $method === 'GET') {
    require_role('national','vp','secretary');
    $allS = DB::findAll('student_members');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student_members_' . gmdate('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM for Excel

    $headers = ['Student ID', 'Full Name', 'Parent Name', 'DOB', 'Age', 'Gender', 'Address', 'PIN',
                'Institution', 'State', 'District', 'City', 'Mobile', 'Email',
                'Heard About',                'Mode of Submission', 'Status', 'Approved By', 'Razorpay Payment ID', 'Approved At', 'Expiry Date', 'Created At'];
    fputcsv($output, $headers);

    foreach ($allS as $s) {
        fputcsv($output, [
            $s['student_id'] ?? '',
            $s['fullname'] ?? '',
            $s['parent_name'] ?? '',
            $s['dob'] ?? '',
            $s['age'] ?? '',
            $s['gender'] ?? '',
            $s['address'] ?? '',
            $s['pin'] ?? '',
            $s['institution'] ?? '',
            $s['state'] ?? '',
            $s['district'] ?? '',
            $s['city'] ?? '',
            $s['mobile'] ?? '',
            $s['email'] ?? '',
            $s['heard_about'] ?? '',
            $s['mode_of_submission'] ?? '',
            $s['status'] ?? '',
            $s['approved_by'] ?? '',
            $s['razorpay_payment_id'] ?? '',
            $s['approved_at'] ?? '',
            $s['expiry_date'] ?? '',
            $s['created_at'] ?? '',
        ]);
    }
    fclose($output);
    exit;
}

// ── LIST ─────────────────────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    $claims = require_role('national','vp','secretary','state');
    $allS = DB::findAll('student_members');
    if (($claims['role'] ?? '') === 'state') {
        $allS = array_values(array_filter($allS, fn($s) => ($s['state'] ?? '') === ($claims['state'] ?? '')));
    }
    $status = $_GET['status'] ?? null;
    if ($status) $allS = array_values(array_filter($allS, fn($s) => ($s['status'] ?? '') === $status));
    ok($allS, count($allS) . ' records');
}

// ── APPROVE ──────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/approve$#', $path, $matches) && $method === 'POST') {
    $claims = require_role('national','vp','secretary');
    $_id = $matches[1];
    $s = DB::findOne('student_members', '_id', $_id);
    if (!$s) err('Not found', 404);

    $now = gmdate('Y-m-d\TH:i:s\Z');
    DB::updateOne('student_members', $_id, [
        'status'      => 'approved',
        'approved_by' => $claims['sub'],
        'approved_at' => $now,
        'expiry_date' => DB::getExpiryDate($now, 1),
    ]);

    // Create portal login for the student
    $defaultPw = substr($s['mobile'], -4) . '@AISU';
    if (!DB::findOne('users', 'email', $s['email'])) {
        DB::insert('users', [
            'name'       => $s['fullname'],
            'email'      => $s['email'],
            'password'   => hash_password($defaultPw),
            'role'       => 'student',
            'state'      => $s['state'] ?? '',
            'student_id' => $s['student_id'],
            'status'     => 'active',
        ]);
    }

    try {
        $s2 = DB::findOne('student_members', '_id', $_id);
        send_student_approved($s2, $defaultPw);
        send_account_created($s['email'], $s['fullname'], $s['student_id'], $defaultPw, 'Student');
    } catch (Exception $e) {}

    ok(['student_id' => $s['student_id']], 'Student approved');
}

// ── REJECT ───────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/reject$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    $_id = $matches[1];
    $data = get_request_data();
    $s = DB::findOne('student_members', '_id', $_id);
    if (!$s) err('Not found', 404);
    DB::updateOne('student_members', $_id, [
        'status'           => 'rejected',
        'rejection_reason' => $data['reason'] ?? '',
    ]);
    ok(null, 'Rejected');
}

// ── STATS ────────────────────────────────────────────────────
if ($path === '/stats/summary' && $method === 'GET') {
    require_role('national','vp','secretary');
    $allS = DB::findAll('student_members');
    ok([
        'total'    => count($allS),
        'pending'  => count(array_filter($allS, fn($s) => ($s['status'] ?? '') === 'pending')),
        'approved' => count(array_filter($allS, fn($s) => ($s['status'] ?? '') === 'approved')),
        'rejected' => count(array_filter($allS, fn($s) => ($s['status'] ?? '') === 'rejected')),
    ]);
}

err('Endpoint not found', 404);
