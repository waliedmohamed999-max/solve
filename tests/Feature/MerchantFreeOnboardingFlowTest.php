<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantFreeOnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_registers_merchant_on_free_plan_and_shows_locked_value(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('انضم كتاجر')
            ->assertSee('ابدأ مجاناً')
            ->assertSee('جرّب لوحة التحكم');

        $this->get('/merchant/register')
            ->assertOk()
            ->assertSee('Free');

        $this->post('/merchant/register', [
            'owner_name' => 'Noura Salem',
            'store_name' => 'Noura Beauty',
            'email' => 'noura@example.test',
            'phone' => '+966500000111',
            'password' => 'StrongPass2026',
            'password_confirmation' => 'StrongPass2026',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'activity_type' => 'Beauty',
            'store_slug' => 'noura-beauty',
            'terms' => '1',
        ])->assertRedirect('/merchant/onboarding/plans');

        $store = PartnerStore::query()->where('store_id', 'store-noura-beauty')->firstOrFail();
        $this->assertSame('Free', $store->plan);
        $this->assertSame('free', $store->status);
        $this->assertSame('free', $store->payment_status);
        $this->assertTrue(PartnerUser::query()->where('username', 'noura@example.test')->where('store_id', $store->store_id)->exists());

        $this->get('/merchant/onboarding/plans')->assertOk()->assertSee('الباقة الحالية');
        $this->get('/merchant/onboarding')->assertOk()->assertSee('جهّز متجرك');
        $this->get('/partner/dashboard')->assertOk();

        $navigation = $this->getJson('/partner/api/navigation')->assertOk()->json('sections');
        $apps = collect($navigation)->firstWhere('key', 'apps');
        $this->assertContains('ai', collect($apps['items'] ?? [])->pluck('key')->all());
        $this->assertTrue((bool) collect($apps['items'])->firstWhere('key', 'ai')['locked']);

        $this->get('/partner/apps/ai')->assertRedirect('/partner/subscription');
        $this->assertTrue(PlatformActivityLog::query()->where('action', 'subscription.access_denied')->where('store_id', $store->store_id)->exists());
    }

    public function test_merchant_public_apis_onboarding_checkout_and_admin_controls(): void
    {
        $this->postJson('/api/merchant/check-email', ['email' => 'merchant@example.test'])
            ->assertOk()
            ->assertJsonPath('available', true);

        $this->postJson('/api/merchant/register', [
            'owner_name' => 'Atlas Owner',
            'store_name' => 'Atlas Free',
            'email' => 'merchant@example.test',
            'phone' => '+966500000222',
            'password' => 'StrongPass2026',
            'password_confirmation' => 'StrongPass2026',
            'store_slug' => 'atlas-free',
            'terms' => '1',
        ])->assertCreated()->assertJsonPath('created', true);

        $store = PartnerStore::query()->where('store_id', 'store-atlas-free')->firstOrFail();

        $this->getJson('/api/merchant/onboarding')->assertOk()->assertJsonStructure(['steps']);
        $this->patchJson('/api/merchant/onboarding', ['step_key' => 'identity', 'status' => 'completed'])->assertOk();
        $this->postJson('/api/merchant/onboarding/complete')->assertOk()->assertJsonPath('completed', true);

        $this->getJson('/api/partner/features')->assertOk()->assertJsonPath('subscription.plan_name', 'Free');
        $this->postJson('/api/partner/features/check', ['feature_key' => 'custom_domain'])->assertOk()->assertJsonPath('allowed', false);
        $this->getJson('/api/partner/usage')->assertOk()->assertJsonPath('usage.plan', 'Free');

        $this->get('/partner/subscription/checkout/growth')->assertOk()->assertSee('ترقية الباقة');
        $this->postJson('/api/partner/subscription/checkout', ['plan' => 'Growth', 'cycle' => 'monthly'])->assertOk()->assertJsonPath('subscription.plan_name', 'Growth');

        $this->loginAsAdmin()->getJson('/api/admin/merchants')->assertOk();
        $this->loginAsAdmin()->getJson('/api/admin/merchants/' . $store->store_id)->assertOk()->assertJsonPath('store_id', $store->store_id);
        $this->loginAsAdmin()->patchJson('/api/admin/merchants/' . $store->store_id . '/status', ['status' => 'suspended'])->assertOk()->assertJsonPath('status', 'suspended');
    }
}
