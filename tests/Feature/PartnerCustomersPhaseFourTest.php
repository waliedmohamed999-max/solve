<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerCustomersPhaseFourTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_index_detail_and_api_are_database_backed_and_store_scoped(): void
    {
        PlatformRecord::query()->create([
            'section' => 'customers',
            'record_id' => 'rowaa-hidden-customer',
            'store_id' => 'store-rowaa',
            'status' => 'نشط',
            'payload' => [
                'name' => 'عميل رواء المخفي',
                'email' => 'hidden@rowaa.test',
                'phone' => '966500000099',
                'orders' => 4,
                'spent' => '999 ر.س',
                'city' => 'جدة',
            ],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/customers')
            ->assertOk()
            ->assertSee('جميع العملاء')
            ->assertSee('store-atlas')
            ->assertDontSee('hidden@rowaa.test');

        $response = $this->getJson('/api/partner/customers?per_page=2')
            ->assertOk()
            ->assertJsonPath('filters.status', 'all')
            ->assertJsonPath('pagination.per_page', 2);

        $customers = collect($response->json('customers'));
        $this->assertTrue($customers->isNotEmpty());
        $this->assertFalse($customers->contains(fn (array $customer) => ($customer['store_id'] ?? null) === 'store-rowaa'));

        $customerId = $customers->first()['id'];

        $this->get('/partner/customers/' . $customerId)
            ->assertOk()
            ->assertSee('Timeline')
            ->assertSee($customers->first()['email']);

        $this->getJson('/api/partner/customers/' . $customerId)
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonStructure(['orders', 'timeline', 'notes', 'addresses']);
    }

    public function test_customer_profile_update_notes_tags_and_export_work(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $customerId = $this->getJson('/api/partner/customers')->json('customers.0.id');

        $this->patchJson('/api/partner/customers/' . $customerId, [
            'name' => 'عميل محدث',
            'email' => 'updated@example.test',
            'phone' => '966500000010',
            'city' => 'الرياض',
            'status' => 'vip',
            'tags' => 'VIP, حملة مايو',
        ])->assertOk()
            ->assertJsonPath('name', 'عميل محدث')
            ->assertJsonPath('status_key', 'vip');

        $this->postJson('/api/partner/customers/' . $customerId . '/notes', [
            'note' => 'يتواصل معه فريق الدعم قبل الشحن.',
        ])->assertOk()
            ->assertJsonPath('notes.0.body', 'يتواصل معه فريق الدعم قبل الشحن.');

        $this->postJson('/api/partner/customers/' . $customerId . '/tags', [
            'tags' => 'مهم, واتساب',
        ])->assertOk();

        $this->post('/api/partner/customers/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('store-atlas');

        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'customer_updated',
            'subject_id' => $customerId,
        ]);
        $this->assertDatabaseHas('platform_activity_logs', [
            'store_id' => 'store-atlas',
            'action' => 'customer_note_added',
            'subject_id' => $customerId,
        ]);
    }

    public function test_customer_related_sections_are_functional_and_store_scoped(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/customers/groups')->assertOk()->assertSee('مجموعات العملاء')->assertSee('store-atlas');
        $this->get('/partner/customers/reviews')->assertOk()->assertSee('التقييمات')->assertSee('store-atlas');
        $this->get('/partner/customers/questions')->assertOk()->assertSee('الأسئلة')->assertSee('store-atlas');
        $this->get('/partner/customers/back-in-stock')->assertOk()->assertSee('إشعارات توفر المخزون')->assertSee('store-atlas');

        $group = $this->postJson('/api/partner/customer-groups', [
            'name' => 'عملاء الاختبار',
            'condition_type' => 'city',
            'condition_value' => 'الرياض',
            'status' => 'نشط',
        ])->assertCreated()
            ->assertJsonPath('store_id', 'store-atlas')
            ->json();

        $this->patchJson('/api/partner/customer-groups/' . $group['id'], [
            'name' => 'عملاء الرياض المحدثة',
            'condition_type' => 'orders_count',
            'condition_value' => '3',
            'status' => 'نشط',
        ])->assertOk()
            ->assertJsonPath('name', 'عملاء الرياض المحدثة');

        $reviewId = PlatformRecord::query()->where('section', 'customer_reviews')->where('store_id', 'store-atlas')->value('record_id');
        $this->patchJson('/api/partner/reviews/' . $reviewId . '/status', ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('status_key', 'published');
        $this->postJson('/api/partner/reviews/' . $reviewId . '/reply', ['reply' => 'شكرا لتقييمك.'])
            ->assertOk()
            ->assertJsonPath('reply', 'شكرا لتقييمك.');

        $questionId = PlatformRecord::query()->where('section', 'customer_questions')->where('store_id', 'store-atlas')->value('record_id');
        $this->postJson('/api/partner/questions/' . $questionId . '/reply', ['reply' => 'نعم متوفر.'])
            ->assertOk()
            ->assertJsonPath('status_key', 'answered');
        $this->patchJson('/api/partner/questions/' . $questionId . '/status', ['status' => 'hidden'])
            ->assertOk()
            ->assertJsonPath('status_key', 'hidden');

        $alertId = PlatformRecord::query()->where('section', 'back_in_stock_alerts')->where('store_id', 'store-atlas')->value('record_id');
        $this->postJson('/api/partner/back-in-stock/' . $alertId . '/notify')
            ->assertOk()
            ->assertJsonPath('status_key', 'sent');

        $this->deleteJson('/api/partner/customer-groups/' . $group['id'])
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('platform_records', [
            'section' => 'customer_groups',
            'record_id' => $group['id'],
            'store_id' => 'store-atlas',
        ]);

        $this->assertFalse(PlatformActivityLog::query()->where('store_id', 'store-rowaa')->exists());
    }
}
