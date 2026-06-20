<?php
// =============================================================
//  routes/rbac.php — RBAC Management Routes
//  Frontend: (admin only)
//  Admin panel: Role Management, Team Management, Promotion History,
//               Designations, Approvals tabs
//  API prefix: /api/rbac
//  Promotion, Demotion, Transfer, History, Team Directory,
//  Permissions API
// =============================================================

require_once __DIR__ . '/../rbac.php';

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── GET MY PERMISSIONS ──────────────────────────────────────
// Returns current user's role, permissions, and allowed panels
if ($path === '/my-permissions' && $method === 'GET') {
    $claims = require_auth();
    $role = $claims['role'] ?? 'user';
    ok([
        'role'         => $role,
        'rank'         => role_rank($role),
        'permissions'  => get_permissions($role),
        'panels'       => get_allowed_panels($role),
        'designations' => get_designations_for_role($role),
    ]);
}

// ── GET ROLE CONFIG ─────────────────────────────────────────
// Public: returns role hierarchy and designation options
if ($path === '/roles' && $method === 'GET') {
    ok([
        'hierarchy'    => ROLE_HIERARCHY,
        'designations' => DESIGNATION_MAP,
    ]);
}

// ── PROMOTE MEMBER ──────────────────────────────────────────
if (preg_match('#^/promote/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.promote');
    $_id = $matches[1];
    $data = get_request_data();

    $newRole        = $data['new_role'] ?? '';
    $newLevel       = $data['new_level'] ?? $newRole;
    $newDesignation = $data['new_designation'] ?? '';
    $note           = $data['note'] ?? '';

    if (!$newRole || !$newDesignation) {
        err('new_role and new_designation are required');
    }

    // Validate role exists
    if (!isset(ROLE_HIERARCHY[$newRole])) {
        err("Invalid role: $newRole. Valid roles: " . implode(', ', array_keys(ROLE_HIERARCHY)));
    }

    // Ensure promoter outranks the target role
    $myRole = $claims['role'] ?? '';
    if (!can_manage_role($myRole, $newRole) && $myRole !== 'national') {
        err('You cannot assign a role equal to or higher than your own', 403);
    }

    // Find the member
    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    // Record old values
    $oldRole        = $member['level'] ?? $member['role_status'] ?? 'member';
    $oldDesignation = $member['designation'] ?? 'Primary Member';

    // Create promotion history entry
    $historyEntry = [
        '_id'              => DB::makeId(),
        'member_id'        => $member['member_id'] ?? $member['_id'],
        'member_name'      => $member['fullname'] ?? '',
        'action'           => 'promoted',
        'old_role'         => $oldRole,
        'new_role'         => $newRole,
        'old_level'        => $member['level'] ?? '',
        'new_level'        => $newLevel,
        'old_designation'  => $oldDesignation,
        'new_designation'  => $newDesignation,
        'promoted_by'      => $claims['sub'],
        'promoted_by_name' => $claims['name'] ?? '',
        'note'             => $note,
        'effective_date'   => $data['effective_date'] ?? gmdate('Y-m-d'),
        'created_at'       => gmdate('Y-m-d\TH:i:s\Z'),
    ];

    // Save to promotion_history collection
    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    // Update the member record
    $memberUpdates = [
        'designation'       => $newDesignation,
        'level'             => $newLevel,
        'role_status'       => 'promoted',
        'last_promoted_at'  => gmdate('Y-m-d\TH:i:s\Z'),
        'last_promoted_by'  => $claims['sub'],
    ];
    DB::updateOne('primary_members', $member['_id'], $memberUpdates);

    // Update the user account role + designation for portal access
    $user = DB::findOne('users', 'email', $member['email'] ?? '');
    if ($user) {
        DB::updateOne('users', $user['_id'], [
            'role'        => $newRole,
            'designation' => $newDesignation,
            'level'       => $newLevel,
        ]);
    }

    ok($historyEntry, "Member promoted to $newDesignation ($newRole level)");
}

