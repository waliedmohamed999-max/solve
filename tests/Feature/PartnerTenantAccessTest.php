<?php

namespace Tests\Feature;

use Tests\TestCase;

class PartnerTenantAccessTest extends TestCase
{
    public function test_admin_can_open_partners_index(): void
    {
        $response = $this
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/partners');

        $response
            ->assertOk()
            ->assertSee('atlas.solve.sa')
            ->assertSee('rowaa.solve.sa');
    }

    public function test_partner_dashboard_is_scoped_to_current_store(): void
    {
        $response = $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $response->assertRedirect('/partner/dashboard');

        $dashboard = $this->get('/partner/dashboard');

        $dashboard
            ->assertOk()
            ->assertSee('store-atlas')
            ->assertDontSee('store-rowaa');
    }

    public function test_staff_user_cannot_open_payments(): void
    {
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ]);

        $this->get('/partner/payments')->assertForbidden();
    }

    public function test_partner_login_page_renders_a_fresh_csrf_form(): void
    {
        $response = $this->get('/partner/login');

        $response
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('بوابة دخول الشركاء والتجار')
            ->assertDontSee('merchant@atlas.sa')
            ->assertDontSee('AtlasMerchant@2026')
            ->assertDontSee('AtlasStaff@2026');
    }
}
