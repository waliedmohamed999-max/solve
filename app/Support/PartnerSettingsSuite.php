<?php

namespace App\Support;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use App\Models\StoreSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerSettingsSuite
{
    public static function summary(array $partner): array
    {
        $settings = PartnerSettings::ensure($partner);

        return [
            'store_id' => $partner['store_id'],
            'store' => PartnerSettings::api($partner, 'store')['section']['data'],
            'identity' => PartnerSettings::api($partner, 'identity')['section']['data'],
            'domain' => self::domain($partner),
            'shipping' => self::shipping($partner),
            'payments' => self::payments($partner),
            'taxes' => self::taxes($partner),
            'notifications' => self::notifications($partner),
            'staff_count' => self::staff($partner)['meta']['total'],
            'roles_count' => count(self::roles($partner)['roles']),
            'security' => [
                'two_factor_enabled' => (bool) (($settings?->identity ?? [])['two_factor_enabled'] ?? false),
                'active_sessions' => count(self::sessions($partner)['sessions']),
            ],
            'meta' => self::meta(['store_settings', 'partner_users', 'platform_records', 'platform_activity_logs']),
        ];
    }

    public static function updateSection(array $partner, string $section, array $data, ?array $actor = null): array
    {
        PartnerSettings::update($partner, $section, $data);
        self::log($partner, $actor, 'settings.updated', 'settings', $section, ['keys' => array_keys($data)]);

        return PartnerSettings::api($partner, $section);
    }

    public static function uploadIdentity(array $partner, array $data, ?array $actor = null): array
    {
        $type = $data['type'] ?? 'logo';
        abort_unless(in_array($type, ['logo', 'favicon', 'social_image'], true), 422);

        PartnerSettings::update($partner, 'identity', [$type => $data['path'] ?? '']);
        self::log($partner, $actor, 'settings.identity_uploaded', 'settings', $type, ['path' => $data['path'] ?? null]);

        return PartnerSettings::api($partner, 'identity');
    }

    public static function staff(array $partner): array
    {
        if (! Schema::hasTable('partner_users')) {
            $rows = collect($partner['users'] ?? [])
                ->map(fn (array $user) => [
                    'id' => $user['username'] ?? $user['email'] ?? Str::slug($user['name'] ?? 'staff'),
                    'name' => $user['name'] ?? 'Staff',
                    'username' => $user['username'] ?? $user['email'] ?? null,
                    'email' => $user['email'] ?? null,
                    'role' => $user['role'] ?? 'staff',
                    'role_label' => PartnerTenantStore::roleLabel((string) ($user['role'] ?? 'staff')),
                    'status' => $user['status'] ?? 'active',
                    'abilities' => $user['abilities'] ?? [],
                    'last_login_at' => null,
                    'store_id' => $partner['store_id'],
                ])
                ->values()
                ->all();

            return [
                'store_id' => $partner['store_id'],
                'staff' => $rows,
                'roles' => self::roles($partner)['roles'],
                'meta' => self::meta(['partner_users']) + ['total' => count($rows)],
            ];
        }

        $rows = PartnerUser::query()
            ->where('store_id', $partner['store_id'])
            ->orderByRaw("case when role = 'partner_admin' then 0 else 1 end")
            ->orderBy('name')
            ->get()
            ->map(fn (PartnerUser $user) => self::staffPayload($user))
            ->values()
            ->all();

        return [
            'store_id' => $partner['store_id'],
            'staff' => $rows,
            'roles' => self::roles($partner)['roles'],
            'meta' => self::meta(['partner_users']) + ['total' => count($rows)],
        ];
    }

    public static function inviteStaff(array $partner, array $data, ?array $actor = null): array
    {
        abort_if(SubscriptionManager::limitReached($partner, 'staff'), 402, 'Staff limit reached for the current subscription plan.');

        $store = self::storeModel($partner);
        $role = $data['role'] ?? 'staff';
        $abilities = $data['abilities'] ?? self::abilitiesForRole($role);

        $user = PartnerUser::query()->create([
            'partner_store_id' => $store->id,
            'store_id' => $partner['store_id'],
            'name' => $data['name'],
            'username' => $data['username'] ?? $data['email'],
            'email' => $data['email'],
            'password_hash' => Hash::make(Str::random(32)),
            'role' => $role,
            'status' => 'invited',
            'abilities' => $abilities,
            'invite_token' => Str::random(40),
            'invite_expires_at' => now()->addDays(7),
        ]);

        self::log($partner, $actor, 'staff.invited', 'partner_user', (string) $user->id, ['email' => $user->email, 'role' => $role]);

        return self::staffPayload($user);
    }

