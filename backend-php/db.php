<?php
// =============================================================
//  db.php — JSON-file-based data store (PHP port of db.py)
// =============================================================

require_once __DIR__ . '/config.php';

class DB {

    // ── File Helpers ─────────────────────────────────────────────
    private static function path(string $collection): string {
        return DATA_DIR . "/$collection.json";
    }

    private static function load(string $collection): array {
        $p = self::path($collection);
        if (!file_exists($p)) return [];
        $content = file_get_contents($p);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private static function save(string $collection, array $data): void {
        $p = self::path($collection);
        file_put_contents($p, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    // ── CRUD ─────────────────────────────────────────────────────

    public static function insert(string $collection, array $doc): array {
        if (!isset($doc['_id']))        $doc['_id'] = self::makeId();
        if (!isset($doc['created_at'])) $doc['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
        if (!isset($doc['status']))     $doc['status'] = 'pending';

        $data = self::load($collection);
        $data[] = $doc;
        self::save($collection, $data);
        return $doc;
    }

    public static function findAll(string $collection): array {
        return self::load($collection);
    }

    public static function findOne(string $collection, string $field, $value): ?array {
        foreach (self::load($collection) as $doc) {
            if (isset($doc[$field]) && $doc[$field] === $value) {
                return $doc;
            }
        }
        return null;
    }

    public static function findMany(string $collection, ?string $field = null, $value = null, ?array $filters = null): array {
        $data = self::load($collection);
        if ($filters) {
            return array_values(array_filter($data, function ($d) use ($filters) {
                foreach ($filters as $k => $v) {
                    if (($d[$k] ?? null) !== $v) return false;
                }
                return true;
            }));
        }
        if ($field) {
            return array_values(array_filter($data, fn($d) => ($d[$field] ?? null) === $value));
        }
        return $data;
    }

    public static function updateOne(string $collection, string $_id, array $updates): ?array {
        $data = self::load($collection);
        foreach ($data as &$doc) {
            if (($doc['_id'] ?? '') === $_id) {
                foreach ($updates as $k => $v) $doc[$k] = $v;
                $doc['updated_at'] = gmdate('Y-m-d\TH:i:s\Z');
                self::save($collection, $data);
                return $doc;
            }
        }
        return null;
    }

    public static function deleteOne(string $collection, string $_id): bool {
        $data = self::load($collection);
        $new = array_values(array_filter($data, fn($d) => ($d['_id'] ?? '') !== $_id));
        $changed = count($new) < count($data);
        if ($changed) self::save($collection, $new);
        return $changed;
    }

    public static function count(string $collection, ?array $filters = null): int {
        return count($filters ? self::findMany($collection, null, null, $filters) : self::findAll($collection));
    }

    public static function loadCollection(string $collection): array {
        return self::findAll($collection);
    }

    public static function saveCollection(string $collection, array $data): void {
        self::save($collection, $data);
    }

    public static function makeId(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // ── State Code Map ──────────────────────────────────────────

    public static function stateCode(string $stateName): string {
        $codes = [
            'Andhra Pradesh'=>'AP','Arunachal Pradesh'=>'AR','Assam'=>'AS','Bihar'=>'BR',
            'Chhattisgarh'=>'CG','Delhi'=>'DL','Goa'=>'GA','Gujarat'=>'GJ','Haryana'=>'HR',
            'Himachal Pradesh'=>'HP','Jammu & Kashmir'=>'JK','Jharkhand'=>'JH',
            'Karnataka'=>'KA','Kerala'=>'KL','Madhya Pradesh'=>'MP','Maharashtra'=>'MH',
            'Manipur'=>'MN','Meghalaya'=>'ML','Mizoram'=>'MZ','Nagaland'=>'NL',
            'Odisha'=>'OD','Punjab'=>'PB','Rajasthan'=>'RJ','Sikkim'=>'SK',
            'Tamil Nadu'=>'TN','Telangana'=>'TS','Tripura'=>'TR','Uttar Pradesh'=>'UP',
            'Uttarakhand'=>'UK','West Bengal'=>'WB',
        ];
        return $codes[$stateName] ?? 'XX';
    }

    // ── ID Generators ───────────────────────────────────────────

    public static function genMemberId(string $stateName): string {
        $sc   = self::stateCode($stateName);
        $year = gmdate('y');
        $seq  = self::count('primary_members') + 1;
        return sprintf('AISU%s%s%04d', $sc, $year, $seq);
    }

    public static function genStudentId(string $stateName): string {
        $sc   = self::stateCode($stateName);
        $year = gmdate('Y');
        $seq  = self::count('student_members') + 1;
        return sprintf('AISUSM%s%s%06d', $sc, $year, $seq);
    }

    public static function genAffiliationId(): string {
        $year = gmdate('Y');
        $seq  = self::count('affiliations') + 1;
        return sprintf('FIYAOA%s%04d', $year, $seq);
    }

    public static function genComplaintId(): string {
        $year = gmdate('y');
        $seq  = self::count('complaints') + 1;
        return sprintf('AISUCMP%s%05d', $year, $seq);
    }

    public static function genCertId(string $progCode = 'COMP'): string {
        $year = gmdate('Y');
        $seq  = self::count('certificates') + 1;
        return sprintf('AISUCERT%s%s%06d', strtoupper($progCode), $year, $seq);
    }

    public static function genInnovationId(): string {
        $year = gmdate('Y');
        $seq  = self::count('innovations') + 1;
        return sprintf('AISUIC%s%04d', $year, $seq);
    }

    public static function genCompetitionId(): string {
        $year = gmdate('Y');
        $seq  = self::count('competitions') + 1;
        return sprintf('AISUCOMP%s%04d', $year, $seq);
    }

    // ── Expiry Helpers ──────────────────────────────────────────

    public static function daysUntilExpiry(string $approvedAtIso, int $validityYears): ?int {
        try {
            $approved = new DateTime(str_replace('Z', '', $approvedAtIso));
            $expiry = clone $approved;
            $expiry->modify("+{$validityYears} years");
            $now = new DateTime();
            return (int) $now->diff($expiry)->format('%r%a');
        } catch (Exception $e) {
            return null;
        }
    }

    public static function isExpired(string $approvedAtIso, int $validityYears): bool {
        $d = self::daysUntilExpiry($approvedAtIso, $validityYears);
        return $d !== null && $d <= 0;
    }

    public static function getExpiryDate(string $approvedAtIso, int $validityYears): string {
        try {
            $approved = new DateTime(str_replace('Z', '', $approvedAtIso));
            $expiry = clone $approved;
            $expiry->modify("+{$validityYears} years");
            return $expiry->format('d-m-Y');
        } catch (Exception $e) {
            return 'N/A';
        }
    }
}
