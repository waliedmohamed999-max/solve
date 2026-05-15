<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerAppsPhaseElevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_apps_pages_are_real_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'partner_apps',
            'record_id' => 'store-rowaa-secret-app',
            'store_id' => 'store-rowaa',
            'status' => 'مثبت',
            'payload' => ['id' => 'secret-app', 'name' => 'Rowaa Secret App', 'status_key' => 'installed', 'status' => 'مثبت'],
        ]);

        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);

        $this->get('/partner/apps')->assertOk()->assertSee('store-atlas')->assertDontSee('Rowaa Secret App');
        $this->get('/partner/apps/marketplace')->assertOk()->assertSee('Mada Pay');
        $this->get('/partner/apps/installed')->assertOk();
        $this->get('/partner/apps/automations')->assertOk()->assertSee('الأتمتة');
        $this->get('/partner/apps/ai')->assertOk()->assertSee('أدوات الذكاء الاصطناعي');

        $this->getJson('/api/partner/apps')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonMissing(['name' => 'Rowaa Secret App']);
    }

    public function test_app_install_settings_test_uninstall_automation_and_ai_work(): void
    {
        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);

        $this->postJson('/api/partner/apps/ga4/install')
            ->assertCreated()
            ->assertJsonPath('app.status_key', 'needs_setup');

        $this->patchJson('/api/partner/apps/ga4/settings', [
            'api_key' => 'ga4-secret-key',
            'permissions' => ['analytics:read'],
            'events' => ['order.created'],
            'webhook_url' => 'https://example.com/webhook',
        ])->assertOk()
            ->assertJsonPath('settings.app_id', 'ga4')
            ->assertJsonMissing(['api_key' => 'ga4-secret-key']);

        $this->postJson('/api/partner/apps/ga4/test')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/partner/apps/ga4/logs')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $automationId = $this->postJson('/api/partner/automations', [
            'name' => 'Order WhatsApp',
            'trigger' => 'new_order',
            'action' => 'send_whatsapp',
            'conditions' => 'paid only',
        ])->assertCreated()->json('id');

        $this->patchJson('/api/partner/automations/' . $automationId, [
            'name' => 'Order Email',
            'trigger' => 'new_order',
            'action' => 'send_email',
        ])->assertOk()->assertJsonPath('name', 'Order Email');
        $this->patchJson('/api/partner/automations/' . $automationId . '/status', ['status' => 'disabled'])->assertOk()->assertJsonPath('status_key', 'disabled');
        $this->getJson('/api/partner/automations/' . $automationId . '/logs')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->getJson('/api/partner/ai/tools')->assertOk()->assertJsonPath('store_id', 'store-atlas');
        $this->postJson('/api/partner/ai/generate', [
            'tool' => 'product-description',
            'prompt' => 'عطر فاخر',
        ])->assertOk()->assertJsonPath('store_id', 'store-atlas');
        $this->getJson('/api/partner/ai/usage')->assertOk()->assertJsonPath('used', 1);
        $this->getJson('/api/partner/ai/recommendations')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->deleteJson('/api/partner/apps/ga4/uninstall')->assertOk()->assertJsonPath('deleted', true);
        $this->deleteJson('/api/partner/automations/' . $automationId)->assertOk()->assertJsonPath('deleted', true);

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'app_installed',
        ]);
    }

    public function test_apps_respect_role_plan_and_admin_paused_status(): void
    {
        $this->post('/partner/login', ['username' => 'staff@atlas.sa', 'password' => 'AtlasStaff@2026']);
        $this->get('/partner/apps')->assertForbidden();
        $this->post('/partner/logout');

        $this->post('/partner/login', ['username' => 'merchant@rowaa.sa', 'password' => 'RowaaMerchant@2026']);
        $this->postJson('/api/partner/apps/solve-ai/install')->assertStatus(402);
        $this->post('/partner/logout');

        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);
        $this->getJson('/api/partner/apps')->assertOk();
        PlatformRecord::query()
            ->where('section', 'partner_apps')
            ->where('store_id', 'store-atlas')
            ->where('payload->id', 'mailchimp')
            ->update(['status' => 'موقوف من الأدمن', 'payload->status_key' => 'admin_paused', 'payload->status' => 'موقوف من الأدمن']);

        $this->postJson('/api/partner/apps/mailchimp/install')->assertForbidden();
    }
}
