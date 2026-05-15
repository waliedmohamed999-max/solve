<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerSmartInsights
{
    public static function forPartner(array $partner, ?Request $request = null): array
    {
        PartnerDashboardSummary::ensureStoreData($partner);
        PartnerMarketing::ensureStoreData($partner);

        $storeId = (string) $partner['store_id'];
        $days = self::periodDays($request);
        $orders = self::records('orders', $storeId);
        $products = self::records('products', $storeId);
        $carts = self::records('abandoned_carts', $storeId);
        $payments = self::records('payments', $storeId);
        $currentOrders = self::insideDays($orders, $days, 0);
        $previousOrders = self::insideDays($orders, $days, $days);
        $currentSales = self::sales($currentOrders);
        $previousSales = self::sales($previousOrders);
        $lowStock = $products->filter(fn (array $product) => self::stock($product) > 0 && self::stock($product) <= self::threshold($product))->values();
        $outOfStock = $products->filter(fn (array $product) => self::stock($product) <= 0)->values();
        $lateOrders = $orders->filter(fn (array $order) => self::isPending($order) && ($date = self::date($order)) && $date->lt(now()->subDays(3)))->values();
        $failedPayments = $payments->filter(fn (array $payment) => Str::contains(Str::lower((string) ($payment['status'] ?? '')), ['failed', 'declined', 'فشل', 'مرفوض']))->values();
        $productsNeedWork = self::productsNeedImprovement($products, $orders);
        $healthScore = self::healthScore($currentSales, $previousSales, $lowStock, $outOfStock, $lateOrders, $carts, $productsNeedWork);

        return [
            'store_id' => $storeId,
            'period' => ['days' => $days],
            'health' => [
                'score' => $healthScore,
                'label' => self::healthLabel($healthScore),
                'drivers' => self::healthDrivers($currentSales, $previousSales, $lowStock, $outOfStock, $lateOrders, $carts),
            ],
            'alerts' => self::alerts($currentSales, $previousSales, $lowStock, $outOfStock, $lateOrders, $carts, $failedPayments, $productsNeedWork),
            'recommendations' => self::recommendations($partner, $currentSales, $previousSales, $lowStock, $outOfStock, $lateOrders, $carts, $productsNeedWork),
            'inventory_forecast' => self::inventoryForecast($products, $orders),
            'marketing_suggestions' => self::marketingSuggestions($partner, $orders, $carts),
            'pricing_suggestions' => self::pricingSuggestions($products, $orders),
            'seo_suggestions' => self::seoSuggestions($products),
            'automation_suggestions' => self::automationSuggestions($lowStock, $carts, $failedPayments),
            'assistant' => [
                'endpoint' => route('api.partner.ai.assistant'),
                'suggested_prompts' => [
                    'حلل أداء متجري هذا الأسبوع',
                    'اقترح حملة للسلات المتروكة',
                    'اكتب وصف منتج احترافي',
                    'ما المنتجات التي تحتاج تحسين؟',
                ],
            ],
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'source_tables' => ['platform_records'],
                'store_scoped' => true,
            ],
        ];
    }

    public static function assistant(array $partner, array $data, ?array $actor = null): array
    {
        $smart = self::forPartner($partner);
        $prompt = trim((string) ($data['message'] ?? $data['prompt'] ?? ''));
        abort_if($prompt === '', 422, 'Assistant message is required.');

        $intent = self::intent($prompt);
        $answer = self::answer($intent, $prompt, $smart);
        $actions = self::assistantActions($intent, $smart);

        if (Schema::hasTable('platform_records')) {
            PlatformRecord::query()->create([
                'section' => 'partner_ai_assistant_chats',
                'record_id' => self::recordId($partner, 'assistant-' . Str::lower(Str::random(8))),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => 'answered',
                'payload' => [
                    'message' => $prompt,
                    'intent' => $intent,
                    'answer' => $answer,
                    'actions' => $actions,
                    'store_id' => $partner['store_id'],
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);
        }

        self::logActivity($partner, $actor, 'smart_assistant_answered', 'partner_ai_assistant', $intent);

        return [
            'store_id' => $partner['store_id'],
            'intent' => $intent,
            'message' => $prompt,
            'answer' => $answer,
            'actions' => $actions,
            'health' => $smart['health'],
        ];
    }

    private static function alerts(
        float $currentSales,
        float $previousSales,
        Collection $lowStock,
        Collection $outOfStock,
        Collection $lateOrders,
        Collection $carts,
        Collection $failedPayments,
        Collection $productsNeedWork
    ): array {
        $alerts = [];

        if ($previousSales > 0 && $currentSales < ($previousSales * 0.8)) {
            $alerts[] = self::item('sales_drop', 'انخفاض المبيعات', 'المبيعات أقل من الفترة السابقة بأكثر من 20%. راجع الحملات والمنتجات الأعلى زيارة.', 'danger', route('partner.analytics.sales'));
        }

        if ($outOfStock->isNotEmpty()) {
            $alerts[] = self::item('out_of_stock', 'منتجات نفدت', $outOfStock->count() . ' منتج غير متوفر حالياً ويحتاج إعادة توريد.', 'danger', route('partner.products.inventory'));
        }

        if ($lowStock->isNotEmpty()) {
            $alerts[] = self::item('low_stock', 'مخزون منخفض', $lowStock->count() . ' منتج اقترب من النفاد.', 'warning', route('partner.products.inventory'));
        }

        if ($lateOrders->isNotEmpty()) {
            $alerts[] = self::item('late_orders', 'طلبات متأخرة', $lateOrders->count() . ' طلب لم يكتمل منذ أكثر من 3 أيام.', 'warning', route('partner.orders'));
        }

        if ($carts->count() >= 3) {
            $alerts[] = self::item('abandoned_carts', 'سلات متروكة مرتفعة', 'يوجد ' . $carts->count() . ' سلات تحتاج حملة استرجاع.', 'warning', route('partner.orders.abandoned-carts'));
        }

        if ($failedPayments->isNotEmpty()) {
            $alerts[] = self::item('failed_payments', 'مدفوعات فاشلة', $failedPayments->count() . ' عملية دفع تحتاج متابعة.', 'warning', route('partner.analytics.payments'));
        }

        if ($productsNeedWork->isNotEmpty()) {
            $alerts[] = self::item('product_improvements', 'منتجات تحتاج تحسين', $productsNeedWork->count() . ' منتج يحتاج وصف أو SEO أو صورة أفضل.', 'info', route('partner.products'));
        }

        return array_slice($alerts, 0, 6);
    }

    private static function recommendations(array $partner, float $currentSales, float $previousSales, Collection $lowStock, Collection $outOfStock, Collection $lateOrders, Collection $carts, Collection $productsNeedWork): array
    {
        $rows = [];

        if ($carts->isNotEmpty()) {
            $rows[] = self::item('recover_carts', 'فعّل حملة استرجاع السلات', 'أنشئ كوبون محدود وذكّر العملاء عبر واتساب أو البريد.', 'high', route('partner.marketing.abandoned-carts'));
        }

        if ($lowStock->isNotEmpty() || $outOfStock->isNotEmpty()) {
            $rows[] = self::item('reorder_inventory', 'أعد طلب المنتجات المهمة', 'ابدأ بالمنتجات الأقل مخزوناً حتى لا تخسر مبيعات جاهزة.', 'high', route('partner.products.inventory'));
        }

        if ($lateOrders->isNotEmpty()) {
            $rows[] = self::item('speed_operations', 'راجع تجهيز الطلبات', 'استخدم تحديث جماعي للحالة واطبع بوليصات الشحن للطلبات المتأخرة.', 'high', route('partner.orders'));
        }

        if ($previousSales > 0 && $currentSales < $previousSales) {
            $rows[] = self::item('launch_campaign', 'أطلق حملة قصيرة', 'المبيعات أقل من الفترة السابقة. ابدأ بحملة على أفضل المنتجات أو العملاء المتكررين.', 'medium', route('partner.marketing.campaigns'));
        }

        if ($productsNeedWork->isNotEmpty()) {
            $rows[] = self::item('enhance_products', 'حسّن صفحات المنتجات', 'استخدم مساعد Solve لكتابة وصف، عنوان SEO، وتحسين عرض المنتج.', 'medium', route('partner.apps.ai'));
        }

        if ($rows === []) {
            $rows[] = self::item('keep_growth', 'المتجر مستقر', 'استمر في مراقبة المخزون وجرّب حملة بسيطة لزيادة متوسط قيمة الطلب.', 'low', route('partner.analytics'));
        }

        return $rows;
    }

    private static function inventoryForecast(Collection $products, Collection $orders): array
    {
        return $products
            ->map(function (array $product) use ($orders) {
                $name = (string) ($product['name'] ?? $product['product'] ?? '');
                $sku = (string) ($product['sku'] ?? $product['id'] ?? '');
                $stock = self::stock($product);
                $sold = max(1, self::soldUnits($orders, $name, $sku));
                $dailyVelocity = max(0.1, $sold / 30);
                $daysLeft = (int) floor($stock / $dailyVelocity);

                return [
                    'name' => $name ?: $sku,
                    'sku' => $sku,
                    'stock' => $stock,
                    'daily_velocity' => round($dailyVelocity, 2),
                    'days_until_stockout' => $stock <= 0 ? 0 : $daysLeft,
                    'reorder_quantity' => max(self::threshold($product) * 2, (int) ceil($dailyVelocity * 21)),
                    'priority' => $stock <= 0 || $daysLeft <= 7 ? 'high' : ($daysLeft <= 21 ? 'medium' : 'low'),
                ];
            })
            ->sortBy(fn (array $row) => $row['days_until_stockout'])
            ->take(6)
            ->values()
            ->all();
    }

    private static function marketingSuggestions(array $partner, Collection $orders, Collection $carts): array
    {
        $bestCustomer = $orders->groupBy(fn (array $order) => $order['customer'] ?? 'غير محدد')
            ->map(fn (Collection $items, string $customer) => ['customer' => $customer, 'orders' => $items->count(), 'sales' => self::sales($items)])
            ->sortByDesc('sales')
            ->first();

        return [
            [
                'title' => 'حملة استرجاع السلات',
                'audience' => $carts->count() . ' سلة متروكة',
                'offer' => $carts->isNotEmpty() ? 'خصم 10% لمدة 48 ساعة' : 'احتفظ بها عند ظهور سلات جديدة',
                'expected_impact' => $carts->isNotEmpty() ? 'متوسط' : 'منخفض',
            ],
            [
                'title' => 'حملة العملاء الأفضل',
                'audience' => $bestCustomer['customer'] ?? 'العملاء المتكررون',
                'offer' => 'شحن مجاني أو نقاط ولاء إضافية',
                'expected_impact' => ($bestCustomer['orders'] ?? 0) > 1 ? 'مرتفع' : 'متوسط',
            ],
        ];
    }

    private static function pricingSuggestions(Collection $products, Collection $orders): array
    {
        return $products
            ->take(5)
            ->map(function (array $product) use ($orders) {
                $name = (string) ($product['name'] ?? $product['product'] ?? $product['sku'] ?? '');
                $sales = self::soldUnits($orders, $name, (string) ($product['sku'] ?? ''));
                $price = self::money($product['price'] ?? 0);
                $suggestion = $sales >= 5 ? 'اختبر رفع السعر 3% مع مراقبة التحويل' : 'اختبر خصم بسيط أو Bundle لزيادة الطلب';

                return [
                    'product' => $name,
                    'current_price' => $price,
                    'sold_units' => $sales,
                    'suggestion' => $suggestion,
                ];
            })
            ->values()
            ->all();
    }

    private static function seoSuggestions(Collection $products): array
    {
        return $products
            ->filter(fn (array $product) => blank($product['seo_title'] ?? null) || blank($product['description'] ?? null))
            ->take(5)
            ->map(fn (array $product) => [
                'product' => $product['name'] ?? $product['product'] ?? $product['sku'] ?? '-',
                'missing' => array_values(array_filter([
                    blank($product['description'] ?? null) ? 'description' : null,
                    blank($product['seo_title'] ?? null) ? 'seo_title' : null,
                    blank($product['seo_description'] ?? null) ? 'seo_description' : null,
                ])),
                'action' => route('partner.apps.ai'),
            ])
            ->values()
            ->all();
    }

    private static function automationSuggestions(Collection $lowStock, Collection $carts, Collection $failedPayments): array
    {
        return array_values(array_filter([
            $lowStock->isNotEmpty() ? ['trigger' => 'low_stock', 'action' => 'send_notification', 'label' => 'إذا انخفض المخزون أرسل تنبيه للفريق'] : null,
            $carts->isNotEmpty() ? ['trigger' => 'abandoned_cart', 'action' => 'create_coupon', 'label' => 'إذا زادت السلات المتروكة أنشئ كوبون استرجاع'] : null,
            $failedPayments->isNotEmpty() ? ['trigger' => 'payment_failed', 'action' => 'send_whatsapp', 'label' => 'إذا فشل الدفع أرسل رابط دفع للعميل'] : null,
        ]));
    }

    private static function answer(string $intent, string $prompt, array $smart): string
    {
        return match ($intent) {
            'campaign' => 'أفضل خطوة الآن: ابدأ بحملة قصيرة للسلات المتروكة مع كوبون محدود. عدد السلات الحالي ' . self::alertCount($smart, 'abandoned_carts') . '، واجعل الرسالة مباشرة مع رابط إكمال الشراء.',
            'inventory' => 'المخزون يحتاج متابعة حسب التوقعات. ابدأ بأول منتج في قائمة التنبؤ لأنه الأقرب للنفاد، واقترح كمية إعادة الطلب من جدول Smart Inventory.',
            'product_copy' => 'صياغة مقترحة: ' . Str::limit($prompt, 40, '') . ' بتجربة عملية وجودة موثوقة، مناسب للاستخدام اليومي، مع شحن سريع ودعم من المتجر.',
            'pricing' => 'استخدم اختبار سعر صغير: المنتجات عالية الطلب يمكن رفعها 3%، والمنتجات بطيئة الحركة الأفضل ربطها بعرض Bundle أو خصم مؤقت.',
            default => 'ملخص ذكي: صحة المتجر ' . $smart['health']['score'] . '% (' . $smart['health']['label'] . '). ركز أولاً على أعلى توصية في Smart Recommendations ثم راقب أثرها في التحليلات خلال 7 أيام.',
        };
    }

    private static function assistantActions(string $intent, array $smart): array
    {
        return match ($intent) {
            'campaign' => [['label' => 'إنشاء حملة', 'url' => route('partner.marketing.campaigns')], ['label' => 'السلات المتروكة', 'url' => route('partner.marketing.abandoned-carts')]],
            'inventory' => [['label' => 'فتح المخزون', 'url' => route('partner.products.inventory')]],
            'product_copy' => [['label' => 'فتح أدوات AI', 'url' => route('partner.apps.ai')], ['label' => 'المنتجات', 'url' => route('partner.products')]],
            'pricing' => [['label' => 'تقارير المنتجات', 'url' => route('partner.analytics.products')]],
            default => collect($smart['recommendations'])->take(2)->map(fn (array $row) => ['label' => $row['title'], 'url' => $row['url'] ?? route('partner.dashboard')])->values()->all(),
        };
    }

    private static function intent(string $prompt): string
    {
        $text = Str::lower($prompt);

        return match (true) {
            Str::contains($text, ['حملة', 'كوبون', 'تسويق', 'campaign', 'coupon']) => 'campaign',
            Str::contains($text, ['مخزون', 'نفاد', 'توريد', 'inventory', 'stock']) => 'inventory',
            Str::contains($text, ['وصف', 'عنوان', 'seo', 'منتج']) => 'product_copy',
            Str::contains($text, ['سعر', 'تسعير', 'pricing', 'price']) => 'pricing',
            default => 'analysis',
        };
    }

    private static function productsNeedImprovement(Collection $products, Collection $orders): Collection
    {
        return $products
            ->filter(function (array $product) use ($orders) {
                $name = (string) ($product['name'] ?? $product['product'] ?? '');
                $sku = (string) ($product['sku'] ?? '');
                $sales = self::soldUnits($orders, $name, $sku);

                return blank($product['description'] ?? null)
                    || blank($product['seo_title'] ?? null)
                    || ((int) ($product['views'] ?? 0) > 20 && $sales === 0);
            })
            ->values();
    }

    private static function healthScore(float $currentSales, float $previousSales, Collection $lowStock, Collection $outOfStock, Collection $lateOrders, Collection $carts, Collection $productsNeedWork): int
    {
        $score = 92;

        if ($previousSales > 0 && $currentSales < $previousSales) {
            $score -= min(20, (int) round((1 - ($currentSales / max(1, $previousSales))) * 30));
        }

        $score -= min(20, $outOfStock->count() * 6);
        $score -= min(16, $lowStock->count() * 3);
        $score -= min(16, $lateOrders->count() * 4);
        $score -= min(12, $carts->count() * 2);
        $score -= min(10, $productsNeedWork->count() * 2);

        return max(0, min(100, $score));
    }

    private static function healthDrivers(float $currentSales, float $previousSales, Collection $lowStock, Collection $outOfStock, Collection $lateOrders, Collection $carts): array
    {
        return [
            ['label' => 'المبيعات', 'value' => $previousSales > 0 ? round(($currentSales / max(1, $previousSales)) * 100) . '% من الفترة السابقة' : 'لا توجد فترة مقارنة'],
            ['label' => 'المخزون', 'value' => ($lowStock->count() + $outOfStock->count()) . ' منتج يحتاج متابعة'],
            ['label' => 'العمليات', 'value' => $lateOrders->count() . ' طلب متأخر'],
            ['label' => 'التسويق', 'value' => $carts->count() . ' سلة متروكة'],
        ];
    }

    private static function healthLabel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'ممتاز',
            $score >= 70 => 'جيد',
            $score >= 50 => 'يحتاج تحسين',
            default => 'حرج',
        };
    }

    private static function records(string $section, string $storeId): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $storeId)
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => array_merge($record->payload ?? [], [
                'id' => $record->record_id,
                'store_id' => $record->store_id,
                'status' => $record->status ?? ($record->payload['status'] ?? null),
                'db_created_at' => $record->created_at?->toDateString(),
            ]));
    }

    private static function insideDays(Collection $records, int $days, int $offset): Collection
    {
        $to = now()->subDays($offset)->endOfDay();
        $from = $to->copy()->subDays($days - 1)->startOfDay();

        return $records->filter(fn (array $row) => ($date = self::date($row)) ? $date->betweenIncluded($from, $to) : $offset === 0)->values();
    }

    private static function date(array $row): ?Carbon
    {
        foreach (['created_at', 'date', 'ordered_at', 'db_created_at'] as $key) {
            if (! empty($row[$key])) {
                try {
                    return Carbon::parse($row[$key]);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    private static function sales(Collection $orders): float
    {
        return $orders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0));
    }

    private static function soldUnits(Collection $orders, string $name, string $sku): int
    {
        return $orders->sum(function (array $order) use ($name, $sku) {
            $items = collect($order['items'] ?? []);

            if ($items->isEmpty()) {
                $product = (string) ($order['product'] ?? '');

                return ($name !== '' && Str::contains($product, $name)) || ($sku !== '' && Str::contains($product, $sku)) ? 1 : 0;
            }

            return $items
                ->filter(fn (array $item) => ($name !== '' && ($item['name'] ?? '') === $name) || ($sku !== '' && ($item['sku'] ?? '') === $sku))
                ->sum(fn (array $item) => (int) ($item['quantity'] ?? 1));
        });
    }

    private static function isPending(array $order): bool
    {
        $status = Str::lower((string) ($order['status'] ?? ''));

        return ! Str::contains($status, ['completed', 'delivered', 'cancelled', 'canceled', 'مكتمل', 'تم التسليم', 'ملغي']);
    }

    private static function stock(array $product): int
    {
        return (int) preg_replace('/[^\d-]/', '', (string) ($product['stock'] ?? 0));
    }

    private static function threshold(array $product): int
    {
        return max(1, (int) ($product['low_stock_threshold'] ?? 12));
    }

    private static function money(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private static function periodDays(?Request $request): int
    {
        $days = (int) ($request?->query('period', 30) ?? 30);

        return in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    private static function item(string $key, string $title, string $body, string $priority, ?string $url = null): array
    {
        return compact('key', 'title', 'body', 'priority', 'url');
    }

    private static function alertCount(array $smart, string $key): int
    {
        return collect($smart['alerts'])->where('key', $key)->count();
    }

    private static function recordId(array $partner, string $id): string
    {
        $storeId = (string) ($partner['store_id'] ?? '');

        return Str::startsWith($id, $storeId . '-') ? $id : $storeId . '-' . $id;
    }

    private static function logActivity(array $partner, ?array $actor, string $action, string $subjectType, string $subjectId): void
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
            'properties' => ['source' => 'smart_insights'],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
