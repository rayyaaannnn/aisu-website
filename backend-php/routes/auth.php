<?php
// =============================================================
//  routes/auth.php — Login, Register, Token Refresh, Password Change
//  Frontend: login.html
//  Admin panel: Auth guard (all pages)
//  API prefix: /api/auth
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── Seed default test users on first load ───────────────────────
if (!DB::findOne('users', 'email', 'admin@aisu4india.in')) {
    DB::insert('users', [
        'name'     => 'National Admin',
        'email'    => 'admin@aisu4india.in',
        'password' => hash_password('Admin@AISU2024'),
        'role'     => 'national',
        'state'    => 'ALL',
        'status'   => 'active',
    ]);
}
if (!DB::findOne('users', 'email', 'student@aisu4india.in')) {
    DB::insert('users', [
        'name'     => 'Test Student',
        'email'    => 'student@aisu4india.in',
        'password' => hash_password('Student@AISU2024'),
        'role'     => 'student',
        'state'    => 'ALL',
        'status'   => 'active',
    ]);
}
if (!DB::findOne('users', 'email', 'user@aisu4india.in')) {
    DB::insert('users', [
        'name'     => 'Test User',
        'email'    => 'user@aisu4india.in',
        'password' => hash_password('User@AISU2024'),
        'role'     => 'user',
        'state'    => 'ALL',
        'status'   => 'active',
    ]);
}

// ── REGISTER (Disabled — accounts created on membership approval) ────
if ($path === '/register' && $method === 'POST') {
    err('Public registration is not available. Please apply for Primary or Student Membership — your login account will be created automatically upon approval.', 403);
}

