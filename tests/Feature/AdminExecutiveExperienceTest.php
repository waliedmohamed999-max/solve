<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExecutiveExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_is_executive_operations_center_with_real_metrics(): void
    {
        $store = PartnerStore::query()->create([
            'partner_id' => 'partner-exec',
            'store_id' => 'store-exec',
            'name' => 'متجر تنفيذي',
            'owner_name' => 'مدير المتجر',
            'owner_email' => 'exec@example.test',
            'status' => 'نشط',
            'plan' => 'Enterprise',
            'payment_status' => 'active',
        ]);

        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'order-exec-1',
            'store_id' => $store->store_id,
            'status' => 'paid',
            'payload' => ['order_number' => 'ORD-EXEC-1', 'total' => 1200, 'store_id' => $store->store_id],
        ]);

        PlatformRecord::query()->create([
            'section' => 'subscription_payments',
            'record_id' => 'pay-exec-failed',
            'store_id' => $store->store_id,
            'status' => 'failed',
            'payload' => ['amount' => 500, 'status' => 'failed', 'store_id' => $store->store_id],
        ]);

        PlatformRecord::query()->create([
            'section' => 'solve_ai_usage',
            'record_id' => 'ai-exec-1',
            'store_id' => $store->store_id,
            'status' => 'success',
            'payload' => ['tool' => 'campaign_generator', 'tokens' => 300, 'credits' => 2, 'store_id' => $store->store_id],
        ]);

        PlatformActivityLog::query()->create([
            'actor_type' => 'system',
            'actor_id' => 'billing',
            'actor_name' => 'Billing Monitor',
            'role' => 'system',
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'action' => 'payment_failed',
            'subject_type' => 'subscription_payment',
            'subject_id' => 'pay-exec-failed',
            'properties' => [],
        ]);

        $this->loginAsAdmin()
            ->get('/admin')
            ->assertOk()
            ->assertSee('مركز قيادة Solve اليومي')
            ->assertSee('Revenue Today')
            ->assertSee('Active Merchants')
            ->assertSee('AI Usage')
            ->assertSee('Smart Alerts Center')
            ->assertSee('Command Center')
            ->assertSee('SaaS Health Score');
    }

    public function test_executive_search_feed_alerts_and_commands_work(): void
    {
        PartnerStore::query()->create([
            'partner_id' => 'partner-search',
            'store_id' => 'store-search',
            'name' => 'متجر البحث',
            'owner_name' => 'مالك البحث',
            'owner_email' => 'search@example.test',
            'status' => 'نشط',
            'plan' => 'Basic',
        ]);

        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'ORD-SEARCH-1',
            'store_id' => 'store-search',
            'status' => 'paid',
            'payload' => ['order_number' => 'ORD-SEARCH-1', 'customer_name' => 'عميل البحث', 'total' => 150],
        ]);

        $this->loginAsAdmin()
            ->getJson('/admin/api/executive/search?q=store-search')
            ->assertOk()
            ->assertJsonPath('results.0.type', 'store');

        $this->loginAsAdmin()
            ->postJson('/admin/api/executive/command', [
                'command' => 'upgrade_plan',
                'payload' => ['store_id' => 'store-search', 'plan' => 'Enterprise'],
            ])
            ->assertOk()
            ->assertJsonPath('executed', true)
            ->assertJsonPath('plan', 'Enterprise');

        $this->assertSame('Enterprise', PartnerStore::query()->where('store_id', 'store-search')->value('plan'));
        $this->assertTrue(PlatformActivityLog::query()->where('action', 'executive.command.upgrade_plan')->where('store_id', 'store-search')->exists());

        $this->loginAsAdmin()
            ->postJson('/admin/api/executive/alerts/manual-alert', [
                'action' => 'assign',
                'assignee' => 'ops-team',
            ])
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('assigned_to', 'ops-team');

        $this->loginAsAdmin()
            ->getJson('/admin/api/executive/feed')
            ->assertOk()
            ->assertJsonStructure(['feed']);
    }
}
