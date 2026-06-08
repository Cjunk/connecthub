<?php
/**
 * Import exported local community data into production MySQL.
 *
 * Usage:
 *   php tools/migrations/import_event_data_mysql.php /path/to/migration_payload.json
 */

require_once __DIR__ . '/../../config/constants.php';

$inputFile = $argv[1] ?? '';
if ($inputFile === '' || !is_file($inputFile)) {
    fwrite(STDERR, "Usage: php tools/migrations/import_event_data_mysql.php <payload.json>\n");
    exit(1);
}

$raw = file_get_contents($inputFile);
$payload = json_decode($raw, true);
if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
    throw new RuntimeException('Invalid payload format');
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, (int)DB_PORT, DB_NAME, defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tableColumnsCache = [];

function mysqlTableColumns(PDO $pdo, string $table, array &$cache): array {
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table");
    $stmt->execute([':table' => $table]);
    $cols = array_map(static fn($r) => $r['COLUMN_NAME'], $stmt->fetchAll());
    $cache[$table] = $cols;
    return $cols;
}

function hasCol(PDO $pdo, string $table, string $col, array &$cache): bool {
    return in_array($col, mysqlTableColumns($pdo, $table, $cache), true);
}

function normalizeStatus($status) {
    if ($status === null) {
        return null;
    }
    $s = strtolower(trim((string)$status));
    if ($s === 'active') return 'active';
    if ($s === 'inactive') return 'inactive';
    if ($s === 'pending') return 'pending';
    if ($s === 'suspended') return 'suspended';
    if ($s === '1') return 1;
    if ($s === '0') return 0;
    return $status;
}

function normalizeGroupRole($role): string {
    $r = strtolower(trim((string)$role));
    if ($r === 'creator' || $r === 'admin') return 'owner';
    if ($r === 'co_organizer') return 'co_host';
    if ($r === 'moderator') return 'moderator';
    if ($r === 'owner' || $r === 'co_host' || $r === 'member') return $r;
    return 'member';
}

function normalizeEventStatus($status): string {
    $s = strtolower(trim((string)$status));
    $allowed = ['draft', 'published', 'cancelled', 'completed'];
    return in_array($s, $allowed, true) ? $s : 'draft';
}

function normalizeRsvpStatus($status): string {
    $s = strtolower(trim((string)$status));
    $allowed = ['going', 'maybe', 'not_going', 'waitlist'];
    return in_array($s, $allowed, true) ? $s : 'going';
}

function parsePgArrayToJsonArray($value): ?string {
    if ($value === null || $value === '') {
        return null;
    }
    if (is_array($value)) {
        return json_encode(array_values($value));
    }
    $raw = trim((string)$value);
    if ($raw === '{}') {
        return json_encode([]);
    }
    if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
        $inner = substr($raw, 1, -1);
        if ($inner === '') {
            return json_encode([]);
        }
        $parts = array_map(static fn($x) => trim($x, "\" "), explode(',', $inner));
        return json_encode($parts);
    }
    return json_encode([$raw]);
}

function splitName(string $name): array {
    $name = trim($name);
    if ($name === '') return ['User', ''];
    $parts = preg_split('/\s+/', $name);
    $first = $parts[0] ?? 'User';
    $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    return [$first, $last];
}

function resolveId(array $map, int $sourceId, PDO $pdo, string $table): ?int {
    if ($sourceId <= 0) {
        return null;
    }
    if (isset($map[$sourceId])) {
        return (int)$map[$sourceId];
    }
    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $sourceId]);
    $found = $stmt->fetchColumn();
    return $found ? (int)$found : null;
}

function uniqueUsername(PDO $pdo, string $preferred): string {
    $base = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(trim($preferred)));
    if ($base === '') {
        $base = 'user';
    }
    $candidate = substr($base, 0, 45);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $candidate]);
    if (!$stmt->fetchColumn()) {
        return $candidate;
    }

    $prefix = substr($candidate, 0, 38);
    for ($i = 1; $i <= 9999; $i++) {
        $next = $prefix . '_' . $i;
        $stmt->execute([':username' => $next]);
        if (!$stmt->fetchColumn()) {
            return $next;
        }
    }

    return 'user_' . bin2hex(random_bytes(4));
}

