<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterprisePhaseFourModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_four_enterprise_pages_load(): void
    {
        $pages = [
            '/admin/integrations' => 'مركز التكاملات',
            '/admin/automation' => 'نظام Automation',
            '/admin/developer' => 'Webhooks و API Keys',
            '/admin/messages' => 'مركز الرسائل',
            '/admin/reviews' => 'المراجعات والتقييمات',
            '/admin/financials' => 'تقارير مالية متقدمة',
            '/admin/payouts' => 'نظام التسويات Payouts',
            '/admin/security-center' => 'مركز الأمان',
            '/admin/merchant-experience' => 'تجربة تجهيز المتجر',
            '/admin/operations' => 'الأداء والاستقرار',
        ];

        foreach ($pages as $path => $text) {
            $this->loginAsAdmin()->get($path)
                ->assertOk()
                ->assertSee($text);
        }
    }

    public function test_integrations_page_shows_connection_states(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/integrations');

        $response->assertOk();
        $response->assertSee('Moyasar');
        $response->assertSee('متصل');
        $response->assertSee('يحتاج إعداد');
        $response->assertSee('غير متصل');
    }

    public function test_automation_page_shows_rule_builder(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/automation');

        $response->assertOk();
        $response->assertSee('Rule Builder');
        $response->assertSee('انخفاض المخزون');
        $response->assertSee('Retry');
    }
}
