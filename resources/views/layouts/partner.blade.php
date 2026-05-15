<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Solve Merchant')</title>
    <link rel="icon" type="image/png" href="{{ asset('solve-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Tajawal', 'sans-serif'] },
                    colors: {
                        solve: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            500: '#7c3aed',
                            600: '#6d28d9',
                            700: '#5b21b6',
                            900: '#2e1065'
                        }
                    },
                    boxShadow: {
                        card: '0 12px 34px rgba(15,23,42,0.07)'
                    }
                }
            }
        };
    </script>
    <style>
        body { font-family: 'Tajawal', sans-serif; }
        [x-cloak] { display: none !important; }
        .sidebar-scroll::-webkit-scrollbar { width: 6px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #d8dbe3; border-radius: 999px; }
    </style>
</head>
@php
    $activeSection = $activeSection ?? null;
    $activePage = $activePage ?? null;
    $commandPages = collect($partnerSections ?? [])
        ->flatMap(function (array $section) {
            return collect($section['items'] ?? [])->map(function (array $item) use ($section) {
                $url = $item['key'] === 'home'
                    ? route('partner.dashboard')
                    : (($item['legacyRoute'] ?? null)
                        ? (($section['key'] === 'settings' && $item['legacyRoute'] === 'partner.settings.section')
                            ? route($item['legacyRoute'], ['section' => $item['key'] === 'payment-shipping' ? 'shipping' : $item['key']])
                            : route($item['legacyRoute']))
                        : route('partner.pages.show', ['section' => $section['key'], 'page' => $item['key']]));

                return ['label' => $item['label'], 'section' => $section['label'], 'url' => $url];
            });
        })
        ->values()
        ->all();
@endphp
<body class="bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-slate-100"
    x-data="{
        mobileNav: false,
        query: '',
        commandOpen: false,
        commandQuery: '',
        commands: @js($commandPages),
        dark: localStorage.getItem('solve.dark') === '1',
        open: JSON.parse(localStorage.getItem('solve.sidebar.open.v2') || 'null') || ['dashboard', 'orders', 'products', 'customers', 'marketing', 'storefront', 'analytics', 'finance', 'services', 'channels', 'apps', 'settings'],
        favorites: JSON.parse(localStorage.getItem('solve.sidebar.favorites') || '[]'),
        toggleDark() { this.dark = !this.dark; localStorage.setItem('solve.dark', this.dark ? '1' : '0'); },
        toggleSection(key) {
            this.open = this.open.includes(key) ? this.open.filter(item => item !== key) : [...this.open, key];
            localStorage.setItem('solve.sidebar.open.v2', JSON.stringify(this.open));
        },
        toggleFavorite(label, url) {
            const exists = this.favorites.some(item => item.url === url);
            this.favorites = exists ? this.favorites.filter(item => item.url !== url) : [{ label, url }, ...this.favorites].slice(0, 6);
            localStorage.setItem('solve.sidebar.favorites', JSON.stringify(this.favorites));
        },
        filteredCommands() {
            const q = this.commandQuery.trim();
            return this.commands.filter(item => q === '' || (item.label + ' ' + item.section).includes(q)).slice(0, 9);
        },
        goCommand(url) {
            this.commandOpen = false;
            window.location.href = url;
        }
    }"
    :class="{ 'dark': dark }"
    @keydown.window.ctrl.k.prevent="commandOpen = true; commandQuery = ''"
    @keydown.window.meta.k.prevent="commandOpen = true; commandQuery = ''"
    @keydown.window.escape="commandOpen = false">
    <div class="min-h-screen">
        <div class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm lg:hidden" x-show="mobileNav" x-cloak @click="mobileNav = false"></div>
        <div class="fixed inset-0 z-[70] bg-slate-950/45 p-4 backdrop-blur-sm" x-show="commandOpen" x-cloak @click.self="commandOpen = false">
            <div class="mx-auto mt-20 max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800" data-testid="partner-command-palette">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    @include('partner.partials.icon', ['name' => 'search', 'class' => 'h-5 w-5 text-slate-400'])
                    <input x-model="commandQuery" x-ref="commandInput" x-effect="if (commandOpen) $nextTick(() => $refs.commandInput.focus())"
                        type="search" placeholder="اكتب اسم صفحة أو إجراء سريع"
                        class="h-11 flex-1 bg-transparent text-base font-black outline-none dark:text-white">
                    <span class="rounded-xl bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-500 dark:bg-slate-800">Ctrl K</span>
                </div>
                <div class="max-h-[420px] overflow-y-auto p-3">
                    <template x-for="item in filteredCommands()" :key="item.url">
                        <button type="button" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-right transition hover:bg-solve-50 dark:hover:bg-slate-800"
                            @click="goCommand(item.url)">
                            <span>
                                <span class="block text-sm font-black text-slate-950 dark:text-white" x-text="item.label"></span>
                                <span class="mt-1 block text-xs font-bold text-slate-400" x-text="item.section"></span>
                            </span>
                            @include('partner.partials.icon', ['name' => 'chevron', 'class' => 'h-4 w-4 text-slate-400'])
                        </button>
                    </template>
                    <div x-show="filteredCommands().length === 0" class="px-4 py-10 text-center text-sm font-black text-slate-500">لا توجد نتيجة مطابقة.</div>
                </div>
            </div>
        </div>

        <aside class="fixed inset-y-0 right-0 z-50 flex w-[292px] flex-col border-l border-slate-200/80 bg-white shadow-card transition-transform duration-300 dark:border-slate-800 dark:bg-slate-900"
            :class="mobileNav ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4 dark:border-slate-800">
                <a href="{{ route('partner.dashboard') }}" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-solve-50 ring-1 ring-solve-100 dark:bg-solve-900/40 dark:ring-solve-500/20">
                        <img src="{{ asset('solve-logo.png') }}" alt="Solve" class="h-8 w-auto object-contain">
                    </span>
                    <span>
                        <span class="block text-xs font-black uppercase tracking-[0.24em] text-solve-600 dark:text-solve-300">Solve</span>
                        <span class="block text-sm font-black text-slate-900 dark:text-white">{{ $partner['name'] ?? 'لوحة التاجر' }}</span>
                    </span>
                </a>
                <button type="button" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" @click="mobileNav = false" aria-label="إغلاق القائمة">
                    @include('partner.partials.icon', ['name' => 'x'])
                </button>
            </div>

            <div class="border-b border-slate-100 p-4 dark:border-slate-800">
                <label class="relative block">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                        @include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])
                    </span>
                    <input x-model="query" type="search" placeholder="بحث داخل القائمة"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pr-10 pl-3 text-sm font-bold outline-none transition focus:border-solve-300 focus:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:border-solve-500">
                </label>
            </div>

            <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 py-4">
                <div class="space-y-2">
                    @foreach (($partnerSections ?? []) as $section)
                        @php $sectionActive = $activeSection === $section['key'] || (($activeRoute ?? '') === 'partner.dashboard' && $section['key'] === 'dashboard'); @endphp
                        <div class="rounded-2xl" x-data="{ sectionLabel: @js($section['label']) }" x-show="query === '' || sectionLabel.includes(query) || $el.innerText.includes(query)">
                            <button type="button"
                                class="flex w-full items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-black transition {{ $sectionActive ? 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}"
                                @click="toggleSection('{{ $section['key'] }}')">
                                <span class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $sectionActive ? 'bg-white text-solve-700 dark:bg-slate-900 dark:text-solve-200' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                        @include('partner.partials.icon', ['name' => $section['icon'], 'class' => 'h-4 w-4'])
                                    </span>
                                    {{ $section['label'] }}
                                </span>
                                <span class="transition-transform" :class="open.includes('{{ $section['key'] }}') ? '-rotate-90' : ''">
                                    @include('partner.partials.icon', ['name' => 'chevron', 'class' => 'h-4 w-4'])
                                </span>
                            </button>

                            <div class="mt-1 space-y-1 overflow-hidden pr-5" x-show="open.includes('{{ $section['key'] }}') || query !== ''" x-transition>
                                @foreach ($section['items'] as $item)
                                    @php
                                        $url = $item['key'] === 'home'
                                            ? route('partner.dashboard')
                                            : (($item['legacyRoute'] ?? null)
                                                ? (($section['key'] === 'settings' && $item['legacyRoute'] === 'partner.settings.section')
                                                    ? route($item['legacyRoute'], ['section' => $item['key'] === 'payment-shipping' ? 'shipping' : $item['key']])
                                                    : route($item['legacyRoute']))
                                                : route('partner.pages.show', ['section' => $section['key'], 'page' => $item['key']]));
                                        $itemActive = ($activeSection === $section['key'] && $activePage === $item['key'])
                                            || (($activeRoute ?? '') === 'partner.dashboard' && $item['key'] === 'home')
                                            || (($activeRoute ?? '') === ($item['legacyRoute'] ?? null));
                                    @endphp
                                    <div class="group flex items-center gap-1" x-data="{ label: @js($item['label']), url: @js($url) }" x-show="query === '' || label.includes(query)">
                                        <a href="{{ $url }}"
                                            title="{{ $item['locked'] ?? false ? ($item['lock_reason'] ?? 'Upgrade required') : '' }}"
                                            class="flex min-h-9 flex-1 items-center justify-between rounded-xl px-3 py-2 text-sm font-bold transition {{ $itemActive ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' }} {{ $item['locked'] ?? false ? 'opacity-70' : '' }}">
                                            <span>{{ $item['label'] }}</span>
                                            @if ($item['locked'] ?? false)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black text-amber-700">Lock</span>
                                            @elseif (($item['plan'] ?? 'Starter') !== 'Starter')
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-500 dark:bg-slate-800">{{ $item['plan'] }}</span>
                                            @endif
                                        </a>
                                        <button type="button" class="rounded-lg p-2 text-slate-300 opacity-0 transition hover:bg-slate-100 hover:text-amber-500 group-hover:opacity-100 dark:hover:bg-slate-800"
                                            @click="toggleFavorite(label, url)" aria-label="إضافة للمفضلة">
                                            @include('partner.partials.icon', ['name' => 'star', 'class' => 'h-4 w-4'])
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div class="px-3 text-xs font-black uppercase tracking-wide text-slate-400">المفضلة</div>
                    <div class="mt-2 space-y-1" x-show="favorites.length" x-cloak>
                        <template x-for="favorite in favorites" :key="favorite.url">
                            <a :href="favorite.url" class="block rounded-xl px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800" x-text="favorite.label"></a>
                        </template>
                    </div>
                    <p class="mt-2 px-3 text-xs font-bold text-slate-400" x-show="!favorites.length">استخدم النجمة بجانب أي صفحة.</p>
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div class="px-3 text-xs font-black uppercase tracking-wide text-slate-400">آخر الصفحات</div>
                    <div class="mt-2 space-y-1">
                        @forelse (($recentPages ?? []) as $recent)
                            <a href="{{ $recent['url'] }}" class="block rounded-xl px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">{{ $recent['label'] }}</a>
                        @empty
                            <p class="px-3 py-2 text-xs font-bold text-slate-400">لا توجد صفحات حديثة بعد.</p>
                        @endforelse
                    </div>
                </div>
            </nav>

            <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $partnerUser['name'] ?? '' }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $roleLabel ?? '' }} · {{ $partner['plan'] ?? '' }}</p>
                        </div>
                        <button type="button" class="rounded-xl p-2 text-slate-500 hover:bg-white dark:hover:bg-slate-800" @click="toggleDark()" aria-label="تبديل الوضع الليلي">
                            @include('partner.partials.icon', ['name' => 'moon', 'class' => 'h-4 w-4'])
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-w-0 lg:mr-[292px]">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
                <div class="flex h-[68px] items-center justify-between gap-4 px-4 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="rounded-xl p-3 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" @click="mobileNav = true" aria-label="فتح القائمة">
                            @include('partner.partials.icon', ['name' => 'menu'])
                        </button>
                        <div>
                            <p class="text-xs font-black text-slate-400">Store ID</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $partner['store_id'] ?? '' }}</p>
                        </div>
                    </div>

                    <div class="hidden min-w-0 flex-1 justify-center md:flex">
                        <div class="flex w-full max-w-xl items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-800 dark:bg-slate-900"
                            x-data="{ globalSearch: '', submit() { const q = this.globalSearch.trim(); if (q) window.location.href = @js(route('partner.orders')) + '?q=' + encodeURIComponent(q); } }">
                            @include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4 text-slate-400'])
                            <input x-model="globalSearch" @keydown.enter.prevent="submit()" type="search" placeholder="بحث سريع في الطلبات والمنتجات والعملاء"
                                class="h-8 flex-1 bg-transparent text-sm font-bold outline-none dark:text-white">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="commandOpen = true; commandQuery = ''"
                            class="hidden rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-500 hover:bg-slate-50 md:inline-flex dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            Command K
                        </button>
                        <a href="{{ route('partner.orders.manual') }}" class="hidden rounded-xl bg-solve-600 px-4 py-2 text-sm font-black text-white transition hover:bg-solve-700 sm:inline-flex">إجراء سريع</a>
                        <form method="POST" action="{{ route('partner.logout') }}">
                            @csrf
                            <button class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">خروج</button>
                        </form>
                    </div>
                </div>
            </header>

            @yield('partner-content')
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pageKey = 'solve.filters.' + window.location.pathname;
            document.querySelectorAll('form[method="GET"], form:not([method])').forEach((form) => {
                form.addEventListener('submit', () => {
                    const data = {};
                    new FormData(form).forEach((value, key) => { data[key] = value; });
                    localStorage.setItem(pageKey, JSON.stringify(data));
                });
            });

            if (! window.location.search) {
                const saved = JSON.parse(localStorage.getItem(pageKey) || '{}');
                Object.entries(saved).forEach(([key, value]) => {
                    const field = document.querySelector(`[name="${CSS.escape(key)}"]`);
                    if (field && value !== '') field.value = value;
                });
            }
        });
    </script>
</body>
</html>
