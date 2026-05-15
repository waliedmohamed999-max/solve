<?php

namespace App\Support;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PlatformReadiness
{
    public static function report(): array
    {
        $checks = [
            self::check('بيئة الإنتاج', app()->environment('production'), 'اضبط APP_ENV=production قبل الإطلاق.'),
            self::check('إخفاء أخطاء Laravel', config('app.debug') === false, 'اضبط APP_DEBUG=false حتى لا تظهر تفاصيل الأخطاء للزوار.'),
            self::check('مفتاح التطبيق', is_string(config('app.key')) && config('app.key') !== '', 'نفذ php artisan key:generate واضبط APP_KEY.'),
            self::check('رابط HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'اضبط APP_URL على دومين HTTPS الحقيقي.'),
            self::check('حساب أدمن مشفر', self::adminHashConfigured(), 'اضبط ADMIN_USERNAME و ADMIN_PASSWORD_HASH. لا تستخدم ADMIN_PASSWORD في الإنتاج.'),
            self::check('تشفير الجلسات', (bool) config('session.encrypt'), 'فعّل SESSION_ENCRYPT=true لحماية جلسات الأدمن والتجار.'),
            self::check('كوكيز آمنة', (bool) config('session.secure'), 'فعّل SESSION_SECURE_COOKIE=true في بيئة HTTPS.'),
            self::check('جلسات إنتاجية', ! in_array(config('session.driver'), ['array', 'cookie', 'file'], true), 'استخدم SESSION_DRIVER=database أو redis.'),
            self::check('كاش إنتاجي', ! in_array(config('cache.default'), ['array', 'file'], true), 'استخدم CACHE_STORE=database أو redis.'),
            self::check('طوابير غير sync', config('queue.default') !== 'sync', 'استخدم QUEUE_CONNECTION=database أو redis مع queue worker.'),
            self::check('جداول SaaS الأساسية', self::hasTables(['platform_records', 'store_settings', 'store_onboarding_steps', 'platform_notifications']), 'نفذ migrations الأساسية.'),
            self::check('جداول الشركاء', self::hasTables(['partner_stores', 'partner_users']), 'نفذ migrations الخاصة بحسابات الشركاء.'),
            self::check('متاجر مفعلة', self::partnerStoresCount() > 0, 'أنشئ متجر شريك واحد على الأقل من لوحة الأدمن.'),
            self::check('حسابات تجار', self::partnerUsersCount() > 0, 'كل متجر يحتاج حساب تاجر بكلمة مرور مشفرة.'),
            self::check('البريد خارج log', config('mail.default') !== 'log', 'اضبط مزود بريد فعلي لإرسال الدعوات والتنبيهات.'),
            self::check('Health Check API', self::healthIsOk(), 'Expose /health for load balancers and production monitoring.'),
            self::check('Slow Query Monitoring', (int) env('SLOW_QUERY_THRESHOLD_MS', 750) > 0, 'Set SLOW_QUERY_THRESHOLD_MS to log slow database queries.'),
            self::check('Recent Platform Backup', PlatformBackup::hasRecentBackup((int) env('PLATFORM_BACKUP_MAX_AGE_HOURS', 24)), 'Schedule php artisan solve:backup at least once per day before production launch.'),
            self::check('Commercial Signup Flow', \Illuminate\Support\Facades\Route::has('commercial.signup') && \Illuminate\Support\Facades\Route::has('commercial.signup.store'), 'Keep the self-service signup wizard available for merchant acquisition.'),
            self::check('Subscription Lifecycle', \Illuminate\Support\Facades\Route::has('admin.api.subscriptions.renew') && \Illuminate\Support\Facades\Route::has('admin.api.subscriptions.enforce'), 'Enable renewal, failed payment handling, usage tracking, and automatic suspension jobs.'),
        ];

        $blocking = collect($checks)->where('level', 'blocking')->count();
        $warnings = collect($checks)->where('level', 'warning')->count();

        return [
            'ready' => $blocking === 0,
            'score' => (int) round((collect($checks)->where('passed', true)->count() / max(1, count($checks))) * 100),
            'blocking' => $blocking,
            'warnings' => $warnings,
            'checks' => $checks,
            'metrics' => [
                ['label' => 'المتاجر', 'value' => (string) self::partnerStoresCount()],
                ['label' => 'مستخدمي التجار', 'value' => (string) self::partnerUsersCount()],
                ['label' => 'الجداول', 'value' => self::hasTables(['partner_stores', 'partner_users', 'platform_records']) ? 'جاهزة' : 'ناقصة'],
                ['label' => 'النتيجة', 'value' => $blocking === 0 ? 'قابل للإطلاق' : 'يحتاج إكمال'],
                ['label' => 'Backups', 'value' => PlatformBackup::latest()['path'] ?? 'Not configured'],
            ],
        ];
    }

    private static function check(string $label, bool $passed, string $message): array
    {
        return [
            'label' => $label,
            'passed' => $passed,
            'message' => $message,
            'level' => $passed ? 'passed' : 'blocking',
        ];
    }

    private static function adminHashConfigured(): bool
    {
        $username = (string) config('admin.username');
        $passwordHash = (string) config('admin.password_hash');

        if ($username === '' || $passwordHash === '') {
            return false;
        }

        return str_starts_with($passwordHash, '$2y$')
            || str_starts_with($passwordHash, '$argon2i$')
            || str_starts_with($passwordHash, '$argon2id$');
    }

    private static function hasTables(array $tables): bool
    {
        try {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private static function partnerStoresCount(): int
    {
        try {
            return Schema::hasTable('partner_stores') ? PartnerStore::query()->count() : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private static function partnerUsersCount(): int
    {
        try {
            return Schema::hasTable('partner_users') ? PartnerUser::query()->count() : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private static function healthIsOk(): bool
    {
        try {
            return PlatformHealth::summary()['status'] === 'ok';
        } catch (Throwable) {
            return false;
        }
    }
}