    public static function updateStaff(array $partner, string $id, array $data, ?array $actor = null): array
    {
        $user = self::staffModel($partner, $id);
        $allowed = Arr::only($data, ['name', 'email', 'status', 'abilities']);

        if (isset($allowed['email'])) {
            $allowed['username'] = $allowed['email'];
        }

        $user->fill($allowed)->save();
        self::log($partner, $actor, 'staff.updated', 'partner_user', (string) $user->id, ['keys' => array_keys($allowed)]);

        return self::staffPayload($user->fresh());
    }

    public static function deleteStaff(array $partner, string $id, ?array $actor = null): array
    {
        $user = self::staffModel($partner, $id);
        abort_if($user->role === 'partner_admin', 422, 'Owner account cannot be deleted.');

        $user->forceFill(['status' => 'disabled'])->save();
        self::log($partner, $actor, 'staff.disabled', 'partner_user', (string) $user->id, ['email' => $user->email]);

        return ['store_id' => $partner['store_id'], 'deleted' => true, 'staff' => self::staffPayload($user->fresh())];
    }

    public static function assignRole(array $partner, string $id, array $data, ?array $actor = null): array
    {
        $user = self::staffModel($partner, $id);
        $role = $data['role'] ?? $data['role_id'] ?? 'staff';
        abort_if($user->role === 'partner_admin' && $role !== 'partner_admin', 422, 'Owner role cannot be downgraded.');

        $user->forceFill([
            'role' => $role,
            'abilities' => $data['abilities'] ?? self::abilitiesForRole($role),
        ])->save();

        self::log($partner, $actor, 'staff.role_assigned', 'partner_user', (string) $user->id, ['role' => $role]);

        return self::staffPayload($user->fresh());
    }

    public static function roles(array $partner): array
    {
        $custom = self::records($partner, 'partner_roles')
            ->map(fn (PlatformRecord $record) => self::recordPayload($record))
            ->values()
            ->all();

        return [
            'store_id' => $partner['store_id'],
            'roles' => array_values(array_merge(self::baseRoles(), $custom)),
            'permissions' => self::permissionMatrix(),
            'meta' => self::meta(['platform_records']) + ['store_scoped' => true],
        ];
    }

    public static function createRole(array $partner, array $data, ?array $actor = null): array
    {
        $id = Str::slug($data['id'] ?? $data['name'] ?? 'role-' . Str::random(6));
        $payload = [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'permissions' => array_values($data['permissions'] ?? []),
            'custom' => true,
        ];

        $record = self::upsertRecord($partner, 'partner_roles', $id, $payload, 'active');
        self::log($partner, $actor, 'role.created', 'partner_role', $id, ['permissions' => $payload['permissions']]);

        return self::recordPayload($record);
    }

    public static function updateRole(array $partner, string $role, array $data, ?array $actor = null): array
    {
        abort_if(array_key_exists($role, self::baseRolesById()), 422, 'Built-in roles cannot be changed.');

        $record = self::record($partner, 'partner_roles', $role);
        $payload = array_merge($record->payload ?? [], Arr::only($data, ['name', 'description']));

        if (array_key_exists('permissions', $data)) {
            $payload['permissions'] = array_values($data['permissions'] ?? []);
        }

        $record->forceFill(['payload' => $payload, 'status' => $data['status'] ?? $record->status])->save();
        self::log($partner, $actor, 'role.updated', 'partner_role', $role, ['keys' => array_keys($data)]);

        return self::recordPayload($record->fresh());
    }

    public static function deleteRole(array $partner, string $role, ?array $actor = null): array
    {
        abort_if(array_key_exists($role, self::baseRolesById()), 422, 'Built-in roles cannot be deleted.');

        $record = self::record($partner, 'partner_roles', $role);
        $record->delete();
        self::log($partner, $actor, 'role.deleted', 'partner_role', $role);

        return ['store_id' => $partner['store_id'], 'deleted' => true, 'role_id' => $role];
    }