// ── DEMOTE MEMBER ───────────────────────────────────────────
if (preg_match('#^/demote/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.demote');
    $_id = $matches[1];
    $data = get_request_data();

    $newRole        = $data['new_role'] ?? 'member';
    $newLevel       = $data['new_level'] ?? $newRole;
    $newDesignation = $data['new_designation'] ?? 'Primary Member';
    $note           = $data['note'] ?? '';

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    $oldRole        = $member['level'] ?? 'member';
    $oldDesignation = $member['designation'] ?? 'Primary Member';

    $historyEntry = [
        '_id'              => DB::makeId(),
        'member_id'        => $member['member_id'] ?? $member['_id'],
        'member_name'      => $member['fullname'] ?? '',
        'action'           => 'demoted',
        'old_role'         => $oldRole,
        'new_role'         => $newRole,
        'old_level'        => $member['level'] ?? '',
        'new_level'        => $newLevel,
        'old_designation'  => $oldDesignation,
        'new_designation'  => $newDesignation,
        'promoted_by'      => $claims['sub'],
        'promoted_by_name' => $claims['name'] ?? '',
        'note'             => $note,
        'effective_date'   => $data['effective_date'] ?? gmdate('Y-m-d'),
        'created_at'       => gmdate('Y-m-d\TH:i:s\Z'),
    ];

    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    DB::updateOne('primary_members', $member['_id'], [
        'designation'      => $newDesignation,
        'level'            => $newLevel,
        'role_status'      => 'demoted',
        'last_promoted_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'last_promoted_by' => $claims['sub'],
    ]);

    $user = DB::findOne('users', 'email', $member['email'] ?? '');
    if ($user) {
        DB::updateOne('users', $user['_id'], [
            'role'        => $newRole,
            'designation' => $newDesignation,
            'level'       => $newLevel,
        ]);
    }

    ok($historyEntry, "Member demoted to $newDesignation ($newRole level)");
}

// ── TRANSFER MEMBER ─────────────────────────────────────────
if (preg_match('#^/transfer/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.transfer');
    $_id = $matches[1];
    $data = get_request_data();

    $newDesignation = $data['new_designation'] ?? '';
    $newState       = $data['new_state'] ?? '';
    $newDistrict    = $data['new_district'] ?? '';
    $note           = $data['note'] ?? '';

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    $historyEntry = [
        '_id'              => DB::makeId(),
        'member_id'        => $member['member_id'] ?? $member['_id'],
        'member_name'      => $member['fullname'] ?? '',
        'action'           => 'transferred',
        'old_role'         => $member['level'] ?? '',
        'new_role'         => $member['level'] ?? '',
        'old_level'        => $member['level'] ?? '',
        'new_level'        => $member['level'] ?? '',
        'old_designation'  => $member['designation'] ?? '',
        'new_designation'  => $newDesignation ?: ($member['designation'] ?? ''),
        'old_state'        => $member['state'] ?? '',
        'new_state'        => $newState ?: ($member['state'] ?? ''),
        'old_district'     => $member['district'] ?? '',
        'new_district'     => $newDistrict ?: ($member['district'] ?? ''),
        'promoted_by'      => $claims['sub'],
        'promoted_by_name' => $claims['name'] ?? '',
        'note'             => $note,
        'effective_date'   => $data['effective_date'] ?? gmdate('Y-m-d'),
        'created_at'       => gmdate('Y-m-d\TH:i:s\Z'),
    ];

    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    $updates = ['role_status' => 'transferred'];
    if ($newDesignation) $updates['designation'] = $newDesignation;
    if ($newState)       $updates['state'] = $newState;
    if ($newDistrict)    $updates['district'] = $newDistrict;

    DB::updateOne('primary_members', $member['_id'], $updates);

    $user = DB::findOne('users', 'email', $member['email'] ?? '');
    if ($user) {
        $userUpdates = [];
        if ($newDesignation) $userUpdates['designation'] = $newDesignation;
        if ($newState)       $userUpdates['state'] = $newState;
        if ($userUpdates) DB::updateOne('users', $user['_id'], $userUpdates);
    }

    ok($historyEntry, 'Member transferred successfully');
}

