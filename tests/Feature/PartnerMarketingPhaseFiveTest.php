<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerMarketingPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_pages_and_summary_are_real_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'marketing_coupons',
            'record_id' => 'rowaa-hidden-coupon',
            'store_id' => 'store-rowaa',
            'status' => 'نشط',
            'payload' => ['name' => 'كوبون رواء مخفي', 'code' => 'RO-HIDDEN', 'status' => 'نشط', 'status_key' => 'active'],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/marketing')->assertOk()->assertSee('ملخص التسويق')->assertSee('store-atlas')->assertDontSee('RO-HIDDEN');
        $this->get('/partner/marketing/coupons')->assertOk()->assertSee('الكوبونات والخصومات')->assertDontSee('RO-HIDDEN');
        $this->get('/partner/marketing/campaigns')->assertOk()->assertSee('الحملات التسويقية');
        $this->get('/partner/marketing/bundles')->assertOk()->assertSee('الحزم التسويقية');
        $this->get('/partner/marketing/loyalty')->assertOk()->assertSee('برنامج الولاء');
        $this->get('/partner/marketing/affiliate')->assertOk()->assertSee('التسويق بالعمولة');
        $this->get('/partner/marketing/abandoned-carts')->assertOk()->assertSee('السلات المتروكة');
        $this->get('/partner/marketing/ads')->assertOk()->assertSee('الإعلانات والتتبع');

        $summary = $this->getJson('/api/partner/marketing/summary')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->json();

        $this->assertNotEmpty($summary['kpis']);
        $this->assertDatabaseHas('platform_records', ['section' => 'marketing_coupons', 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_records', ['section' => 'marketing_campaigns', 'store_id' => 'store-atlas']);
    }

    public function test_coupons_campaigns_bundles_affiliate_and_ads_apis_work(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $coupon = $this->postJson('/api/partner/coupons', [
            'name' => 'كوبون API',
            'code' => 'API10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order' => 100,
            'usage_limit' => 50,
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('code', 'API10')
            ->json();

        $this->patchJson('/api/partner/coupons/' . $coupon['id'], [
            'name' => 'كوبون API محدث',
            'code' => 'API15',
            'discount_type' => 'fixed',
            'discount_value' => 15,
            'minimum_order' => 120,
            'usage_limit' => 40,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('code', 'API15');

        $this->patchJson('/api/partner/coupons/' . $coupon['id'] . '/status', ['status' => 'paused'])
            ->assertOk()
            ->assertJsonPath('status_key', 'paused');
        $this->getJson('/api/partner/coupons/' . $coupon['id'] . '/usage')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $campaign = $this->postJson('/api/partner/campaigns', [
            'name' => 'حملة API',
            'type' => 'whatsapp',
            'target_audience' => 'عملاء VIP',
            'coupon_code' => 'API15',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i'),
            'visits' => 100,
            'orders' => 5,
            'sales' => 1500,
            'status' => 'scheduled',
        ])->assertCreated()->assertJsonPath('store_id', 'store-atlas')->json();
        $this->getJson('/api/partner/campaigns/' . $campaign['id'] . '/analytics')
            ->assertOk()
            ->assertJsonPath('metrics.orders', 5);

        $bundle = $this->postJson('/api/partner/bundles', [
            'name' => 'حزمة API',
            'products' => 'AT-100, AT-220',
            'bundle_price' => 399,
            'discount_value' => 20,
            'orders' => 2,
            'sales' => 798,
            'status' => 'active',
        ])->assertCreated()->json();
        $this->patchJson('/api/partner/bundles/' . $bundle['id'], [
            'name' => 'حزمة API محدثة',
            'products' => 'AT-100',
            'bundle_price' => 299,
            'discount_value' => 10,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('name', 'حزمة API محدثة');

        $affiliate = $this->postJson('/api/partner/affiliate/links', [
            'name' => 'رابط API',
            'marketer' => 'مسوق API',
            'url' => 'https://solve.test/a/api',
            'commission_rate' => 12,
            'status' => 'active',
        ])->assertCreated()->json();
        $this->patchJson('/api/partner/affiliate/' . $affiliate['id'] . '/status', ['status' => 'paused'])
            ->assertOk()
            ->assertJsonPath('status_key', 'paused');

        $ad = $this->getJson('/api/partner/ads/integrations')->assertOk()->json('rows.0');
        $this->patchJson('/api/partner/ads/integrations/' . $ad['id'], [
            'name' => 'Meta Pixel',
            'provider' => 'Meta Pixel',
            'pixel_id' => 'META-UPDATED',
            'conversions' => 10,
            'spend' => 100,
            'sales' => 500,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('pixel_id', 'META-UPDATED');
        $this->getJson('/api/partner/ads/reports')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->deleteJson('/api/partner/coupons/' . $coupon['id'])->assertOk()->assertJsonPath('deleted', true);
        $this->assertDatabaseMissing('platform_records', ['section' => 'marketing_coupons', 'record_id' => $coupon['id'], 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_activity_logs', ['store_id' => 'store-atlas', 'action' => 'marketing_campaigns_created']);
    }

    public function test_loyalty_and_abandoned_cart_recovery_are_functional(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->getJson('/api/partner/loyalty')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['settings', 'customers', 'transactions']);

        $this->patchJson('/api/partner/loyalty/settings', [
            'enabled' => true,
            'points_per_currency' => 2,
            'point_value' => 0.2,
        ])->assertOk()
            ->assertJsonPath('points_per_currency', 2);

        $cartId = PlatformRecord::query()->where('section', 'abandoned_carts')->where('store_id', 'store-atlas')->value('record_id');
        $this->postJson('/api/partner/abandoned-carts/' . $cartId . '/coupon')
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('discount_type', 'percentage');

        $this->postJson('/api/partner/abandoned-carts/' . $cartId . '/remind')
            ->assertOk();

        $this->assertFalse(PlatformActivityLog::query()->where('store_id', 'store-rowaa')->exists());
    }
}
