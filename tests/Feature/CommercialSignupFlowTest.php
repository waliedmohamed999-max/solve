<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformRecord;
use App\Models\StoreOnboardingStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommercialSignupFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_wizard_renders_plans_and_conversion_copy(): void
    {
        $this->get('/signup')
            ->assertOk()
            ->assertSee('Signup Wizard')
            ->assertSee('Starter')
            ->assertSee('Growth')
            ->assertSee('Enterprise')
            ->assertSee('تجربة مجانية');

        $this->loginAsAdmin()
            ->get('/admin/production-readiness')
            ->assertOk()
            ->assertSee('Commercial Signup Flow');
    }

    public function test_merchant_can_create_trial_store_and_enter_dashboard(): void
    {
        $response = $this->post('/signup', [
            'store_name' => 'متجر الاختبار التجاري',
            'owner_name' => 'سارة التاجر',
            'email' => 'commercial-owner@example.test',
            'phone' => '+966500000001',
            'plan' => 'Growth',
            'password' => 'StrongPass2026',
            'password_confirmation' => 'StrongPass2026',
        ]);

        $response->assertRedirect('/partner/dashboard');
        $response->assertSessionHas('partner_user.store_id');

        $store = PartnerStore::query()->where('owner_email', 'commercial-owner@example.test')->firstOrFail();
        $user = PartnerUser::query()->where('username', 'commercial-owner@example.test')->firstOrFail();

        $this->assertSame('trial', $store->status);
        $this->assertSame('Growth', $store->plan);
        $this->assertSame('trial', $store->payment_status);
        $this->assertTrue(Hash::check('StrongPass2026', $user->password_hash));

        $this->assertDatabaseHas('store_onboarding_steps', [
            'store_id' => $store->store_id,
            'step_key' => 'store-profile',
            'status' => 'completed',
        ]);

        $this->assertGreaterThanOrEqual(5, StoreOnboardingStep::query()->where('store_id', $store->store_id)->count());

        $this->assertDatabaseHas('platform_records', [
            'section' => 'subscriptions',
            'record_id' => 'subscription-' . $store->store_id,
            'store_id' => $store->store_id,
            'status' => 'trial',
        ]);

        $subscription = PlatformRecord::query()->where('section', 'subscriptions')->where('store_id', $store->store_id)->firstOrFail();
        $this->assertSame('Growth', $subscription->payload['plan']);
        $this->assertSame(1000, $subscription->payload['limits']['products']);

        $this->assertDatabaseHas('platform_notifications', [
            'type' => 'signup',
            'store_id' => $store->store_id,
        ]);

        $this->assertTrue(PlatformActivityLog::query()->where('action', 'signup_completed')->where('store_id', $store->store_id)->exists());
    }

    public function test_signup_email_must_be_unique_for_real_merchants(): void
    {
        PartnerStore::query()->create([
            'partner_id' => 'partner-existing',
            'store_id' => 'store-existing',
            'name' => 'Existing',
            'owner_email' => 'existing@example.test',
        ]);

        PartnerUser::query()->create([
            'partner_store_id' => PartnerStore::query()->first()->id,
            'store_id' => 'store-existing',
            'name' => 'Existing Owner',
            'username' => 'existing@example.test',
            'email' => 'existing@example.test',
            'password_hash' => Hash::make('StrongPass2026'),
            'role' => 'partner_admin',
            'status' => 'active',
            'abilities' => ['*'],
        ]);

        $this->post('/signup', [
            'store_name' => 'Duplicate Store',
            'owner_name' => 'Duplicate Owner',
            'email' => 'existing@example.test',
            'phone' => '+966500000002',
            'plan' => 'Starter',
            'password' => 'StrongPass2026',
            'password_confirmation' => 'StrongPass2026',
        ])->assertSessionHasErrors('email');
    }
}
