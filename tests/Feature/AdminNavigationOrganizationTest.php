<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminNavigationOrganizationTest extends TestCase
{
    public function test_sidebar_is_grouped_for_merchant_workflows(): void
    {
        $this->loginAsAdmin()->get('/admin')
            ->assertOk()
            ->assertSee('ابحث في القائمة')
            ->assertSee('لوحة التحكم')
            ->assertSee('الطلبات')
            ->assertSee('المنتجات')
            ->assertSee('العملاء')
            ->assertSee('التسويق')
            ->assertSee('المتجر الإلكتروني')
            ->assertSee('التحليلات')
            ->assertSee('المالية')
            ->assertSee('الخدمات')
            ->assertSee('القنوات')
            ->assertSee('التطبيقات')
            ->assertSee('الإعدادات')
            ->assertSee('آخر الطلبات')
            ->assertSee('تنبيهات مهمة');
    }
}
