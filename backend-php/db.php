<?php
// =============================================================
//  db.php — SQLite PDO data store (replaces JSON file store)
//  Preserves exact same public API as the JSON version.
//  All route files work without modification.
// =============================================================

require_once __DIR__ . '/config.php';

class DB {

    private static ?PDO $pdo = null;

    // ── Collection → Table name mapping ────────────────────────
    private const TABLE_MAP = [
        'newsletter' => 'newsletter_subscriptions',
    ];

    // ── Known boolean columns (to restore proper types on read) ─
    private const BOOL_COLUMNS = [
        'verified', 'quiz_started', 'quiz_submitted', 'disqualified',
        'is_anonymous', 'is_confidential', 'quiz_disqualified',
    ];

    // ── Store initialized tables so we only CREATE TABLE once ──
    private static array $initDone = [];

    // ── PDO Connection (lazy, with auto-schema init) ──────────
    private static function db(): PDO {
        if (self::$pdo === null) {
            $dbPath = DB_PATH;
            $dir = dirname($dbPath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            self::$pdo = new PDO(
                'sqlite:' . $dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            // Enable WAL mode for better concurrent read performance
            self::$pdo->exec('PRAGMA journal_mode=WAL');
            self::$pdo->exec('PRAGMA foreign_keys=ON');
        }
        return self::$pdo;
    }

    // ── Table name resolution ─────────────────────────────────
    private static function tableName(string $collection): string {
        return self::TABLE_MAP[$collection] ?? $collection;
    }

    // ── Auto-create table if it doesn't exist ─────────────────
    private static function ensureTable(string $table): void {
        if (isset(self::$initDone[$table])) return;
        self::$initDone[$table] = true;

        $db = self::db();
        // Check if table exists
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) return;

        // Table doesn't exist — create it from schema.sql
        static::tryCreateFromSchema($db, $table);
    }

    /**
     * Look up the CREATE TABLE statement for this table from schema.sql
     * and execute it, plus any CREATE INDEX statements.
     */
    private static function tryCreateFromSchema(PDO $db, string $table): void {
        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            $db->exec("CREATE TABLE IF NOT EXISTS `$table` (
                _id TEXT PRIMARY KEY,
                created_at TEXT DEFAULT NULL,
                updated_at TEXT DEFAULT NULL
            )");
            return;
        }

        $sql = file_get_contents($schemaFile);

        // Look for CREATE TABLE IF NOT EXISTS for this specific table
        $escaped  = preg_quote($table, '/');
        $pattern  = '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?' . $escaped . '`?\s*\(([^;]+)\)/si';
        if (preg_match($pattern, $sql, $m)) {
            $createSql = "CREATE TABLE IF NOT EXISTS `$table` (" . $m[1] . ")";
            $db->exec($createSql);

            // Also execute any CREATE INDEX statements for this table
            $indexPattern = '/CREATE\s+(?:UNIQUE\s+)?INDEX\s+(?:IF\s+NOT\s+EXISTS\s+)?(\S+)\s+ON\s+`?' . $escaped . '`?\s*\(([^)]+)\)/si';
            if (preg_match_all($indexPattern, $sql, $indexMatches)) {
                foreach ($indexMatches[0] as $idxSql) {
                    try { $db->exec($idxSql); } catch (Exception $e) {}
                }
            }
            return;
        }

        // Fallback if not found in schema
        $db->exec("CREATE TABLE IF NOT EXISTS `$table` (
            _id TEXT PRIMARY KEY,
            created_at TEXT DEFAULT NULL,
            updated_at TEXT DEFAULT NULL
        )");
    }

    // ── Encode values for SQL insert/update ───────────────────
    private static function encode(array $doc): array {
        $encoded = [];
        foreach ($doc as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $encoded[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            } else {
                $encoded[$k] = $v;
            }
        }
        return $encoded;
    }

    // ── Decode values from SQL select ─────────────────────────
    private static function decode(array $row): array {
        foreach ($row as $k => $v) {
            if (is_string($v) && strlen($v) >= 2) {
                $first = $v[0];
                if (($first === '{' || $first === '[') && in_array($v[strlen($v)-1], ['}', ']'])) {
                    $decoded = json_decode($v, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $row[$k] = $decoded;
                        continue;
                    }
                }
            }
            if (in_array($k, self::BOOL_COLUMNS, true)) {
                $row[$k] = !empty($v);
            }
        }
        return $row;
    }

