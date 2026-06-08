<?php
// Minimal, production-safe diagnostics. Remove this file after use.
// Optional: add a simple guard
// if (!isset($_GET['ok'])) { http_response_code(403); echo 'Forbidden'; exit; }

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/bootstrap.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function mask($s) { if ($s === '') return ''; $len = strlen($s); return str_repeat('*', max(0, $len - 2)) . substr($s, -2); }

$loadedEnvFiles = class_exists('EnvLoader') ? EnvLoader::getLoadedFiles() : [];
$php = [
    'version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
    ],
];

$config = [
    'APP_ENV' => defined('APP_ENV') ? APP_ENV : '(not defined)',
    'APP_DEBUG' => defined('APP_DEBUG') ? (APP_DEBUG ? 'true' : 'false') : '(not defined)',
    'BASE_URL' => defined('BASE_URL') ? BASE_URL : '(not defined)',
    'SITE_URL' => defined('SITE_URL') ? SITE_URL : '(not defined)',
    'ROOT_PATH' => defined('ROOT_PATH') ? ROOT_PATH : '(not defined)',
    'PUBLIC_PATH' => defined('PUBLIC_PATH') ? PUBLIC_PATH : '(not defined)',
];

$db = [
    'host' => defined('DB_HOST') ? DB_HOST : '(not defined)',
    'name' => defined('DB_NAME') ? DB_NAME : '(not defined)',
    'user' => defined('DB_USER') ? DB_USER : '(not defined)',
    'pass' => defined('DB_PASS') ? mask(DB_PASS) : '(not defined)',
    'port' => defined('DB_PORT') ? DB_PORT : '(not defined)',
    'charset' => defined('DB_CHARSET') ? DB_CHARSET : '(not defined)',
];

$selected = (defined('APP_ENV') && APP_ENV === 'production') ? 'mysql' : 'pgsql';
$override = isset($_GET['driver']) ? strtolower($_GET['driver']) : null; // mysql|pgsql
if ($override === 'mysql' || $override === 'pgsql') { $selected = $override; }

$results = [];

// Try connection using Database class first (app path)
try {
    $dbInstance = Database::getInstance();
    $pdo = $dbInstance->getConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $v = $pdo->query($driver === 'mysql' ? 'SELECT VERSION() AS v' : 'SELECT version() AS v')->fetch();
    $results[] = ['ok' => true, 'step' => 'Database::getInstance()', 'message' => 'Connected via app Database class', 'driver' => $driver, 'version' => $v['v'] ?? 'n/a'];
} catch (Throwable $e) {
    $results[] = ['ok' => false, 'step' => 'Database::getInstance()', 'message' => $e->getMessage()];
}

