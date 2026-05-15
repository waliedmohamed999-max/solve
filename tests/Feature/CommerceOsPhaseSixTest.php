<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceOsPhaseSixTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_six_commerce_os_pages_load(): void
    {
        $pages = [
            '/admin/multi-vendor' => 'Multi Vendor Marketplace',
            '/admin/pos' => 'نظام POS',
            '/admin/mobile-apps' => 'تطبيقات الجوال',
            '/admin/smart-analytics' => 'Analytics ذكي',
            '/admin/ai-commerce' => 'AI Commerce',
            '/admin/workflow-engine' => 'Workflow Engine',
            '/admin/advanced-shipping' => 'إدارة الشحن المتقدم',
            '/admin/admin-experience' => 'تجربة الأدمن',
            '/admin/enterprise-security' => 'Enterprise Security',
            '/admin/technical-architecture' => 'البنية التقنية',
            '/admin/ux-polish' => 'تحسين تجربة المستخدم',
            '/admin/commercial-launch' => 'جاهزية الإطلاق التجاري',
        ];

        foreach ($pages as $path => $text) {
            $this->loginAsAdmin()->get($path)
                ->assertOk()
                ->assertSee($text);
        }
    }

    public function test_multi_vendor_page_contains_vendor_order_split_and_approval(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/multi-vendor');

        $response->assertOk();
        $response->assertSee('تقسيم الطلبات');
        $response->assertSee('بانتظار موافقة');
        $response->assertSee('vendor_id');
    }

    public function test_pos_page_contains_cashier_and_inventory_sync_controls(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/pos');

        $response->assertOk();
        $response->assertSee('Barcode Scan');
        $response->assertSee('جلسات POS');
        $response->assertSee('مزامنة المخزون');
    }

    public function test_ai_and_launch_pages_show_commerce_os_capabilities(): void
    {
        $this->loginAsAdmin()->get('/admin/ai-commerce')
            ->assertOk()
            ->assertSee('اقتراح أسعار ذكية')
            ->assertSee('Chat Assistant');

        $this->loginAsAdmin()->get('/admin/commercial-launch')
            ->assertOk()
            ->assertSee('Trial')
            ->assertSee('Billing')
            ->assertSee('Merchant Signup Flow');
    }
}
