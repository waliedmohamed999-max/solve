<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagedSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_section_supports_filtering(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/stores?q=أطلس&status=نشط');

        $response->assertOk();
        $response->assertSee('متجر أطلس');
        $response->assertSee('admin/sections/stores/store-atlas', false);
        $response->assertDontSee('admin/sections/stores/store-abaad', false);
    }

    public function test_store_status_can_be_updated_from_section_actions(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/stores/store-shahd', [
            'status' => 'نشط',
            'current_status' => 'الكل',
        ]);

        $response->assertRedirect('/admin/stores');
        $response->assertSessionHas('status', 'تم تحديث المتجر بنجاح.');

        $saved = SiteContent::query()->where('key', 'admin_section:stores')->firstOrFail();
        $this->assertSame('نشط', $saved->payload[3]['status']);
    }

    public function test_store_can_be_created_from_section_form(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/stores', [
            'name' => 'متجر جديد',
            'brand_name' => 'New Brand',
            'owner' => 'مالك جديد',
            'owner_email' => 'owner@newstore.sa',
            'owner_phone' => '+966500000099',
            'status' => 'نشط',
            'plan' => 'Starter',
            'segment' => 'إلكترونيات',
            'domain' => 'new-store.solve.sa',
            'city' => 'الرياض',
            'launch_date' => '2026-03-17',
            'team_size' => '3',
            'payment_gateway' => 'مدى',
            'shipping_partner' => 'أرامكس',
            'inventory_source' => 'CSV',
            'monthly_target' => '50,000 ر.س',
            'expected_orders' => '250',
            'sales' => '0 ر.س',
            'orders' => '0',
            'created_at' => '17 مارس 2026',
            'onboarding_stage' => 'جديد',
            'notes' => 'سجل اختباري جديد',
        ]);

        $response->assertRedirect('/admin/stores');
        $response->assertSessionHas('status', 'تمت إضافة المتجر جديد بنجاح.');

        $saved = SiteContent::query()->where('key', 'admin_section:stores')->firstOrFail();
        $this->assertTrue(collect($saved->payload)->contains(fn (array $record) => $record['name'] === 'متجر جديد' && $record['domain'] === 'new-store.solve.sa'));
    }

    public function test_store_creation_provisions_partner_account_and_login(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/stores', [
            'name' => 'Ready Store',
            'brand_name' => 'Ready Brand',
            'owner' => 'Ready Owner',
            'owner_email' => 'ready-owner@example.test',
            'owner_phone' => '+966500000177',
            'status' => 'نشط',
            'plan' => 'Growth',
            'segment' => 'Electronics',
            'domain' => 'ready-store.solve.sa',
            'city' => 'Riyadh',
            'launch_date' => '2026-05-13',
            'team_size' => '5',
            'payment_gateway' => 'مدى',
            'shipping_partner' => 'أرامكس',
            'inventory_source' => 'ERP',
            'monthly_target' => '75,000 SAR',
            'expected_orders' => '350',
            'sales' => '0 SAR',
            'orders' => '0',
            'created_at' => '13 May 2026',
            'onboarding_stage' => 'Ready',
            'notes' => 'Production onboarding test',
        ]);

        $response->assertRedirect('/admin/stores');
        $response->assertSessionHas('provisioning.store_id');
        $temporaryPassword = session('provisioning.temporary_password');

        $store = PartnerStore::query()->where('domain', 'ready-store.solve.sa')->firstOrFail();
        $user = PartnerUser::query()->where('username', 'ready-owner@example.test')->firstOrFail();

        $this->assertSame($store->store_id, $user->store_id);
        $this->assertSame('partner_admin', $user->role);
        $this->assertNotSame($temporaryPassword, $user->password_hash);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password_hash));

        $this->post('/partner/login', [
            'username' => 'ready-owner@example.test',
            'password' => $temporaryPassword,
        ])->assertRedirect('/partner/dashboard');

        $this->get('/partner/dashboard')
            ->assertOk()
            ->assertSee($store->store_id)
            ->assertDontSee('store-atlas');
    }

    public function test_store_can_be_edited_from_section_form(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/stores/store-atlas/edit', [
            'name' => 'متجر أطلس',
            'brand_name' => 'Atlas Fashion',
            'owner' => 'سارة الحربي',
            'owner_email' => 'sara@atlas.sa',
            'owner_phone' => '+966500000001',
            'status' => 'نشط',
            'plan' => 'Enterprise Plus',
            'segment' => 'أزياء',
            'domain' => 'atlas-plus.solve.sa',
            'city' => 'الرياض',
            'launch_date' => '2026-01-15',
            'team_size' => '12',
            'payment_gateway' => 'مدى',
            'shipping_partner' => 'أرامكس',
            'inventory_source' => 'ERP',
            'monthly_target' => '500,000 ر.س',
            'expected_orders' => '2600',
            'sales' => '418,200 ر.س',
            'orders' => '2,418',
            'created_at' => '15 يناير 2026',
            'onboarding_stage' => 'جاهز للإطلاق',
            'notes' => 'تم تحديث الخطة إلى Enterprise Plus',
        ]);

        $response->assertRedirect('/admin/stores');
        $response->assertSessionHas('status', 'تم تعديل المتجر بنجاح.');

        $saved = SiteContent::query()->where('key', 'admin_section:stores')->firstOrFail();
        $this->assertSame('Enterprise Plus', $saved->payload[0]['plan']);
        $this->assertSame('atlas-plus.solve.sa', $saved->payload[0]['domain']);
    }

    public function test_section_can_be_exported_as_csv(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/sections/stores/export?status=نشط');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('اسم المتجر');
        $response->assertSee('متجر أطلس');
    }

    public function test_payment_operational_actions_update_financial_record(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/payments/payment-bank', [
            'action' => 'mark_invoice_paid',
        ]);

        $response->assertRedirect('/admin/payments');

        $saved = SiteContent::query()->where('key', 'admin_section:payments')->firstOrFail();
        $record = collect($saved->payload)->firstWhere('id', 'payment-bank');

        $this->assertSame("\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}", $record['invoice_status']);
        $this->assertSame("\u{0646}\u{0634}\u{0637}", $record['status']);
        $this->assertStringContainsString('Invoice was marked as paid', $record['ai_summary']);
    }

    public function test_subscription_operational_actions_can_renew_account(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/subscriptions/subscription-rowaa', [
            'action' => 'renew_subscription',
        ]);

        $response->assertRedirect('/admin/subscriptions');

        $saved = SiteContent::query()->where('key', 'admin_section:subscriptions')->firstOrFail();
        $record = collect($saved->payload)->firstWhere('id', 'subscription-rowaa');

        $this->assertSame("\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}", $record['invoice_status']);
        $this->assertSame("\u{0646}\u{0634}\u{0637}", $record['status']);
        $this->assertSame('2026-05-10', $record['renewal_date']);
    }

    public function test_shipping_operational_actions_can_dispatch_carrier(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/shipping/shipping-aramex', [
            'action' => 'dispatch_carrier',
        ]);

        $response->assertRedirect('/admin/shipping');

        $saved = SiteContent::query()->where('key', 'admin_section:shipping')->firstOrFail();
        $record = collect($saved->payload)->firstWhere('id', 'shipping-aramex');

        $this->assertSame("\u{062A}\u{0645} \u{0627}\u{0644}\u{0625}\u{0631}\u{0633}\u{0627}\u{0644}", $record['service_level']);
        $this->assertSame("\u{0646}\u{0634}\u{0637}", $record['status']);
    }

    public function test_support_operational_actions_can_resolve_ticket(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/support/support-1', [
            'action' => 'resolve_ticket',
        ]);

        $response->assertRedirect('/admin/support');

        $saved = SiteContent::query()->where('key', 'admin_section:support')->firstOrFail();
        $record = collect($saved->payload)->firstWhere('id', 'support-1');

        $this->assertSame("\u{0645}\u{063A}\u{0644}\u{0642}", $record['status']);
        $this->assertSame("Met", $record['sla']);
    }

    public function test_settings_operational_actions_can_disable_module(): void
    {
        $response = $this->loginAsAdmin()->post('/admin/sections/settings/setting-auth', [
            'action' => 'disable_module',
        ]);

        $response->assertRedirect('/admin/settings');

        $saved = SiteContent::query()->where('key', 'admin_section:settings')->firstOrFail();
        $record = collect($saved->payload)->firstWhere('id', 'setting-auth');

        $this->assertSame("\u{0645}\u{0639}\u{0637}\u{0644}", $record['status']);
    }

    public function test_payments_section_can_create_and_edit_records(): void
    {
        $createResponse = $this->loginAsAdmin()->post('/admin/sections/payments', [
            'gateway' => 'STC Pay',
            'region' => 'السعودية',
            'status' => 'نشط',
            'success_rate' => '99.1%',
            'failed_rate' => '0.9%',
            'refunds' => '3',
            'settlement_cycle' => '24 ساعة',
        ]);

        $createResponse->assertRedirect('/admin/payments');
        $createResponse->assertSessionHas('status', 'تمت إضافة بوابة الدفع جديد بنجاح.');

        $saved = SiteContent::query()->where('key', 'admin_section:payments')->firstOrFail();
        $this->assertTrue(collect($saved->payload)->contains(fn (array $record) => $record['gateway'] === 'STC Pay'));

        $recordId = collect($saved->payload)->firstWhere('gateway', 'STC Pay')['id'];

        $editResponse = $this->loginAsAdmin()->post("/admin/sections/payments/{$recordId}/edit", [
            'gateway' => 'STC Pay',
            'region' => 'الخليج',
            'status' => 'مراقبة',
            'success_rate' => '98.7%',
            'failed_rate' => '1.3%',
            'refunds' => '8',
            'settlement_cycle' => '48 ساعة',
        ]);

        $editResponse->assertRedirect('/admin/payments');
        $editResponse->assertSessionHas('status', 'تم تعديل بوابة الدفع بنجاح.');

        $saved = SiteContent::query()->where('key', 'admin_section:payments')->firstOrFail();
        $this->assertTrue(collect($saved->payload)->contains(fn (array $record) => $record['gateway'] === 'STC Pay' && $record['region'] === 'الخليج' && $record['status'] === 'مراقبة'));
    }
}