    // ── Build column list for INSERT ──────────────────────────
    private static function columns(array $doc): array {
        $cols = $vals = $params = [];
        foreach ($doc as $k => $v) {
            $cols[] = "`$k`";
            $vals[] = "?";
            $params[] = $v;
        }
        return [
            'cols'    => implode(', ', $cols),
            'vals'    => implode(', ', $vals),
            'params'  => $params,
        ];
    }

    // ── Get existing columns for a table ───────────────────────
    private static function getColumns(PDO $db, string $table): array {
        $stmt = $db->prepare("SELECT name FROM pragma_table_info(?)");
        $stmt->execute([$table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // ── Ensure all columns in the doc exist in the table ──────
    private static function ensureColumns(PDO $db, string $table, array $doc): void {
        $existing = self::getColumns($db, $table);
        foreach ($doc as $k => $v) {
            if (in_array($k, $existing, true)) continue;
            $type = match(true) {
                is_int($v)   => 'INTEGER',
                is_float($v) => 'REAL',
                default      => 'TEXT',
            };
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$k` $type DEFAULT NULL");
        }
    }

    // ── Insert a single row (used by insert & saveCollection) ──
    private static function insertRow(PDO $db, string $table, array $doc): void {
        self::ensureTable($table);
        $doc = self::encode($doc);
        self::ensureColumns($db, $table, $doc);
        $sql = self::columns($doc);
        $stmt = $db->prepare("INSERT INTO `$table` ({$sql['cols']}) VALUES ({$sql['vals']})");
        $stmt->execute($sql['params']);
    }

    // ── CRUD ──────────────────────────────────────────────────

    public static function insert(string $collection, array $doc): array {
        if (!isset($doc['_id']))        $doc['_id'] = self::makeId();
        if (!isset($doc['created_at'])) $doc['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
        if (!isset($doc['status']))     $doc['status'] = 'pending';

        $table = self::tableName($collection);
        self::insertRow(self::db(), $table, $doc);
        return $doc;
    }

    public static function findAll(string $collection): array {
        $table = self::tableName($collection);
        self::ensureTable($table);
        $stmt = self::db()->query("SELECT * FROM `$table`");
        return array_map([self::class, 'decode'], $stmt->fetchAll());
    }

    public static function findOne(string $collection, string $field, $value): ?array {
        $table = self::tableName($collection);
        self::ensureTable($table);
        $stmt = self::db()->prepare("SELECT * FROM `$table` WHERE `$field` = ? LIMIT 1");
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ? self::decode($row) : null;
    }

    public static function findMany(string $collection, ?string $field = null, $value = null, ?array $filters = null): array {
        $table = self::tableName($collection);
        self::ensureTable($table);
        $db = self::db();

        if ($filters) {
            $conditions = $params = [];
            foreach ($filters as $k => $v) { $conditions[] = "`$k` = ?"; $params[] = $v; }
            $stmt = $db->prepare("SELECT * FROM `$table` WHERE " . implode(' AND ', $conditions));
            $stmt->execute($params);
        } elseif ($field) {
            $stmt = $db->prepare("SELECT * FROM `$table` WHERE `$field` = ?");
            $stmt->execute([$value]);
        } else {
            $stmt = $db->query("SELECT * FROM `$table`");
        }
        return array_map([self::class, 'decode'], $stmt->fetchAll());
    }

    public static function updateOne(string $collection, string $_id, array $updates): ?array {
        $table = self::tableName($collection);
        self::ensureTable($table);
        $db = self::db();
        $updates = self::encode($updates);

        $sets = $params = [];
        foreach ($updates as $k => $v) { $sets[] = "`$k` = ?"; $params[] = $v; }
        $sets[] = '`updated_at` = ?';
        $params[] = gmdate('Y-m-d\TH:i:s\Z');
        $params[] = $_id;

        $stmt = $db->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `_id` = ?");
        $stmt->execute($params);

        $stmt2 = $db->prepare("SELECT * FROM `$table` WHERE `_id` = ?");
        $stmt2->execute([$_id]);
        $row = $stmt2->fetch();
        return $row ? self::decode($row) : null;
    }

    public static function deleteOne(string $collection, string $_id): bool {
        $table = self::tableName($collection);
        self::ensureTable($table);
        $stmt = self::db()->prepare("DELETE FROM `$table` WHERE `_id` = ?");
        $stmt->execute([$_id]);
        return $stmt->rowCount() > 0;
    }

    public static function count(string $collection, ?array $filters = null): int {
        $table = self::tableName($collection);
        self::ensureTable($table);
        $db = self::db();

        if ($filters) {
            $conditions = $params = [];
            foreach ($filters as $k => $v) { $conditions[] = "`$k` = ?"; $params[] = $v; }
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM `$table` WHERE " . implode(' AND ', $conditions));
            $stmt->execute($params);
        } else {
            $stmt = $db->query("SELECT COUNT(*) as cnt FROM `$table`");
        }
        return (int) $stmt->fetch()['cnt'];
    }

    public static function loadCollection(string $collection): array {
        return self::findAll($collection);
    }

    public static function saveCollection(string $collection, array $data): void {
        $table = self::tableName($collection);
        self::ensureTable($table);
        $db = self::db();
        $db->beginTransaction();
        try {
            $db->exec("DELETE FROM `$table`");
            foreach ($data as $doc) {
                if (!isset($doc['_id'])) $doc['_id'] = self::makeId();
                if (!isset($doc['created_at'])) $doc['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
                self::insertRow($db, $table, $doc);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
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

    // ── State Code Map ─────────────────────────────────────────

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

    // ── ID Generators ─────────────────────────────────────────

    private static function nextSeq(string $name): int {
        $db = self::db();
        $db->exec("CREATE TABLE IF NOT EXISTS sequences (name TEXT PRIMARY KEY, next_val INTEGER NOT NULL DEFAULT 1)");
        $db->exec("INSERT OR IGNORE INTO sequences (name, next_val) VALUES ('$name', 1)");
        $db->exec("UPDATE sequences SET next_val = next_val + 1 WHERE name = '$name'");
        return (int) $db->query("SELECT next_val - 1 as val FROM sequences WHERE name = '$name'")->fetch()['val'];
    }

    public static function genMemberId(string $stateName): string {
        return sprintf('AISU%s%s%04d', self::stateCode($stateName), gmdate('y'), self::nextSeq('primary_members'));
    }

    public static function genStudentId(string $stateName): string {
        return sprintf('AISUSM%s%s%06d', self::stateCode($stateName), gmdate('Y'), self::nextSeq('student_members'));
    }

    public static function genAffiliationId(): string {
        return sprintf('FIYAOA%s%04d', gmdate('Y'), self::nextSeq('affiliations'));
    }

    public static function genComplaintId(): string {
        return sprintf('AISUCMP%s%05d', gmdate('y'), self::nextSeq('complaints'));
    }

    public static function genCertId(string $progCode = 'COMP'): string {
        return sprintf('AISUCERT%s%s%06d', strtoupper($progCode), gmdate('Y'), self::nextSeq('certificates'));
    }

    public static function genInnovationId(): string {
        return sprintf('AISUIC%s%04d', gmdate('Y'), self::nextSeq('innovations'));
    }

    public static function genCompetitionId(): string {
        return sprintf('AISUCOMP%s%04d', gmdate('Y'), self::nextSeq('competitions'));
    }

    // ── Expiry Helpers ─────────────────────────────────────────

    public static function daysUntilExpiry(string $approvedAtIso, int $validityYears): ?int {
        try {
            $approved = new DateTime(str_replace('Z', '', $approvedAtIso));
            $expiry = (clone $approved)->modify("+{$validityYears} years");
            return (int) (new DateTime())->diff($expiry)->format('%r%a');
        } catch (Exception $e) { return null; }
    }

    public static function isExpired(string $approvedAtIso, int $validityYears): bool {
        $d = self::daysUntilExpiry($approvedAtIso, $validityYears);
        return $d !== null && $d <= 0;
    }

    public static function getExpiryDate(string $approvedAtIso, int $validityYears): string {
        try {
            $approved = new DateTime(str_replace('Z', '', $approvedAtIso));
            return (clone $approved)->modify("+{$validityYears} years")->format('d-m-Y');
        } catch (Exception $e) { return 'N/A'; }
    }
}
