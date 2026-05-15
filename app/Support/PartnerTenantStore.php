<?php

namespace App\Support;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerTenantStore
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_PARTNER_ADMIN = 'partner_admin';
    public const ROLE_STAFF = 'staff';

    public static function partners(): array
    {
        if (self::databaseBacked()) {
            self::bootstrapDemoPartnersForNonProduction();

            return PartnerStore::query()
                ->with('users')
                ->orderBy('id')
                ->get()
                ->map(fn (PartnerStore $store) => self::partnerFromModel($store))
                ->all();
        }

        return self::demoPartners();
    }

    public static function provisionFromStoreRecord(array $record): array
    {
        if (! self::databaseBacked()) {
            return [];
        }

        $storeId = (string) ($record['id'] ?? $record['store_id'] ?? self::storeIdFromRecord($record));
        $partnerId = Str::of($storeId)->replaceStart('store-', '')->slug('-')->toString() ?: Str::slug((string) ($record['name'] ?? 'partner'));
        $domain = trim((string) ($record['domain'] ?? ''));
        $ownerEmail = trim((string) ($record['owner_email'] ?? ''));
        $temporaryPassword = Str::password(18, true, true, false, false);

        $store = PartnerStore::query()->updateOrCreate(
            ['store_id' => $storeId],
            [
                'partner_id' => $partnerId,
                'name' => trim((string) ($record['name'] ?? $record['brand_name'] ?? $storeId)),
                'brand_name' => trim((string) ($record['brand_name'] ?? $record['name'] ?? '')),
                'owner_name' => trim((string) ($record['owner'] ?? '')),
                'owner_email' => $ownerEmail !== '' ? $ownerEmail : null,
                'owner_phone' => trim((string) ($record['owner_phone'] ?? '')) ?: null,
                'status' => trim((string) ($record['status'] ?? 'مراجعة')) ?: 'مراجعة',
                'plan' => trim((string) ($record['plan'] ?? 'Starter')) ?: 'Starter',
                'domain' => $domain !== '' ? $domain : null,
                'store_url' => $domain !== '' ? 'https://' . ltrim($domain, '/') : null,
                'logo' => $record['logo_file'] ?? 'solve-logo.png',
                'payment_status' => ($record['status'] ?? '') === 'نشط' ? 'مدفوع' : 'بانتظار السداد',
                'subscription_started_at' => self::dateOrNull($record['launch_date'] ?? null) ?? now()->toDateString(),
                'subscription_renews_at' => now()->addYear()->toDateString(),
                'payment_provider' => $record['payment_gateway'] ?? null,
                'shipping_provider' => $record['shipping_partner'] ?? null,
                'metadata' => [
                    'segment' => $record['segment'] ?? null,
                    'city' => $record['city'] ?? null,
                    'monthly_target' => $record['monthly_target'] ?? null,
                    'expected_orders' => $record['expected_orders'] ?? null,
                    'onboarding_stage' => $record['onboarding_stage'] ?? 'New',
                    'source' => 'admin_store_provisioning',
                ],
            ],
        );

        $username = $ownerEmail !== '' ? $ownerEmail : 'merchant+' . $storeId . '@solve.local';
        $user = PartnerUser::query()->firstOrNew(['username' => $username]);
        $isNew = ! $user->exists;

        $user->fill([
            'partner_store_id' => $store->id,
            'store_id' => $store->store_id,
            'name' => trim((string) ($record['owner'] ?? $store->name)),
            'email' => $ownerEmail !== '' ? $ownerEmail : null,
            'role' => self::ROLE_PARTNER_ADMIN,
            'status' => $store->status === 'نشط' ? 'active' : 'invited',
            'abilities' => ['*'],
            'invite_token' => $isNew ? Str::random(48) : $user->invite_token,
            'invite_expires_at' => $isNew ? now()->addDays(7) : $user->invite_expires_at,
        ]);

        if ($isNew) {
            $user->password_hash = Hash::make($temporaryPassword);
        }

        $user->save();

        PartnerDashboardSummary::ensureStoreData(self::partnerFromModel($store->refresh()->load('users')));

        return [
            'store_id' => $store->store_id,
            'username' => $username,
            'temporary_password' => $isNew ? $temporaryPassword : null,
            'invite_token' => $user->invite_token,
        ];
    }

    private static function demoPartners(): array
    {
        return [
            [
                'id' => 'atlas',
                'store_id' => 'store-atlas',
                'name' => 'متجر أطلس',
                'logo' => 'solve-logo.png',
                'store_url' => 'https://atlas.solve.sa',
                'owner' => 'سارة الحربي',
                'email' => 'sara@atlas.sa',
                'phone' => '+966500000001',
                'status' => 'نشط',
                'plan' => 'Enterprise',
                'subscription_at' => '2026-01-15',
                'renewal_at' => '2027-01-15',
                'payment_status' => 'مدفوع',
                'last_login' => 'منذ 12 دقيقة',
                'domain' => 'atlas.solve.sa',
                'payment_provider' => 'Mada',
                'shipping_provider' => 'Aramex',
                'notifications' => 'البريد والرسائل النصية',
                'users' => [
                    ['name' => 'سارة الحربي', 'username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026', 'role' => self::ROLE_PARTNER_ADMIN, 'email' => 'merchant@atlas.sa'],
                    ['name' => 'فريق أطلس', 'username' => 'staff@atlas.sa', 'password' => 'AtlasStaff@2026', 'role' => self::ROLE_STAFF, 'email' => 'staff@atlas.sa'],
                ],
                'metrics' => [
                    'orders' => '2,418',
                    'sales' => '418,200 ر.س',
                    'products' => '184',
                    'customers' => '7,840',
                    'payments' => '97.8%',
                    'shipments' => '2,301',
                    'conversion' => '4.8%',
                    'returns' => '1.6%',
                ],
                'alerts' => [
                    ['title' => 'أداء الدفع مستقر', 'body' => 'نسبة نجاح مدى أعلى من المتوسط خلال آخر 7 أيام.', 'tone' => 'success'],
                    ['title' => 'مراجعة المخزون', 'body' => '12 منتجًا اقترب من حد النفاد.', 'tone' => 'warning'],
                ],
                'performance' => [
                    ['label' => 'نمو المبيعات', 'value' => '+18.4%', 'width' => '82%'],
                    ['label' => 'رضا العملاء', 'value' => '94%', 'width' => '94%'],
                    ['label' => 'التسليم في الوقت', 'value' => '91%', 'width' => '91%'],
                ],
                'orders' => [
                    ['id' => 'ORD-2401', 'customer' => 'نورة سالم', 'status' => 'مكتمل', 'amount' => '820 ر.س', 'date' => '2026-05-10'],
                    ['id' => 'ORD-2402', 'customer' => 'محمد العتيبي', 'status' => 'قيد الشحن', 'amount' => '1,240 ر.س', 'date' => '2026-05-11'],
                    ['id' => 'ORD-2403', 'customer' => 'ريم خالد', 'status' => 'قيد المعالجة', 'amount' => '430 ر.س', 'date' => '2026-05-12'],
                ],
                'products' => [
                    ['sku' => 'AT-100', 'name' => 'عباية أطلس كلاسيك', 'stock' => '48', 'price' => '320 ر.س', 'status' => 'منشور'],
                    ['sku' => 'AT-220', 'name' => 'حقيبة سفر جلدية', 'stock' => '12', 'price' => '690 ر.س', 'status' => 'منشور'],
                    ['sku' => 'AT-310', 'name' => 'مجموعة إكسسوارات', 'stock' => '6', 'price' => '180 ر.س', 'status' => 'مخزون منخفض'],
                ],
                'customers' => [
                    ['name' => 'نورة سالم', 'email' => 'noura@example.sa', 'orders' => '8', 'spent' => '4,120 ر.س'],
                    ['name' => 'محمد العتيبي', 'email' => 'mohammed@example.sa', 'orders' => '5', 'spent' => '2,840 ر.س'],
                    ['name' => 'ريم خالد', 'email' => 'reem@example.sa', 'orders' => '3', 'spent' => '1,230 ر.س'],
                ],
                'payments' => [
                    ['id' => 'PAY-901', 'gateway' => 'Mada', 'status' => 'ناجحة', 'amount' => '820 ر.س', 'settlement' => 'خلال 48 ساعة'],
                    ['id' => 'PAY-902', 'gateway' => 'Apple Pay', 'status' => 'ناجحة', 'amount' => '1,240 ر.س', 'settlement' => 'خلال 24 ساعة'],
                    ['id' => 'PAY-903', 'gateway' => 'Visa', 'status' => 'مراجعة', 'amount' => '430 ر.س', 'settlement' => 'معلقة'],
                ],
                'shipments' => [
                    ['id' => 'SHP-701', 'carrier' => 'Aramex', 'status' => 'تم التسليم', 'city' => 'الرياض', 'eta' => '2026-05-10'],
                    ['id' => 'SHP-702', 'carrier' => 'SMSA', 'status' => 'في الطريق', 'city' => 'جدة', 'eta' => '2026-05-13'],
                ],
            ],
            [
                'id' => 'rowaa',
                'store_id' => 'store-rowaa',
                'name' => 'Rowaa Beauty',
                'logo' => 'solve-logo.png',
                'store_url' => 'https://rowaa.solve.sa',
                'owner' => 'نورة سالم',
                'email' => 'noura@rowaa.sa',
                'phone' => '+966500000003',
                'status' => 'تحت المراجعة',
                'plan' => 'Starter',
                'subscription_at' => '2026-03-04',
                'renewal_at' => '2026-06-04',
                'payment_status' => 'بانتظار السداد',
                'last_login' => 'منذ ساعة',
                'domain' => 'rowaa.solve.sa',
                'payment_provider' => 'Visa',
                'shipping_provider' => 'Saudi Post',
                'notifications' => 'البريد فقط',
                'users' => [
                    ['name' => 'نورة سالم', 'username' => 'merchant@rowaa.sa', 'password' => 'RowaaMerchant@2026', 'role' => self::ROLE_PARTNER_ADMIN, 'email' => 'merchant@rowaa.sa'],
                    ['name' => 'خدمة العملاء', 'username' => 'staff@rowaa.sa', 'password' => 'RowaaStaff@2026', 'role' => self::ROLE_STAFF, 'email' => 'staff@rowaa.sa'],
                ],
                'metrics' => [
                    'orders' => '143',
                    'sales' => '22,900 ر.س',
                    'products' => '36',
                    'customers' => '914',
                    'payments' => '93.1%',
                    'shipments' => '128',
                    'conversion' => '2.9%',
                    'returns' => '3.8%',
                ],
                'alerts' => [
                    ['title' => 'الفاتورة مستحقة', 'body' => 'يلزم سداد الاشتراك قبل التجديد القادم.', 'tone' => 'danger'],
                    ['title' => 'المتجر تحت المراجعة', 'body' => 'الملف التجاري يحتاج اعتمادًا نهائيًا من الإدارة.', 'tone' => 'warning'],
                ],
                'performance' => [
                    ['label' => 'نمو المبيعات', 'value' => '+6.1%', 'width' => '46%'],
                    ['label' => 'رضا العملاء', 'value' => '86%', 'width' => '86%'],
                    ['label' => 'التسليم في الوقت', 'value' => '78%', 'width' => '78%'],
                ],
                'orders' => [
                    ['id' => 'ORD-1101', 'customer' => 'شهد علي', 'status' => 'قيد المعالجة', 'amount' => '260 ر.س', 'date' => '2026-05-11'],
                    ['id' => 'ORD-1102', 'customer' => 'لمى فهد', 'status' => 'مكتمل', 'amount' => '510 ر.س', 'date' => '2026-05-12'],
                ],
                'products' => [
                    ['sku' => 'RB-10', 'name' => 'مجموعة عناية يومية', 'stock' => '22', 'price' => '190 ر.س', 'status' => 'منشور'],
                    ['sku' => 'RB-18', 'name' => 'سيروم ترطيب', 'stock' => '4', 'price' => '145 ر.س', 'status' => 'مخزون منخفض'],
                ],
                'customers' => [
                    ['name' => 'شهد علي', 'email' => 'shahd@example.sa', 'orders' => '4', 'spent' => '980 ر.س'],
                    ['name' => 'لمى فهد', 'email' => 'lama@example.sa', 'orders' => '2', 'spent' => '510 ر.س'],
                ],
                'payments' => [
                    ['id' => 'PAY-410', 'gateway' => 'Visa', 'status' => 'ناجحة', 'amount' => '260 ر.س', 'settlement' => 'خلال 72 ساعة'],
                    ['id' => 'PAY-411', 'gateway' => 'Bank Transfer', 'status' => 'معلقة', 'amount' => '510 ر.س', 'settlement' => 'بانتظار المطابقة'],
                ],
                'shipments' => [
                    ['id' => 'SHP-410', 'carrier' => 'Saudi Post', 'status' => 'في الطريق', 'city' => 'الدمام', 'eta' => '2026-05-14'],
                ],
            ],
        ];
    }

    public static function allPartners(): array
    {
        return self::partners();
    }

    public static function findPartner(string $idOrStoreId): ?array
    {
        foreach (self::partners() as $partner) {
            if ($partner['id'] === $idOrStoreId || $partner['store_id'] === $idOrStoreId) {
                return $partner;
            }
        }

        return null;
    }

    public static function authenticate(string $username, string $password): ?array
    {
        if (self::databaseBacked()) {
            self::bootstrapDemoPartnersForNonProduction();

            $user = PartnerUser::query()
                ->where('username', $username)
                ->whereIn('status', ['active', 'invited'])
                ->first();

            if (! $user || ! Hash::check($password, $user->password_hash)) {
                return null;
            }

            $store = PartnerStore::query()->where('store_id', $user->store_id)->first();

            if (! $store) {
                return null;
            }

            $user->forceFill(['last_login_at' => now()])->save();

            return [
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'email' => $user->email,
                'abilities' => $user->abilities ?? [],
                'partner_id' => $store->partner_id,
                'store_id' => $store->store_id,
                'store_name' => $store->name,
            ];
        }

        foreach (self::partners() as $partner) {
            foreach ($partner['users'] as $user) {
                if (! hash_equals((string) $user['username'], $username)) {
                    continue;
                }

                $passwordMatches = isset($user['password_hash'])
                    ? Hash::check($password, (string) $user['password_hash'])
                    : hash_equals((string) ($user['password'] ?? ''), $password);

                if ($passwordMatches) {
                    return array_merge(Arr::except($user, ['password', 'password_hash']), [
                        'partner_id' => $partner['id'],
                        'store_id' => $partner['store_id'],
                        'store_name' => $partner['name'],
                    ]);
                }
            }
        }

        return null;
    }

    public static function currentUser(Request $request): ?array
    {
        $user = $request->session()->get('partner_user');

        return is_array($user) ? $user : null;
    }

    public static function currentPartner(Request $request): ?array
    {
        $user = self::currentUser($request);

        if (! $user) {
            return null;
        }

        return self::findPartner((string) $user['store_id']);
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            self::ROLE_PARTNER_ADMIN => 'Owner',
            'manager' => 'Manager',
            'accountant' => 'Accountant',
            'marketer' => 'Marketer',
            'support' => 'Support',
            self::ROLE_STAFF => 'Staff User',
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            default => Str::headline(str_replace('_', ' ', $role)),
        };
    }

    public static function can(array $user, string $ability): bool
    {
        $role = $user['role'] ?? '';

        if ($role === self::ROLE_PARTNER_ADMIN) {
            return true;
        }

        if ($role !== '') {
            $abilities = $user['abilities'] ?? [];
            $abilities = is_array($abilities) ? $abilities : [];

            if ($role === self::ROLE_STAFF && ! in_array('manage-storefront', $abilities, true)) {
                $abilities = array_values(array_unique(array_merge($abilities, self::defaultAbilitiesForRole($role))));
            }

            if ($abilities === []) {
                $abilities = self::defaultAbilitiesForRole($role);
            }

            return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
        }

        return false;
    }

    private static function defaultAbilitiesForRole(string $role): array
    {
        return match ($role) {
            'manager' => ['view-dashboard', 'view-orders', 'view-products', 'view-customers', 'view-analytics', 'view-settings', 'view-subscription'],
            'accountant' => ['view-dashboard', 'view-orders', 'view-payments', 'view-analytics', 'view-subscription'],
            'marketer' => ['view-dashboard', 'view-marketing', 'view-customers', 'manage-storefront'],
            'support' => ['view-dashboard', 'view-orders', 'view-customers'],
            self::ROLE_STAFF => ['view-dashboard', 'view-orders', 'view-products', 'view-customers', 'view-analytics', 'view-settings', 'view-subscription', 'manage-storefront'],
            default => [],
        };
    }

    private static function databaseBacked(): bool
    {
        return Schema::hasTable('partner_stores') && Schema::hasTable('partner_users');
    }

    private static function bootstrapDemoPartnersForNonProduction(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (PartnerStore::query()->exists()) {
            return;
        }

        foreach (self::demoPartners() as $partner) {
            $store = PartnerStore::query()->create([
                'partner_id' => $partner['id'],
                'store_id' => $partner['store_id'],
                'name' => $partner['name'],
                'brand_name' => $partner['name'],
                'owner_name' => $partner['owner'] ?? null,
                'owner_email' => $partner['email'] ?? null,
                'owner_phone' => $partner['phone'] ?? null,
                'status' => $partner['status'] ?? 'نشط',
                'plan' => $partner['plan'] ?? 'Starter',
                'domain' => $partner['domain'] ?? null,
                'store_url' => $partner['store_url'] ?? null,
                'logo' => $partner['logo'] ?? 'solve-logo.png',
                'payment_status' => $partner['payment_status'] ?? null,
                'subscription_started_at' => $partner['subscription_at'] ?? null,
                'subscription_renews_at' => $partner['renewal_at'] ?? null,
                'payment_provider' => $partner['payment_provider'] ?? null,
                'shipping_provider' => $partner['shipping_provider'] ?? null,
                'metadata' => Arr::only($partner, ['metrics', 'alerts', 'performance', 'orders', 'products', 'customers', 'payments', 'shipments', 'notifications']),
            ]);

            foreach ($partner['users'] ?? [] as $user) {
                PartnerUser::query()->create([
                    'partner_store_id' => $store->id,
                    'store_id' => $store->store_id,
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'email' => $user['email'] ?? $user['username'],
                    'password_hash' => Hash::make((string) ($user['password'] ?? Str::random(32))),
                    'role' => $user['role'],
                    'status' => 'active',
                    'abilities' => $user['role'] === self::ROLE_PARTNER_ADMIN ? ['*'] : self::defaultAbilitiesForRole((string) $user['role']),
                ]);
            }
        }
    }

    private static function partnerFromModel(PartnerStore $store): array
    {
        $metadata = $store->metadata ?? [];

        return [
            'id' => $store->partner_id,
            'store_id' => $store->store_id,
            'name' => $store->name,
            'logo' => $store->logo ?: 'solve-logo.png',
            'store_url' => $store->store_url ?: ($store->domain ? 'https://' . $store->domain : null),
            'owner' => $store->owner_name,
            'email' => $store->owner_email,
            'phone' => $store->owner_phone,
            'status' => $store->status,
            'plan' => $store->plan,
            'subscription_at' => $store->subscription_started_at?->toDateString(),
            'renewal_at' => $store->subscription_renews_at?->toDateString(),
            'payment_status' => $store->payment_status,
            'last_login' => optional($store->users->sortByDesc('last_login_at')->first()?->last_login_at)->diffForHumans() ?? 'لم يسجل الدخول بعد',
            'domain' => $store->domain,
            'payment_provider' => $store->payment_provider,
            'shipping_provider' => $store->shipping_provider,
            'notifications' => $metadata['notifications'] ?? null,
            'users' => $store->users->map(fn (PartnerUser $user) => [
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'email' => $user->email,
                'status' => $user->status,
                'abilities' => $user->abilities ?? [],
            ])->values()->all(),
            'metrics' => $metadata['metrics'] ?? [],
            'alerts' => $metadata['alerts'] ?? [],
            'performance' => $metadata['performance'] ?? [],
            'orders' => $metadata['orders'] ?? [],
            'products' => $metadata['products'] ?? [],
            'customers' => $metadata['customers'] ?? [],
            'payments' => $metadata['payments'] ?? [],
            'shipments' => $metadata['shipments'] ?? [],
        ];
    }

    private static function storeIdFromRecord(array $record): string
    {
        return 'store-' . Str::slug((string) ($record['brand_name'] ?? $record['name'] ?? Str::random(8)));
    }

    private static function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
