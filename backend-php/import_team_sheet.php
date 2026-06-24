<?php
// =============================================================
//  import_team_sheet.php — Import members from Google Sheet CSV
//  Reads: backend-php/data/aisu_team_sheet.csv
//  Run:   php backend-php/import_team_sheet.php
// =============================================================

!defined('DATA_DIR') && define('DATA_DIR', __DIR__ . '/data');
!defined('UPLOAD_DIR') && define('UPLOAD_DIR', __DIR__ . '/uploads');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

$csvFile = __DIR__ . '/data/aisu_team_sheet.csv';

if (!file_exists($csvFile)) {
    die("CSV file not found: $csvFile\nDownload it first:\n  curl -sL 'https://docs.google.com/spreadsheets/d/1vnSgXDhmdW_dtcUO6ZogQUwcFBdotuphmRDIAro7oAI/export?format=csv' > \"$csvFile\"\n");
}

// ── Read CSV ────────────────────────────────────────────────
$rows = array_map('str_getcsv', file($csvFile));
echo "Total rows in CSV: " . count($rows) . "\n";

// Skip header rows (rows 0-7 are org info, row 8 is column headers)
// Data starts at row 9 (index 8) ... but let's find the column header row
$headerRowIdx = null;
$dataStartIdx = null;

foreach ($rows as $i => $row) {
    $first = trim($row[0] ?? '');
    $second = trim($row[1] ?? '');
    if (preg_match('/^S\.?\s*No/i', $first) && preg_match('/^Name/i', $second)) {
        $headerRowIdx = $i;
        $dataStartIdx = $i + 1;
        echo "Found header row at index $i: " . json_encode(array_slice($row, 0, 10)) . "\n";
        break;
    }
}

if ($dataStartIdx === null) {
    die("Could not find column header row in CSV\n");
}

// Column mapping (0-indexed)
$colIdx = [
    'name'        => 1, // Name
    'designation' => 2, // Designation at time of issued
    'member_id'   => 3, // ID Number
    'mobile'      => 4, // Mobile No
    'email'       => 5, // Email
    'area'        => 7, // Area Name
    'district'    => 8, // District
    'state'       => 9, // State
    'issued'      => 10, // Issued/Not issued
    'status'      => 11, // Status
];

// ── Helper: Determine level from designation ─────────────────
function determineLevel($designation) {
    $d = strtoupper($designation);
    
    // National level
    if (preg_match('/\bNATIONAL\b/', $d)) return 'national';
    
    // State level
    if (preg_match('/\b(STATE|STATE\s+\w+)\s+(PRESIDENT|VICE|GENERAL|SECRETARY|COORDINATOR|CONVENOR|JOINT|ADDITIONAL|ORGANIZING|SPOKE)/i', $designation)) {
        return 'state';
    }
    if (preg_match('/\b(PRESIDENT\s+OF|GENERAL\s+SECRETARY\s+OF|STATE\s+PRESIDENT|STATE\s+SECRETARY|STATE\s+COORDINATOR|STATE\s+JOINT|STATE\s+ADDITIONAL|STATE\s+ORGANIZING|STATE\s+CONVENOR|STATE\s+SPOKE|STATE\s+VICE|STATE\s+GENERAL)\b/i', $designation)) return 'state';
    if (preg_match('/\b(COORDINATOR\s+OF\s+\w+|UNION\s+TERRITORY\s+(PRESIDENT|VICE|GENERAL|SECRETARY))\b/i', $designation)) return 'state';
    
    // District level
    if (preg_match('/\b(DISTRICT|DISTRICT\s+\w+)\s+(PRESIDENT|VICE|SECRETARY|GENERAL|COORDINATOR|JOINT|ADDITIONAL|ORGANIZING|SPOKE|INCHARGE|CONVENOR)\b/i', $designation)) return 'district';
    if (preg_match('/\bDISTRICT\s+(PRESIDENT|SECRETARY|COORDINATOR|JOINT|ADDITIONAL|ORGANIZING|SPOKE|GENERAL|VICE)/i', $designation)) return 'district';
    
    // Mandal level
    if (preg_match('/\bMANDAL\b/i', $designation)) return 'mandal';
    
    // Institutional / College level
    if (preg_match('/\b(COLLEGE|CAMPUS|INSTITUTE|INSTITUTIONAL|SCHOOL)\b/i', $designation)) return 'institutional';
    if (preg_match('/\b(CAMPUS\s+(HEAD|AMBASSADOR|COORDINATOR)|COLLEGE\s+(HEAD|AMBASSADOR|COORDINATOR|PRESIDENT|MEMBER))\b/i', $designation)) return 'institutional';
    
    // By default, if it mentions a specific area/division type
    if (preg_match('/\b(MEMBER|CELL|WING|DEPT|DIVISIONAL|DIVISION)\b/i', $designation)) return 'member';
    
    return 'member';
}

