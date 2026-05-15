<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerOrdersPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_index_and_api_are_database_backed_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'rowaa-hidden-order',
            'store_id' => 'store-rowaa',
            'status' => 'جديد',
            'payload' => [
                'order_number' => 'RO-HIDDEN',
                'customer' => 'عميل رواء',
                'total' => '999 ر.س',
                'created_at' => now()->toDateString(),
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/orders')
            ->assertOk()
            ->assertSee('قائمة الطلبات')
            ->assertSee('store-atlas')
            ->assertDontSee('RO-HIDDEN');

        $response = $this->getJson('/partner/api/orders')
            ->assertOk()
            ->assertJsonPath('filters.status', 'all');

        $this->assertFalse(collect($response->json('orders'))->contains(fn (array $order) => ($order['store_id'] ?? null) === 'store-rowaa'));
    }

    public function test_order_details_status_update_invoice_and_manual_order_work(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $orders = $this->getJson('/partner/api/orders')->json('orders');
        $orderId = $orders[0]['id'];

        $this->get('/partner/orders/' . $orderId)
            ->assertOk()
            ->assertSee('Timeline')
            ->assertSee($orders[0]['order_number']);

        $this->post('/partner/orders/' . $orderId . '/status', [
            'status' => 'completed',
        ])->assertRedirect();

        $this->get('/partner/orders/' . $orderId)
            ->assertOk()
            ->assertSee('مكتمل');

        $this->get('/partner/orders/' . $orderId . '/invoice')
            ->assertOk()
            ->assertSee('فاتورة')
            ->assertSee($orders[0]['order_number']);

        $product = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', 'store-atlas')
            ->first();

        $this->get('/partner/orders/manual')
            ->assertOk()
            ->assertSee('المنتج من المخزون')
            ->assertSee($product->payload['sku']);

        $this->post('/partner/orders/manual', [
            'customer' => 'عميل يدوي',
            'phone' => '966500000001',
            'email' => 'manual@example.test',
            'product_id' => $product->record_id,
            'product_sku' => $product->payload['sku'],
            'item_name' => 'منتج يدوي',
            'qty' => 2,
            'total' => 250,
            'unit_price' => 100,
            'discount' => 10,
            'shipping_fee' => 25,
            'tax' => 35,
            'payment_status' => 'unpaid',
            'payment_method' => 'إرسال رابط دفع',
            'shipping_method' => 'عادي',
            'city' => 'الرياض',
            'address' => 'حي العليا',
            'source_channel' => 'واتساب',
            'fulfillment_priority' => 'fast',
            'coupon_code' => 'VIP10',
            'customer_note' => 'تسليم بعد العصر',
            'internal_note' => 'راجع العنوان قبل الشحن',
        ])->assertRedirect();

        $this->assertDatabaseHas('platform_records', [
            'section' => 'orders',
            'store_id' => 'store-atlas',
            'status' => 'جديد',
        ]);

        $this->assertDatabaseHas('platform_records', [
            'section' => 'orders',
            'store_id' => 'store-atlas',
            'payload->source_channel' => 'واتساب',
            'payload->product_id' => $product->record_id,
            'payload->product_sku' => $product->payload['sku'],
            'payload->coupon_code' => 'VIP10',
            'payload->fulfillment_priority' => 'fast',
        ]);
    }

    public function test_order_subsections_are_real_store_scoped_records(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/orders/abandoned-carts')->assertOk()->assertSee('السلات المتروكة')->assertSee('store-atlas');
        $this->get('/partner/orders/returns')->assertOk()->assertSee('المرتجعات')->assertSee('store-atlas');
        $this->get('/partner/orders/shipments')->assertOk()->assertSee('الشحنات')->assertSee('store-atlas');

        $this->assertDatabaseHas('platform_records', ['section' => 'abandoned_carts', 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_records', ['section' => 'returns', 'store_id' => 'store-atlas']);
        $this->assertDatabaseHas('platform_records', ['section' => 'shipments', 'store_id' => 'store-atlas']);
    }

    public function test_official_orders_apis_notes_timeline_export_and_related_actions_work(): void
    {
        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'rowaa-api-hidden-order',
            'store_id' => 'store-rowaa',
            'status' => 'جديد',
            'payload' => [
                'order_number' => 'RO-API-HIDDEN',
                'customer' => 'عميل متجر آخر',
                'total' => '999 ر.س',
                'created_at' => now()->toDateString(),
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $ordersResponse = $this->getJson('/api/partner/orders?per_page=2&date_from=' . now()->subDays(7)->toDateString())
            ->assertOk()
            ->assertJsonPath('filters.date_from', now()->subDays(7)->toDateString())
            ->assertJsonPath('pagination.per_page', 2);

        $orders = collect($ordersResponse->json('orders'));
        $this->assertTrue($orders->isNotEmpty());
        $this->assertFalse($orders->contains(fn (array $order) => ($order['store_id'] ?? null) === 'store-rowaa'));

        $orderId = $orders->first()['id'];
        $product = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', 'store-atlas')
            ->firstOrFail();

        $this->postJson('/api/partner/orders/manual', [
            'customer' => 'عميل API',
            'phone' => '966500000002',
            'email' => 'api-order@example.test',
            'product_id' => $product->record_id,
            'product_sku' => $product->payload['sku'],
            'item_name' => $product->payload['name'],
            'qty' => 1,
            'total' => 120,
            'unit_price' => 120,
            'discount' => 0,
            'shipping_fee' => 0,
            'tax' => 0,
            'payment_status' => 'pending',
            'payment_method' => 'إرسال رابط دفع',
            'shipping_method' => 'عادي',
        ])->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('customer', 'عميل API');

        $this->getJson('/api/partner/orders/' . $orderId)
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['items', 'timeline', 'notes', 'change_log']);

        $this->patchJson('/api/partner/orders/' . $orderId . '/status', ['status' => 'processing'])
            ->assertOk()
            ->assertJsonPath('status_key', 'processing');

        $this->postJson('/api/partner/orders/' . $orderId . '/notes', ['note' => 'مراجعة العنوان قبل الشحن'])
            ->assertOk();

        $this->getJson('/api/partner/orders/' . $orderId . '/timeline')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['timeline']);

        $this->getJson('/api/partner/abandoned-carts')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $cartId = PlatformRecord::query()->where('section', 'abandoned_carts')->where('store_id', 'store-atlas')->value('record_id');
        $this->postJson('/api/partner/abandoned-carts/' . $cartId . '/remind')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $this->getJson('/api/partner/returns')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $returnId = PlatformRecord::query()->where('section', 'returns')->where('store_id', 'store-atlas')->value('record_id');
        $this->patchJson('/api/partner/returns/' . $returnId . '/status', ['status' => 'تمت الموافقة'])
            ->assertOk()
            ->assertJsonPath('status', 'تمت الموافقة');

        $this->getJson('/api/partner/shipments')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $shipmentId = PlatformRecord::query()->where('section', 'shipments')->where('store_id', 'store-atlas')->value('record_id');
        $this->patchJson('/api/partner/shipments/' . $shipmentId . '/status', ['status' => 'في الطريق'])
            ->assertOk()
            ->assertJsonPath('status', 'في الطريق');

        $this->post('/api/partner/orders/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('store-atlas');

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'order_note_added',
            'subject_id' => $orderId,
        ]);
        $this->assertDatabaseMissing('platform_activity_logs', [
            'store_id' => 'store-rowaa',
            'subject_id' => $orderId,
        ]);
    }
}
