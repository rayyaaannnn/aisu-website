<?php
// =============================================================
//  rbac.php — Role-Based Access Control Engine
//  Defines role hierarchy, permissions matrix, and helpers
// =============================================================

/**
 * ROLE HIERARCHY (higher index = more power)
 * Each role maps to a numeric level for comparison.
 */
define('ROLE_HIERARCHY', [
    'user'          => 0,
    'member'        => 1,
    'institutional' => 2,
    'mandal'        => 3,
    'district'      => 4,
    'state'         => 5,
    'treasurer'     => 6,
    'secretary'     => 7,
    'vp'            => 8,
    'national'      => 9,
]);

/**
 * PERMISSIONS MATRIX
 * Each role has a list of permissions they can perform.
 */
define('ROLE_PERMISSIONS', [
    'national' => [
        'dashboard.view', 'dashboard.stats',
        'members.list', 'members.view', 'members.approve', 'members.reject',
        'members.promote', 'members.demote', 'members.transfer', 'members.terminate',
        'members.assign_designation', 'members.view_history',
        'students.list', 'students.approve', 'students.reject',
        'complaints.list', 'complaints.update', 'complaints.resolve',
        'competitions.create', 'competitions.manage',
        'internships.list', 'internships.approve',
        'affiliations.list', 'affiliations.approve',
        'certificates.issue', 'certificates.revoke', 'certificates.templates',
        'quiz.create', 'quiz.manage',
        'announcements.post', 'announcements.delete',
        'press.publish', 'gallery.manage',
        'users.list', 'users.create', 'users.update', 'users.deactivate',
        'rbac.manage', 'rbac.view_all',
        'designations.manage', 'designations.approve',
        'team.manage',
        'renewals.manage',
        'reports.view', 'reports.export',
    ],
    'vp' => [
        'dashboard.view', 'dashboard.stats',
        'members.list', 'members.view', 'members.approve', 'members.reject',
        'members.promote', 'members.demote', 'members.transfer',
        'members.assign_designation', 'members.view_history',
        'students.list', 'students.approve', 'students.reject',
        'complaints.list', 'complaints.update', 'complaints.resolve',
        'competitions.create', 'competitions.manage',
        'internships.list', 'internships.approve',
        'affiliations.list', 'affiliations.approve',
        'certificates.issue', 'certificates.templates',
        'quiz.create', 'quiz.manage',
        'announcements.post',
        'press.publish', 'gallery.manage',
        'rbac.view_all',
        'designations.manage', 'designations.approve',
        'team.manage',
        'renewals.manage',
        'reports.view',
    ],
    'secretary' => [
        'dashboard.view', 'dashboard.stats',
        'members.list', 'members.view', 'members.approve', 'members.reject',
        'members.promote', 'members.assign_designation', 'members.view_history',
        'students.list', 'students.approve', 'students.reject',
        'complaints.list', 'complaints.update',
        'competitions.create', 'competitions.manage',
        'internships.list', 'internships.approve',
        'affiliations.list',
        'certificates.issue', 'certificates.templates',
        'quiz.create',
        'announcements.post',
        'press.publish',
        'designations.manage', 'designations.approve',
        'team.manage',
        'renewals.manage',
        'reports.view',
    ],
    'treasurer' => [
        'dashboard.view', 'dashboard.stats',
        'members.list', 'members.view',
        'students.list',
        'renewals.manage',
        'reports.view',
    ],
    'state' => [
        'dashboard.view',
        'members.list', 'members.view', 'members.approve', 'members.reject',
        'members.promote', 'members.assign_designation', 'members.view_history',
        'students.list', 'students.approve',
        'complaints.list', 'complaints.update',
        'internships.list',
        'announcements.post',
        'team.manage',
    ],
    'district' => [
        'dashboard.view',
        'members.list', 'members.view',
        'members.view_history',
        'students.list',
        'complaints.list',
        'internships.list',
        'announcements.post',
    ],
    'mandal' => [
        'dashboard.view',
        'members.list', 'members.view',
        'students.list',
    ],
    'institutional' => [
        'dashboard.view',
        'members.view',
        'students.list',
    ],
    'member' => [
        'dashboard.view',
    ],
    'user' => [],
]);

/**
 * DESIGNATION MAP — Complete organizational designations at each level.
 * This is the DEFAULT set. Custom designations are stored in data/designations_custom.json
 * and merged at runtime via get_all_designations().
 */
