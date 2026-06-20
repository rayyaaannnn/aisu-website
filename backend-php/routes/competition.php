<?php
// =============================================================
//  routes/competition.php — Competition Portal (PHP port)
//  Frontend: competition.html
//  Admin panel: Competitions tab
//  API prefix: /api/competitions
// =============================================================

require_once __DIR__ . '/../email_service.php';

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── CREATE COMPETITION (Admin) ───────────────────────────────
if ($path === '/' && $method === 'POST') {
    $claims = require_role('national','vp','secretary','it_cell');
    $data = get_request_data();
    $missing = validate_required($data, ['title','category','comp_type','last_date']);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $compId = DB::genCompetitionId();
    $doc = DB::insert('competitions', [
        'comp_id'     => $compId,
        'title'       => $data['title'],
        'description' => $data['description'] ?? '',
        'category'    => $data['category'],
        'comp_type'   => $data['comp_type'],
        'last_date'   => $data['last_date'],
        'event_date'  => $data['event_date'] ?? '',
        'group_size'  => $data['group_size'] ?? 1,
        'time_limit'  => $data['time_limit'] ?? 0,
        'questions'   => $data['questions'] ?? [],
        'status'      => 'open',
        'created_by'  => $claims['sub'],
    ]);
    ok(['comp_id' => $compId], 'Competition created', 201);
}

// ── LIST ─────────────────────────────────────────────────────
if ($path === '/' && $method === 'GET') {
    $comps = DB::findAll('competitions');
    $status = $_GET['status'] ?? 'open';
    if ($status !== 'all') {
        $comps = array_values(array_filter($comps, fn($c) => ($c['status'] ?? '') === $status));
    }
    foreach ($comps as &$c) unset($c['questions']);
    ok($comps);
}

// ── MY REGISTRATIONS ─────────────────────────────────────────
if ($path === '/my-registrations' && $method === 'GET') {
    $claims = require_auth();
    $uid  = $claims['sub'];
    $regs = array_values(array_filter(
        DB::findAll('competition_registrations'),
        fn($r) => ($r['user_id'] ?? '') === $uid
    ));
    ok($regs);
}

// ── GET DETAILS ──────────────────────────────────────────────
if (preg_match('#^/([^/]+)$#', $path, $matches) && $method === 'GET' && !str_contains($matches[1], '/')) {
    $compId = $matches[1];
    $c = DB::findOne('competitions', 'comp_id', $compId);
    if (!$c) err('Not found', 404);
    $cOut = $c;
    unset($cOut['questions']);
    ok($cOut);
}

// ── REGISTER ─────────────────────────────────────────────────
if (preg_match('#^/([^/]+)/register$#', $path, $matches) && $method === 'POST') {
    $claims = require_auth();
    $compId = $matches[1];
    $c = DB::findOne('competitions', 'comp_id', $compId);
    if (!$c) err('Competition not found', 404);
    if (($c['status'] ?? '') !== 'open') err('Registration is closed');

    $uid  = $claims['sub'];
    $user = DB::findOne('users', '_id', $uid);
    if (!$user) err('User not found', 401);

    $existing = array_filter(
        DB::findAll('competition_registrations'),
        fn($r) => ($r['comp_id'] ?? '') === $compId && ($r['user_id'] ?? '') === $uid
    );
    if ($existing) err('Already registered for this competition');

    $data = get_request_data();
    $doc = DB::insert('competition_registrations', [
        'comp_id'       => $compId,
        'comp_title'    => $c['title'] ?? '',
        'user_id'       => $uid,
        'name'          => $user['name'] ?? '',
        'email'         => $user['email'] ?? '',
        'institution'   => $data['institution'] ?? ($user['institution'] ?? ''),
        'state'         => $data['state'] ?? ($user['state'] ?? ''),
        'group_members' => $data['group_members'] ?? [],
    ]);
    ok(['reg_id' => $doc['_id']], 'Registered successfully', 201);
}

// ── SUBMIT ENTRY (Document Upload) ───────────────────────────
if (preg_match('#^/([^/]+)/submit$#', $path, $matches) && $method === 'POST') {
    $claims = require_auth();
    $compId = $matches[1];
    $uid = $claims['sub'];

    $regs = DB::findAll('competition_registrations');
    $reg = null;
    foreach ($regs as $r) {
        if (($r['comp_id'] ?? '') === $compId && ($r['user_id'] ?? '') === $uid) { $reg = $r; break; }
    }
    if (!$reg) err('You are not registered for this competition', 403);

    $f = $_FILES['submission'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) err('No file uploaded');
    $fname = save_upload($f, 'submissions');
    if (!$fname) err('Invalid file type');

    DB::updateOne('competition_registrations', $reg['_id'], [
        'submission_file' => $fname,
        'submitted_at'    => gmdate('Y-m-d\TH:i:s\Z'),
        'submission_note' => $_POST['note'] ?? '',
    ]);
    ok(null, 'Submission received. Forwarded to selection committee.');
}

