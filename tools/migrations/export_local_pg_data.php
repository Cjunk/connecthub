<?php
/**
 * Export local PostgreSQL community data for migration into production MySQL.
 *
 * Usage:
 *   php tools/migrations/export_local_pg_data.php [output_file]
 */

require_once __DIR__ . '/../../config/constants.php';

$outputFile = $argv[1] ?? (__DIR__ . '/../../migration_payload.json');

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = [];

$payload = [
    'meta' => [
        'exported_at_utc' => gmdate('c'),
        'source' => 'local-postgresql',
        'db_name' => DB_NAME,
    ],
    'data' => [],
];

function tableExists(PDO $pdo, string $table): bool {
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = :table LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':table' => $table]);
    return (bool)$stmt->fetchColumn();
}

function getTableRows(PDO $pdo, string $table): array {
    $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id ASC");
    return $stmt->fetchAll();
}

function getAllPublicTables(PDO $pdo): array {
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name ASC");
    return array_map(static fn($r) => $r['table_name'], $stmt->fetchAll());
}

$tables = getAllPublicTables($pdo);

foreach ($tables as $table) {
    if (!tableExists($pdo, $table)) {
        $payload['data'][$table] = [];
        continue;
    }
    $payload['data'][$table] = getTableRows($pdo, $table);
}

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    throw new RuntimeException('Failed to encode migration payload JSON');
}

file_put_contents($outputFile, $json);

echo 'EXPORT_OK|' . $outputFile . PHP_EOL;
echo 'COUNTS|' . json_encode(array_map('count', $payload['data'])) . PHP_EOL;