define('DESIGNATION_MAP_DEFAULT', [
    'national' => [
        'National President',
        'National Vice-President',
        'National General Secretary',
        'National Joint Secretary',
        'National Additional Joint Secretary-1',
        'National Additional Joint Secretary-2',
        'National Organizing Secretary',
        'National Additional Organizing Secretary',
        'National Coordinator',
        'National Convenor (Social Media Dept)',
        'National Co-Convenor (Social Media Dept)',
        'National General Secretary (Social Media Dept)',
        'National Secretary (Social Media Dept)',
        'National Convenor (Press Dept)',
        'National Co-Convenor (Press Dept)',
        'National General Secretary (Press Dept)',
        'National Secretary (Press Dept)',
        'National Convenor (IT Cell)',
        'National Co-Convenor (IT Cell)',
        'National General Secretary (IT Cell)',
        'National Secretary (IT Cell)',
        'National Convenor (Legal Dept)',
        'National Co-Convenor (Legal Dept)',
        'National General Secretary (Legal Dept)',
        'National Secretary (Legal Dept)',
        'National Convenor (ICell)',
        'National Co-Convenor (ICell)',
        'National General Secretary (ICell)',
        'National Secretary (ICell)',
        'National Graphic Designer',
        'National Member (Social Media Dept)',
        'National Member (Legal Dept)',
        'National Member (IT Cell)',
        'National Member (ICell)',
        'National Member (Press Dept)',
        'National Spoke Person',
        'National Incharge',
        'Regional Incharge',
    ],
    'state' => [
        'State President',
        'State Vice-President',
        'State General Secretary',
        'State Joint Secretary',
        'State Additional Joint Secretary',
        'State Organizing Secretary',
        'State Additional Organizing Secretary',
        'State Coordinator',
        'State Convenor (Social Media Dept)',
        'State General Secretary (Social Media Dept)',
        'State Convenor (Press Dept)',
        'State General Secretary (Press Dept)',
        'State Convenor (IT Cell)',
        'State General Secretary (IT Cell)',
        'State Convenor (Legal Dept)',
        'State General Secretary (Legal Dept)',
        'State Convenor (ICell)',
        'State General Secretary (ICell)',
        'State Graphic Designer',
        'State Member (Social Media Dept)',
        'State Member (Legal Dept)',
        'State Member (IT Cell)',
        'State Member (ICell)',
        'State Member (Press Dept)',
        'State Spoke Person',
        'Zonal Incharge',
    ],
    'district' => [
        'District President',
        'District Vice-President',
        'District General Secretary',
        'District Joint Secretary',
        'District Organizing Secretary',
        'District Coordinator (Administration)',
        'District Coordinator (Social Media Dept)',
        'District Coordinator (Press Dept)',
        'District Coordinator (IT Cell)',
        'District Coordinator (Legal Dept)',
        'District Coordinator (ICell)',
        'District Graphic Designer',
        'District Member (Social Media Dept)',
        'District Member (Legal Dept)',
        'District Member (IT Cell)',
        'District Member (ICell)',
        'District Member (Press Dept)',
        'District Spoke Person',
        'Divisional Incharge',
    ],
    'mandal' => [
        'Mandal President',
        'Mandal Vice-President',
        'Mandal General Secretary',
        'Mandal Joint Secretary',
        'Mandal Organizing Secretary',
        'Mandal Coordinator (Administration)',
        'Mandal Coordinator (Social Media)',
        'Mandal Coordinator (IT Cell)',
        'Mandal Coordinator (Press Dept)',
        'Mandal Spoke Person',
        'Mandal Member',
        'Village Incharge',
        'Village Member',
    ],
    'institutional' => [
        'College Head',
        'College Ambassador',
        'College Member',
    ],
    'member' => ['Primary Member'],
]);

/**
 * DESIGNATION PRIVILEGE ROLES — Only these designations can add/delete designations
 */
define('DESIGNATION_ADMIN_TITLES', [
    'National President',
    'National Vice-President',
    'National General Secretary',
    'National Joint Secretary',
]);

/**
 * MAX DESIGNATIONS PER INDIVIDUAL
 */
define('MAX_DESIGNATIONS_PER_PERSON', 3);

/**
 * SIDEBAR PANELS — which panels each role can see in admin dashboard
 */
define('SIDEBAR_PANELS', [
    'national'      => ['dashboard','primary-members','student-members','complaints','competitions','internships','icell','affiliations','cert-templates','cert-generate','quiz-rooms','announcements','press','users','rbac-management','team-management','promotion-history','designation-management','designation-approvals'],
    'vp'            => ['dashboard','primary-members','student-members','complaints','competitions','internships','icell','affiliations','cert-templates','cert-generate','quiz-rooms','announcements','press','rbac-management','team-management','promotion-history','designation-management','designation-approvals'],
    'secretary'     => ['dashboard','primary-members','student-members','complaints','competitions','internships','affiliations','cert-templates','cert-generate','quiz-rooms','announcements','press','team-management','promotion-history','designation-management','designation-approvals'],
    'treasurer'     => ['dashboard','primary-members','student-members'],
    'state'         => ['dashboard','primary-members','student-members','complaints','internships','announcements','team-management','promotion-history'],
    'district'      => ['dashboard','primary-members','student-members','complaints','internships','announcements'],
    'mandal'        => ['dashboard','primary-members','student-members'],
    'institutional' => ['dashboard','student-members'],
    'member'        => ['dashboard'],
]);