// ── LOGIN ────────────────────────────────────────────────────
if ($path === '/login' && $method === 'POST') {
    require_once __DIR__ . '/../rbac.php';
    $data = get_request_data();
    $missing = validate_required($data, ['email', 'password']);
    if ($missing) err('Missing fields: ' . implode(', ', $missing));

    $login = strtolower(trim($data['email']));
    
    // Try email first, then name (username, case-insensitive), then member_id/student_id
    $user = DB::findOne('users', 'email', $login);
    if (!$user) {
        $allUsers = DB::findAll('users');
        foreach ($allUsers as $u) {
            if (strtolower($u['name'] ?? '') === $login) {
                $user = $u;
                break;
            }
        }
    }
    if (!$user) {
        // Try member_id or student_id
        $allUsers = DB::findAll('users');
        foreach ($allUsers as $u) {
            if (($u['member_id'] ?? '') === strtoupper($login) || ($u['student_id'] ?? '') === strtoupper($login)) {
                $user = $u;
                break;
            }
        }
    }
    if (!$user || !check_password($data['password'], $user['password'])) {
        err('Invalid email or password', 401);
    }
    if (($user['status'] ?? '') !== 'active') {
        err('Account not active. Contact national admin.', 403);
    }

    $role = $user['role'] ?? 'user';
    $claims = [
        'role'        => $role,
        'name'        => $user['name'],
        'email'       => $user['email'],
        'state'       => $user['state'] ?? '',
        'level'       => $user['level'] ?? $role,
        'designation' => $user['designation'] ?? '',
    ];
    $access  = JWTHandler::createAccessToken($user['_id'], $claims);
    $refresh = JWTHandler::createRefreshToken($user['_id'], $claims);

    ok([
        'access_token'  => $access,
        'refresh_token' => $refresh,
        'user' => [
            'id'          => $user['_id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'role'        => $role,
            'level'       => $user['level'] ?? $role,
            'designation' => $user['designation'] ?? '',
            'state'       => $user['state'] ?? '',
            'member_id'   => $user['member_id'] ?? '',
            'permissions' => get_permissions($role),
            'panels'      => get_allowed_panels($role),
        ],
    ], 'Login successful');
}

// ── REFRESH ──────────────────────────────────────────────────
if ($path === '/refresh' && $method === 'POST') {
    $claims = JWTHandler::verifyRequest('refresh');
    if (!$claims) err('Invalid refresh token', 401);

    $access = JWTHandler::createAccessToken($claims['sub'], [
        'role'        => $claims['role'] ?? '',
        'name'        => $claims['name'] ?? '',
        'email'       => $claims['email'] ?? '',
        'state'       => $claims['state'] ?? '',
        'level'       => $claims['level'] ?? $claims['role'] ?? '',
        'designation' => $claims['designation'] ?? '',
    ]);
    ok(['access_token' => $access], 'Token refreshed');
}

// ── CHANGE PASSWORD ──────────────────────────────────────────
if ($path === '/change-password' && $method === 'POST') {
    $claims = require_auth();
    $data = get_request_data();
    $missing = validate_required($data, ['old_password', 'new_password']);
    if ($missing) err('Missing: ' . implode(', ', $missing));

    $uid  = $claims['sub'];
    $user = DB::findOne('users', '_id', $uid);
    if (!$user || !check_password($data['old_password'], $user['password'])) {
        err('Incorrect current password', 401);
    }
    if (strlen($data['new_password']) < 8) err('New password must be at least 8 characters');

    DB::updateOne('users', $uid, ['password' => hash_password($data['new_password'])]);
    ok(null, 'Password changed successfully');
}

// ── WHO AM I ─────────────────────────────────────────────────
if ($path === '/me' && $method === 'GET') {
    $claims = require_auth();
    $user = DB::findOne('users', '_id', $claims['sub']);
    if (!$user) err('User not found', 404);
    unset($user['password']);
    ok($user);
}

// ── FORGOT PASSWORD — Send OTP (Email or SMS) ───────────────
if ($path === '/forgot-password' && $method === 'POST') {
    require_once __DIR__ . '/../email_service.php';
    require_once __DIR__ . '/../sms_service.php';
    $data = get_request_data();
    $identifier = strtolower(trim($data['identifier'] ?? ''));
    $via        = strtolower(trim($data['via'] ?? 'email')); // 'email' or 'sms'
    if (!$identifier) err('Please provide your registered email or mobile number.');

    // Find user by email or mobile
    $user = DB::findOne('users', 'email', $identifier);
    if (!$user) {
        $digits = preg_replace('/\D/', '', $identifier);
        if (strlen($digits) >= 10) {
            $allUsers = DB::findAll('users');
            foreach ($allUsers as $u) {
                if (($u['mobile'] ?? '') === $digits) { $user = $u; break; }
            }
        }
    }
    if (!$user) err('No account found with this email or mobile number.', 404);

    // Auto-detect 'via' if not explicitly provided
    if ($via === 'auto') {
        $hasDigits = preg_replace('/\D/', '', $identifier);
        if (strlen($hasDigits) >= 10 && filter_var($identifier, FILTER_VALIDATE_EMAIL) === false) {
            $via = 'sms';
        } else {
            $via = 'email';
        }
    }

    // Ensure the user has the required contact info for the chosen delivery method
    if ($via === 'sms' && empty($user['mobile'] ?? '')) {
        $via = 'email'; // Fallback to email if no mobile on record
    }
    if ($via === 'email' && empty($user['email'] ?? '')) {
        err('No email address registered for this account.', 400);
    }

    // Generate 6-digit OTP
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = gmdate('Y-m-d\TH:i:s\Z', time() + 120); // 2 minutes

    // Store OTP (overwrite any existing for this user)
    $otpData = DB::findAll('password_otps');
    $otpData = array_values(array_filter($otpData, fn($o) => ($o['user_id'] ?? '') !== $user['_id']));
    $otpData[] = [
        '_id'        => DB::makeId(),
        'user_id'    => $user['_id'],
        'email'      => $user['email'] ?? '',
        'mobile'     => $user['mobile'] ?? '',
        'otp'        => $otp,
        'via'        => $via,
        'expires_at' => $expiresAt,
        'verified'   => false,
        'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    DB::saveCollection('password_otps', $otpData);

    $sentVia = [];

    if ($via === 'sms') {
        // Send OTP via SMS
        $smsSent = send_sms_otp($user['mobile'], $user['name'] ?? 'User', $otp);
        if ($smsSent) {
            $sentVia[] = 'SMS';
        }
        // If SMS fails and we have an email, send via email as backup
        if (!$smsSent && !empty($user['email'])) {
            try {
                send_forgot_password_otp($user['email'], $user['name'] ?? 'User', $otp);
                $sentVia[] = 'email (SMS unavailable)';
            } catch (Exception $e) {}
        }
    } else {
        // Send OTP via Email
        try {
            send_forgot_password_otp($user['email'], $user['name'] ?? 'User', $otp);
            $sentVia[] = 'email';
        } catch (Exception $e) {}
        // If Twilio is configured and user has mobile, also send via SMS as convenience
        if (!empty($user['mobile']) && TWILIO_ACCOUNT_SID && TWILIO_AUTH_TOKEN) {
            $smsSent = send_sms_otp($user['mobile'], $user['name'] ?? 'User', $otp);
            if ($smsSent) $sentVia[] = 'SMS';
        }
    }

    if (empty($sentVia)) {
        err('Failed to send OTP. Please try again later or contact support.', 500);
    }

    // Build response with masked contact info
    $response = [];
    if ($via === 'sms') {
        $response['masked_mobile'] = mask_phone($user['mobile']);
        $response['message'] = 'OTP sent to your registered mobile number: ' . mask_phone($user['mobile']);
    } else {
        $masked = preg_replace_callback('/^(.)(.*)(@.*)$/', function($m) {
            return $m[1] . str_repeat('*', strlen($m[2])) . $m[3];
        }, $user['email']);
        $response['masked_email'] = $masked;
        $response['message'] = 'OTP sent to your registered email address: ' . $masked;
    }
    $response['via'] = implode(' & ', $sentVia);

    ok($response, 'OTP sent successfully.');
}

// ── FORGOT PASSWORD — Verify OTP ────────────────────────────
if ($path === '/verify-otp' && $method === 'POST') {
    $data = get_request_data();
    $identifier = strtolower(trim($data['identifier'] ?? ''));
    $enteredOtp = trim($data['otp'] ?? '');
    if (!$identifier || !$enteredOtp) err('Missing identifier or OTP.');

    $user = DB::findOne('users', 'email', $identifier);
    if (!$user) {
        $digits = preg_replace('/\D/', '', $identifier);
        $allUsers = DB::findAll('users');
        foreach ($allUsers as $u) {
            if (($u['mobile'] ?? '') === $digits) { $user = $u; break; }
        }
    }
    if (!$user) err('User not found.', 404);

    $otpData = DB::findAll('password_otps');
    $found = null;
    foreach ($otpData as &$o) {
        if (($o['user_id'] ?? '') === $user['_id']) { $found = &$o; break; }
    }
    if (!$found) err('No OTP request found. Please request a new OTP.', 400);

    // Check expiry
    $expiry = strtotime(str_replace('Z', '', $found['expires_at']));
    if (time() > $expiry) err('OTP has expired. Please request a new one.', 400);

    if ($found['otp'] !== $enteredOtp) err('Incorrect OTP. Please try again.', 400);

    // Mark as verified
    $found['verified'] = true;
    DB::saveCollection('password_otps', $otpData);

    ok(null, 'OTP verified successfully. You may now reset your password.');
}

// ── FORGOT PASSWORD — Reset Password ────────────────────────
if ($path === '/reset-password' && $method === 'POST') {
    $data = get_request_data();
    $identifier  = strtolower(trim($data['identifier'] ?? ''));
    $newPassword = $data['new_password'] ?? '';
    if (!$identifier || !$newPassword) err('Missing identifier or new password.');
    if (strlen($newPassword) < 6) err('Password must be at least 6 characters.');

    $user = DB::findOne('users', 'email', $identifier);
    if (!$user) {
        $digits = preg_replace('/\D/', '', $identifier);
        $allUsers = DB::findAll('users');
        foreach ($allUsers as $u) {
            if (($u['mobile'] ?? '') === $digits) { $user = $u; break; }
        }
    }
    if (!$user) err('User not found.', 404);

    // Verify that OTP was confirmed
    $otpData = DB::findAll('password_otps');
    $found = null;
    foreach ($otpData as $o) {
        if (($o['user_id'] ?? '') === $user['_id'] && ($o['verified'] ?? false) === true) { $found = $o; break; }
    }
    if (!$found) err('Please verify your OTP first.', 400);

    // Update password
    DB::updateOne('users', $user['_id'], ['password' => hash_password($newPassword)]);

    // Clean up OTP
    $otpData = array_values(array_filter($otpData, fn($o) => ($o['user_id'] ?? '') !== $user['_id']));
    DB::saveCollection('password_otps', $otpData);

    ok(null, 'Password reset successfully! You can now login with your new password.');
}

err('Endpoint not found', 404);
