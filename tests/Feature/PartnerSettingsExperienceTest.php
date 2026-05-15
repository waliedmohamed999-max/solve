<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerSettingsExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_center_renders_zid_like_real_cards_for_current_store(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/settings')
            ->assertOk()
            ->assertSee('الإعدادات')
            ->assertSee('إعدادات الحساب')
            ->assertSee('خيارات الدفع')
            ->assertSee('الحسابات البنكية')
            ->assertSee('التطبيقات وواجهة برمجة التطبيقات (API)')
            ->assertSee('الربط مع هيئة الزكاة (ZATCA)')
            ->assertSee('رسائل التقييمات')
            ->assertSee('التصنيفات المتعددة')
            ->assertSee('أوقات العمل الرسمية')
            ->assertSee('نقاط البيع')
            ->assertSee('فريق العمل')
            ->assertSee('store-atlas')
            ->assertDontSee('store-rowaa');

        $this->assertDatabaseHas('store_settings', ['store_id' => 'store-atlas']);
    }

    public function test_settings_section_can_be_updated_for_current_store_only(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/settings/store')
            ->assertOk()
            ->assertSee('إعدادات المتجر')
            ->assertSee('store-atlas');

        $this->post('/partner/settings/store', [
            'settings' => [
                'name' => 'متجر أطلس المحدث',
                'owner' => 'سارة',
                'email' => 'new-atlas@example.test',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('store_settings', ['store_id' => 'store-atlas']);
        $this->assertDatabaseMissing('store_settings', ['store_id' => 'store-rowaa', 'identity->email' => 'new-atlas@example.test']);
    }

    public function test_staff_can_see_settings_read_only_but_cannot_update(): void
    {
        $this->post('/partner/login', [
            'username' => 'staff@atlas.sa',
            'password' => 'AtlasStaff@2026',
        ]);

        $this->get('/partner/dashboard')
            ->assertOk()
            ->assertSee('الإعدادات');

        $this->get('/partner/settings/store')
            ->assertOk()
            ->assertSee('صلاحية عرض فقط');

        $this->post('/partner/settings/store', [
            'settings' => ['name' => 'لا يجب حفظه'],
        ])->assertForbidden();
    }
    public function test_settings_sections_have_api_and_typed_persistent_tools(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        foreach (['shipping', 'payments', 'checkout', 'notifications', 'legal', 'branches'] as $section) {
            $this->get('/partner/settings/' . $section)
                ->assertOk()
                ->assertSee('API JSON')
                ->assertSee('store_settings')
                ->assertSee('store-atlas');
        }

        $this->post('/partner/settings/shipping', [
            'settings' => [
                'provider' => 'سمسا',
                'city_from' => 'جدة',
                'default_fee' => '35',
                'same_day' => 'مفعل',
            ],
        ])->assertRedirect();

        $this->getJson('/partner/api/settings/shipping')
            ->assertOk()
            ->assertJsonPath('store.store_id', 'store-atlas')
            ->assertJsonPath('section.bucket', 'shipping')
            ->assertJsonPath('section.data.provider', 'سمسا')
            ->assertJsonPath('meta.store_scoped', true);
    }
}
