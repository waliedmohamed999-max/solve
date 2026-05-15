<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerExperiencePhaseFourteenTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_six_zid_style_kpis_command_palette_and_store_health(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ])->assertRedirect('/partner/dashboard');

        $response = $this->get('/partner/dashboard')
            ->assertOk()
            ->assertSee('data-testid="partner-command-palette"', false)
            ->assertSee('data-testid="dashboard-featured-kpis"', false)
            ->assertSee('صحة المتجر')
            ->assertSee('Command K')
            ->assertDontSee('store-rowaa');

        $this->assertSame(6, substr_count($response->getContent(), 'data-testid="dashboard-kpi-card"'));

        $summary = $this->getJson('/api/partner/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('store.id', 'store-atlas')
            ->assertJsonPath('meta.store_scoped', true)
            ->json();

        $this->assertCount(6, $summary['featuredKpis']);
        $this->assertLessThanOrEqual(2, count($summary['importantAlerts']));
        $this->assertArrayHasKey('storeHealth', $summary);
        $this->assertArrayHasKey('score', $summary['storeHealth']);
    }

    public function test_experience_respects_role_plan_and_forbidden_urls(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@rowaa.sa',
            'password' => 'RowaaMerchant@2026',
        ])->assertRedirect('/partner/dashboard');

        $navigation = $this->getJson('/partner/api/navigation')
            ->assertOk()
            ->json('sections');

        $sections = collect($navigation)->keyBy('key');
        $appItems = collect($sections['apps']['items'] ?? [])->pluck('key')->all();
        $channelItems = collect($sections['channels']['items'] ?? [])->pluck('key')->all();
        $this->assertNotContains('ai', $appItems);
        $this->assertNotContains('mobile-app', $channelItems);
        $this->assertNotContains('pos', $channelItems);

        $this->get('/partner/apps/ai')->assertRedirect('/partner/subscription');
        $this->get('/partner/workspace/apps/ai')->assertRedirect('/partner/subscription');
    }

    public function test_limited_staff_sees_clear_forbidden_response_without_data_leak(): void
    {
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ])->assertRedirect('/partner/dashboard');

        $this->get('/partner/payments')
            ->assertForbidden()
            ->assertSee('ليس لديك صلاحية');

        $this->getJson('/api/partner/orders/rowaa-secret-order')->assertNotFound();
    }
}