// ═══════════════════════════════════════════════════════════════
//  RBAC HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

/**
 * Get numeric rank for a role.
 */
function role_rank(string $role): int {
    return ROLE_HIERARCHY[$role] ?? -1;
}

/**
 * Check if role A outranks role B.
 */
function outranks(string $roleA, string $roleB): bool {
    return role_rank($roleA) > role_rank($roleB);
}

/**
 * Check if a role has a specific permission.
 */
function has_permission(string $role, string $permission): bool {
    $perms = ROLE_PERMISSIONS[$role] ?? [];
    return in_array($permission, $perms);
}

/**
 * Require a specific permission. Exits with 403 if not granted.
 */
function require_permission(string $permission): array {
    $claims = require_auth();
    $role = $claims['role'] ?? '';
    if (!has_permission($role, $permission)) {
        err("Permission denied: $permission", 403);
    }
    return $claims;
}

/**
 * Get all permissions for a role.
 */
function get_permissions(string $role): array {
    return ROLE_PERMISSIONS[$role] ?? [];
}

/**
 * Get sidebar panels allowed for a role.
 */
function get_allowed_panels(string $role): array {
    return SIDEBAR_PANELS[$role] ?? [];
}

/**
 * Load custom designations from data file. Returns merged default + custom.
 */
function get_all_designations(): array {
    $defaults = DESIGNATION_MAP_DEFAULT;
    $customFile = DATA_DIR . '/designations_custom.json';
    $custom = [];
    if (file_exists($customFile)) {
        $data = json_decode(file_get_contents($customFile), true);
        if (is_array($data)) $custom = $data;
    }
    // Merge: add custom designations that are not already in defaults
    foreach ($custom as $level => $desigs) {
        if (!isset($defaults[$level])) {
            $defaults[$level] = $desigs;
        } else {
            foreach ($desigs as $d) {
                if (!in_array($d, $defaults[$level])) {
                    $defaults[$level][] = $d;
                }
            }
        }
    }
    // Also check for deletions
    $deletedFile = DATA_DIR . '/designations_deleted.json';
    if (file_exists($deletedFile)) {
        $deleted = json_decode(file_get_contents($deletedFile), true);
        if (is_array($deleted)) {
            foreach ($deleted as $level => $delDesigs) {
                if (isset($defaults[$level])) {
                    $defaults[$level] = array_values(array_filter($defaults[$level], function($d) use ($delDesigs) {
                        return !in_array($d, $delDesigs);
                    }));
                }
            }
        }
    }
    return $defaults;
}

/**
 * Get valid designations for a role/level.
 */
function get_designations_for_role(string $role): array {
    $all = get_all_designations();
    // Map RBAC roles to designation levels
    $roleToLevel = [
        'national' => 'national',
        'vp'       => 'national',
        'secretary'=> 'national',
        'treasurer'=> 'national',
        'state'    => 'state',
        'district' => 'district',
        'mandal'   => 'mandal',
        'institutional' => 'institutional',
        'member'   => 'member',
    ];
    $level = $roleToLevel[$role] ?? 'member';
    return $all[$level] ?? ['Primary Member'];
}

/**
 * Check if a user can promote/manage another user.
 * A user can only manage roles below their own rank.
 */
function can_manage_role(string $managerRole, string $targetRole): bool {
    return outranks($managerRole, $targetRole);
}

/**
 * Check if a user holds one of the privileged designations
 * that allows managing the designation catalog.
 */
function is_designation_admin(array $claims): bool {
    $designation = $claims['designation'] ?? '';
    $role = $claims['role'] ?? '';
    // Check by designation title
    if (in_array($designation, DESIGNATION_ADMIN_TITLES)) return true;
    // Check by role (national, vp, secretary cover top-4)
    if (in_array($role, ['national', 'vp', 'secretary'])) return true;
    return false;
}

/**
 * Count how many designations a member currently holds.
 * Primary designation + additional_roles array.
 */
function count_member_designations(array $member): int {
    $count = 0;
    if (!empty($member['designation']) && $member['designation'] !== 'Primary Member') {
        $count = 1;
    }
    $additional = $member['additional_roles'] ?? [];
    $count += count($additional);
    return max($count, empty($member['designation']) ? 0 : 1);
}
