<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerThemeIntelligence
{
    public static function overview(array $partner, Request $request): array
    {
        PartnerStorefront::ensureStoreData($partner);

        $context = self::storeContext($partner);
        $presets = collect(self::industryPresets());
        $matched = self::matchPresets($context, $presets);
        $analytics = self::themeAnalytics($partner);
        $rankedThemes = self::rankThemes($partner, $matched, $analytics);
        $season = self::currentSeason();
        $autoStyle = self::autoStyle($partner);
        $bannerGeneration = self::generateBanners($partner, ['season' => $season]);
        $conversionEngine = self::conversionEngine($context, $analytics);

        return [
            'store_id' => $partner['store_id'],
            'context' => $context,
            'matching' => [
                'analyzed' => [
                    'activity' => $context['industry'],
                    'products' => $context['products_sample'],
                    'colors' => $context['dominant_colors'],
                    'audience' => $context['target_audience'],
                ],
                'best_theme' => $rankedThemes[0] ?? null,
                'best_preset' => $matched->first(),
                'layout' => $matched->first()['layout'] ?? 'minimal',
                'hero' => self::heroTitle($context, $matched->first() ?? [], ''),
                'sections' => self::homepageSections($context, $matched->first() ?? self::industryPresets()[0], $season),
            ],
            'recommendation' => [
                'headline' => self::headline($context),
                'best_preset' => $matched->first(),
                'confidence' => $matched->first()['match_score'] ?? 0,
                'reason' => self::matchReason($context, $matched->first()),
                'next_action' => 'طبّق المقترح ثم راقب Conversion خلال 7 أيام.',
            ],
            'presets' => $matched->values()->all(),
            'ranked_themes' => $rankedThemes,
            'generated_banners' => self::bannerIdeas($context, $season),
            'auto_styling' => $autoStyle,
            'banner_generation' => $bannerGeneration,
            'dynamic_homepage' => self::dynamicHomepage($context, $season),
            'conversion_engine' => $conversionEngine,
            'conversion_recommendations' => self::conversionRecommendations($context, $analytics),
            'analytics' => $analytics,
            'theme_analytics' => self::analyticsSnapshot($partner),
            'marketplace_ranking' => [
                'basis' => ['conversion_rate', 'mobile_score', 'speed_score', 'sales_impact', 'engagement_score'],
                'top' => collect($rankedThemes)->take(5)->values()->all(),
            ],
            'api' => [
                'overview' => route('api.partner.themes.intelligence'),
                'generate' => route('api.partner.themes.generate'),
                'apply_preset' => route('api.partner.themes.apply-preset'),
                'auto_style' => route('api.partner.themes.auto-style'),
                'generate_banners' => route('api.partner.themes.generate-banners'),
                'analytics' => route('api.partner.themes.analytics'),
                'ranking' => route('api.partner.themes.ranking'),
            ],
        ];
    }

    public static function generate(array $partner, array $data, ?array $actor = null): array
    {
        PartnerStorefront::ensureStoreData($partner);

        $prompt = trim((string) ($data['prompt'] ?? ''));
        $context = self::storeContext($partner);
        $preset = self::bestPresetForPrompt($prompt, $context);
        $season = self::currentSeason();

        $generated = [
            'id' => 'ai-theme-' . Str::lower(Str::random(8)),
            'store_id' => $partner['store_id'],
            'prompt' => $prompt,
            'preset_key' => $preset['key'],
            'name' => 'Solve AI - ' . $preset['name'],
            'colors' => $preset['colors'],
            'font' => $preset['font'],
            'layout' => $preset['layout'],
            'hero' => [
                'title' => self::heroTitle($context, $preset, $prompt),
                'subtitle' => self::heroSubtitle($context, $preset),
                'cta' => $preset['cta'],
                'style' => $preset['hero_style'],
            ],
            'sections' => self::homepageSections($context, $preset, $season),
            'banners' => self::bannerIdeas($context, $season),
            'product_cards' => $preset['product_cards'],
            'mobile' => $preset['mobile'],
            'conversion_plan' => self::conversionRecommendations($context, self::themeAnalytics($partner)),
            'created_at_human' => 'الآن',
        ];

        self::storeIntelligenceRecord($partner, 'generated_theme', $generated, $actor);

        return $generated;
    }

    public static function autoStyle(array $partner, ?array $actor = null): array
    {
        PartnerStorefront::ensureStoreData($partner);

        $context = self::storeContext($partner);
        $preset = self::matchPresets($context, collect(self::industryPresets()))->first();
        $palette = $context['dominant_colors'];

        $payload = [
            'store_id' => $partner['store_id'],
            'source' => 'products + categories + storefront behavior',
            'industry' => $context['industry'],
            'extracted_palette' => [
                'primary' => $palette[0] ?? '#111827',
                'secondary' => $palette[1] ?? '#6d28d9',
                'accent' => $palette[2] ?? '#f5f3ff',
            ],
            'recommended_branding' => [
                'font' => $preset['font'] ?? 'Tajawal',
                'header_style' => $preset['header_style'] ?? 'compact',
                'footer_style' => $preset['footer_style'] ?? 'rich',
                'card_style' => $preset['card_style'] ?? 'soft',
                'button_style' => $preset['button_style'] ?? 'rounded',
                'hero_style' => $preset['hero_style'] ?? 'commerce',
            ],
            'css_variables' => [
                '--store-primary' => $palette[0] ?? '#111827',
                '--store-secondary' => $palette[1] ?? '#6d28d9',
                '--store-accent' => $palette[2] ?? '#f5f3ff',
                '--store-radius' => str_contains($preset['layout'] ?? '', 'minimal') ? '18px' : '24px',
                '--store-shadow' => str_contains($preset['layout'] ?? '', 'dark') ? '0 24px 80px rgba(15, 23, 42, .35)' : '0 18px 60px rgba(15, 23, 42, .10)',
            ],
            'ui_suggestions' => self::autoStyleSuggestions($context, $preset ?? []),
            'product_signals' => [
                'sample' => $context['products_sample'],
                'categories' => $context['categories'],
                'catalog_depth' => $context['signals']['catalog_depth'] ?? null,
            ],
        ];

        if ($actor) {
            self::storeIntelligenceRecord($partner, 'auto_store_styling', $payload, $actor);
        }

        return $payload;
    }

    public static function generateBanners(array $partner, array $data = [], ?array $actor = null): array
    {
        PartnerStorefront::ensureStoreData($partner);

        $context = self::storeContext($partner);
        $season = trim((string) ($data['season'] ?? self::currentSeason())) ?: self::currentSeason();
        $banners = array_merge(self::bannerIdeas($context, $season), self::seasonalBannerSet($context));

        $payload = [
            'store_id' => $partner['store_id'],
            'season' => $season,
            'industry' => $context['industry'],
            'count' => count($banners),
            'banners' => $banners,
            'publish_rules' => [
                'hero' => 'استخدم أول بنر في الصفحة الرئيسية مع CTA واضح.',
                'secondary' => 'استخدم بنر العروض قبل المنتجات المميزة.',
                'mobile' => 'اختصر النص إلى سطرين وضع زر الشراء في المنتصف.',
            ],
        ];

        if ($actor) {
            self::storeIntelligenceRecord($partner, 'ai_banner_generation', $payload, $actor);
        }

        return $payload;
    }

    public static function analyticsSnapshot(array $partner): array
    {
        PartnerStorefront::ensureStoreData($partner);

        $context = self::storeContext($partner);
        $analytics = self::themeAnalytics($partner);
        $matched = self::matchPresets($context, collect(self::industryPresets()));

        return [
            'store_id' => $partner['store_id'],
            'current' => $analytics,
            'themes' => self::rankThemes($partner, $matched, $analytics),
            'watchlist' => [
                ['metric' => 'conversion_rate', 'value' => $analytics['conversion_rate'], 'target' => 4.5],
                ['metric' => 'mobile_score', 'value' => $analytics['mobile_score'], 'target' => 92],
                ['metric' => 'speed_score', 'value' => $analytics['speed_score'], 'target' => 94],
                ['metric' => 'engagement_score', 'value' => $analytics['engagement_score'], 'target' => 78],
            ],
            'sales_impact_summary' => self::salesImpactSummary($analytics),
        ];
    }

    public static function marketplaceRanking(array $partner): array
    {
        PartnerStorefront::ensureStoreData($partner);

        $context = self::storeContext($partner);
        $analytics = self::themeAnalytics($partner);
        $matched = self::matchPresets($context, collect(self::industryPresets()));
        $ranked = collect(self::rankThemes($partner, $matched, $analytics));

        return [
            'store_id' => $partner['store_id'],
            'basis' => [
                'ai_match_score' => 'مدى توافق القالب مع النشاط والكتالوج',
                'conversion_score' => 'تأثير متوقع على الشراء',
                'speed_score' => 'سرعة القالب',
                'mobile_score' => 'جاهزية الموبايل',
            ],
            'most_used' => $ranked->sortByDesc('installed')->values()->take(5)->all(),
            'highest_conversion' => $ranked->sortByDesc(fn (array $theme) => $theme['conversion_score'] ?? $theme['analytics']['conversion_rate'] ?? 0)->values()->take(5)->all(),
            'fastest' => $ranked->sortByDesc(fn (array $theme) => $theme['speed_score'] ?? 0)->values()->take(5)->all(),
            'recommended' => $ranked->take(8)->values()->all(),
        ];
    }

    public static function applyPreset(array $partner, string $presetKey, ?array $actor = null): array
    {
        $preset = collect(self::industryPresets())->firstWhere('key', $presetKey);
        abort_unless($preset, 404);

        $themes = collect(PartnerStorefront::themes($partner, request())['rows'] ?? []);
        $theme = $themes->firstWhere('active', true) ?? $themes->first();
        abort_unless($theme && isset($theme['id']), 404);

        $updated = PartnerStorefront::customizeTheme($partner, (string) $theme['id'], [
            'primary_color' => $preset['colors']['primary'],
            'secondary_color' => $preset['colors']['secondary'],
            'font' => $preset['font'],
            'header_style' => $preset['header_style'],
            'footer_style' => $preset['footer_style'],
            'card_style' => $preset['card_style'],
            'button_style' => $preset['button_style'],
            'supports_dark' => $preset['supports_dark'],
        ], $actor);

        self::storeIntelligenceRecord($partner, 'preset_applied', [
            'preset_key' => $presetKey,
            'preset_name' => $preset['name'],
            'theme_id' => $theme['id'],
            'colors' => $preset['colors'],
        ], $actor);

        return [
            'store_id' => $partner['store_id'],
            'applied_preset' => $preset,
            'theme' => $updated,
            'message' => 'تم تطبيق توصية ذكاء القوالب على القالب الحالي.',
        ];
    }

    public static function industryPresets(): array
    {
        return [
            self::preset('fashion-luxury', 'Fashion Luxury', 'أزياء فاخرة', ['fashion', 'abaya', 'dress', 'clothes', 'أزياء', 'عباية'], '#0f172a', '#d4af37', 'IBM Plex Sans Arabic', 'editorial', 'صور كبيرة، Lookbook، كروت منتجات عريضة', ['hero_editorial', 'lookbook', 'featured_products', 'instagram_feed', 'testimonials']),
            self::preset('modern-fashion', 'Modern Fashion', 'أزياء عصرية', ['fashion', 'streetwear', 'clothes', 'sneakers', 'أزياء'], '#111827', '#7c3aed', 'Tajawal', 'clean', 'Header بسيط، Hero بصور موديلات، Slider للوصل حديثاً', ['announcement', 'hero_slider', 'categories_grid', 'new_arrivals', 'ugc_reviews']),
            self::preset('arabian-perfume', 'Arabian Perfume', 'عطور عربية', ['perfume', 'oud', 'fragrance', 'عطر', 'عطور', 'عود'], '#120816', '#c084fc', 'Tajawal', 'luxury-dark', 'واجهة داكنة فاخرة، حواف ناعمة، بنرات موسمية للعطور', ['hero_luxury', 'best_sellers', 'scent_notes', 'offers_banner', 'reviews']),
            self::preset('jewelry-elegant', 'Jewelry Elegant', 'مجوهرات', ['jewelry', 'gold', 'ring', 'watch', 'مجوهرات', 'ذهب'], '#3b2519', '#f8d7a1', 'IBM Plex Sans Arabic', 'elegant', 'Hero قريب للمنتج، مساحات بيضاء، CTA هادئ', ['hero_closeup', 'collections', 'gift_ideas', 'trust_badges', 'newsletter']),
            self::preset('tech-store', 'Tech Store', 'إلكترونيات', ['tech', 'electronics', 'mobile', 'laptop', 'إلكترونيات', 'جوال'], '#020617', '#38bdf8', 'Tajawal', 'tech', 'Mega Menu، مقارنة منتجات، عروض سريعة', ['mega_header', 'hero_tech', 'comparison', 'flash_sale', 'product_slider']),
            self::preset('fitness-pro', 'Fitness Pro', 'رياضة ولياقة', ['sport', 'fitness', 'gym', 'shoes', 'رياضة'], '#111827', '#f97316', 'Tajawal', 'dynamic', 'صور حركة، Countdown، كروت قوية للموبايل', ['hero_motion', 'categories_grid', 'countdown', 'best_sellers', 'sticky_cta']),
            self::preset('beauty-glow', 'Beauty Glow', 'تجميل وعناية', ['beauty', 'makeup', 'skin', 'cosmetics', 'جمال', 'تجميل'], '#fff1f2', '#e11d48', 'IBM Plex Sans Arabic', 'soft', 'ألوان ناعمة، بنرات قبل/بعد، تقييمات واضحة', ['hero_soft', 'before_after', 'featured_products', 'reviews', 'newsletter']),
            self::preset('home-decor', 'Home Decor', 'أثاث ومنزل', ['home', 'decor', 'furniture', 'أثاث', 'منزل'], '#292524', '#d6a86a', 'Tajawal', 'warm', 'صور غرف، Collections، قصص استخدام', ['hero_room', 'collections', 'image_text', 'brands', 'faq']),
            self::preset('restaurant-dark', 'Restaurant Dark', 'مطاعم وكافيهات', ['food', 'coffee', 'restaurant', 'حلويات', 'مطعم', 'قهوة'], '#09090b', '#f59e0b', 'Tajawal', 'dark-menu', 'قائمة سريعة، عروض يومية، CTA واتساب', ['hero_menu', 'daily_offers', 'menu_grid', 'whatsapp_cta', 'testimonials']),
            self::preset('minimal-clean', 'Minimal Clean', 'Minimal', ['minimal', 'digital', 'clean', 'متجر', 'عام'], '#f8fafc', '#111827', 'IBM Plex Sans Arabic', 'minimal', 'تصميم خفيف وسريع، مناسب للمتاجر الجديدة', ['hero_clean', 'categories_grid', 'featured_products', 'trust_badges', 'newsletter']),
        ];
    }

    private static function preset(string $key, string $name, string $industry, array $keywords, string $primary, string $secondary, string $font, string $layout, string $description, array $sections): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'industry' => $industry,
            'keywords' => $keywords,
            'colors' => ['primary' => $primary, 'secondary' => $secondary, 'accent' => self::accent($secondary)],
            'font' => $font,
            'layout' => $layout,
            'description' => $description,
            'sections' => $sections,
            'header_style' => str_contains($layout, 'tech') ? 'mega' : (str_contains($layout, 'minimal') ? 'compact' : 'editorial'),
            'footer_style' => str_contains($layout, 'minimal') ? 'minimal' : 'rich',
            'card_style' => str_contains($layout, 'luxury') ? 'elevated' : (str_contains($layout, 'tech') ? 'comparison' : 'soft'),
            'button_style' => str_contains($layout, 'luxury') ? 'pill' : 'rounded',
            'supports_dark' => str_contains($layout, 'dark') || in_array($key, ['tech-store', 'fashion-luxury', 'fitness-pro'], true),
            'hero_style' => str_contains($layout, 'dark') ? 'cinematic' : (str_contains($layout, 'minimal') ? 'clean' : 'commerce'),
            'product_cards' => str_contains($layout, 'tech') ? 'comparison_cards' : (str_contains($layout, 'luxury') ? 'premium_cards' : 'conversion_cards'),
            'cta' => str_contains($layout, 'dark') ? 'تسوّق المجموعة الآن' : 'ابدأ التسوق',
            'mobile' => ['bottom_nav' => true, 'sticky_cta' => true, 'compact_cards' => true],
        ];
    }

    private static function storeContext(array $partner): array
    {
        $products = collect(PartnerProducts::list($partner, request())['products'] ?? []);
        $orders = self::records($partner, 'orders');
        $events = self::records($partner, 'storefront_events');
        $productText = Str::lower($products->map(fn (array $product) => implode(' ', [
            $product['name'] ?? '',
            $product['category'] ?? '',
            $product['description'] ?? '',
            $product['sku'] ?? '',
        ]))->implode(' '));

        $industry = self::detectIndustry($partner, $productText);
        $palette = self::extractPalette($products, $industry);
        $audience = self::targetAudience($industry, $products);

        return [
            'store_name' => $partner['name'] ?? 'Solve Store',
            'store_id' => $partner['store_id'],
            'industry' => $industry,
            'product_count' => $products->count(),
            'products_sample' => $products->take(6)->pluck('name')->filter()->values()->all(),
            'categories' => $products->pluck('category')->filter()->unique()->values()->all(),
            'dominant_colors' => $palette,
            'target_audience' => $audience,
            'orders_count' => $orders->count(),
            'views_count' => $events->where('event', 'view')->count(),
            'add_to_cart_count' => $events->where('event', 'add_to_cart')->count(),
            'conversion_rate' => self::conversionRate($orders->count(), max($events->count(), 1)),
            'signals' => self::signals($industry, $products, $orders, $events),
        ];
    }

    private static function detectIndustry(array $partner, string $productText): string
    {
        $source = Str::lower(implode(' ', [
            $partner['business_type'] ?? '',
            $partner['category'] ?? '',
            $partner['name'] ?? '',
            $productText,
        ]));

        $map = [
            'عطور' => ['perfume', 'fragrance', 'oud', 'عطر', 'عطور', 'عود'],
            'أزياء' => ['fashion', 'abaya', 'dress', 'clothes', 'shirt', 'عباية', 'أزياء', 'ملابس'],
            'إلكترونيات' => ['tech', 'electronics', 'mobile', 'laptop', 'إلكترونيات', 'جوال', 'سماعة'],
            'مجوهرات' => ['jewelry', 'gold', 'ring', 'watch', 'مجوهرات', 'ذهب', 'ساعة'],
            'رياضة' => ['sport', 'fitness', 'gym', 'shoes', 'رياضة', 'حذاء'],
            'منزل' => ['home', 'decor', 'furniture', 'أثاث', 'منزل', 'ديكور'],
            'مطاعم' => ['food', 'coffee', 'restaurant', 'sweet', 'مطعم', 'قهوة', 'حلويات'],
            'تجميل' => ['beauty', 'makeup', 'skin', 'cosmetics', 'تجميل', 'عناية'],
        ];

        foreach ($map as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($source, Str::lower($keyword))) {
                    return $industry;
                }
            }
        }

        return 'متجر عام';
    }

    private static function matchPresets(array $context, Collection $presets): Collection
    {
        $industry = $context['industry'];
        $text = Str::lower(implode(' ', array_merge([$industry, $context['store_name']], $context['products_sample'], $context['categories'])));

        return $presets
            ->map(function (array $preset) use ($text, $industry): array {
                $score = 48;
                if (Str::contains($preset['industry'], $industry) || Str::contains($industry, $preset['industry'])) {
                    $score += 28;
                }
                foreach ($preset['keywords'] as $keyword) {
                    if (Str::contains($text, Str::lower($keyword))) {
                        $score += 6;
                    }
                }
                $preset['match_score'] = min(99, $score);
                $preset['match_label'] = $preset['match_score'] >= 80 ? 'مطابق جداً' : ($preset['match_score'] >= 65 ? 'مناسب' : 'اقتراح بديل');

                return $preset;
            })
            ->sortByDesc('match_score')
            ->values();
    }

    private static function bestPresetForPrompt(string $prompt, array $context): array
    {
        $prompt = Str::lower($prompt);
        $presets = collect(self::industryPresets());

        foreach ($presets as $preset) {
            foreach ($preset['keywords'] as $keyword) {
                if ($prompt !== '' && Str::contains($prompt, Str::lower($keyword))) {
                    return $preset;
                }
            }
        }

        return self::matchPresets($context, $presets)->first();
    }

    private static function themeAnalytics(array $partner): array
    {
        $events = self::records($partner, 'storefront_events');
        $orders = self::records($partner, 'orders');
        $views = max(1, $events->count());
        $ordersCount = $orders->count();
        $conversion = self::conversionRate($ordersCount, $views);

        return [
            'conversion_rate' => $conversion,
            'mobile_score' => min(99, 82 + (int) round($conversion)),
            'speed_score' => min(99, 88 + (int) ($events->where('event', 'view')->count() % 8)),
            'sales_impact' => min(100, 55 + ($ordersCount * 4)),
            'engagement_score' => min(100, 60 + $events->whereIn('event', ['product_view', 'add_to_cart'])->count() * 3),
            'ctr' => self::conversionRate($events->where('event', 'add_to_cart')->count(), max($events->where('event', 'product_view')->count(), 1)),
            'scroll_depth' => min(92, 58 + $events->where('event', 'scroll')->count() * 2),
        ];
    }

    private static function rankThemes(array $partner, Collection $matched, array $analytics): array
    {
        $themes = collect(PartnerStorefront::themes($partner, request())['rows'] ?? []);

        return $themes
            ->map(function (array $theme) use ($matched, $analytics): array {
                $style = Str::lower(($theme['name'] ?? '') . ' ' . ($theme['style'] ?? '') . ' ' . ($theme['layout'] ?? ''));
                $preset = $matched->first(fn (array $item) => Str::contains($style, Str::lower($item['layout'])) || Str::contains($style, Str::lower($item['name'])));
                $match = $preset['match_score'] ?? $matched->first()['match_score'] ?? 70;
                $theme['ai_match_score'] = min(99, (int) round(($match * 0.55) + ($analytics['speed_score'] * 0.2) + ($analytics['mobile_score'] * 0.15) + ($analytics['conversion_rate'] * 0.1)));
                $theme['ai_reason'] = $preset ? 'متوافق مع نشاط ' . $preset['industry'] : 'قابل للتخصيص حسب نشاط المتجر';
                $theme['analytics'] = $analytics;
                $theme['ranking_badge'] = $theme['ai_match_score'] >= 85 ? 'AI Recommended' : 'Good Fit';

                return $theme;
            })
            ->sortByDesc('ai_match_score')
            ->values()
            ->all();
    }

    private static function dynamicHomepage(array $context, string $season): array
    {
        return [
            'season' => $season,
            'rule' => 'تتغير الصفحة حسب الموسم والمنتجات الرائجة والحملات النشطة.',
            'sections' => [
                ['name' => 'Hero موسمي', 'source' => $season, 'dynamic' => true],
                ['name' => 'الأكثر مبيعاً', 'source' => 'orders + products', 'dynamic' => true],
                ['name' => 'وصل حديثاً', 'source' => 'latest products', 'dynamic' => true],
                ['name' => 'عروض ذكية', 'source' => 'campaigns + coupons', 'dynamic' => true],
                ['name' => 'توصيات AI', 'source' => 'behavior + stock', 'dynamic' => true],
            ],
        ];
    }

    private static function bannerIdeas(array $context, string $season): array
    {
        $industry = $context['industry'];
        $colors = $context['dominant_colors'];

        return [
            [
                'title' => "عروض {$season}",
                'subtitle' => "مختارة خصيصاً لمتجر {$context['store_name']}",
                'cta' => 'تسوق الآن',
                'placement' => 'home_hero',
                'colors' => $colors,
                'layout' => $industry === 'عطور' ? 'dark-luxury-overlay' : 'wide-commerce-banner',
            ],
            [
                'title' => 'الأكثر طلباً هذا الأسبوع',
                'subtitle' => 'اعرض المنتجات الرائجة أولاً لرفع معدل التحويل.',
                'cta' => 'اكتشف المجموعة',
                'placement' => 'home_secondary',
                'colors' => [$colors[1] ?? '#111827', $colors[0] ?? '#ffffff'],
                'layout' => 'split-product-focus',
            ],
            [
                'title' => 'وصل حديثاً',
                'subtitle' => 'بنر سريع للمنتجات الجديدة مع CTA واضح.',
                'cta' => 'شاهد الجديد',
                'placement' => 'collection_top',
                'colors' => [$colors[0] ?? '#f8fafc', $colors[2] ?? '#7c3aed'],
                'layout' => 'minimal-seasonal',
            ],
        ];
    }

    private static function conversionRecommendations(array $context, array $analytics): array
    {
        $recommendations = [];

        if (($analytics['conversion_rate'] ?? 0) < 4) {
            $recommendations[] = ['priority' => 'high', 'title' => 'ارفع وضوح CTA', 'body' => 'اجعل زر الشراء في Hero وفوق أول قائمة منتجات، مع Sticky CTA على الموبايل.'];
        }

        if (($analytics['mobile_score'] ?? 0) < 90) {
            $recommendations[] = ['priority' => 'medium', 'title' => 'حسّن تجربة الموبايل', 'body' => 'استخدم Product Cards مختصرة وBottom Navigation وزر شراء ثابت.'];
        }

        if ($context['product_count'] < 5) {
            $recommendations[] = ['priority' => 'medium', 'title' => 'اعرض أقسام ثقة بدلاً من منتجات كثيرة', 'body' => 'استخدم Trust Badges، Reviews، FAQ، وWhatsApp CTA حتى يكتمل الكتالوج.'];
        }

        $recommendations[] = ['priority' => 'low', 'title' => 'اختبر ترتيب الصفحة', 'body' => 'جرّب Hero ثم الأكثر مبيعاً ثم العروض؛ راقب CTR وScroll Depth لمدة أسبوع.'];

        return $recommendations;
    }

    private static function conversionEngine(array $context, array $analytics): array
    {
        return [
            'watching' => [
                'conversion_rate' => $analytics['conversion_rate'] ?? 0,
                'ctr' => $analytics['ctr'] ?? 0,
                'scroll_depth' => $analytics['scroll_depth'] ?? 0,
                'mobile_score' => $analytics['mobile_score'] ?? 0,
            ],
            'insights' => [
                [
                    'title' => 'اختبر ترتيب الصفحة الرئيسية',
                    'body' => 'ابدأ بـ Hero واضح ثم الأكثر مبيعاً ثم العروض. هذا الترتيب يناسب نشاط ' . $context['industry'] . ' ويقلل التشتت.',
                    'impact' => 'conversion',
                ],
                [
                    'title' => 'خصص CTA حسب الموسم',
                    'body' => 'استخدم CTA قصير في البنر الموسمي واربطه بمجموعة منتجات حقيقية من المتجر.',
                    'impact' => 'ctr',
                ],
                [
                    'title' => 'اجعل الصفحة ديناميكية',
                    'body' => 'بدل المنتجات يدوياً، اعرض الأكثر طلباً ووصل حديثاً من بيانات الطلبات والمخزون.',
                    'impact' => 'sales_impact',
                ],
            ],
            'experiments' => [
                ['name' => 'Hero CTA A/B', 'duration' => '7 أيام', 'metric' => 'CTR'],
                ['name' => 'Product cards density', 'duration' => '5 أيام', 'metric' => 'Add to cart'],
                ['name' => 'Mobile sticky buy button', 'duration' => '7 أيام', 'metric' => 'Mobile conversion'],
            ],
        ];
    }

    private static function autoStyleSuggestions(array $context, array $preset): array
    {
        $suggestions = [
            [
                'title' => 'طبق Palette من الكتالوج',
                'body' => 'الألوان المقترحة مأخوذة من نوع المنتجات والتصنيفات الحالية، وتناسب ' . $context['industry'] . '.',
            ],
            [
                'title' => 'استخدم أقسام ديناميكية',
                'body' => 'اجعل Featured Products وBest Sellers مرتبطة بالطلبات والمخزون بدل الاختيار اليدوي.',
            ],
            [
                'title' => 'حافظ على CTA واحد واضح',
                'body' => 'الواجهة المقترحة تستخدم زر شراء واضح ومكرر في الهيدر والموبايل.',
            ],
        ];

        if (($context['orders_count'] ?? 0) === 0) {
            $suggestions[] = [
                'title' => 'اعرض عناصر ثقة أكثر',
                'body' => 'لا توجد طلبات كافية بعد، لذلك ارفع الثقة عبر سياسات الشحن والاسترجاع والتقييمات وواتساب.',
            ];
        }

        if (($preset['supports_dark'] ?? false) === true) {
            $suggestions[] = [
                'title' => 'اختبر النسخة الداكنة',
                'body' => 'القالب يدعم Dark Style، وهو مناسب للمنتجات الفاخرة أو التقنية عالية التباين.',
            ];
        }

        return $suggestions;
    }

    private static function seasonalBannerSet(array $context): array
    {
        $colors = $context['dominant_colors'];
        $storeName = $context['store_name'];

        return [
            [
                'title' => 'عروض رمضان',
                'subtitle' => "تجربة موسمية هادئة لمتجر {$storeName} مع منتجات مختارة.",
                'cta' => 'تسوق عروض رمضان',
                'placement' => 'seasonal_ramadan',
                'colors' => ['#2f1b46', '#f8d7a1', $colors[2] ?? '#fff7ed'],
                'layout' => 'crescent-premium',
            ],
            [
                'title' => 'الجمعة البيضاء',
                'subtitle' => 'بنر عالي التباين للخصومات السريعة والمنتجات الأكثر طلباً.',
                'cta' => 'اكتشف الخصومات',
                'placement' => 'white_friday',
                'colors' => ['#020617', '#ffffff', $colors[1] ?? '#7c3aed'],
                'layout' => 'flash-sale-countdown',
            ],
            [
                'title' => 'صيف جديد',
                'subtitle' => 'واجهة مشرقة لعرض المنتجات الموسمية والخفيفة.',
                'cta' => 'تسوق الصيف',
                'placement' => 'summer',
                'colors' => ['#fef3c7', '#06b6d4', '#ffffff'],
                'layout' => 'bright-seasonal',
            ],
            [
                'title' => 'دفء الشتاء',
                'subtitle' => 'بنر واسع للمنتجات المناسبة للموسم والعروض الهادئة.',
                'cta' => 'ابدأ التسوق',
                'placement' => 'winter',
                'colors' => ['#0f172a', '#dbeafe', $colors[0] ?? '#111827'],
                'layout' => 'cozy-editorial',
            ],
        ];
    }

    private static function salesImpactSummary(array $analytics): array
    {
        $conversion = (float) ($analytics['conversion_rate'] ?? 0);
        $speed = (int) ($analytics['speed_score'] ?? 0);

        return [
            'status' => $conversion >= 4 ? 'قوي' : 'يحتاج تحسين',
            'message' => $conversion >= 4
                ? 'القالب الحالي يعطي إشارة تحويل جيدة، ركز على تحسين البنرات والموبايل.'
                : 'معدل التحويل أقل من الهدف، ابدأ بتغيير Hero وCTA وترتيب المنتجات.',
            'risk' => $speed < 88 ? 'سرعة القالب قد تؤثر على المبيعات.' : 'الأداء مناسب للتجارب القادمة.',
        ];
    }

    private static function homepageSections(array $context, array $preset, string $season): array
    {
        return collect($preset['sections'])
            ->map(fn (string $section, int $index): array => [
                'key' => $section,
                'label' => Str::headline(str_replace('_', ' ', $section)),
                'order' => $index + 1,
                'data_source' => match (true) {
                    Str::contains($section, ['products', 'best', 'sale', 'arrivals']) => 'products/orders',
                    Str::contains($section, ['categories', 'collections']) => 'categories',
                    Str::contains($section, ['reviews', 'testimonials']) => 'reviews/customers',
                    default => 'storefront/settings',
                },
                'seasonal_variant' => $season,
            ])
            ->values()
            ->all();
    }

    private static function currentSeason(): string
    {
        $month = (int) now()->format('n');

        return match (true) {
            in_array($month, [3, 4], true) => 'رمضان والعيد',
            in_array($month, [6, 7, 8], true) => 'الصيف',
            in_array($month, [11], true) => 'الجمعة البيضاء',
            in_array($month, [12, 1, 2], true) => 'الشتاء',
            default => 'الموسم الحالي',
        };
    }

    private static function heroTitle(array $context, array $preset, string $prompt): string
    {
        if ($prompt !== '') {
            return Str::limit($prompt, 54, '');
        }

        return match ($context['industry']) {
            'عطور' => 'رائحة تترك أثراً لا ينسى',
            'أزياء' => 'أسلوبك يبدأ من هنا',
            'إلكترونيات' => 'تقنية أسرع لحياتك اليومية',
            'مجوهرات' => 'لمعان يدوم للأبد',
            default => 'تجربة تسوق مصممة للبيع',
        };
    }

    private static function heroSubtitle(array $context, array $preset): string
    {
        return 'واجهة ' . $preset['industry'] . ' مبنية على منتجات ' . $context['store_name'] . ' مع ترتيب Sections ذكي ومناسب للموبايل.';
    }

    private static function headline(array $context): string
    {
        return "ذكاء Solve يوصي بتجربة {$context['industry']} مبنية على {$context['product_count']} منتج و{$context['orders_count']} طلب.";
    }

    private static function matchReason(array $context, ?array $preset): string
    {
        if (! $preset) {
            return 'لا توجد إشارة كافية، لذلك تم اختيار قالب عام سريع وقابل للتخصيص.';
        }

        return "تم اختيار {$preset['name']} لأنه يطابق نشاط {$context['industry']}، ويستخدم ألواناً وخطوطاً مناسبة للفئة: {$context['target_audience']}.";
    }

    private static function targetAudience(string $industry, Collection $products): string
    {
        return match ($industry) {
            'أزياء' => 'عملاء يهتمون بالصور، المقاسات، والشراء السريع من الموبايل',
            'عطور' => 'عملاء يبحثون عن إحساس فاخر، تقييمات، وقصص الروائح',
            'إلكترونيات' => 'عملاء يقارنون المواصفات والسعر والضمان',
            'مجوهرات' => 'عملاء يحتاجون ثقة عالية وصور قريبة وتغليف هدايا',
            default => $products->count() > 10 ? 'متسوقون متكررون يحتاجون تصفح سريع' : 'زوار جدد يحتاجون ثقة ووضوح',
        };
    }

    private static function extractPalette(Collection $products, string $industry): array
    {
        return match ($industry) {
            'عطور' => ['#120816', '#c084fc', '#f5d0fe'],
            'أزياء' => ['#0f172a', '#7c3aed', '#f8fafc'],
            'إلكترونيات' => ['#020617', '#38bdf8', '#dbeafe'],
            'مجوهرات' => ['#3b2519', '#f8d7a1', '#fff7ed'],
            'رياضة' => ['#111827', '#f97316', '#fff7ed'],
            'منزل' => ['#292524', '#d6a86a', '#fafaf9'],
            default => ['#f8fafc', '#6d28d9', '#06b6d4'],
        };
    }

    private static function signals(string $industry, Collection $products, Collection $orders, Collection $events): array
    {
        return [
            'industry_detected' => $industry,
            'catalog_depth' => $products->count() >= 8 ? 'غني' : 'بداية كتالوج',
            'demand_signal' => $orders->count() > 0 ? 'يوجد طلبات يمكن البناء عليها' : 'لا توجد طلبات كافية بعد',
            'behavior_signal' => $events->count() > 0 ? 'أحداث المتجر متاحة للتحسين' : 'ابدأ بتفعيل تتبع الأحداث',
        ];
    }

    private static function conversionRate(int|float $part, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 2);
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
            ->map(fn (PlatformRecord $record) => $record->payload ?? []);
    }

    private static function storeIntelligenceRecord(array $partner, string $type, array $payload, ?array $actor): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        PlatformRecord::query()->create([
            'section' => 'storefront_theme_intelligence',
            'record_id' => $type . '-' . Str::lower(Str::random(10)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $type,
            'payload' => $payload + ['type' => $type, 'store_id' => $partner['store_id']],
        ]);

        if (Schema::hasTable('platform_activity_logs')) {
            PlatformActivityLog::query()->create([
                'actor_type' => 'partner',
                'actor_id' => $actor['username'] ?? $actor['email'] ?? null,
                'actor_name' => $actor['name'] ?? null,
                'role' => $actor['role'] ?? null,
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'action' => 'storefront_theme_intelligence_' . $type,
                'subject_type' => 'storefront_theme_intelligence',
                'subject_id' => $payload['id'] ?? $payload['preset_key'] ?? $type,
                'properties' => $payload,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        }
    }

    private static function accent(string $hex): string
    {
        return match (Str::lower($hex)) {
            '#c084fc' => '#f5d0fe',
            '#38bdf8' => '#dbeafe',
            '#f97316' => '#ffedd5',
            '#d4af37', '#f8d7a1' => '#fff7ed',
            default => '#f5f3ff',
        };
    }
}