function ensureSupplementalTables(PDO $pdo): void {
    $sql = [];

    $sql[] = "CREATE TABLE IF NOT EXISTS event_categories (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(50) NULL,
        color VARCHAR(7) NULL,
        display_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_event_categories_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $sql[] = "CREATE TABLE IF NOT EXISTS group_categories (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(50) NULL,
        color VARCHAR(7) NULL,
        display_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_group_categories_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $sql[] = "CREATE TABLE IF NOT EXISTS group_join_requests (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        group_id INT UNSIGNED NOT NULL,
        message TEXT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        requested_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL DEFAULT NULL,
        responded_by INT UNSIGNED NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_group_join_requests_user_group (user_id, group_id),
        KEY idx_group_join_requests_group (group_id),
        KEY idx_group_join_requests_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $sql[] = "CREATE TABLE IF NOT EXISTS group_role_permissions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        role VARCHAR(20) NOT NULL,
        permission VARCHAR(100) NOT NULL,
        description TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_group_role_permission (role, permission)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $sql[] = "CREATE TABLE IF NOT EXISTS group_activity_log (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        group_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        details JSON NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_group_activity_log_group (group_id),
        KEY idx_group_activity_log_user (user_id),
        KEY idx_group_activity_log_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    foreach ($sql as $statement) {
        $pdo->exec($statement);
    }
}

function insertRow(PDO $pdo, string $table, array $row): int {
    $cols = array_keys($row);
    $ph = array_map(static fn($c) => ':' . $c, $cols);
    $sql = "INSERT INTO {$table} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $ph) . ")";
    $stmt = $pdo->prepare($sql);
    $params = [];
    foreach ($row as $k => $v) {
        $params[':' . $k] = $v;
    }
    $stmt->execute($params);
    return (int)$pdo->lastInsertId();
}

