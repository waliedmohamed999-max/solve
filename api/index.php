<?php

declare(strict_types=1);

$storagePath = sys_get_temp_dir().'/solve-storage';

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
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

$host = $_SERVER['HTTP_HOST'] ?? 'solve-mu.vercel.app';

$readEnv = static function (string $key): ?string {
    $value = getenv($key);

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

$setDefault = static function (string $key, string $value) use ($readEnv): void {
    if ($readEnv($key) !== null) {
        return;
    }

    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

$defaults = [
    'APP_NAME' => 'Solve',
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://'.$host,
    'APP_STORAGE_PATH' => $storagePath,
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
];

foreach ($defaults as $key => $value) {
    $setDefault($key, $value);
}

if ($readEnv('APP_KEY') === null) {
    $setDefault('APP_KEY', 'base64:'.base64_encode(hash('sha256', 'solve-vercel-preview-key', true)));
}

if ($readEnv('DB_CONNECTION') === null) {
    $sqlitePath = $storagePath.'/database.sqlite';

    if (! file_exists($sqlitePath)) {
        touch($sqlitePath);
    }

    $setDefault('DB_CONNECTION', 'sqlite');
    $setDefault('DB_DATABASE', $sqlitePath);
} elseif ($readEnv('DB_CONNECTION') === 'sqlite' && $readEnv('DB_DATABASE') === null) {
    $sqlitePath = $storagePath.'/database.sqlite';

    if (! file_exists($sqlitePath)) {
        touch($sqlitePath);
    }

    $setDefault('DB_DATABASE', $sqlitePath);
}

require __DIR__.'/../public/index.php';