// ── Helper: Generate initials from name ──────────────────────
function getInitials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        if (!empty($p) && !preg_match('/^(MR|MS|MRS|DR|SMT|SHRI)\.?$/i', $p)) {
            $initials .= strtoupper(mb_substr($p, 0, 1));
        }
    }
    return $initials ?: '?';
}

// ── Helper: Color palette for levels ─────────────────────────
function getLevelColor($level) {
    $colors = [
        'national'      => '#FF6F0F',
        'state'         => '#3B82F6',
        'district'      => '#8B5CF6',
        'mandal'        => '#F59E0B',
        'institutional' => '#22C55E',
        'member'        => '#64748B',
    ];
    return $colors[$level] ?? '#64748B';
}

// ── Helper: Generate SVG Avatar ──────────────────────────────
function generateAvatar($initials, $color, $filename) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$color};stop-opacity:1" />
      <stop offset="100%" style="stop-color:{$color};stop-opacity:0.7" />
    </linearGradient>
  </defs>
  <circle cx="100" cy="100" r="95" fill="url(#bg)" stroke="#fff" stroke-width="3"/>
  <circle cx="100" cy="75" r="32" fill="rgba(255,255,255,0.25)"/>
  <text x="100" y="85" font-family="Arial, sans-serif" font-size="36" font-weight="bold" fill="#fff" text-anchor="middle" dominant-baseline="middle">{$initials}</text>
  <ellipse cx="100" cy="155" rx="38" ry="18" fill="rgba(255,255,255,0.18)"/>
</svg>
SVG;
    file_put_contents($filename, $svg);
}

// ── Ensure photo dir exists ─────────────────────────────────
$photoDir = UPLOAD_DIR . '/photo';
if (!is_dir($photoDir)) {
    mkdir($photoDir, 0755, true);
}

// ── Clear old team data ─────────────────────────────────────
echo "Clearing old team data...\n";
$db = new PDO('sqlite:' . DATA_DIR . '/aisu.sqlite');
$db->exec("DELETE FROM primary_members WHERE status = 'approved'");
// Keep admin/student users, remove imported member users
$db->exec("DELETE FROM users WHERE email LIKE '%@aisu4india.in' AND email NOT IN ('admin@aisu4india.in')");

// ── Import Data ──────────────────────────────────────────────
echo "\nImporting team members...\n";
$imported = 0;
$skipped = 0;
$now = gmdate('Y-m-d\TH:i:s\Z');