// ── QUIZ: START SESSION ──────────────────────────────────────
if (preg_match('#^/([^/]+)/start-quiz$#', $path, $matches) && $method === 'POST') {
    $claims = require_auth();
    $compId = $matches[1];
    $uid = $claims['sub'];

    $regs = DB::findAll('competition_registrations');
    $reg = null;
    foreach ($regs as $r) {
        if (($r['comp_id'] ?? '') === $compId && ($r['user_id'] ?? '') === $uid) { $reg = $r; break; }
    }
    if (!$reg) err('Not registered', 403);
    if (!empty($reg['quiz_started'])) err('Quiz already started');
    if (!empty($reg['disqualified'])) err('You have been disqualified');

    $c = DB::findOne('competitions', 'comp_id', $compId);
    if (!$c || !in_array($c['comp_type'] ?? '', ['exam_quiz','group_quiz'])) err('Not a quiz competition');

    DB::updateOne('competition_registrations', $reg['_id'], [
        'quiz_started'    => true,
        'quiz_start_time' => gmdate('Y-m-d\TH:i:s\Z'),
    ]);

    $questions = $c['questions'] ?? [];
    shuffle($questions);
    $safeQ = array_map(function($q) {
        $safe = $q;
        unset($safe['correct_answer']);
        return $safe;
    }, $questions);

    ok([
        'questions'    => $safeQ,
        'time_limit'   => $c['time_limit'] ?? 30,
        'total'        => count($safeQ),
        'instructions' => $c['instructions'] ?? '',
    ]);
}

// ── QUIZ: SUBMIT ANSWERS ─────────────────────────────────────
if (preg_match('#^/([^/]+)/submit-quiz$#', $path, $matches) && $method === 'POST') {
    $claims = require_auth();
    $compId = $matches[1];
    $uid = $claims['sub'];

    $regs = DB::findAll('competition_registrations');
    $reg = null;
    foreach ($regs as $r) {
        if (($r['comp_id'] ?? '') === $compId && ($r['user_id'] ?? '') === $uid) { $reg = $r; break; }
    }
    if (!$reg) err('Not registered', 403);
    if (!empty($reg['disqualified'])) err('Disqualified');
    if (!empty($reg['quiz_submitted'])) err('Already submitted');

    $data    = get_request_data();
    $answers = $data['answers'] ?? [];
    $c       = DB::findOne('competitions', 'comp_id', $compId);

    $score = 0;
    $total = count($c['questions'] ?? []);
    foreach ($c['questions'] ?? [] as $q) {
        if (isset($answers[$q['id'] ?? '']) && ($answers[$q['id']] ?? '') === ($q['correct_answer'] ?? '')) {
            $score++;
        }
    }

    DB::updateOne('competition_registrations', $reg['_id'], [
        'quiz_submitted'  => true,
        'quiz_submit_time'=> gmdate('Y-m-d\TH:i:s\Z'),
        'answers'         => $answers,
        'score'           => $score,
        'score_percent'   => $total > 0 ? round($score / $total * 100, 1) : 0,
    ]);
    ok(['score' => $score, 'total' => $total], 'Quiz submitted');
}

// ── DISQUALIFY ───────────────────────────────────────────────
if (preg_match('#^/reg/([^/]+)/disqualify$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary','it_cell');
    $regId = $matches[1];
    $data = get_request_data();
    $reg = DB::findOne('competition_registrations', '_id', $regId);
    if (!$reg) err('Not found', 404);
    DB::updateOne('competition_registrations', $regId, [
        'disqualified'             => true,
        'disqualification_reason'  => $data['reason'] ?? 'Rule violation',
    ]);
    ok(null, 'Disqualified');
}

// ── RESULTS (Admin post) ─────────────────────────────────────
if (preg_match('#^/([^/]+)/results$#', $path, $matches) && $method === 'POST') {
    require_role('national','vp','secretary');
    $compId = $matches[1];
    $data = get_request_data();
    $c = DB::findOne('competitions', 'comp_id', $compId);
    if (!$c) err('Not found', 404);
    DB::updateOne('competitions', $c['_id'], [
        'status'  => 'completed',
        'results' => $data['results'] ?? [],
    ]);
    ok(null, 'Results published');
}

// ── GET RESULTS (Public) ─────────────────────────────────────
if (preg_match('#^/([^/]+)/results$#', $path, $matches) && $method === 'GET') {
    $compId = $matches[1];
    $c = DB::findOne('competitions', 'comp_id', $compId);
    if (!$c) err('Not found', 404);
    if (($c['status'] ?? '') !== 'completed') err('Results not yet published');
    ok(['comp_id' => $compId, 'title' => $c['title'] ?? '', 'results' => $c['results'] ?? []]);
}

err('Endpoint not found', 404);
