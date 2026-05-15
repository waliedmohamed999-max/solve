<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerProductsPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_and_api_are_database_backed_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => 'rowaa-hidden-product',
            'store_id' => 'store-rowaa',
            'status' => 'منشور',
            'payload' => [
                'name' => 'منتج رواء المخفي',
                'sku' => 'RO-HIDDEN',
                'price' => '999 ر.س',
                'stock' => 9,
                'status' => 'منشور',
                'store_id' => 'store-rowaa',
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/products')
            ->assertOk()
            ->assertSee('المنتجات')
            ->assertSee('store-atlas')
            ->assertDontSee('RO-HIDDEN');

        $response = $this->getJson('/partner/api/products')
            ->assertOk()
            ->assertJsonPath('filters.type', 'all');

        $this->assertFalse(collect($response->json('products'))->contains(fn (array $product) => ($product['store_id'] ?? null) === 'store-rowaa'));
    }

    public function test_product_create_update_pause_and_delete_work(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->post('/partner/products', [
            'name' => 'منتج اختبار',
            'sku' => 'TEST-SKU-1',
            'type' => 'single',
            'status' => 'published',
            'price' => 125,
            'compare_at_price' => 150,
            'cost_price' => 80,
            'stock' => 20,
            'low_stock_threshold' => 5,
            'category' => 'اختبار',
            'brand' => 'Solve',
            'barcode' => '628000000001',
            'weight' => 0.5,
            'tags' => 'جديد, اختبار',
            'option_name' => 'اللون',
            'option_values' => 'أسود, أبيض',
            'seo_title' => 'منتج اختبار',
            'seo_description' => 'وصف SEO',
            'visibility' => 'visible',
            'track_inventory' => 1,
            'allow_backorders' => 0,
            'requires_shipping' => 1,
            'description' => 'وصف تجريبي',
        ])->assertRedirect();

        $record = PlatformRecord::query()->where('section', 'products')->where('store_id', 'store-atlas')->where('record_id', 'like', 'product-%')->latest()->first();
        $this->assertNotNull($record);
        $this->assertSame('Solve', $record->payload['brand']);
        $this->assertSame(['جديد', 'اختبار'], $record->payload['tags']);
        $this->assertSame(['أسود', 'أبيض'], $record->payload['option_values']);

        $this->get('/partner/products/' . $record->record_id . '/edit')
            ->assertOk()
            ->assertSee('منتج اختبار');

        $this->post('/partner/products/' . $record->record_id, [
            'name' => 'منتج اختبار محدث',
            'sku' => 'TEST-SKU-2',
            'type' => 'variable',
            'status' => 'published',
            'price' => 150,
            'stock' => 3,
            'low_stock_threshold' => 5,
            'category' => 'اختبار',
            'description' => 'وصف محدث',
        ])->assertRedirect();

        $this->get('/partner/products')->assertOk()->assertSee('منتج اختبار محدث')->assertSee('مخزون منخفض');

        $this->post('/partner/products/' . $record->record_id . '/pause')->assertRedirect();
        $this->get('/partner/products/' . $record->record_id . '/edit')->assertOk()->assertSee('متوقف');

        $this->post('/partner/products/' . $record->record_id . '/delete')->assertRedirect('/partner/products');
        $this->assertDatabaseMissing('platform_records', ['section' => 'products', 'record_id' => $record->record_id]);
    }

    public function test_product_subsections_are_real_store_scoped_records(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/products/categories')->assertOk()->assertSee('التصنيفات')->assertSee('store-atlas');
        $this->get('/partner/products/inventory')->assertOk()->assertSee('المخزون')->assertSee('store-atlas');
        $this->get('/partner/products/options')->assertOk()->assertSee('مكتبة الخيارات')->assertSee('store-atlas');
        $this->get('/partner/products/filters')->assertOk()->assertSee('معايير التصفية')->assertSee('store-atlas');
        $this->get('/partner/products/custom-fields')->assertOk()->assertSee('الحقول المخصصة')->assertSee('store-atlas');

        $this->assertDatabaseHas('platform_records', ['section' => 'product_categories', 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_records', ['section' => 'product_options', 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_records', ['section' => 'product_filters', 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_records', ['section' => 'product_custom_fields', 'store_id' => 'store-atlas']);
    }

    public function test_official_products_apis_inventory_related_resources_and_store_isolation_work(): void
    {
        PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => 'rowaa-api-hidden-product',
            'store_id' => 'store-rowaa',
            'status' => 'منشور',
            'payload' => [
                'name' => 'منتج API مخفي',
                'sku' => 'RO-API-HIDDEN',
                'price' => '999 ر.س',
                'stock' => 9,
                'status' => 'منشور',
                'store_id' => 'store-rowaa',
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $response = $this->getJson('/api/partner/products?per_page=2&stock=all&view=grid')
            ->assertOk()
            ->assertJsonPath('filters.view', 'grid')
            ->assertJsonPath('pagination.per_page', 2);

        $products = collect($response->json('products'));
        $this->assertTrue($products->isNotEmpty());
        $this->assertFalse($products->contains(fn (array $product) => ($product['store_id'] ?? null) === 'store-rowaa'));

        $productId = $products->first()['id'];

        $this->getJson('/api/partner/products/' . $productId)
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['variants', 'media']);

        $created = $this->postJson('/api/partner/products', [
            'name' => 'منتج API',
            'sku' => 'API-SKU-1',
            'type' => 'variable',
            'status' => 'published',
            'price' => 200,
            'compare_at_price' => 250,
            'cost_price' => 100,
            'stock' => 15,
            'low_stock_threshold' => 4,
            'category' => 'API',
            'barcode' => '628000000099',
            'tags' => 'api,test',
            'option_name' => 'اللون',
            'option_values' => 'أحمر, أزرق',
            'description' => 'منتج من API',
        ])->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('sku', 'API-SKU-1')
            ->assertJsonCount(2, 'variants')
            ->json();

        $this->patchJson('/api/partner/products/' . $created['id'], [
            'name' => 'منتج API محدث',
            'sku' => 'API-SKU-2',
            'type' => 'single',
            'status' => 'published',
            'price' => 220,
            'stock' => 7,
            'low_stock_threshold' => 3,
            'category' => 'API',
            'description' => 'محدث',
        ])->assertOk()
            ->assertJsonPath('sku', 'API-SKU-2');

        $this->postJson('/api/partner/products/' . $created['id'] . '/duplicate')
            ->assertCreated()
            ->assertJsonPath('status_key', 'draft');

        $this->patchJson('/api/partner/products/bulk', [
            'product_ids' => [$created['id']],
            'status' => 'paused',
            'category' => 'Bulk API',
        ])->assertOk()
            ->assertJsonPath('updated.0.status_key', 'paused');

        $this->post('/api/partner/products/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('store-atlas');

        $this->postJson('/api/partner/products/import', [
            'rows' => [
                ['name' => 'مستورد API', 'sku' => 'IMP-API-1', 'price' => 50, 'stock' => 6],
            ],
        ])->assertCreated()
            ->assertJsonPath('created.0.store_id', 'store-atlas');

        $category = $this->postJson('/api/partner/categories', [
            'name' => 'تصنيف API',
            'sort_order' => 1,
            'status' => 'نشط',
        ])->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->json();

        $this->patchJson('/api/partner/categories/' . $category['id'], ['name' => 'تصنيف API محدث', 'status' => 'نشط'])
            ->assertOk()
            ->assertJsonPath('name', 'تصنيف API محدث');

        $this->getJson('/api/partner/categories')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->getJson('/api/partner/inventory')->assertOk()->assertJsonPath('store_id', 'store-atlas');
        $this->patchJson('/api/partner/inventory/' . $created['id'], ['stock' => 22, 'reason' => 'جرد يدوي'])
            ->assertOk()
            ->assertJsonPath('stock', 22);
        $this->getJson('/api/partner/inventory/logs')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        foreach ([
            'product-filters' => ['name' => 'فلتر API', 'values' => 'أ,ب', 'category' => 'API'],
            'custom-fields' => ['name' => 'حقل API', 'type' => 'نص', 'category' => 'API'],
            'options' => ['name' => 'خيار API', 'values' => 'S,M,L'],
        ] as $endpoint => $payload) {
            $row = $this->postJson('/api/partner/' . $endpoint, $payload)
                ->assertCreated()
                ->assertJsonPath('store_id', 'store-atlas')
                ->json();

            $this->getJson('/api/partner/' . $endpoint)->assertOk()->assertJsonPath('store_id', 'store-atlas');
            $this->patchJson('/api/partner/' . $endpoint . '/' . $row['id'], $payload + ['status' => 'متوقف'])->assertOk();
            $this->deleteJson('/api/partner/' . $endpoint . '/' . $row['id'])->assertOk()->assertJsonPath('deleted', true);
        }

        $this->deleteJson('/api/partner/categories/' . $category['id'])->assertOk()->assertJsonPath('deleted', true);
        $this->deleteJson('/api/partner/products/' . $created['id'])->assertOk();

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'product_created',
            'subject_type' => 'products',
        ]);
        $this->assertDatabaseMissing('platform_activity_logs', [
            'store_id' => 'store-rowaa',
            'subject_id' => $created['id'],
        ]);
    }
}
