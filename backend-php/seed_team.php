<?php
// =============================================================
//  seed_team.php — Seed sample team members with avatar photos
//  Run: php backend-php/seed_team.php
// =============================================================

!defined('DATA_DIR') && define('DATA_DIR', __DIR__ . '/data');
!defined('UPLOAD_DIR') && define('UPLOAD_DIR', __DIR__ . '/uploads');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

// ── Team Members Data ───────────────────────────────────────
$members = [
    // ── National Level ───────────────────────────────────────
    [
        'fullname'    => 'Dr. Rajesh Kumar Singh',
        'designation' => 'National President',
        'level'       => 'national',
        'state'       => 'Delhi',
        'district'    => 'New Delhi',
        'mobile'      => '9876543210',
        'email'       => 'president@aisu4india.in',
        'initials'    => 'RS',
        'color'       => '#FF6F0F',
    ],
    [
        'fullname'    => 'Ms. Priya Sharma',
        'designation' => 'National Vice President',
        'level'       => 'national',
        'state'       => 'Delhi',
        'district'    => 'New Delhi',
        'mobile'      => '9876543211',
        'email'       => 'vp@aisu4india.in',
        'initials'    => 'PS',
        'color'       => '#E85D04',
    ],
    [
        'fullname'    => 'Mr. Amit Verma',
        'designation' => 'National General Secretary',
        'level'       => 'national',
        'state'       => 'Uttar Pradesh',
        'district'    => 'Lucknow',
        'mobile'      => '9876543212',
        'email'       => 'generalsecretary@aisu4india.in',
        'initials'    => 'AV',
        'color'       => '#DC2F02',
    ],
    [
        'fullname'    => 'Ms. Sneha Patel',
        'designation' => 'National Treasurer',
        'level'       => 'national',
        'state'       => 'Gujarat',
        'district'    => 'Ahmedabad',
        'mobile'      => '9876543213',
        'email'       => 'treasurer@aisu4india.in',
        'initials'    => 'SP',
        'color'       => '#9D0208',
    ],
    [
        'fullname'    => 'Mr. Vikram Joshi',
        'designation' => 'National Joint Secretary',
        'level'       => 'national',
        'state'       => 'Maharashtra',
        'district'    => 'Mumbai',
        'mobile'      => '9876543214',
        'email'       => 'jointsecretary@aisu4india.in',
        'initials'    => 'VJ',
        'color'       => '#6A040F',
    ],

    // ── State Level ──────────────────────────────────────────
    [
        'fullname'    => 'Mr. Sunil Yadav',
        'designation' => 'State President',
        'level'       => 'state',
        'state'       => 'Bihar',
        'district'    => 'Patna',
        'mobile'      => '9876543220',
        'email'       => 'bihar.president@aisu4india.in',
        'initials'    => 'SY',
        'color'       => '#3B82F6',
    ],
    [
        'fullname'    => 'Ms. Anjali Mishra',
        'designation' => 'State Secretary',
        'level'       => 'state',
        'state'       => 'Bihar',
        'district'    => 'Muzaffarpur',
        'mobile'      => '9876543221',
        'email'       => 'bihar.secretary@aisu4india.in',
        'initials'    => 'AM',
        'color'       => '#2563EB',
    ],
    [
        'fullname'    => 'Mr. Rohan Deshmukh',
        'designation' => 'State President',
        'level'       => 'state',
        'state'       => 'Maharashtra',
        'district'    => 'Pune',
        'mobile'      => '9876543222',
        'email'       => 'maharashtra.president@aisu4india.in',
        'initials'    => 'RD',
        'color'       => '#1D4ED8',
    ],
    [
        'fullname'    => 'Ms. Deepika Reddy',
        'designation' => 'State Secretary',
        'level'       => 'state',
        'state'       => 'Telangana',
        'district'    => 'Hyderabad',
        'mobile'      => '9876543223',
        'email'       => 'telangana.secretary@aisu4india.in',
        'initials'    => 'DR',
        'color'       => '#1E40AF',
    ],
    [
        'fullname'    => 'Mr. Arvind Nair',
        'designation' => 'State President',
        'level'       => 'state',
        'state'       => 'Kerala',
        'district'    => 'Thiruvananthapuram',
        'mobile'      => '9876543224',
        'email'       => 'kerala.president@aisu4india.in',
        'initials'    => 'AN',
        'color'       => '#3B82F6',
    ],

    // ── District Level ───────────────────────────────────────
    [
        'fullname'    => 'Mr. Pankaj Kumar',
        'designation' => 'District President',
        'level'       => 'district',
        'state'       => 'Bihar',
        'district'    => 'West Champaran',
        'mobile'      => '9876543230',
        'email'       => 'westchamparan.president@aisu4india.in',
        'initials'    => 'PK',
        'color'       => '#8B5CF6',
    ],
    [
        'fullname'    => 'Ms. Kavita Singh',
        'designation' => 'District Secretary',
        'level'       => 'district',
        'state'       => 'Bihar',
        'district'    => 'East Champaran',
        'mobile'      => '9876543231',
        'email'       => 'eastchamparan.secretary@aisu4india.in',
        'initials'    => 'KS',
        'color'       => '#7C3AED',
    ],
    [
        'fullname'    => 'Mr. Dinesh Gupta',
        'designation' => 'District President',
        'level'       => 'district',
        'state'       => 'Uttar Pradesh',
        'district'    => 'Varanasi',
        'mobile'      => '9876543232',
        'email'       => 'varanasi.president@aisu4india.in',
        'initials'    => 'DG',
        'color'       => '#6D28D9',
    ],
    [
        'fullname'    => 'Ms. Meena Rao',
        'designation' => 'District Secretary',
        'level'       => 'district',
        'state'       => 'Karnataka',
        'district'    => 'Bengaluru Urban',
        'mobile'      => '9876543233',
        'email'       => 'bangalore.secretary@aisu4india.in',
        'initials'    => 'MR',
        'color'       => '#5B21B6',
    ],

    // ── Mandal Level ─────────────────────────────────────────
    [
        'fullname'    => 'Mr. Suresh Ram',
        'designation' => 'Mandal President',
        'level'       => 'mandal',
        'state'       => 'Bihar',
        'district'    => 'West Champaran',
        'mobile'      => '9876543240',
        'email'       => 'bagaha.president@aisu4india.in',
        'initials'    => 'SR',
        'color'       => '#F59E0B',
    ],
    [
        'fullname'    => 'Ms. Rita Devi',
        'designation' => 'Mandal Secretary',
        'level'       => 'mandal',
        'state'       => 'Bihar',
        'district'    => 'West Champaran',
        'mobile'      => '9876543241',
        'email'       => 'narkatiaganj.secretary@aisu4india.in',
        'initials'    => 'RD',
        'color'       => '#D97706',
    ],
    [
        'fullname'    => 'Mr. Gopal Das',
        'designation' => 'Mandal President',
        'level'       => 'mandal',
        'state'       => 'Uttar Pradesh',
        'district'    => 'Gorakhpur',
        'mobile'      => '9876543242',
        'email'       => 'gorakhpur.mandal@aisu4india.in',
        'initials'    => 'GD',
        'color'       => '#B45309',
    ],

    // ── Institutional Level ──────────────────────────────────
    [
        'fullname'    => 'Mr. Ravi Teja',
        'designation' => 'Institutional Coordinator',
        'level'       => 'institutional',
        'state'       => 'Telangana',
        'district'    => 'Hyderabad',
        'mobile'      => '9876543250',
        'email'       => 'ou.campus@aisu4india.in',
        'initials'    => 'RT',
        'color'       => '#22C55E',
    ],
    [
        'fullname'    => 'Ms. Neha Agarwal',
        'designation' => 'Institutional Secretary',
        'level'       => 'institutional',
        'state'       => 'Delhi',
        'district'    => 'New Delhi',
        'mobile'      => '9876543251',
        'email'       => 'du.campus@aisu4india.in',
        'initials'    => 'NA',
        'color'       => '#16A34A',
    ],
    [
        'fullname'    => 'Mr. Irfan Khan',
        'designation' => 'Institutional Coordinator',
        'level'       => 'institutional',
        'state'       => 'Maharashtra',
        'district'    => 'Mumbai',
        'mobile'      => '9876543252',
        'email'       => 'mumbai.campus@aisu4india.in',
        'initials'    => 'IK',
        'color'       => '#15803D',
    ],
];

