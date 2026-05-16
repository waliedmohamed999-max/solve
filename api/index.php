<?php

declare(strict_types=1);

$storagePath = sys_get_temp_dir().'/solve-storage';

foreach ([
    $storagePath,
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

$defaults = [
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
    'APP_MAINTENANCE_DRIVER' => 'file',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false && ! isset($_ENV[$key]) && ! isset($_SERVER[$key])) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

if (getenv('APP_KEY') === false && ! isset($_ENV['APP_KEY']) && ! isset($_SERVER['APP_KEY'])) {
    $fallbackKey = 'base64:'.base64_encode(hash('sha256', 'solve-vercel-preview-key', true));
    putenv('APP_KEY='.$fallbackKey);
    $_ENV['APP_KEY'] = $fallbackKey;
    $_SERVER['APP_KEY'] = $fallbackKey;
}

require __DIR__.'/../public/index.php';
