<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductsStoreControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_products_page_groups_products_by_store_and_filters_selected_store(): void
    {
        $this->createStore('partner-atlas', 'store-atlas', 'Atlas Store', 'Sara Owner');
        $this->createStore('partner-rowaa', 'store-rowaa', 'Rowaa Store', 'Rowaa Owner');

        $this->createProduct('admin-atlas-product', 'store-atlas', 'AT-PRO-1', 'Atlas Product', 18);
        $this->createProduct('admin-rowaa-product', 'store-rowaa', 'RO-PRO-1', 'Rowaa Product', 3);
        $this->createOrder('admin-rowaa-order', 'store-rowaa', 'RO-ADMIN-1', '870 SAR');

        $this->loginAsAdmin()
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Atlas Store')
            ->assertSee('Rowaa Store')
            ->assertSee('store-atlas')
            ->assertSee('store-rowaa')
            ->assertSee('AT-PRO-1')
            ->assertSee('RO-PRO-1')
            ->assertSee('ملخص طلبات المتجر');

        $this->loginAsAdmin()
            ->get('/admin/products?store_id=store-rowaa')
            ->assertOk()
            ->assertSee('Rowaa Store')
            ->assertSee('RO-PRO-1')
            ->assertSee('RO-ADMIN-1')
            ->assertDontSee('AT-PRO-1');
    }

    public function test_admin_products_page_uses_store_scoped_product_filters(): void
    {
        $this->createStore('partner-atlas', 'store-atlas', 'Atlas Store', 'Sara Owner');
        $this->createProduct('admin-atlas-low', 'store-atlas', 'LOW-1', 'Low Stock Product', 2);
        $this->createProduct('admin-atlas-ok', 'store-atlas', 'OK-1', 'Available Product', 50);

        $this->loginAsAdmin()
            ->get('/admin/products?store_id=store-atlas&stock=low')
            ->assertOk()
            ->assertSee('LOW-1')
            ->assertDontSee('OK-1');
    }

    private function createStore(string $partnerId, string $storeId, string $name, string $owner): void
    {
        PartnerStore::query()->create([
            'partner_id' => $partnerId,
            'store_id' => $storeId,
            'name' => $name,
            'brand_name' => $name,
            'owner_name' => $owner,
            'owner_email' => $storeId . '@example.test',
            'owner_phone' => '+966500000000',
            'status' => 'active',
            'plan' => 'Enterprise',
            'domain' => $storeId . '.solve.test',
            'payment_status' => 'paid',
        ]);
    }

    private function createProduct(string $id, string $storeId, string $sku, string $name, int $stock): void
    {
        PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => $id,
            'store_id' => $storeId,
            'partner_id' => str_replace('store-', 'partner-', $storeId),
            'status' => 'active',
            'payload' => [
                'name' => $name,
                'sku' => $sku,
                'price' => '250 SAR',
                'stock' => $stock,
                'low_stock_threshold' => 5,
                'status' => 'active',
                'created_at' => now()->toDateString(),
            ],
        ]);
    }

    private function createOrder(string $id, string $storeId, string $number, string $total): void
    {
        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => $id,
            'store_id' => $storeId,
            'partner_id' => str_replace('store-', 'partner-', $storeId),
            'status' => 'processing',
            'payload' => [
                'order_number' => $number,
                'customer' => 'Store Customer',
                'total' => $total,
                'status' => 'processing',
                'payment_status' => 'paid',
                'shipping_status' => 'ready',
                'created_at' => now()->toDateString(),
            ],
        ]);
    }
}