// ── ADD ADDITIONAL RESPONSIBILITY ───────────────────────────
if (preg_match('#^/additional-role/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.assign_designation');
    $_id = $matches[1];
    $data = get_request_data();

    $additionalDesignation = $data['additional_designation'] ?? '';
    $note = $data['note'] ?? '';

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    $historyEntry = [
        '_id'              => DB::makeId(),
        'member_id'        => $member['member_id'] ?? $member['_id'],
        'member_name'      => $member['fullname'] ?? '',
        'action'           => 'additional_responsibility',
        'old_designation'  => $member['designation'] ?? '',
        'new_designation'  => ($member['designation'] ?? '') . ' + ' . $additionalDesignation,
        'old_role'         => $member['level'] ?? '',
        'new_role'         => $member['level'] ?? '',
        'old_level'        => $member['level'] ?? '',
        'new_level'        => $member['level'] ?? '',
        'promoted_by'      => $claims['sub'],
        'promoted_by_name' => $claims['name'] ?? '',
        'note'             => $note,
        'effective_date'   => $data['effective_date'] ?? gmdate('Y-m-d'),
        'created_at'       => gmdate('Y-m-d\TH:i:s\Z'),
    ];

    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    $additionalRoles = $member['additional_roles'] ?? [];
    $additionalRoles[] = [
        'designation' => $additionalDesignation,
        'assigned_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'assigned_by' => $claims['sub'],
    ];

    DB::updateOne('primary_members', $member['_id'], [
        'additional_roles' => $additionalRoles,
        'role_status'      => 'additional_responsibility',
    ]);

    ok($historyEntry, "Additional responsibility assigned: $additionalDesignation");
}

// ── SUSPEND MEMBER ──────────────────────────────────────────
if (preg_match('#^/suspend/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.promote');
    $_id = $matches[1];
    $data = get_request_data();
    $days = intval($data['days'] ?? 0);
    $note = $data['note'] ?? '';
    if ($days <= 0) err('Number of suspension days is required and must be > 0');

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    $suspendUntil = gmdate('Y-m-d', strtotime("+{$days} days"));
    $historyEntry = [
        '_id' => DB::makeId(),
        'member_id' => $member['member_id'] ?? $member['_id'],
        'member_name' => $member['fullname'] ?? '',
        'action' => 'suspended',
        'old_role' => $member['level'] ?? '', 'new_role' => $member['level'] ?? '',
        'old_level' => $member['level'] ?? '', 'new_level' => $member['level'] ?? '',
        'old_designation' => $member['designation'] ?? '', 'new_designation' => $member['designation'] ?? '',
        'promoted_by' => $claims['sub'], 'promoted_by_name' => $claims['name'] ?? '',
        'note' => "Suspended for $days days until $suspendUntil. " . $note,
        'effective_date' => gmdate('Y-m-d'), 'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    DB::updateOne('primary_members', $member['_id'], [
        'role_status' => 'suspended',
        'suspended_until' => $suspendUntil,
        'suspended_days' => $days,
        'suspended_by' => $claims['sub'],
        'suspended_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ]);

    // Deactivate user account during suspension
    $user = DB::findOne('users', 'email', $member['email'] ?? '');
    if ($user) DB::updateOne('users', $user['_id'], ['status' => 'suspended']);

    ok($historyEntry, "Member suspended for $days days until $suspendUntil");
}

// ── DISMISS MEMBER ──────────────────────────────────────────
if (preg_match('#^/dismiss/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.terminate');
    $_id = $matches[1];
    $data = get_request_data();
    $note = $data['note'] ?? '';

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    $historyEntry = [
        '_id' => DB::makeId(),
        'member_id' => $member['member_id'] ?? $member['_id'],
        'member_name' => $member['fullname'] ?? '',
        'action' => 'dismissed',
        'old_role' => $member['level'] ?? '', 'new_role' => 'none',
        'old_level' => $member['level'] ?? '', 'new_level' => 'none',
        'old_designation' => $member['designation'] ?? '', 'new_designation' => 'None',
        'promoted_by' => $claims['sub'], 'promoted_by_name' => $claims['name'] ?? '',
        'note' => $note, 'effective_date' => gmdate('Y-m-d'), 'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    DB::updateOne('primary_members', $member['_id'], [
        'role_status' => 'dismissed', 'status' => 'inactive',
        'designation' => '', 'level' => 'member', 'additional_roles' => [],
    ]);

    $user = DB::findOne('users', 'email', $member['email'] ?? '');
    if ($user) DB::updateOne('users', $user['_id'], ['status' => 'inactive', 'role' => 'user']);

    ok($historyEntry, 'Member dismissed');
}