// ── Generate SVG Avatar ─────────────────────────────────────
function generateAvatar($initials, $color, $filename) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$color};stop-opacity:1" />
      <stop offset="100%" style="stop-color:{$color};stop-opacity:0.7" />
    </linearGradient>
    <clipPath id="circle">
      <circle cx="100" cy="100" r="95"/>
    </clipPath>
  </defs>
  <circle cx="100" cy="100" r="95" fill="url(#bg)" stroke="#fff" stroke-width="3"/>
  <circle cx="100" cy="75" r="32" fill="rgba(255,255,255,0.25)"/>
  <text x="100" y="85" font-family="Arial, sans-serif" font-size="36" font-weight="bold" fill="#fff" text-anchor="middle" dominant-baseline="middle">{$initials}</text>
  <ellipse cx="100" cy="155" rx="38" ry="18" fill="rgba(255,255,255,0.18)"/>
</svg>
SVG;
    file_put_contents($filename, $svg);
    echo "  Created: $filename\n";
}

// ── Create Upload Directory ─────────────────────────────────
$photoDir = UPLOAD_DIR . '/photo';
if (!is_dir($photoDir)) {
    mkdir($photoDir, 0755, true);
    echo "Created directory: $photoDir\n";
}

// ── Generate Photos & Insert Members ────────────────────────
echo "Seeding team members...\n\n";
$now = gmdate('Y-m-d\TH:i:s\Z');

