<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedCommerceModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_advanced_order_details(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/orders/order-1001');

        $response->assertOk();
        $response->assertSee('Order Timeline');
        $response->assertSee('SO-01001');
        $response->assertSee('ملصق شحن');
    }

    public function test_admin_can_open_customer_crm_profile(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/customers/customer-noura');

        $response->assertOk();
        $response->assertSee('CRM Profile');
        $response->assertSee('نورة السالم');
        $response->assertSee('سجل الطلبات');
    }

    public function test_admin_can_open_phase_three_modules(): void
    {
        foreach (['/admin/inventory', '/admin/invoices', '/admin/plans', '/admin/roles'] as $path) {
            $this->loginAsAdmin()->get($path)->assertOk();
        }
    }

    public function test_global_search_returns_grouped_results(): void
    {
        $response = $this->loginAsAdmin()->getJson('/admin/api/search?q=SO-01001');

        $response->assertOk();
        $response->assertJsonStructure([
            'stores',
            'orders',
            'customers',
            'products',
            'invoices',
            'support',
        ]);
        $response->assertJsonFragment(['order_number' => 'SO-01001']);
    }
}
