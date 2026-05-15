<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerDashboardPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_api_is_backed_by_store_scoped_database_records(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ])->assertRedirect('/partner/dashboard');

        $this->get('/partner/dashboard')
            ->assertOk()
            ->assertSee('طلبات اليوم')
            ->assertSee('مبيعات اليوم')
            ->assertSee('الزوار')
            ->assertSee('إجمالي المنتجات')
            ->assertSee('إجمالي العملاء')
            ->assertSee('الطلبات المعلقة')
            ->assertSee('منخفض المخزون')
            ->assertSee('إجراءات سريعة')
            ->assertSee('خطوات تجهيز المتجر')
            ->assertSee('منتجات منخفضة المخزون')
            ->assertSee('آخر الطلبات')
            ->assertSee('حالة الاشتراك')
            ->assertSee('store-atlas')
            ->assertDontSee('store-rowaa');

        $this->assertDatabaseHas('platform_records', [
            'section' => 'orders',
            'store_id' => 'store-atlas',
        ]);

        $this->assertDatabaseHas('store_settings', [
            'store_id' => 'store-atlas',
        ]);

        $this->assertDatabaseHas('store_onboarding_steps', [
            'store_id' => 'store-atlas',
            'step_key' => 'store-profile',
        ]);

        $response = $this->getJson('/partner/api/dashboard-summary')
            ->assertOk()
            ->assertJsonPath('store.id', 'store-atlas')
            ->assertJsonPath('meta.store_scoped', true);

        $this->assertCount(9, $response->json('kpis'));
        $this->assertTrue(collect($response->json('kpis'))->contains('key', 'new_customers'));
        $this->assertTrue(collect($response->json('kpis'))->contains('key', 'awaiting_shipping'));
        $this->assertNotEmpty($response->json('charts.orders'));
        $this->assertNotEmpty($response->json('activities'));
    }

    public function test_official_dashboard_phase_one_apis_export_and_admin_view_are_available(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ])->assertRedirect('/partner/dashboard');

        $this->getJson('/api/partner/dashboard/summary?period=7')
            ->assertOk()
            ->assertJsonPath('store.id', 'store-atlas')
            ->assertJsonPath('period.days', 7)
            ->assertJsonPath('meta.store_scoped', true);

        $this->getJson('/api/partner/dashboard/charts?period=30')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('period.days', 30)
            ->assertJsonStructure(['charts' => ['orders', 'sales']]);

        $this->getJson('/api/partner/dashboard/latest-orders')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['latestOrders']);

        $this->getJson('/api/partner/dashboard/activities')
            ->assertOk()
            ->assertJsonPath('summary.store_id', 'store-atlas');

        $this->getJson('/api/partner/dashboard/alerts')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['alerts']);

        $this->getJson('/api/partner/store/status')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['subscription', 'setupProgress', 'generated_at']);

        $this->get('/partner/dashboard/export?period=7')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('store-atlas');

        $this->loginAsAdmin()
            ->get('/admin/partners/atlas')
            ->assertOk()
            ->assertSee('store-atlas')
            ->assertSee('طلبات اليوم');
    }

    public function test_dashboard_summary_only_counts_current_store_records(): void
    {
        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'rowaa-only-order',
            'store_id' => 'store-rowaa',
            'status' => 'قيد المعالجة',
            'payload' => [
                'order_number' => 'RO-ONLY',
                'customer' => 'عميل آخر',
                'total' => '999 SAR',
                'created_at' => now()->toDateString(),
                'store_id' => 'store-rowaa',
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $response = $this->getJson('/partner/api/dashboard-summary')->assertOk();

        $latestOrders = collect($response->json('latestOrders'));

        $this->assertFalse($latestOrders->contains(fn (array $order) => ($order['store_id'] ?? null) === 'store-rowaa'));
        $this->assertFalse($latestOrders->contains(fn (array $order) => ($order['order_number'] ?? null) === 'RO-ONLY'));
    }

    public function test_dashboard_activities_and_notifications_are_store_scoped_pages_and_apis(): void
    {
        PlatformActivityLog::query()->create([
            'actor_type' => 'admin',
            'actor_name' => 'Super Admin',
            'role' => 'super_admin',
            'store_id' => 'store-rowaa',
            'action' => 'rowaa_only_action',
            'subject_type' => 'orders',
            'subject_id' => 'RO-1',
        ]);

        PlatformNotification::query()->create([
            'type' => 'partner_dashboard',
            'title' => 'تنبيه متجر آخر',
            'body' => 'لا يجب أن يظهر لأطلس',
            'store_id' => 'store-rowaa',
            'severity' => 'danger',
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/activities')
            ->assertOk()
            ->assertSee('آخر النشاطات')
            ->assertSee('store-atlas')
            ->assertDontSee('rowaa_only_action');

        $this->getJson('/partner/api/activities')
            ->assertOk()
            ->assertJsonPath('summary.store_id', 'store-atlas');

        $this->get('/partner/notifications')
            ->assertOk()
            ->assertSee('الإشعارات')
            ->assertSee('store-atlas')
            ->assertDontSee('تنبيه متجر آخر');

        $this->getJson('/partner/api/notifications')
            ->assertOk()
            ->assertJsonPath('summary.store_id', 'store-atlas');
    }
}
