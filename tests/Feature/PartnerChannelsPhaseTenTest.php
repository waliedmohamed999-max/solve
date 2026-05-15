<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerChannelsPhaseTenTest extends TestCase
{
    use RefreshDatabase;

    public function test_channels_pages_are_real_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'partner_channels',
            'record_id' => 'rowaa-secret-channel',
            'store_id' => 'store-rowaa',
            'status' => 'مفعلة',
            'payload' => ['id' => 'rowaa-secret-channel', 'name' => 'Rowaa Secret Channel', 'status_key' => 'enabled', 'status' => 'مفعلة'],
        ]);

        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);

        $this->get('/partner/channels')->assertOk()->assertSee('store-atlas')->assertDontSee('Rowaa Secret Channel');
        $this->get('/partner/channels/storefront')->assertOk()->assertSee('store-atlas');
        $this->get('/partner/channels/marketplaces')->assertOk()->assertSee('Amazon')->assertSee('Noon');
        $this->get('/partner/channels/mobile-app')->assertOk()->assertSee('Push Notifications');
        $this->get('/partner/channels/pos')->assertOk()->assertSee('POS');

        $this->getJson('/api/partner/channels')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonMissing(['name' => 'Rowaa Secret Channel']);
    }

    public function test_channel_sync_marketplaces_mobile_and_pos_apis_work(): void
    {
        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);

        $this->patchJson('/api/partner/channels/storefront/settings', [
            'visibility' => 'عام',
            'domain_status' => 'متصل',
            'theme_status' => 'منشور',
        ])->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->postJson('/api/partner/channels/marketplaces/amazon/connect', [
            'seller_id' => 'atlas-amazon',
            'api_key' => 'amazon-secret-token',
        ])->assertOk()
            ->assertJsonPath('status_key', 'enabled')
            ->assertJsonMissing(['api_key' => 'amazon-secret-token']);

        $this->postJson('/api/partner/channels/marketplaces/amazon/sync-products')
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->postJson('/api/partner/channels/marketplaces/amazon/sync-orders')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->patchJson('/api/partner/channels/mobile-app/settings', [
            'primary_color' => '#111827',
            'push_enabled' => true,
            'publish_status' => 'جاهز للنشر',
        ])->assertOk()->assertJsonPath('settings.push_enabled', true);
        $this->postJson('/api/partner/channels/mobile-app/push-test')->assertOk()->assertJsonPath('success', true);

        $this->patchJson('/api/partner/channels/pos/settings', [
            'enabled' => true,
            'branch_name' => 'الرياض',
            'sync_inventory' => true,
            'allow_returns' => true,
        ])->assertOk()->assertJsonPath('settings.enabled', true);

        $device = $this->postJson('/api/partner/channels/pos/devices', [
            'name' => 'iPad POS',
            'cashier' => 'سارة',
            'branch' => 'الرياض',
        ])->assertCreated()->json('id');

        $this->patchJson('/api/partner/channels/pos/devices/' . $device, [
            'name' => 'iPad POS 2',
            'status' => 'enabled',
        ])->assertOk()->assertJsonPath('name', 'iPad POS 2');

        $this->getJson('/api/partner/channels/pos/reports')->assertOk()->assertJsonPath('store_id', 'store-atlas');
        $this->getJson('/api/partner/channels/marketplaces/logs')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'marketplace_connected',
        ]);
    }

    public function test_channels_respect_role_plan_and_admin_paused_status(): void
    {
        $this->post('/partner/login', ['username' => 'staff@atlas.sa', 'password' => 'AtlasStaff@2026']);
        $this->get('/partner/channels')->assertForbidden();
        $this->post('/partner/logout');

        $this->post('/partner/login', ['username' => 'merchant@rowaa.sa', 'password' => 'RowaaMerchant@2026']);
        $this->patchJson('/api/partner/channels/mobile-app/status', ['status' => 'enabled'])->assertForbidden();
        $this->post('/partner/logout');

        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);
        $this->getJson('/api/partner/channels')->assertOk();

        PlatformRecord::query()
            ->where('section', 'partner_channels')
            ->where('store_id', 'store-atlas')
            ->where('record_id', 'store-atlas-marketplaces')
            ->update(['status' => 'متوقفة من الأدمن', 'payload->status_key' => 'admin_paused', 'payload->status' => 'متوقفة من الأدمن']);

        $this->patchJson('/api/partner/channels/marketplaces/status', ['status' => 'enabled'])->assertForbidden();
    }
}
