<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartnerSmartFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_dashboard_renders_real_store_scoped_intelligence(): void
    {
        $store = $this->createStore();
        $this->createPartnerUser($store);
        $this->seedSmartData($store);
        $this->record($this->createStore(['store_id' => 'store-other', 'partner_id' => 'partner-other']), 'products', 'other-product', [
            'name' => 'Other Product',
            'stock' => 0,
        ]);

        $this->actingAsPartner($store)
            ->get('/partner/dashboard')
            ->assertOk()
            ->assertSee('Solve Intelligence')
            ->assertSee('Smart Store Health')
            ->assertSee('Smart Recommendations')
            ->assertSee('Smart Inventory')
            ->assertSee('مساعد Solve AI');

        $this->actingAsPartner($store)
            ->getJson('/api/partner/dashboard/smart')
            ->assertOk()
            ->assertJsonPath('store_id', $store->store_id)
            ->assertJsonStructure([
                'health' => ['score', 'label', 'drivers'],
                'alerts',
                'recommendations',
                'inventory_forecast',
                'automation_suggestions',
            ])
            ->assertJsonMissing(['store_id' => 'store-other']);
    }

    public function test_smart_assistant_answers_and_logs_current_store_only(): void
    {
        $store = $this->createStore();
        $this->createPartnerUser($store);
        $this->seedSmartData($store);

        $this->actingAsPartner($store)
            ->postJson('/api/partner/ai/assistant', [
                'message' => 'اقترح حملة للسلات المتروكة',
            ])
            ->assertOk()
            ->assertJsonPath('store_id', $store->store_id)
            ->assertJsonPath('intent', 'campaign')
            ->assertJsonStructure(['answer', 'actions']);

        $this->assertDatabaseHas('platform_records', [
            'section' => 'partner_ai_assistant_chats',
            'store_id' => $store->store_id,
            'status' => 'answered',
        ]);

        $this->assertTrue(PlatformActivityLog::query()
            ->where('store_id', $store->store_id)
            ->where('action', 'smart_assistant_answered')
            ->exists());
    }

    public function test_staff_without_dashboard_permission_cannot_use_smart_assistant(): void
    {
        $store = $this->createStore();
        PartnerUser::query()->create([
            'partner_store_id' => $store->id,
            'store_id' => $store->store_id,
            'name' => 'Limited Staff',
            'username' => 'limited@example.test',
            'email' => 'limited@example.test',
            'password_hash' => Hash::make('StrongPass2026'),
            'role' => 'staff',
            'status' => 'active',
            'abilities' => ['view-orders'],
        ]);

        $this->withSession([
            'partner_user' => [
                'name' => 'Limited Staff',
                'username' => 'limited@example.test',
                'role' => 'staff',
                'store_id' => $store->store_id,
                'abilities' => ['view-orders'],
            ],
        ])->postJson('/api/partner/ai/assistant', [
            'message' => 'حلل الأداء',
        ])->assertForbidden();
    }

    private function createStore(array $overrides = []): PartnerStore
    {
        return PartnerStore::query()->create(array_merge([
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
            'subscription_started_at' => now()->subMonth()->toDateString(),
            'subscription_renews_at' => now()->addMonth()->toDateString(),
        ], $overrides));
    }

    private function createPartnerUser(PartnerStore $store): void
    {
        PartnerUser::query()->create([
            'partner_store_id' => $store->id,
            'store_id' => $store->store_id,
            'name' => 'Store Owner',
            'username' => 'owner@example.test',
            'email' => 'owner@example.test',
            'password_hash' => Hash::make('StrongPass2026'),
            'role' => 'partner_admin',
            'status' => 'active',
            'abilities' => ['*'],
        ]);
    }

    private function actingAsPartner(PartnerStore $store): self
    {
        $this->withSession([
            'partner_user' => [
                'name' => 'Store Owner',
                'username' => 'owner@example.test',
                'role' => 'partner_admin',
                'store_id' => $store->store_id,
                'abilities' => ['*'],
            ],
        ]);

        return $this;
    }

    private function seedSmartData(PartnerStore $store): void
    {
        $this->record($store, 'orders', 'current-order', [
            'order_number' => 'ORD-SMART-1',
            'customer' => 'Noura',
            'status' => 'processing',
            'total' => '120 SAR',
            'created_at' => now()->subDay()->toDateString(),
            'items' => [['name' => 'Hero Product', 'sku' => 'HP-1', 'quantity' => 2]],
        ]);

        $this->record($store, 'orders', 'previous-order', [
            'order_number' => 'ORD-SMART-0',
            'customer' => 'Noura',
            'status' => 'completed',
            'total' => '2000 SAR',
            'created_at' => now()->subDays(40)->toDateString(),
        ]);

        $this->record($store, 'orders', 'late-order', [
            'order_number' => 'ORD-LATE',
            'customer' => 'Fahad',
            'status' => 'processing',
            'total' => '300 SAR',
            'created_at' => now()->subDays(6)->toDateString(),
        ]);

        $this->record($store, 'products', 'hero-product', [
            'name' => 'Hero Product',
            'sku' => 'HP-1',
            'stock' => 2,
            'low_stock_threshold' => 5,
            'price' => '80 SAR',
            'views' => 55,
        ]);

        $this->record($store, 'abandoned_carts', 'cart-1', [
            'customer' => 'Noura',
            'total' => '420 SAR',
            'created_at' => now()->subDay()->toDateString(),
        ]);

        $this->record($store, 'payments', 'payment-failed', [
            'status' => 'failed',
            'amount' => '420 SAR',
            'created_at' => now()->toDateString(),
        ]);
    }

    private function record(PartnerStore $store, string $section, string $recordId, array $payload): PlatformRecord
    {
        return PlatformRecord::query()->create([
            'section' => $section,
            'record_id' => $recordId,
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'status' => $payload['status'] ?? 'active',
            'payload' => $payload + ['store_id' => $store->store_id],
        ]);
    }
}
