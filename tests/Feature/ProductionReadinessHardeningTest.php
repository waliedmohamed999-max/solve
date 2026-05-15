<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionReadinessHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_admin_health_checks_are_available(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonMissingPath('checks');

        $this->loginAsAdmin()
            ->getJson('/admin/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    ['name', 'ok', 'message'],
                ],
            ]);
    }

    public function test_successful_sensitive_mutations_are_audited_with_masked_input(): void
    {
        $this->loginAsAdmin()
            ->post('/admin/sections/settings', [
                'name' => 'Payment Gateway',
                'status' => 'active',
                'api_key' => 'secret-live-key',
                'client_secret' => 'client-secret-value',
            ])
            ->assertRedirect();

        $log = PlatformActivityLog::query()
            ->where('action', 'http_mutation')
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('POST', $log->properties['method']);
        $this->assertSame('/admin/sections/settings', $log->properties['path']);
        $this->assertArrayNotHasKey('api_key', $log->properties['input']);
        $this->assertArrayNotHasKey('client_secret', $log->properties['input']);
        $this->assertSame('Payment Gateway', $log->properties['input']['name']);
    }

    public function test_production_readiness_includes_monitoring_controls(): void
    {
        $this->loginAsAdmin()
            ->get('/admin/production-readiness')
            ->assertOk()
            ->assertSee('Health Check API')
            ->assertSee('Slow Query Monitoring');
    }
}