function updateById(PDO $pdo, string $table, int $id, array $row): void {
    $sets = [];
    $params = [':id' => $id];
    foreach ($row as $k => $v) {
        if ($k === 'id') continue;
        $sets[] = "{$k} = :{$k}";
        $params[':' . $k] = $v;
    }
    if (empty($sets)) return;
    $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

$users = $payload['data']['users'] ?? [];
$categories = $payload['data']['categories'] ?? [];
$groups = $payload['data']['groups'] ?? [];
$groupMemberships = $payload['data']['group_memberships'] ?? [];
$events = $payload['data']['events'] ?? [];
$eventAttendees = $payload['data']['event_attendees'] ?? [];
$eventComments = $payload['data']['event_comments'] ?? [];
$commentLikes = $payload['data']['comment_likes'] ?? [];
$eventMedia = $payload['data']['event_media'] ?? [];
$eventCategories = $payload['data']['event_categories'] ?? [];
$groupCategories = $payload['data']['group_categories'] ?? [];
$groupJoinRequests = $payload['data']['group_join_requests'] ?? [];
$groupRolePermissions = $payload['data']['group_role_permissions'] ?? [];
$groupActivityLog = $payload['data']['group_activity_log'] ?? [];

$userIdMap = [];
$categoryIdByName = [];
$groupIdMap = [];
$eventIdMap = [];
$commentIdMap = [];

ensureSupplementalTables($pdo);

$pdo->beginTransaction();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

try {
    // Categories
    if (hasCol($pdo, 'categories', 'name', $tableColumnsCache)) {
        foreach ($categories as $cat) {
            $name = trim((string)($cat['name'] ?? ''));
            if ($name === '') continue;
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
            $stmt->execute([':name' => $name]);
            $existing = $stmt->fetchColumn();
            if ($existing) {
                $categoryIdByName[$name] = (int)$existing;
                continue;
            }

            $row = [];
            foreach (['name', 'slug', 'description', 'icon', 'color', 'status', 'created_at'] as $col) {
                if (array_key_exists($col, $cat) && hasCol($pdo, 'categories', $col, $tableColumnsCache)) {
                    $row[$col] = $cat[$col];
                }
            }
            if (!isset($row['slug']) && hasCol($pdo, 'categories', 'slug', $tableColumnsCache)) {
                $row['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }
            if (!isset($row['status']) && hasCol($pdo, 'categories', 'status', $tableColumnsCache)) {
                $row['status'] = 1;
            }
            $newId = insertRow($pdo, 'categories', $row);
            $categoryIdByName[$name] = $newId;
        }
    }

    // Users (map local id -> target id by email)
    foreach ($users as $u) {
        $email = trim((string)($u['email'] ?? ''));
        if ($email === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $existingId = $stmt->fetchColumn();

        $targetRow = [];
        $userCols = mysqlTableColumns($pdo, 'users', $tableColumnsCache);

        foreach (['email', 'password_hash', 'phone', 'bio', 'role', 'created_at', 'updated_at', 'last_login', 'email_verified'] as $col) {
            if (array_key_exists($col, $u) && in_array($col, $userCols, true)) {
                $targetRow[$col] = $u[$col];
            }
        }

        if (in_array('status', $userCols, true)) {
            $s = normalizeStatus($u['status'] ?? null);
            if (is_string($s)) {
                $targetRow['status'] = ($s === 'active') ? 1 : 0;
            } else {
                $targetRow['status'] = $s ?? 1;
            }
        }

        if (in_array('username', $userCols, true)) {
            if (!empty($u['username'])) {
                $targetRow['username'] = $u['username'];
            } elseif (!empty($u['name'])) {
                $targetRow['username'] = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($u['name'])) ?: ('user' . ($u['id'] ?? ''));
            } else {
                $targetRow['username'] = explode('@', $email)[0];
            }
            $targetRow['username'] = uniqueUsername($pdo, (string)$targetRow['username']);
        }

        if (in_array('first_name', $userCols, true) || in_array('last_name', $userCols, true)) {
            if (!empty($u['first_name']) || !empty($u['last_name'])) {
                $first = (string)($u['first_name'] ?? 'User');
                $last = (string)($u['last_name'] ?? '');
            } else {
                [$first, $last] = splitName((string)($u['name'] ?? 'User'));
            }
            if (in_array('first_name', $userCols, true)) {
                $targetRow['first_name'] = $first;
            }
            if (in_array('last_name', $userCols, true)) {
                $targetRow['last_name'] = $last;
            }
        }

        if (in_array('name', $userCols, true)) {
            if (!empty($u['name'])) {
                $targetRow['name'] = $u['name'];
            } else {
                $targetRow['name'] = trim(($targetRow['first_name'] ?? 'User') . ' ' . ($targetRow['last_name'] ?? ''));
            }
        }

        if (in_array('membership_expires_at', $userCols, true)) {
            $targetRow['membership_expires_at'] = $u['membership_expires_at'] ?? $u['membership_expires'] ?? null;
        }
        if (in_array('membership_expires', $userCols, true)) {
            $targetRow['membership_expires'] = $u['membership_expires'] ?? $u['membership_expires_at'] ?? null;
        }

        if ($existingId) {
            $eid = (int)$existingId;
            updateById($pdo, 'users', $eid, $targetRow);
            if (!empty($u['id'])) {
                $userIdMap[(int)$u['id']] = $eid;
            }
            continue;
        }

        $localId = isset($u['id']) ? (int)$u['id'] : 0;
        if ($localId > 0 && hasCol($pdo, 'users', 'id', $tableColumnsCache)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $localId]);
            $idTaken = $stmt->fetchColumn();
            if (!$idTaken) {
                $targetRow['id'] = $localId;
                insertRow($pdo, 'users', $targetRow);
                $userIdMap[$localId] = $localId;
                continue;
            }
        }

        $newId = insertRow($pdo, 'users', $targetRow);
        if ($localId > 0) {
            $userIdMap[$localId] = $newId;
        }
    }

    // Fill category cache from target
    if (hasCol($pdo, 'categories', 'name', $tableColumnsCache)) {
        foreach ($pdo->query('SELECT id, name FROM categories')->fetchAll() as $r) {
            $categoryIdByName[(string)$r['name']] = (int)$r['id'];
        }
    }

    // Event categories (PostgreSQL-only table)
    foreach ($eventCategories as $cat) {
        $name = trim((string)($cat['name'] ?? ''));
        if ($name === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM event_categories WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);
        $existingId = $stmt->fetchColumn();

        $row = [
            'name' => $name,
            'description' => $cat['description'] ?? null,
            'icon' => $cat['icon'] ?? null,
            'color' => $cat['color'] ?? null,
            'display_order' => (int)($cat['display_order'] ?? 0),
            'is_active' => (int)(($cat['is_active'] ?? true) ? 1 : 0),
        ];
        if (isset($cat['created_at']) && hasCol($pdo, 'event_categories', 'created_at', $tableColumnsCache)) {
            $row['created_at'] = $cat['created_at'];
        }

        if ($existingId) {
            updateById($pdo, 'event_categories', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'event_categories', $row);
        }
    }

    // Group categories (PostgreSQL-only table)
    foreach ($groupCategories as $cat) {
        $name = trim((string)($cat['name'] ?? ''));
        if ($name === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM group_categories WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);
        $existingId = $stmt->fetchColumn();

        $row = [
            'name' => $name,
            'description' => $cat['description'] ?? null,
            'icon' => $cat['icon'] ?? null,
            'color' => $cat['color'] ?? null,
            'display_order' => (int)($cat['display_order'] ?? 0),
            'is_active' => (int)(($cat['is_active'] ?? true) ? 1 : 0),
        ];
        if (isset($cat['created_at']) && hasCol($pdo, 'group_categories', 'created_at', $tableColumnsCache)) {
            $row['created_at'] = $cat['created_at'];
        }

        if ($existingId) {
            updateById($pdo, 'group_categories', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'group_categories', $row);
        }
    }

    // Groups
    foreach ($groups as $g) {
        $slug = trim((string)($g['slug'] ?? ''));
        if ($slug === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM groups WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $existingId = $stmt->fetchColumn();

        $row = [];
        $cols = mysqlTableColumns($pdo, 'groups', $tableColumnsCache);

        foreach (['name', 'slug', 'description', 'created_at', 'updated_at'] as $col) {
            if (array_key_exists($col, $g) && in_array($col, $cols, true)) {
                $row[$col] = $g[$col];
            }
        }

        if (in_array('image', $cols, true)) {
            $row['image'] = $g['image'] ?? $g['cover_image'] ?? null;
        }

        if (in_array('privacy', $cols, true)) {
            $row['privacy'] = $g['privacy'] ?? $g['privacy_level'] ?? 'public';
        }
        if (in_array('privacy_level', $cols, true)) {
            $row['privacy_level'] = $g['privacy_level'] ?? $g['privacy'] ?? 'public';
        }

        if (in_array('status', $cols, true)) {
            $s = normalizeStatus($g['status'] ?? null);
            if (is_string($s)) {
                $row['status'] = ($s === 'active') ? 1 : 0;
            } else {
                $row['status'] = $s ?? 1;
            }
        }

        $localCreator = isset($g['created_by']) ? (int)$g['created_by'] : 0;
        $mappedCreator = resolveId($userIdMap, $localCreator, $pdo, 'users');
        if (!$mappedCreator) {
            continue;
        }
        if (in_array('created_by', $cols, true)) {
            $row['created_by'] = $mappedCreator;
        }
        if (in_array('organizer_id', $cols, true)) {
            $row['organizer_id'] = $mappedCreator;
        }

        if (in_array('category_id', $cols, true)) {
            if (!empty($g['category_id'])) {
                $row['category_id'] = (int)$g['category_id'];
            } elseif (!empty($g['category']) && isset($categoryIdByName[$g['category']])) {
                $row['category_id'] = $categoryIdByName[$g['category']];
            } else {
                $row['category_id'] = null;
            }
        }
        if (in_array('category', $cols, true)) {
            $row['category'] = $g['category'] ?? null;
        }

        if (in_array('location', $cols, true)) {
            $row['location'] = $g['location'] ?? null;
        }
        if (in_array('location_city', $cols, true)) {
            $row['location_city'] = $g['location_city'] ?? ($g['location'] ?? null);
        }
        if (in_array('location_state', $cols, true) && isset($g['location_state'])) {
            $row['location_state'] = $g['location_state'];
        }
        if (in_array('location_country', $cols, true) && isset($g['location_country'])) {
            $row['location_country'] = $g['location_country'];
        }

        if ($existingId) {
            $targetId = (int)$existingId;
            updateById($pdo, 'groups', $targetId, $row);
            if (!empty($g['id'])) {
                $groupIdMap[(int)$g['id']] = $targetId;
            }
            continue;
        }

        $localId = isset($g['id']) ? (int)$g['id'] : 0;
        if ($localId > 0 && hasCol($pdo, 'groups', 'id', $tableColumnsCache)) {
            $stmt = $pdo->prepare('SELECT id FROM groups WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $localId]);
            if (!$stmt->fetchColumn()) {
                $row['id'] = $localId;
                insertRow($pdo, 'groups', $row);
                $groupIdMap[$localId] = $localId;
                continue;
            }
        }

        $newId = insertRow($pdo, 'groups', $row);
        if ($localId > 0) {
            $groupIdMap[$localId] = $newId;
        }
    }

    // Group memberships
    foreach ($groupMemberships as $gm) {
        $localGroup = isset($gm['group_id']) ? (int)$gm['group_id'] : 0;
        $localUser = isset($gm['user_id']) ? (int)$gm['user_id'] : 0;
        $groupId = resolveId($groupIdMap, $localGroup, $pdo, 'groups');
        $userId = resolveId($userIdMap, $localUser, $pdo, 'users');
        if (!$groupId || !$userId) continue;

        $stmt = $pdo->prepare('SELECT id FROM group_memberships WHERE group_id = :group_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
        $existingId = $stmt->fetchColumn();

        $row = [
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => normalizeGroupRole($gm['role'] ?? 'member'),
            'status' => in_array(strtolower((string)($gm['status'] ?? 'active')), ['active', 'pending', 'banned', 'inactive'], true)
                ? strtolower((string)$gm['status'])
                : 'active',
        ];

        if (hasCol($pdo, 'group_memberships', 'joined_at', $tableColumnsCache) && isset($gm['joined_at'])) {
            $row['joined_at'] = $gm['joined_at'];
        }

        if ($existingId) {
            updateById($pdo, 'group_memberships', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'group_memberships', $row);
        }
    }

    // Events
    foreach ($events as $e) {
        $slug = trim((string)($e['slug'] ?? ''));
        if ($slug === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM events WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $existingId = $stmt->fetchColumn();

        $row = [];
        $cols = mysqlTableColumns($pdo, 'events', $tableColumnsCache);

        foreach (['title', 'slug', 'description', 'event_date', 'start_time', 'end_time', 'timezone', 'location_type', 'venue_name', 'venue_address', 'online_link', 'max_attendees', 'registration_deadline', 'price', 'currency', 'cover_image', 'requirements', 'created_at', 'updated_at'] as $col) {
            if (array_key_exists($col, $e) && in_array($col, $cols, true)) {
                $row[$col] = $e[$col];
            }
        }

        if (in_array('status', $cols, true)) {
            $row['status'] = normalizeEventStatus($e['status'] ?? 'draft');
        }

        if (in_array('tags', $cols, true)) {
            $row['tags'] = parsePgArrayToJsonArray($e['tags'] ?? null);
        }

        $localGroup = isset($e['group_id']) ? (int)$e['group_id'] : 0;
        $localCreator = isset($e['created_by']) ? (int)$e['created_by'] : 0;
        $mappedGroup = resolveId($groupIdMap, $localGroup, $pdo, 'groups');
        $mappedCreator = resolveId($userIdMap, $localCreator, $pdo, 'users');
        if (!$mappedGroup || !$mappedCreator) {
            continue;
        }

        if (in_array('group_id', $cols, true)) {
            $row['group_id'] = $mappedGroup;
        }
        if (in_array('created_by', $cols, true)) {
            $row['created_by'] = $mappedCreator;
        }
        if (in_array('organizer_id', $cols, true)) {
            $row['organizer_id'] = $mappedCreator;
        }

        if ($existingId) {
            $targetId = (int)$existingId;
            updateById($pdo, 'events', $targetId, $row);
            if (!empty($e['id'])) {
                $eventIdMap[(int)$e['id']] = $targetId;
            }
            continue;
        }

        $localId = isset($e['id']) ? (int)$e['id'] : 0;
        if ($localId > 0 && hasCol($pdo, 'events', 'id', $tableColumnsCache)) {
            $stmt = $pdo->prepare('SELECT id FROM events WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $localId]);
            if (!$stmt->fetchColumn()) {
                $row['id'] = $localId;
                insertRow($pdo, 'events', $row);
                $eventIdMap[$localId] = $localId;
                continue;
            }
        }

        $newId = insertRow($pdo, 'events', $row);
        if ($localId > 0) {
            $eventIdMap[$localId] = $newId;
        }
    }

    // Event attendees
    foreach ($eventAttendees as $ea) {
        $eventId = resolveId($eventIdMap, (int)($ea['event_id'] ?? 0), $pdo, 'events');
        $userId = resolveId($userIdMap, (int)($ea['user_id'] ?? 0), $pdo, 'users');
        if (!$eventId || !$userId) continue;

        $stmt = $pdo->prepare('SELECT id FROM event_attendees WHERE event_id = :event_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
        $existingId = $stmt->fetchColumn();

        $row = [
            'event_id' => $eventId,
            'user_id' => $userId,
            'status' => normalizeRsvpStatus($ea['status'] ?? 'going'),
        ];
        foreach (['registered_at', 'notes'] as $col) {
            if (isset($ea[$col]) && hasCol($pdo, 'event_attendees', $col, $tableColumnsCache)) {
                $row[$col] = $ea[$col];
            }
        }

        if ($existingId) {
            updateById($pdo, 'event_attendees', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'event_attendees', $row);
        }
    }

    // Event comments (two pass to resolve parent IDs)
    usort($eventComments, static function ($a, $b) {
        $aTime = (string)($a['created_at'] ?? '');
        $bTime = (string)($b['created_at'] ?? '');
        if ($aTime === $bTime) {
            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        }
        return strcmp($aTime, $bTime);
    });

    $insertComment = static function (array $c, ?int $parentTargetId) use ($pdo, &$tableColumnsCache, &$eventIdMap, &$userIdMap, &$commentIdMap) {
        $eventId = resolveId($eventIdMap, (int)($c['event_id'] ?? 0), $pdo, 'events');
        $userId = resolveId($userIdMap, (int)($c['user_id'] ?? 0), $pdo, 'users');
        if (!$eventId || !$userId) {
            return;
        }

        $lookupSql = 'SELECT id FROM event_comments WHERE event_id = :event_id AND user_id = :user_id AND comment = :comment';
        $lookupParams = [
            ':event_id' => $eventId,
            ':user_id' => $userId,
            ':comment' => (string)($c['comment'] ?? ''),
        ];
        if ($parentTargetId === null) {
            $lookupSql .= ' AND parent_id IS NULL';
        } else {
            $lookupSql .= ' AND parent_id = :parent_id';
            $lookupParams[':parent_id'] = $parentTargetId;
        }
        $lookupSql .= ' LIMIT 1';
        $stmt = $pdo->prepare($lookupSql);
        $stmt->execute($lookupParams);
        $existing = $stmt->fetchColumn();

        $row = [
            'event_id' => $eventId,
            'user_id' => $userId,
            'comment' => (string)($c['comment'] ?? ''),
        ];
        if (hasCol($pdo, 'event_comments', 'parent_id', $tableColumnsCache)) {
            $row['parent_id'] = $parentTargetId;
        }
        if (hasCol($pdo, 'event_comments', 'status', $tableColumnsCache)) {
            $row['status'] = strtolower((string)($c['status'] ?? 'active'));
            if (!in_array($row['status'], ['active', 'hidden', 'deleted'], true)) {
                $row['status'] = 'active';
            }
        }
        foreach (['likes_count', 'created_at', 'updated_at'] as $col) {
            if (array_key_exists($col, $c) && hasCol($pdo, 'event_comments', $col, $tableColumnsCache)) {
                $row[$col] = $c[$col];
            }
        }

        if ($existing) {
            $targetId = (int)$existing;
            updateById($pdo, 'event_comments', $targetId, $row);
            if (!empty($c['id'])) {
                $commentIdMap[(int)$c['id']] = $targetId;
            }
            return;
        }

        $targetId = insertRow($pdo, 'event_comments', $row);
        if (!empty($c['id'])) {
            $commentIdMap[(int)$c['id']] = $targetId;
        }
    };

    foreach ($eventComments as $c) {
        if (!empty($c['parent_id'])) continue;
        $insertComment($c, null);
    }
    foreach ($eventComments as $c) {
        if (empty($c['parent_id'])) continue;
        $parentTarget = $commentIdMap[(int)$c['parent_id']] ?? null;
        $insertComment($c, $parentTarget);
    }

    // Comment likes
    foreach ($commentLikes as $cl) {
        $commentId = resolveId($commentIdMap, (int)($cl['comment_id'] ?? 0), $pdo, 'event_comments');
        $userId = resolveId($userIdMap, (int)($cl['user_id'] ?? 0), $pdo, 'users');
        if (!$commentId || !$userId) continue;

        $stmt = $pdo->prepare('SELECT id FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':comment_id' => $commentId, ':user_id' => $userId]);
        $existingId = $stmt->fetchColumn();

        $row = [
            'comment_id' => $commentId,
            'user_id' => $userId,
            'reaction_type' => strtolower((string)($cl['reaction_type'] ?? 'like')),
        ];
        if (hasCol($pdo, 'comment_likes', 'created_at', $tableColumnsCache) && isset($cl['created_at'])) {
            $row['created_at'] = $cl['created_at'];
        }

        if ($existingId) {
            updateById($pdo, 'comment_likes', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'comment_likes', $row);
        }
    }

    // Event media
    foreach ($eventMedia as $m) {
        $eventId = resolveId($eventIdMap, (int)($m['event_id'] ?? 0), $pdo, 'events');
        $sourceUserId = (int)($m['user_id'] ?? $m['uploaded_by'] ?? 0);
        $userId = resolveId($userIdMap, $sourceUserId, $pdo, 'users');
        if (!$eventId || !$userId) continue;

        $filePath = (string)($m['file_path'] ?? '');
        if ($filePath === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM event_media WHERE event_id = :event_id AND user_id = :user_id AND file_path = :file_path LIMIT 1');
        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId, ':file_path' => $filePath]);
        $existingId = $stmt->fetchColumn();

        $filename = (string)($m['filename'] ?? basename($filePath));
        $originalFilename = (string)($m['original_filename'] ?? $m['original_name'] ?? $filename);

        $row = [
            'event_id' => $eventId,
            'user_id' => $userId,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'file_path' => $filePath,
            'file_type' => (string)($m['file_type'] ?? 'image'),
            'file_size' => (int)($m['file_size'] ?? 0),
            'mime_type' => (string)($m['mime_type'] ?? 'application/octet-stream'),
        ];
        if (hasCol($pdo, 'event_media', 'status', $tableColumnsCache)) {
            $status = strtolower((string)($m['status'] ?? 'active'));
            $row['status'] = in_array($status, ['active', 'deleted'], true) ? $status : 'active';
        }
        if (hasCol($pdo, 'event_media', 'created_at', $tableColumnsCache) && isset($m['created_at'])) {
            $row['created_at'] = $m['created_at'];
        }

        if ($existingId) {
            updateById($pdo, 'event_media', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'event_media', $row);
        }
    }

    // Group role permissions
    foreach ($groupRolePermissions as $grpPerm) {
        $role = trim((string)($grpPerm['role'] ?? ''));
        $permission = trim((string)($grpPerm['permission'] ?? ''));
        if ($role === '' || $permission === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM group_role_permissions WHERE role = :role AND permission = :permission LIMIT 1');
        $stmt->execute([':role' => $role, ':permission' => $permission]);
        $existingId = $stmt->fetchColumn();

        $row = [
            'role' => $role,
            'permission' => $permission,
            'description' => $grpPerm['description'] ?? null,
        ];
        if (isset($grpPerm['created_at']) && hasCol($pdo, 'group_role_permissions', 'created_at', $tableColumnsCache)) {
            $row['created_at'] = $grpPerm['created_at'];
        }

        if ($existingId) {
            updateById($pdo, 'group_role_permissions', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'group_role_permissions', $row);
        }
    }

    // Group join requests
    foreach ($groupJoinRequests as $req) {
        $groupId = resolveId($groupIdMap, (int)($req['group_id'] ?? 0), $pdo, 'groups');
        $userId = resolveId($userIdMap, (int)($req['user_id'] ?? 0), $pdo, 'users');
        $respondedBy = resolveId($userIdMap, (int)($req['responded_by'] ?? 0), $pdo, 'users');
        if (!$groupId || !$userId) continue;

        $stmt = $pdo->prepare('SELECT id FROM group_join_requests WHERE group_id = :group_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
        $existingId = $stmt->fetchColumn();

        $status = strtolower((string)($req['status'] ?? 'pending'));
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $row = [
            'group_id' => $groupId,
            'user_id' => $userId,
            'message' => $req['message'] ?? null,
            'status' => $status,
            'responded_by' => $respondedBy,
        ];
        foreach (['requested_at', 'responded_at'] as $col) {
            if (isset($req[$col]) && hasCol($pdo, 'group_join_requests', $col, $tableColumnsCache)) {
                $row[$col] = $req[$col];
            }
        }

        if ($existingId) {
            updateById($pdo, 'group_join_requests', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'group_join_requests', $row);
        }
    }

    // Group activity log
    foreach ($groupActivityLog as $activity) {
        $groupId = resolveId($groupIdMap, (int)($activity['group_id'] ?? 0), $pdo, 'groups');
        $userId = resolveId($userIdMap, (int)($activity['user_id'] ?? 0), $pdo, 'users');
        if (!$groupId || !$userId) continue;

        $action = trim((string)($activity['action'] ?? ''));
        if ($action === '') continue;

        $stmt = $pdo->prepare('SELECT id FROM group_activity_log WHERE group_id = :group_id AND user_id = :user_id AND action = :action AND created_at = :created_at LIMIT 1');
        $stmt->execute([
            ':group_id' => $groupId,
            ':user_id' => $userId,
            ':action' => $action,
            ':created_at' => $activity['created_at'] ?? null,
        ]);
        $existingId = $stmt->fetchColumn();

        $details = $activity['details'] ?? null;
        if (is_array($details)) {
            $details = json_encode($details);
        }

        $row = [
            'group_id' => $groupId,
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
        ];
        if (isset($activity['created_at']) && hasCol($pdo, 'group_activity_log', 'created_at', $tableColumnsCache)) {
            $row['created_at'] = $activity['created_at'];
        }

        if ($existingId) {
            updateById($pdo, 'group_activity_log', (int)$existingId, $row);
        } else {
            insertRow($pdo, 'group_activity_log', $row);
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();

    $counts = [
        'users' => count($users),
        'categories' => count($categories),
        'groups' => count($groups),
        'group_memberships' => count($groupMemberships),
        'events' => count($events),
        'event_attendees' => count($eventAttendees),
        'event_comments' => count($eventComments),
        'comment_likes' => count($commentLikes),
        'event_media' => count($eventMedia),
        'event_categories' => count($eventCategories),
        'group_categories' => count($groupCategories),
        'group_join_requests' => count($groupJoinRequests),
        'group_role_permissions' => count($groupRolePermissions),
        'group_activity_log' => count($groupActivityLog),
    ];

    echo 'IMPORT_OK|' . json_encode($counts) . PHP_EOL;

} catch (Throwable $e) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->rollBack();
    throw $e;
}
