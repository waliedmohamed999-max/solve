@php
    $groups = [
        [
            'label' => 'لوحة التحكم',
            'icon' => '▦',
            'items' => [
                ['label' => 'الرئيسية', 'route' => 'admin.dashboard'],
                ['label' => 'آخر النشاطات', 'route' => 'admin.activity'],
                ['label' => 'التنبيهات', 'route' => 'admin.notifications'],
            ],
        ],
        [
            'label' => 'الطلبات',
            'icon' => '▱',
            'items' => [
                ['label' => 'جميع الطلبات', 'route' => 'admin.orders'],
                ['label' => 'الشحنات', 'route' => 'admin.shipping'],
                ['label' => 'المرتجعات', 'route' => 'admin.orders', 'params' => ['status' => 'returns']],
                ['label' => 'الفواتير', 'route' => 'admin.invoices'],
            ],
        ],
        [
            'label' => 'المنتجات',
            'icon' => '▤',
            'items' => [
                ['label' => 'قائمة المنتجات', 'route' => 'admin.products'],
                ['label' => 'التصنيفات', 'route' => 'admin.products', 'params' => ['view' => 'categories']],
                ['label' => 'المخزون', 'route' => 'admin.inventory'],
                ['label' => 'الكوبونات', 'route' => 'admin.coupons'],
            ],
        ],
        [
            'label' => 'العملاء',
            'icon' => '♧',
            'items' => [
                ['label' => 'قائمة العملاء', 'route' => 'admin.customers'],
                ['label' => 'التقييمات', 'route' => 'admin.reviews'],
                ['label' => 'الرسائل', 'route' => 'admin.messages'],
                ['label' => 'الدعم الفني', 'route' => 'admin.support'],
            ],
        ],
        [
            'label' => 'التسويق',
            'icon' => '◁',
            'items' => [
                ['label' => 'الحملات', 'route' => 'admin.marketing-campaigns'],
                ['label' => 'السلات المتروكة', 'route' => 'admin.abandoned-carts'],
                ['label' => 'الولاء والنقاط', 'route' => 'admin.loyalty'],
                ['label' => 'التوصيات', 'route' => 'admin.smart-recommendations'],
            ],
        ],
        [
            'label' => 'المتجر الإلكتروني',
            'icon' => '▧',
            'items' => [
                ['label' => 'المتاجر', 'route' => 'admin.stores'],
                ['label' => 'محتوى المتجر', 'route' => 'admin.store-content'],
                ['label' => 'تجهيز المتجر', 'route' => 'admin.onboarding'],
                ['label' => 'الدومين', 'route' => 'admin.stores.settings', 'params' => ['store' => 'store-atlas']],
            ],
        ],
        [
            'label' => 'التحليلات',
            'icon' => '▥',
            'items' => [
                ['label' => 'الإحصائيات', 'route' => 'admin.analytics'],
                ['label' => 'التقارير', 'route' => 'admin.reports'],
                ['label' => 'صحة المتجر', 'route' => 'admin.store-health'],
                ['label' => 'بحث سريع', 'route' => 'admin.analytics'],
            ],
        ],
        [
            'label' => 'المالية',
            'icon' => '▭',
            'items' => [
                ['label' => 'المدفوعات', 'route' => 'admin.payments'],
                ['label' => 'الباقات', 'route' => 'admin.plans'],
                ['label' => 'التسويات', 'route' => 'admin.payouts'],
                ['label' => 'العمولات', 'route' => 'admin.commissions'],
            ],
        ],
    ];

    $secondaryGroups = [
        [
            'label' => 'الخدمات',
            'items' => [
                ['label' => 'خدمات الشركاء', 'route' => 'admin.partners'],
                ['label' => 'البلاغات والمخالفات', 'route' => 'admin.moderation'],
            ],
        ],
        [
            'label' => 'القنوات',
            'items' => [
                ['label' => 'مركز الرسائل', 'route' => 'admin.messages'],
                ['label' => 'الإشعارات', 'route' => 'admin.notifications'],
            ],
        ],
        [
            'label' => 'التطبيقات',
            'items' => [
                ['label' => 'التكاملات', 'route' => 'admin.integrations'],
                ['label' => 'ذكاء Solve', 'route' => 'admin.solve-ai'],
                ['label' => 'بوابات الدفع', 'route' => 'admin.integrations', 'params' => ['category' => 'payments']],
                ['label' => 'شركات الشحن', 'route' => 'admin.integrations', 'params' => ['category' => 'shipping']],
            ],
        ],
        [
            'label' => 'الإعدادات',
            'items' => [
                ['label' => 'إعدادات المنصة', 'route' => 'admin.settings'],
                ['label' => 'الموظفين', 'route' => 'admin.staff'],
                ['label' => 'الصلاحيات', 'route' => 'admin.roles'],
                ['label' => 'جاهزية الإنتاج', 'route' => 'admin.production-readiness'],
            ],
        ],
    ];

    $allGroups = array_merge($groups, $secondaryGroups);

    $isGroupActive = function (array $group) use ($activeRoute): bool {
        return collect($group['items'])->contains(fn (array $item) => ($activeRoute ?? '') === $item['route']);
    };
