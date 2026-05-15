<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PartnerProducts
{
    public const PRODUCT_TYPES = [
        'single' => 'المنتجات الفردية',
        'variable' => 'قسيمة',
        'bundle' => 'المنتجات المجمعة',
        'digital' => 'الملفات الرقمية',
        'flexible' => 'الحزم المرنة',
    ];

    public const PRODUCT_STATUSES = [
        'published' => 'منشور',
        'draft' => 'مسودة',
        'paused' => 'متوقف',
        'low_stock' => 'مخزون منخفض',
        'archived' => 'مؤرشف',
    ];

    public static function ensureStoreData(array $partner): void
    {
        PartnerDashboardSummary::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureSection($partner, 'product_categories', [
            ['name' => 'الأكثر مبيعاً', 'products_count' => count($partner['products'] ?? []), 'status' => 'نشط'],
            ['name' => 'وصل حديثاً', 'products_count' => max(count($partner['products'] ?? []) - 1, 0), 'status' => 'نشط'],
        ]);

        self::ensureSection($partner, 'product_options', [
            ['name' => 'الألوان', 'values' => 'أسود، أبيض، ذهبي', 'products_count' => 8, 'status' => 'نشط'],
            ['name' => 'المقاسات', 'values' => 'S, M, L, XL', 'products_count' => 12, 'status' => 'نشط'],
        ]);

        self::ensureSection($partner, 'product_filters', [
            ['name' => 'السعر', 'values' => 'أقل من 100، 100-300، أعلى من 300', 'status' => 'نشط'],
            ['name' => 'التوفر', 'values' => 'متوفر، منخفض، نافد', 'status' => 'نشط'],
        ]);

        self::ensureSection($partner, 'product_custom_fields', [
            ['name' => 'مادة التصنيع', 'type' => 'نص', 'required' => 'لا', 'status' => 'نشط'],
            ['name' => 'بلد المنشأ', 'type' => 'اختيار', 'required' => 'لا', 'status' => 'نشط'],
        ]);
    }

    public static function list(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        $products = self::productRecords((string) $partner['store_id']);
        $filtered = self::applyFilters($products, $request);
        $perPage = max(1, min(50, (int) $request->query('per_page', 12)));
        $page = max(1, (int) $request->query('page', 1));
        $paginated = $filtered->forPage($page, $perPage)->values();

        return [
            'products' => $paginated->all(),
            'counts' => self::typeCounts($products),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'type' => trim((string) $request->query('type', 'all')),
                'status' => trim((string) $request->query('status', 'all')),
                'category' => trim((string) $request->query('category', 'all')),
                'stock' => trim((string) $request->query('stock', 'all')),
                'view' => trim((string) $request->query('view', 'table')),
            ],
            'typeOptions' => ['all' => 'الكل'] + self::PRODUCT_TYPES,
            'statusOptions' => ['all' => 'كل الحالات'] + self::PRODUCT_STATUSES,
            'summary' => [
                'total' => $products->count(),
                'filtered' => $filtered->count(),
                'published' => $products->where('status_key', 'published')->count(),
                'low_stock' => $products->filter(fn (array $product) => self::isLowStock($product))->count(),
            ],
            'categoryOptions' => ['all' => 'كل التصنيفات'] + $products->pluck('category')->filter()->unique()->mapWithKeys(fn (string $category) => [$category => $category])->all(),
            'stockOptions' => [
                'all' => 'كل المخزون',
                'available' => 'متوفر',
                'low' => 'منخفض',
                'out' => 'نفد',
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filtered->count(),
                'last_page' => max(1, (int) ceil($filtered->count() / $perPage)),
                'from' => $filtered->count() === 0 ? 0 : (($page - 1) * $perPage) + 1,
                'to' => min($filtered->count(), $page * $perPage),
            ],
        ];
    }

    public static function findForStore(array $partner, string $productId): array
    {
        self::ensureStoreData($partner);

        abort_unless(Schema::hasTable('platform_records'), 404);

        $record = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $productId)
            ->first();

        abort_unless($record, 404);

        return self::normalizeProduct($record);
    }

    public static function create(array $partner, array $data, ?string $imagePath = null, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available. Run the platform migrations before creating products.');
        abort_if(SubscriptionManager::limitReached($partner, 'products'), 402, 'Product limit reached for the current subscription plan.');

        $recordId = 'product-' . Str::lower(Str::random(8));
        $payload = self::payload($partner, $recordId, $data, $imagePath);

        $record = PlatformRecord::query()->create([
            'section' => 'products',
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'],
            'payload' => $payload,
        ]);

        self::createInventoryLog($partner, $recordId, (int) ($payload['stock'] ?? 0), 'initial_stock', 'رصيد افتتاحي', $actor);
        self::logActivity($partner, $actor, 'product_created', 'products', $recordId, ['sku' => $payload['sku'] ?? null]);

        return self::normalizeProduct($record);
    }

    public static function update(array $partner, string $productId, array $data, ?string $imagePath = null, ?array $actor = null): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available. Run the platform migrations before updating products.');

        $record = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $productId)
            ->first();

        abort_unless($record, 404);

        $existing = $record->payload ?? [];
        $payload = array_merge($existing, self::payload($partner, $productId, $data, $imagePath ?: ($existing['image'] ?? null)));
        $oldStock = (int) ($existing['stock'] ?? 0);
        $newStock = (int) ($payload['stock'] ?? 0);

        $record->update([
            'status' => $payload['status'],
            'payload' => $payload,
        ]);

        if ($oldStock !== $newStock) {
            self::createInventoryLog($partner, $productId, $newStock - $oldStock, 'product_update', $data['stock_reason'] ?? 'تحديث المنتج', $actor);
        }

        self::logActivity($partner, $actor, 'product_updated', 'products', $productId, ['sku' => $payload['sku'] ?? null]);

        return self::normalizeProduct($record->refresh());
    }

    public static function delete(array $partner, string $productId, ?array $actor = null): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available. Run the platform migrations before deleting products.');

        $record = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $productId)
            ->first();

        abort_unless($record, 404);

        $hasOrders = PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $partner['store_id'])
            ->where(function ($query) use ($productId, $record) {
                $query->where('payload->product_id', $productId)
                    ->orWhere('payload->product_sku', $record->payload['sku'] ?? $productId);
            })
            ->exists();

        if ($hasOrders) {
            $payload = $record->payload ?? [];
            $payload['status_key'] = 'archived';
            $payload['status'] = 'مؤرشف';
            $payload['archived_at'] = now()->toIso8601String();
            $record->update(['status' => 'مؤرشف', 'payload' => $payload]);
            self::logActivity($partner, $actor, 'product_archived', 'products', $productId);

            return self::normalizeProduct($record->refresh());
        }

        $record->delete();
        self::logActivity($partner, $actor, 'product_deleted', 'products', $productId);

        return [];
    }

    public static function pause(array $partner, string $productId, ?array $actor = null): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available. Run the platform migrations before updating products.');

        $record = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $productId)
            ->first();

        abort_unless($record, 404);

        $payload = $record->payload ?? [];
        $payload['status_key'] = 'paused';
        $payload['status'] = self::PRODUCT_STATUSES['paused'];
        $payload['updated_at_human'] = 'الآن';

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'product_paused', 'products', $productId);

        return self::normalizeProduct($record->refresh());
    }

    public static function duplicate(array $partner, string $productId, ?array $actor = null): array
    {
        $product = self::findForStore($partner, $productId);
        $data = $product;
        $data['name'] = ($product['name'] ?? 'منتج') . ' - نسخة';
        $data['sku'] = ($product['sku'] ?? 'SKU') . '-COPY';
        $data['status'] = 'draft';
        $data['type'] = $product['type_key'] ?? 'single';
        $data['price'] = self::money($product['price'] ?? 0);
        $data['compare_at_price'] = self::money($product['compare_at_price'] ?? 0);
        $data['cost_price'] = self::money($product['cost_price'] ?? 0);
        $data['tags'] = implode(', ', $product['tags'] ?? []);
        $data['option_values'] = implode(', ', $product['option_values'] ?? []);

        $copy = self::create($partner, $data, $product['image'] ?? null, $actor);
        self::logActivity($partner, $actor, 'product_duplicated', 'products', $copy['id'], ['source_product_id' => $productId]);

        return $copy;
    }

    public static function bulk(array $partner, array $productIds, array $data, ?array $actor = null): array
    {
        $updated = [];

        foreach ($productIds as $productId) {
            $product = self::findForStore($partner, (string) $productId);
            $payload = array_merge($product, [
                'status' => $data['status'] ?? $product['status_key'],
                'type' => $product['type_key'] ?? 'single',
                'price' => self::money($product['price'] ?? 0),
                'compare_at_price' => self::money($product['compare_at_price'] ?? 0),
                'cost_price' => self::money($product['cost_price'] ?? 0),
                'stock' => $data['stock'] ?? $product['stock'],
                'category' => $data['category'] ?? $product['category'],
                'tags' => implode(', ', $product['tags'] ?? []),
                'option_values' => implode(', ', $product['option_values'] ?? []),
            ]);

            $updated[] = self::update($partner, (string) $productId, $payload, $product['image'] ?? null, $actor);
        }

        return $updated;
    }

    public static function relatedRows(array $partner, string $section): array
    {
        self::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => self::relatedRecord($record))
            ->values()
            ->all();
    }

    public static function createRelated(array $partner, string $section, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        self::assertProductSection($section);

        $recordId = $section . '-' . Str::lower(Str::random(8));
        $payload = self::relatedPayload($section, $data) + ['store_id' => $partner['store_id']];

        $record = PlatformRecord::query()->create([
            'section' => $section,
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'] ?? 'نشط',
            'payload' => $payload,
        ]);

        self::logActivity($partner, $actor, $section . '_created', $section, $recordId);

        return self::relatedRecord($record);
    }

    public static function updateRelated(array $partner, string $section, string $recordId, array $data, ?array $actor = null): array
    {
        self::assertProductSection($section);
        $record = self::recordForStore($partner, $section, $recordId);
        $payload = array_merge($record->payload ?? [], self::relatedPayload($section, $data));

        $record->update(['status' => $payload['status'] ?? $record->status, 'payload' => $payload]);
        self::logActivity($partner, $actor, $section . '_updated', $section, $recordId);

        return self::relatedRecord($record->refresh());
    }

    public static function deleteRelated(array $partner, string $section, string $recordId, ?array $actor = null): void
    {
        self::assertProductSection($section);
        $record = self::recordForStore($partner, $section, $recordId);
        $record->delete();
        self::logActivity($partner, $actor, $section . '_deleted', $section, $recordId);
    }

    public static function inventory(array $partner, Request $request): array
    {
        $products = self::list($partner, $request)['products'];

        return collect($products)->map(fn (array $product) => [
            'id' => $product['id'],
            'SKU' => $product['sku'],
            'المنتج' => $product['name'],
            'الكمية' => $product['stock'],
            'حد التنبيه' => $product['low_stock_threshold'],
            'الحالة' => self::isLowStock($product) ? 'مخزون منخفض' : 'متوفر',
            'store_id' => $product['store_id'],
        ])->values()->all();
    }

    public static function updateInventory(array $partner, string $productId, int $stock, string $reason, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'products', $productId);
        $payload = $record->payload ?? [];
        $oldStock = (int) ($payload['stock'] ?? 0);
        $payload['stock'] = $stock;
        $payload['updated_at_human'] = 'الآن';

        $record->update(['payload' => $payload]);
        self::createInventoryLog($partner, $productId, $stock - $oldStock, 'manual_adjustment', $reason, $actor);
        self::logActivity($partner, $actor, 'inventory_updated', 'products', $productId, ['stock' => $stock, 'reason' => $reason]);

        return self::normalizeProduct($record->refresh());
    }

    public static function inventoryLogs(array $partner): array
    {
        return self::relatedRows($partner, 'inventory_logs');
    }

    public static function exportCsv(array $partner, Request $request): Response
    {
        $rows = self::list($partner, $request)['products'];
        $lines = ["id,name,sku,price,stock,category,status,store_id"];

        foreach ($rows as $product) {
            $lines[] = implode(',', array_map([self::class, 'csv'], [
                $product['id'],
                $product['name'],
                $product['sku'],
                $product['price'],
                $product['stock'],
                $product['category'],
                $product['status'],
                $product['store_id'],
            ]));
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=products-' . $partner['store_id'] . '-' . now()->format('Ymd-His') . '.csv',
        ]);
    }

    public static function importRows(array $partner, array $rows, ?array $actor = null): array
    {
        $created = [];

        foreach ($rows as $row) {
            $created[] = self::create($partner, [
                'name' => $row['name'] ?? $row['product'] ?? 'منتج مستورد',
                'sku' => $row['sku'] ?? 'IMP-' . Str::upper(Str::random(6)),
                'type' => $row['type'] ?? 'single',
                'status' => $row['status'] ?? 'draft',
                'price' => (float) ($row['price'] ?? 0),
                'stock' => (int) ($row['stock'] ?? 0),
                'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? 12),
                'category' => $row['category'] ?? 'مستورد',
                'description' => $row['description'] ?? '',
            ], null, $actor);
        }

        return $created;
    }

    public static function addMedia(array $partner, ?string $productId, string $path, array $meta, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $recordId = 'media-' . Str::lower(Str::random(10));
        $record = PlatformRecord::query()->create([
            'section' => 'product_media',
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'active',
            'payload' => [
                'product_id' => $productId,
                'path' => $path,
                'is_primary' => (bool) ($meta['is_primary'] ?? false),
                'sort_order' => (int) ($meta['sort_order'] ?? 0),
                'mime' => $meta['mime'] ?? null,
                'size' => $meta['size'] ?? null,
                'optimized' => true,
                'store_id' => $partner['store_id'],
            ],
        ]);

        if ($productId) {
            $product = self::recordForStore($partner, 'products', $productId);
            $payload = $product->payload ?? [];
            $media = $payload['media'] ?? [];
            $media[] = ['id' => $recordId, 'path' => $path, 'is_primary' => (bool) ($meta['is_primary'] ?? false)];
            $payload['media'] = $media;
            $payload['image'] = ($meta['is_primary'] ?? false) || empty($payload['image']) ? $path : $payload['image'];
            $product->update(['payload' => $payload]);
        }

        self::logActivity($partner, $actor, 'product_media_uploaded', 'product_media', $recordId);

        return self::relatedRecord($record);
    }

    public static function deleteMedia(array $partner, string $mediaId, ?array $actor = null): void
    {
        $record = self::recordForStore($partner, 'product_media', $mediaId);
        $record->delete();
        self::logActivity($partner, $actor, 'product_media_deleted', 'product_media', $mediaId);
    }

    private static function productRecords(string $storeId): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', $storeId)
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => self::normalizeProduct($record));
    }

    private static function normalizeProduct(PlatformRecord $record): array
    {
        $payload = $record->payload ?? [];
        $stock = (int) ($payload['stock'] ?? 0);
        $threshold = (int) ($payload['low_stock_threshold'] ?? 12);
        $status = $payload['status'] ?? $record->status ?? self::PRODUCT_STATUSES['published'];
        $statusKey = $payload['status_key'] ?? self::statusKeyFromLabel($status);

        if ($stock <= $threshold && $statusKey === 'published') {
            $statusKey = 'low_stock';
            $status = self::PRODUCT_STATUSES['low_stock'];
        }

        return array_merge($payload, [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'name' => $payload['name'] ?? $payload['product'] ?? 'منتج',
            'sku' => $payload['sku'] ?? $record->record_id,
            'type_key' => $payload['type_key'] ?? 'single',
            'type' => self::PRODUCT_TYPES[$payload['type_key'] ?? 'single'] ?? 'المنتجات الفردية',
            'status_key' => $statusKey,
            'status' => $status,
            'price' => $payload['price'] ?? '0 ر.س',
            'stock' => $stock,
            'low_stock_threshold' => $threshold,
            'category' => $payload['category'] ?? 'عام',
            'image' => $payload['image'] ?? null,
            'media' => $payload['media'] ?? [],
            'variants' => $payload['variants'] ?? self::variantsFromOptions($payload),
            'created_at' => $payload['created_at'] ?? $record->created_at?->toDateString(),
            'updated_at_human' => $payload['updated_at_human'] ?? $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function payload(array $partner, string $recordId, array $data, ?string $imagePath): array
    {
        $statusKey = $data['status'] ?? 'published';
        $typeKey = $data['type'] ?? 'single';

        return [
            'id' => $recordId,
            'store_id' => $partner['store_id'],
            'store' => $partner['name'],
            'name' => $data['name'],
            'product' => $data['name'],
            'sku' => $data['sku'],
            'type_key' => $typeKey,
            'type' => self::PRODUCT_TYPES[$typeKey] ?? self::PRODUCT_TYPES['single'],
            'status_key' => $statusKey,
            'status' => self::PRODUCT_STATUSES[$statusKey] ?? self::PRODUCT_STATUSES['published'],
            'price' => self::formatMoney((float) $data['price']),
            'compare_at_price' => isset($data['compare_at_price']) ? self::formatMoney((float) $data['compare_at_price']) : null,
            'cost_price' => isset($data['cost_price']) ? self::formatMoney((float) $data['cost_price']) : null,
            'stock' => (int) $data['stock'],
            'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 12),
            'category' => $data['category'] ?? 'عام',
            'brand' => $data['brand'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'weight' => isset($data['weight']) ? (float) $data['weight'] : null,
            'tags' => collect(explode(',', (string) ($data['tags'] ?? '')))
                ->map(fn (string $tag) => trim($tag))
                ->filter()
                ->values()
                ->all(),
            'option_name' => $data['option_name'] ?? null,
            'option_values' => collect(explode(',', (string) ($data['option_values'] ?? '')))
                ->map(fn (string $value) => trim($value))
                ->filter()
                ->values()
                ->all(),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'visibility' => $data['visibility'] ?? 'visible',
            'track_inventory' => (bool) ($data['track_inventory'] ?? true),
            'allow_backorders' => (bool) ($data['allow_backorders'] ?? false),
            'requires_shipping' => (bool) ($data['requires_shipping'] ?? true),
            'published_at' => $data['published_at'] ?? null,
            'description' => $data['description'] ?? '',
            'image' => $imagePath,
            'media' => $imagePath ? [['id' => 'primary', 'path' => $imagePath, 'is_primary' => true]] : [],
            'variants' => self::variantsFromInput($recordId, $data),
            'created_at' => $data['created_at'] ?? now()->toDateString(),
            'updated_at_human' => 'الآن',
        ];
    }

    private static function applyFilters(Collection $products, Request $request): Collection
    {
        $query = Str::lower(trim((string) $request->query('q', '')));
        $type = trim((string) $request->query('type', 'all'));
        $status = trim((string) $request->query('status', 'all'));
        $category = trim((string) $request->query('category', 'all'));
        $stock = trim((string) $request->query('stock', 'all'));

        return $products
            ->filter(fn (array $product) => $query === '' || Str::contains(Str::lower(json_encode($product, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $product) => $type === 'all' || ($product['type_key'] ?? null) === $type)
            ->filter(fn (array $product) => $status === 'all' || ($product['status_key'] ?? null) === $status)
            ->filter(fn (array $product) => $category === 'all' || ($product['category'] ?? null) === $category)
            ->filter(fn (array $product) => match ($stock) {
                'available' => (int) ($product['stock'] ?? 0) > (int) ($product['low_stock_threshold'] ?? 0),
                'low' => self::isLowStock($product) && (int) ($product['stock'] ?? 0) > 0,
                'out' => (int) ($product['stock'] ?? 0) <= 0,
                default => true,
            })
            ->values();
    }

    private static function typeCounts(Collection $products): array
    {
        $counts = ['all' => $products->count()];

        foreach (self::PRODUCT_TYPES as $key => $label) {
            $counts[$key] = $products->where('type_key', $key)->count();
        }

        return $counts;
    }

    private static function ensureSection(array $partner, string $section, array $rows): void
    {
        if (PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->exists()) {
            return;
        }

        foreach ($rows as $index => $row) {
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => $section . '-' . $partner['store_id'] . '-' . ($index + 1),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $row['status'] ?? null,
                'payload' => $row + ['store_id' => $partner['store_id']],
            ]);
        }
    }

    private static function isLowStock(array $product): bool
    {
        return (int) ($product['stock'] ?? 0) <= (int) ($product['low_stock_threshold'] ?? 12);
    }

    private static function statusKeyFromLabel(string $label): string
    {
        return match (true) {
            Str::contains($label, ['مسودة', 'draft']) => 'draft',
            Str::contains($label, ['متوقف', 'paused']) => 'paused',
            Str::contains($label, ['مؤرشف', 'archived']) => 'archived',
            Str::contains($label, ['منخفض']) => 'low_stock',
            default => 'published',
        };
    }

    private static function recordForStore(array $partner, string $section, string $recordId): PlatformRecord
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $record = PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $recordId)
            ->first();

        abort_unless($record, 404);

        return $record;
    }

    private static function relatedRecord(PlatformRecord $record): array
    {
        return array_merge($record->payload ?? [], [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'status' => $record->status ?? ($record->payload['status'] ?? null),
            'updated_at_human' => $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function assertProductSection(string $section): void
    {
        abort_unless(in_array($section, ['product_categories', 'product_options', 'product_filters', 'product_custom_fields'], true), 404);
    }

    private static function relatedPayload(string $section, array $data): array
    {
        return match ($section) {
            'product_categories' => [
                'name' => $data['name'] ?? 'تصنيف',
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'products_count' => (int) ($data['products_count'] ?? 0),
                'status' => $data['status'] ?? 'نشط',
            ],
            'product_filters' => [
                'name' => $data['name'] ?? 'فلتر',
                'category' => $data['category'] ?? 'كل التصنيفات',
                'values' => $data['values'] ?? '',
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => $data['status'] ?? 'نشط',
            ],
            'product_custom_fields' => [
                'name' => $data['name'] ?? 'حقل مخصص',
                'type' => $data['type'] ?? 'نص',
                'category' => $data['category'] ?? 'كل التصنيفات',
                'required' => $data['required'] ?? 'لا',
                'status' => $data['status'] ?? 'نشط',
            ],
            default => [
                'name' => $data['name'] ?? 'خيار',
                'values' => $data['values'] ?? '',
                'products_count' => (int) ($data['products_count'] ?? 0),
                'status' => $data['status'] ?? 'نشط',
            ],
        };
    }

    private static function createInventoryLog(array $partner, string $productId, int $delta, string $type, string $reason, ?array $actor): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        PlatformRecord::query()->create([
            'section' => 'inventory_logs',
            'record_id' => 'stock-' . Str::lower(Str::random(10)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $type,
            'payload' => [
                'product_id' => $productId,
                'delta' => $delta,
                'type' => $type,
                'reason' => $reason,
                'actor' => $actor['name'] ?? 'Partner',
                'created_at' => now()->format('Y-m-d H:i'),
                'store_id' => $partner['store_id'],
            ],
        ]);
    }

    private static function variantsFromInput(string $recordId, array $data): array
    {
        $optionName = trim((string) ($data['option_name'] ?? ''));
        $values = collect(explode(',', (string) ($data['option_values'] ?? '')))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values();

        if ($optionName === '' || $values->isEmpty()) {
            return [];
        }

        return $values->map(fn (string $value, int $index) => [
            'id' => $recordId . '-variant-' . ($index + 1),
            'option' => $optionName,
            'value' => $value,
            'sku' => ($data['sku'] ?? $recordId) . '-' . Str::upper(Str::slug($value, '')),
            'price' => self::formatMoney((float) ($data['price'] ?? 0)),
            'stock' => (int) ($data['stock'] ?? 0),
            'image' => null,
        ])->all();
    }

    private static function variantsFromOptions(array $payload): array
    {
        if (! empty($payload['variants']) && is_array($payload['variants'])) {
            return $payload['variants'];
        }

        return self::variantsFromInput((string) ($payload['id'] ?? 'product'), [
            'option_name' => $payload['option_name'] ?? '',
            'option_values' => is_array($payload['option_values'] ?? null) ? implode(',', $payload['option_values']) : ($payload['option_values'] ?? ''),
            'sku' => $payload['sku'] ?? '',
            'price' => self::money($payload['price'] ?? 0),
            'stock' => $payload['stock'] ?? 0,
        ]);
    }

    private static function logActivity(array $partner, ?array $actor, string $action, string $subjectType, string $subjectId, array $properties = []): void
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return;
        }

        PlatformActivityLog::query()->create([
            'actor_type' => 'partner',
            'actor_id' => $actor['username'] ?? $actor['email'] ?? null,
            'actor_name' => $actor['name'] ?? 'Partner',
            'role' => $actor['role'] ?? null,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
        ]);
    }

    private static function money(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private static function csv(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && preg_match('/^[=\-+@\t\r]/', $value) === 1) {
            $value = "'" . $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

    private static function formatMoney(float $amount): string
    {
        return number_format($amount) . ' ر.س';
    }
}
