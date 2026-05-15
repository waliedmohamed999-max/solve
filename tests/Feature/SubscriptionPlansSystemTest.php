<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionPlansSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_plans_coupons_and_store_subscriptions(): void
    {
        $store = $this->createStore(['plan' => 'Starter']);
        $this->createPartnerUser($store);

        $this->loginAsAdmin()
            ->get('/admin/plans')
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('Growth');

        $this->postJson('/api/admin/plans', [
            'name' => 'Pro',
            'price' => 1299,
            'yearly_price' => 12990,
            'trial_days' => 21,
            'limit_products' => 2500,
            'limit_orders' => 10000,
            'limit_staff' => 20,
            'limit_branches' => 5,
            'limit_apps' => 20,
            'limit_channels' => 5,
            'advanced_reports' => true,
            'custom_domain' => true,
            'automation' => true,
            'features' => "Advanced reports\nAutomation",
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'Pro')
            ->assertJsonPath('limits.products', 2500);

        $this->patchJson('/api/admin/subscriptions/' . $store->store_id . '/plan', [
            'plan' => 'Pro',
            'cycle' => 'yearly',
        ])
            ->assertOk()
            ->assertJsonPath('plan_name', 'Pro')
            ->assertJsonPath('usage.0.key', 'products');

        $this->postJson('/api/admin/coupons', [
            'code' => 'LAUNCH20',
            'type' => 'percent',
            'value' => 20,
            'plan' => 'Pro',
        ])->assertCreated()->assertJsonPath('code', 'LAUNCH20');

        $this->assertSame('Pro', $store->fresh()->plan);
        $this->assertDatabaseHas('platform_records', ['section' => 'subscription_plans', 'record_id' => 'plan-pro']);
        $this->assertDatabaseHas('platform_records', ['section' => 'subscription_coupons']);
        $this->assertTrue(PlatformActivityLog::query()->where('action', 'subscription.plan_changed')->where('store_id', $store->store_id)->exists());
    }

    public function test_partner_can_view_upgrade_renew_cancel_and_manage_billing_without_cross_store_leaks(): void
    {
        $store = $this->createStore(['store_id' => 'store-atlas', 'partner_id' => 'atlas', 'plan' => 'Starter']);
        $other = $this->createStore(['store_id' => 'store-rowaa', 'partner_id' => 'rowaa', 'plan' => 'Enterprise']);
        $user = $this->createPartnerUser($store);

        PlatformRecord::query()->create([
            'section' => 'subscription_invoices',
            'record_id' => 'other-invoice',
            'store_id' => $other->store_id,
            'status' => 'paid',
            'payload' => ['id' => 'other-invoice', 'store_id' => $other->store_id, 'amount' => 999],
        ]);

        $this->withSession(['partner_user' => [
            'id' => $user->id,
            'store_id' => $store->store_id,
            'role' => 'partner_admin',
            'username' => $user->username,
            'name' => $user->name,
        ]]);

        $this->get('/partner/subscription')
            ->assertOk()
            ->assertSee('Starter')
            ->assertDontSee('store-rowaa');

        $this->getJson('/api/partner/subscription')
            ->assertOk()
            ->assertJsonPath('subscription.store_id', 'store-atlas')
            ->assertJsonPath('subscription.plan_name', 'Starter');

        $this->postJson('/api/partner/subscription/upgrade', ['plan' => 'Growth', 'cycle' => 'monthly'])
            ->assertOk()
            ->assertJsonPath('subscription.plan_name', 'Growth');

        $this->postJson('/api/partner/payment-methods', [
            'brand' => 'Mada',
            'number' => '4111111111114242',
            'holder' => 'Sara',
        ])->assertCreated()->assertJsonPath('last4', '4242');

        $this->getJson('/api/partner/invoices')
            ->assertOk()
            ->assertJsonMissing(['store_id' => 'store-rowaa']);

        $this->postJson('/api/partner/subscription/cancel')
            ->assertOk()
            ->assertJsonPath('subscription.status', 'cancelled');

        $this->assertSame('cancelled', $store->fresh()->status);
    }

    public function test_subscription_limits_block_products_and_staff_from_backend(): void
    {
        $store = $this->createStore(['plan' => 'Starter']);
        $user = $this->createPartnerUser($store);

        foreach (range(1, 100) as $index) {
            PlatformRecord::query()->create([
                'section' => 'products',
                'record_id' => 'product-' . $index,
                'store_id' => $store->store_id,
                'status' => 'published',
                'payload' => ['name' => 'Product ' . $index],
            ]);
        }

        $this->withSession(['partner_user' => [
            'id' => $user->id,
            'store_id' => $store->store_id,
            'role' => 'partner_admin',
            'username' => $user->username,
            'name' => $user->name,
        ]]);

        $this->postJson('/api/partner/products', [
            'name' => 'Blocked Product',
            'sku' => 'BLOCKED-1',
            'type' => 'single',
            'status' => 'published',
            'price' => 10,
            'stock' => 1,
        ])->assertStatus(402);
    }

    public function test_feature_gates_block_locked_routes_and_log_denied_attempts(): void
    {
        $store = $this->createStore(['store_id' => 'store-free', 'partner_id' => 'free', 'plan' => 'Starter']);
        $user = $this->createPartnerUser($store);
        $this->loginPartner($user, $store);

        $this->getJson('/api/partner/ai/tools')
            ->assertStatus(402)
            ->assertJsonPath('reason', 'feature_locked')
            ->assertJsonPath('feature', 'ai');

        $this->get('/partner/apps/ai')
            ->assertRedirect('/partner/subscription');

        $this->assertTrue(PlatformActivityLog::query()
            ->where('action', 'subscription.access_denied')
            ->where('store_id', $store->store_id)
            ->where('properties->feature', 'ai')
            ->exists());
    }

    public function test_subscription_statuses_and_usage_limits_are_enforced_by_backend(): void
    {
        $this->loginAsAdmin()->postJson('/api/admin/plans', [
            'name' => 'Micro',
            'price' => 99,
            'limit_products' => 1,
            'limit_orders' => 0,
            'limit_staff' => 1,
            'limit_branches' => 1,
            'limit_apps' => 0,
            'limit_channels' => 1,
            'limit_ai_requests' => 1,
            'limit_automations' => 0,
            'apps' => true,
            'ai' => true,
            'automation' => true,
            'features' => 'Tiny operational plan',
        ])->assertCreated();

        $micro = $this->createStore(['store_id' => 'store-micro', 'partner_id' => 'micro', 'plan' => 'Micro']);
        $microUser = $this->createPartnerUser($micro);
        $this->loginPartner($microUser, $micro);

        $this->postJson('/api/partner/apps/mada-pay/install')->assertStatus(402);

        $this->postJson('/api/partner/automations', [
            'name' => 'Low stock alert',
            'trigger' => 'low_stock',
            'action' => 'send_notification',
        ])->assertStatus(402);

        PlatformRecord::query()->create([
            'section' => 'partner_ai_usage',
            'record_id' => 'ai-used',
            'store_id' => $micro->store_id,
            'status' => 'ok',
            'payload' => ['tool' => 'store-analysis', 'store_id' => $micro->store_id],
        ]);

        $this->postJson('/api/partner/ai/generate', [
            'tool' => 'store-analysis',
            'prompt' => 'Analyze my store',
        ])->assertStatus(402);

        $this->postJson('/api/partner/orders/manual', [
            'customer' => 'Noura',
            'product_id' => 'p-1',
            'item_name' => 'Test Product',
            'qty' => 1,
            'total' => 25,
            'payment_status' => 'paid',
        ])->assertStatus(402);

        $this->assertTrue(PlatformActivityLog::query()
            ->where('action', 'subscription.usage_denied')
            ->where('store_id', $micro->store_id)
            ->exists());

        $expired = $this->createStore([
            'store_id' => 'store-expired',
            'partner_id' => 'expired',
            'status' => 'expired',
            'subscription_renews_at' => now()->subDay()->toDateString(),
        ]);
        $expiredUser = $this->createPartnerUser($expired);
        $this->loginPartner($expiredUser, $expired);
        $this->getJson('/api/partner/products')
            ->assertStatus(402)
            ->assertJsonPath('reason', 'subscription_expired');

        $suspended = $this->createStore([
            'store_id' => 'store-suspended',
            'partner_id' => 'suspended',
            'status' => 'suspended',
        ]);
        $suspendedUser = $this->createPartnerUser($suspended);
        $this->loginPartner($suspendedUser, $suspended);
        $this->postJson('/api/partner/products', [
            'name' => 'Suspended Product',
            'sku' => 'SUSP-1',
            'type' => 'single',
            'status' => 'published',
            'price' => 10,
            'stock' => 1,
        ])
            ->assertStatus(423)
            ->assertJsonPath('reason', 'subscription_suspended');
    }

    private function createStore(array $overrides = []): PartnerStore
    {
        return PartnerStore::query()->create(array_merge([
            'partner_id' => 'atlas',
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
            'role' => 'partner_admin',
            'status' => 'active',
            'abilities' => ['*'],
        ]);
    }

    private function loginPartner(PartnerUser $user, PartnerStore $store): void
    {
        $this->withSession(['partner_user' => [
            'id' => $user->id,
            'store_id' => $store->store_id,
            'role' => 'partner_admin',
            'username' => $user->username,
            'name' => $user->name,
        ], 'admin_authenticated' => false]);
    }
}
