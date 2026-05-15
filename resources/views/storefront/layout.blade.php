@php
    $primary = $theme['primary_color'] ?? '#6d28d9';
    $secondary = $theme['secondary_color'] ?? '#06b6d4';
    $font = $theme['font'] ?? 'Tajawal';
    $fontStacks = [
        'Tajawal' => "'Tajawal', Tahoma, Arial, sans-serif",
        'IBM Plex Sans Arabic' => "'IBM Plex Sans Arabic', Tahoma, Arial, sans-serif",
        'Cairo' => "'Cairo', Tahoma, Arial, sans-serif",
    ];
    $fontStack = $fontStacks[$font] ?? $fontStacks['Tajawal'];
    $sanitizeThemeToken = fn ($value, string $fallback): string => preg_match('/^[a-z0-9_-]+$/', (string) $value) ? (string) $value : $fallback;
    $headerStyle = $sanitizeThemeToken($theme['header_style'] ?? 'compact', 'compact');
    $footerStyle = $sanitizeThemeToken($theme['footer_style'] ?? 'rich', 'rich');
    $cardStyle = $sanitizeThemeToken($theme['card_style'] ?? 'soft', 'soft');
    $buttonStyle = $sanitizeThemeToken($theme['button_style'] ?? 'rounded', 'rounded');
    $supportsDark = (bool) ($theme['supports_dark'] ?? false);
    $storeName = $settings['store_name'] ?? $store['name'] ?? ($partner['name'] ?? 'Solve Store');
    $logo = $settings['logo'] ?? $partner['logo'] ?? 'solve-logo.png';
    $imageUrl = function (?string $path) {
        if (! $path) {
            return asset('solve-logo.png');
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset($path);
    };
    $storeRoute = fn (string $path = '') => url('/store/' . $slug . $path);
    $headerMenu = collect($navigation['header_menu'] ?? [])->filter(fn ($item) => $item['visible'] ?? true);
    $footerMenu = collect($navigation['footer_menu'] ?? [])->filter(fn ($item) => $item['visible'] ?? true);
    $storefrontSectionRows = collect($builderSections ?? []);
    $storefrontSectionVisible = function (array|string $types, bool $default = true) use ($storefrontSectionRows): bool {
        $types = (array) $types;
        $matches = $storefrontSectionRows->filter(fn (array $section): bool => in_array($section['type'] ?? '', $types, true));

        if ($matches->isEmpty()) {
            return $default;
        }

        return $matches->contains(function (array $section): bool {
            $status = $section['status_key'] ?? $section['status'] ?? 'active';

            return (bool) ($section['visible'] ?? true) && ! in_array($status, ['hidden', 'disabled'], true);
        });
    };
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $seo['meta_title'] ?? $storeName)</title>
    <meta name="description" content="@yield('description', $seo['meta_description'] ?? 'متجر إلكتروني يعمل على منصة Solve')">
    <meta property="og:title" content="@yield('title', $seo['meta_title'] ?? $storeName)">
    <meta property="og:description" content="@yield('description', $seo['meta_description'] ?? 'متجر إلكتروني يعمل على منصة Solve')">
    <meta property="og:image" content="{{ $imageUrl($seo['social_image'] ?? $logo) }}">
    <style>
        :root {
            --primary: {{ $primary }};
            --secondary: {{ $secondary }};
            --noon:#feee00;
            --noon-dark:#111827;
            --deal:#ffedd5;
            --deal-ink:#c2410c;
            --ink:#07111f;
            --muted:#64748b;
            --line:#dbe5f0;
            --soft:#f7fafc;
            --surface:#ffffff;
            --night:#0d1728;
            --success:#10b981;
            --warning:#f59e0b;
            --shadow:0 24px 70px rgba(15,23,42,.08);
            --brand-gradient:linear-gradient(135deg, var(--primary), #334ce7 48%, var(--secondary));
            --store-font: {!! $fontStack !!};
        }
        * { box-sizing: border-box; }
        html { scroll-behavior:smooth; }
        body {
            margin:0;
            font-family: var(--store-font);
            background:#f5f6f8;
            color:var(--ink);
        }
        a { color:inherit; text-decoration:none; }
        .wrap { width:min(1180px, calc(100% - 32px)); margin:auto; }
        .store-strip { background:var(--noon); color:var(--noon-dark); font-weight:900; font-size:13px; border-bottom:1px solid rgba(17,24,39,.08); }
        .store-strip-inner { min-height:34px; display:flex; align-items:center; justify-content:space-between; gap:14px; }
        .topbar { position:sticky; top:0; z-index:20; border-bottom:1px solid rgba(17,24,39,.08); background:#fff; }
        .nav { min-height:78px; display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .brand { display:flex; align-items:center; gap:12px; font-weight:900; min-width:180px; }
        .brand img { width:46px; height:46px; border-radius:16px; object-fit:cover; border:1px solid var(--line); background:#fff; box-shadow:0 12px 28px rgba(15,23,42,.08); }
        .brand-name { display:grid; gap:2px; }
        .brand-name span:first-child { font-size:15px; }
        .brand-name span:last-child { color:#7c3aed; font-size:11px; letter-spacing:.18em; text-transform:uppercase; }
        .links { display:flex; align-items:center; gap:6px; color:var(--muted); font-weight:900; font-size:14px; }
        .links a { padding:10px 13px; border-radius:999px; transition:.18s ease; }
        .links a:hover { color:#111827; background:#fff7c2; }
        .actions { display:flex; align-items:center; gap:10px; }
        .store-search {
            width:min(360px, 34vw);
            height:46px;
            display:flex;
            align-items:center;
            gap:8px;
            padding:0 12px;
            border:1px solid var(--line);
            border-radius:18px;
            background:#fff;
            box-shadow:0 12px 28px rgba(15,23,42,.04);
            transition:.18s ease;
        }
        .store-search:focus-within {
            border-color:rgba(109,40,217,.36);
            box-shadow:0 16px 34px rgba(109,40,217,.1);
        }
        .store-search input {
            min-width:0;
            flex:1;
            border:0;
            outline:0;
            background:transparent;
            color:var(--ink);
            font:inherit;
            font-size:13px;
            font-weight:900;
        }
        .store-search input::placeholder { color:#94a3b8; }
        .store-search button {
            width:30px;
            height:30px;
            border:0;
            border-radius:11px;
            display:grid;
            place-items:center;
            background:#f1f5f9;
            color:#111827;
            cursor:pointer;
            transition:.18s ease;
        }
        .store-search button:hover { background:var(--noon); }
        .cart-icon-btn {
            position:relative;
            min-width:112px;
            height:56px;
            padding:0 18px;
            border-radius:22px;
            flex:0 0 auto;
            background:linear-gradient(135deg,#07111f,#172033);
            border:1px solid rgba(255,255,255,.08);
            box-shadow:0 18px 34px rgba(7,17,31,.2);
        }
        .cart-icon-btn svg { width:21px; height:21px; }
        .cart-icon-btn .cart-label { font-size:15px; font-weight:900; }
        .cart-icon-btn .cart-count {
            position:absolute;
            top:-8px;
            inset-inline-start:-8px;
            width:28px;
            height:28px;
            box-shadow:0 8px 18px rgba(17,24,39,.16);
        }
        .btn { border:0; border-radius:16px; padding:12px 18px; font-weight:900; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:.18s ease; white-space:nowrap; }
        .btn:hover { transform:translateY(-1px); }
        .btn-primary { background:#111827; color:#fff; box-shadow:0 16px 30px rgba(17,24,39,.16); }
        .btn-soft { background:#fff; color:var(--ink); border:1px solid var(--line); }
        .btn-ghost { background:#edf3f8; color:var(--ink); }
        .theme-header-compact .nav { min-height:68px; }
        .theme-header-mega .nav { min-height:92px; }
        .theme-header-mega .links { padding:6px; border:1px solid var(--line); border-radius:999px; background:#f8fafc; }
        .theme-header-centered .nav { justify-content:center; flex-wrap:wrap; row-gap:10px; }
        .theme-header-centered .brand { flex:0 0 100%; justify-content:center; min-width:0; }
        .theme-header-centered .links { order:2; }
        .theme-header-centered .actions { order:3; }
        .theme-buttons-square .btn { border-radius:10px; }
        .theme-buttons-pill .btn { border-radius:999px; }
        .theme-cards-elevated .panel,
        .theme-cards-elevated .product-card,
        .theme-cards-elevated .category-thumb-card,
        .theme-cards-elevated .trust-card { box-shadow:0 28px 70px rgba(15,23,42,.14); }
        .theme-cards-compact .product-card,
        .theme-cards-compact .panel,
        .theme-cards-compact .category-thumb-card { border-radius:12px; }
        .theme-supports-dark.theme-preview-dark {
            --ink:#f8fafc;
            --muted:#cbd5e1;
            --line:#263244;
            --soft:#0f172a;
            --surface:#111827;
            background:#07111f;
            color:#f8fafc;
        }
        .theme-supports-dark.theme-preview-dark .topbar,
        .theme-supports-dark.theme-preview-dark .panel,
        .theme-supports-dark.theme-preview-dark .product-card,
        .theme-supports-dark.theme-preview-dark .category-thumb-card,
        .theme-supports-dark.theme-preview-dark .trust-card,
        .theme-supports-dark.theme-preview-dark .mini-stat,
        .theme-supports-dark.theme-preview-dark .input {
            background:#111827;
            border-color:#263244;
            color:#f8fafc;
        }
        .theme-supports-dark.theme-preview-dark .store-strip { background:var(--primary); color:#fff; }
        .theme-supports-dark.theme-preview-dark .links a:hover { background:rgba(255,255,255,.08); color:#fff; }
        .theme-supports-dark.theme-preview-dark .btn-soft { background:#0f172a; color:#fff; border-color:#263244; }
        .theme-supports-dark.theme-preview-dark .store-search,
        .theme-supports-dark.theme-preview-dark .store-search input { background:#0f172a; color:#fff; border-color:#263244; }
        .theme-supports-dark.theme-preview-dark .store-search button { background:#182235; color:#fff; }
        .hero { padding:44px 0 24px; }
        .hero-grid { display:grid; grid-template-columns:1.08fr .92fr; gap:22px; align-items:stretch; }
        .hero-card { border-radius:22px; padding:38px; min-height:360px; background:linear-gradient(135deg,#111827 0%,#1f2937 52%,#feee00 52%,#fff7b0 100%); color:#fff; overflow:hidden; position:relative; box-shadow:0 22px 54px rgba(17,24,39,.12); isolation:isolate; }
        .hero-card:before { content:""; position:absolute; inset:24px auto auto 24px; width:160px; height:160px; border-radius:34px; background:rgba(254,238,0,.22); transform:rotate(12deg); z-index:-1; }
        .hero-card:after { content:""; position:absolute; inset:auto -80px -110px auto; width:320px; height:320px; border-radius:50%; background:rgba(255,255,255,.16); z-index:-1; }
        .hero-card h1 { margin:0; font-size:clamp(34px, 5vw, 68px); line-height:1.05; letter-spacing:0; max-width:720px; }
        .hero-card p { margin:18px 0 0; color:rgba(255,255,255,.88); font-size:17px; line-height:1.9; max-width:660px; }
        .visual-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px; background:var(--noon); color:#111827; font-weight:900; font-size:12px; }
        .visual-chip:before { content:""; width:8px; height:8px; border-radius:50%; background:#111827; box-shadow:0 0 0 5px rgba(17,24,39,.12); }
        .panel { border:1px solid rgba(219,229,240,.95); border-radius:18px; background:#fff; box-shadow:0 14px 34px rgba(15,23,42,.05); }
        .glass-panel { border:1px solid rgba(255,255,255,.72); background:rgba(255,255,255,.72); backdrop-filter:blur(14px); box-shadow:var(--shadow); }
        .section { padding:28px 0; }
        .section-head { display:flex; align-items:end; justify-content:space-between; gap:16px; margin-bottom:16px; }
        .section-head h2 { margin:0; font-size:28px; letter-spacing:0; }
        .section-head p { margin:6px 0 0; color:var(--muted); font-weight:800; }
        .section-kicker { display:inline-flex; align-items:center; gap:8px; color:#111827; font-weight:900; font-size:12px; margin-bottom:8px; }
        .section-kicker:before { content:""; width:22px; height:3px; border-radius:999px; background:var(--noon); }
        .grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:14px; }
        .products-grid { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:12px; }
        .product-card { padding:10px; border-radius:16px; background:#fff; border:1px solid #e8edf3; transition:.18s ease; min-height:100%; position:relative; overflow:hidden; }
        .product-card:hover { transform:translateY(-2px); box-shadow:0 18px 38px rgba(15,23,42,.1); }
        .product-card img { width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:12px; background:#eef3f8; }
        .product-card h3 { margin:10px 0 6px; font-size:14px; line-height:1.5; min-height:42px; }
        .category-card { position:relative; overflow:hidden; padding:18px; min-height:150px; transition:.18s ease; }
        .category-card:hover { transform:translateY(-2px); }
        .category-card:after { content:""; position:absolute; inset:auto -36px -44px auto; width:120px; height:120px; border-radius:32px; background:linear-gradient(135deg, rgba(254,238,0,.55), rgba(17,24,39,.08)); transform:rotate(14deg); }
        .category-strip { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:12px; }
        .category-thumb-card { display:grid; gap:10px; align-content:start; min-height:178px; padding:12px; border:1px solid #dbe5f0; background:#fff; border-radius:18px; box-shadow:0 14px 34px rgba(15,23,42,.05); transition:.18s ease; overflow:hidden; }
        .category-thumb-card:hover { transform:translateY(-2px); box-shadow:0 18px 42px rgba(15,23,42,.09); }
        .category-visual { height:104px; border-radius:16px; display:grid; place-items:center; font-size:36px; font-weight:900; color:#111827; background:#eef3f8; position:relative; overflow:hidden; }
        .category-visual img { width:100%; height:100%; object-fit:cover; display:block; transition:.22s ease; }
        .category-thumb-card:hover .category-visual img { transform:scale(1.06); }
        .category-visual:after { content:""; position:absolute; inset:0; background:linear-gradient(180deg, transparent 45%, rgba(17,24,39,.42)); pointer-events:none; }
        .category-visual .category-label { position:absolute; right:10px; bottom:10px; z-index:1; background:#feee00; color:#111827; border-radius:999px; padding:6px 9px; font-size:11px; font-weight:900; }
        .category-thumb-card h3 { margin:0; font-size:15px; line-height:1.4; }
        .category-thumb-card p { margin:0; color:#64748b; font-weight:800; font-size:12px; }
        .banner-card { border-radius:18px; border:1px solid rgba(17,24,39,.08); min-height:210px; display:flex; align-items:end; justify-content:space-between; gap:16px; overflow:hidden; position:relative; color:#fff; background:#111827; isolation:isolate; padding:24px; }
        .banner-card img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:-2; transition:.3s ease; }
        .banner-card:hover img { transform:scale(1.04); }
        .banner-card:before { content:""; position:absolute; inset:0; background:linear-gradient(90deg, rgba(17,24,39,.82), rgba(17,24,39,.45), rgba(17,24,39,.08)); z-index:-1; }
        .banner-card .badge { background:#feee00; color:#111827; }
        .banner-card .muted { color:#e2e8f0; }
        .promo-grid { display:grid; grid-template-columns:1.2fr .8fr .8fr; gap:12px; }
        .promo-card { min-height:150px; border-radius:18px; padding:20px; position:relative; overflow:hidden; display:flex; align-items:flex-end; justify-content:space-between; gap:16px; color:#111827; border:1px solid rgba(17,24,39,.08); }
        .promo-card strong { display:block; font-size:24px; margin:8px 0; }
        .promo-card.yellow { background:linear-gradient(135deg,#feee00,#fff7b0); }
        .promo-card.dark { background:linear-gradient(135deg,#111827,#253044); color:#fff; }
        .promo-card.cyan { background:linear-gradient(135deg,#cffafe,#ecfeff); }
        .promo-card:after { content:""; position:absolute; inset:auto -44px -50px auto; width:150px; height:150px; border-radius:38px; background:rgba(255,255,255,.22); transform:rotate(14deg); }
        .wide-offer-banner { min-height:260px; border-radius:24px; overflow:hidden; position:relative; isolation:isolate; display:flex; align-items:center; justify-content:space-between; gap:20px; padding:30px; color:#fff; border:1px solid rgba(17,24,39,.08); box-shadow:0 20px 54px rgba(15,23,42,.08); }
        .wide-offer-banner img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:-2; transition:.3s ease; }
        .wide-offer-banner:hover img { transform:scale(1.04); }
        .wide-offer-banner:before { content:""; position:absolute; inset:0; z-index:-1; background:linear-gradient(90deg, rgba(17,24,39,.88), rgba(17,24,39,.48), rgba(17,24,39,.12)); }
        .wide-offer-content { max-width:560px; }
        .wide-offer-content h2 { margin:12px 0 8px; font-size:clamp(30px,4vw,48px); line-height:1.15; }
        .wide-offer-content p { margin:0; color:#e2e8f0; font-weight:900; line-height:1.9; }
        .wide-offer-stats { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .wide-offer-stats span { border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.1); padding:10px 12px; border-radius:14px; font-weight:900; }
        .wide-offer-slider { position:relative; }
        .wide-offer-slide { display:none; }
        .wide-offer-slide.active { display:flex; }
        .offer-dots { position:absolute; inset:auto 28px 18px auto; z-index:4; display:flex; gap:8px; }
        .offer-dots button { width:10px; height:10px; border-radius:999px; border:0; background:rgba(255,255,255,.55); cursor:pointer; padding:0; }
        .offer-dots button.active { width:28px; background:#feee00; }
        .service-bar { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
        .service-tile { padding:14px; border:1px solid var(--line); background:#fff; border-radius:16px; display:flex; align-items:center; gap:10px; font-weight:900; }
        .service-icon { width:38px; height:38px; border-radius:12px; display:grid; place-items:center; background:#fff7c2; color:#111827; }
        .trust-section { background:linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,0)); border-radius:28px; padding:8px 0 4px; }
        .trust-head { display:grid; grid-template-columns:1fr auto; align-items:end; gap:18px; margin-bottom:18px; }
        .trust-head h2 { margin:0; font-size:clamp(28px, 4vw, 42px); line-height:1.15; }
        .trust-head p { margin:8px 0 0; color:var(--muted); font-weight:900; }
        .trust-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .trust-card { position:relative; min-height:190px; padding:22px; overflow:hidden; border-radius:22px; background:#fff; border:1px solid #dbe5f0; box-shadow:0 18px 46px rgba(15,23,42,.06); display:flex; flex-direction:column; justify-content:space-between; }
        .trust-card:before { content:""; position:absolute; inset:auto -34px -48px auto; width:150px; height:150px; border-radius:36px; background:linear-gradient(135deg, rgba(254,238,0,.85), rgba(255,247,176,.28)); transform:rotate(14deg); }
        .trust-top { display:flex; align-items:center; justify-content:space-between; gap:12px; position:relative; z-index:1; }
        .trust-number { width:34px; height:34px; border-radius:999px; display:grid; place-items:center; background:#fff3a3; color:#111827; font-weight:900; }
        .trust-icon { width:46px; height:46px; border-radius:16px; display:grid; place-items:center; background:#111827; color:#feee00; font-weight:900; font-size:20px; }
        .trust-card h3 { margin:26px 0 10px; font-size:22px; position:relative; z-index:1; }
        .trust-card p { margin:0; line-height:1.8; position:relative; z-index:1; }
        .trust-summary { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
        .trust-pill { padding:10px 12px; border-radius:999px; background:#fff; border:1px solid var(--line); font-weight:900; color:#111827; }
        .muted { color:var(--muted); font-weight:800; }
        .price { font-size:19px; font-weight:900; color:#111827; }
        .old-price { color:#94a3b8; text-decoration:line-through; font-size:12px; font-weight:800; }
        .deal-tag { position:absolute; top:12px; right:12px; z-index:2; background:var(--deal); color:var(--deal-ink); border-radius:999px; padding:6px 9px; font-size:11px; font-weight:900; }
        .rating { display:inline-flex; align-items:center; gap:4px; color:#92400e; background:#fef3c7; border-radius:999px; padding:5px 8px; font-size:11px; font-weight:900; }
        .product-media { position:relative; display:block; }
        .product-tools { position:absolute; left:8px; top:8px; display:grid; gap:6px; opacity:0; transform:translateY(-4px); transition:.18s ease; }
        .product-card:hover .product-tools { opacity:1; transform:translateY(0); }
        .tool-btn { width:34px; height:34px; border-radius:12px; border:1px solid var(--line); background:rgba(255,255,255,.92); cursor:pointer; display:grid; place-items:center; font-weight:900; box-shadow:0 10px 22px rgba(15,23,42,.12); }
        .tool-btn.active { background:#111827; color:#fff; border-color:#111827; }
        .stock-meter { height:6px; border-radius:999px; background:#edf2f7; overflow:hidden; margin-top:8px; }
        .stock-meter span { display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,#22c55e,#feee00); }
        .badge { display:inline-flex; align-items:center; border-radius:999px; padding:7px 10px; background:#fff7c2; color:#111827; font-size:12px; font-weight:900; }
        .badge-success { background:#dcfce7; color:#047857; }
        .toolbar { display:flex; flex-wrap:wrap; gap:10px; padding:14px; margin-bottom:16px; }
        .input { height:46px; border-radius:16px; border:1px solid var(--line); background:#fff; padding:0 14px; font-weight:800; min-width:180px; color:var(--ink); }
        .input:focus { outline:2px solid rgba(109,40,217,.16); border-color:rgba(109,40,217,.42); }
        .table { width:100%; border-collapse:collapse; overflow:hidden; }
        .table th,.table td { padding:16px; border-bottom:1px solid var(--line); text-align:right; }
        .table th { background:#f8fafc; color:var(--muted); font-size:13px; }
        .footer { margin-top:44px; padding:46px 0 22px; background:#111827; color:#fff; }
        .footer-grid { display:grid; grid-template-columns:1.45fr .8fr .85fr 1fr; gap:34px; align-items:start; }
        .footer-brand { max-width:360px; }
        .footer-title { margin:0 0 16px; font-size:16px; color:#fff; }
        .footer-links { display:grid; gap:11px; color:#cbd5e1; font-weight:800; }
        .footer-links a { width:max-content; }
        .footer-note { color:#b8c4d4; line-height:1.9; margin:0; }
        .footer-contact { display:grid; gap:10px; color:#cbd5e1; font-weight:800; line-height:1.7; }
        .footer-contact span { color:#94a3b8; font-size:12px; display:block; margin-bottom:2px; }
        .payment-badges { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
        .payment-badges span { border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.06); padding:8px 10px; border-radius:12px; font-size:12px; font-weight:900; color:#e2e8f0; }
        .footer-bottom { border-top:1px solid rgba(255,255,255,.1); margin-top:34px; padding-top:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; color:#94a3b8; font-weight:800; font-size:13px; }
        .footer a:hover { color:#a5f3fc; }
        .theme-footer-minimal .footer { padding:34px 0 18px; }
        .theme-footer-minimal .footer-grid { grid-template-columns:1fr; text-align:center; }
        .theme-footer-minimal .footer-brand { margin:auto; max-width:520px; }
        .theme-footer-minimal .footer-links,
        .theme-footer-minimal .footer-contact { justify-items:center; }
        .theme-footer-minimal .payment-badges,
        .theme-footer-minimal .footer-bottom { justify-content:center; }
        .theme-footer-columns .footer-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
        .theme-footer-rich .footer { background:radial-gradient(circle at 15% 10%, rgba(109,40,217,.25), transparent 26%), #111827; }
        .cart-count { min-width:24px; height:24px; border-radius:999px; background:var(--noon); color:#111827; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:900; }
        .cart-line { display:grid; grid-template-columns:74px 1fr auto; gap:14px; align-items:center; padding:14px; border:1px solid var(--line); border-radius:20px; background:#fff; }
        .cart-line img { width:74px; height:74px; border-radius:16px; object-fit:cover; background:#f1f5f9; }
        .qty-control { display:inline-grid; grid-template-columns:38px 52px 38px; align-items:center; border:1px solid var(--line); border-radius:16px; overflow:hidden; background:#fff; }
        .qty-control button { height:38px; border:0; background:#f8fafc; font-weight:900; cursor:pointer; }
        .qty-control input { width:52px; height:38px; border:0; text-align:center; font-weight:900; }
        .mini-stat { border:1px solid var(--line); background:#fff; border-radius:22px; padding:16px; box-shadow:0 14px 38px rgba(15,23,42,.05); }
        .mini-stat strong { display:block; font-size:22px; margin-top:8px; }
        .segmented { display:inline-flex; gap:6px; padding:6px; border:1px solid var(--line); border-radius:18px; background:#fff; }
        .segmented a,.segmented button { border:0; border-radius:14px; padding:10px 14px; background:transparent; font-weight:900; cursor:pointer; }
        .segmented .active { background:#111827; color:#fff; }
        .summary-row { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 0; border-bottom:1px solid var(--line); font-weight:800; }
        .summary-row:last-child { border-bottom:0; }
        .empty-state { min-height:220px; display:grid; place-items:center; text-align:center; border:1px dashed var(--line); border-radius:22px; background:#f8fafc; padding:24px; }
        .product-row { display:grid; grid-template-columns:96px 1fr auto; gap:14px; align-items:center; padding:14px; border:1px solid var(--line); border-radius:22px; background:#fff; }
        .product-row img { width:96px; height:96px; border-radius:18px; object-fit:cover; background:#f1f5f9; }
        .form-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .cart-toast { position:fixed; bottom:20px; left:20px; background:#0f172a; color:#fff; padding:14px 18px; border-radius:18px; box-shadow:0 18px 38px rgba(15,23,42,.22); display:none; z-index:30; font-weight:900; }
        .builder-content-card,
        .builder-contact-hub,
        .builder-map-card,
        .builder-video-card {
            border:1px solid rgba(219,229,240,.95);
            border-radius:28px;
            background:
                radial-gradient(circle at 12% 18%, rgba(254,238,0,.18), transparent 26%),
                radial-gradient(circle at 88% 8%, rgba(6,182,212,.14), transparent 24%),
                #fff;
            box-shadow:0 26px 70px rgba(15,23,42,.08);
            overflow:hidden;
            position:relative;
        }
        .builder-content-card:before,
        .builder-contact-hub:before,
        .builder-map-card:before,
        .builder-video-card:before {
            content:"";
            position:absolute;
            inset:0 0 auto;
            height:5px;
            background:linear-gradient(90deg, var(--primary), var(--secondary), var(--noon));
        }
        .builder-content-grid { display:grid; grid-template-columns:1.12fr .88fr; gap:22px; align-items:stretch; padding:28px; }
        .builder-content-copy { display:flex; flex-direction:column; justify-content:center; min-height:320px; }
        .builder-content-copy h2,
        .builder-contact-side h2,
        .builder-map-info h2 { margin:0; font-size:clamp(28px,4vw,46px); line-height:1.16; letter-spacing:0; }
        .builder-content-copy p,
        .builder-contact-side p,
        .builder-map-info p { margin:12px 0 0; color:var(--muted); font-weight:850; line-height:1.9; max-width:720px; }
        .builder-tool-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:22px; }
        .builder-tool {
            border:1px solid #dbe5f0;
            background:rgba(248,250,252,.86);
            border-radius:18px;
            padding:14px;
            min-height:94px;
            display:grid;
            gap:6px;
            transition:.18s ease;
        }
        .builder-tool:hover { transform:translateY(-2px); background:#fff; box-shadow:0 18px 36px rgba(15,23,42,.08); }
        .builder-tool strong { font-size:14px; }
        .builder-tool span { color:var(--muted); font-size:12px; font-weight:850; line-height:1.6; }
        .builder-tool-icon {
            width:34px;
            height:34px;
            border-radius:13px;
            display:grid;
            place-items:center;
            background:#111827;
            color:#feee00;
            font-weight:900;
        }
        .builder-content-visual {
            border-radius:24px;
            min-height:320px;
            background:linear-gradient(135deg,#111827,#253044 52%,#feee00 52%,#fff7b0);
            color:#fff;
            padding:22px;
            position:relative;
            overflow:hidden;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            isolation:isolate;
        }
        .builder-content-visual:before {
            content:"";
            position:absolute;
            inset:auto -54px -70px auto;
            width:210px;
            height:210px;
            border-radius:54px;
            background:rgba(255,255,255,.22);
            transform:rotate(16deg);
            z-index:-1;
        }
        .builder-brand-orb {
            width:118px;
            height:118px;
            border-radius:34px;
            display:grid;
            place-items:center;
            font-size:42px;
            font-weight:900;
            background:rgba(255,255,255,.16);
            border:1px solid rgba(255,255,255,.22);
            backdrop-filter:blur(12px);
        }
        .builder-metric-strip { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .builder-metric {
            border:1px solid rgba(255,255,255,.18);
            background:rgba(255,255,255,.12);
            border-radius:16px;
            padding:12px;
            color:#fff;
        }
        .builder-metric span { display:block; color:#dbeafe; font-size:11px; font-weight:850; }
        .builder-metric strong { display:block; margin-top:4px; font-size:20px; }
        .builder-contact-hub { display:grid; grid-template-columns:.82fr 1.18fr; gap:0; }
        .builder-contact-side {
            padding:30px;
            background:linear-gradient(135deg,#111827,#1f2937);
            color:#fff;
            min-height:430px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            position:relative;
            overflow:hidden;
        }
        .builder-contact-side:after {
            content:"";
            position:absolute;
            inset:auto -58px -86px auto;
            width:230px;
            height:230px;
            border-radius:60px;
            background:rgba(254,238,0,.22);
            transform:rotate(18deg);
        }
        .builder-contact-side p { color:#cbd5e1; }
        .builder-contact-list { display:grid; gap:10px; margin-top:22px; position:relative; z-index:1; }
        .builder-contact-item {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:13px 14px;
            border-radius:16px;
            border:1px solid rgba(255,255,255,.12);
            background:rgba(255,255,255,.08);
            color:#fff;
            font-weight:900;
        }
        .builder-contact-item span { color:#cbd5e1; font-size:12px; font-weight:850; }
        .builder-contact-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:22px; position:relative; z-index:1; }
        .builder-contact-actions .btn { min-width:128px; }
        .builder-contact-form { padding:30px; display:grid; gap:14px; align-content:center; }
        .builder-contact-form .input,
        .builder-contact-form textarea.input { width:100%; min-width:0; }
        .builder-contact-form textarea.input { height:142px; padding-top:14px; resize:vertical; }
        .builder-contact-result {
            display:none;
            margin:0;
            padding:12px 14px;
            border-radius:14px;
            background:#f8fafc;
            color:var(--muted);
            font-weight:900;
        }
        .builder-contact-result.is-success { background:#dcfce7; color:#047857; }
        .builder-contact-result.is-error { background:#fee2e2; color:#b91c1c; }
        .builder-map-card { display:grid; grid-template-columns:1fr .88fr; gap:0; min-height:410px; }
        .builder-map-visual {
            min-height:410px;
            position:relative;
            overflow:hidden;
            background:#eef3f8;
        }
        .builder-map-visual iframe {
            position:absolute;
            inset:0;
            width:100%;
            height:100%;
            border:0;
            filter:saturate(.96) contrast(1.02);
        }
        .builder-map-google-badge {
            position:absolute;
            inset:18px auto auto 18px;
            z-index:2;
            border-radius:999px;
            padding:9px 12px;
            background:rgba(255,255,255,.92);
            color:#111827;
            border:1px solid rgba(219,229,240,.9);
            box-shadow:0 12px 28px rgba(15,23,42,.12);
            font-size:12px;
            font-weight:900;
        }
        .builder-map-route {
            position:absolute;
            inset:38% 12% auto 12%;
            height:6px;
            border-radius:999px;
            background:linear-gradient(90deg,var(--primary),var(--secondary),var(--noon));
            transform:rotate(-5deg);
            box-shadow:0 16px 32px rgba(15,23,42,.16);
        }
        .builder-map-pin {
            position:absolute;
            width:74px;
            height:74px;
            border-radius:24px 24px 24px 8px;
            transform:rotate(-45deg);
            background:#111827;
            box-shadow:0 20px 38px rgba(15,23,42,.22);
            display:grid;
            place-items:center;
            right:22%;
            top:27%;
        }
        .builder-map-pin span {
            transform:rotate(45deg);
            width:34px;
            height:34px;
            border-radius:999px;
            display:grid;
            place-items:center;
            background:#feee00;
            color:#111827;
            font-weight:900;
        }
        .builder-map-info { padding:30px; display:flex; flex-direction:column; justify-content:center; }
        .builder-map-meta { display:grid; gap:10px; margin:22px 0; }
        .builder-map-meta div {
            border:1px solid #dbe5f0;
            background:#f8fafc;
            border-radius:16px;
            padding:13px 14px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            font-weight:900;
        }
        .builder-map-meta span { color:var(--muted); font-size:12px; font-weight:850; }
        .builder-map-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .builder-video-card {
            display:grid;
            grid-template-columns:.92fr 1.08fr;
            gap:0;
            min-height:420px;
            background:
                radial-gradient(circle at 12% 12%, rgba(109,40,217,.16), transparent 28%),
                radial-gradient(circle at 88% 18%, rgba(254,238,0,.16), transparent 24%),
                #fff;
        }
        .builder-video-copy {
            padding:34px;
            display:flex;
            flex-direction:column;
            justify-content:center;
        }
        .builder-video-copy h2 {
            margin:0;
            font-size:clamp(28px,4vw,48px);
            line-height:1.15;
        }
        .builder-video-copy p {
            margin:14px 0 0;
            color:var(--muted);
            font-weight:850;
            line-height:1.9;
        }
        .builder-video-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:24px; }
        .builder-video-frame {
            min-height:420px;
            background:#0f172a;
            position:relative;
            overflow:hidden;
        }
        .builder-video-frame iframe,
        .builder-video-frame video,
        .builder-video-frame img {
            position:absolute;
            inset:0;
            width:100%;
            height:100%;
            border:0;
            object-fit:cover;
        }
        @media (max-width: 900px) {
            .hero-grid,.footer-grid,.form-grid { grid-template-columns:1fr; }
            .builder-content-grid,
            .builder-contact-hub,
            .builder-map-card,
            .builder-video-card { grid-template-columns:1fr; }
            .builder-map-visual { min-height:300px; order:2; }
            .builder-video-frame { min-height:300px; order:2; }
            .grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
            .products-grid { grid-template-columns:repeat(3, minmax(0,1fr)); }
            .promo-grid,.service-bar { grid-template-columns:1fr 1fr; }
            .category-strip { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .wide-offer-banner { align-items:flex-start; flex-direction:column; }
            .trust-head { grid-template-columns:1fr; }
            .trust-summary { justify-content:flex-start; }
            .trust-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .links { order:3; width:100%; overflow:auto; padding-bottom:4px; }
            .nav { height:auto; padding:14px 0; flex-wrap:wrap; }
            .actions { flex:1; justify-content:flex-end; }
            .store-search { width:min(420px, 52vw); }
            .cart-line,.product-row { grid-template-columns:72px 1fr; }
            .cart-line > div:last-child,.product-row > div:last-child { grid-column:1/-1; }
        }
        @media (max-width: 560px) {
            .grid { grid-template-columns:1fr; }
            .products-grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
            .promo-grid,.service-bar { grid-template-columns:1fr; }
            .category-strip { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .trust-grid { grid-template-columns:1fr; }
            .product-tools { opacity:1; transform:none; }
            .footer-bottom { align-items:flex-start; flex-direction:column; }
            .hero-card { padding:26px; min-height:320px; }
            .actions { width:100%; justify-content:space-between; }
            .store-search { width:calc(100% - 122px); }
            .input { width:100%; }
            .section-head { align-items:flex-start; flex-direction:column; }
            .builder-content-grid,
            .builder-contact-side,
            .builder-contact-form,
            .builder-map-info,
            .builder-video-copy { padding:22px; }
            .builder-tool-grid,
            .builder-metric-strip { grid-template-columns:1fr; }
        }
    </style>
    @stack('head')
</head>
<body class="theme-header-{{ $headerStyle }} theme-footer-{{ $footerStyle }} theme-cards-{{ $cardStyle }} theme-buttons-{{ $buttonStyle }} {{ $supportsDark ? 'theme-supports-dark' : 'theme-light-only' }}" data-theme-font="{{ $font }}">
    <div class="store-strip">
        <div class="wrap store-strip-inner">
            <span>عروض يومية وتجربة تسوق سريعة من {{ $storeName }}</span>
            <span>شحن واضح | دفع آمن | منتجات محدثة</span>
        </div>
    </div>
    <header class="topbar">
        <div class="wrap nav">
            <a class="brand" href="{{ $storeRoute() }}">
                <img src="{{ $imageUrl($logo) }}" alt="{{ $storeName }}">
                <span class="brand-name">
                    <span>{{ $storeName }}</span>
                    <span>Solve Store</span>
                </span>
            </a>
            <nav class="links">
                <a href="{{ $storeRoute() }}">الرئيسية</a>
                <a href="{{ $storeRoute('/products') }}">المنتجات</a>
                <a href="{{ $storeRoute('/categories') }}">التصنيفات</a>
                @foreach($headerMenu as $item)
                    @php $url = $item['url'] ?? '#'; @endphp
                    @if(! in_array($url, ['/', '/products'], true))
                        <a href="{{ str_starts_with($url, 'http') ? $url : $storeRoute($url) }}">{{ $item['label'] ?? 'رابط' }}</a>
                    @endif
                @endforeach
            </nav>
            <div class="actions">
                <form class="store-search" action="{{ $storeRoute('/products') }}" method="GET" role="search">
                    <button type="submit" aria-label="بحث">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m20 20-4.4-4.4m2.4-5.1a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <input name="q" value="{{ request('q') }}" placeholder="ابحث عن منتج أو تصنيف..." autocomplete="off">
                </form>
                <a class="btn btn-primary cart-icon-btn" href="{{ $storeRoute('/cart') }}" aria-label="السلة">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6.2 6.4h13.1l-1.2 7.2a2 2 0 0 1-2 1.7H9a2 2 0 0 1-2-1.6L5.3 3.8H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9.4 20.2h.1M16.8 20.2h.1" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
                    </svg>
                    <span class="cart-label">السلة</span>
                    <span id="cartCount" class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @if($storefrontSectionVisible('footer'))
    <footer class="footer">
        <div class="wrap">
            <div class="footer-grid">
                <div class="footer-brand">
                    <span class="visual-chip">Powered by Solve</span>
                    <h2 style="margin:14px 0 10px">{{ $storeName }}</h2>
                    <p class="footer-note">{{ $seo['meta_description'] ?? 'متجر إلكتروني احترافي يعمل على منصة Solve.' }}</p>
                    <div class="payment-badges">
                        <span>Mada</span>
                        <span>Visa</span>
                        <span>Apple Pay</span>
                        <span>COD</span>
                    </div>
                </div>
                <div>
                    <h3 class="footer-title">تسوق</h3>
                    <div class="footer-links">
                        <a href="{{ $storeRoute('/products') }}">كل المنتجات</a>
                        <a href="{{ $storeRoute('/categories') }}">التصنيفات</a>
                        <a href="{{ $storeRoute('/cart') }}">السلة</a>
                        <a href="{{ $storeRoute('/checkout') }}">إتمام الطلب</a>
                    </div>
                </div>
                <div>
                    <h3 class="footer-title">خدمة العملاء</h3>
                    <div class="footer-links">
                        <a href="{{ $storeRoute('/contact') }}">تواصل معنا</a>
                        <a href="{{ $storeRoute('/about') }}">من نحن</a>
                        @foreach($footerMenu->take(3) as $item)
                            @php $url = $item['url'] ?? '#'; @endphp
                            <a href="{{ str_starts_with($url, 'http') ? $url : $storeRoute($url) }}">{{ $item['label'] ?? 'رابط' }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3 class="footer-title">التواصل</h3>
                    <div class="footer-contact">
                        <div><span>الجوال</span>{{ $settings['contact_phone'] ?? $partner['phone'] ?? '-' }}</div>
                        <div><span>البريد</span>{{ $settings['contact_email'] ?? $partner['email'] ?? '-' }}</div>
                        <div><span>أوقات العمل</span>{{ $settings['working_hours'] ?? '-' }}</div>
                    </div>
                    <a class="btn" style="background:#feee00;color:#111827;margin-top:12px" href="{{ $storeRoute('/contact') }}">راسل المتجر</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© {{ date('Y') }} {{ $storeName }}. جميع الحقوق محفوظة.</span>
                <span>واجهة متجر ذكية تعمل على Solve</span>
            </div>
        </div>
    </footer>
    @endif

    <footer class="footer" style="display:none">
        <div class="wrap footer-grid">
            <div>
                <span class="visual-chip">Powered by Solve</span>
                <h2 style="margin:14px 0 10px">{{ $storeName }}</h2>
                <p style="color:#cbd5e1;line-height:1.9;margin:0">{{ $seo['meta_description'] ?? 'متجر إلكتروني احترافي يعمل على منصة Solve.' }}</p>
            </div>
            <div>
                <h3>روابط المتجر</h3>
                <div style="display:grid;gap:10px;color:#cbd5e1;font-weight:800">
                    <a href="{{ $storeRoute('/products') }}">المنتجات</a>
                    <a href="{{ $storeRoute('/about') }}">من نحن</a>
                    <a href="{{ $storeRoute('/contact') }}">اتصل بنا</a>
                    @foreach($footerMenu as $item)
                        @php $url = $item['url'] ?? '#'; @endphp
                        <a href="{{ str_starts_with($url, 'http') ? $url : $storeRoute($url) }}">{{ $item['label'] ?? 'رابط' }}</a>
                    @endforeach
                </div>
            </div>
            <div>
                <h3>التواصل</h3>
                <p style="color:#cbd5e1;line-height:1.9">{{ $settings['contact_phone'] ?? $partner['phone'] ?? '-' }}<br>{{ $settings['contact_email'] ?? $partner['email'] ?? '-' }}<br>{{ $settings['working_hours'] ?? '' }}</p>
            </div>
        </div>
    </footer>
    <div id="cartToast" class="cart-toast">تمت إضافة المنتج للسلة</div>
    <script>
        window.solveStorefront = {
            cartEndpoint: @json($cartEndpoint),
            checkoutEndpoint: @json($checkoutEndpoint),
            eventEndpoint: @json($eventEndpoint),
            newsletterEndpoint: @json(route('api.storefront.newsletter', ['slug' => $slug])),
            contactEndpoint: @json(route('api.storefront.contact', ['slug' => $slug])),
            cartKey: @json('solve_cart_' . $slug)
        };
        window.readStorefrontCart = function() {
            try { return JSON.parse(localStorage.getItem(window.solveStorefront.cartKey) || '[]'); }
            catch (error) { return []; }
        };
        window.writeStorefrontCart = function(items) {
            localStorage.setItem(window.solveStorefront.cartKey, JSON.stringify(items));
            window.updateCartCount();
            window.dispatchEvent(new CustomEvent('solve-cart-updated'));
        };
        window.updateCartCount = function() {
            const count = window.readStorefrontCart().reduce((sum, item) => sum + Number(item.qty || 0), 0);
            const badge = document.getElementById('cartCount');
            if (badge) badge.textContent = count;
        };
        window.removeStorefrontCartItem = function(productId) {
            window.writeStorefrontCart(window.readStorefrontCart().filter(item => String(item.product_id) !== String(productId)));
        };
        window.clearStorefrontCart = function() { window.writeStorefrontCart([]); };
        window.cartApiPayload = function(couponCode = '') {
            return {
                coupon_code: couponCode || undefined,
                items: window.readStorefrontCart().map(item => ({product_id: item.product_id, qty: Number(item.qty || 1)}))
            };
        };
        window.solveToggleListItem = function(key, productId, button) {
            const storageKey = 'solve_' + key + '_{{ $slug }}';
            const current = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const id = String(productId);
            const next = current.includes(id) ? current.filter(item => item !== id) : current.concat(id).slice(-24);
            localStorage.setItem(storageKey, JSON.stringify(next));
            if (button) button.classList.toggle('active', next.includes(id));
            const toast = document.getElementById('cartToast');
            toast.textContent = key === 'compare' ? 'تم تحديث المقارنة' : 'تم تحديث المفضلة';
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 1800);
        };
        window.trackStorefrontEvent = async function(eventName, payload = {}) {
            try {
                await fetch(window.solveStorefront.eventEndpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: JSON.stringify({event: eventName, path: location.pathname, ...payload})
                });
            } catch (error) {}
        };
        window.addStorefrontCartItem = async function(productId, qty = 1, product = {}) {
            const requestedQty = Math.max(1, Number(qty) || 1);
            const current = window.readStorefrontCart();
            const existing = current.find(item => String(item.product_id) === String(productId));
            if (existing) {
                existing.qty = Number(existing.qty || 0) + requestedQty;
            } else {
                current.push({
                    product_id: String(productId),
                    qty: requestedQty,
                    name: product.name || 'منتج',
                    price: product.price || '0 ر.س',
                    image: product.image || @json(asset('solve-logo.png')),
                    sku: product.sku || '',
                    url: product.url || ''
                });
            }
            window.writeStorefrontCart(current);
            const response = await fetch(window.solveStorefront.cartEndpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify(window.cartApiPayload())
            });
            window.trackStorefrontEvent('add_to_cart', {product_id: productId});
            const toast = document.getElementById('cartToast');
            toast.textContent = response.ok ? 'تمت إضافة المنتج للسلة' : 'تعذر تحديث السلة';
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 2400);
            return response.json();
        };
        window.updateCartCount();
        window.trackStorefrontEvent('page_view');
    </script>
    @stack('scripts')
</body>
</html>
