<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerStorefrontPhaseSixTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_pages_are_real_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'storefront_pages',
            'record_id' => 'rowaa-hidden-page',
            'store_id' => 'store-rowaa',
            'status' => 'published',
            'payload' => ['title' => 'Rowaa Hidden Page', 'slug' => 'rowaa-hidden', 'status' => 'منشورة', 'status_key' => 'published'],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/storefront')->assertOk()->assertSee('store-atlas')->assertDontSee('Rowaa Hidden Page');
        $this->get('/partner/storefront/themes')->assertOk()->assertSee('Solve Minimal');
        $this->get('/partner/storefront/customize')
            ->assertOk()
            ->assertSee('partner/storefront/customize')
            ->assertSee('navigator.clipboard')
            ->assertSee('target="_blank"', false)
            ->assertSee('تعديل واجهة المتجر');
        $this->get('/partner/settings/storefront')
            ->assertRedirect(route('partner.storefront.customize'));
        $this->get('/partner/storefront/pages')->assertOk()->assertDontSee('Rowaa Hidden Page');
        $this->get('/partner/storefront/banners')->assertOk();
        $this->get('/partner/storefront/navigation')->assertOk();
        $this->get('/partner/storefront/domain')->assertOk();
        $this->get('/partner/storefront/seo')->assertOk();
        $this->get('/partner/storefront/settings')->assertOk();

        $this->getJson('/api/partner/storefront/summary')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $this->getJson('/api/partner/pages/rowaa-hidden-page')->assertNotFound();
    }

    public function test_themes_domain_seo_and_settings_apis_update_store_records(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $theme = $this->getJson('/api/partner/themes')->assertOk()->json('rows.1');

        $this->patchJson('/api/partner/themes/' . $theme['id'] . '/activate')
            ->assertOk()
            ->assertJsonPath('active', true);

        $this->patchJson('/api/partner/themes/' . $theme['id'] . '/customize', [
            'primary_color' => '#111827',
            'secondary_color' => '#22c55e',
            'font' => 'Tajawal',
            'header_style' => 'mega',
            'footer_style' => 'columns',
            'card_style' => 'compact',
            'button_style' => 'pill',
            'supports_dark' => true,
        ])->assertOk()->assertJsonPath('primary_color', '#111827');

        $this->getJson('/api/partner/themes/' . $theme['id'] . '/settings')
            ->assertOk()
            ->assertJsonPath('settings.header', 'mega');

        $this->postJson('/api/partner/domain/connect', ['custom_domain' => 'atlas.example.com'])
            ->assertCreated()
            ->assertJsonPath('custom_domain', 'atlas.example.com')
            ->assertJsonPath('dns_status_key', 'pending');

        $this->postJson('/api/partner/domain/verify')
            ->assertOk()
            ->assertJsonPath('dns_status_key', 'verified')
            ->assertJsonPath('ssl_status_key', 'active');

        $this->patchJson('/api/partner/domain/status', ['active' => false])
            ->assertOk()
            ->assertJsonPath('active', false);

        $this->patchJson('/api/partner/seo', [
            'meta_title' => 'Atlas SEO',
            'meta_description' => 'Atlas store description',
            'social_image' => 'social.png',
            'sitemap_enabled' => true,
            'robots_txt' => "User-agent: *\nAllow: /",
            'open_graph_enabled' => true,
            'speed_score' => 97,
            'index_status' => 'indexed',
        ])->assertOk()->assertJsonPath('meta_title', 'Atlas SEO');

        $this->patchJson('/api/partner/store-settings', [
            'store_name' => 'Atlas Public Store',
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
            'contact_email' => 'store@atlas.sa',
            'contact_phone' => '+966500000001',
            'working_hours' => '9-10',
            'social_links' => ['https://instagram.com/atlas'],
            'language' => 'ar',
            'currency' => 'SAR',
        ])->assertOk()->assertJsonPath('store_name', 'Atlas Public Store');

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'storefront_theme_activated',
        ]);
    }

    public function test_staff_partner_can_manage_and_edit_storefront(): void
    {
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ]);

        $this->get('/partner/storefront/customize')
            ->assertOk()
            ->assertSee('تعديل واجهة المتجر');
        $this->get('/partner/storefront/themes')
            ->assertOk()
            ->assertSee('Solve Minimal');
        $this->get('/partner/storefront/settings')
            ->assertOk()
            ->assertSee('إعدادات المتجر');

        $theme = $this->getJson('/api/partner/themes')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->json('rows.0');

        $this->getJson('/api/partner/themes/' . $theme['id'] . '/settings')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $this->getJson('/api/partner/store-settings')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $this->patchJson('/api/partner/themes/' . $theme['id'] . '/customize', [
            'primary_color' => '#000000',
        ])->assertOk()->assertJsonPath('primary_color', '#000000');

        $this->post('/partner/storefront/settings', [
            'store_name' => 'Staff Managed Store',
            'logo' => 'solve-logo.png',
            'favicon' => 'solve-logo.png',
            'contact_email' => 'staff-store@atlas.sa',
            'contact_phone' => '+966500000001',
            'working_hours' => '9-10',
            'language' => 'ar',
            'currency' => 'SAR',
        ])->assertRedirect();

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'storefront_settings_updated',
        ]);
    }

    public function test_pages_banners_navigation_crud_and_reorder_work(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $page = $this->postJson('/api/partner/pages', [
            'title' => 'Landing Page',
            'slug' => 'landing',
            'content' => 'Content',
            'seo_title' => 'Landing SEO',
            'seo_description' => 'Description',
            'preview_url' => 'https://atlas.solve.sa/landing',
            'status' => 'draft',
        ])->assertCreated()->assertJsonPath('store_id', 'store-atlas')->json();

        $this->patchJson('/api/partner/pages/' . $page['id'], [
            'title' => 'Landing Published',
            'slug' => 'landing',
            'content' => 'Content updated',
            'seo_title' => 'Landing SEO',
            'seo_description' => 'Description',
            'preview_url' => 'https://atlas.solve.sa/landing',
            'status' => 'published',
        ])->assertOk()->assertJsonPath('status_key', 'published');

        $banner = $this->postJson('/api/partner/banners', [
            'title' => 'Hero API',
            'image_url' => 'hero.png',
            'link_type' => 'url',
            'link_target' => '/offers',
            'placement' => 'home_hero',
            'sort_order' => 3,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('store_id', 'store-atlas')->json();

        $this->patchJson('/api/partner/banners/' . $banner['id'], [
            'title' => 'Hero API Updated',
            'image_url' => 'hero-2.png',
            'link_type' => 'category',
            'link_target' => 'featured',
            'placement' => 'home_secondary',
            'sort_order' => 1,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'status' => 'scheduled',
        ])->assertOk()->assertJsonPath('status_key', 'scheduled');

        $this->patchJson('/api/partner/banners/reorder', ['order' => [$banner['id']]])
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $this->patchJson('/api/partner/navigation', [
            'header_menu' => [
                ['label' => 'Home', 'url' => '/', 'visible' => true, 'children' => []],
            ],
            'footer_menu' => "Contact|/contact\nTerms|/terms",
        ])->assertOk()->assertJsonPath('header_menu.0.label', 'Home');

        $this->deleteJson('/api/partner/pages/' . $page['id'])->assertOk()->assertJsonPath('deleted', true);
        $this->deleteJson('/api/partner/banners/' . $banner['id'])->assertOk()->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('platform_records', ['record_id' => $page['id'], 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'storefront_navigation_updated',
        ]);
    }

    public function test_visual_builder_sections_are_real_store_scoped_and_publishable(): void
    {
        PlatformRecord::query()->create([
            'section' => 'storefront_sections',
            'record_id' => 'rowaa-hidden-builder-section',
            'store_id' => 'store-rowaa',
            'status' => 'active',
            'payload' => [
                'type' => 'hero',
                'title' => 'Rowaa Hidden Builder Section',
                'placement' => 'home',
                'sort_order' => 1,
                'visible' => true,
                'status_key' => 'active',
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/storefront/builder')
            ->assertOk()
            ->assertSee('partner/storefront/customize')
            ->assertSee('builder-layout-form')
            ->assertSee('builder-publish-form')
            ->assertSee('builder-component-form')
            ->assertSee('builder-section-edit-form')
            ->assertSee('builder-section-delete-button')
            ->assertSee('storefrontLivePreview')
            ->assertSee('/store/atlas', false)
            ->assertSee('data-api-url', false)
            ->assertSee('إضافة Section جديد')
            ->assertDontSee('Rowaa Hidden Builder Section');

        $builderPayload = $this->getJson('/api/partner/storefront/builder')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['sections'])
            ->json();

        $this->post('/partner/storefront/sections', [
            'type' => 'rich_text',
            'title' => 'Builder Connected Text',
            'placement' => 'home',
            'sort_order' => 12,
            'visible' => true,
            'status' => 'active',
            'settings' => [
                'headline' => 'Builder Component Connected',
                'body' => 'This section was created from the visual components panel.',
            ],
        ])->assertRedirect();

        $this->get('/store/atlas')
            ->assertOk()
            ->assertSee('Builder Component Connected')
            ->assertSee('builder-dynamic-section');

        $this->post('/partner/storefront/sections', [
            'type' => 'featured_products',
            'title' => 'Builder Product Row',
            'placement' => 'home',
            'sort_order' => 13,
            'visible' => true,
            'status' => 'active',
            'settings' => [
                'source' => 'featured',
                'limit' => '4',
            ],
        ])->assertRedirect();

        $this->get('/store/atlas')
            ->assertOk()
            ->assertSee('Builder Product Row')
            ->assertSee('قسم منتجات تم إنشاؤه من محرر الواجهة');

        $sectionOrder = collect($builderPayload['sections'])
            ->pluck('id')
            ->filter()
            ->take(3)
            ->values()
            ->all();

        $this->assertCount(3, $sectionOrder);

        $newOrder = [$sectionOrder[1], $sectionOrder[0], $sectionOrder[2]];

        $this->patchJson('/api/partner/storefront/sections/reorder', ['order' => $newOrder])
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('rows.0.id', $sectionOrder[1])
            ->assertJsonPath('rows.1.id', $sectionOrder[0]);

        $featuredSection = collect($builderPayload['sections'])
            ->firstWhere('type', 'featured_products');

        $this->assertNotNull($featuredSection);

        $this->patchJson('/api/partner/storefront/sections/' . $featuredSection['id'], [
            'type' => 'featured_products',
            'title' => $featuredSection['title'],
            'placement' => $featuredSection['placement'],
            'sort_order' => $featuredSection['sort_order'],
            'visible' => false,
            'status' => 'hidden',
            'settings' => $featuredSection['settings'] ?? [],
        ])
            ->assertOk()
            ->assertJsonPath('visible', false);

        $this->get('/store/atlas')
            ->assertOk()
            ->assertDontSee('<h2>منتجات مميزة</h2>', false)
            ->assertSee('Builder Product Row');

        $this->patchJson('/api/partner/storefront/sections/' . $featuredSection['id'], [
            'type' => 'featured_products',
            'title' => $featuredSection['title'],
            'placement' => $featuredSection['placement'],
            'sort_order' => $featuredSection['sort_order'],
            'visible' => true,
            'status' => 'active',
            'settings' => $featuredSection['settings'] ?? [],
        ])
            ->assertOk()
            ->assertJsonPath('visible', true);

        $this->patchJson('/api/partner/storefront/builder', [
            'page' => 'home',
            'device' => 'mobile',
            'mode' => 'visual',
            'settings' => ['selected_section' => 'hero', 'zoom' => 90],
            'draft' => ['layout' => 'commerce-builder'],
        ])
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('device', 'mobile')
            ->assertJsonPath('status_key', 'draft');

        $section = $this->postJson('/api/partner/storefront/sections', [
            'type' => 'countdown',
            'title' => 'Flash Countdown',
            'placement' => 'home',
            'sort_order' => 10,
            'visible' => true,
            'status' => 'active',
            'settings' => ['headline' => 'Ends tonight', 'source' => 'campaigns'],
        ])
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('type', 'countdown')
            ->json();

        $this->patchJson('/api/partner/storefront/sections/' . $section['id'], [
            'type' => 'countdown',
            'title' => 'Flash Countdown Hidden',
            'placement' => 'home',
            'sort_order' => 11,
            'visible' => false,
            'status' => 'hidden',
            'settings' => ['headline' => 'Paused'],
        ])
            ->assertOk()
            ->assertJsonPath('visible', false)
            ->assertJsonPath('status_key', 'hidden');

        $this->postJson('/api/partner/storefront/builder/publish')
            ->assertOk()
            ->assertJsonPath('status_key', 'published')
            ->assertJsonStructure(['published_snapshot' => ['settings', 'draft', 'sections']]);

        $this->postJson('/api/partner/storefront/builder/rollback')
            ->assertOk()
            ->assertJsonPath('status_key', 'draft');

        $this->deleteJson('/api/partner/storefront/sections/' . $section['id'])
            ->assertOk()
            ->assertJsonPath('deleted', true)
            ->assertJsonPath('store_id', 'store-atlas');

        $this->assertDatabaseMissing('platform_records', [
            'record_id' => $section['id'],
            'store_id' => 'store-atlas',
        ]);
        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'storefront_builder_published',
        ]);
        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'storefront_sections_reordered',
        ]);
    }
}
