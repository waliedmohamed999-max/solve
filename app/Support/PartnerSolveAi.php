<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerSolveAi
{
    public static function dashboard(array $partner, string $mode = 'home'): array
    {
        self::ensureData($partner);

        return [
            'store_id' => $partner['store_id'],
            'mode' => $mode,
            'title' => 'ذكاء Solve',
            'tools' => self::tools($partner),
            'categories' => self::categories(),
            'usage' => self::usage($partner),
            'recommendations' => self::recommendations($partner),
            'history' => self::history($partner, 12),
            'settings' => self::settings($partner),
            'insights' => self::storeInsights($partner),
            'api' => [
                'tools' => route('api.partner.solve-ai.tools'),
                'generate' => route('api.partner.solve-ai.generate'),
                'improve' => route('api.partner.solve-ai.improve'),
                'analyze' => route('api.partner.solve-ai.analyze'),
                'apply' => route('api.partner.solve-ai.apply'),
                'chat' => route('api.partner.solve-ai.chat'),
                'history' => route('api.partner.solve-ai.chat.history'),
                'usage' => route('api.partner.solve-ai.usage'),
            ],
        ];
    }

    public static function tools(array $partner): array
    {
        $usage = self::usage($partner);

        return collect(self::catalog())
            ->map(function (array $tool) use ($partner, $usage) {
                $tool['locked'] = ! self::toolAllowed($partner, $tool);
                $tool['upgrade_plan'] = $tool['required_plan'] ?? 'Pro';
                $tool['remaining'] = $usage['remaining'];

                return $tool;
            })
            ->values()
            ->all();
    }

    public static function generate(array $partner, array $data, ?array $actor = null, string $kind = 'generate'): array
    {
        self::ensureData($partner);
        $tool = self::tool((string) ($data['tool'] ?? 'store_growth_advisor'));
        abort_if(! self::toolAllowed($partner, $tool), 402, 'Solve AI tool is not available for the current subscription plan.');

        $usage = self::usage($partner);
        if ($usage['limit'] !== 'unlimited' && $usage['used'] >= (int) $usage['limit']) {
            SubscriptionManager::recordUsageDenied($partner, $actor, 'ai_requests');
            self::logActivity($partner, $actor, 'solve_ai_limit_denied', 'solve_ai_usage', $tool['id'], ['tool' => $tool['id']]);
            abort(402, 'Solve AI monthly usage limit reached.');
        }

        $prompt = trim((string) ($data['prompt'] ?? ''));
        $context = self::storeContext($partner);
        $output = self::renderOutput($tool, $prompt, $context, $kind);
        $tokens = max(80, mb_strlen($prompt) + mb_strlen($output));

        $record = PlatformRecord::query()->create([
            'section' => 'solve_ai_usage',
            'record_id' => self::recordId($partner, 'solve-ai-' . Str::lower(Str::random(10))),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'success',
            'payload' => [
                'id' => Str::uuid()->toString(),
                'kind' => $kind,
                'tool' => $tool['id'],
                'tool_name' => $tool['name'],
                'category' => $tool['category'],
                'prompt' => $prompt,
                'output' => $output,
                'tokens' => $tokens,
                'credits' => self::creditsFor($tool),
                'store_id' => $partner['store_id'],
                'created_at' => now()->toDateTimeString(),
            ],
        ]);

        self::logActivity($partner, $actor, 'solve_ai_' . $kind, 'solve_ai_usage', $record->record_id, [
            'tool' => $tool['id'],
            'tokens' => $tokens,
        ]);

        return [
            'store_id' => $partner['store_id'],
            'tool' => $tool,
            'prompt' => $prompt,
            'output' => $output,
            'usage' => self::usage($partner),
            'record_id' => $record->record_id,
        ];
    }

    public static function chat(array $partner, array $data, ?array $actor = null): array
    {
        $data['tool'] = $data['tool'] ?? 'store_growth_advisor';
        $result = self::generate($partner, $data, $actor, 'chat');

        PlatformRecord::query()->create([
            'section' => 'solve_ai_chat',
            'record_id' => self::recordId($partner, 'chat-' . Str::lower(Str::random(10))),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'active',
            'payload' => [
                'id' => Str::uuid()->toString(),
                'message' => $result['prompt'],
                'answer' => $result['output'],
                'tool' => $result['tool']['id'],
                'store_id' => $partner['store_id'],
                'created_at' => now()->toDateTimeString(),
            ],
        ]);

        return $result + ['history' => self::history($partner, 20)];
    }

    public static function apply(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureData($partner);
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $tool = self::tool((string) ($data['tool'] ?? 'store_growth_advisor'));
        abort_if(! self::toolAllowed($partner, $tool), 402, 'Solve AI tool is not available for the current subscription plan.');

        $output = trim((string) ($data['output'] ?? ''));
        abort_if($output === '', 422, 'AI output is required before applying the result.');

        $targetType = (string) ($data['target_type'] ?? self::defaultTargetForTool($tool));
        $targetId = (string) ($data['target_id'] ?? $data['product_id'] ?? '');
        $appliedTargetId = null;
        $appliedPayload = [];

        if ($targetType === 'product') {
            $record = self::findStoreRecord($partner, 'products', $targetId)
                ?? PlatformRecord::query()->where('section', 'products')->where('store_id', $partner['store_id'])->latest()->first();

            abort_unless($record, 404, 'No product was found for this store.');

            $payload = $record->payload ?? [];
            $payload = array_merge($payload, self::productPatchForTool($tool, $output));
            $payload['ai_last_tool'] = $tool['id'];
            $payload['ai_last_applied_at'] = now()->toDateTimeString();

            $record->update(['payload' => $payload]);
            $appliedTargetId = $record->record_id;
            $appliedPayload = $payload;
        } elseif ($targetType === 'campaign') {
            $record = PlatformRecord::query()->create([
                'section' => 'marketing_campaigns',
                'record_id' => self::recordId($partner, 'ai-campaign-' . Str::lower(Str::random(8))),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => 'draft',
                'payload' => [
                    'id' => Str::uuid()->toString(),
                    'name' => Str::limit((string) ($data['prompt'] ?? $tool['name']), 70, ''),
                    'type' => 'ai_generated',
                    'status' => 'draft',
                    'status_key' => 'draft',
                    'ai_tool' => $tool['id'],
                    'content' => $output,
                    'store_id' => $partner['store_id'],
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);

            $appliedTargetId = $record->record_id;
            $appliedPayload = $record->payload ?? [];
        } else {
            $appliedTargetId = self::recordId($partner, 'ai-note-' . Str::lower(Str::random(8)));
            $appliedPayload = [
                'id' => Str::uuid()->toString(),
                'target_type' => $targetType,
                'tool' => $tool['id'],
                'content' => $output,
                'store_id' => $partner['store_id'],
                'created_at' => now()->toDateTimeString(),
            ];
        }

        PlatformRecord::query()->create([
            'section' => 'solve_ai_applied_results',
            'record_id' => self::recordId($partner, 'applied-' . Str::lower(Str::random(10))),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'applied',
            'payload' => [
                'tool' => $tool['id'],
                'tool_name' => $tool['name'],
                'target_type' => $targetType,
                'target_id' => $appliedTargetId,
                'prompt' => $data['prompt'] ?? null,
                'output' => $output,
                'store_id' => $partner['store_id'],
                'created_at' => now()->toDateTimeString(),
            ],
        ]);

        self::logActivity($partner, $actor, 'solve_ai_result_applied', $targetType, (string) $appliedTargetId, [
            'tool' => $tool['id'],
            'target_type' => $targetType,
        ]);

        return [
            'applied' => true,
            'store_id' => $partner['store_id'],
            'tool' => $tool['id'],
            'target_type' => $targetType,
            'target_id' => $appliedTargetId,
            'payload' => $appliedPayload,
        ];
    }

    public static function history(array $partner, int $limit = 30): array
    {
        return self::records($partner, 'solve_ai_chat')
            ->take($limit)
            ->values()
            ->all();
    }

    public static function deleteChat(array $partner, string $id, ?array $actor = null): void
    {
        $record = PlatformRecord::query()
            ->where('section', 'solve_ai_chat')
            ->where('store_id', $partner['store_id'])
            ->where(function ($query) use ($id) {
                $query->where('record_id', $id)->orWhere('payload->id', $id);
            })
            ->firstOrFail();

        $record->delete();
        self::logActivity($partner, $actor, 'solve_ai_chat_deleted', 'solve_ai_chat', $id);
    }

    public static function usage(array $partner): array
    {
        $limit = self::limitForPlan((string) ($partner['plan'] ?? 'Free'));
        $records = self::records($partner, 'solve_ai_usage')
            ->filter(fn (array $record) => Str::startsWith((string) ($record['created_at'] ?? ''), now()->format('Y-m')));
        $used = $records->count();
        $credits = $records->sum(fn (array $record) => (int) ($record['credits'] ?? 1));
        $tokens = $records->sum(fn (array $record) => (int) ($record['tokens'] ?? 0));

        return [
            'store_id' => $partner['store_id'],
            'plan' => $partner['plan'] ?? 'Free',
            'limit' => $limit,
            'used' => $used,
            'credits_used' => $credits,
            'tokens' => $tokens,
            'remaining' => $limit === 'unlimited' ? 'unlimited' : max(0, (int) $limit - $used),
            'percent' => $limit === 'unlimited' ? 0 : min(100, (int) round(($used / max(1, (int) $limit)) * 100)),
        ];
    }

    public static function adminUsage(): array
    {
        $records = Schema::hasTable('platform_records')
            ? PlatformRecord::query()->where('section', 'solve_ai_usage')->latest()->get()
            : collect();

        $items = $records->map(fn (PlatformRecord $record) => self::normalize($record));

        return [
            'total_requests' => $items->count(),
            'tokens' => $items->sum(fn (array $item) => (int) ($item['tokens'] ?? 0)),
            'stores' => $items->groupBy('store_id')->map->count()->sortDesc()->all(),
            'tools' => $items->groupBy('tool')->map->count()->sortDesc()->all(),
            'recent' => $items->take(40)->values()->all(),
        ];
    }

    public static function adminTools(): array
    {
        $settings = self::adminSettings();

        return collect(self::catalog())->map(function (array $tool) use ($settings) {
            $tool['enabled'] = ! in_array($tool['id'], $settings['disabled_tools'] ?? [], true);

            return $tool;
        })->values()->all();
    }

    public static function updateAdminTool(string $id, array $data): array
    {
        self::tool($id);
        $settings = self::adminSettings();
        $disabled = collect($settings['disabled_tools'] ?? []);
        $enabled = (bool) ($data['enabled'] ?? true);
        $settings['disabled_tools'] = $enabled
            ? $disabled->reject(fn (string $tool) => $tool === $id)->values()->all()
            : $disabled->push($id)->unique()->values()->all();

        self::saveAdminSettings($settings);

        return ['id' => $id, 'enabled' => $enabled, 'settings' => $settings];
    }

    public static function adminSettings(): array
    {
        $record = Schema::hasTable('platform_records')
            ? PlatformRecord::query()->where('section', 'solve_ai_admin_settings')->where('record_id', 'global')->first()
            : null;

        return array_merge([
            'disabled_tools' => [],
            'free_limit' => 20,
            'pro_limit' => 500,
            'enterprise_limit' => 2500,
            'data_retention_days' => 180,
        ], $record?->payload ?? []);
    }

    public static function updateAdminSettings(array $data): array
    {
        $settings = array_merge(self::adminSettings(), array_filter([
            'free_limit' => isset($data['free_limit']) ? (int) $data['free_limit'] : null,
            'pro_limit' => isset($data['pro_limit']) ? (int) $data['pro_limit'] : null,
            'enterprise_limit' => isset($data['enterprise_limit']) ? (int) $data['enterprise_limit'] : null,
            'data_retention_days' => isset($data['data_retention_days']) ? (int) $data['data_retention_days'] : null,
        ], fn ($value) => $value !== null));

        self::saveAdminSettings($settings);

        return $settings;
    }

    private static function ensureData(array $partner): void
    {
        PartnerProducts::ensureStoreData($partner);
        PartnerOrders::ensureStoreData($partner);
        PartnerCustomers::ensureStoreData($partner);
        PartnerMarketing::ensureStoreData($partner);
    }

    private static function storeContext(array $partner): array
    {
        $products = self::records($partner, 'products');
        $orders = self::records($partner, 'orders');
        $customers = self::records($partner, 'customers');
        $carts = self::records($partner, 'abandoned_carts');

        $sales = $orders->sum(fn (array $order) => (float) ($order['total'] ?? $order['grand_total'] ?? 0));
        $lowStock = $products->filter(fn (array $product) => (int) ($product['stock'] ?? 0) <= (int) ($product['low_stock_threshold'] ?? 10));
        $bestProduct = $products->sortByDesc(fn (array $product) => (int) ($product['sales_count'] ?? $product['sold'] ?? 0))->first();

        return [
            'store' => $partner,
            'products_count' => $products->count(),
            'orders_count' => $orders->count(),
            'customers_count' => $customers->count(),
            'abandoned_carts_count' => $carts->count(),
            'sales' => $sales,
            'average_order' => $orders->count() ? round($sales / max(1, $orders->count()), 2) : 0,
            'low_stock' => $lowStock->take(5)->values()->all(),
            'best_product' => $bestProduct,
            'latest_order' => $orders->first(),
        ];
    }

    private static function storeInsights(array $partner): array
    {
        $context = self::storeContext($partner);

        return [
            ['label' => 'إجمالي الطلبات', 'value' => $context['orders_count'], 'hint' => 'محسوبة من طلبات المتجر'],
            ['label' => 'إجمالي المبيعات', 'value' => number_format($context['sales']) . ' ر.س', 'hint' => 'من سجلات الطلبات'],
            ['label' => 'منتجات منخفضة', 'value' => count($context['low_stock']), 'hint' => 'تحتاج متابعة'],
            ['label' => 'عملاء المتجر', 'value' => $context['customers_count'], 'hint' => 'حسب store_id'],
        ];
    }

    private static function recommendations(array $partner): array
    {
        $context = self::storeContext($partner);
        $best = $context['best_product']['name'] ?? 'أفضل منتجاتك';

        return [
            [
                'title' => 'حملة ذكية للمنتج الأعلى أداءً',
                'body' => 'ابدأ حملة قصيرة حول ' . $best . ' مع كوبون محدود لمدة 48 ساعة.',
                'priority' => 'عالية',
                'tool' => 'campaign_generator',
            ],
            [
                'title' => 'تحسين المخزون',
                'body' => count($context['low_stock']) . ' منتجات وصلت لحد التنبيه. جهز طلب توريد قبل نفادها.',
                'priority' => count($context['low_stock']) ? 'عالية' : 'منخفضة',
                'tool' => 'stockout_forecast',
            ],
            [
                'title' => 'رفع متوسط الطلب',
                'body' => 'متوسط الطلب الحالي ' . $context['average_order'] . ' ر.س. جرّب Bundle أو Upsell في صفحة المنتج.',
                'priority' => 'متوسطة',
                'tool' => 'offer_ideas',
            ],
        ];
    }

    private static function renderOutput(array $tool, string $prompt, array $context, string $kind): string
    {
        $storeName = $context['store']['name'] ?? 'متجرك';
        $best = $context['best_product']['name'] ?? 'منتجك الأقوى';
        $sales = number_format((float) $context['sales']);
        $orders = $context['orders_count'];
        $lowStock = collect($context['low_stock'])->pluck('name')->filter()->take(3)->implode('، ');

        return match ($tool['id']) {
            'product_description_writer' => "وصف مقترح لـ {$prompt}: منتج مختار من {$storeName} يجمع بين الجودة والتفاصيل العملية، مناسب للاستخدام اليومي ويصل للعميل بتجربة شراء موثوقة. أضف صوراً واضحة، وحدد المزايا الأساسية، واستخدم كلمات مثل: جودة، شحن سريع، ضمان، أصلي.",
            'product_description_improver' => "تحسين الوصف: ابدأ بفائدة واضحة للعميل، ثم اذكر الخامة أو الاستخدام، وبعدها نقطة ثقة مثل الشحن أو الاسترجاع. النص المحسن: {$prompt} - اختيار عملي بجودة عالية وتجربة شراء سهلة من {$storeName}.",
            'product_name_ideas' => "أسماء مقترحة: {$prompt} الفاخر، {$prompt} اليومي، {$prompt} برو، {$prompt} المختار، {$prompt} الأساسي.",
            'product_keywords', 'product_seo' => "Keywords وSEO: {$prompt}، {$storeName}، شراء أونلاين، شحن سريع، عروض، جودة عالية، منتج أصلي. Meta title مقترح: {$prompt} | {$storeName}.",
            'campaign_generator' => "حملة مقترحة: ركز على {$best}. الهدف زيادة الطلبات من {$orders} طلب. الرسالة: عرض محدود من {$storeName} على المنتجات الأكثر طلباً. القنوات: واتساب + إعلان Meta + بنر رئيسي. مدة الحملة: 3 أيام.",
            'coupon_generator' => "كوبون مقترح: SOLVE15. خصم 15% للطلبات فوق متوسط الطلب الحالي. اربطه بالمنتجات الأعلى مشاهدة، واجعل صلاحيته 48 ساعة لزيادة التحويل.",
            'meta_ads_writer' => "إعلان Meta: اكتشف منتجات {$storeName} المختارة بعناية. اطلب الآن واستمتع بتجربة دفع آمنة وشحن سريع. CTA: تسوق الآن.",
            'tiktok_ads_writer' => "سكريبت TikTok: لقطة سريعة للمنتج، ثم عرض المشكلة، ثم المنتج كحل. النص: جرب {$best} من {$storeName} واستفد من عرض محدود اليوم.",
            'whatsapp_writer' => "رسالة واتساب: أهلاً، عندنا عرض جديد على {$best}. الكمية محدودة والشحن سريع. استخدم الكوبون SOLVE15 قبل انتهاء العرض.",
            'email_campaign_writer' => "Email Campaign: العنوان: عروض مختارة من {$storeName}. المقدمة: اخترنا لك منتجات تحقق طلباً أعلى هذا الأسبوع. CTA: شاهد العروض.",
            'sales_drop_analysis' => "تحليل المبيعات: مبيعات المتجر الحالية {$sales} ر.س من {$orders} طلب. راقب المنتجات منخفضة المخزون، وراجع البنرات والكوبونات إذا انخفضت الزيارات أو التحويل.",
            'stale_products_analysis' => "المنتجات الراكدة تحتاج صور أو وصف أقوى وربطها بعروض. ابدأ بتحسين عنوان المنتج، ثم أضف Bundle مع {$best}.",
            'best_products' => "أفضل منتج حالياً: {$best}. استثمره في الصفحة الرئيسية والبنرات ورسائل واتساب.",
            'stockout_forecast' => $lowStock ? "توقع نفاد قريب للمنتجات: {$lowStock}. اقترح إعادة توريد خلال 7 أيام أو إيقاف الحملات عليها مؤقتاً." : 'لا توجد منتجات حرجة حالياً، لكن راقب حد التنبيه أسبوعياً.',
            'customers_analysis' => "تحليل العملاء: لديك {$context['customers_count']} عميل. أنشئ شريحة للعملاء المتكررين، وشريحة للعملاء غير النشطين لإرسال كوبون عودة.",
            'conversion_forecast' => "توقع التحويل: حسّن صفحة المنتج بصور أكثر، Trust badges، وتوصيات مرتبطة. هذا مناسب لرفع التحويل خصوصاً حول {$best}.",
            'policy_writer' => "سياسة جاهزة: يلتزم {$storeName} بتوفير تجربة شراء واضحة، مع حق الاستبدال حسب حالة المنتج، وتأكيد الطلب قبل الشحن، وحماية بيانات العميل.",
            'faq_generator' => "FAQ مقترح: كيف يتم الشحن؟ ما مدة التوصيل؟ هل الدفع آمن؟ كيف أستبدل المنتج؟ كيف أتواصل مع الدعم؟",
            'support_replies', 'customer_reply_assistant' => "رد دعم مقترح: أهلاً بك، سعداء بتواصلك مع {$storeName}. سنراجع طلبك ونحدثك بالحالة، ويمكنك تزويدنا برقم الطلب لتسريع الخدمة.",
            'review_analysis' => "تحليل التقييمات: ركز على تكرار كلمات الجودة، الشحن، المقاس، التغليف. أي تكرار سلبي حول الشحن يجب تحويله لتنبيه تشغيلي.",
            default => "ملخص ذكي: {$storeName} لديه {$orders} طلب ومبيعات {$sales} ر.س. أفضل خطوة الآن هي تحسين {$best} بحملة قصيرة ومراجعة المنتجات منخفضة المخزون.",
        };
    }

    private static function catalog(): array
    {
        return [
            ['id' => 'product_description_writer', 'name' => 'كاتب وصف المنتجات', 'category' => 'products', 'badge' => 'الأكثر استخداماً', 'popular' => true, 'new' => false, 'required_plan' => 'Free', 'description' => 'يكتب وصفاً متوافقاً مع محركات البحث ومناسباً لطبيعة المنتج.'],
            ['id' => 'product_description_improver', 'name' => 'تحسين وصف المنتجات', 'category' => 'products', 'badge' => 'منتجات', 'popular' => true, 'new' => false, 'required_plan' => 'Free', 'description' => 'يعيد صياغة الوصف ليصبح أوضح وأكثر إقناعاً.'],
            ['id' => 'product_name_ideas', 'name' => 'اقتراح أسماء منتجات', 'category' => 'products', 'badge' => 'جديد', 'popular' => false, 'new' => true, 'required_plan' => 'Free', 'description' => 'يقترح أسماء تجارية قصيرة وواضحة.'],
            ['id' => 'product_keywords', 'name' => 'اقتراح Keywords', 'category' => 'seo', 'badge' => 'SEO', 'popular' => true, 'new' => false, 'required_plan' => 'Free', 'description' => 'ينتج كلمات بحث من بيانات المنتج والمتجر.'],
            ['id' => 'product_seo', 'name' => 'تحسين SEO للمنتج', 'category' => 'seo', 'badge' => 'SEO', 'popular' => true, 'new' => false, 'required_plan' => 'Pro', 'description' => 'ينشئ عنوان ووصف SEO ومقترحات Schema.'],
            ['id' => 'product_image_generator', 'name' => 'مولد صور المنتجات AI', 'category' => 'products', 'badge' => 'صور', 'popular' => false, 'new' => true, 'required_plan' => 'Enterprise', 'description' => 'يجهز Prompt احترافي لتوليد صورة منتج.'],
            ['id' => 'background_remover', 'name' => 'إزالة خلفية الصور', 'category' => 'products', 'badge' => 'صور', 'popular' => false, 'new' => true, 'required_plan' => 'Pro', 'description' => 'يدير طلب إزالة الخلفية ضمن سجل AI.'],
            ['id' => 'image_enhancer', 'name' => 'تحسين جودة الصور', 'category' => 'products', 'badge' => 'صور', 'popular' => false, 'new' => true, 'required_plan' => 'Pro', 'description' => 'يعطي إرشادات تحسين صور المنتج.'],
            ['id' => 'campaign_generator', 'name' => 'مولد حملات تسويقية', 'category' => 'marketing', 'badge' => 'تسويق', 'popular' => true, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يقترح حملة مبنية على الطلبات والمنتجات.'],
            ['id' => 'coupon_generator', 'name' => 'مولد كوبونات', 'category' => 'marketing', 'badge' => 'تسويق', 'popular' => true, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يقترح كوبون وشروط استخدام مناسبة.'],
            ['id' => 'meta_ads_writer', 'name' => 'كتابة إعلانات Meta', 'category' => 'marketing', 'badge' => 'Ads', 'popular' => false, 'new' => false, 'required_plan' => 'Pro', 'description' => 'ينشئ نص إعلان لفيسبوك وإنستغرام.'],
            ['id' => 'tiktok_ads_writer', 'name' => 'كتابة إعلانات TikTok', 'category' => 'marketing', 'badge' => 'Ads', 'popular' => false, 'new' => true, 'required_plan' => 'Pro', 'description' => 'ينتج سكريبت إعلان قصير.'],
            ['id' => 'whatsapp_writer', 'name' => 'كتابة رسائل واتساب', 'category' => 'marketing', 'badge' => 'WhatsApp', 'popular' => true, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يكتب رسائل تذكير أو عروض.'],
            ['id' => 'email_campaign_writer', 'name' => 'كتابة Email Campaigns', 'category' => 'marketing', 'badge' => 'Email', 'popular' => false, 'new' => false, 'required_plan' => 'Pro', 'description' => 'ينشئ عنوان ومحتوى بريد تسويقي.'],
            ['id' => 'offer_ideas', 'name' => 'اقتراح عروض', 'category' => 'marketing', 'badge' => 'عروض', 'popular' => true, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يقترح عروضاً لرفع متوسط الطلب.'],
            ['id' => 'sales_drop_analysis', 'name' => 'تحليل انخفاض المبيعات', 'category' => 'analytics', 'badge' => 'تحليل', 'popular' => true, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يفسر انخفاض المبيعات من بيانات المتجر.'],
            ['id' => 'stale_products_analysis', 'name' => 'تحليل المنتجات الراكدة', 'category' => 'analytics', 'badge' => 'تحليل', 'popular' => false, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يكشف المنتجات التي تحتاج تحسيناً.'],
            ['id' => 'best_products', 'name' => 'اقتراح أفضل المنتجات', 'category' => 'analytics', 'badge' => 'تحليل', 'popular' => true, 'new' => false, 'required_plan' => 'Free', 'description' => 'يعرض المنتج الأفضل وكيف تستفيد منه.'],
            ['id' => 'stockout_forecast', 'name' => 'توقع نفاد المخزون', 'category' => 'analytics', 'badge' => 'مخزون', 'popular' => true, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يعتمد على المخزون الحالي وحد التنبيه.'],
            ['id' => 'customers_analysis', 'name' => 'تحليل العملاء', 'category' => 'analytics', 'badge' => 'عملاء', 'popular' => false, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يقترح شرائح عملاء للحملات.'],
            ['id' => 'conversion_forecast', 'name' => 'توقع معدل التحويل', 'category' => 'analytics', 'badge' => 'تحويل', 'popular' => false, 'new' => true, 'required_plan' => 'Enterprise', 'description' => 'يعطي توقعات ومقترحات UX.'],
            ['id' => 'policy_writer', 'name' => 'كاتب السياسات', 'category' => 'operations', 'badge' => 'تشغيل', 'popular' => false, 'new' => false, 'required_plan' => 'Free', 'description' => 'ينشئ سياسة شحن واسترجاع واضحة.'],
            ['id' => 'faq_generator', 'name' => 'إنشاء صفحات FAQ', 'category' => 'operations', 'badge' => 'تشغيل', 'popular' => false, 'new' => false, 'required_plan' => 'Free', 'description' => 'ينشئ أسئلة شائعة للمتجر.'],
            ['id' => 'support_replies', 'name' => 'إنشاء ردود الدعم', 'category' => 'operations', 'badge' => 'دعم', 'popular' => true, 'new' => false, 'required_plan' => 'Free', 'description' => 'يقترح ردوداً جاهزة لخدمة العملاء.'],
            ['id' => 'customer_reply_assistant', 'name' => 'مساعد الرد على العملاء', 'category' => 'operations', 'badge' => 'دعم', 'popular' => false, 'new' => true, 'required_plan' => 'Pro', 'description' => 'يصيغ ردوداً حسب الحالة.'],
            ['id' => 'review_analysis', 'name' => 'تحليل تقييمات العملاء', 'category' => 'operations', 'badge' => 'تقييمات', 'popular' => false, 'new' => false, 'required_plan' => 'Pro', 'description' => 'يلخص نقاط القوة والضعف من التقييمات.'],
            ['id' => 'store_growth_advisor', 'name' => 'مستشار نمو المتجر', 'category' => 'analytics', 'badge' => 'DNA', 'popular' => true, 'new' => true, 'required_plan' => 'Free', 'description' => 'يربط بين الطلبات والمنتجات والعملاء ليقترح الخطوة التالية.'],
        ];
    }

    private static function categories(): array
    {
        return [
            'all' => 'الكل',
            'popular' => 'الأكثر استخداماً',
            'new' => 'الأدوات الجديدة',
            'marketing' => 'أدوات التسويق',
            'products' => 'أدوات المنتجات',
            'analytics' => 'أدوات التحليلات',
            'seo' => 'أدوات SEO',
            'operations' => 'أدوات التشغيل',
        ];
    }

    private static function tool(string $id): array
    {
        $tool = collect(self::catalog())->firstWhere('id', $id);
        abort_unless($tool, 422, 'Unknown Solve AI tool.');

        return $tool;
    }

    private static function toolAllowed(array $partner, array $tool): bool
    {
        $settings = self::adminSettings();
        if (in_array($tool['id'], $settings['disabled_tools'] ?? [], true)) {
            return false;
        }

        return self::planRank((string) ($partner['plan'] ?? 'Free')) >= self::planRank((string) ($tool['required_plan'] ?? 'Free'));
    }

    private static function defaultTargetForTool(array $tool): string
    {
        return match ($tool['category']) {
            'products', 'seo' => 'product',
            'marketing' => 'campaign',
            'operations' => 'policy',
            default => 'note',
        };
    }

    private static function productPatchForTool(array $tool, string $output): array
    {
        return match ($tool['id']) {
            'product_seo', 'product_keywords' => [
                'seo_title' => Str::limit(strip_tags($output), 70, ''),
                'seo_description' => Str::limit(strip_tags($output), 160, ''),
                'seo_keywords' => collect(preg_split('/[،,\n]+/u', $output))->map(fn ($word) => trim((string) $word))->filter()->take(12)->values()->all(),
                'ai_seo' => $output,
            ],
            'product_name_ideas' => [
                'ai_name_ideas' => $output,
            ],
            default => [
                'description' => $output,
                'ai_description' => $output,
            ],
        };
    }

    private static function findStoreRecord(array $partner, string $section, string $id): ?PlatformRecord
    {
        if ($id === '') {
            return null;
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->get()
            ->first(function (PlatformRecord $record) use ($id) {
                $payload = $record->payload ?? [];

                return $record->record_id === $id
                    || (string) ($payload['id'] ?? '') === $id
                    || (string) ($payload['sku'] ?? '') === $id;
            });
    }

    private static function limitForPlan(string $plan): int|string
    {
        $settings = self::adminSettings();

        return match ($plan) {
            'Enterprise' => $settings['enterprise_limit'] ?? 2500,
            'Growth', 'Pro' => $settings['pro_limit'] ?? 500,
            'Starter', 'Basic' => 100,
            default => $settings['free_limit'] ?? 20,
        };
    }

    private static function planRank(string $plan): int
    {
        return ['Free' => 0, 'Starter' => 1, 'Basic' => 1, 'Growth' => 2, 'Pro' => 2, 'Enterprise' => 3][$plan] ?? 0;
    }

    private static function creditsFor(array $tool): int
    {
        return in_array($tool['category'], ['analytics', 'products'], true) && ($tool['required_plan'] ?? 'Free') !== 'Free' ? 2 : 1;
    }

    private static function settings(array $partner): array
    {
        return [
            'data_sources' => ['products', 'orders', 'customers', 'inventory', 'marketing'],
            'privacy' => 'store_scoped',
            'masked_sensitive_data' => true,
            'monthly_limit' => self::limitForPlan((string) ($partner['plan'] ?? 'Free')),
        ];
    }

    private static function records(array $partner, string $section): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => self::normalize($record));
    }

    private static function normalize(PlatformRecord $record): array
    {
        return array_merge($record->payload ?? [], [
            'record_id' => $record->record_id,
            'store_id' => $record->store_id,
            'status' => $record->status,
            'updated_at_human' => $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function saveAdminSettings(array $settings): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'solve_ai_admin_settings', 'record_id' => 'global', 'store_id' => null],
            ['status' => 'active', 'payload' => $settings]
        );
    }

    private static function recordId(array $partner, string $id): string
    {
        return ($partner['store_id'] ?? 'store') . '-' . $id;
    }

    private static function logActivity(array $partner, ?array $actor, string $action, string $subjectType, string $subjectId, array $properties = []): void
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return;
        }

        PlatformActivityLog::query()->create([
            'actor_type' => 'partner',
            'actor_id' => $actor['username'] ?? $actor['email'] ?? null,
            'actor_name' => $actor['name'] ?? null,
            'role' => $actor['role'] ?? null,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
