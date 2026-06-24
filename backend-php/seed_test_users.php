<?php
/**
 * seed_test_users.php
 * 
 * Creates test login accounts for all RBAC designation levels.
 * Each test account has designated email, password, role, and panels.
 * 
 * Usage: php backend-php/seed_test_users.php
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/rbac.php';

echo "=== Seeding Test Users for All Designation Levels ===\n\n";

$testUsers = [
    // Level 1: Member
    [
        'name'        => 'Test Member',
        'email'       => 'test.member@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'member',
        'designation' => 'Primary Member',
        'level'       => 'member',
        'state'       => 'Bihar',
        'panels'      => SIDEBAR_PANELS['member'] ?? [],
    ],
    // Level 2: Institutional
    [
        'name'        => 'Test Institutional',
        'email'       => 'test.institutional@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'institutional',
        'designation' => 'College Head',
        'level'       => 'institutional',
        'state'       => 'Delhi',
        'panels'      => SIDEBAR_PANELS['institutional'] ?? [],
    ],
    // Level 3: Mandal
    [
        'name'        => 'Test Mandal',
        'email'       => 'test.mandal@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'mandal',
        'designation' => 'Mandal President',
        'level'       => 'mandal',
        'state'       => 'Uttar Pradesh',
        'panels'      => SIDEBAR_PANELS['mandal'] ?? [],
    ],
    // Level 4: District
    [
        'name'        => 'Test District',
        'email'       => 'test.district@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'district',
        'designation' => 'District President',
        'level'       => 'district',
        'state'       => 'Maharashtra',
        'panels'      => SIDEBAR_PANELS['district'] ?? [],
    ],
    // Level 5: State
    [
        'name'        => 'Test State',
        'email'       => 'test.state@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'state',
        'designation' => 'State President',
        'level'       => 'state',
        'state'       => 'Rajasthan',
        'panels'      => SIDEBAR_PANELS['state'] ?? [],
    ],
    // Level 6: Treasurer
    [
        'name'        => 'Test Treasurer',
        'email'       => 'test.treasurer@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'treasurer',
        'designation' => 'National Treasurer',
        'level'       => 'treasurer',
        'state'       => 'ALL',
        'panels'      => SIDEBAR_PANELS['treasurer'] ?? [],
    ],
    // Level 7: Secretary
    [
        'name'        => 'Test Secretary',
        'email'       => 'test.secretary@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'secretary',
        'designation' => 'National General Secretary',
        'level'       => 'secretary',
        'state'       => 'ALL',
        'panels'      => SIDEBAR_PANELS['secretary'] ?? [],
    ],
    // Level 8: Vice President
    [
        'name'        => 'Test Vice President',
        'email'       => 'test.vp@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'vp',
        'designation' => 'National Vice-President',
        'level'       => 'vp',
        'state'       => 'ALL',
        'panels'      => SIDEBAR_PANELS['vp'] ?? [],
    ],
    // Level 9: National (already exists as admin@aisu4india.in, but create a second one)
    [
        'name'        => 'Test National',
        'email'       => 'test.national@aisu4india.in',
        'password'    => 'Test@AISU2024',
        'role'        => 'national',
        'designation' => 'National President',
        'level'       => 'national',
        'state'       => 'ALL',
        'panels'      => SIDEBAR_PANELS['national'] ?? [],
    ],
];

$created = 0;
$skipped = 0;

foreach ($testUsers as $user) {
    // Check if user already exists
    $existing = DB::findOne('users', 'email', $user['email']);
    if ($existing) {
        // Update existing user with latest config
        DB::updateOne('users', $existing['_id'], [
            'name'        => $user['name'],
            'role'        => $user['role'],
            'designation' => $user['designation'],
            'level'       => $user['level'],
            'state'       => $user['state'],
            'status'      => 'active',
        ]);
        echo "  ⚡ Updated: {$user['email']} ({$user['role']})\n";
        $skipped++;
        continue;
    }

    // Create new user
    $userId = DB::makeId();
    $newUser = [
        '_id'         => $userId,
        'name'        => $user['name'],
        'email'       => $user['email'],
        'password'    => hash_password($user['password']),
        'role'        => $user['role'],
        'designation' => $user['designation'],
        'level'       => $user['level'],
        'state'       => $user['state'],
        'status'      => 'active',
        'panels'      => $user['panels'],
        'created_at'  => gmdate('Y-m-d\TH:i:s\Z'),
    ];
    DB::insert('users', $newUser);
    $created++;
    echo "  ✓ Created: {$user['email']} ({$user['role']}) — Password: {$user['password']}\n";
}

echo "\n=== Summary ===\n";
echo "Created: $created new users\n";
echo "Already existed (updated): $skipped\n";
echo "\n=== Test Login Credentials ===\n";
echo str_pad('Email', 40) . str_pad('Password', 22) . str_pad('Role', 15) . 'Designation' . PHP_EOL;
echo str_repeat('-', 100) . PHP_EOL;
foreach ($testUsers as $user) {
    echo str_pad($user['email'], 40) . str_pad($user['password'], 22) . str_pad($user['role'], 15) . $user['designation'] . PHP_EOL;
}
?>