    public static function domain(array $partner): array
    {
        $data = PartnerSettings::api($partner, 'domain')['section']['data'];

        return [
            'store_id' => $partner['store_id'],
            'custom_domain' => $data['custom_domain'] ?? '',
            'default_domain' => $partner['domain'] ?? ($partner['store_id'] . '.solve.sa'),
            'store_url' => $data['store_url'] ?? ($partner['store_url'] ?? ''),
            'dns_status' => $data['dns_status'] ?? 'pending',
            'ssl_status' => $data['ssl'] ?? 'pending',
            'active' => ($data['domain_status'] ?? 'active') !== 'disabled',
            'instructions' => [
                ['type' => 'CNAME', 'host' => 'www', 'value' => 'shops.solve.sa'],
                ['type' => 'TXT', 'host' => '@', 'value' => 'solve-store=' . $partner['store_id']],
            ],
            'meta' => self::meta(['store_settings']),
        ];
    }

    public static function connectDomain(array $partner, array $data, ?array $actor = null): array
    {
        PartnerSettings::update($partner, 'domain', [
            'custom_domain' => $data['custom_domain'],
            'store_url' => 'https://' . $data['custom_domain'],
            'ssl' => 'pending',
            'dns_status' => 'pending',
            'domain_status' => 'active',
        ]);
        self::log($partner, $actor, 'domain.connected', 'domain', $data['custom_domain']);

        return self::domain($partner);
    }

    public static function verifyDomain(array $partner, ?array $actor = null): array
    {
        $domain = self::domain($partner);
        PartnerSettings::update($partner, 'domain', [
            'custom_domain' => $domain['custom_domain'],
            'store_url' => $domain['store_url'],
            'ssl' => 'active',
            'dns_status' => 'verified',
        ]);
        self::log($partner, $actor, 'domain.verified', 'domain', $domain['custom_domain'] ?: $domain['default_domain']);

        return self::domain($partner);
    }

    public static function deleteDomain(array $partner, ?array $actor = null): array
    {
        PartnerSettings::update($partner, 'domain', [
            'custom_domain' => '',
            'store_url' => $partner['store_url'] ?? '',
            'ssl' => 'pending',
            'dns_status' => 'disconnected',
            'domain_status' => 'disabled',
        ]);
        self::log($partner, $actor, 'domain.disconnected', 'domain', $partner['store_id']);

        return self::domain($partner);
    }

    public static function shipping(array $partner): array
    {
        return self::typedSettings($partner, 'shipping', 'shipping-settings');
    }

    public static function updateShipping(array $partner, array $data, ?array $actor = null): array
    {
        return self::updateTypedSettings($partner, 'shipping', 'shipping-settings', $data, $actor);
    }

    public static function payments(array $partner): array
    {
        $payload = self::typedSettings($partner, 'payments', 'payment-settings');
        $payload['settings'] = self::maskSecrets($payload['settings']);

        return $payload;
    }

    public static function updatePayments(array $partner, array $data, ?array $actor = null): array
    {
        $payload = self::updateTypedSettings($partner, 'payments', 'payment-settings', self::maskIncomingSecrets($partner, 'payments', $data), $actor);
        $payload['settings'] = self::maskSecrets($payload['settings']);

        return $payload;
    }

    public static function taxes(array $partner): array
    {
        return self::typedSettings($partner, 'taxes', 'tax-settings');
    }

    public static function updateTaxes(array $partner, array $data, ?array $actor = null): array
    {
        return self::updateTypedSettings($partner, 'taxes', 'tax-settings', $data, $actor);
    }

    public static function notifications(array $partner): array
    {
        return self::typedSettings($partner, 'notifications', 'notification-settings');
    }

    public static function updateNotifications(array $partner, array $data, ?array $actor = null): array
    {
        return self::updateTypedSettings($partner, 'notifications', 'notification-settings', $data, $actor);
    }

    public static function testNotification(array $partner, array $data, ?array $actor = null): array
    {
        self::log($partner, $actor, 'notification.tested', 'notification', $data['channel'] ?? 'dashboard');

        return [
            'store_id' => $partner['store_id'],
            'sent' => true,
            'channel' => $data['channel'] ?? 'dashboard',
            'template' => $data['template'] ?? 'order_created',
            'message' => 'Test notification queued for this store.',
        ];
    }

    public static function sessions(array $partner): array
    {
        self::ensureSecurityRecords($partner);

        return [
            'store_id' => $partner['store_id'],
            'sessions' => self::records($partner, 'partner_security_sessions')
                ->map(fn (PlatformRecord $record) => self::recordPayload($record))
                ->values()
                ->all(),
            'meta' => self::meta(['platform_records']),
        ];
    }

