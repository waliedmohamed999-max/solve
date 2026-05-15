<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformBackupReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_backup_command_exports_core_tables(): void
    {
        Storage::fake('local');
        $this->createStore();

        $exitCode = Artisan::call('solve:backup', ['--label' => 'test-run']);

        $this->assertSame(0, $exitCode);

        $files = Storage::disk('local')->files('backups/platform');
        $this->assertCount(1, $files);

        $payload = json_decode(Storage::disk('local')->get($files[0]), true);

        $this->assertSame('test-run', $payload['label']);
        $this->assertArrayHasKey('partner_stores', $payload['tables']);
        $this->assertSame(1, $payload['tables']['partner_stores']['count']);
    }

    public function test_admin_can_trigger_and_inspect_latest_backup(): void
    {
        Storage::fake('local');
        $this->createStore();

        $this->loginAsAdmin()
            ->postJson('/admin/api/backups', ['label' => 'admin-trigger'])
            ->assertCreated()
            ->assertJsonPath('backup.label', 'admin-trigger')
            ->assertJsonStructure(['backup' => ['path', 'checksum', 'tables']]);

        $this->loginAsAdmin()
            ->getJson('/admin/api/backups/latest')
            ->assertOk()
            ->assertJsonPath('backup.label', 'admin-trigger');

        $this->loginAsAdmin()
            ->get('/admin/production-readiness')
            ->assertOk()
            ->assertSee('Recent Platform Backup')
            ->assertSee('backups/platform');
    }

    private function createStore(): void
    {
        PartnerStore::query()->create([
            'partner_id' => 'partner-atlas',
            'store_id' => 'store-atlas',
            'name' => 'Atlas Store',
            'brand_name' => 'Atlas Store',
            'owner_name' => 'Sara',
            'owner_email' => 'sara@example.test',
            'owner_phone' => '+966500000000',
            'status' => 'active',
            'plan' => 'Enterprise',
            'payment_status' => 'paid',
        ]);
    }
}