// Explicit driver tests (bypass app branching) for clarity
function try_mysql($db, &$out) {
    if (!extension_loaded('pdo_mysql')) { $out[] = ['ok' => false, 'step' => 'pdo_mysql', 'message' => 'pdo_mysql not loaded']; return; }
    $port = is_numeric($db['port']) ? (int)$db['port'] : 3306;
    $charset = $db['charset'] ?: 'utf8mb4';
    $dsn = "mysql:host={$db['host']};port={$port};dbname={$db['name']};charset={$charset}";
    try {
        $pdo = new PDO($dsn, $db['user'], defined('DB_PASS') ? DB_PASS : '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $v = $pdo->query('SELECT VERSION() AS v')->fetch();
        $out[] = ['ok' => true, 'step' => 'Direct MySQL', 'message' => 'Connected', 'driver' => 'mysql', 'version' => $v['v'] ?? 'n/a'];
    } catch (Throwable $t) {
        $out[] = ['ok' => false, 'step' => 'Direct MySQL', 'message' => $t->getMessage(), 'dsn' => $dsn];
    }
}

function try_pgsql($db, &$out) {
    if (!extension_loaded('pdo_pgsql')) { $out[] = ['ok' => false, 'step' => 'pdo_pgsql', 'message' => 'pdo_pgsql not loaded']; return; }
    $port = is_numeric($db['port']) ? (int)$db['port'] : 5432;
    $dsn = "pgsql:host={$db['host']};port={$port};dbname={$db['name']}";
    try {
        $pdo = new PDO($dsn, $db['user'], defined('DB_PASS') ? DB_PASS : '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $v = $pdo->query('SELECT version() AS v')->fetch();
        $out[] = ['ok' => true, 'step' => 'Direct PgSQL', 'message' => 'Connected', 'driver' => 'pgsql', 'version' => $v['v'] ?? 'n/a'];
    } catch (Throwable $t) {
        $out[] = ['ok' => false, 'step' => 'Direct PgSQL', 'message' => $t->getMessage(), 'dsn' => $dsn];
    }
}

if ($selected === 'mysql') {
    try_mysql($db, $results);
} else {
    try_pgsql($db, $results);
}

// Also try the other driver for completeness
if ($selected !== 'mysql') { try_mysql($db, $results); }
if ($selected !== 'pgsql') { try_pgsql($db, $results); }

// Render
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Healthcheck</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; padding: 20px; }
        code { background: #f3f4f6; padding: 2px 4px; border-radius: 4px; }
        .ok { color: #16a34a; }
        .fail { color: #dc2626; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: 220px 1fr; gap: 8px 16px; }
        .muted { color: #6b7280; }
    </style>
    </head>
<body>
    <h1>Healthcheck</h1>
    <p class="muted">Remove this file after debugging.</p>

    <div class="card">
        <h2>Environment</h2>
        <div class="grid">
            <div>PHP Version</div><div><code><?php echo h($php['version']); ?></code></div>
            <div>APP_ENV</div><div><code><?php echo h($config['APP_ENV']); ?></code></div>
            <div>APP_DEBUG</div><div><code><?php echo h($config['APP_DEBUG']); ?></code></div>
            <div>BASE_URL</div><div><code><?php echo h($config['BASE_URL']); ?></code></div>
            <div>SITE_URL</div><div><code><?php echo h($config['SITE_URL']); ?></code></div>
            <div>ROOT_PATH</div><div><code><?php echo h($config['ROOT_PATH']); ?></code></div>
            <div>PUBLIC_PATH</div><div><code><?php echo h($config['PUBLIC_PATH']); ?></code></div>
        </div>
    </div>

    <div class="card">
        <h2>Loaded Extensions</h2>
        <div class="grid">
            <div>pdo</div><div><?php echo $php['extensions']['pdo'] ? '<span class="ok">enabled</span>' : '<span class="fail">missing</span>'; ?></div>
            <div>pdo_mysql</div><div><?php echo $php['extensions']['pdo_mysql'] ? '<span class="ok">enabled</span>' : '<span class="fail">missing</span>'; ?></div>
            <div>pdo_pgsql</div><div><?php echo $php['extensions']['pdo_pgsql'] ? '<span class="ok">enabled</span>' : '<span class="fail">missing</span>'; ?></div>
        </div>
    </div>

    <div class="card">
        <h2>Config</h2>
        <div class="grid">
            <div>DB_HOST</div><div><code><?php echo h($db['host']); ?></code></div>
            <div>DB_NAME</div><div><code><?php echo h($db['name']); ?></code></div>
            <div>DB_USER</div><div><code><?php echo h($db['user']); ?></code></div>
            <div>DB_PASS</div><div><code><?php echo h($db['pass']); ?></code></div>
            <div>DB_PORT</div><div><code><?php echo h($db['port']); ?></code></div>
            <div>DB_CHARSET</div><div><code><?php echo h($db['charset']); ?></code></div>
            <div>.env files loaded</div><div><code><?php echo h(implode(', ', $loadedEnvFiles)); ?></code></div>
        </div>
    </div>

    <div class="card">
        <h2>DB Connectivity</h2>
        <p>Selected by app: <code><?php echo h($selected); ?></code>. You can override with <code>?driver=mysql</code> or <code>?driver=pgsql</code>.</p>
        <ul>
            <?php foreach ($results as $r): ?>
                <li class="<?php echo $r['ok'] ? 'ok' : 'fail'; ?>">
                    <strong><?php echo h($r['step']); ?>:</strong>
                    <?php echo h($r['message']); ?>
                    <?php if (!empty($r['driver'])): ?> (driver=<?php echo h($r['driver']); ?>)<?php endif; ?>
                    <?php if (!empty($r['version'])): ?> [<?php echo h($r['version']); ?>]<?php endif; ?>
                    <?php if (!$r['ok'] && !empty($r['dsn'])): ?><div class="muted">DSN: <code><?php echo h($r['dsn']); ?></code></div><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h2>Next steps</h2>
        <ol>
            <li>If APP_ENV is not <code>production</code>, ensure <code>.env</code> in web root has <code>APP_ENV=production</code>.</li>
            <li>Make sure <code>production_config.php</code> is in web root and has the correct MySQL creds.</li>
            <li>Ensure <code>pdo_mysql</code> is enabled on the server (GoDaddy supports it by default).</li>
            <li>Remove this file when done for security.</li>
        </ol>
    </div>
</body>
</html>