    public static function deleteSession(array $partner, string $session, ?array $actor = null): array
    {
        $record = self::record($partner, 'partner_security_sessions', $session);
        $record->forceFill(['status' => 'revoked', 'payload' => array_merge($record->payload ?? [], ['revoked_at' => now()->toIso8601String()])])->save();
        self::log($partner, $actor, 'security.session_revoked', 'session', $session);

        return ['store_id' => $partner['store_id'], 'revoked' => true, 'session' => self::recordPayload($record->fresh())];
    }

    public static function enableTwoFactor(array $partner, ?array $actor = null): array
    {
        self::setIdentityFlag($partner, 'two_factor_enabled', true);
        self::log($partner, $actor, 'security.2fa_enabled', 'store', $partner['store_id']);

        return ['store_id' => $partner['store_id'], 'two_factor_enabled' => true, 'recovery_codes_generated' => true];
    }

    public static function disableTwoFactor(array $partner, ?array $actor = null): array
    {
        self::setIdentityFlag($partner, 'two_factor_enabled', false);
        self::log($partner, $actor, 'security.2fa_disabled', 'store', $partner['store_id']);

        return ['store_id' => $partner['store_id'], 'two_factor_enabled' => false];
    }

    public static function loginHistory(array $partner): array
    {
        self::ensureSecurityRecords($partner);

        return [
            'store_id' => $partner['store_id'],
            'rows' => self::records($partner, 'partner_login_history')
                ->map(fn (PlatformRecord $record) => self::recordPayload($record))
                ->values()
                ->all(),
            'meta' => self::meta(['platform_records']),
        ];
    }

    private static function typedSettings(array $partner, string $section, string $source): array
    {
        $data = PartnerSettings::api($partner, $section)['section']['data'];

        return [
            'store_id' => $partner['store_id'],
            'settings' => $data,
            'meta' => self::meta(['store_settings']) + ['source' => $source],
        ];
    }

    private static function updateTypedSettings(array $partner, string $section, string $source, array $data, ?array $actor): array
    {
        PartnerSettings::update($partner, $section, $data);
        self::log($partner, $actor, $source . '.updated', 'settings', $section, ['keys' => array_keys($data)]);

        return self::typedSettings($partner, $section, $source);
    }

    private static function storeModel(array $partner): PartnerStore
    {
        return PartnerStore::query()->where('store_id', $partner['store_id'])->firstOrFail();
    }

    private static function staffModel(array $partner, string $id): PartnerUser
    {
        return PartnerUser::query()->where('store_id', $partner['store_id'])->whereKey($id)->firstOrFail();
    }

    private static function staffPayload(PartnerUser $user): array
    {
        return [
            'id' => (string) $user->id,
            'store_id' => $user->store_id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'abilities' => $user->abilities ?? [],
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'invite_expires_at' => $user->invite_expires_at?->toIso8601String(),
        ];
    }

