<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPublicSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_pages_and_apis_are_scoped_to_store_slug(): void
    {
        PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => 'rowaa-private-product',
            'store_id' => 'store-rowaa',
            'status' => 'published',
            'payload' => [
                'id' => 'rowaa-private-product',
                'store_id' => 'store-rowaa',
                'name' => 'Rowaa Private Product',
                'sku' => 'ROWAA-1',
                'price' => '999 ر.س',
                'stock' => 10,
                'status' => 'منشور',
                'status_key' => 'published',
            ],
        ]);

        $this->get('/store/atlas')
            ->assertOk()
            ->assertSee('متجر أطلس')
            ->assertDontSee('Rowaa Private Product');

        $this->get('/store/atlas/products')
            ->assertOk()
            ->assertDontSee('Rowaa Private Product');

        $this->getJson('/api/store/atlas')
            ->assertOk()
            ->assertJsonPath('store.preview_url', 'https://atlas.solve.sa');

        $products = $this->getJson('/api/store/atlas/products')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonMissing(['store_id' => 'store-rowaa'])
            ->json('products');

        $this->assertNotEmpty($products);
        $product = $products[0];

        $this->get('/store/atlas/product/' . $product['id'])
            ->assertOk()
            ->assertSee($product['name'])
            ->assertSee('دفع آمن')
            ->assertSee('شوهدت مؤخراً')
            ->assertSee('قد يناسبك أيضاً');

        $this->getJson('/api/store/atlas/products/' . $product['id'])
            ->assertOk()
            ->assertJsonPath('product.store_id', 'store-atlas');
    }

    public function test_cart_and_checkout_create_real_partner_order_and_update_inventory(): void
    {
        $products = $this->getJson('/api/store/atlas/products')->assertOk()->json('products');
        $product = collect($products)->first(fn (array $row) => (int) ($row['stock'] ?? 0) > 2);
        $this->assertNotEmpty($product);

        $stockBefore = (int) $product['stock'];

        $this->postJson('/api/store/atlas/cart', [
            'items' => [
                ['product_id' => $product['id'], 'qty' => 2],
            ],
            'coupon_code' => null,
        ])
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('items.0.product_id', $product['id']);

        $order = $this->postJson('/api/store/atlas/checkout', [
            'customer' => [
                'name' => 'عميل المتجر',
                'email' => 'customer@example.test',
                'phone' => '966500000000',
                'city' => 'Riyadh',
                'address' => 'Test address',
            ],
            'items' => [
                ['product_id' => $product['id'], 'qty' => 2],
            ],
            'payment_method' => 'cod',
            'shipping_method' => 'standard',
        ])
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('order.source_channel', 'storefront')
            ->json('order');

        $this->assertDatabaseHas('platform_records', [
            'section' => 'orders',
            'record_id' => $order['id'],
            'store_id' => 'store-atlas',
        ]);
        $this->assertDatabaseHas('platform_records', [
            'section' => 'invoices',
            'store_id' => 'store-atlas',
        ]);
        $this->assertDatabaseHas('platform_records', [
            'section' => 'abandoned_carts',
            'store_id' => 'store-atlas',
            'record_id' => 'storefront-' . $this->findLatestCartId(),
        ]);

        $record = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', 'store-atlas')
            ->where('record_id', $product['id'])
            ->firstOrFail();

        $this->assertSame($stockBefore - 2, (int) ($record->payload['stock'] ?? 0));
    }

    public function test_conversion_events_and_partner_report_are_store_scoped(): void
    {
        $products = $this->getJson('/api/store/atlas/products')->assertOk()->json('products');
        $product = $products[0];

        $this->postJson('/api/store/atlas/events', [
            'event' => 'page_view',
            'path' => '/store/atlas',
        ])->assertOk()->assertJsonPath('tracked', true);

        $this->postJson('/api/store/atlas/events', [
            'event' => 'product_view',
            'product_id' => $product['id'],
            'path' => '/store/atlas/product/' . $product['id'],
        ])->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->postJson('/api/store/rowaa/events', [
            'event' => 'page_view',
            'path' => '/store/rowaa',
        ])->assertOk()->assertJsonPath('store_id', 'store-rowaa');

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->getJson('/api/partner/storefront/conversion')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('cards.0.value', 1)
            ->assertJsonMissing(['store_id' => 'store-rowaa']);
    }

    public function test_partner_storefront_alias_apis_work_with_existing_dashboard_data(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->patchJson('/api/partner/themes/customize', [
            'primary_color' => '#123456',
            'secondary_color' => '#22c55e',
            'font' => 'Tajawal',
        ])->assertOk()->assertJsonPath('primary_color', '#123456');

        $page = $this->postJson('/api/partner/storefront/pages', [
            'title' => 'Privacy',
            'slug' => 'privacy',
            'content' => 'Privacy content',
            'seo_title' => 'Privacy',
            'seo_description' => 'Privacy page',
            'status' => 'published',
        ])->assertCreated()->assertJsonPath('store_id', 'store-atlas')->json();

        $this->patchJson('/api/partner/storefront/pages/' . $page['id'], [
            'title' => 'Privacy Updated',
            'slug' => 'privacy',
            'content' => 'Updated',
            'seo_title' => 'Privacy',
            'seo_description' => 'Privacy page',
            'status' => 'published',
        ])->assertOk()->assertJsonPath('title', 'Privacy Updated');

        $this->postJson('/api/partner/storefront/banners', [
            'title' => 'Public Hero',
            'image_url' => 'services/banner-storefront.svg',
            'link_type' => 'url',
            'link_target' => '/products',
            'placement' => 'home_hero',
            'sort_order' => 1,
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('store_id', 'store-atlas');

        $this->patchJson('/api/partner/storefront/seo', [
            'meta_title' => 'Atlas Storefront SEO',
            'meta_description' => 'SEO for public storefront',
            'sitemap_enabled' => true,
            'open_graph_enabled' => true,
        ])->assertOk()->assertJsonPath('meta_title', 'Atlas Storefront SEO');

        $this->get('/store/atlas/pages/privacy')
            ->assertOk()
            ->assertSee('Privacy Updated');
    }

    public function test_newsletter_contact_sitemap_and_robots_are_functional(): void
    {
        $this->postJson('/api/store/atlas/newsletter', [
            'email' => 'buyer@example.test',
            'name' => 'Buyer',
        ])
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('subscriber.email', 'buyer@example.test');

        $this->postJson('/api/store/atlas/contact', [
            'name' => 'سارة',
            'contact' => 'sara@example.test',
            'message' => 'أريد الاستفسار عن منتج.',
        ])
            ->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('message_record.status', 'new');

        $this->assertDatabaseHas('platform_records', [
            'section' => 'storefront_newsletter_subscribers',
            'store_id' => 'store-atlas',
        ]);
        $this->assertDatabaseHas('platform_records', [
            'section' => 'storefront_contact_messages',
            'store_id' => 'store-atlas',
        ]);

        $this->get('/store/atlas/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/store/atlas/products', false)
            ->assertSee('/store/atlas/product/', false);

        $this->get('/store/atlas/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap:', false)
            ->assertSee('/store/atlas/sitemap.xml', false);
    }

    private function findLatestCartId(): string
    {
        $cart = PlatformRecord::query()
            ->where('section', 'storefront_carts')
            ->where('store_id', 'store-atlas')
            ->latest()
            ->first();

        return $cart?->record_id ?? '';
    }
}
