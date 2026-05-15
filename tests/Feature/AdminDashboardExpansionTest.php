<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminDashboardExpansionTest extends TestCase
{
    public function test_dashboard_and_new_commerce_sections_load(): void
    {
        $this->withSession(['admin_authenticated' => true])
            ->get('/admin')
            ->assertOk()
            ->assertSee('تابع متجرك من مكان واحد')
            ->assertSee('مبيعات اليوم')
            ->assertSee('تجهيز المتجر');

        foreach (['orders', 'products', 'customers', 'coupons', 'staff'] as $section) {
            $this->withSession(['admin_authenticated' => true])
                ->get("/admin/{$section}")
                ->assertOk();
        }
    }
}
