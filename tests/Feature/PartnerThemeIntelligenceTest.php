<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerThemeIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_intelligence_page_and_api_use_current_store_data_only(): void
    {
        PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => 'rowaa-perfume',
            'store_id' => 'store-rowaa',
            'status' => 'published',
            'payload' => [
                'name' => 'Rowaa Hidden Perfume',
                'category' => 'عطور',
                'status_key' => 'published',
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/storefront/themes')
            ->assertOk()
            ->assertSee('AI Theme Intelligence')
            ->assertSee('قوالب تفهم نشاط متجرك')
            ->assertSee('Fashion Luxury')
            ->assertDontSee('Rowaa Hidden Perfume');

        $this->getJson('/api/partner/themes/intelligence')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure([
                'recommendation' => ['best_preset', 'confidence', 'reason'],
                'matching' => ['analyzed', 'best_theme', 'best_preset', 'layout', 'hero', 'sections'],
                'presets',
                'ranked_themes',
                'auto_styling' => ['extracted_palette', 'recommended_branding', 'css_variables', 'ui_suggestions'],
                'generated_banners',
                'banner_generation' => ['banners', 'publish_rules'],
                'dynamic_homepage',
                'conversion_engine' => ['watching', 'insights', 'experiments'],
                'analytics',
                'theme_analytics' => ['current', 'themes', 'watchlist', 'sales_impact_summary'],
                'marketplace_ranking' => ['basis', 'top'],
            ])
            ->assertJsonMissing(['name' => 'Rowaa Hidden Perfume']);

        $this->getJson('/api/partner/themes/auto-style')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure([
                'extracted_palette' => ['primary', 'secondary', 'accent'],
                'recommended_branding',
                'css_variables',
                'ui_suggestions',
                'product_signals',
            ]);

        $this->getJson('/api/partner/themes/analytics')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['current', 'themes', 'watchlist', 'sales_impact_summary']);

        $this->getJson('/api/partner/themes/ranking')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['basis', 'most_used', 'highest_conversion', 'fastest', 'recommended']);
    }

    public function test_ai_theme_generator_and_preset_apply_are_logged_and_update_active_theme(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->postJson('/api/partner/themes/generate', [
            'prompt' => 'أريد متجر عطور فاخر بواجهة داكنة',
        ])
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('preset_key', 'arabian-perfume')
            ->assertJsonPath('hero.cta', 'تسوّق المجموعة الآن');

        $this->postJson('/api/partner/themes/apply-preset', [
            'preset_key' => 'tech-store',
        ])
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('applied_preset.key', 'tech-store')
            ->assertJsonPath('theme.primary_color', '#020617');

        $this->postJson('/api/partner/themes/generate-banners', [
            'season' => 'White Friday',
        ])
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure([
                'banners' => [
                    ['title', 'subtitle', 'cta', 'placement', 'colors', 'layout'],
                ],
                'publish_rules',
            ]);

        $this->assertDatabaseHas('platform_records', [
            'section' => 'storefront_theme_intelligence',
            'store_id' => 'store-atlas',
            'status' => 'generated_theme',
        ]);

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'storefront_theme_intelligence_preset_applied',
        ]);
    }
}
