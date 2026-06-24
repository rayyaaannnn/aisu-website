<?php
// =============================================================
//  migrate.php — Import JSON data files into SQLite
//  Run: php backend-php/migrate.php
// =============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "=== AISU JSON → SQLite Migration Tool ===\n\n";

// ── 1. Test SQLite connection ───────────────────────────────
try {
    DB::findAll('users'); // triggers connection + table creation
    echo "[OK] Connected to SQLite at " . DB_PATH . "\n";
} catch (Exception $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
    exit(1);
}

// ── 2. Collection mappings ──────────────────────────────────
$collections = [
    'users'                      => 'users',
    'primary_members'            => 'primary_members',
    'student_members'            => 'student_members',
    'complaints'                 => 'complaints',
    'contacts'                   => 'contacts',
    'certificates'               => 'certificates',
    'cert_templates'             => 'cert_templates',
    'competitions'               => 'competitions',
    'competition_registrations'  => 'competition_registrations',
    'internships'                => 'internships',
    'affiliations'               => 'affiliations',
    'innovations'                => 'innovations',
    'newsletter_subscriptions'   => 'newsletter_subscriptions',
    'password_otps'              => 'password_otps',
    'promotion_history'          => 'promotion_history',
    'designation_approvals'      => 'designation_approvals',
    'announcements'              => 'announcements',
    'press_releases'             => 'press_releases',
    'gallery'                    => 'gallery',
];

// Also check alternate names
$altFiles = [
    'newsletter' => 'newsletter_subscriptions',
];

$totalImported = 0;
$totalSkipped = 0;

foreach ($collections as $jsonName => $table) {
    $filePath = DATA_DIR . '/' . $jsonName . '.json';
    
    if (!file_exists($filePath)) {
        // Check alternate name
        $found = false;
        foreach ($altFiles as $altJson => $altTable) {
            if ($altTable === $table) {
                $altPath = DATA_DIR . '/' . $altJson . '.json';
                if (file_exists($altPath)) {
                    $filePath = $altPath;
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) {
            echo "[SKIP] $jsonName — file not found (will be created on first use)\n";
            $totalSkipped++;
            continue;
        }
    }

    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    
    if (!is_array($data) || count($data) === 0) {
        echo "[SKIP] $jsonName — empty\n";
        $totalSkipped++;
        continue;
    }

    echo "[MIGRATE] $jsonName → $table (" . count($data) . " records)\n";

    // Ensure table exists by calling findAll first
    DB::findAll($table);

    // Clear the table
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->exec("DELETE FROM `$table`");

    // Insert each document
    $inserted = 0;
    $errors = 0;
    foreach ($data as $doc) {
        try {
            DB::insert($table, $doc);
            $inserted++;
        } catch (Exception $e) {
            echo "  [ERROR] " . ($doc['_id'] ?? 'unknown') . ": " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    echo "  → Inserted: $inserted, Errors: $errors\n";
    $totalImported += $inserted;
}

// ── 3. Handle special files ─────────────────────────────────
// quiz_rooms - stored in a separate location
$quizRoomsFile = DATA_DIR . '/quiz_rooms.json';
if (file_exists($quizRoomsFile)) {
    $content = file_get_contents($quizRoomsFile);
    $rooms = json_decode($content, true);
    if (is_array($rooms) && count($rooms) > 0) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        // Ensure table exists
        try { DB::findAll('quiz_rooms'); } catch (Exception $e) {}
        $pdo->exec("DELETE FROM `quiz_rooms`");
        $inserted = 0;
        foreach ($rooms as $code => $room) {
            $room['room_code'] = $code;
            try {
                DB::insert('quiz_rooms', $room);
                $inserted++;
            } catch (Exception $e) {
                echo "  [ERROR] quiz_room $code: " . $e->getMessage() . "\n";
            }
        }
        echo "[MIGRATE] quiz_rooms → quiz_rooms ($inserted rooms)\n";
        $totalImported += $inserted;
    } else {
        echo "[SKIP] quiz_rooms — empty\n";
    }
} else {
    echo "[SKIP] quiz_rooms — file not found\n";
}

// ── 4. Update sequences to match current counts ─────────────
echo "\n[UPDATE] Setting sequence values based on current record counts...\n";
$sequences = [
    'primary_members' => 'primary_members',
    'student_members' => 'student_members',
    'affiliations'    => 'affiliations',
    'complaints'      => 'complaints',
    'certificates'    => 'certificates',
    'innovations'     => 'innovations',
    'competitions'    => 'competitions',
];

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->exec("CREATE TABLE IF NOT EXISTS sequences (name TEXT PRIMARY KEY, next_val INTEGER NOT NULL DEFAULT 1)");

foreach ($sequences as $seqName => $table) {
    // Skip if table doesn't exist yet
    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    if (!$check->fetch()) {
        echo "  $seqName — table not yet created, skipping\n";
        continue;
    }
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
    $count = (int) $stmt->fetch()['cnt'];
    $nextVal = $count + 1;
    
    $pdo->exec("INSERT OR REPLACE INTO sequences (name, next_val) VALUES ('$seqName', $nextVal)");
    echo "  $seqName → next ID: $nextVal\n";
}

// ── 5. Summary ──────────────────────────────────────────────
echo "\n=== Migration Complete ===\n";
echo "  Total records imported: $totalImported\n";
echo "  Collections skipped: $totalSkipped\n";

if ($totalImported > 0) {
    echo "\n[NOTE] The JSON files in backend-php/data/ are preserved as backup.\n";
    echo "       Database file: " . DB_PATH . "\n";
}
echo "\nDone.\n";