@endphp

<aside
    class="fixed inset-y-0 right-0 z-40 w-[254px] overflow-y-auto border-l border-slate-100 bg-white px-3 py-5 shadow-card"
    :class="mobileNav ? 'block' : 'hidden lg:block'"
    x-data="{
        query: '',
        open: @js(collect($allGroups)->mapWithKeys(fn ($group, $index) => [$index => $isGroupActive($group)])->all()),
        toggle(index) { this.open[index] = ! this.open[index] },
        visible(label, items) {
            const term = this.query.trim().toLowerCase();
            return term === '' || label.toLowerCase().includes(term) || items.toLowerCase().includes(term);
        }
    }"
>
    <div class="flex items-center justify-between px-3">
        <div class="flex items-center gap-3">
            <img src="{{ asset('solve-logo.png') }}" alt="Solve" class="h-10 w-auto object-contain">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-600">Solve</p>
                <h1 class="text-xl font-black text-slate-950">لوحة الإدارة</h1>
            </div>
        </div>
        <button type="button" class="rounded-xl p-2 text-slate-500 lg:hidden" @click="mobileNav = false">×</button>
    </div>

    <label class="relative mt-5 block px-1">
        <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">⌕</span>
        <input x-model="query" type="search" placeholder="ابحث في القائمة"
            class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-10 pl-3 text-sm font-bold outline-none transition focus:border-brand-300 focus:bg-white">
    </label>

    <div class="mx-1 mt-4 rounded-3xl bg-slate-50 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-black text-slate-900">Solve SaaS</p>
                <p class="mt-1 text-xs font-bold text-slate-500">بيع المتاجر وإدارة الشركاء</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">نشط</span>
        </div>
        <div class="mt-3 h-2 rounded-full bg-white">
            <div class="h-2 rounded-full bg-brand-500" style="width: 82%"></div>
        </div>
    </div>

    <nav class="mt-6 space-y-1 text-sm font-bold">
        @foreach ($groups as $index => $group)
            @php
                $groupActive = $isGroupActive($group);
                $searchText = collect($group['items'])->pluck('label')->implode(' ');
            @endphp
            <section x-show="visible(@js($group['label']), @js($searchText))" x-transition>
                <button type="button"
                    class="{{ $groupActive ? 'bg-slate-100 text-brand-900' : 'text-slate-700 hover:bg-slate-50' }} flex w-full items-center justify-between rounded-2xl px-4 py-3 transition"
                    @click="toggle({{ $index }})">
                    <span>{{ $group['label'] }}</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl text-lg text-slate-500">{{ $group['icon'] }}</span>
                </button>
                <div class="mt-1 space-y-1 pr-4" x-cloak x-show="open[{{ $index }}] || query.trim() !== ''" x-transition>
                    @foreach ($group['items'] as $item)
                        @php
                            $isActive = ($activeRoute ?? '') === $item['route'];
                            $params = $item['params'] ?? [];
                        @endphp
                        <a href="{{ route($item['route'], $params) }}"
                            x-show="query.trim() === '' || @js($item['label']).toLowerCase().includes(query.trim().toLowerCase()) || @js($group['label']).toLowerCase().includes(query.trim().toLowerCase())"
                            class="{{ $isActive ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }} block rounded-xl px-4 py-2.5 text-xs font-black transition">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </nav>

    <div class="mt-6 space-y-1 border-t border-slate-100 pt-4 text-sm font-bold">
        @foreach ($secondaryGroups as $secondaryIndex => $group)
            @php
                $index = count($groups) + $secondaryIndex;
                $groupActive = $isGroupActive($group);
                $searchText = collect($group['items'])->pluck('label')->implode(' ');
            @endphp
            <section x-show="visible(@js($group['label']), @js($searchText))" x-transition>
                <button type="button"
                    class="{{ $groupActive ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }} flex w-full items-center justify-between rounded-2xl px-4 py-3 transition"
                    @click="toggle({{ $index }})">
                    <span>{{ $group['label'] }}</span>
                    <span x-text="open[{{ $index }}] ? '⌃' : '‹'"></span>
                </button>
                <div class="mt-1 space-y-1 pr-4" x-cloak x-show="open[{{ $index }}] || query.trim() !== ''" x-transition>
                    @foreach ($group['items'] as $item)
                        @php
                            $isActive = ($activeRoute ?? '') === $item['route'];
                            $params = $item['params'] ?? [];
                        @endphp
                        <a href="{{ route($item['route'], $params) }}"
                            x-show="query.trim() === '' || @js($item['label']).toLowerCase().includes(query.trim().toLowerCase()) || @js($group['label']).toLowerCase().includes(query.trim().toLowerCase())"
                            class="{{ $isActive ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }} block rounded-xl px-4 py-2.5 text-xs font-black transition">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</aside>
