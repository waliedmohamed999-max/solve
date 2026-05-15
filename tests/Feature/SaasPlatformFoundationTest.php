<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasPlatformFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sections_are_backed_by_platform_records(): void
    {
        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('ORD-1001');

        $this->assertDatabaseHas('platform_records', [
            'section' => 'orders',
            'record_id' => 'order-1001',
        ]);
    }

    public function test_activity_and_notifications_are_created_for_admin_changes(): void
    {
        $this->withSession(['admin_authenticated' => true])
            ->post('/admin/sections/orders', [
                'order_number' => 'ORD-9999',
                'store' => 'متجر أطلس',
                'customer' => 'عميل اختبار',
                'status' => 'جديد',
                'total' => '99 SAR',
                'payment_status' => 'مدفوع',
                'shipping_status' => 'بانتظار التجهيز',
                'created_at' => '2026-05-12',
            ])
            ->assertRedirect();

        $this->assertGreaterThan(0, PlatformRecord::query()->where('section', 'orders')->count());
        $this->assertGreaterThan(0, PlatformActivityLog::query()->where('action', 'created')->count());
        $this->assertGreaterThan(0, PlatformNotification::query()->where('type', 'new_order')->count());
    }

    public function test_new_platform_pages_load(): void
    {
        foreach (['notifications', 'activity', 'onboarding', 'reports', 'marketplace'] as $page) {
            $this->withSession(['admin_authenticated' => true])
                ->get("/admin/{$page}")
                ->assertOk();
        }

        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/stores/store-atlas/settings')
            ->assertOk()
            ->assertSee('store-atlas');
    }

    public function test_partner_staff_page_is_store_scoped_and_restricted(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/staff')
            ->assertOk()
            ->assertSee('store-atlas')
            ->assertDontSee('store-rowaa');

        $this->post('/partner/logout');
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ]);

        $this->get('/partner/staff')->assertForbidden();
    }
}