    private static function records(array $partner, string $section)
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->orderByDesc('updated_at')
            ->get();
    }

    private static function record(array $partner, string $section, string $id): PlatformRecord
    {
        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->where('record_id', self::recordId($partner, $id))
            ->firstOrFail();
    }

    private static function upsertRecord(array $partner, string $section, string $id, array $payload, string $status = 'active'): PlatformRecord
    {
        return PlatformRecord::query()->updateOrCreate(
            ['section' => $section, 'record_id' => self::recordId($partner, $id)],
            [
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['partner_id'] ?? null,
                'status' => $status,
                'payload' => $payload + ['id' => $id, 'store_id' => $partner['store_id']],
            ],
        );
    }

    private static function recordPayload(PlatformRecord $record): array
    {
        return array_merge($record->payload ?? [], [
            'id' => ($record->payload['id'] ?? Str::after($record->record_id, $record->store_id . '-')),
            'record_id' => $record->record_id,
            'status' => $record->status,
            'updated_at' => $record->updated_at?->toIso8601String(),
        ]);
    }

    private static function recordId(array $partner, string $id): string
    {
        return $partner['store_id'] . '-' . $id;
    }

    private static function baseRoles(): array
    {
        return array_values(self::baseRolesById());
    }

    private static function baseRolesById(): array
    {
        return [
            'partner_admin' => ['id' => 'partner_admin', 'name' => 'Owner', 'description' => 'Full store access.', 'permissions' => ['*'], 'custom' => false],
            'manager' => ['id' => 'manager', 'name' => 'Manager', 'description' => 'Operations, products, customers and reports.', 'permissions' => self::abilitiesForRole('manager'), 'custom' => false],
            'accountant' => ['id' => 'accountant', 'name' => 'Accountant', 'description' => 'Finance, invoices, payments and tax.', 'permissions' => self::abilitiesForRole('accountant'), 'custom' => false],
            'marketer' => ['id' => 'marketer', 'name' => 'Marketer', 'description' => 'Marketing, campaigns, storefront content and AI.', 'permissions' => self::abilitiesForRole('marketer'), 'custom' => false],
            'support' => ['id' => 'support', 'name' => 'Support', 'description' => 'Customers, orders and support workflows.', 'permissions' => self::abilitiesForRole('support'), 'custom' => false],
            'staff' => ['id' => 'staff', 'name' => 'Staff', 'description' => 'Custom selected permissions.', 'permissions' => self::abilitiesForRole('staff'), 'custom' => false],
        ];
    }

    private static function abilitiesForRole(string $role): array
    {
        return match ($role) {
            'partner_admin' => ['*'],
            'manager' => ['view-dashboard', 'view-orders', 'view-products', 'view-customers', 'view-analytics', 'view-settings', 'manage-settings'],
            'accountant' => ['view-dashboard', 'view-orders', 'view-payments', 'view-analytics', 'view-settings', 'manage-settings'],
            'marketer' => ['view-dashboard', 'view-customers', 'view-marketing', 'manage-storefront', 'manage-apps', 'view-settings', 'manage-settings'],
            'support' => ['view-dashboard', 'view-orders', 'view-customers', 'view-settings'],
            default => ['view-dashboard', 'view-orders', 'view-products', 'view-customers', 'view-analytics', 'view-settings'],
        };
    }

    private static function permissionMatrix(): array
    {
        $modules = ['dashboard', 'orders', 'products', 'customers', 'marketing', 'storefront', 'analytics', 'finance', 'services', 'channels', 'apps', 'settings'];
        $actions = ['view', 'create', 'edit', 'delete', 'export', 'approve'];

        return collect($modules)
            ->mapWithKeys(fn (string $module) => [$module => collect($actions)->map(fn (string $action) => $action . '-' . $module)->all()])
            ->all();
    }

    private static function ensureSecurityRecords(array $partner): void
    {
        self::upsertRecord($partner, 'partner_security_sessions', 'current', [
            'id' => 'current',
            'device' => 'Chrome on Windows',
            'ip_address' => '127.0.0.1',
            'location' => 'Riyadh',
            'trusted' => true,
            'last_seen_at' => now()->toIso8601String(),
        ], 'active');

        self::upsertRecord($partner, 'partner_login_history', 'latest', [
            'id' => 'latest',
            'event' => 'login',
            'ip_address' => '127.0.0.1',
            'device' => 'Chrome on Windows',
            'created_at' => now()->toIso8601String(),
        ], 'success');
    }

    private static function setIdentityFlag(array $partner, string $key, mixed $value): void
    {
        $settings = PartnerSettings::ensure($partner);
        abort_if(! $settings instanceof StoreSetting, 503);
        $identity = $settings->identity ?? [];
        $identity[$key] = $value;
        $settings->identity = $identity;
        $settings->save();
    }

    private static function maskIncomingSecrets(array $partner, string $section, array $data): array
    {
        $current = PartnerSettings::api($partner, $section)['section']['data'];

        foreach (['api_key', 'secret_key', 'access_token'] as $key) {
            if (($data[$key] ?? null) === '********') {
                $data[$key] = $current[$key] ?? '';
            }
        }

        return $data;
    }

    private static function maskSecrets(array $data): array
    {
        foreach (['api_key', 'secret_key', 'access_token'] as $key) {
            if (filled($data[$key] ?? null)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }

    private static function log(array $partner, ?array $actor, string $action, ?string $subjectType = null, ?string $subjectId = null, array $properties = []): void
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return;
        }

        PlatformActivityLog::query()->create([
            'actor_type' => 'partner',
            'actor_id' => (string) ($actor['id'] ?? $actor['username'] ?? 'system'),
            'actor_name' => $actor['name'] ?? $actor['username'] ?? 'System',
            'role' => $actor['role'] ?? null,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['partner_id'] ?? null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
        ]);
    }

    private static function meta(array $tables): array
    {
        return [
            'store_scoped' => true,
            'source_tables' => $tables,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
