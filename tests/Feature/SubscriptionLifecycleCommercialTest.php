<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionLifecycleCommercialTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_inspect_plan_usage_and_limit_overages(): void
    {
        $store = $this->createStore(['plan' => 'Starter']);

        foreach (range(1, 101) as $index) {
            $this->record($store, 'products', 'product-' . $index);
        }

        $this->loginAsAdmin()
            ->getJson('/admin/api/subscriptions/' . $store->store_id . '/usage')
            ->assertOk()
            ->assertJsonPath('usage.plan', 'Starter')
            ->assertJsonPath('usage.limits.products', 100)
            ->assertJsonPath('usage.counts.products', 101)
            ->assertJsonPath('usage.exceeded.0', 'products');
    }

    public function test_admin_can_renew_and_change_subscription_plan(): void
    {
        $store = $this->createStore([
            'plan' => 'Starter',
            'status' => 'past_due',
            'payment_status' => 'failed',
            'subscription_renews_at' => now()->subDay()->toDateString(),
        ]);

        $this->loginAsAdmin()
            ->postJson('/admin/api/subscriptions/' . $store->store_id . '/renew', [
                'plan' => 'Growth',
                'months' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('store.plan', 'Growth')
            ->assertJsonPath('store.status', 'active')
            ->assertJsonPath('store.payment_status', 'paid')
            ->assertJsonPath('usage.limits.products', 1000);

        $store->refresh();

        $this->assertSame('Growth', $store->plan);
        $this->assertSame('active', $store->status);
        $this->assertSame('paid', $store->payment_status);
        $this->assertTrue($store->subscription_renews_at->greaterThan(now()->addMonth()));

        $subscription = PlatformRecord::query()
            ->where('section', 'subscriptions')
            ->where('record_id', 'subscription-' . $store->store_id)
            ->firstOrFail();

        $this->assertSame('active', $subscription->status);
        $this->assertSame('Growth', $subscription->payload['plan']);
        $this->assertTrue(PlatformActivityLog::query()->where('action', 'subscription_renewed')->where('store_id', $store->store_id)->exists());
    }

    public function test_failed_payment_marks_store_past_due_without_leaking_other_stores(): void
    {
        $store = $this->createStore(['store_id' => 'store-billing']);
        $other = $this->createStore(['store_id' => 'store-other', 'partner_id' => 'partner-other']);

        $this->loginAsAdmin()
            ->postJson('/admin/api/subscriptions/' . $store->store_id . '/fail-payment', [
                'reason' => 'card_declined',
            ])
            ->assertOk()
            ->assertJsonPath('store.status', 'past_due')
            ->assertJsonPath('store.payment_status', 'failed');

        $this->assertSame('active', $other->fresh()->status);
        $this->assertDatabaseHas('platform_notifications', [
            'type' => 'billing_failed',
            'store_id' => $store->store_id,
        ]);
    }

    public function test_enforcement_suspends_expired_stores_and_surfaces_status_to_merchant(): void
    {
        $store = $this->createStore([
            'status' => 'active',
            'payment_status' => 'paid',
            'subscription_renews_at' => now()->subDay()->toDateString(),
        ]);

        $this->createPartnerUser($store);

        $this->loginAsAdmin()
            ->postJson('/admin/api/subscriptions/enforce')
            ->assertOk()
            ->assertJsonPath('processed', 1)
            ->assertJsonPath('suspended', 1);

        $this->assertSame('suspended', $store->fresh()->status);
        $this->assertSame('expired', $store->fresh()->payment_status);
        $this->assertDatabaseHas('platform_notifications', [
            'type' => 'subscription_suspended',
            'store_id' => $store->store_id,
        ]);

        $this->withSession([
            'partner_user' => [
                'id' => 1,
                'store_id' => $store->store_id,
                'role' => 'partner_admin',
                'abilities' => ['*'],
            ],
        ])->getJson('/api/partner/store/status')
            ->assertOk()
            ->assertJsonPath('store_id', $store->store_id)
            ->assertJsonPath('status', 'suspended');
    }

    public function test_subscription_enforcement_console_command_is_schedulable(): void
    {
        $this->createStore([
            'status' => 'active',
            'subscription_renews_at' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame(0, Artisan::call('solve:subscriptions:enforce'));
        $this->assertStringContainsString('Suspended stores: 1', Artisan::output());
    }

    public function test_production_readiness_tracks_subscription_lifecycle(): void
    {
        $this->loginAsAdmin()
            ->get('/admin/production-readiness')
            ->assertOk()
            ->assertSee('Subscription Lifecycle');
    }

    private function createStore(array $overrides = []): PartnerStore
    {
        return PartnerStore::query()->create(array_merge([
            'partner_id' => 'partner-atlas',
            'store_id' => 'store-atlas',
            'name' => 'Atlas Store',
            'brand_name' => 'Atlas Store',
            'owner_name' => 'Sara',
            'owner_email' => 'sara@example.test',
            'owner_phone' => '+966500000000',
            'status' => 'active',
            'plan' => 'Growth',
            'payment_status' => 'paid',
            'subscription_started_at' => now()->subMonth()->toDateString(),
            'subscription_renews_at' => now()->addMonth()->toDateString(),
        ], $overrides));
    }

    private function createPartnerUser(PartnerStore $store): PartnerUser
    {
        return PartnerUser::query()->create([
            'partner_store_id' => $store->id,
            'store_id' => $store->store_id,
            'name' => 'Store Owner',
            'username' => 'owner-' . $store->store_id . '@example.test',
            'email' => 'owner-' . $store->store_id . '@example.test',
            'password_hash' => Hash::make('StrongPass2026'),
            'role' => 'owner',
            'status' => 'active',
            'abilities' => ['*'],
        ]);
    }

    private function record(PartnerStore $store, string $section, string $recordId): PlatformRecord
    {
        return PlatformRecord::query()->create([
            'section' => $section,
            'record_id' => $recordId,
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'status' => 'active',
            'payload' => ['name' => $recordId],
        ]);
    }
}