for ($i = $dataStartIdx; $i < count($rows); $i++) {
    $row = $rows[$i];
    $name = trim($row[$colIdx['name']] ?? '');
    $designation = trim($row[$colIdx['designation']] ?? '');
    $memberId = trim($row[$colIdx['member_id']] ?? '');
    $mobile = trim($row[$colIdx['mobile']] ?? '');
    $email = trim($row[$colIdx['email']] ?? '');
    $area = trim($row[$colIdx['area']] ?? '');
    $district = trim($row[$colIdx['district']] ?? '');
    $state = trim($row[$colIdx['state']] ?? '');
    $issued = trim($row[$colIdx['issued']] ?? '');
    $status = trim($row[$colIdx['status']] ?? '');

    // Skip empty rows
    if (empty($name) && empty($designation)) continue;

    // Skip section headers within data (like "NATIONAL EXECUTIVE COMMITTEE" etc.)
    if (empty($name) && preg_match('/\b(COMMITTEE|LEVEL|SECTION|DETAILS|OFFICE)\b/i', $designation)) continue;
    if (empty($memberId) && empty($mobile) && empty($email) && !empty($name)) {
        // Check if this is a section header
        if (!preg_match('/^[A-Za-z\s.]+$/', $name) || strlen($name) < 3) {
            $skipped++;
            continue;
        }
    }

    // Clean mobile - remove spaces, +91 etc.
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (strlen($mobile) > 10) $mobile = substr($mobile, -10);
    if (strlen($mobile) < 10) $mobile = ''; // Skip invalid mobiles

    // Skip if no name
    if (empty($name)) { $skipped++; continue; }

    // Determine level
    $level = determineLevel($designation);
    
    // Clean email
    if (empty($email) && !empty($mobile)) {
        // Generate a placeholder email if none provided
        $email = strtolower(str_replace(' ', '.', $name)) . '@aisu4india.in';
    }

    // Generate member ID if missing
    if (empty($memberId)) {
        $memberId = 'AISU' . str_pad((string)(2000 + $imported), 6, '0', STR_PAD_LEFT);
    }

    // Generate photo
    $photoFilename = 'member_' . preg_replace('/[^a-z0-9]/', '', strtolower($name)) . '.svg';
    $photoPath = $photoDir . '/' . $photoFilename;
    
    $initials = getInitials($name);
    $color = getLevelColor($level);
    generateAvatar($initials, $color, $photoPath);

    // Map status
    $roleStatus = 'active';
    $memberStatus = 'approved';
    if (stripos($status, 'expired') !== false || stripos($issued, 'expired') !== false) {
        $roleStatus = 'expired';
        $memberStatus = 'expired';
    } elseif (stripos($issued, 'not issued') !== false) {
        $roleStatus = 'pending';
        $memberStatus = 'pending';
    }

    // Insert into primary_members
    try {
        DB::insert('primary_members', [
            'member_id'          => $memberId,
            'fullname'           => strtoupper($name),
            'designation'        => $designation,
            'level'              => $level,
            'state'              => $state,
            'district'           => $district,
            'city'               => $area,
            'institution'        => $area,
            'mobile'             => $mobile,
            'email'              => $email,
            'photo'              => $photoFilename,
            'status'             => $memberStatus,
            'role_status'        => $roleStatus,
            'approved_at'        => $now,
            'expiry_date'        => $roleStatus === 'expired' ? '' : date('Y-m-d\TH:i:s\Z', strtotime('+3 years')),
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
    } catch (Exception $e) {
        echo "  ERROR: {$e->getMessage()} for {$name}\n";
        $skipped++;
        continue;
    }

    // Create user account (skip for expired/pending)
    if ($roleStatus === 'active' && !empty($email)) {
        $existingUser = DB::findOne('users', 'email', $email);
        if (!$existingUser) {
            $defaultPw = !empty($mobile) ? substr($mobile, -4) . '@AISU' : 'aisu@123';
            try {
                DB::insert('users', [
                    'name'        => $name,
                    'email'       => $email,
                    'password'    => hash_password($defaultPw),
                    'role'        => $level,
                    'level'       => $level,
                    'designation' => $designation,
                    'state'       => $state,
                    'district'    => $district,
                    'mobile'      => $mobile,
                    'member_id'   => $memberId,
                    'status'      => 'active',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            } catch (Exception $e) {
                // User may already exist - skip
            }
        }
    }

    $imported++;
    if ($imported % 50 === 0) {
        echo "  Progress: {$imported} members imported...\n";
    }
}

echo "\n✅ Import complete!\n";
echo "   Imported: {$imported} members\n";
echo "   Skipped:  {$skipped} rows\n";
echo "   Photos:   backend-php/uploads/photo/\n";
echo "   Logins:   last 4 digits of mobile + @AISU (if mobile available)\n";
