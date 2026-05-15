<?php

namespace Tests\Feature;

use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerServicesPhaseNineTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_pages_are_real_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'partner_services',
            'record_id' => 'rowaa-secret-service',
            'store_id' => 'store-rowaa',
            'status' => 'مفعلة',
            'payload' => ['id' => 'rowaa-secret-service', 'name' => 'Rowaa Secret Service', 'status_key' => 'enabled', 'status' => 'مفعلة'],
        ]);

        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);

        $this->get('/partner/services')->assertOk()->assertSee('store-atlas')->assertDontSee('Rowaa Secret Service');
        $this->get('/partner/services/logistics')->assertOk()->assertSee('Aramex');
        $this->get('/partner/services/payment-gateways')->assertOk()->assertSee('Mada');
        $this->get('/partner/services/whatsapp')->assertOk()->assertSee('WhatsApp');
        $this->get('/partner/services/financing')->assertOk()->assertSee('طلبات التمويل');
        $this->get('/partner/services/growth')->assertOk()->assertSee('توصيات النمو');

        $this->getJson('/api/partner/services')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonMissing(['name' => 'Rowaa Secret Service']);
    }

    public function test_logistics_payment_whatsapp_financing_and_growth_apis_work(): void
    {
        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);

        $logistics = $this->getJson('/api/partner/services/logistics')->assertOk()->json('rows.0');
        $this->patchJson('/api/partner/services/logistics/' . $logistics['id'] . '/settings', [
            'provider' => 'Aramex',
            'api_key' => 'secret-logistics-key',
            'regions' => 'الرياض, جدة',
            'shipping_rates' => '25 SAR',
            'status' => 'enabled',
        ])->assertOk()
            ->assertJsonPath('api_key_masked', '****************-key')
            ->assertJsonMissing(['api_key' => 'secret-logistics-key']);

        $this->postJson('/api/partner/services/logistics/' . $logistics['id'] . '/test')
            ->assertOk()
            ->assertJsonPath('success', true);

        $gateway = $this->getJson('/api/partner/services/payment-gateways')->assertOk()->json('rows.0');
        $this->patchJson('/api/partner/services/payment-gateways/' . $gateway['id'] . '/settings', [
            'provider' => 'Mada',
            'api_key' => 'secret-payment-key',
            'mode' => 'production',
            'status' => 'enabled',
        ])->assertOk()->assertJsonPath('mode', 'production')->assertJsonMissing(['api_key' => 'secret-payment-key']);

        $this->patchJson('/api/partner/services/whatsapp/settings', [
            'business_number' => '966500000001',
            'access_token' => 'whatsapp-secret-token',
            'order_confirmation_template' => 'تم تأكيد طلبك',
            'order_status_template' => 'تحديث الطلب',
            'abandoned_cart_template' => 'سلتك بانتظارك',
            'back_in_stock_template' => 'المنتج متوفر',
        ])->assertOk()->assertJsonMissing(['access_token' => 'whatsapp-secret-token']);

        $this->postJson('/api/partner/services/whatsapp/test')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/partner/services/whatsapp/logs')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->patchJson('/api/partner/services/financing/settings', [
            'provider' => 'تمارا للأعمال',
            'enabled' => true,
            'min_order_total' => 500,
            'max_installments' => 4,
            'terms' => 'تمويل للطلبات المؤهلة.',
        ])->assertOk()->assertJsonPath('enabled', true);

        $requestId = $this->getJson('/api/partner/services/financing/requests')->assertOk()->json('requests.0.id');
        $this->patchJson('/api/partner/services/financing/requests/' . $requestId . '/status', ['status' => 'مكتملة'])
            ->assertOk()
            ->assertJsonPath('request_status', 'مكتملة');

        $this->getJson('/api/partner/services/growth')->assertOk()->assertJsonPath('store_id', 'store-atlas');
        $this->getJson('/api/partner/services/growth/recommendations')->assertOk()->assertJsonPath('store_id', 'store-atlas');

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'logistics_settings_updated',
        ]);
    }

    public function test_admin_paused_service_cannot_be_enabled_by_partner(): void
    {
        $this->post('/partner/login', ['username' => 'merchant@atlas.sa', 'password' => 'AtlasMerchant@2026']);
        $this->getJson('/api/partner/services')->assertOk();

        PlatformRecord::query()
            ->where('section', 'partner_services')
            ->where('store_id', 'store-atlas')
            ->where('record_id', 'growth')
            ->update(['status' => 'موقوفة من الأدمن', 'payload->status_key' => 'admin_paused', 'payload->status' => 'موقوفة من الأدمن']);

        $this->patchJson('/api/partner/services/growth/status', ['status' => 'enabled'])->assertForbidden();
    }
}
