<?php

namespace App\Support;

use App\Models\PartnerStore;
use App\Models\PlatformRecord;
use Illuminate\Support\Facades\Schema;

class SubscriptionLifecycle
{
    public static function usage(PartnerStore $store): array
    {
        $plan = SubscriptionManager::plan($store->plan);
        $counts = [
            'products' => self::countRecords($store->store_id, 'products'),
            'orders' => self::countRecords($store->store_id, 'orders'),
            'staff' => $store->users()->count(),
            'apps' => self::countInstalledApps($store->store_id),
            'channels' => self::countEnabledChannels($store->store_id),
            'ai_requests' => self::countRecords($store->store_id, 'partner_ai_usage'),
            'automations' => self::countActiveAutomations($store->store_id),
        ];

        return [
            'store_id' => $store->store_id,
            'plan' => $plan['name'],
            'limits' => $plan['limits'],
            'counts' => $counts,
            'exceeded' => collect($counts)->filter(function (int $count, string $key) use ($plan) {
                $limit = $plan['limits'][$key] ?? 'unlimited';

                return $limit !== 'unlimited' && $count > (int) $limit;
            })->keys()->values()->all(),
        ];
    }

    public static function renew(PartnerStore $store, ?string $plan = null, int $months = 1): PartnerStore
    {
        $targetPlan = SubscriptionManager::plan($plan ?: $store->plan);
        $start = now();
        $renewal = $targetPlan['cycle'] === 'annual' ? $start->copy()->addYear() : $start->copy()->addMonths(max(1, $months));

        $store->forceFill([
            'plan' => $targetPlan['name'],
            'status' => 'active',
            'payment_status' => 'paid',
            'subscription_started_at' => $store->subscription_started_at ?: $start->toDateString(),
            'subscription_renews_at' => $renewal->toDateString(),
            'metadata' => array_merge($store->metadata ?? [], [
                'billing_cycle' => $targetPlan['cycle'],
                'limits' => $targetPlan['limits'],
                'features' => $targetPlan['features'],
                'last_renewed_at' => now()->toIso8601String(),
            ]),
        ])->save();

        self::syncSubscriptionRecord($store, 'active');
        PlatformAudit::notify('subscription_renewed', 'تم تجديد اشتراك المتجر', $store->name, [
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'severity' => 'success',
            'url' => route('admin.subscriptions'),
        ]);

        return $store->refresh();
    }

    public static function markPaymentFailed(PartnerStore $store, string $reason = 'payment_failed'): PartnerStore
    {
        $store->forceFill([
            'status' => 'past_due',
            'payment_status' => 'failed',
            'metadata' => array_merge($store->metadata ?? [], [
                'failed_payment_reason' => $reason,
                'failed_payment_at' => now()->toIso8601String(),
            ]),
        ])->save();

        self::syncSubscriptionRecord($store, 'past_due');
        PlatformAudit::notify('billing_failed', 'فشل دفع اشتراك متجر', $store->name, [
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'severity' => 'warning',
            'url' => route('admin.subscriptions'),
        ]);

        return $store->refresh();
    }

    public static function enforceExpirations(): array
    {
        if (! Schema::hasTable('partner_stores')) {
            return ['processed' => 0, 'suspended' => 0, 'warnings' => 0];
        }

        $processed = 0;
        $suspended = 0;
        $warnings = 0;

        PartnerStore::query()
            ->whereNotNull('subscription_renews_at')
            ->orderBy('id')
            ->chunk(100, function ($stores) use (&$processed, &$suspended, &$warnings) {
                foreach ($stores as $store) {
                    $processed++;
                    $renewal = $store->subscription_renews_at;
                    $daysLeft = now()->startOfDay()->diffInDays($renewal, false);

                    if ($daysLeft < 0 && ! in_array($store->status, ['suspended', 'موقوف'], true)) {
                        $store->forceFill([
                            'status' => 'suspended',
                            'payment_status' => $store->payment_status === 'paid' ? 'expired' : ($store->payment_status ?: 'expired'),
                            'metadata' => array_merge($store->metadata ?? [], [
                                'auto_suspended_at' => now()->toIso8601String(),
                                'auto_suspension_reason' => 'subscription_expired',
                            ]),
                        ])->save();

                        self::syncSubscriptionRecord($store, 'suspended');
                        PlatformAudit::notify('subscription_suspended', 'تم تعليق متجر لانتهاء الاشتراك', $store->name, [
                            'store_id' => $store->store_id,
                            'partner_id' => $store->partner_id,
                            'severity' => 'danger',
                            'url' => route('admin.subscriptions'),
                        ]);
                        $suspended++;
                    } elseif ($daysLeft >= 0 && $daysLeft <= 3) {
                        PlatformAudit::notify('subscription_expiring', 'اشتراك يوشك على الانتهاء', $store->name . ' ينتهي خلال ' . $daysLeft . ' يوم', [
                            'store_id' => $store->store_id,
                            'partner_id' => $store->partner_id,
                            'severity' => 'warning',
                            'url' => route('admin.subscriptions'),
                        ]);
                        $warnings++;
                    }
                }
            });

        return compact('processed', 'suspended', 'warnings');
    }

    public static function syncSubscriptionRecord(PartnerStore $store, string $status): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        $plan = SubscriptionManager::plan($store->plan);

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'subscriptions', 'record_id' => 'subscription-' . $store->store_id],
            [
                'store_id' => $store->store_id,
                'partner_id' => $store->partner_id,
                'status' => $status,
                'payload' => [
                    'store' => $store->name,
                    'owner' => $store->owner_name,
                    'owner_email' => $store->owner_email,
                    'plan' => $store->plan,
                    'status' => $status,
                    'payment_status' => $store->payment_status,
                    'billing_cycle' => $plan['cycle'],
                    'amount' => $plan['price'] ? $plan['price'] . ' SAR' : 'Custom',
                    'start_date' => $store->subscription_started_at?->toDateString(),
                    'renewal_date' => $store->subscription_renews_at?->toDateString(),
                    'limits' => $plan['limits'],
                    'usage' => self::usage($store)['counts'],
                ],
            ],
        );
    }

    private static function countRecords(string $storeId, string $section): int
    {
        if (! Schema::hasTable('platform_records')) {
            return 0;
        }

        return PlatformRecord::query()->where('store_id', $storeId)->where('section', $section)->count();
    }

    private static function countInstalledApps(string $storeId): int
    {
        if (! Schema::hasTable('platform_records')) {
            return 0;
        }

        return PlatformRecord::query()
            ->where('store_id', $storeId)
            ->where('section', 'partner_apps')
            ->whereIn('payload->status_key', ['installed', 'needs_setup', 'disabled'])
            ->count();
    }

    private static function countEnabledChannels(string $storeId): int
    {
        if (! Schema::hasTable('platform_records')) {
            return 0;
        }

        return PlatformRecord::query()
            ->where('store_id', $storeId)
            ->where('section', 'partner_channels')
            ->where('payload->status_key', 'enabled')
            ->count();
    }

    private static function countActiveAutomations(string $storeId): int
    {
        if (! Schema::hasTable('platform_records')) {
            return 0;
        }

        return PlatformRecord::query()
            ->where('store_id', $storeId)
            ->where('section', 'partner_automations')
            ->where('payload->status_key', 'installed')
            ->count();
    }
}
