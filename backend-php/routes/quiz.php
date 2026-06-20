<?php
// =============================================================
//  routes/quiz.php — Quiz Room REST Endpoints (PHP port)
//  Frontend: quiz-room.html
//  Admin panel: Quiz Rooms tab
//  API prefix: /api/quiz
//  NOTE: WebSocket (Socket.IO) is not available in vanilla PHP.
//  The live quiz room requires a Node.js or Ratchet WebSocket server.
//  This file provides the REST endpoints only.
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// In-memory quiz rooms stored in file for persistence across requests
$ROOMS_FILE = DATA_DIR . '/quiz_rooms.json';

function loadRooms(): array {
    global $ROOMS_FILE;
    if (!file_exists($ROOMS_FILE)) return [];
    $data = json_decode(file_get_contents($ROOMS_FILE), true);
    return is_array($data) ? $data : [];
}

function saveRooms(array $rooms): void {
    global $ROOMS_FILE;
    file_put_contents($ROOMS_FILE, json_encode($rooms, JSON_PRETTY_PRINT), LOCK_EX);
}

// ── CREATE ROOM ──────────────────────────────────────────────
if ($path === '/rooms' && $method === 'POST') {
    $data = get_request_data();
    $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    $rooms = loadRooms();
    $rooms[$code] = [
        'code'               => $code,
        'competition_id'     => $data['competition_id'] ?? null,
        'room_name'          => $data['room_name'] ?? 'Quiz Room',
        'max_teams'          => intval($data['max_teams'] ?? 8),
        'time_limit_minutes' => intval($data['time_limit_minutes'] ?? 30),
        'status'             => 'waiting',
        'participants'       => [],
        'teams'              => [],
        'questions'          => [],
        'current_q'          => -1,
        'answers'            => [],
        'chat'               => [],
        'created_at'         => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    saveRooms($rooms);
    ok(['room_code' => $code, 'room' => $rooms[$code]], 'Quiz room created');
}

// ── LIST ROOMS ───────────────────────────────────────────────
if ($path === '/rooms' && $method === 'GET') {
    $rooms = loadRooms();
    $public = [];
    foreach ($rooms as $code => $r) {
        $public[] = [
            'code'         => $code,
            'room_name'    => $r['room_name'],
            'status'       => $r['status'],
            'participants' => count($r['participants'] ?? []),
            'max_teams'    => $r['max_teams'],
        ];
    }
    ok($public);
}

// ── GET ROOM DETAILS ─────────────────────────────────────────
if (preg_match('#^/rooms/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $code = strtoupper($matches[1]);
    $rooms = loadRooms();
    if (!isset($rooms[$code])) err('Room not found', 404);
    ok($rooms[$code]);
}

// ── NOTE: WebSocket Events ───────────────────────────────────
// The following Socket.IO events from the Python version are NOT
// available in vanilla PHP and require a WebSocket server:
//
//   - join_quiz_room
//   - moderator_start_quiz
//   - moderator_next_question
//   - submit_answer
//   - quiz_chat
//   - disconnect
//
// For real-time quiz functionality, use either:
// 1. A Node.js + Socket.IO server alongside this PHP API
// 2. PHP Ratchet WebSocket library
// 3. Server-Sent Events (SSE) as a polling fallback

err('Endpoint not found', 404);
