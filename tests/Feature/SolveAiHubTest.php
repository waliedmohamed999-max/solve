<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolveAiHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_open_solve_ai_hub_and_fetch_store_scoped_tools(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->get('/partner/apps/solve-ai')
            ->assertOk()
            ->assertSee('ذكاء Solve')
            ->assertSee('store-atlas');

        $this->getJson('/api/partner/solve-ai/tools')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonFragment(['id' => 'product_description_writer']);
    }

    public function test_solve_ai_generation_uses_current_store_and_logs_usage(): void
    {
        PlatformRecord::query()->create([
            'section' => 'solve_ai_chat',
            'record_id' => 'rowaa-chat',
            'store_id' => 'store-rowaa',
            'status' => 'active',
            'payload' => ['message' => 'سجل متجر آخر', 'answer' => 'لا يظهر', 'store_id' => 'store-rowaa'],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $this->postJson('/api/partner/solve-ai/generate', [
            'tool' => 'product_description_writer',
            'prompt' => 'عباية أطلس كلاسيك',
        ])
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('tool.id', 'product_description_writer')
            ->assertJsonFragment(['prompt' => 'عباية أطلس كلاسيك']);

        $this->postJson('/api/partner/solve-ai/chat', [
            'prompt' => 'ما المنتجات منخفضة المخزون؟',
        ])
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas');

        $history = $this->getJson('/api/partner/solve-ai/chat/history')
            ->assertOk()
            ->assertJsonPath('store_id', 'store-atlas')
            ->json('history');

        $this->assertNotEmpty($history);
        $this->assertStringNotContainsString('store-rowaa', json_encode($history, JSON_UNESCAPED_UNICODE));
        $this->assertTrue(PlatformRecord::query()->where('section', 'solve_ai_usage')->where('store_id', 'store-atlas')->exists());
        $this->assertTrue(PlatformActivityLog::query()->where('action', 'solve_ai_generate')->where('store_id', 'store-atlas')->exists());
    }

    public function test_admin_can_monitor_solve_ai_usage_and_toggle_tools(): void
    {
        $this->loginAsAdmin()
            ->get('/admin/solve-ai')
            ->assertOk()
            ->assertSee('ذكاء Solve');

        $this->loginAsAdmin()
            ->getJson('/api/admin/solve-ai/tools')
            ->assertOk()
            ->assertJsonFragment(['id' => 'product_description_writer']);

        $this->loginAsAdmin()
            ->patchJson('/api/admin/solve-ai/tools/product_description_writer', ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('enabled', false);
    }

    public function test_core_solve_ai_tools_generate_from_real_store_scoped_data(): void
    {
        PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => 'rowaa-order-hidden',
            'store_id' => 'store-rowaa',
            'status' => 'paid',
            'payload' => ['customer_name' => 'عميل متجر آخر', 'total' => 999999, 'store_id' => 'store-rowaa'],
        ]);

        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $tools = [
            'product_description_writer',
            'product_seo',
            'campaign_generator',
            'whatsapp_writer',
            'sales_drop_analysis',
            'stockout_forecast',
            'customers_analysis',
            'policy_writer',
            'support_replies',
        ];

        foreach ($tools as $tool) {
            $payload = $this->postJson('/api/partner/solve-ai/generate', [
                'tool' => $tool,
                'prompt' => 'اختبار أداة ' . $tool,
            ])
                ->assertOk()
                ->assertJsonPath('store_id', 'store-atlas')
                ->assertJsonPath('tool.id', $tool)
                ->json();

            $this->assertNotEmpty($payload['output'] ?? null);
            $this->assertStringNotContainsString('store-rowaa', json_encode($payload, JSON_UNESCAPED_UNICODE));
            $this->assertStringNotContainsString('999999', json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $this->assertSame(count($tools), PlatformRecord::query()->where('section', 'solve_ai_usage')->where('store_id', 'store-atlas')->count());
    }

    public function test_partner_can_apply_ai_result_to_product_and_campaign(): void
    {
        $this->post('/partner/login', [
            'username' => 'merchant@atlas.sa',
            'password' => 'AtlasMerchant@2026',
        ]);

        $generated = $this->postJson('/api/partner/solve-ai/generate', [
            'tool' => 'product_description_writer',
            'prompt' => 'عباية أطلس كلاسيك',
        ])->assertOk()->json();

        $product = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', 'store-atlas')
            ->firstOrFail();

        $this->postJson('/api/partner/solve-ai/apply', [
            'tool' => 'product_description_writer',
            'target_type' => 'product',
            'target_id' => $product->record_id,
            'output' => $generated['output'],
        ])
            ->assertOk()
            ->assertJsonPath('applied', true)
            ->assertJsonPath('store_id', 'store-atlas')
            ->assertJsonPath('target_type', 'product')
            ->assertJsonPath('target_id', $product->record_id);

        $updatedProduct = $product->refresh();
        $this->assertSame($generated['output'], $updatedProduct->payload['ai_description'] ?? null);

        $campaign = $this->postJson('/api/partner/solve-ai/apply', [
            'tool' => 'campaign_generator',
            'target_type' => 'campaign',
            'prompt' => 'حملة نهاية الأسبوع',
            'output' => 'حملة تسويقية جاهزة مرتبطة بمنتجات store-atlas.',
        ])
            ->assertOk()
            ->assertJsonPath('applied', true)
            ->assertJsonPath('target_type', 'campaign')
            ->json();

        $this->assertTrue(PlatformRecord::query()->where('section', 'marketing_campaigns')->where('store_id', 'store-atlas')->where('record_id', $campaign['target_id'])->exists());
        $this->assertTrue(PlatformRecord::query()->where('section', 'solve_ai_applied_results')->where('store_id', 'store-atlas')->count() >= 2);
        $this->assertTrue(PlatformActivityLog::query()->where('action', 'solve_ai_result_applied')->where('store_id', 'store-atlas')->exists());
    }

    public function test_admin_controls_ai_limits_and_usage_monitoring(): void
    {
        $this->loginAsAdmin()
            ->patchJson('/api/admin/solve-ai/settings', [
                'free_limit' => 3,
                'pro_limit' => 250,
                'enterprise_limit' => 900,
                'data_retention_days' => 90,
            ])
            ->assertOk()
            ->assertJsonPath('free_limit', 3)
            ->assertJsonPath('pro_limit', 250)
            ->assertJsonPath('enterprise_limit', 900);

        $this->loginAsAdmin()
            ->getJson('/api/admin/solve-ai/usage')
            ->assertOk()
            ->assertJsonStructure(['total_requests', 'tokens', 'stores', 'tools', 'recent']);
    }
}
