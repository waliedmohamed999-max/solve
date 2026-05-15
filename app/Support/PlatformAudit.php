<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformAudit
{
    public static function activity(string $action, string $subjectType, ?string $subjectId = null, array $properties = [], ?Request $request = null): void
    {
        if (! self::tableExists('platform_activity_logs')) {
            return;
        }

        $admin = $request?->session()->get('admin_authenticated') ? [
            'actor_type' => 'admin',
            'actor_name' => 'Super Admin',
            'role' => 'super_admin',
        ] : [];
        $partner = $request ? PartnerTenantStore::currentUser($request) : null;

        PlatformActivityLog::query()->create(array_merge([
            'actor_type' => $partner ? 'partner' : 'system',
            'actor_id' => $partner['username'] ?? null,
            'actor_name' => $partner['name'] ?? 'System',
            'role' => $partner['role'] ?? null,
            'store_id' => $partner['store_id'] ?? ($properties['store_id'] ?? null),
            'partner_id' => $partner['partner_id'] ?? ($properties['partner_id'] ?? null),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ], $admin));
    }

    public static function notify(string $type, string $title, ?string $body = null, array $payload = []): void
    {
        if (! self::tableExists('platform_notifications')) {
            return;
        }

        PlatformNotification::query()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'store_id' => $payload['store_id'] ?? null,
            'partner_id' => $payload['partner_id'] ?? null,
            'severity' => $payload['severity'] ?? 'info',
            'url' => $payload['url'] ?? null,
            'payload' => $payload,
        ]);
    }

    private static function tableExists(string $table): bool
    {
        try {
            DB::connection()->getPdo();

            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
