<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantFocusedExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_page_is_merchant_focused(): void
    {
        $this->loginAsAdmin()->get('/admin/products')
            ->assertOk()
            ->assertSee('إدارة المنتجات')
            ->assertSee('منتج متعدد الخيارات')
            ->assertSee('مسودة')
            ->assertSee('حفظ تلقائي');
    }

    public function test_orders_page_has_timeline_and_fast_actions(): void
    {
        $this->loginAsAdmin()->get('/admin/orders')
            ->assertOk()
            ->assertSee('إدارة الطلبات')
            ->assertSee('Timeline')
            ->assertSee('طباعة فاتورة')
            ->assertSee('فلترة ذكية');
    }

    public function test_customers_reports_integrations_and_subscriptions_are_simple(): void
    {
        $this->loginAsAdmin()->get('/admin/customers')
            ->assertOk()
            ->assertSee('ملف عميل مرتب')
            ->assertSee('VIP');

        $this->loginAsAdmin()->get('/admin/analytics')
            ->assertOk()
            ->assertSee('مبيعات اليوم')
            ->assertSee('أفضل المنتجات');

        $this->loginAsAdmin()->get('/admin/integrations')
            ->assertOk()
            ->assertSee('Moyasar')
            ->assertSee('WhatsApp Business');

        $this->loginAsAdmin()->get('/admin/subscriptions')
            ->assertOk()
            ->assertSee('Growth')
            ->assertSee('ترقية الباقة');
    }
}
