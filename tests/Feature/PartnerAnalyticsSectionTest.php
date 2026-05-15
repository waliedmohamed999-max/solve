<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerAnalyticsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_analytics_pages_are_real_store_scoped_reports(): void
    {
        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'rowaa-analytics-order',
            'store_id' => 'store-rowaa',
            'status' => 'paid',
            'payload' => [
                'order_number' => 'RO-ANALYTICS',
                'customer' => 'Other Store Customer',
                'total' => '999 SAR',
                'created_at' => now()->toDateString(),
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ])->assertRedirect('/partner/dashboard');

        $this->get('/partner/analytics')
            ->assertOk()
            ->assertSee('API JSON')
            ->assertSee('Store ID: store-atlas')
            ->assertSee('platform_records')
            ->assertDontSee('RO-ANALYTICS')
            ->assertDontSee('store-rowaa');

        foreach (['sales', 'inventory', 'customers', 'products', 'payments'] as $report) {
            $this->get('/partner/analytics/' . $report)
                ->assertOk()
                ->assertSee('Store ID: store-atlas')
                ->assertSee('تصدير CSV');
        }
    }

    public function test_partner_analytics_api_and_export_are_store_scoped(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $response = $this->getJson('/partner/api/analytics/sales')
            ->assertOk()
            ->assertJsonPath('store.id', 'store-atlas')
            ->assertJsonPath('meta.store_scoped', true);

        $this->assertCount(6, $response->json('cards'));

        $this->get('/partner/analytics/sales/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertDontSee('store-rowaa', false);
    }

    public function test_analytics_reports_respect_plan_and_role_permissions(): void
    {
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ]);

        $this->get('/partner/analytics/sales')->assertOk();
        $this->get('/partner/analytics/payments')->assertForbidden();
        $this->getJson('/partner/api/analytics/payments')->assertForbidden();

        $this->post('/partner/logout');
        $this->post('/partner/login', [
            'username' => 'merchant@rowaa.sa',
            'password' => 'RowaaMerchant@2026',
        ]);

        $this->get('/partner/analytics/operations')->assertRedirect('/partner/subscription');
    }
}
