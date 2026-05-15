<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerSettingsPhaseTwelveTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_pages_and_official_summary_are_store_scoped(): void
    {
        $this->loginAtlasOwner();

        foreach (['store', 'identity', 'staff', 'permissions', 'domain', 'shipping', 'payments', 'taxes', 'notifications', 'security'] as $section) {
            $this->get('/partner/settings/' . $section)
                ->assertOk()
                ->assertSee('store-atlas')
                ->assertDontSee('store-rowaa');
        }

        $this->getJson('/api/partner/settings')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('meta.store_scoped', true);
    }

    public function test_store_identity_shipping_payment_tax_notification_and_domain_apis_persist_for_current_store(): void
    {
        $this->loginAtlasOwner();

        $this->patchJson('/api/partner/settings/store', [
            'settings' => [
                'name' => 'Atlas Production Store',
                'city' => 'Riyadh',
                'currency' => 'SAR',
                'store_status' => 'open',
            ],
        ])->assertOk()->assertJsonPath('store.store_id', 'store-atlas');

        $this->patchJson('/api/partner/settings/identity', [
            'settings' => [
                'primary_color' => '#4c1d95',
                'font' => 'Tajawal',
            ],
        ])->assertOk();

        $this->postJson('/api/partner/settings/identity/upload', [
            'type' => 'logo',
            'path' => 'uploads/identity/atlas-logo.png',
        ])->assertCreated()->assertJsonPath('section.data.logo', 'uploads/identity/atlas-logo.png');

        $this->patchJson('/api/partner/shipping-settings', [
            'settings' => [
                'provider' => 'Solve Logistics',
                'regions' => 'Riyadh,Jeddah',
                'default_fee' => '25',
            ],
        ])->assertOk()->assertJsonPath('settings.provider', 'Solve Logistics');

        $this->patchJson('/api/partner/payment-settings', [
            'settings' => [
                'provider' => 'Apple Pay',
                'mode' => 'production',
                'api_key' => 'sk_live_atlas',
            ],
        ])->assertOk()->assertJsonPath('settings.api_key', '********');

        $this->patchJson('/api/partner/tax-settings', [
            'settings' => [
                'enabled' => 'enabled',
                'vat' => '15%',
                'tax_number' => '300000000000003',
            ],
        ])->assertOk()->assertJsonPath('settings.tax_number', '300000000000003');

        $this->patchJson('/api/partner/notification-settings', [
            'settings' => [
                'channels' => 'كل القنوات',
                'whatsapp_enabled' => 'enabled',
            ],
        ])->assertOk()->assertJsonPath('settings.whatsapp_enabled', 'enabled');

        $this->postJson('/api/partner/notification-settings/test', [
            'channel' => 'whatsapp',
            'template' => 'order_created',
        ])->assertOk()->assertJsonPath('sent', true);

        $this->postJson('/api/partner/domain/connect', [
            'custom_domain' => 'atlas.example.com',
        ])->assertCreated();

        $this->deleteJson('/api/partner/domain')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('active', false);

        $this->assertDatabaseHas('store_settings', ['store_id' => 'store-atlas']);
        $this->assertDatabaseMissing('store_settings', ['store_id' => 'store-rowaa', 'identity->name' => 'Atlas Production Store']);
    }

    public function test_staff_roles_and_security_are_real_store_scoped_records(): void
    {
        $this->loginAtlasOwner();

        $staff = $this->postJson('/api/partner/staff/invite', [
            'name' => 'Operations User',
            'email' => 'ops-atlas@example.test',
            'role' => 'support',
        ])->assertCreated()->json();

        $this->getJson('/api/partner/staff')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonFragment(['email' => 'ops-atlas@example.test'])
            ->assertJsonMissing(['store_id' => 'store-rowaa']);

        $this->postJson('/api/partner/roles', [
            'id' => 'fulfillment-lead',
            'name' => 'Fulfillment Lead',
            'permissions' => ['view-orders', 'edit-orders', 'export-orders'],
        ])->assertCreated()->assertJsonPath('id', 'fulfillment-lead');

        $this->patchJson('/api/partner/staff/' . $staff['id'] . '/role', [
            'role' => 'fulfillment-lead',
            'abilities' => ['view-orders', 'edit-orders'],
        ])->assertOk()->assertJsonPath('role', 'fulfillment-lead');

        $this->patchJson('/api/partner/roles/fulfillment-lead', [
            'description' => 'Owns shipping preparation.',
        ])->assertOk()->assertJsonPath('description', 'Owns shipping preparation.');

        $this->deleteJson('/api/partner/roles/fulfillment-lead')->assertOk()->assertJsonPath('deleted', true);

        $this->getJson('/api/partner/security/sessions')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonFragment(['id' => 'current']);

        $this->postJson('/api/partner/security/2fa/enable')
            ->assertOk()
            ->assertJsonPath('two_factor_enabled', true);

        $this->getJson('/api/partner/security/login-history')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonFragment(['event' => 'login']);

        $this->deleteJson('/api/partner/security/sessions/current')
            ->assertOk()
            ->assertJsonPath('revoked', true);

        $this->deleteJson('/api/partner/staff/' . $staff['id'])->assertOk()->assertJsonPath('deleted', true);

        $this->assertDatabaseHas('platform_activity_logs', ['store_id' => 'store-atlas', 'action' => 'staff.invited']);
        $this->assertDatabaseHas('platform_records', ['store_id' => 'store-atlas', 'section' => 'partner_security_sessions']);
        $this->assertDatabaseMissing('partner_users', ['store_id' => 'store-rowaa', 'email' => 'ops-atlas@example.test']);
    }

    public function test_staff_without_manage_settings_cannot_use_sensitive_settings_apis(): void
    {
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ]);

        $this->getJson('/api/partner/settings')->assertOk();
        $this->getJson('/api/partner/staff')->assertForbidden();
        $this->patchJson('/api/partner/settings/store', [
            'settings' => ['name' => 'Should Not Save'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('store_settings', ['store_id' => 'store-atlas', 'identity->name' => 'Should Not Save']);
    }

    private function loginAtlasOwner(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);
    }
}
