<?php

declare(strict_types=1);

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (in_array($requestPath, ['/up', '/health', '/api/health'], true)) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');

    echo json_encode([
        'status' => 'ok',
        'app' => 'Solve',
        'runtime' => 'php',
    ], JSON_UNESCAPED_SLASHES);

    return;
}

$vercelRuntime = (function_exists('getenv') && getenv('VERCEL') !== false)
    || array_key_exists('VERCEL', $_ENV)
    || array_key_exists('VERCEL', $_SERVER)
    || strpos($host, 'vercel.app') !== false;

$storagePath = $vercelRuntime ? '/tmp/solve-storage' : rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'solve-storage';

$ensureDirectory = static function (string $directory): void {
    if (is_dir($directory)) {
        return;
    }

    if (! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
        error_log('Solve Vercel bootstrap could not create directory: '.$directory);
    }
};

foreach ([
    $storagePath,
    $storagePath.'/app',
    $storagePath.'/app/private',
    $storagePath.'/app/public',
    $storagePath.'/framework',
    $storagePath.'/framework/cache',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
] as $directory) {
    $ensureDirectory($directory);
}

$readEnv = static function (string $key): ?string {
    $value = function_exists('getenv') ? getenv($key) : false;

    if ($value === false && array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    }

    if ($value === false && array_key_exists($key, $_SERVER)) {
        $value = $_SERVER[$key];
    }

    if ($value === false || $value === null) {
        return null;
    }

    $value = (string) $value;

    return trim($value) === '' ? null : $value;
};

