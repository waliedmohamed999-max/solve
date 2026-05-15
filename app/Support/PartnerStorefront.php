<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerStorefront
{
    public const PAGE_STATUSES = [
        'published' => 'منشورة',
        'hidden' => 'مخفية',
        'draft' => 'مسودة',
    ];

    public const BANNER_STATUSES = [
        'active' => 'نشط',
        'scheduled' => 'مجدول',
        'paused' => 'متوقف',
    ];

    public static function ensureStoreData(array $partner): void
    {
        PartnerDashboardSummary::ensureStoreData($partner);
        PartnerProducts::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureThemes($partner);
        self::ensurePages($partner);
        self::ensureBanners($partner);
        self::ensureNavigation($partner);
        self::ensureDomain($partner);
        self::ensureSeo($partner);
        self::ensureStoreSettings($partner);
        self::ensureBuilder($partner);
        self::ensureBuilderSections($partner);
    }

    public static function summary(array $partner): array
    {
        self::ensureStoreData($partner);

        $themes = self::records($partner, 'storefront_themes');
        $pages = self::records($partner, 'storefront_pages');
        $banners = self::records($partner, 'storefront_banners');
        $domain = self::domain($partner);
        $seo = self::seo($partner);
        $settings = self::storeSettings($partner);
        $currentTheme = $themes->firstWhere('active', true) ?? $themes->first();
        $seoReady = ! empty($seo['meta_title']) && ! empty($seo['meta_description']) && ! empty($seo['sitemap_enabled']);
        $domainReady = ($domain['dns_status_key'] ?? '') === 'verified' && ($domain['ssl_status_key'] ?? '') === 'active';

        return [
            'store_id' => $partner['store_id'],
            'store' => [
                'name' => $settings['store_name'] ?? $partner['name'],
                'status' => $partner['status'] ?? 'نشط',
                'url' => $domain['current_domain'] ?? $partner['store_url'] ?? null,
                'preview_url' => $currentTheme['preview_url'] ?? ($partner['store_url'] ?? '#'),
            ],
            'cards' => [
                ['label' => 'حالة المتجر', 'value' => $partner['status'] ?? 'نشط', 'hint' => 'تنعكس من لوحة الأدمن'],
                ['label' => 'حالة الدومين', 'value' => $domainReady ? 'مربوط وآمن' : ($domain['dns_status'] ?? 'بانتظار التحقق'), 'hint' => $domain['custom_domain'] ?? $domain['current_domain'] ?? '-'],
                ['label' => 'القالب الحالي', 'value' => $currentTheme['name'] ?? '-', 'hint' => ($currentTheme['style'] ?? 'Light') . ' style'],
                ['label' => 'الصفحات', 'value' => $pages->count(), 'hint' => $pages->where('status_key', 'published')->count() . ' منشورة'],
                ['label' => 'البنرات', 'value' => $banners->count(), 'hint' => $banners->where('status_key', 'active')->count() . ' نشطة'],
                ['label' => 'حالة SEO', 'value' => $seoReady ? 'جاهز' : 'يحتاج تحسين', 'hint' => 'Sitemap و Open Graph'],
            ],
            'currentTheme' => $currentTheme,
            'domain' => $domain,
            'seo' => $seo,
            'settings' => $settings,
            'quickActions' => [
                ['label' => 'تعديل القالب', 'route' => 'partner.storefront.themes'],
                ['label' => 'إضافة صفحة', 'route' => 'partner.storefront.pages'],
                ['label' => 'إضافة بنر', 'route' => 'partner.storefront.banners'],
                ['label' => 'معاينة المتجر', 'url' => $currentTheme['preview_url'] ?? ($partner['store_url'] ?? '#')],
                ['label' => 'ربط دومين', 'route' => 'partner.storefront.domain'],
            ],
            'readiness' => [
                ['label' => 'قالب مفعل', 'done' => (bool) $currentTheme],
                ['label' => 'صفحة رئيسية منشورة', 'done' => $pages->where('slug', 'home')->where('status_key', 'published')->isNotEmpty()],
                ['label' => 'بنر نشط', 'done' => $banners->where('status_key', 'active')->isNotEmpty()],
                ['label' => 'دومين موثق', 'done' => $domainReady],
                ['label' => 'SEO مكتمل', 'done' => $seoReady],
            ],
            'recentPages' => $pages->take(5)->values()->all(),
            'activeBanners' => $banners->where('status_key', 'active')->take(5)->values()->all(),
            'builder' => self::builder($partner),
        ];
    }

    public static function list(array $partner, string $section, Request $request): array
    {
        self::ensureStoreData($partner);
        self::assertSection($section);

        $rows = self::applyFilters(self::records($partner, $section), $request);
        $perPage = max(1, min(50, (int) $request->query('per_page', 12)));
        $page = max(1, (int) $request->query('page', 1));

        return [
            'store_id' => $partner['store_id'],
            'section' => $section,
            'rows' => $rows->forPage($page, $perPage)->values()->all(),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', 'all')),
            ],
            'statusOptions' => ['all' => 'كل الحالات'] + ($section === 'storefront_pages' ? self::PAGE_STATUSES : self::BANNER_STATUSES),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $rows->count(),
                'last_page' => max(1, (int) ceil($rows->count() / $perPage)),
                'from' => $rows->count() === 0 ? 0 : (($page - 1) * $perPage) + 1,
                'to' => min($rows->count(), $page * $perPage),
            ],
            'summary' => [
                'total' => self::records($partner, $section)->count(),
                'filtered' => $rows->count(),
                'active' => $rows->filter(fn (array $row) => in_array($row['status_key'] ?? '', ['active', 'published'], true))->count(),
                'draft' => $rows->where('status_key', 'draft')->count(),
            ],
        ];
    }

    public static function themes(array $partner, Request $request): array
    {
        $payload = self::list($partner, 'storefront_themes', $request);
        $payload['categories'] = self::themeCategories();
        $payload['currentTheme'] = collect(self::records($partner, 'storefront_themes'))
            ->firstWhere('active', true) ?? collect($payload['rows'] ?? [])->first();

        return $payload;
    }

    public static function themeCategories(): array
    {
        return [
            ['key' => 'all', 'label' => 'الكل', 'icon' => 'grid', 'count_hint' => 'كل القوالب'],
            ['key' => 'mens-fashion', 'label' => 'أزياء رجالية', 'icon' => 'shirt', 'count_hint' => 'Fashion'],
            ['key' => 'womens-fashion', 'label' => 'أزياء نسائية', 'icon' => 'sparkles', 'count_hint' => 'Fashion'],
            ['key' => 'perfumes', 'label' => 'عطور', 'icon' => 'gem', 'count_hint' => 'Luxury'],
            ['key' => 'accessories', 'label' => 'إكسسوارات', 'icon' => 'gift', 'count_hint' => 'Soft'],
            ['key' => 'jewelry', 'label' => 'مجوهرات', 'icon' => 'diamond', 'count_hint' => 'Premium'],
            ['key' => 'electronics', 'label' => 'إلكترونيات', 'icon' => 'zap', 'count_hint' => 'Tech'],
            ['key' => 'sports', 'label' => 'رياضة', 'icon' => 'activity', 'count_hint' => 'Dynamic'],
            ['key' => 'home', 'label' => 'أثاث ومنزل', 'icon' => 'home', 'count_hint' => 'Warm'],
            ['key' => 'restaurants', 'label' => 'مطاعم وكافيهات', 'icon' => 'coffee', 'count_hint' => 'Menu'],
            ['key' => 'bakery', 'label' => 'حلويات ومخبوزات', 'icon' => 'cake', 'count_hint' => 'Warm'],
            ['key' => 'supermarket', 'label' => 'سوبرماركت', 'icon' => 'basket', 'count_hint' => 'Retail'],
            ['key' => 'pharmacy', 'label' => 'صيدليات', 'icon' => 'shield', 'count_hint' => 'Clean'],
            ['key' => 'cars', 'label' => 'سيارات', 'icon' => 'truck', 'count_hint' => 'Bold'],
            ['key' => 'digital', 'label' => 'منتجات رقمية', 'icon' => 'monitor', 'count_hint' => 'SaaS'],
            ['key' => 'luxury', 'label' => 'متاجر فاخرة', 'icon' => 'crown', 'count_hint' => 'Luxury'],
            ['key' => 'minimal', 'label' => 'متاجر Minimal', 'icon' => 'minus', 'count_hint' => 'Clean'],
            ['key' => 'dark', 'label' => 'متاجر Dark Mode', 'icon' => 'moon', 'count_hint' => 'Dark'],
        ];
    }

    public static function themeMarketplace(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        $query = Str::lower(trim((string) $request->query('q', '')));
        $category = trim((string) $request->query('category', 'all'));
        $sort = trim((string) $request->query('sort', 'recommended'));
        $installed = collect(self::records($partner, 'storefront_themes'));

        $templates = collect(self::themeMarketplaceRows($partner))
            ->map(function (array $theme) use ($installed): array {
                $storeTheme = $installed->firstWhere('id', $theme['id']);
                $theme['installed'] = (bool) $storeTheme;
                $theme['active'] = (bool) ($storeTheme['active'] ?? false);
                $theme['favorite'] = (bool) ($storeTheme['favorite'] ?? false);
                $theme['status_key'] = $storeTheme['status_key'] ?? ($theme['status_key'] ?? 'available');
                $theme['status'] = $storeTheme['status'] ?? ($theme['status'] ?? 'متاح');

                return $theme;
            })
            ->filter(fn (array $theme) => $category === 'all' || ($theme['category_key'] ?? null) === $category || in_array($category, $theme['related_categories'] ?? [], true))
            ->filter(fn (array $theme) => $query === '' || Str::contains(Str::lower(json_encode($theme, JSON_UNESCAPED_UNICODE)), $query));

        $templates = match ($sort) {
            'speed' => $templates->sortByDesc('speed_score'),
            'conversion' => $templates->sortByDesc('conversion_score'),
            'mobile' => $templates->sortByDesc('mobile_score'),
            'newest' => $templates->sortByDesc('updated_at'),
            default => $templates->sortByDesc('ai_match_score'),
        };

        return [
            'store_id' => $partner['store_id'],
            'filters' => ['q' => $query, 'category' => $category, 'sort' => $sort],
            'categories' => self::themeCategories(),
            'templates' => $templates->values()->all(),
            'stats' => [
                'total' => count(self::themeMarketplaceRows($partner)),
                'visible' => $templates->count(),
                'premium' => collect(self::themeMarketplaceRows($partner))->where('plan', 'pro')->count(),
                'installed' => $installed->where('installed', true)->count(),
            ],
            'recommendation' => self::themeAiRecommendation($partner),
        ];
    }

    public static function publicThemeMarketplace(Request $request): array
    {
        $partner = [
            'store_id' => 'demo',
            'id' => null,
            'name' => 'Solve Demo',
            'store_url' => route('site.home'),
        ];

        $payload = self::themeMarketplace($partner, $request);
        unset($payload['recommendation']);

        return $payload + ['public' => true];
    }

    public static function findTheme(array $partner, string $themeId): array
    {
        self::ensureStoreData($partner);

        $record = self::records($partner, 'storefront_themes')->firstWhere('id', $themeId);
        if ($record) {
            return $record;
        }

        $theme = collect(self::themeMarketplaceRows($partner))->firstWhere('id', $themeId);
        abort_unless($theme, 404);

        return $theme;
    }

    public static function installTheme(array $partner, string $themeId, ?array $actor = null): array
    {
        self::ensureStoreData($partner);

        $existing = PlatformRecord::query()
            ->where('section', 'storefront_themes')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $themeId)
            ->first();

        if ($existing) {
            $payload = array_merge($existing->payload ?? [], [
                'installed' => true,
                'status_key' => ($existing->payload['active'] ?? false) ? 'active' : 'draft',
                'status' => ($existing->payload['active'] ?? false) ? 'مفعل' : 'مثبت كمسودة',
                'updated_at_human' => 'الآن',
            ]);
            $existing->update(['status' => $payload['status'], 'payload' => $payload]);
            $record = $existing;
        } else {
            $theme = collect(self::themeMarketplaceRows($partner))->firstWhere('id', $themeId);
            abort_unless($theme, 404);
            $payload = array_merge($theme, [
                'installed' => true,
                'active' => false,
                'status_key' => 'draft',
                'status' => 'مثبت كمسودة',
                'store_id' => $partner['store_id'],
            ]);

            $record = PlatformRecord::query()->create([
                'section' => 'storefront_themes',
                'record_id' => $themeId,
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $payload['status'],
                'payload' => $payload,
            ]);
        }

        self::logActivity($partner, $actor, 'storefront_theme_installed', 'storefront_themes', $themeId);

        return self::normalize($record->refresh());
    }

    public static function previewTheme(array $partner, string $themeId, Request $request): array
    {
        $theme = self::findTheme($partner, $themeId);
        $products = collect(PartnerProducts::list($partner, request())['products'] ?? [])->take(4)->values()->all();

        return [
            'store_id' => $partner['store_id'],
            'theme' => $theme,
            'device' => $request->input('device', $request->query('device', 'desktop')),
            'preview_url' => $theme['preview_url'] ?? ($partner['store_url'] ?? '#'),
            'demo' => [
                'hero' => $theme['hero'] ?? [],
                'sections' => $theme['sections_included'] ?? [],
                'products' => $products,
                'navigation' => self::navigation($partner)['header_menu'] ?? [],
            ],
        ];
    }

    public static function favoriteTheme(array $partner, string $themeId, ?array $actor = null): array
    {
        $theme = self::installTheme($partner, $themeId, $actor);
        $record = self::recordForStore($partner, 'storefront_themes', $themeId);
        $payload = array_merge($record->payload ?? [], [
            'favorite' => ! (bool) ($record->payload['favorite'] ?? false),
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_theme_favorite_toggled', 'storefront_themes', $themeId, ['favorite' => $payload['favorite']]);

        return self::normalize($record->refresh());
    }

    public static function publishTheme(array $partner, string $themeId, ?array $actor = null): array
    {
        self::installTheme($partner, $themeId, $actor);
        $theme = self::activateTheme($partner, $themeId, $actor);
        self::logActivity($partner, $actor, 'storefront_theme_published', 'storefront_themes', $themeId);

        return $theme;
    }

    public static function rollbackTheme(array $partner, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $themes = self::records($partner, 'storefront_themes');
        $current = $themes->firstWhere('active', true);
        $previous = $themes
            ->where('installed', true)
            ->where('id', '!=', $current['id'] ?? null)
            ->first() ?? $themes->where('id', '!=', $current['id'] ?? null)->first();

        abort_unless($previous && isset($previous['id']), 404);
        $theme = self::activateTheme($partner, (string) $previous['id'], $actor);
        self::logActivity($partner, $actor, 'storefront_theme_rollback', 'storefront_themes', (string) $previous['id']);

        return $theme;
    }

    public static function createThemeReview(array $data): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $payload = [
            'theme_id' => $data['theme_id'] ?? null,
            'rating' => max(1, min(5, (int) ($data['rating'] ?? 5))),
            'review' => trim((string) ($data['review'] ?? '')),
            'name' => trim((string) ($data['name'] ?? 'Merchant')),
            'status' => 'pending',
        ];

        $record = PlatformRecord::query()->create([
            'section' => 'theme_marketplace_reviews',
            'record_id' => 'theme-review-' . Str::lower(Str::random(8)),
            'store_id' => 'marketplace',
            'partner_id' => null,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        return self::normalize($record);
    }

    public static function find(array $partner, string $section, string $recordId): array
    {
        return self::normalize(self::recordForStore($partner, $section, $recordId));
    }

    public static function activateTheme(array $partner, string $themeId, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $target = self::recordForStore($partner, 'storefront_themes', $themeId);

        PlatformRecord::query()
            ->where('section', 'storefront_themes')
            ->where('store_id', $partner['store_id'])
            ->get()
            ->each(function (PlatformRecord $record) use ($target): void {
                $payload = $record->payload ?? [];
                $payload['active'] = $record->id === $target->id;
                $payload['status_key'] = $record->id === $target->id ? 'active' : 'available';
                $payload['status'] = $record->id === $target->id ? 'مفعل' : 'متاح';
                $record->update(['status' => $payload['status'], 'payload' => $payload]);
            });

        self::logActivity($partner, $actor, 'storefront_theme_activated', 'storefront_themes', $themeId);

        return self::normalize($target->refresh());
    }

    public static function customizeTheme(array $partner, string $themeId, array $data, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'storefront_themes', $themeId);
        $payload = array_merge($record->payload ?? [], [
            'primary_color' => $data['primary_color'] ?? ($record->payload['primary_color'] ?? '#6d28d9'),
            'secondary_color' => $data['secondary_color'] ?? ($record->payload['secondary_color'] ?? '#06b6d4'),
            'font' => $data['font'] ?? ($record->payload['font'] ?? 'Tajawal'),
            'header_style' => $data['header_style'] ?? ($record->payload['header_style'] ?? 'compact'),
            'footer_style' => $data['footer_style'] ?? ($record->payload['footer_style'] ?? 'rich'),
            'card_style' => $data['card_style'] ?? ($record->payload['card_style'] ?? 'soft'),
            'button_style' => $data['button_style'] ?? ($record->payload['button_style'] ?? 'rounded'),
            'supports_dark' => (bool) ($data['supports_dark'] ?? ($record->payload['supports_dark'] ?? true)),
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_theme_customized', 'storefront_themes', $themeId);

        return self::normalize($record->refresh());
    }

    public static function themeSettings(array $partner, string $themeId): array
    {
        $theme = self::find($partner, 'storefront_themes', $themeId);

        return [
            'store_id' => $partner['store_id'],
            'theme' => $theme,
            'settings' => [
                'colors' => [
                    'primary' => $theme['primary_color'] ?? '#6d28d9',
                    'secondary' => $theme['secondary_color'] ?? '#06b6d4',
                ],
                'font' => $theme['font'] ?? 'Tajawal',
                'header' => $theme['header_style'] ?? 'compact',
                'footer' => $theme['footer_style'] ?? 'rich',
                'cards' => $theme['card_style'] ?? 'soft',
                'buttons' => $theme['button_style'] ?? 'rounded',
                'dark_mode' => (bool) ($theme['supports_dark'] ?? true),
            ],
        ];
    }

    public static function createPage(array $partner, array $data, ?array $actor = null): array
    {
        return self::create($partner, 'storefront_pages', self::pagePayload($data), $actor);
    }

    public static function updatePage(array $partner, string $pageId, array $data, ?array $actor = null): array
    {
        return self::update($partner, 'storefront_pages', $pageId, self::pagePayload($data), $actor);
    }

    public static function deletePage(array $partner, string $pageId, ?array $actor = null): void
    {
        self::delete($partner, 'storefront_pages', $pageId, $actor);
    }

    public static function createBanner(array $partner, array $data, ?array $actor = null): array
    {
        return self::create($partner, 'storefront_banners', self::bannerPayload($data), $actor);
    }

    public static function updateBanner(array $partner, string $bannerId, array $data, ?array $actor = null): array
    {
        return self::update($partner, 'storefront_banners', $bannerId, self::bannerPayload($data), $actor);
    }

    public static function deleteBanner(array $partner, string $bannerId, ?array $actor = null): void
    {
        self::delete($partner, 'storefront_banners', $bannerId, $actor);
    }

    public static function reorderBanners(array $partner, array $order, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        foreach ($order as $index => $id) {
            $record = self::recordForStore($partner, 'storefront_banners', (string) $id);
            $payload = $record->payload ?? [];
            $payload['sort_order'] = $index + 1;
            $payload['updated_at_human'] = 'الآن';
            $record->update(['payload' => $payload]);
        }

        self::logActivity($partner, $actor, 'storefront_banners_reordered', 'storefront_banners', 'bulk', ['order' => array_values($order)]);

        return self::list($partner, 'storefront_banners', request());
    }

    public static function navigation(array $partner): array
    {
        self::ensureStoreData($partner);

        return self::records($partner, 'storefront_navigation')->first() ?? [];
    }

    public static function updateNavigation(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_navigation');
        $payload = array_merge($record->payload ?? [], [
            'header_menu' => self::menuItems($data['header_menu'] ?? []),
            'footer_menu' => self::menuItems($data['footer_menu'] ?? []),
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_navigation_updated', 'storefront_navigation', $record->record_id);

        return self::normalize($record->refresh());
    }

    public static function domain(array $partner): array
    {
        self::ensureStoreData($partner);

        return self::records($partner, 'storefront_domain')->first() ?? [];
    }

    public static function connectDomain(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_domain');
        $domain = Str::lower(trim((string) ($data['custom_domain'] ?? $data['domain'] ?? '')));
        $payload = array_merge($record->payload ?? [], [
            'custom_domain' => $domain,
            'current_domain' => $domain ?: ($record->payload['current_domain'] ?? $partner['store_url'] ?? null),
            'dns_status_key' => 'pending',
            'dns_status' => 'بانتظار التحقق',
            'ssl_status_key' => 'pending',
            'ssl_status' => 'بانتظار الإصدار',
            'active' => false,
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $payload['dns_status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_domain_connected', 'storefront_domain', $record->record_id, ['domain' => $domain]);

        return self::normalize($record->refresh());
    }

    public static function verifyDomain(array $partner, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_domain');
        $payload = array_merge($record->payload ?? [], [
            'dns_status_key' => 'verified',
            'dns_status' => 'تم التحقق من DNS',
            'ssl_status_key' => 'active',
            'ssl_status' => 'SSL فعال',
            'active' => true,
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $payload['dns_status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_domain_verified', 'storefront_domain', $record->record_id);

        return self::normalize($record->refresh());
    }

    public static function updateDomainStatus(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_domain');
        $active = (bool) ($data['active'] ?? false);
        $payload = array_merge($record->payload ?? [], [
            'active' => $active,
            'status_key' => $active ? 'active' : 'paused',
            'status' => $active ? 'نشط' : 'متوقف',
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_domain_status_updated', 'storefront_domain', $record->record_id, ['active' => $active]);

        return self::normalize($record->refresh());
    }

    public static function seo(array $partner): array
    {
        self::ensureStoreData($partner);

        return self::records($partner, 'storefront_seo')->first() ?? [];
    }

    public static function updateSeo(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_seo');
        $payload = array_merge($record->payload ?? [], [
            'meta_title' => $data['meta_title'] ?? ($record->payload['meta_title'] ?? $partner['name']),
            'meta_description' => $data['meta_description'] ?? ($record->payload['meta_description'] ?? ''),
            'social_image' => $data['social_image'] ?? ($record->payload['social_image'] ?? null),
            'sitemap_enabled' => (bool) ($data['sitemap_enabled'] ?? false),
            'robots_txt' => $data['robots_txt'] ?? ($record->payload['robots_txt'] ?? "User-agent: *\nAllow: /"),
            'open_graph_enabled' => (bool) ($data['open_graph_enabled'] ?? false),
            'speed_score' => (int) ($data['speed_score'] ?? ($record->payload['speed_score'] ?? 92)),
            'index_status' => $data['index_status'] ?? ($record->payload['index_status'] ?? 'جاهز للأرشفة'),
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_seo_updated', 'storefront_seo', $record->record_id);

        return self::normalize($record->refresh());
    }

    public static function storeSettings(array $partner): array
    {
        self::ensureStoreData($partner);

        return self::records($partner, 'storefront_settings')->first() ?? [];
    }

    public static function updateStoreSettings(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_settings');
        $payload = array_merge($record->payload ?? [], [
            'store_name' => $data['store_name'] ?? ($record->payload['store_name'] ?? $partner['name']),
            'logo' => $data['logo'] ?? ($record->payload['logo'] ?? $partner['logo'] ?? 'solve-logo.png'),
            'favicon' => $data['favicon'] ?? ($record->payload['favicon'] ?? 'solve-logo.png'),
            'contact_email' => $data['contact_email'] ?? ($record->payload['contact_email'] ?? $partner['email'] ?? null),
            'contact_phone' => $data['contact_phone'] ?? ($record->payload['contact_phone'] ?? $partner['phone'] ?? null),
            'working_hours' => $data['working_hours'] ?? ($record->payload['working_hours'] ?? 'يوميا 9 ص - 10 م'),
            'social_links' => self::listLines($data['social_links'] ?? ($record->payload['social_links'] ?? [])),
            'language' => $data['language'] ?? ($record->payload['language'] ?? 'ar'),
            'currency' => $data['currency'] ?? ($record->payload['currency'] ?? 'SAR'),
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_settings_updated', 'storefront_settings', $record->record_id);

        return self::normalize($record->refresh());
    }

    public static function builder(array $partner): array
    {
        self::ensureStoreData($partner);

        $record = self::records($partner, 'storefront_builder')->first() ?? [];

        return $record + [
            'store_id' => $partner['store_id'],
            'sections' => self::builderSections($partner)['rows'] ?? [],
            'preview_url' => $partner['store_url'] ?? '#',
        ];
    }

    public static function updateBuilder(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_builder');
        $payload = array_merge($record->payload ?? [], [
            'page' => $data['page'] ?? ($record->payload['page'] ?? 'home'),
            'device' => $data['device'] ?? ($record->payload['device'] ?? 'desktop'),
            'mode' => $data['mode'] ?? ($record->payload['mode'] ?? 'visual'),
            'draft' => array_replace_recursive($record->payload['draft'] ?? [], $data['draft'] ?? []),
            'settings' => array_replace_recursive($record->payload['settings'] ?? [], $data['settings'] ?? []),
            'status_key' => 'draft',
            'status' => 'draft',
            'updated_at_human' => 'now',
        ]);

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_builder_updated', 'storefront_builder', $record->record_id, [
            'page' => $payload['page'],
            'device' => $payload['device'],
        ]);

        return self::normalize($record->refresh()) + ['sections' => self::builderSections($partner)['rows'] ?? []];
    }

    public static function publishBuilder(array $partner, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_builder');
        $sections = self::builderSections($partner)['rows'] ?? [];
        $payload = array_merge($record->payload ?? [], [
            'published_snapshot' => [
                'settings' => $record->payload['settings'] ?? [],
                'draft' => $record->payload['draft'] ?? [],
                'sections' => $sections,
            ],
            'published_at' => now()->toDateTimeString(),
            'status_key' => 'published',
            'status' => 'published',
            'updated_at_human' => 'now',
        ]);

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_builder_published', 'storefront_builder', $record->record_id, [
            'sections_count' => count($sections),
        ]);

        return self::normalize($record->refresh()) + ['sections' => $sections];
    }

    public static function rollbackBuilder(array $partner, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'storefront_builder');
        $snapshot = $record->payload['published_snapshot'] ?? [];
        $payload = array_merge($record->payload ?? [], [
            'settings' => $snapshot['settings'] ?? ($record->payload['settings'] ?? []),
            'draft' => $snapshot['draft'] ?? ($record->payload['draft'] ?? []),
            'status_key' => 'draft',
            'status' => 'draft',
            'updated_at_human' => 'now',
        ]);

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_builder_rollback', 'storefront_builder', $record->record_id);

        return self::normalize($record->refresh()) + ['sections' => self::builderSections($partner)['rows'] ?? []];
    }

    public static function builderSections(array $partner, ?Request $request = null): array
    {
        self::ensureStoreData($partner);
        $request ??= request();
        $rows = self::applyFilters(self::records($partner, 'storefront_sections'), $request)
            ->sortBy(fn (array $row) => (int) ($row['sort_order'] ?? 0))
            ->values();

        return [
            'store_id' => $partner['store_id'],
            'section' => 'storefront_sections',
            'rows' => $rows->values()->all(),
            'summary' => [
                'total' => self::records($partner, 'storefront_sections')->count(),
                'visible' => $rows->where('visible', true)->count(),
                'hidden' => $rows->where('visible', false)->count(),
            ],
        ];
    }

    public static function createBuilderSection(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $payload = self::builderSectionPayload($data);
        $recordId = 'builder-section-' . Str::lower(Str::random(8));

        $record = PlatformRecord::query()->create([
            'section' => 'storefront_sections',
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'],
            'payload' => $payload + ['store_id' => $partner['store_id']],
        ]);

        self::logActivity($partner, $actor, 'storefront_section_created', 'storefront_sections', $recordId, [
            'type' => $payload['type'],
        ]);

        return self::normalize($record);
    }

    public static function updateBuilderSection(array $partner, string $sectionId, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::recordForStore($partner, 'storefront_sections', $sectionId);
        $payload = array_merge($record->payload ?? [], self::builderSectionPayload($data, $record->payload ?? []), [
            'updated_at_human' => 'now',
        ]);

        $record->update(['status' => $payload['status'] ?? $record->status, 'payload' => $payload]);
        self::logActivity($partner, $actor, 'storefront_section_updated', 'storefront_sections', $sectionId, [
            'type' => $payload['type'] ?? null,
        ]);

        return self::normalize($record->refresh());
    }

    public static function reorderBuilderSections(array $partner, array $order, ?array $actor = null): array
    {
        self::ensureStoreData($partner);

        $order = collect($order)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();

        abort_if($order->isEmpty(), 422, 'Section order is required.');

        $records = PlatformRecord::query()
            ->where('section', 'storefront_sections')
            ->where('store_id', $partner['store_id'])
            ->whereIn('record_id', $order->all())
            ->get()
            ->keyBy('record_id');

        abort_unless($records->count() === $order->count(), 404);

        $nextSort = 1;

        foreach ($order as $recordId) {
            $record = $records->get($recordId);
            $payload = array_merge($record->payload ?? [], [
                'sort_order' => $nextSort++,
                'updated_at_human' => 'now',
            ]);

            $record->update(['payload' => $payload]);
        }

        $remaining = PlatformRecord::query()
            ->where('section', 'storefront_sections')
            ->where('store_id', $partner['store_id'])
            ->whereNotIn('record_id', $order->all())
            ->get()
            ->sortBy(fn (PlatformRecord $record) => (int) ($record->payload['sort_order'] ?? 0));

        foreach ($remaining as $record) {
            $payload = array_merge($record->payload ?? [], [
                'sort_order' => $nextSort++,
                'updated_at_human' => 'now',
            ]);

            $record->update(['payload' => $payload]);
        }

        self::logActivity($partner, $actor, 'storefront_sections_reordered', 'storefront_sections', 'bulk', [
            'order' => $order->all(),
        ]);

        return self::builderSections($partner);
    }

    public static function deleteBuilderSection(array $partner, string $sectionId, ?array $actor = null): void
    {
        self::ensureStoreData($partner);
        $record = self::recordForStore($partner, 'storefront_sections', $sectionId);
        $record->delete();
        self::logActivity($partner, $actor, 'storefront_section_deleted', 'storefront_sections', $sectionId);
    }

    private static function create(array $partner, string $section, array $payload, ?array $actor): array
    {
        self::ensureStoreData($partner);
        self::assertMutableSection($section);
        $recordId = self::prefix($section) . '-' . Str::lower(Str::random(8));
        $payload += ['store_id' => $partner['store_id']];

        $record = PlatformRecord::query()->create([
            'section' => $section,
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'] ?? null,
            'payload' => $payload,
        ]);

        self::logActivity($partner, $actor, $section . '_created', $section, $recordId);

        return self::normalize($record);
    }

    private static function update(array $partner, string $section, string $recordId, array $payload, ?array $actor): array
    {
        self::assertMutableSection($section);
        $record = self::recordForStore($partner, $section, $recordId);
        $payload = array_merge($record->payload ?? [], $payload, ['updated_at_human' => 'الآن']);
        $record->update(['status' => $payload['status'] ?? $record->status, 'payload' => $payload]);
        self::logActivity($partner, $actor, $section . '_updated', $section, $recordId);

        return self::normalize($record->refresh());
    }

    private static function delete(array $partner, string $section, string $recordId, ?array $actor): void
    {
        self::assertMutableSection($section);
        $record = self::recordForStore($partner, $section, $recordId);
        $record->delete();
        self::logActivity($partner, $actor, $section . '_deleted', $section, $recordId);
    }

    private static function pagePayload(array $data): array
    {
        $status = $data['status'] ?? 'draft';

        return [
            'title' => $data['title'] ?? 'صفحة جديدة',
            'slug' => self::slug($data['slug'] ?? $data['title'] ?? 'page'),
            'content' => $data['content'] ?? '',
            'seo_title' => $data['seo_title'] ?? ($data['title'] ?? ''),
            'seo_description' => $data['seo_description'] ?? '',
            'preview_url' => $data['preview_url'] ?? '#',
            'status_key' => $status,
            'status' => self::PAGE_STATUSES[$status] ?? self::PAGE_STATUSES['draft'],
        ];
    }

    private static function bannerPayload(array $data): array
    {
        $status = $data['status'] ?? 'active';

        return [
            'title' => $data['title'] ?? 'بنر جديد',
            'image_url' => $data['image_url'] ?? $data['image'] ?? null,
            'link_type' => $data['link_type'] ?? 'url',
            'link_target' => $data['link_target'] ?? '#',
            'placement' => $data['placement'] ?? 'home_hero',
            'sort_order' => (int) ($data['sort_order'] ?? 1),
            'starts_at' => $data['starts_at'] ?? now()->toDateString(),
            'ends_at' => $data['ends_at'] ?? now()->addMonth()->toDateString(),
            'status_key' => $status,
            'status' => self::BANNER_STATUSES[$status] ?? self::BANNER_STATUSES['active'],
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
            ->map(fn (PlatformRecord $record) => self::normalize($record))
            ->sortBy(fn (array $row) => (int) ($row['sort_order'] ?? 0))
            ->values();
    }

    private static function applyFilters(Collection $rows, Request $request): Collection
    {
        $query = Str::lower(trim((string) $request->query('q', '')));
        $status = trim((string) $request->query('status', 'all'));

        return $rows
            ->filter(fn (array $row) => $query === '' || Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $row) => $status === 'all' || ($row['status_key'] ?? null) === $status)
            ->values();
    }

    private static function normalize(PlatformRecord $record): array
    {
        $payload = $record->payload ?? [];

        return array_merge($payload, [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'name' => $payload['name'] ?? $payload['title'] ?? $record->record_id,
            'status' => $payload['status'] ?? $record->status ?? null,
            'status_key' => $payload['status_key'] ?? self::statusKey((string) ($payload['status'] ?? $record->status ?? 'active')),
            'created_at' => $payload['created_at'] ?? $record->created_at?->toDateString(),
            'updated_at_human' => $payload['updated_at_human'] ?? $record->updated_at?->diffForHumans(),
        ]);
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

    private static function singleRecord(array $partner, string $section): PlatformRecord
    {
        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->firstOrFail();
    }

    private static function themeMarketplaceRows(array $partner): array
    {
        $baseUrl = $partner['store_url'] ?? '#';
        $rows = [
            self::themeTemplate('theme-urban-style', 'Urban Style', 'أزياء رجالية', 'mens-fashion', '#0f172a', '#eab308', 'أزياء رجالية عصرية', 'editorial_dark', 'صور موديلات كبيرة مع Lookbook وHero داكن', ['New', 'Free'], ['SEO Optimized', 'Mobile First', 'Quick Checkout', 'Instagram Feed'], ['Hero Editorial', 'Lookbook', 'Featured Products', 'Testimonials'], 94, 91, 95, 88, 'free'),
            self::themeTemplate('theme-luxury-women', 'Luxury Women', 'أزياء نسائية', 'womens-fashion', '#fb7185', '#111827', 'موضة راقية للمرأة العصرية', 'fashion_editorial', 'Hero ناعم، بطاقات منتجات فاخرة، وتنسيق Instagram', ['Popular', 'Premium'], ['Conversion Optimized', 'Video Hero', 'Instagram Feed', 'Dark Mode'], ['Hero Slider', 'New Arrivals', 'Lookbook', 'UGC Reviews'], 91, 96, 94, 93, 'pro'),
            self::themeTemplate('theme-minimal-fashion', 'Minimal Fashion', 'متاجر Minimal', 'minimal', '#f8fafc', '#111827', 'واجهة خفيفة للأزياء اليومية', 'minimal_clean', 'Header مضغوط، مساحات بيضاء، وكروت هادئة', ['Free'], ['Fast Loading', 'SEO Optimized', 'Mobile First'], ['Clean Hero', 'Categories Grid', 'Featured Products'], 98, 93, 97, 84, 'free'),
            self::themeTemplate('theme-streetwear', 'Streetwear', 'أزياء رجالية', 'mens-fashion', '#020617', '#7c3aed', 'ستايل شبابي سريع التحويل', 'streetwear', 'صور جريئة، CTA واضح، وعروض سريعة', ['New'], ['Mega Menu', 'Flash Sale', 'TikTok Feed'], ['Hero Motion', 'Flash Sale', 'Product Slider'], 90, 89, 92, 87, 'pro'),
            self::themeTemplate('theme-luxury-perfume', 'Luxury Perfume', 'عطور', 'perfumes', '#160b1f', '#f4c95d', 'عطور تترك أثراً لا ينسى', 'luxury_dark', 'Dark Luxury UI مع حركة ناعمة وبنرات موسمية', ['Recommended', 'Premium'], ['Dark Mode', 'Video Hero', 'AI Ready', 'Conversion Optimized'], ['Luxury Hero', 'Scent Notes', 'Best Sellers', 'Reviews'], 92, 97, 95, 96, 'pro'),
            self::themeTemplate('theme-arabian-oud', 'Arabian Oud', 'عطور', 'perfumes', '#28150a', '#c084fc', 'فخامة العود والعطور الشرقية', 'oud_luxury', 'تدرجات داكنة، صور قريبة، وثقة عالية', ['Premium'], ['Dark Mode', 'Multi Banner', 'SEO Optimized'], ['Hero Luxury', 'Collections', 'Trust Badges'], 90, 94, 92, 91, 'pro'),
            self::themeTemplate('theme-dark-essence', 'Dark Essence', 'متاجر Dark Mode', 'dark', '#030712', '#a855f7', 'واجهة داكنة فاخرة للمنتجات المميزة', 'dark_essence', 'Dark-first مع بطاقات مرتفعة وفوتر غني', ['Dark'], ['Dark Mode', 'Fast Loading', 'AI Ready'], ['Cinematic Hero', 'Featured Products', 'Newsletter'], 93, 92, 96, 88, 'pro'),
            self::themeTemplate('theme-chic-accessories', 'Accessories Chic', 'إكسسوارات', 'accessories', '#f5e7dc', '#3f2d20', 'إكسسوارات تكمل أناقتك', 'soft_accessories', 'ألوان ناعمة، كروت عائمة، وتصفح بسيط', ['New', 'Free'], ['Mobile First', 'Quick Checkout', 'Instagram Feed'], ['Soft Hero', 'Collections', 'Gift Ideas'], 95, 91, 96, 86, 'free'),
            self::themeTemplate('theme-jewelry-elegant', 'Jewelry Elegant', 'مجوهرات', 'jewelry', '#3b2519', '#f8d7a1', 'لمعان يدوم للأبد', 'jewelry_elegant', 'Hero قريب للمنتج، Typography أنيق، وCTA هادئ', ['Popular'], ['SEO Optimized', 'Conversion Optimized', 'Multi Banner'], ['Closeup Hero', 'Collections', 'Gift Guide'], 91, 96, 93, 92, 'pro'),
            self::themeTemplate('theme-luxury-bags', 'Luxury Bags', 'إكسسوارات', 'accessories', '#fff7ed', '#7c2d12', 'حقائب فاخرة بتجربة Editorial', 'luxury_bags', 'بنرات عريضة، صور نمط حياة، وCards ناعمة', ['Premium'], ['Video Hero', 'Instagram Feed', 'Mobile First'], ['Lifestyle Hero', 'Lookbook', 'Featured Products'], 89, 92, 91, 88, 'pro'),
            self::themeTemplate('theme-tech-store', 'Tech Store', 'إلكترونيات', 'electronics', '#020617', '#38bdf8', 'تكنولوجيا تسبق عصرها', 'tech_modern', 'Mega Menu، مقارنة منتجات، وعروض سريعة', ['Popular', 'Free'], ['Mega Menu', 'Fast Loading', 'Comparison Blocks', 'Quick Checkout'], ['Tech Hero', 'Comparison', 'Flash Sale', 'Product Slider'], 97, 94, 95, 94, 'free'),
            self::themeTemplate('theme-gaming-hub', 'Gaming Hub', 'إلكترونيات', 'electronics', '#111827', '#22c55e', 'متجر ألعاب وملحقات تفاعلي', 'gaming_neon', 'تباين قوي، Sections ديناميكية، Countdown', ['New'], ['Dark Mode', 'Flash Sale', 'TikTok Feed'], ['Gaming Hero', 'Countdown', 'Bundles'], 88, 90, 91, 87, 'pro'),
            self::themeTemplate('theme-gadgets-pro', 'Gadgets Pro', 'إلكترونيات', 'electronics', '#0f172a', '#06b6d4', 'واجهة احترافية للملحقات الذكية', 'gadgets_pro', 'Product comparison وQuick specs', ['Premium'], ['Mega Menu', 'SEO Optimized', 'AI Ready'], ['Specs Hero', 'Comparison', 'Best Sellers'], 93, 93, 94, 90, 'pro'),
            self::themeTemplate('theme-sports-pro', 'Sports Pro', 'رياضة', 'sports', '#0b1220', '#f97316', 'تحدى حدودك', 'sports_motion', 'صور حركة، Sticky CTA، وعروض موسمية', ['New', 'Free'], ['Mobile First', 'Countdown', 'Quick Checkout'], ['Motion Hero', 'Categories', 'Flash Sale'], 94, 88, 95, 86, 'free'),
            self::themeTemplate('theme-fitness-store', 'Fitness Store', 'رياضة', 'sports', '#111827', '#84cc16', 'متجر لياقة سريع ومباشر', 'fitness_clean', 'واجهة عملية للمنتجات والباقات الرياضية', ['Free'], ['Fast Loading', 'Mobile First', 'SEO Optimized'], ['Hero CTA', 'Product Grid', 'Testimonials'], 96, 89, 96, 84, 'free'),
            self::themeTemplate('theme-home-decor', 'Home Decor', 'أثاث ومنزل', 'home', '#292524', '#d6a86a', 'تصميم يليق بجمال منزلك', 'home_warm', 'صور غرف، Collections، وقصص استخدام', ['Popular', 'Free'], ['SEO Optimized', 'Multi Banner', 'Mobile First'], ['Room Hero', 'Collections', 'Image Text', 'FAQ'], 93, 92, 94, 89, 'free'),
            self::themeTemplate('theme-modern-furniture', 'Modern Furniture', 'أثاث ومنزل', 'home', '#f5f5f4', '#44403c', 'أثاث حديث بمساحات منظمة', 'furniture_modern', 'Grid واسع، صور كبيرة، وFooter غني', ['Premium'], ['Fast Loading', 'Conversion Optimized', 'Mega Menu'], ['Hero Gallery', 'Collections', 'Brands'], 91, 93, 92, 87, 'pro'),
        ];

        return array_map(fn (array $row): array => array_merge($row, [
            'preview_url' => $baseUrl,
            'updated_at' => '2026-05-14',
            'updated_at_human' => 'آخر تحديث هذا الأسبوع',
        ]), $rows);
    }

    private static function themeTemplate(string $id, string $name, string $category, string $categoryKey, string $primary, string $secondary, string $headline, string $layout, string $description, array $badges, array $features, array $sections, int $speed, int $seo, int $mobile, int $conversion, string $plan): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'category_key' => $categoryKey,
            'related_categories' => array_values(array_filter([$plan === 'pro' ? 'luxury' : 'minimal', str_contains($layout, 'dark') || str_contains($primary, '02') ? 'dark' : null])),
            'style' => $layout,
            'style_tags' => [$category, $layout, $plan === 'pro' ? 'Premium' : 'Free'],
            'headline' => $headline,
            'description' => $description,
            'hero' => ['title' => $headline, 'subtitle' => $description, 'cta' => 'معاينة القالب'],
            'header_layout' => str_contains($layout, 'tech') ? 'Mega Header' : (str_contains($layout, 'minimal') ? 'Compact Header' : 'Editorial Header'),
            'hero_style' => str_contains($layout, 'dark') || str_contains($layout, 'luxury') ? 'Cinematic Hero' : 'Commerce Hero',
            'product_cards' => str_contains($layout, 'tech') ? 'Comparison Cards' : (str_contains($layout, 'luxury') ? 'Premium Cards' : 'Soft Cards'),
            'typography' => str_contains($layout, 'luxury') ? 'IBM Plex Sans Arabic' : 'Tajawal',
            'banner_style' => str_contains($layout, 'dark') ? 'Dark Overlay Banners' : 'Clean Promotional Banners',
            'navigation' => str_contains($layout, 'tech') ? 'Mega Menu + Search' : 'Simple Menu + Collections',
            'cta_style' => $plan === 'pro' ? 'Bold Gradient CTA' : 'Rounded Solid CTA',
            'footer_layout' => $plan === 'pro' ? 'Rich Multi-column Footer' : 'Clean Footer',
            'homepage_structure' => $sections,
            'mobile_experience' => ['Bottom navigation', 'Sticky cart', 'Fast cards'],
            'sections_included' => $sections,
            'pages_count' => $plan === 'pro' ? 8 : 5,
            'speed_score' => $speed,
            'seo_score' => $seo,
            'mobile_score' => $mobile,
            'conversion_score' => $conversion,
            'ai_match_score' => (int) round(($speed + $seo + $mobile + $conversion) / 4),
            'supports_dark' => str_contains($layout, 'dark') || str_contains($layout, 'luxury') || str_contains($primary, '02'),
            'primary_color' => $primary,
            'secondary_color' => $secondary,
            'font' => str_contains($layout, 'luxury') ? 'IBM Plex Sans Arabic' : 'Tajawal',
            'header_style' => str_contains($layout, 'tech') ? 'mega' : 'editorial',
            'footer_style' => $plan === 'pro' ? 'rich' : 'minimal',
            'card_style' => str_contains($layout, 'tech') ? 'comparison' : 'soft',
            'button_style' => 'rounded',
            'features' => $features,
            'badges' => $badges,
            'plan' => $plan,
            'price' => $plan === 'pro' ? 'Premium' : 'مجاني',
            'status_key' => 'available',
            'status' => 'متاح',
            'installed' => false,
            'favorite' => false,
            'active' => false,
            'marketplace_ready' => true,
        ];
    }

    private static function themeAiRecommendation(array $partner): array
    {
        $intelligence = PartnerThemeIntelligence::overview($partner, request());
        $best = $intelligence['recommendation']['best_preset'] ?? [];
        $industry = $intelligence['context']['industry'] ?? 'متجر عام';

        $category = match (true) {
            Str::contains($industry, 'عطور') => 'perfumes',
            Str::contains($industry, 'أزياء') => 'womens-fashion',
            Str::contains($industry, 'إلكترونيات') => 'electronics',
            Str::contains($industry, 'رياضة') => 'sports',
            Str::contains($industry, 'منزل') => 'home',
            default => 'minimal',
        };

        $recommended = collect(self::themeMarketplaceRows($partner))
            ->where('category_key', $category)
            ->sortByDesc('ai_match_score')
            ->first() ?? collect(self::themeMarketplaceRows($partner))->sortByDesc('ai_match_score')->first();

        return [
            'industry' => $industry,
            'preset' => $best,
            'theme' => $recommended,
            'reason' => $intelligence['recommendation']['reason'] ?? 'تم اختيار القالب بناء على نوع النشاط والمنتجات الحالية.',
            'colors' => $best['colors'] ?? ['primary' => '#111827', 'secondary' => '#6d28d9'],
        ];
    }

    private static function ensureThemes(array $partner): void
    {
        self::ensureSection($partner, 'storefront_themes', [
            ['name' => 'Solve Minimal', 'style' => 'Light', 'active' => true, 'supports_dark' => true, 'primary_color' => '#6d28d9', 'secondary_color' => '#06b6d4', 'font' => 'Tajawal', 'header_style' => 'compact', 'footer_style' => 'rich', 'card_style' => 'soft', 'button_style' => 'rounded', 'preview_url' => $partner['store_url'] ?? '#', 'status_key' => 'active', 'status' => 'مفعل'],
            ['name' => 'Solve Retail', 'style' => 'Marketplace', 'active' => false, 'supports_dark' => true, 'primary_color' => '#111827', 'secondary_color' => '#22c55e', 'font' => 'Tajawal', 'header_style' => 'mega', 'footer_style' => 'columns', 'card_style' => 'compact', 'button_style' => 'pill', 'preview_url' => $partner['store_url'] ?? '#', 'status_key' => 'available', 'status' => 'متاح'],
            ['name' => 'Solve Premium', 'style' => 'Editorial', 'active' => false, 'supports_dark' => false, 'primary_color' => '#4c1d95', 'secondary_color' => '#f59e0b', 'font' => 'Tajawal', 'header_style' => 'centered', 'footer_style' => 'minimal', 'card_style' => 'elevated', 'button_style' => 'square', 'preview_url' => $partner['store_url'] ?? '#', 'status_key' => 'available', 'status' => 'متاح'],
        ]);

        foreach (self::themeMarketplaceRows($partner) as $theme) {
            PlatformRecord::query()->firstOrCreate(
                [
                    'section' => 'storefront_themes',
                    'store_id' => $partner['store_id'],
                    'record_id' => $theme['id'],
                ],
                [
                    'partner_id' => $partner['id'] ?? null,
                    'status' => $theme['status'],
                    'payload' => $theme + ['store_id' => $partner['store_id']],
                ]
            );
        }
    }

    private static function ensurePages(array $partner): void
    {
        self::ensureSection($partner, 'storefront_pages', [
            ['title' => 'الرئيسية', 'slug' => 'home', 'content' => 'واجهة المتجر الرئيسية مرتبطة بالمنتجات والبنرات.', 'seo_title' => $partner['name'] ?? 'Solve Store', 'seo_description' => 'تسوق منتجات المتجر مباشرة.', 'status' => 'published'],
            ['title' => 'من نحن', 'slug' => 'about', 'content' => 'صفحة تعريفية عن المتجر وفريقه.', 'seo_title' => 'من نحن', 'seo_description' => 'تعرف على المتجر.', 'status' => 'published'],
            ['title' => 'سياسة الاستبدال والاسترجاع', 'slug' => 'returns-policy', 'content' => 'سياسة واضحة لعمليات الاسترجاع والاستبدال.', 'seo_title' => 'سياسة الاسترجاع', 'seo_description' => 'تفاصيل الاسترجاع والاستبدال.', 'status' => 'draft'],
        ]);
    }

    private static function ensureBanners(array $partner): void
    {
        self::ensureSection($partner, 'storefront_banners', [
            ['title' => 'عرض الموسم', 'image_url' => 'services/banner-storefront.svg', 'link_type' => 'category', 'link_target' => 'featured', 'placement' => 'home_hero', 'sort_order' => 1, 'status' => 'active'],
            ['title' => 'منتجات جديدة', 'image_url' => 'services/banner-products.svg', 'link_type' => 'url', 'link_target' => '/products', 'placement' => 'home_secondary', 'sort_order' => 2, 'status' => 'scheduled'],
        ]);
    }

    private static function ensureNavigation(array $partner): void
    {
        self::ensureSection($partner, 'storefront_navigation', [[
            'name' => 'القوائم الرئيسية',
            'header_menu' => [
                ['label' => 'الرئيسية', 'url' => '/', 'visible' => true, 'children' => []],
                ['label' => 'المنتجات', 'url' => '/products', 'visible' => true, 'children' => []],
                ['label' => 'من نحن', 'url' => '/pages/about', 'visible' => true, 'children' => []],
            ],
            'footer_menu' => [
                ['label' => 'سياسة الاسترجاع', 'url' => '/pages/returns-policy', 'visible' => true, 'children' => []],
                ['label' => 'تواصل معنا', 'url' => '/contact', 'visible' => true, 'children' => []],
            ],
            'status' => 'نشط',
        ]]);
    }

    private static function ensureDomain(array $partner): void
    {
        $domain = $partner['domain'] ?? parse_url((string) ($partner['store_url'] ?? ''), PHP_URL_HOST) ?: ($partner['store_id'] . '.solve.sa');
        self::ensureSection($partner, 'storefront_domain', [[
            'name' => 'الدومين',
            'current_domain' => $domain,
            'custom_domain' => $domain,
            'dns_status_key' => 'verified',
            'dns_status' => 'تم التحقق من DNS',
            'ssl_status_key' => 'active',
            'ssl_status' => 'SSL فعال',
            'active' => true,
            'instructions' => ['A record إلى 127.0.0.1', 'CNAME www إلى stores.solve.sa'],
            'status' => 'نشط',
        ]]);
    }

    private static function ensureSeo(array $partner): void
    {
        self::ensureSection($partner, 'storefront_seo', [[
            'name' => 'SEO',
            'meta_title' => $partner['name'] ?? 'Solve Store',
            'meta_description' => 'متجر إلكتروني احترافي يعمل على منصة Solve.',
            'social_image' => 'solve-logo.png',
            'sitemap_enabled' => true,
            'robots_txt' => "User-agent: *\nAllow: /",
            'open_graph_enabled' => true,
            'speed_score' => 94,
            'index_status' => 'جاهز للأرشفة',
            'status' => 'جاهز',
        ]]);
    }

    private static function ensureStoreSettings(array $partner): void
    {
        self::ensureSection($partner, 'storefront_settings', [[
            'name' => 'إعدادات المتجر',
            'store_name' => $partner['name'] ?? 'Solve Store',
            'logo' => $partner['logo'] ?? 'solve-logo.png',
            'favicon' => 'solve-logo.png',
            'contact_email' => $partner['email'] ?? null,
            'contact_phone' => $partner['phone'] ?? null,
            'working_hours' => 'يوميا 9 ص - 10 م',
            'social_links' => ['https://instagram.com/solve'],
            'language' => 'ar',
            'currency' => 'SAR',
            'status' => 'نشط',
        ]]);
    }

    private static function ensureBuilder(array $partner): void
    {
        self::ensureSection($partner, 'storefront_builder', [[
            'name' => 'Solve Visual Builder',
            'page' => 'home',
            'mode' => 'visual',
            'device' => 'desktop',
            'settings' => [
                'global_colors' => ['primary' => '#6d28d9', 'secondary' => '#06b6d4', 'accent' => '#feee00'],
                'typography' => ['font' => 'Tajawal', 'scale' => 'comfortable'],
                'spacing' => ['section' => 72, 'card_radius' => 24],
                'buttons' => ['style' => 'rounded', 'shadow' => true],
            ],
            'draft' => [
                'autosave' => true,
                'layout' => 'commerce-home',
                'updated_by_builder' => true,
            ],
            'published_snapshot' => [],
            'preview_url' => $partner['store_url'] ?? '#',
            'status_key' => 'draft',
            'status' => 'draft',
        ]]);
    }

    private static function ensureBuilderSections(array $partner): void
    {
        self::ensureSection($partner, 'storefront_sections', [
            ['type' => 'announcement', 'title' => 'Announcement Bar', 'placement' => 'top', 'sort_order' => 1, 'visible' => true, 'settings' => ['text' => 'خصم 20% لفترة محدودة', 'cta' => 'تسوق الآن'], 'status' => 'active'],
            ['type' => 'header', 'title' => 'Header', 'placement' => 'header', 'sort_order' => 2, 'visible' => true, 'settings' => ['sticky' => true, 'search' => true, 'icons' => true], 'status' => 'active'],
            ['type' => 'hero', 'title' => 'Hero Banner', 'placement' => 'home', 'sort_order' => 3, 'visible' => true, 'settings' => ['source' => 'active_banner', 'layout' => 'split', 'cta' => 'تسوق الآن'], 'status' => 'active'],
            ['type' => 'trust_bar', 'title' => 'Trust Badges', 'placement' => 'home', 'sort_order' => 4, 'visible' => true, 'settings' => ['items' => ['شحن سريع', 'دفع آمن', 'استرجاع سهل', 'دعم 24/7']], 'status' => 'active'],
            ['type' => 'categories_grid', 'title' => 'Categories Grid', 'placement' => 'home', 'sort_order' => 5, 'visible' => true, 'settings' => ['source' => 'store_categories', 'limit' => 8], 'status' => 'active'],
            ['type' => 'featured_products', 'title' => 'Featured Products', 'placement' => 'home', 'sort_order' => 6, 'visible' => true, 'settings' => ['source' => 'featured', 'limit' => 8], 'status' => 'active'],
            ['type' => 'offers_banner', 'title' => 'Offers Banner', 'placement' => 'home', 'sort_order' => 7, 'visible' => true, 'settings' => ['source' => 'promotions', 'style' => 'wide'], 'status' => 'active'],
            ['type' => 'newsletter', 'title' => 'Newsletter', 'placement' => 'home', 'sort_order' => 8, 'visible' => true, 'settings' => ['headline' => 'اشترك في النشرة', 'incentive' => 'العروض والكوبونات الجديدة'], 'status' => 'active'],
            ['type' => 'footer', 'title' => 'Footer', 'placement' => 'footer', 'sort_order' => 9, 'visible' => true, 'settings' => ['columns' => 4, 'payment_badges' => true, 'social' => true], 'status' => 'active'],
        ]);
    }

    private static function ensureSection(array $partner, string $section, array $rows): void
    {
        if (PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->exists()) {
            return;
        }

        foreach ($rows as $index => $row) {
            $payload = match ($section) {
                'storefront_pages' => self::pagePayload($row),
                'storefront_banners' => self::bannerPayload($row),
                'storefront_sections' => self::builderSectionPayload($row),
                default => $row,
            };

            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => $section . '-' . $partner['store_id'] . '-' . ($index + 1),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $payload['status'] ?? $row['status'] ?? null,
                'payload' => $payload + ['store_id' => $partner['store_id']],
            ]);
        }
    }

    private static function assertSection(string $section): void
    {
        abort_unless(in_array($section, [
            'storefront_themes',
            'storefront_pages',
            'storefront_banners',
            'storefront_navigation',
            'storefront_domain',
            'storefront_seo',
            'storefront_settings',
            'storefront_builder',
            'storefront_sections',
        ], true), 404);
    }

    private static function assertMutableSection(string $section): void
    {
        abort_unless(in_array($section, ['storefront_pages', 'storefront_banners', 'storefront_sections'], true), 404);
    }

    private static function prefix(string $section): string
    {
        return match ($section) {
            'storefront_pages' => 'page',
            'storefront_banners' => 'banner',
            'storefront_sections' => 'builder-section',
            default => 'storefront',
        };
    }

    private static function builderSectionPayload(array $data, array $current = []): array
    {
        $status = $data['status'] ?? ($current['status_key'] ?? 'active');

        return [
            'type' => $data['type'] ?? ($current['type'] ?? 'custom'),
            'title' => $data['title'] ?? ($current['title'] ?? 'Section'),
            'placement' => $data['placement'] ?? ($current['placement'] ?? 'home'),
            'sort_order' => (int) ($data['sort_order'] ?? ($current['sort_order'] ?? 1)),
            'visible' => (bool) ($data['visible'] ?? ($current['visible'] ?? true)),
            'settings' => array_replace_recursive($current['settings'] ?? [], $data['settings'] ?? []),
            'responsive' => array_replace_recursive($current['responsive'] ?? [], $data['responsive'] ?? []),
            'status_key' => $status,
            'status' => $status,
        ];
    }

    private static function statusKey(string $status): string
    {
        $status = Str::lower($status);

        return match (true) {
            Str::contains($status, ['مسودة', 'draft']) => 'draft',
            Str::contains($status, ['مخفية', 'hidden']) => 'hidden',
            Str::contains($status, ['منشورة', 'published']) => 'published',
            Str::contains($status, ['مجدول', 'scheduled']) => 'scheduled',
            Str::contains($status, ['متوقف', 'paused']) => 'paused',
            Str::contains($status, ['متاح', 'available']) => 'available',
            default => 'active',
        };
    }

    private static function slug(string $value): string
    {
        $slug = preg_replace('/[^\p{Arabic}A-Za-z0-9\-\s]/u', '', trim($value));
        $slug = preg_replace('/[\s\-]+/u', '-', (string) $slug);

        return trim((string) $slug, '-') ?: Str::lower(Str::random(8));
    }

    private static function menuItems(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return array_values($decoded);
        }

        return collect(explode("\n", (string) $value))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line): array {
                [$label, $url] = array_pad(explode('|', $line, 2), 2, '#');

                return ['label' => trim($label), 'url' => trim($url), 'visible' => true, 'children' => []];
            })
            ->values()
            ->all();
    }

    private static function listLines(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        return collect(explode("\n", (string) $value))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
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
