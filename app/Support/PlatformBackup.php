<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PlatformBackup
{
    private const TABLES = [
        'partner_stores',
        'partner_users',
        'platform_records',
        'platform_activity_logs',
        'platform_notifications',
        'store_settings',
        'store_onboarding_steps',
        'marketplace_apps',
    ];

    public static function create(string $label = 'manual'): array
    {
        $label = Str::slug($label) ?: 'manual';
        $tables = [];

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                $tables[$table] = [
                    'exists' => false,
                    'count' => 0,
                    'rows' => [],
                ];

                continue;
            }

            $rows = DB::table($table)->orderBy('id')->get()->map(fn (object $row) => (array) $row)->all();

            $tables[$table] = [
                'exists' => true,
                'count' => count($rows),
                'rows' => $rows,
            ];
        }

        $payload = [
            'version' => 1,
            'label' => $label,
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
        ];

        $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $path = self::directory() . '/' . now()->format('Ymd_His') . '_' . $label . '.json';

        Storage::disk('local')->put($path, $content);

        return [
            'path' => $path,
            'label' => $label,
            'created_at' => $payload['created_at'],
            'checksum' => hash('sha256', $content),
            'tables' => collect($tables)->map(fn (array $table) => [
                'exists' => $table['exists'],
                'count' => $table['count'],
            ])->all(),
        ];
    }

    public static function latest(): ?array
    {
        try {
            $files = collect(Storage::disk('local')->files(self::directory()))
                ->filter(fn (string $path) => str_ends_with($path, '.json'))
                ->sortByDesc(fn (string $path) => Storage::disk('local')->lastModified($path))
                ->values();

            if ($files->isEmpty()) {
                return null;
            }

            $path = $files->first();
            $content = Storage::disk('local')->get($path);
            $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

            return [
                'path' => $path,
                'label' => $payload['label'] ?? null,
                'created_at' => $payload['created_at'] ?? null,
                'checksum' => hash('sha256', $content),
                'tables' => collect($payload['tables'] ?? [])->map(fn (array $table) => [
                    'exists' => (bool) ($table['exists'] ?? false),
                    'count' => (int) ($table['count'] ?? 0),
                ])->all(),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    public static function hasRecentBackup(int $maxAgeHours = 24): bool
    {
        $latest = self::latest();

        if (! $latest || empty($latest['created_at'])) {
            return false;
        }

        return now()->diffInHours($latest['created_at']) <= $maxAgeHours;
    }

    public static function directory(): string
    {
        return trim((string) env('PLATFORM_BACKUP_PATH', 'backups/platform'), '/');
    }
}
