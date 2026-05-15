<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartnerSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_login_intended_url_is_stored_as_internal_path_only(): void
    {
        $this->withHeader('Host', 'evil.example')
            ->get('/partner/dashboard?tab=orders')
            ->assertRedirect(route('partner.login'));

        $this->assertSame('/partner/dashboard?tab=orders', session('partner.intended'));

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ])->assertRedirect('/partner/dashboard?tab=orders');
    }

    public function test_admin_login_intended_url_is_stored_as_internal_path_only(): void
    {
        $this->withHeader('Host', 'evil.example')
            ->get('/admin/stores')
            ->assertRedirect(route('admin.login'));

        $this->assertSame('/admin/stores', session('url.intended'));

        $this->post('/admin/login', [
            'username' => 'owner@solve.sa',
            'password' => 'SolveOwner@2026',
        ])->assertRedirect('/admin/stores');
    }

    public function test_production_admin_login_requires_password_hash(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->app->detectEnvironment(fn () => 'production');
        config()->set('admin.username', 'owner@solve.sa');
        config()->set('admin.password', 'SolveOwner@2026');
        config()->set('admin.password_hash', '');

        $this->post('/admin/login', [
            'username' => 'owner@solve.sa',
            'password' => 'SolveOwner@2026',
        ])->assertSessionHasErrors('username');

        config()->set('admin.password', '');
        config()->set('admin.password_hash', Hash::make('SolveOwner@2026'));

        $this->post('/admin/login', [
            'username' => 'owner@solve.sa',
            'password' => 'SolveOwner@2026',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_order_exports_neutralize_spreadsheet_formula_values(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'csv-formula-order',
            'store_id' => 'store-atlas',
            'partner_id' => 'atlas',
            'status' => 'new',
            'payload' => [
                'order_number' => 'CSV-1',
                'customer' => '=2+2',
                'phone' => '+966500000001',
                'status' => 'new',
                'payment_status' => 'paid',
                'total' => '100 ر.س',
                'created_at' => now()->toDateString(),
            ],
        ]);

        $response = $this->get('/partner/orders/export')->assertOk();

        $this->assertStringContainsString('"\'=2+2"', $response->getContent());
        $this->assertStringContainsString('"\'+966500000001"', $response->getContent());
    }

    public function test_analytics_exports_neutralize_spreadsheet_formula_values(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'analytics-formula-order',
            'store_id' => 'store-atlas',
            'partner_id' => 'atlas',
            'status' => 'new',
            'payload' => [
                'order_number' => 'AN-1',
                'customer' => '@SUM(1,1)',
                'status' => 'new',
                'total' => '100 ر.س',
                'created_at' => now()->toDateString(),
            ],
        ]);

        $response = $this->get('/partner/analytics/sales/export')->assertOk();

        $this->assertStringContainsString('"\'@SUM(1,1)"', $response->getContent());
    }

    public function test_security_headers_are_sent_on_partner_pages(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/dashboard')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Download-Options', 'noopen')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public function test_hsts_is_sent_for_secure_requests(): void
    {
        $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('/partner/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