// ── APPROVE RESIGNATION ─────────────────────────────────────
if (preg_match('#^/approve-resignation/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.terminate');
    $_id = $matches[1];
    $data = get_request_data();
    $note = $data['note'] ?? '';

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    $historyEntry = [
        '_id' => DB::makeId(),
        'member_id' => $member['member_id'] ?? $member['_id'],
        'member_name' => $member['fullname'] ?? '',
        'action' => 'resignation_approved',
        'old_role' => $member['level'] ?? '', 'new_role' => 'none',
        'old_level' => $member['level'] ?? '', 'new_level' => 'none',
        'old_designation' => $member['designation'] ?? '', 'new_designation' => 'None',
        'promoted_by' => $claims['sub'], 'promoted_by_name' => $claims['name'] ?? '',
        'note' => $note, 'effective_date' => gmdate('Y-m-d'), 'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    DB::updateOne('primary_members', $member['_id'], [
        'role_status' => 'resigned', 'status' => 'inactive',
        'designation' => '', 'level' => 'member', 'additional_roles' => [],
    ]);

    $user = DB::findOne('users', 'email', $member['email'] ?? '');
    if ($user) DB::updateOne('users', $user['_id'], ['status' => 'inactive', 'role' => 'user']);

    ok($historyEntry, 'Resignation approved');
}