foreach ($members as $i => $m) {
    $photoFilename = strtolower(str_replace(' ', '_', $m['fullname'])) . '.svg';
    $photoPath = $photoDir . '/' . $photoFilename;

    // Generate avatar SVG
    generateAvatar($m['initials'], $m['color'], $photoPath);

    // Generate member ID
    $memberId = 'AISU' . str_pad((string)(1001 + $i), 6, '0', STR_PAD_LEFT);

    // Check for duplicates (skip if email already exists)
    $existingMember = DB::findOne('primary_members', 'email', $m['email']);
    if ($existingMember) {
        echo "  Skipped (exists): {$m['fullname']}\n";
        continue;
    }

    // Insert into primary_members
    DB::insert('primary_members', [
        'member_id'          => $memberId,
        'fullname'           => strtoupper($m['fullname']),
        'designation'        => $m['designation'],
        'level'              => $m['level'],
        'state'              => $m['state'],
        'district'           => $m['district'],
        'mobile'             => $m['mobile'],
        'email'              => $m['email'],
        'photo'              => $photoFilename,
        'status'             => 'approved',
        'role_status'        => 'active',
        'approved_at'        => $now,
        'expiry_date'        => date('Y-m-d\TH:i:s\Z', strtotime('+3 years')),
        'created_at'         => $now,
        'updated_at'         => $now,
    ]);

    // Insert corresponding user account
    $existingUser = DB::findOne('users', 'email', $m['email']);
    if (!$existingUser) {
        $defaultPw = substr($m['mobile'], -4) . '@AISU';
        DB::insert('users', [
            'name'        => $m['fullname'],
            'email'       => $m['email'],
            'password'    => hash_password($defaultPw),
            'role'        => $m['level'],
            'level'       => $m['level'],
            'designation' => $m['designation'],
            'state'       => $m['state'],
            'district'    => $m['district'],
            'mobile'      => $m['mobile'],
            'member_id'   => $memberId,
            'status'      => 'active',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    echo "  Inserted: {$m['fullname']} ({$m['designation']}) — $memberId\n";
}

echo "\n✅ Seeded " . count($members) . " team members with avatar photos!\n";
echo "   Photos saved to: backend-php/uploads/photo/\n";
echo "   Login passwords: last 4 digits of mobile + @AISU (e.g., 3210@AISU)\n";
