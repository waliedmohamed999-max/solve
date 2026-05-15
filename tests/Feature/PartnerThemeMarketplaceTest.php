<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerThemeMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_marketplace_page_lists_real_store_scoped_templates(): void
    {
        PlatformRecord::query()->create([
            'section' => 'storefront_themes',
            'record_id' => 'rowaa-secret-theme',
            'store_id' => 'store-rowaa',
            'status' => 'available',
            'payload' => [
                'name' => 'Rowaa Hidden Theme',
                'category' => 'Hidden',
                'status_key' => 'available',
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/storefront/themes')
            ->assertOk()
            ->assertSee('قوالب المتجر')
            ->assertSee('Urban Style')
            ->assertSee('Luxury Perfume')
            ->assertSee('Tech Store')
            ->assertSee('AI Theme Match')
            ->assertDontSee('Rowaa Hidden Theme');
    }

    public function test_theme_marketplace_apis_cover_categories_preview_install_favorite_and_publish(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->getJson('/api/partner/themes/categories')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonFragment(['label' => 'عطور'])
            ->assertJsonFragment(['label' => 'إلكترونيات']);

        $this->getJson('/api/partner/themes/theme-luxury-perfume')
            ->assertOk()
            ->assertJsonPath('id', 'theme-luxury-perfume')
            ->assertJsonPath('category', 'عطور');

        $this->postJson('/api/partner/themes/preview', [
            'theme_id' => 'theme-luxury-perfume',
            'device' => 'mobile',
        ])
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('device', 'mobile')
            ->assertJsonPath('theme.id', 'theme-luxury-perfume');

        $this->postJson('/api/partner/themes/install', [
            'theme_id' => 'theme-luxury-perfume',
        ])
            ->assertCreated()
            ->assertJsonPath('id', 'theme-luxury-perfume')
            ->assertJsonPath('installed', true);

        $this->postJson('/api/partner/themes/favorite', [
            'theme_id' => 'theme-luxury-perfume',
        ])
            ->assertOk()
            ->assertJsonPath('favorite', true);

        $this->postJson('/api/partner/themes/publish', [
            'theme_id' => 'theme-luxury-perfume',
        ])
            ->assertOk()
            ->assertJsonPath('active', true);

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'storefront_theme_published',
        ]);
    }

    public function test_public_marketplace_and_reviews_are_available(): void
    {
        $this->getJson('/api/themes/marketplace')
            ->assertOk()
            ->assertJsonPath('public', true)
            ->assertJsonFragment(['name' => 'Urban Style'])
            ->assertJsonFragment(['name' => 'Sports Pro']);

        $this->postJson('/api/themes/reviews', [
            'theme_id' => 'theme-urban-style',
            'rating' => 5,
            'review' => 'قالب سريع ومنظم.',
            'name' => 'Atlas',
        ])
            ->assertCreated()
            ->assertJsonPath('theme_id', 'theme-urban-style')
            ->assertJsonPath('status', 'pending');
    }
}
