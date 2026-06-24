<?php
/**
 * update_dates_from_csv.php
 * 
 * Updates created_at (Date of Joining) and approved_at (Date of ID Issued)
 * for all primary members using dates from the Google Sheet CSV.
 * 
 * Usage: php backend-php/update_dates_from_csv.php
 */

require_once __DIR__ . '/db.php';

$csvPath = __DIR__ . '/data/aisu_team_sheet.csv';

if (!file_exists($csvPath)) {
    die("CSV not found: $csvPath\n");
}

$f = fopen($csvPath, 'r');
if (!$f) {
    die("Failed to open CSV\n");
}

$ln = 0;
$updated = 0;
$notFound = 0;
$emptyDate = 0;

while (($row = fgetcsv($f)) !== false) {
    $ln++;
    
    // Skip header rows (lines 1-9 are metadata/headers)
    if ($ln <= 9) continue;
    if (count($row) < 14) continue;
    
    $memberId = trim($row[3] ?? '');       // ID Number
    $name     = trim($row[1] ?? '');        // Name
    $doj      = trim($row[6] ?? '');        // Date of Joining
    $doi      = trim($row[13] ?? '');       // Date of ID Issued
    
    if (empty($memberId)) continue;
    
    // Find member in database
    $member = DB::findOne('primary_members', 'member_id', $memberId);
    if (!$member) {
        $notFound++;
        continue;
    }
    
    $updates = [];
    
    // Parse date string in DD-MM-YYYY or DD/MM/YYYY format
    // Returns ISO 8601 string or null if invalid
    $parseDate = function($dateStr) {
        $normalized = str_replace('/', '-', $dateStr);
        $parts = explode('-', $normalized);
        if (count($parts) === 3) {
            $day = (int)$parts[0];
            $month = (int)$parts[1];
            $year = (int)$parts[2];
            if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2030) {
                return sprintf('%04d-%02d-%02dT00:00:00Z', $year, $month, $day);
            }
        }
        return null;
    };
    
    // Parse Date of Joining (DD-MM-YYYY format)
    if (!empty($doj)) {
        $parsed = $parseDate($doj);
        if ($parsed) {
            $updates['created_at'] = $parsed;
        }
    }
    
    // Parse Date of ID Issued (DD-MM-YYYY format)
    if (!empty($doi)) {
        $parsed = $parseDate($doi);
        if ($parsed) {
            $updates['approved_at'] = $parsed;
        }
    }
    
    if (empty($updates)) {
        $emptyDate++;
        continue;
    }
    
    DB::updateOne('primary_members', $member['_id'], $updates);
    $updated++;
    
    if ($updated <= 5 || ($updated % 50 === 0)) {
        echo "  ✓ {$member['member_id']} - {$member['fullname']}: ";
        if (isset($updates['created_at'])) echo "DOJ={$updates['created_at']} ";
        if (isset($updates['approved_at'])) echo "DOI={$updates['approved_at']}";
        echo PHP_EOL;
    }
}

fclose($f);

echo PHP_EOL;
echo "=== Done ===\n";
echo "Updated: $updated members\n";
echo "Not found (ID not in DB): $notFound\n";
echo "No date in CSV: $emptyDate\n";