$writeEnv = static function (string $key, string $value): void {
    if (function_exists('putenv')) {
        @putenv($key.'='.$value);
    }

    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

$isVercel = $vercelRuntime;

$setDefault = static function (string $key, string $value) use ($readEnv, $writeEnv): void {
    if ($readEnv($key) !== null) {
        return;
    }

    $writeEnv($key, $value);
};

$forceEnv = static function (string $key, string $value) use ($writeEnv): void {
    $writeEnv($key, $value);
};

$defaults = [
    'APP_NAME' => 'Solve',
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://'.$host,
    'APP_STORAGE_PATH' => $storagePath,
    'LARAVEL_STORAGE_PATH' => $storagePath,
    'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
    'LOG_CHANNEL' => 'stderr',
    'LOG_STACK' => 'stderr',
    'SLOW_QUERY_LOG_CHANNEL' => 'stderr',
    'SESSION_DRIVER' => 'cookie',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'QUEUE_FAILED_DRIVER' => 'null',
    'BROADCAST_CONNECTION' => 'log',
    'FILESYSTEM_DISK' => 'local',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_SAME_SITE' => 'lax',
    'MAIL_MAILER' => 'log',
];

foreach ($defaults as $key => $value) {
    $setDefault($key, $value);
}

if ($isVercel) {
    foreach ([
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://'.$host,
        'APP_STORAGE_PATH' => $storagePath,
        'LARAVEL_STORAGE_PATH' => $storagePath,
        'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
        'APP_SERVICES_CACHE' => $storagePath.'/framework/cache/services.php',
        'APP_PACKAGES_CACHE' => $storagePath.'/framework/cache/packages.php',
        'APP_CONFIG_CACHE' => $storagePath.'/framework/cache/config.php',
        'APP_EVENTS_CACHE' => $storagePath.'/framework/cache/events.php',
        'APP_ROUTES_CACHE' => $storagePath.'/framework/cache/routes-v7.php',
        'LOG_CHANNEL' => 'stderr',
        'LOG_STACK' => 'stderr',
        'SLOW_QUERY_LOG_CHANNEL' => 'stderr',
        'SESSION_DRIVER' => 'cookie',
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'QUEUE_FAILED_DRIVER' => 'null',
        'BROADCAST_CONNECTION' => 'log',
        'FILESYSTEM_DISK' => 'local',
        'APP_MAINTENANCE_DRIVER' => 'file',
        'MAIL_MAILER' => 'log',
    ] as $key => $value) {
        $forceEnv($key, $value);
    }
}

$hasValidAppKey = static function (?string $key): bool {
    if ($key === null) {
        return false;
    }

    if (str_starts_with($key, 'base64:')) {
        $decoded = base64_decode(substr($key, 7), true);

        return is_string($decoded) && strlen($decoded) === 32;
    }

    return strlen($key) >= 32;
};

if (! $hasValidAppKey($readEnv('APP_KEY'))) {
    $forceEnv('APP_KEY', 'base64:'.base64_encode(hash('sha256', 'solve-vercel-preview-key', true)));
}

$sqlitePath = $storagePath.'/database.sqlite';

$prepareSqlite = static function () use ($sqlitePath, $ensureDirectory, $forceEnv): void {
    $ensureDirectory(dirname($sqlitePath));

    if (! file_exists($sqlitePath)) {
        @touch($sqlitePath);
    }

    $forceEnv('DB_CONNECTION', 'sqlite');
    $forceEnv('DB_DATABASE', $sqlitePath);
};

$dbConnection = strtolower((string) ($readEnv('DB_CONNECTION') ?? ''));
$dbHost = strtolower((string) ($readEnv('DB_HOST') ?? ''));
$hasExternalDatabaseUrl = $readEnv('DB_URL') !== null || $readEnv('DATABASE_URL') !== null;
$hasExternalDatabaseHost = $dbHost !== '' && ! in_array($dbHost, ['127.0.0.1', 'localhost'], true);
$shouldUseEphemeralSqlite = $dbConnection === ''
    || $dbConnection === 'sqlite'
    || ($isVercel && ! $hasExternalDatabaseUrl && ! $hasExternalDatabaseHost);

if ($shouldUseEphemeralSqlite) {
    $prepareSqlite();
} elseif ($dbConnection === 'sqlite' && $readEnv('DB_DATABASE') === null) {
    $prepareSqlite();
}

$renderLandingFallback = static function () use ($host): void {
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');

    $logo = '/solve-logo.png';
    $hero = '/منصة_متاجر.png';

    echo <<<HTML
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solve - منصة المتاجر الذكية</title>
    <style>
        :root { color-scheme: light; --brand:#6d28d9; --ink:#0f172a; --muted:#64748b; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Tahoma, Arial, sans-serif; background:#f7f9fc; color:var(--ink); }
        .page { min-height:100vh; display:grid; place-items:center; padding:32px; }
        .card { width:min(1120px, 100%); display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:center; background:#fff; border:1px solid #dbe4f0; border-radius:32px; padding:44px; box-shadow:0 28px 80px rgba(15,23,42,.08); }
        .logo { width:190px; max-width:70%; display:block; margin-bottom:28px; }
        h1 { margin:0; font-size:clamp(34px, 5vw, 64px); line-height:1.2; letter-spacing:0; }
        p { margin:18px 0 0; color:var(--muted); font-size:20px; line-height:1.9; }
        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:30px; }
        a { text-decoration:none; }
        .btn { display:inline-flex; align-items:center; justify-content:center; min-height:48px; padding:0 24px; border-radius:999px; font-weight:800; }
        .primary { background:var(--brand); color:#fff; box-shadow:0 16px 34px rgba(109,40,217,.22); }
        .secondary { border:1px solid #cbd5e1; color:var(--ink); background:#fff; }
        .visual { background:linear-gradient(135deg,#eef2ff,#ecfeff); border:1px solid #dbe4f0; border-radius:28px; padding:28px; }
        .visual img { width:100%; display:block; object-fit:contain; max-height:460px; }
        .note { margin-top:22px; font-size:13px; color:#94a3b8; }
        @media (max-width: 860px) {
            .card { grid-template-columns:1fr; padding:28px; border-radius:24px; }
            p { font-size:17px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <div>
                <img class="logo" src="{$logo}" alt="Solve">
                <h1>منصة Solve لإنشاء وإدارة المتاجر الإلكترونية</h1>
                <p>واجهة SaaS احترافية لإطلاق متجر، إدارة المنتجات والطلبات، وربط الخدمات والتسويق من مكان واحد.</p>
                <div class="actions">
                    <a class="btn primary" href="/merchant/register">ابدأ مجاناً</a>
                    <a class="btn secondary" href="/admin">لوحة الإدارة</a>
                </div>
                <div class="note">Fallback آمن يعمل عند تعطل قاعدة البيانات أو إعدادات runtime على {$host}.</div>
            </div>
            <div class="visual">
                <img src="{$hero}" alt="منصة متاجر Solve">
            </div>
        </section>
    </main>
</body>
</html>
HTML;
};

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $exception) {
    error_log('Solve Vercel runtime failure: '.$exception->getMessage());

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $requestPath === '/') {
        $renderLandingFallback();

        return;
    }

    throw $exception;
}
