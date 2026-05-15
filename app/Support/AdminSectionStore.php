<?php

namespace App\Support;

use App\Models\PlatformRecord;
use App\Models\SiteContent as SiteContentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSectionStore
{
    private const KEY_PREFIX = 'admin_section:';

    public static function get(string $section, array $default = []): array
    {
        if (self::platformRecordsTableExists()) {
            $records = PlatformRecord::query()
                ->where('section', $section)
                ->orderBy('id')
                ->get()
                ->map(fn (PlatformRecord $record) => array_merge($record->payload ?? [], [
                    'id' => $record->record_id,
                    'store_id' => $record->store_id ?? ($record->payload['store_id'] ?? null),
                ]))
                ->all();

            if ($records !== []) {
                return $records;
            }

            if (SiteContent::contentTableExists()) {
                $legacy = SiteContentModel::query()->where('key', self::KEY_PREFIX . $section)->first();

                if ($legacy && is_array($legacy->payload)) {
                    self::put($section, $legacy->payload);

                    return $legacy->payload;
                }
            }

            self::put($section, $default);

            return $default;
        }

        if (! SiteContent::contentTableExists()) {
            return $default;
        }

        $record = SiteContentModel::query()->where('key', self::KEY_PREFIX . $section)->first();

        if (! $record) {
            return $default;
        }

        return is_array($record->payload) ? $record->payload : $default;
    }

    public static function put(string $section, array $records): void
    {
        if (self::platformRecordsTableExists()) {
            $existingIds = [];

            foreach (array_values($records) as $index => $record) {
                $recordId = (string) ($record['id'] ?? $record['record_id'] ?? $section . '-' . ($index + 1));
                $existingIds[] = $recordId;

                PlatformRecord::query()->updateOrCreate(
                    ['section' => $section, 'record_id' => $recordId],
                    [
                        'store_id' => $record['store_id'] ?? self::inferStoreId($record),
                        'partner_id' => $record['partner_id'] ?? null,
                        'status' => $record['status'] ?? null,
                        'payload' => array_merge($record, ['id' => $recordId]),
                    ],
                );
            }

            PlatformRecord::query()
                ->where('section', $section)
                ->whereNotIn('record_id', $existingIds)
                ->delete();

            self::putLegacy($section, $records);

            return;
        }

        self::putLegacy($section, $records);
    }

    private static function putLegacy(string $section, array $records): void
    {
        if (! SiteContent::contentTableExists()) {
            return;
        }

        SiteContentModel::query()->updateOrCreate(
            ['key' => self::KEY_PREFIX . $section],
            ['payload' => array_values($records)],
        );
    }

    private static function platformRecordsTableExists(): bool
    {
        try {
            DB::connection()->getPdo();

            return Schema::hasTable('platform_records');
        } catch (\Throwable) {
            return false;
        }
    }

    private static function inferStoreId(array $record): ?string
    {
        $candidate = $record['store_id'] ?? $record['store'] ?? $record['name'] ?? null;

        if (! is_string($candidate) || trim($candidate) === '') {
            return null;
        }

        return str($candidate)->lower()->replaceMatches('/[^a-z0-9\p{Arabic}]+/u', '-')->trim('-')->prepend('store-')->toString();
    }
}