// ── REMOVE ADDITIONAL CHARGE ────────────────────────────────
if (preg_match('#^/remove-additional-role/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.assign_designation');
    $_id = $matches[1];
    $data = get_request_data();
    $designationToRemove = $data['designation'] ?? '';
    if (!$designationToRemove) err('designation to remove is required');

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    $additionalRoles = $member['additional_roles'] ?? [];
    $newRoles = array_values(array_filter($additionalRoles, fn($r) => ($r['designation'] ?? '') !== $designationToRemove));
    if (count($newRoles) === count($additionalRoles)) err('Designation not found in additional roles');

    $historyEntry = [
        '_id' => DB::makeId(),
        'member_id' => $member['member_id'] ?? $member['_id'],
        'member_name' => $member['fullname'] ?? '',
        'action' => 'additional_charge_removed',
        'old_role' => $member['level'] ?? '', 'new_role' => $member['level'] ?? '',
        'old_level' => $member['level'] ?? '', 'new_level' => $member['level'] ?? '',
        'old_designation' => $member['designation'] ?? '', 'new_designation' => $member['designation'] ?? '',
        'promoted_by' => $claims['sub'], 'promoted_by_name' => $claims['name'] ?? '',
        'note' => "Removed additional charge: $designationToRemove",
        'effective_date' => gmdate('Y-m-d'), 'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    $history = DB::loadCollection('promotion_history');
    array_unshift($history, $historyEntry);
    DB::saveCollection('promotion_history', $history);

    DB::updateOne('primary_members', $member['_id'], ['additional_roles' => $newRoles]);
    ok($historyEntry, "Additional charge '$designationToRemove' removed");
}

// ── GET PROMOTION HISTORY (All or for a specific member) ────
if ($path === '/history' && $method === 'GET') {
    $claims = require_permission('members.view_history');
    $history = DB::loadCollection('promotion_history');

    // Optional filters
    $memberId = $_GET['member_id'] ?? null;
    $action   = $_GET['action'] ?? null;
    $level    = $_GET['level'] ?? null;
    $limit    = intval($_GET['limit'] ?? 50);

    if ($memberId) {
        $history = array_values(array_filter($history, fn($h) =>
            ($h['member_id'] ?? '') === $memberId
        ));
    }
    if ($action) {
        $history = array_values(array_filter($history, fn($h) =>
            ($h['action'] ?? '') === $action
        ));
    }
    if ($level) {
        $history = array_values(array_filter($history, fn($h) =>
            ($h['new_level'] ?? '') === $level
        ));
    }

    $history = array_slice($history, 0, $limit);
    ok($history, count($history) . ' records');
}

// ── GET MEMBER HISTORY ──────────────────────────────────────
if (preg_match('#^/history/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $claims = require_permission('members.view_history');
    $memberId = $matches[1];
    $history = DB::loadCollection('promotion_history');
    $memberHistory = array_values(array_filter($history, fn($h) =>
        ($h['member_id'] ?? '') === $memberId
    ));
    ok($memberHistory, count($memberHistory) . ' records');
}

// ── TEAM DIRECTORY (Public — for "Our Team" page) ───────────
// Returns only active, approved members grouped by level
if ($path === '/team-directory' && $method === 'GET') {
    $allMembers = DB::findAll('primary_members');

    // Include: active + resigned + terminated (exclude suspended, dismissed, expired, pending)
    $visible = array_filter($allMembers, fn($m) =>
        ($m['status'] ?? '') === 'approved' &&
        !in_array($m['role_status'] ?? '', ['expired', 'suspended', 'dismissed'])
        ||
        in_array($m['role_status'] ?? '', ['resigned', 'terminated'])
    );

    // Optional filter by level or state
    $level = $_GET['level'] ?? null;
    $state = $_GET['state'] ?? null;
    if ($level && $level !== 'all') {
        $visible = array_filter($visible, fn($m) => ($m['level'] ?? '') === $level);
    }
    if ($state && $state !== 'all') {
        $visible = array_filter($visible, fn($m) => ($m['state'] ?? '') === $state);
    }

    // Sort by rank (national first), resigned/terminated at end
    usort($visible, function($a, $b) {
        $statusOrder = ['active'=>0,'promoted'=>0,'additional_responsibility'=>0,'resigned'=>1,'terminated'=>2];
        $sa = $statusOrder[$a['role_status'] ?? 'active'] ?? 0;
        $sb = $statusOrder[$b['role_status'] ?? 'active'] ?? 0;
        if ($sa !== $sb) return $sa - $sb;
        $rankA = ROLE_HIERARCHY[$a['level'] ?? 'member'] ?? 0;
        $rankB = ROLE_HIERARCHY[$b['level'] ?? 'member'] ?? 0;
        return $rankB - $rankA;
    });

    // Designations that get full contact info (mobile + email)
    // President and General Secretary at each level
    $fullContactKeywords = ['president', 'general secretary'];

    // Shape data for public display
    $team = array_values(array_map(function($m) use ($fullContactKeywords) {
        $designation = $m['designation'] ?? 'Primary Member';
        $roleStatus = $m['role_status'] ?? 'active';
        $isInactive = in_array($roleStatus, ['resigned', 'terminated']);

        // Check if designation is President or General Secretary
        $desigLower = strtolower($designation);
        $showFullContact = false;
        foreach ($fullContactKeywords as $kw) {
            // Match "President" or "General Secretary" but not "Vice-President"
            if ($kw === 'president') {
                if (preg_match('/\bpresident\b/i', $desigLower) && !preg_match('/vice/i', $desigLower)) {
                    $showFullContact = true;
                    break;
                }
            } else {
                if (strpos($desigLower, $kw) !== false) {
                    $showFullContact = true;
                    break;
                }
            }
        }

        $entry = [
            'name'          => $m['fullname'] ?? '',
            'designation'   => $designation,
            'level'         => $m['level'] ?? 'member',
            'state'         => $m['state'] ?? '',
            'district'      => $m['district'] ?? '',
            'institution'   => $m['institution'] ?? '',
            'photo'         => $m['photo'] ?? '',
            'member_id'     => $m['member_id'] ?? '',
            'role_status'   => $roleStatus,
        ];

        // Contact info rules:
        // Resigned/Terminated: no contact info at all
        // National level President/General Secretary: org official phone + email
        // Other level President/General Secretary: personal mobile + email
        // Everyone else: email only
        $memberLevel = $m['level'] ?? 'member';
        if ($isInactive) {
            $entry['email'] = '';
            $entry['mobile'] = '';
        } elseif ($showFullContact && $memberLevel === 'national') {
            $entry['email'] = $m['email'] ?? '';
            $entry['mobile'] = ORG_OFFICIAL_PHONE;
        } elseif ($showFullContact) {
            $entry['email'] = $m['email'] ?? '';
            $entry['mobile'] = $m['mobile'] ?? '';
        } else {
            $entry['email'] = $m['email'] ?? '';
            $entry['mobile'] = '';
        }

        return $entry;
    }, $visible));

    ok($team, count($team) . ' team members');
}

// ── GET DESIGNATIONS FOR A ROLE ─────────────────────────────
if (preg_match('#^/designations/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $role = $matches[1];
    $designations = get_designations_for_role($role);
    ok($designations);
}

// ── BULK UPDATE (for sync operations) ───────────────────────
if ($path === '/sync-team' && $method === 'POST') {
    $claims = require_permission('team.manage');
    // This endpoint ensures all user accounts reflect their member data
    $members = DB::findAll('primary_members');
    $synced = 0;
    foreach ($members as $m) {
        if (($m['status'] ?? '') !== 'approved') continue;
        $user = DB::findOne('users', 'email', $m['email'] ?? '');
        if ($user) {
            $updates = [];
            if (!empty($m['designation']) && ($user['designation'] ?? '') !== $m['designation']) {
                $updates['designation'] = $m['designation'];
            }
            if (!empty($m['level']) && ($user['role'] ?? '') !== $m['level']) {
                // Only sync if member level is a valid role and higher than current
                if (isset(ROLE_HIERARCHY[$m['level']])) {
                    $updates['role'] = $m['level'];
                }
            }
            if ($updates) {
                DB::updateOne('users', $user['_id'], $updates);
                $synced++;
            }
        }
    }
    ok(['synced' => $synced], "$synced user accounts synchronized");
}

// ── GET ALL DESIGNATIONS (full catalog) ─────────────────────
if ($path === '/designations-catalog' && $method === 'GET') {
    ok(get_all_designations());
}

// ── ADD A NEW DESIGNATION TO A LEVEL ────────────────────────
if ($path === '/designations' && $method === 'POST') {
    $claims = require_permission('designations.manage');
    if (!is_designation_admin($claims)) {
        err('Only National President, Vice-President, General Secretary, or Joint Secretary can manage designations', 403);
    }
    $data = get_request_data();
    $level = $data['level'] ?? '';
    $title = trim($data['title'] ?? '');
    if (!$level || !$title) err('level and title are required');
    $all = get_all_designations();
    if (!isset($all[$level])) err("Invalid level: $level");
    if (in_array($title, $all[$level])) err('Designation already exists at this level');
    // Save to custom file
    $customFile = DATA_DIR . '/designations_custom.json';
    $custom = file_exists($customFile) ? (json_decode(file_get_contents($customFile), true) ?: []) : [];
    $custom[$level] = $custom[$level] ?? [];
    $custom[$level][] = $title;
    file_put_contents($customFile, json_encode($custom, JSON_PRETTY_PRINT), LOCK_EX);
    ok(['level' => $level, 'title' => $title], "Designation '$title' added to $level level");
}

// ── DELETE A DESIGNATION FROM A LEVEL ───────────────────────
if ($path === '/designations' && $method === 'DELETE') {
    $claims = require_permission('designations.manage');
    if (!is_designation_admin($claims)) {
        err('Only National President, Vice-President, General Secretary, or Joint Secretary can manage designations', 403);
    }
    $data = get_request_data();
    $level = $data['level'] ?? '';
    $title = trim($data['title'] ?? '');
    if (!$level || !$title) err('level and title are required');
    // Add to deleted file
    $deletedFile = DATA_DIR . '/designations_deleted.json';
    $deleted = file_exists($deletedFile) ? (json_decode(file_get_contents($deletedFile), true) ?: []) : [];
    $deleted[$level] = $deleted[$level] ?? [];
    if (!in_array($title, $deleted[$level])) $deleted[$level][] = $title;
    file_put_contents($deletedFile, json_encode($deleted, JSON_PRETTY_PRINT), LOCK_EX);
    // Also remove from custom if present
    $customFile = DATA_DIR . '/designations_custom.json';
    if (file_exists($customFile)) {
        $custom = json_decode(file_get_contents($customFile), true) ?: [];
        if (isset($custom[$level])) {
            $custom[$level] = array_values(array_filter($custom[$level], fn($d) => $d !== $title));
            file_put_contents($customFile, json_encode($custom, JSON_PRETTY_PRINT), LOCK_EX);
        }
    }
    ok(['level' => $level, 'title' => $title], "Designation '$title' removed from $level level");
}

// ── ASSIGN DESIGNATION(S) TO A MEMBER ───────────────────────
// Handles the max-3 rule and approval workflow for multiple designations
if (preg_match('#^/assign-designation/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('members.assign_designation');
    $_id = $matches[1];
    $data = get_request_data();
    $newDesignation = trim($data['designation'] ?? '');
    $level = $data['level'] ?? '';
    $note = $data['note'] ?? '';
    if (!$newDesignation) err('designation is required');

    $member = DB::findOne('primary_members', '_id', $_id)
           ?: DB::findOne('primary_members', 'member_id', $_id);
    if (!$member) err('Member not found', 404);

    // Count current designations
    $currentCount = count_member_designations($member);

    // Check max 3
    if ($currentCount >= MAX_DESIGNATIONS_PER_PERSON) {
        err('Maximum ' . MAX_DESIGNATIONS_PER_PERSON . ' designations allowed per individual. This member already has ' . $currentCount, 400);
    }

    // If this would be a 2nd or 3rd designation, require approval
    if ($currentCount >= 1) {
        // Create approval request
        $approval = [
            '_id'            => DB::makeId(),
            'member_id'      => $member['member_id'] ?? $member['_id'],
            'member_name'    => $member['fullname'] ?? '',
            'member_internal_id' => $member['_id'],
            'designation'    => $newDesignation,
            'level'          => $level ?: ($member['level'] ?? 'member'),
            'current_designations' => array_filter([
                $member['designation'] ?? null,
                ...array_map(fn($r) => $r['designation'] ?? null, $member['additional_roles'] ?? [])
            ]),
            'designation_number' => $currentCount + 1,
            'requested_by'  => $claims['sub'],
            'requested_by_name' => $claims['name'] ?? '',
            'note'          => $note,
            'status'        => 'pending',
            'created_at'    => gmdate('Y-m-d\TH:i:s\Z'),
        ];
        $approvals = DB::loadCollection('designation_approvals');
        array_unshift($approvals, $approval);
        DB::saveCollection('designation_approvals', $approvals);
        ok($approval, 'Approval required. This is designation #' . ($currentCount + 1) . ' for this member. Awaiting approval from a National Officer.');
    } else {
        // First designation — auto-assign, no approval needed
        $updates = ['designation' => $newDesignation];
        if ($level) $updates['level'] = $level;
        $updates['role_status'] = 'appointed';
        $updates['last_promoted_at'] = gmdate('Y-m-d\TH:i:s\Z');
        $updates['last_promoted_by'] = $claims['sub'];
        DB::updateOne('primary_members', $member['_id'], $updates);
        // Update user account
        $user = DB::findOne('users', 'email', $member['email'] ?? '');
        if ($user) {
            $userUp = ['designation' => $newDesignation];
            if ($level && isset(ROLE_HIERARCHY[$level])) $userUp['role'] = $level;
            DB::updateOne('users', $user['_id'], $userUp);
        }
        // Log history
        $historyEntry = [
            '_id' => DB::makeId(), 'member_id' => $member['member_id'] ?? $member['_id'],
            'member_name' => $member['fullname'] ?? '', 'action' => 'appointed',
            'old_designation' => $member['designation'] ?? '', 'new_designation' => $newDesignation,
            'old_role' => $member['level'] ?? 'member', 'new_role' => $level ?: ($member['level'] ?? 'member'),
            'old_level' => $member['level'] ?? '', 'new_level' => $level ?: ($member['level'] ?? ''),
            'promoted_by' => $claims['sub'], 'promoted_by_name' => $claims['name'] ?? '',
            'note' => $note, 'effective_date' => gmdate('Y-m-d'), 'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
        $history = DB::loadCollection('promotion_history');
        array_unshift($history, $historyEntry);
        DB::saveCollection('promotion_history', $history);
        ok($historyEntry, "Designation '$newDesignation' assigned successfully (no approval needed for first designation)");
    }
}

// ── GET PENDING APPROVALS ───────────────────────────────────
if ($path === '/pending-approvals' && $method === 'GET') {
    $claims = require_permission('designations.approve');
    $approvals = DB::loadCollection('designation_approvals');
    $pending = array_values(array_filter($approvals, fn($a) => ($a['status'] ?? '') === 'pending'));
    ok($pending, count($pending) . ' pending approvals');
}

// ── APPROVE A DESIGNATION REQUEST ───────────────────────────
if (preg_match('#^/approve-designation/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('designations.approve');
    if (!is_designation_admin($claims)) {
        err('Only National President, Vice-President, General Secretary, or Joint Secretary can approve', 403);
    }
    $approvalId = $matches[1];
    $approvals = DB::loadCollection('designation_approvals');
    $found = false;
    foreach ($approvals as &$a) {
        if (($a['_id'] ?? '') === $approvalId && ($a['status'] ?? '') === 'pending') {
            $a['status'] = 'approved';
            $a['approved_by'] = $claims['sub'];
            $a['approved_by_name'] = $claims['name'] ?? '';
            $a['approved_at'] = gmdate('Y-m-d\TH:i:s\Z');
            $found = true;
            // Apply the designation
            $member = DB::findOne('primary_members', '_id', $a['member_internal_id'] ?? '')
                   ?: DB::findOne('primary_members', 'member_id', $a['member_id'] ?? '');
            if ($member) {
                $additionalRoles = $member['additional_roles'] ?? [];
                $additionalRoles[] = [
                    'designation' => $a['designation'],
                    'level' => $a['level'] ?? $member['level'] ?? '',
                    'assigned_at' => gmdate('Y-m-d\TH:i:s\Z'),
                    'assigned_by' => $claims['sub'],
                    'approval_id' => $approvalId,
                ];
                DB::updateOne('primary_members', $member['_id'], [
                    'additional_roles' => $additionalRoles,
                    'role_status' => 'additional_responsibility',
                ]);
                // History
                $historyEntry = [
                    '_id' => DB::makeId(), 'member_id' => $a['member_id'],
                    'member_name' => $a['member_name'], 'action' => 'additional_responsibility',
                    'old_designation' => $member['designation'] ?? '',
                    'new_designation' => ($member['designation'] ?? '') . ' + ' . $a['designation'],
                    'old_role' => $member['level'] ?? '', 'new_role' => $member['level'] ?? '',
                    'old_level' => $member['level'] ?? '', 'new_level' => $member['level'] ?? '',
                    'promoted_by' => $claims['sub'], 'promoted_by_name' => $claims['name'] ?? '',
                    'note' => 'Multi-designation approved (designation #' . ($a['designation_number'] ?? '?') . ')',
                    'effective_date' => gmdate('Y-m-d'), 'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
                ];
                $history = DB::loadCollection('promotion_history');
                array_unshift($history, $historyEntry);
                DB::saveCollection('promotion_history', $history);
            }
            break;
        }
    }
    unset($a);
    if (!$found) err('Approval not found or already processed', 404);
    DB::saveCollection('designation_approvals', $approvals);
    ok(null, 'Designation approved and applied successfully');
}

// ── REJECT A DESIGNATION REQUEST ────────────────────────────
if (preg_match('#^/reject-designation/([^/]+)$#', $path, $matches) && $method === 'POST') {
    $claims = require_permission('designations.approve');
    if (!is_designation_admin($claims)) {
        err('Only National President, Vice-President, General Secretary, or Joint Secretary can reject', 403);
    }
    $approvalId = $matches[1];
    $data = get_request_data();
    $approvals = DB::loadCollection('designation_approvals');
    $found = false;
    foreach ($approvals as &$a) {
        if (($a['_id'] ?? '') === $approvalId && ($a['status'] ?? '') === 'pending') {
            $a['status'] = 'rejected';
            $a['rejected_by'] = $claims['sub'];
            $a['rejected_by_name'] = $claims['name'] ?? '';
            $a['rejected_at'] = gmdate('Y-m-d\TH:i:s\Z');
            $a['rejection_reason'] = $data['reason'] ?? '';
            $found = true;
            break;
        }
    }
    unset($a);
    if (!$found) err('Approval not found or already processed', 404);
    DB::saveCollection('designation_approvals', $approvals);
    ok(null, 'Designation request rejected');
}

err('Endpoint not found', 404);
