<?php

namespace App\Http\Controllers;

use App\Models\PartnerStore;
use App\Models\PlatformRecord;
use App\Support\PartnerOrders;
use App\Support\PartnerProducts;
use App\Support\PartnerStorefront;
use App\Support\PartnerTenantStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(Request $request, string $slug): View
    {
        $data = $this->storefront($slug, $request);

        return view('storefront.home', $data);
    }

    public function products(Request $request, string $slug): View
    {
        $data = $this->storefront($slug, $request);
        $data['productsPage'] = $this->productsPayload($data['partner'], $request);

        return view('storefront.products', $data);
    }

    public function product(Request $request, string $slug, string $product): View
    {
        $data = $this->storefront($slug, $request);
        $data['product'] = PartnerProducts::findForStore($data['partner'], $product);
        abort_unless($this->isPublicProduct($data['product']), 404);
        $data['relatedProducts'] = $this->publicProducts($data['partner'], $request)
            ->reject(fn (array $row) => ($row['id'] ?? null) === $product)
            ->where('category', $data['product']['category'] ?? null)
            ->take(4)
            ->values()
            ->all();
        $data['crossSellProducts'] = $this->publicProducts($data['partner'], $request)
            ->reject(fn (array $row) => ($row['id'] ?? null) === $product)
            ->reject(fn (array $row) => ($row['category'] ?? null) === ($data['product']['category'] ?? null))
            ->take(4)
            ->values()
            ->all();
        $data['reviews'] = $this->productReviews($data['partner'], $product);

        return view('storefront.product', $data);
    }

    public function categories(Request $request, string $slug): View
    {
        $data = $this->storefront($slug, $request);
        $data['categories'] = $this->categoriesPayload($data['partner']);

        return view('storefront.categories', $data);
    }

    public function cart(Request $request, string $slug): View
    {
        $data = $this->storefront($slug, $request);

        return view('storefront.cart', $data);
    }

    public function checkout(Request $request, string $slug): View
    {
        $data = $this->storefront($slug, $request);

        return view('storefront.checkout', $data);
    }

    public function page(Request $request, string $slug, string $page): View
    {
        $data = $this->storefront($slug, $request);
        $record = $this->publishedPages($data['partner'])
            ->first(fn (array $row) => ($row['slug'] ?? '') === $page);

        abort_unless($record, 404);

        $data['page'] = $record;

        return view('storefront.page', $data);
    }

    public function about(Request $request, string $slug): View
    {
        return $this->page($request, $slug, 'about');
    }

    public function contact(Request $request, string $slug): View
    {
        $data = $this->storefront($slug, $request);

        return view('storefront.contact', $data);
    }

    public function sitemap(Request $request, string $slug): Response
    {
        $data = $this->storefront($slug, $request);
        $base = url('/store/' . $slug);
        $urls = collect([
            [$base, now()->toDateString()],
            [$base . '/products', now()->toDateString()],
            [$base . '/categories', now()->toDateString()],
            [$base . '/about', now()->toDateString()],
            [$base . '/contact', now()->toDateString()],
        ]);

        $productUrls = $this->publicProducts($data['partner'], $request)
            ->map(fn (array $product) => [$base . '/product/' . $product['id'], $product['created_at'] ?? now()->toDateString()]);
        $pageUrls = $this->publishedPages($data['partner'])
            ->reject(fn (array $page) => in_array($page['slug'] ?? '', ['home'], true))
            ->map(fn (array $page) => [$base . '/pages/' . $page['slug'], $page['created_at'] ?? now()->toDateString()]);

        $xml = view('storefront.sitemap', ['urls' => $urls->merge($productUrls)->merge($pageUrls)->values()])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(Request $request, string $slug): Response
    {
        $data = $this->storefront($slug, $request);
        $robots = trim((string) ($data['seo']['robots_txt'] ?? ''));
        $robots = $robots !== '' ? $robots : "User-agent: *\nAllow: /";
        $robots .= "\nSitemap: " . url('/store/' . $slug . '/sitemap.xml') . "\n";

        return response($robots, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function showApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);

        return response()->json([
            'store' => $data['store'],
            'theme' => $data['theme'],
            'settings' => $data['settings'],
            'seo' => $data['seo'],
            'navigation' => $data['navigation'],
            'banners' => $data['banners'],
            'builder' => $data['builder'],
            'builder_sections' => collect($data['builderSections'])
                ->where('visible', true)
                ->values()
                ->all(),
            'featured_products' => $data['featuredProducts'],
            'latest_products' => $data['latestProducts'],
            'categories' => $this->categoriesPayload($data['partner']),
        ]);
    }

    public function productsApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);

        return response()->json($this->productsPayload($data['partner'], $request));
    }

    public function productApi(Request $request, string $slug, string $product): JsonResponse
    {
        $data = $this->storefront($slug, $request);
        $row = PartnerProducts::findForStore($data['partner'], $product);
        abort_unless($this->isPublicProduct($row), 404);

        return response()->json(['store_id' => $data['partner']['store_id'], 'product' => $row]);
    }

    public function categoriesApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);

        return response()->json([
            'store_id' => $data['partner']['store_id'],
            'categories' => $this->categoriesPayload($data['partner']),
        ]);
    }

    public function cartApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);
        $cart = $this->cartPayload($data['partner'], $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
        ]));

        $this->persistCart($data['partner'], $cart, $request);

        return response()->json($cart, 201);
    }

    public function checkoutApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);
        $validated = $request->validate([
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.email' => ['nullable', 'email', 'max:160'],
            'customer.phone' => ['required', 'string', 'max:40'],
            'customer.city' => ['nullable', 'string', 'max:120'],
            'customer.address' => ['nullable', 'string', 'max:240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'shipping_method' => ['nullable', 'string', 'max:80'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = $this->cartPayload($data['partner'], $validated);
        $first = $cart['items'][0];
        $customer = $validated['customer'];

        $order = PartnerOrders::createManual($data['partner'], [
            'customer' => $customer['name'],
            'phone' => $customer['phone'],
            'email' => $customer['email'] ?? null,
            'city' => $customer['city'] ?? null,
            'address' => $customer['address'] ?? null,
            'product_id' => $first['product_id'],
            'product_sku' => $first['sku'] ?? null,
            'item_name' => $first['name'],
            'qty' => $first['qty'],
            'total' => $cart['totals']['total_numeric'],
            'unit_price' => $first['unit_price_numeric'],
            'discount' => $cart['totals']['discount_numeric'],
            'shipping_fee' => $cart['totals']['shipping_numeric'],
            'tax' => $cart['totals']['tax_numeric'],
            'coupon_code' => $validated['coupon_code'] ?? null,
            'payment_status' => ($validated['payment_method'] ?? '') === 'cod' ? 'pending' : 'unpaid',
            'payment_method' => $this->paymentLabel($validated['payment_method'] ?? 'payment_link'),
            'shipping_method' => $this->shippingLabel($validated['shipping_method'] ?? 'standard'),
            'source_channel' => 'storefront',
            'customer_note' => $validated['customer_note'] ?? null,
            'internal_note' => 'Order created from public storefront checkout.',
        ], [
            'name' => 'Storefront Checkout',
            'role' => 'customer',
            'username' => $customer['email'] ?? $customer['phone'],
        ]);

        $this->syncCheckoutOrderPayload($data['partner'], $order['id'], $cart, $validated);

        return response()->json([
            'store_id' => $data['partner']['store_id'],
            'order' => PartnerOrders::findForStore($data['partner'], $order['id']),
            'message' => 'تم إنشاء الطلب وربطه بلوحة التاجر.',
        ], 201);
    }

    public function eventApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:80'],
            'product_id' => ['nullable', 'string', 'max:120'],
            'cart_id' => ['nullable', 'string', 'max:120'],
            'path' => ['nullable', 'string', 'max:240'],
            'metadata' => ['nullable', 'array'],
        ]);

        $record = $this->storefrontRecord($data['partner'], 'storefront_events', 'event-', [
            'event' => Str::slug($validated['event'], '_'),
            'product_id' => $validated['product_id'] ?? null,
            'cart_id' => $validated['cart_id'] ?? null,
            'path' => $validated['path'] ?? $request->headers->get('referer'),
            'metadata' => $validated['metadata'] ?? [],
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 180, ''),
            'created_at' => now()->toDateTimeString(),
        ]);

        return response()->json(['tracked' => true, 'store_id' => $data['partner']['store_id'], 'event' => $record]);
    }

    public function newsletterApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $record = $this->storefrontRecord($data['partner'], 'storefront_newsletter_subscribers', 'newsletter-', [
            'email' => Str::lower($validated['email']),
            'name' => $validated['name'] ?? null,
            'source' => 'storefront',
            'status' => 'subscribed',
            'created_at' => now()->toDateTimeString(),
        ], ['email' => Str::lower($validated['email'])]);

        return response()->json([
            'store_id' => $data['partner']['store_id'],
            'subscriber' => $record,
            'message' => 'تم الاشتراك في النشرة بنجاح.',
        ], 201);
    }

    public function contactApi(Request $request, string $slug): JsonResponse
    {
        $data = $this->storefront($slug, $request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $record = $this->storefrontRecord($data['partner'], 'storefront_contact_messages', 'message-', [
            'name' => $validated['name'],
            'contact' => $validated['contact'],
            'message' => $validated['message'],
            'source' => 'storefront',
            'status' => 'new',
            'created_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'store_id' => $data['partner']['store_id'],
            'message_record' => $record,
            'message' => 'تم استلام رسالتك.',
        ], 201);
    }

    private function storefront(string $slug, Request $request): array
    {
        $partner = $this->resolvePartner($slug, $request);
        PartnerStorefront::ensureStoreData($partner);

        $summary = PartnerStorefront::summary($partner);
        $theme = $summary['currentTheme'] ?? [];
        $settings = PartnerStorefront::storeSettings($partner);
        $seo = PartnerStorefront::seo($partner);
        $navigation = PartnerStorefront::navigation($partner);
        $builder = PartnerStorefront::builder($partner);
        $builderSections = collect(PartnerStorefront::builderSections($partner)['rows'] ?? [])
            ->sortBy(fn (array $row) => (int) ($row['sort_order'] ?? 0))
            ->values()
            ->all();
        $banners = $this->activeBanners($partner);
        $products = $this->publicProducts($partner, $request);

        return [
            'partner' => $partner,
            'store' => $summary['store'],
            'summary' => $summary,
            'theme' => $theme,
            'settings' => $settings,
            'seo' => $seo,
            'navigation' => $navigation,
            'builder' => $builder,
            'builderSections' => $builderSections,
            'banners' => $banners,
            'featuredProducts' => $products->take(8)->values()->all(),
            'bestSellingProducts' => $products->sortByDesc(fn (array $row) => (int) ($row['views'] ?? $row['stock'] ?? 0))->take(8)->values()->all(),
            'latestProducts' => $products->sortByDesc('created_at')->take(8)->values()->all(),
            'trustBadges' => $this->trustBadges($partner),
            'categories' => $this->categoriesPayload($partner),
            'cartEndpoint' => route('api.storefront.cart', ['slug' => $slug]),
            'checkoutEndpoint' => route('api.storefront.checkout', ['slug' => $slug]),
            'eventEndpoint' => route('api.storefront.events', ['slug' => $slug]),
            'slug' => $slug,
        ];
    }

    private function resolvePartner(string $slug, Request $request): array
    {
        $slug = Str::of($slug)->lower()->trim()->toString();
        $host = Str::of($request->getHost())->lower()->toString();
        $candidates = collect([
            $slug,
            Str::startsWith($slug, 'store-') ? $slug : 'store-' . $slug,
        ])->filter()->unique()->values();

        $store = null;
        if (Schema::hasTable('partner_stores')) {
            $store = PartnerStore::query()
                ->whereIn('store_id', $candidates->all())
                ->orWhere('domain', $slug)
                ->orWhere('domain', $host)
                ->orWhere('store_url', 'like', '%' . $slug . '%')
                ->first();
        }

        $partner = $store ? PartnerTenantStore::findPartner($store->store_id) : null;
        $partner ??= PartnerTenantStore::findPartner((string) $candidates->first());
        $partner ??= PartnerTenantStore::findPartner((string) $candidates->last());

        abort_unless($partner, 404);

        return $partner;
    }

    private function productsPayload(array $partner, Request $request): array
    {
        $all = $this->publicProducts($partner, $request);
        $query = Str::lower(trim((string) $request->query('q', '')));
        $category = trim((string) $request->query('category', 'all'));
        $sort = trim((string) $request->query('sort', 'latest'));
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $perPage = max(1, min(48, (int) $request->query('per_page', 12)));
        $page = max(1, (int) $request->query('page', 1));

        $filtered = $all
            ->filter(fn (array $row) => $query === '' || Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $row) => $category === 'all' || ($row['category'] ?? null) === $category)
            ->filter(fn (array $row) => $minPrice === null || $minPrice === '' || $this->money($row['price'] ?? 0) >= (float) $minPrice)
            ->filter(fn (array $row) => $maxPrice === null || $maxPrice === '' || $this->money($row['price'] ?? 0) <= (float) $maxPrice);

        $filtered = match ($sort) {
            'price_asc' => $filtered->sortBy(fn (array $row) => $this->money($row['price'] ?? 0)),
            'price_desc' => $filtered->sortByDesc(fn (array $row) => $this->money($row['price'] ?? 0)),
            'stock' => $filtered->sortByDesc(fn (array $row) => (int) ($row['stock'] ?? 0)),
            default => $filtered->sortByDesc('created_at'),
        };

        return [
            'store_id' => $partner['store_id'],
            'products' => $filtered->forPage($page, $perPage)->values()->all(),
            'filters' => [
                'q' => $query,
                'category' => $category,
                'sort' => $sort,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filtered->count(),
                'last_page' => max(1, (int) ceil($filtered->count() / $perPage)),
            ],
            'categories' => $this->categoriesPayload($partner),
        ];
    }

    private function publicProducts(array $partner, Request $request): Collection
    {
        $productRequest = Request::create($request->path(), 'GET', array_merge($request->query(), ['per_page' => 50]));

        return collect(PartnerProducts::list($partner, $productRequest)['products'] ?? [])
            ->filter(fn (array $row) => $this->isPublicProduct($row))
            ->values();
    }

    private function isPublicProduct(array $product): bool
    {
        return ! in_array($product['status_key'] ?? 'published', ['draft', 'paused', 'archived'], true)
            && ($product['visibility'] ?? 'visible') !== 'hidden';
    }

    private function categoriesPayload(array $partner): array
    {
        return collect(PartnerProducts::relatedRows($partner, 'product_categories'))
            ->map(function (array $category) use ($partner): array {
                $name = $category['name'] ?? 'عام';
                $count = $this->publicProducts($partner, request())->where('category', $name)->count();

                return $category + [
                    'slug' => Str::slug($name) ?: $category['id'],
                    'products_count' => $count ?: (int) ($category['products_count'] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function activeBanners(array $partner): array
    {
        return collect(PartnerStorefront::list($partner, 'storefront_banners', request())['rows'] ?? [])
            ->filter(fn (array $row) => ($row['status_key'] ?? null) === 'active')
            ->sortBy(fn (array $row) => (int) ($row['sort_order'] ?? 0))
            ->values()
            ->all();
    }

    private function publishedPages(array $partner): Collection
    {
        return collect(PartnerStorefront::list($partner, 'storefront_pages', request())['rows'] ?? [])
            ->filter(fn (array $row) => ($row['status_key'] ?? null) === 'published')
            ->values();
    }

    private function cartPayload(array $partner, array $data): array
    {
        $items = collect($data['items'])
            ->map(function (array $item) use ($partner): array {
                $product = PartnerProducts::findForStore($partner, (string) $item['product_id']);
                abort_unless($this->isPublicProduct($product), 404);

                $qty = (int) $item['qty'];
                abort_if((int) ($product['stock'] ?? 0) < $qty && ! (bool) ($product['allow_backorders'] ?? false), 422, 'Product stock is not enough.');
                $unit = $this->money($product['price'] ?? 0);
                $line = $unit * $qty;

                return [
                    'product_id' => $product['id'],
                    'sku' => $product['sku'] ?? null,
                    'name' => $product['name'],
                    'image' => $product['image'] ?? null,
                    'qty' => $qty,
                    'unit_price' => $this->formatMoney($unit),
                    'unit_price_numeric' => $unit,
                    'line_total' => $this->formatMoney($line),
                    'line_total_numeric' => $line,
                ];
            })
            ->values();

        $subtotal = $items->sum('line_total_numeric');
        $discount = $this->couponDiscount($partner, (string) ($data['coupon_code'] ?? ''), $subtotal);
        $shipping = $subtotal > 0 ? 25.0 : 0.0;
        $tax = max(0, ($subtotal - $discount + $shipping) * 0.15);
        $total = max(0, $subtotal - $discount + $shipping + $tax);

        return [
            'store_id' => $partner['store_id'],
            'cart_id' => 'cart-' . Str::lower(Str::random(10)),
            'items' => $items->all(),
            'coupon_code' => $data['coupon_code'] ?? null,
            'totals' => [
                'subtotal' => $this->formatMoney($subtotal),
                'subtotal_numeric' => $subtotal,
                'discount' => $this->formatMoney($discount),
                'discount_numeric' => $discount,
                'shipping' => $this->formatMoney($shipping),
                'shipping_numeric' => $shipping,
                'tax' => $this->formatMoney($tax),
                'tax_numeric' => $tax,
                'total' => $this->formatMoney($total),
                'total_numeric' => $total,
            ],
        ];
    }

    private function persistCart(array $partner, array $cart, Request $request): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        PlatformRecord::query()->create([
            'section' => 'storefront_carts',
            'record_id' => $cart['cart_id'],
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'active',
            'payload' => $cart + [
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 180, ''),
                'created_at' => now()->toDateTimeString(),
            ],
        ]);

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'abandoned_carts', 'store_id' => $partner['store_id'], 'record_id' => 'storefront-' . $cart['cart_id']],
            [
                'partner_id' => $partner['id'] ?? null,
                'status' => 'مفتوحة',
                'payload' => [
                    'customer' => 'زائر المتجر',
                    'phone' => null,
                    'email' => null,
                    'items_count' => count($cart['items'] ?? []),
                    'items' => $cart['items'] ?? [],
                    'total' => $cart['totals']['total'] ?? '0 ر.س',
                    'recovery_action' => 'رسالة استرجاع جاهزة',
                    'recovery_message' => 'نسيت منتجاتك في السلة؟ أكمل طلبك الآن واستفد من عروض المتجر.',
                    'source' => 'storefront',
                    'last_activity' => now()->diffForHumans(),
                    'updated_at' => now()->toDateTimeString(),
                    'store_id' => $partner['store_id'],
                ],
            ]
        );
    }

    private function storefrontRecord(array $partner, string $section, string $prefix, array $payload, array $unique = []): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $attributes = ['section' => $section, 'store_id' => $partner['store_id']];
        $record = null;

        if ($unique !== []) {
            $record = PlatformRecord::query()
                ->where($attributes)
                ->get()
                ->first(function (PlatformRecord $record) use ($unique): bool {
                    $current = $record->payload ?? [];

                    foreach ($unique as $key => $value) {
                        if (($current[$key] ?? null) !== $value) {
                            return false;
                        }
                    }

                    return true;
                });
        }

        if ($record) {
            $merged = array_merge($record->payload ?? [], $payload, ['updated_at' => now()->toDateTimeString()]);
            $record->update(['status' => $merged['status'] ?? $record->status, 'payload' => $merged + ['store_id' => $partner['store_id']]]);

            return $this->normalizeRecord($record->refresh());
        }

        $record = PlatformRecord::query()->create([
            'section' => $section,
            'record_id' => $prefix . Str::lower(Str::random(10)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'] ?? 'new',
            'payload' => $payload + ['store_id' => $partner['store_id']],
        ]);

        return $this->normalizeRecord($record);
    }

    private function normalizeRecord(PlatformRecord $record): array
    {
        return array_merge($record->payload ?? [], [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'status' => $record->status,
        ]);
    }

    private function syncCheckoutOrderPayload(array $partner, string $orderId, array $cart, array $validated): void
    {
        $record = PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $orderId)
            ->first();

        if (! $record) {
            return;
        }

        $payload = $record->payload ?? [];
        $payload['source'] = 'المتجر الإلكتروني';
        $payload['source_channel'] = 'storefront';
        $payload['items'] = collect($cart['items'])->map(fn (array $item) => [
            'product_id' => $item['product_id'],
            'sku' => $item['sku'],
            'name' => $item['name'],
            'qty' => $item['qty'],
            'price' => $item['unit_price'],
            'line_total' => $item['line_total'],
        ])->all();
        $payload['subtotal'] = $cart['totals']['subtotal'];
        $payload['discount'] = $cart['totals']['discount'];
        $payload['shipping_fee'] = $cart['totals']['shipping'];
        $payload['tax'] = $cart['totals']['tax'];
        $payload['total'] = $cart['totals']['total'];
        $payload['timeline'][] = ['label' => 'تم إنشاء الطلب من واجهة المتجر', 'time' => now()->format('Y-m-d H:i'), 'state' => 'done'];
        $record->update(['payload' => $payload]);

        $this->storefrontRecord($partner, 'storefront_events', 'event-', [
            'event' => 'order_created',
            'product_id' => $cart['items'][0]['product_id'] ?? null,
            'cart_id' => $cart['cart_id'] ?? null,
            'path' => '/checkout',
            'metadata' => ['order_id' => $orderId, 'total' => $cart['totals']['total_numeric'] ?? 0],
            'created_at' => now()->toDateTimeString(),
        ]);

        collect($cart['items'])->skip(1)->each(function (array $item) use ($partner, $orderId): void {
            $this->adjustStock($partner, (string) $item['product_id'], -((int) $item['qty']), $orderId);
        });

        $this->persistCart($partner, $cart + ['checkout_order_id' => $orderId], request());
    }

    private function adjustStock(array $partner, string $productId, int $delta, string $orderId): void
    {
        $product = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $productId)
            ->first();

        if (! $product) {
            return;
        }

        $payload = $product->payload ?? [];
        $before = (int) ($payload['stock'] ?? 0);
        $after = max(0, $before + $delta);
        $payload['stock'] = $after;
        $payload['updated_at_human'] = 'الآن';
        $product->update(['payload' => $payload]);

        PlatformRecord::query()->create([
            'section' => 'inventory_logs',
            'record_id' => 'inventory-' . Str::lower(Str::random(10)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'storefront_order_created',
            'payload' => [
                'store_id' => $partner['store_id'],
                'product_id' => $productId,
                'source_id' => $orderId,
                'reason' => 'storefront_order_created',
                'delta' => $delta,
                'before' => $before,
                'after' => $after,
                'actor' => 'Storefront Checkout',
                'created_at' => now()->format('Y-m-d H:i'),
            ],
        ]);
    }

    private function couponDiscount(array $partner, string $couponCode, float $subtotal): float
    {
        if ($couponCode === '' || ! Schema::hasTable('platform_records')) {
            return 0.0;
        }

        $coupon = PlatformRecord::query()
            ->where('store_id', $partner['store_id'])
            ->whereIn('section', ['marketing_coupons', 'coupons'])
            ->get()
            ->first(function (PlatformRecord $record) use ($couponCode): bool {
                $payload = $record->payload ?? [];

                return Str::lower((string) ($payload['code'] ?? $payload['coupon_code'] ?? '')) === Str::lower($couponCode)
                    && ! in_array($payload['status_key'] ?? 'active', ['paused', 'expired', 'inactive'], true);
            });

        if (! $coupon) {
            return 0.0;
        }

        $payload = $coupon->payload ?? [];
        $type = $payload['discount_type'] ?? $payload['type'] ?? 'percentage';
        $value = $this->money($payload['discount_value'] ?? $payload['value'] ?? 0);

        return min($subtotal, $type === 'fixed' ? $value : ($subtotal * ($value / 100)));
    }

    private function productReviews(array $partner, string $productId): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'customer_reviews')
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => $this->normalizeRecord($record))
            ->filter(function (array $review) use ($productId): bool {
                $status = $review['status_key'] ?? $review['status'] ?? 'published';
                $matches = ($review['product_id'] ?? null) === $productId || empty($review['product_id']);

                return $matches && ! in_array($status, ['rejected', 'hidden'], true);
            })
            ->take(6)
            ->values()
            ->all();
    }

    private function trustBadges(array $partner): array
    {
        return [
            ['title' => 'دفع آمن', 'body' => 'طرق دفع محمية ومرتبطة بطلبك.'],
            ['title' => 'شحن موثوق', 'body' => 'تتبع واضح لحالة الشحنة.'],
            ['title' => 'استبدال واسترجاع', 'body' => 'سياسات المتجر تظهر قبل الشراء.'],
            ['title' => 'متجر موثق', 'body' => 'يعمل على منصة Solve باسم ' . ($partner['name'] ?? 'المتجر')],
        ];
    }

    private function paymentLabel(string $method): string
    {
        return match ($method) {
            'cod' => 'الدفع عند الاستلام',
            'bank' => 'تحويل بنكي',
            'card' => 'بطاقة دفع',
            default => 'رابط دفع',
        };
    }

    private function shippingLabel(string $method): string
    {
        return match ($method) {
            'express' => 'شحن سريع',
            'pickup' => 'استلام من الفرع',
            default => 'شحن عادي',
        };
    }

    private function money(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private function formatMoney(float|int $amount): string
    {
        return number_format((float) $amount, 2) . ' ر.س';
    }
}
