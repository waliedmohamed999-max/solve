<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformRecord;
use App\Support\PartnerTenantStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartnerFinalIntegrationPhaseThirteenTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_order_updates_inventory_finance_and_analytics_for_current_store_only(): void
    {
        $this->loginAsAtlasOwner();

        PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => 'phase13-product',
            'store_id' => 'store-atlas',
            'partner_id' => 'atlas',
            'status' => 'published',
            'payload' => [
                'id' => 'phase13-product',
                'name' => 'Phase 13 Product',
                'sku' => 'P13',
                'price' => '100 ر.س',
                'stock' => 10,
                'low_stock_threshold' => 2,
                'status' => 'منشور',
                'store_id' => 'store-atlas',
            ],
        ]);

        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'rowaa-secret-order',
            'store_id' => 'store-rowaa',
            'status' => 'secret',
            'payload' => ['order_number' => 'RO-SECRET', 'customer' => 'Rowaa', 'store_id' => 'store-rowaa'],
        ]);

        $order = $this->postJson('/api/partner/orders/manual', [
            'customer' => 'عميل اختبار',
            'phone' => '966500000000',
            'email' => 'customer@example.test',
            'product_id' => 'phase13-product',
            'product_sku' => 'P13',
            'item_name' => 'Phase 13 Product',
            'qty' => 3,
            'unit_price' => 100,
            'total' => 330,
            'discount' => 0,
            'shipping_fee' => 15,
            'tax' => 15,
            'payment_status' => 'paid',
            'payment_method' => 'mada',
            'shipping_method' => 'aramex',
        ])->assertCreated()->assertJsonPath('store_id', 'store-atlas')->json();

        $this->assertDatabaseHas('platform_records', [
            'section' => 'payments',
            'record_id' => 'payment-' . $order['id'],
            'store_id' => 'store-atlas',
        ]);
        $this->assertDatabaseHas('platform_records', [
            'section' => 'invoices',
            'record_id' => 'invoice-' . $order['id'],
            'store_id' => 'store-atlas',
        ]);
        $this->assertDatabaseHas('platform_records', [
            'section' => 'wallet_transactions',
            'record_id' => 'wallet-order-' . $order['id'],
            'store_id' => 'store-atlas',
        ]);

        $product = PlatformRecord::query()->where('section', 'products')->where('record_id', 'phase13-product')->firstOrFail();
        $this->assertSame(7, $product->payload['stock']);

        $this->assertDatabaseHas('platform_records', [
            'section' => 'inventory_logs',
            'store_id' => 'store-atlas',
            'status' => 'order_created',
        ]);

        $this->getJson('/api/partner/analytics/summary')
            ->assertOk()
            ->assertJsonPath('store.id', 'store-atlas');

        $this->getJson('/api/partner/orders/rowaa-secret-order')->assertNotFound();
        $this->getJson('/api/partner/orders/' . $order['id'])->assertOk()->assertJsonPath('store_id', 'store-atlas');
    }

    public function test_return_refund_restores_inventory_and_updates_wallet_and_payment(): void
    {
        $this->loginAsAtlasOwner();

        PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => 'return-product',
            'store_id' => 'store-atlas',
            'partner_id' => 'atlas',
            'status' => 'published',
            'payload' => ['id' => 'return-product', 'name' => 'Return Product', 'sku' => 'RET', 'stock' => 4, 'store_id' => 'store-atlas'],
        ]);

        $order = $this->postJson('/api/partner/orders/manual', [
            'customer' => 'عميل مرتجع',
            'product_id' => 'return-product',
            'product_sku' => 'RET',
            'item_name' => 'Return Product',
            'qty' => 2,
            'unit_price' => 50,
            'total' => 100,
            'payment_status' => 'paid',
        ])->assertCreated()->json();

        PlatformRecord::query()->create([
            'section' => 'returns',
            'record_id' => 'return-phase13',
            'store_id' => 'store-atlas',
            'partner_id' => 'atlas',
            'status' => 'pending',
            'payload' => [
                'id' => 'return-phase13',
                'order_number' => $order['order_number'],
                'product_id' => 'return-product',
                'qty' => 2,
                'amount' => 100,
                'status' => 'pending',
                'store_id' => 'store-atlas',
            ],
        ]);

        $this->patchJson('/api/partner/returns/return-phase13/status', [
            'status' => 'approved_refund',
        ])->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $product = PlatformRecord::query()->where('section', 'products')->where('record_id', 'return-product')->firstOrFail();
        $this->assertSame(4, $product->payload['stock']);

        $this->assertDatabaseHas('platform_records', [
            'section' => 'refunds',
            'record_id' => 'refund-return-phase13',
            'store_id' => 'store-atlas',
        ]);
        $this->assertDatabaseHas('platform_records', [
            'section' => 'wallet_transactions',
            'record_id' => 'wallet-refund-return-phase13',
            'store_id' => 'store-atlas',
        ]);

        $payment = PlatformRecord::query()->where('section', 'payments')->where('record_id', 'payment-' . $order['id'])->firstOrFail();
        $this->assertSame('refunded', $payment->payload['refund_status']);
    }

    public function test_accountant_and_marketer_roles_are_enforced_by_routes_and_apis(): void
    {
        $this->createRoleUser('accountant@example.test', 'accountant', ['view-dashboard', 'view-payments', 'view-analytics']);
        $this->post('/partner/login', ['username' => 'accountant@example.test', 'password' => 'Password@123']);

        $this->get('/partner/payments')->assertOk();
        $this->get('/partner/marketing')->assertForbidden();
        $this->getJson('/api/partner/marketing/summary')->assertForbidden();

        $this->post('/partner/logout');

        $this->createRoleUser('marketer@example.test', 'marketer', ['view-dashboard', 'view-marketing', 'view-customers']);
        $this->post('/partner/login', ['username' => 'marketer@example.test', 'password' => 'Password@123']);

        $this->get('/partner/marketing')->assertOk();
        $this->get('/partner/payments')->assertForbidden();
        $this->getJson('/api/partner/analytics/finance')->assertForbidden();
    }

    public function test_admin_store_and_service_controls_reflect_in_partner_dashboard(): void
    {
        PartnerTenantStore::partners();

        $this->loginAsAdmin()->post('/admin/sections/stores/store-atlas/edit', [
            'name' => 'متجر أطلس',
            'brand_name' => 'Atlas Fashion',
            'owner' => 'سارة الحربي',
            'owner_email' => 'sara@atlas.sa',
            'owner_phone' => '+966500000001',
            'status' => 'suspended',
            'plan' => 'Starter',
            'segment' => 'Fashion',
            'domain' => 'atlas.solve.sa',
            'city' => 'Riyadh',
            'launch_date' => '2026-01-15',
            'team_size' => '12',
            'payment_gateway' => 'Mada',
            'shipping_partner' => 'Aramex',
            'inventory_source' => 'ERP',
            'monthly_target' => '450000',
            'expected_orders' => '2400',
            'sales' => '418200',
            'orders' => '2418',
            'created_at' => '15 Jan 2026',
            'onboarding_stage' => 'Suspended',
            'notes' => 'Suspended from admin final review test',
        ])->assertRedirect('/admin/stores');

        $store = PartnerStore::query()->where('store_id', 'store-atlas')->firstOrFail();
        $this->assertSame('suspended', $store->status);
        $this->assertSame('Starter', $store->plan);

        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);

        $this->getJson('/api/partner/store/status')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('status', 'suspended');

        $this->get('/partner/workspace/apps/ai')->assertRedirect('/partner/subscription');

        $this->getJson('/api/partner/services')->assertOk();
        PlatformRecord::query()
            ->where('section', 'partner_services')
            ->where('store_id', 'store-atlas')
            ->where('record_id', 'growth')
            ->update([
                'status' => 'موقوفة من الأدمن',
                'payload->status_key' => 'admin_paused',
                'payload->status' => 'موقوفة من الأدمن',
            ]);

        $this->patchJson('/api/partner/services/growth/status', ['status' => 'enabled'])->assertStatus(423);
    }

    private function loginAsAtlasOwner(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ])->assertRedirect('/partner/dashboard');
    }

    private function createRoleUser(string $username, string $role, array $abilities): PartnerUser
    {
        PartnerTenantStore::partners();
        $store = PartnerStore::query()->where('store_id', 'store-atlas')->firstOrFail();

        return PartnerUser::query()->create([
            'partner_store_id' => $store->id,
            'store_id' => $store->store_id,
            'name' => $role . ' user',
            'username' => $username,
            'email' => $username,
            'password_hash' => Hash::make('Password@123'),
            'role' => $role,
            'status' => 'active',
            'abilities' => $abilities,
        ]);
    }
}
