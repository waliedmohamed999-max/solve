<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PlatformHealth
{
    public static function summary(bool $includeDetails = false): array
    {
        $checks = [
            self::database(),
            self::cache(),
            self::storage(),
            self::queueTables(),
            self::tenantTables(),
        ];

        $healthy = collect($checks)->every(fn (array $check) => $check['ok']);

        return [
            'status' => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $includeDetails ? $checks : collect($checks)->map(fn (array $check) => [
                'name' => $check['name'],
                'ok' => $check['ok'],
            ])->values()->all(),
        ];
    }

    private static function database(): array
    {
        try {
            DB::select('select 1');

            return self::check('database', true, 'Database connection is reachable.');
        } catch (Throwable $exception) {
            return self::check('database', false, 'Database connection failed.', $exception);
        }
    }

    private static function cache(): array
    {
        try {
            $key = 'solve:health:' . app()->environment();
            Cache::put($key, 'ok', 30);

            return self::check('cache', Cache::get($key) === 'ok', 'Cache store can write and read.');
        } catch (Throwable $exception) {
            return self::check('cache', false, 'Cache store failed.', $exception);
        }
    }

    private static function storage(): array
    {
        try {
            $path = 'health/.solve-health';
            Storage::disk('local')->put($path, now()->toIso8601String());
            $ok = Storage::disk('local')->exists($path);
            Storage::disk('local')->delete($path);

            return self::check('storage', $ok, 'Local storage is writable.');
        } catch (Throwable $exception) {
            return self::check('storage', false, 'Local storage is not writable.', $exception);
        }
    }

    private static function queueTables(): array
    {
        try {
            $usesDatabaseQueue = config('queue.default') === 'database';
            $ok = ! $usesDatabaseQueue || Schema::hasTable('jobs');

            return self::check('queue', $ok, $ok ? 'Queue configuration is usable.' : 'Database queue selected but jobs table is missing.');
        } catch (Throwable $exception) {
            return self::check('queue', false, 'Queue check failed.', $exception);
        }
    }

    private static function tenantTables(): array
    {
        try {
            $required = ['partner_stores', 'partner_users', 'platform_records', 'platform_activity_logs'];
            $missing = collect($required)->reject(fn (string $table) => Schema::hasTable($table))->values()->all();

            return self::check('multi_tenant_tables', $missing === [], $missing === [] ? 'Tenant tables are ready.' : 'Missing tables: ' . implode(', ', $missing), null, [
                'missing' => $missing,
            ]);
        } catch (Throwable $exception) {
            return self::check('multi_tenant_tables', false, 'Tenant table check failed.', $exception);
        }
    }

    private static function check(string $name, bool $ok, string $message, ?Throwable $exception = null, array $extra = []): array
    {
        return array_merge([
            'name' => $name,
            'ok' => $ok,
            'message' => $message,
            'error' => $exception ? class_basename($exception) : null,
        ], $extra);
    }
}
