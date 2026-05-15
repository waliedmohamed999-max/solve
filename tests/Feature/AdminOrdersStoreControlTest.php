<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersStoreControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_orders_page_groups_orders_by_store_and_filters_selected_store(): void
    {
        $this->createStore('partner-atlas', 'store-atlas', 'Atlas Store', 'Sara Owner');
        $this->createStore('partner-rowaa', 'store-rowaa', 'Rowaa Store', 'Rowaa Owner');

        $this->createOrder('admin-atlas-order', 'store-atlas', 'AT-ADMIN-1', 'Atlas Customer', '1,250 SAR');
        $this->createOrder('admin-rowaa-order', 'store-rowaa', 'RO-ADMIN-1', 'Rowaa Customer', '870 SAR');

        $this->loginAsAdmin()
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Atlas Store')
            ->assertSee('Rowaa Store')
            ->assertSee('store-atlas')
            ->assertSee('store-rowaa')
            ->assertSee('AT-ADMIN-1')
            ->assertSee('RO-ADMIN-1');

        $this->loginAsAdmin()
            ->get('/admin/orders?store_id=store-rowaa')
            ->assertOk()
            ->assertSee('Rowaa Store')
            ->assertSee('RO-ADMIN-1')
            ->assertDontSee('AT-ADMIN-1');
    }

    public function test_admin_can_open_database_backed_order_details_from_store_control(): void
    {
        $this->createStore('partner-rowaa', 'store-rowaa', 'Rowaa Store', 'Rowaa Owner');
        $this->createOrder('admin-rowaa-order', 'store-rowaa', 'RO-ADMIN-1', 'Rowaa Customer', '870 SAR');

        $this->loginAsAdmin()
            ->get('/admin/orders/admin-rowaa-order')
            ->assertOk()
            ->assertSee('RO-ADMIN-1')
            ->assertSee('Rowaa Customer')
            ->assertSee('Order Timeline');
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

    private function createOrder(string $id, string $storeId, string $number, string $customer, string $total): void
    {
        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => $id,
            'store_id' => $storeId,
            'partner_id' => str_replace('store-', 'partner-', $storeId),
            'status' => 'processing',
            'payload' => [
                'order_number' => $number,
                'customer' => $customer,
                'total' => $total,
                'status' => 'processing',
                'payment_status' => 'paid',
                'shipping_status' => 'ready',
                'created_at' => now()->toDateString(),
                'items' => [
                    ['name' => 'Product A', 'qty' => 1, 'total' => $total],
                ],
            ],
        ]);
    }
}
