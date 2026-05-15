<?php

namespace Tests\Feature;

use App\Support\PartnerWorkspace;
use Tests\TestCase;

class PartnerWorkspaceNavigationTest extends TestCase
{
    public function test_partner_workspace_page_and_api_are_store_scoped(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/workspace/products/stock')
            ->assertOk()
            ->assertSee('store-atlas')
            ->assertSee('API JSON')
            ->assertDontSee('store-rowaa');

        $this->getJson('/partner/api/pages/products/stock')
            ->assertOk()
            ->assertJsonPath('storeScope.store_id', 'store-atlas')
            ->assertJsonPath('title', 'المخزون');
    }

    public function test_starter_plan_cannot_open_enterprise_page(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@rowaa.sa',
            'password' => 'RowaaMerchant@2026',
        ]);

        $this->get('/partner/workspace/marketing/affiliate')->assertForbidden();
    }

    public function test_staff_cannot_open_finance_api(): void
    {
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ]);

        $this->getJson('/partner/api/pages/finance/payments')->assertForbidden();
    }

    public function test_partner_sidebar_contains_complete_main_sections_and_settings_items(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $navigation = $this->getJson('/partner/api/navigation')
            ->assertOk()
            ->json('sections');

        $sections = collect($navigation)->keyBy('key');

        foreach (['dashboard', 'orders', 'products', 'customers', 'marketing', 'analytics', 'finance', 'services', 'channels', 'apps', 'settings'] as $section) {
            $this->assertTrue($sections->has($section), 'Missing sidebar section: ' . $section);
        }

        $settingsItems = collect($sections['settings']['items'])->pluck('key')->all();

        foreach (['account', 'store', 'identity', 'domain', 'shipping', 'payments', 'checkout', 'taxes', 'bank-accounts', 'api', 'order-settings', 'zatca', 'maintenance', 'contacts', 'messages', 'review-messages', 'notifications', 'social', 'languages', 'storefront', 'categories-display', 'working-hours', 'staff', 'permissions', 'branches', 'legal', 'pos'] as $item) {
            $this->assertContains($item, $settingsItems, 'Missing settings sidebar item: ' . $item);
        }
    }

    public function test_all_visible_enterprise_sidebar_items_resolve_to_reachable_pages(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $navigation = $this->getJson('/partner/api/navigation')
            ->assertOk()
            ->json('sections');

        foreach ($navigation as $section) {
            foreach ($section['items'] as $item) {
                $url = $this->urlForSidebarItem($section['key'], $item);
                $response = $this->get($url);

                $this->assertNotSame(404, $response->getStatusCode(), "Sidebar item {$section['key']}.{$item['key']} resolves to 404 at {$url}");
                $this->assertNotSame(500, $response->getStatusCode(), "Sidebar item {$section['key']}.{$item['key']} resolves to 500 at {$url}");
            }
        }
    }

    public function test_generic_workspace_quick_actions_are_real_links(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/workspace/marketing/discounts')
            ->assertOk()
            ->assertSee('href="' . route('partner.api.page', ['section' => 'marketing', 'page' => 'discounts']) . '"', false)
            ->assertSee('href="' . route('partner.pages.show', ['section' => 'marketing', 'page' => 'campaigns']) . '"', false);

        $api = $this->getJson('/partner/api/pages/products/stock')
            ->assertOk()
            ->json();

        $this->assertNotEmpty($api['quickActions']);
        $this->assertArrayHasKey('label', $api['quickActions'][0]);
        $this->assertArrayHasKey('url', $api['quickActions'][0]);
    }

    private function urlForSidebarItem(string $sectionKey, array $item): string
    {
        $legacyRoute = $item['legacyRoute'] ?? null;

        if ($legacyRoute === 'partner.settings.section') {
            return route($legacyRoute, ['section' => $item['key']]);
        }

        if ($legacyRoute && app('router')->has($legacyRoute)) {
            return route($legacyRoute);
        }

        $definition = PartnerWorkspace::findPage($sectionKey, $item['key']);
        $this->assertNotNull($definition, "Missing sidebar definition {$sectionKey}.{$item['key']}");

        return route('partner.pages.show', ['section' => $sectionKey, 'page' => $item['key']]);
    }
}
