<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceGrowthPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_five_growth_pages_load(): void
    {
        $pages = [
            '/admin/marketing-campaigns' => 'الحملات التسويقية',
            '/admin/loyalty' => 'الولاء والنقاط',
            '/admin/abandoned-carts' => 'السلات المتروكة',
            '/admin/smart-recommendations' => 'توصيات ذكية',
            '/admin/store-content' => 'إدارة محتوى المتجر',
            '/admin/commissions' => 'نظام العمولات',
            '/admin/store-health' => 'مركز صحة المتجر',
            '/admin/moderation' => 'البلاغات والمخالفات',
            '/admin/workspace-tools' => 'أدوات تجربة العمل',
            '/admin/production-readiness' => 'جاهزية الإنتاج',
        ];

        foreach ($pages as $path => $text) {
            $this->loginAsAdmin()->get($path)
                ->assertOk()
                ->assertSee($text);
        }
    }

    public function test_marketing_campaigns_include_scheduling_and_performance(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/marketing-campaigns');

        $response->assertOk();
        $response->assertSee('Campaign Builder');
        $response->assertSee('الجدولة');
        $response->assertSee('إيراد من الحملات');
    }

    public function test_loyalty_page_contains_customer_levels_and_points(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/loyalty');

        $response->assertOk();
        $response->assertSee('VIP');
        $response->assertSee('فضي');
        $response->assertSee('سجل النقاط');
    }

    public function test_production_readiness_page_contains_launch_controls(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/production-readiness');

        $response->assertOk();
        $response->assertSee('Environment Config');
        $response->assertSee('Backup Strategy');
        $response->assertSee('Database Indexing');
        $response->assertSee('Monitoring');
    }
}
