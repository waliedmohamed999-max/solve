@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $partner['name'])

@section('partner-content')
@php
    $kpis = collect($dashboard['kpis'])->keyBy('key');
    $ordersToday = $kpis->get('orders_today', ['label' => 'الطلبات', 'value' => '0', 'hint' => 'من قاعدة بيانات الطلبات']);
    $salesToday = $kpis->get('sales_today', ['label' => 'مبيعات اليوم', 'value' => '0 ر.س', 'hint' => 'طلبات اليوم المدفوعة']);
    $visitorsTotal = $kpis->get('visitors_total', ['label' => 'الزوار', 'value' => '0', 'hint' => 'زيارات الفترة المحددة']);
    $productsTotal = $kpis->get('products_total', ['label' => 'المنتجات', 'value' => '0', 'hint' => 'منتجات المتجر']);
    $customersTotal = $kpis->get('customers_total', ['label' => 'العملاء', 'value' => '0', 'hint' => 'عملاء المتجر']);
    $newCustomers = $kpis->get('new_customers', ['label' => 'العملاء الجدد', 'value' => '0', 'hint' => 'عملاء الفترة المحددة']);
    $pendingOrders = $kpis->get('pending_orders', ['label' => 'طلبات تحتاج متابعة', 'value' => '0', 'hint' => 'قيد المعالجة']);
    $awaitingShipping = $kpis->get('awaiting_shipping', ['label' => 'بانتظار الشحن', 'value' => '0', 'hint' => 'طلبات تحتاج تسليم للناقل']);
    $lowStock = $kpis->get('low_stock', ['label' => 'منتجات منخفضة المخزون', 'value' => '0', 'hint' => 'أقل من حد التنبيه']);
    $goalCurrent = (int) preg_replace('/[^\d]/', '', (string) ($ordersToday['value'] ?? 0));
    $goalTarget = $dashboard['goal']['target'];
    $goalPercent = $dashboard['goal']['progress'];
    $periodDays = $dashboard['period']['days'];
    $periodStart = now()->subDays($periodDays - 1)->translatedFormat('j F');
    $periodEnd = now()->translatedFormat('j F Y');
    $maxOrdersChart = max(1, collect($dashboard['charts']['orders'])->max('value') ?: 1);
    $maxSalesChart = max(1, collect($dashboard['charts']['sales'])->max('value') ?: 1);
    $featuredKpis = collect($dashboard['featuredKpis'] ?? [])->keyBy('key');
    $featuredCards = [
        ['title' => 'طلبات اليوم', 'kpi' => $featuredKpis->get('orders_today', $ordersToday), 'icon' => 'shopping-bag', 'url' => route('partner.orders')],
        ['title' => 'مبيعات اليوم', 'kpi' => $featuredKpis->get('sales_today', $salesToday), 'icon' => 'bar-chart', 'url' => route('partner.analytics.sales')],
        ['title' => 'إجمالي المنتجات', 'kpi' => $featuredKpis->get('products_total', $productsTotal), 'icon' => 'package', 'url' => route('partner.products')],
        ['title' => 'إجمالي العملاء', 'kpi' => $featuredKpis->get('customers_total', $customersTotal), 'icon' => 'users', 'url' => route('partner.customers')],
        ['title' => 'الطلبات المعلقة', 'kpi' => $featuredKpis->get('pending_orders', $pendingOrders), 'icon' => 'bolt', 'url' => route('partner.orders')],
        ['title' => 'منخفض المخزون', 'kpi' => $featuredKpis->get('low_stock', $lowStock), 'icon' => 'store', 'url' => route('partner.products.inventory')],
    ];
    $smartAlerts = $smart['alerts'] ?? [];
    $smartRecommendations = $smart['recommendations'] ?? [];
    $smartInventory = $smart['inventory_forecast'] ?? [];
@endphp

<div class="min-h-[calc(100vh-68px)] bg-[#f5f7fb] px-4 py-6 lg:px-8 dark:bg-slate-950"
    x-data="{
        loading: true,
        error: '',
        assistantOpen: false,
        assistantMessage: '',
        assistantAnswer: '',
        assistantActions: [],
        assistantLoading: false,
        async refresh() {
            this.loading = true;
            this.error = '';
            try {
                const response = await fetch(@js($dashboard['apiUrl']), { headers: { 'Accept': 'application/json' } });
                if (! response.ok) throw new Error('تعذر تحميل ملخص الداشبورد');
                await response.json();
            } catch (exception) {
                this.error = exception.message || 'حدث خطأ غير متوقع';
            } finally {
                this.loading = false;
            }
        },
        async askAssistant(message = '') {
            const body = (message || this.assistantMessage).trim();
            if (! body) return;
            this.assistantOpen = true;
            this.assistantLoading = true;
            this.assistantAnswer = '';
            this.assistantActions = [];
            try {
                const response = await fetch(@js(route('api.partner.ai.assistant')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token())
                    },
                    body: JSON.stringify({ message: body })
                });
                if (! response.ok) throw new Error('تعذر تشغيل مساعد Solve الآن');
                const payload = await response.json();
                this.assistantAnswer = payload.answer;
                this.assistantActions = payload.actions || [];
                this.assistantMessage = '';
            } catch (exception) {
                this.assistantAnswer = exception.message || 'حدث خطأ غير متوقع';
            } finally {
                this.assistantLoading = false;
            }
        }
    }"
    x-init="refresh()">

    <section class="rounded-[28px] bg-white p-5 shadow-[0_24px_80px_rgba(15,23,42,0.08)] dark:bg-slate-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('partner.products.new') }}" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white transition hover:bg-solve-700 dark:bg-white dark:text-slate-950">إضافة منتج</a>
                <a href="{{ route('partner.notifications') }}" class="relative flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 text-amber-500 dark:border-slate-700">
                    <span class="absolute right-3 top-2 h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                    @include('partner.partials.icon', ['name' => 'megaphone', 'class' => 'h-5 w-5'])
                </a>
                <form method="POST" action="{{ route('partner.logout') }}">
                    @csrf
                    <button class="h-12 rounded-2xl border border-slate-200 px-5 text-sm font-black text-slate-500 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">خروج</button>
                </form>
            </div>

            <label class="relative block w-full max-w-md" x-data="{ heroSearch: '', submit() { const q = this.heroSearch.trim(); if (q) window.location.href = @js(route('partner.orders')) + '?q=' + encodeURIComponent(q); } }">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                    @include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])
                </span>
                <input x-model="heroSearch" @keydown.enter.prevent="submit()" type="search" placeholder="ابحث عن طلب، منتج، عميل"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-11 pl-4 text-sm font-black outline-none transition focus:border-solve-300 focus:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </label>
        </div>
    </section>

    <section class="mt-8 grid gap-5 xl:grid-cols-[1fr_390px] xl:items-end">
        <div class="text-right">
            <h1 class="text-3xl font-black tracking-tight text-slate-950 dark:text-white">يا هلا بفريق {{ $dashboard['store']['name'] }}</h1>
            <p class="mt-3 text-sm font-black leading-7 text-slate-500">تابع متجرك من مكان واحد - واجهة خفيفة لإدارة الطلبات والمبيعات والنمو اليومي.</p>
        </div>

        <div class="flex min-h-14 flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex gap-2">
                @foreach ($dashboard['period']['options'] as $option)
                    <a href="{{ route('partner.dashboard', ['period' => $option]) }}" class="rounded-xl px-4 py-2 text-sm font-black {{ $periodDays === $option ? 'bg-solve-600 text-white' : 'bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">آخر {{ $option }} يوم</a>
                @endforeach
            </div>
            <span class="text-sm font-black text-slate-950 dark:text-white">{{ $periodStart }} - {{ $periodEnd }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('partner.analytics', ['period' => $periodDays]) }}" class="rounded-xl bg-slate-50 px-4 py-2 text-xs font-black text-slate-600 transition hover:bg-solve-50 hover:text-solve-700 dark:bg-slate-800 dark:text-slate-200">عرض التقارير</a>
                <a href="{{ route('partner.dashboard.export', ['period' => $periodDays]) }}" class="rounded-xl bg-slate-50 px-4 py-2 text-xs font-black text-slate-600 transition hover:bg-solve-50 hover:text-solve-700 dark:bg-slate-800 dark:text-slate-200">تصدير الملخص</a>
                <button type="button" @click="refresh()" class="rounded-xl bg-solve-600 px-4 py-2 text-xs font-black text-white transition hover:bg-solve-700">تحديث البيانات</button>
            </div>
        </div>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">منحنى الطلبات</h2>
                <a href="{{ route('partner.analytics.sales') }}" class="text-sm font-black text-solve-600">تفاصيل التقرير</a>
            </div>
            <div class="mt-6 flex h-44 items-end gap-1">
                @foreach ($dashboard['charts']['orders'] as $point)
                    <div class="group flex flex-1 flex-col items-center gap-2">
                        <div title="{{ $point['date'] }}: {{ $point['value'] }}" class="w-full rounded-t-lg bg-solve-500/80 transition group-hover:bg-solve-700" style="height: {{ max(8, ((float) $point['value'] / $maxOrdersChart) * 150) }}px"></div>
                        @if ($loop->first || $loop->last || $loop->iteration % max(1, (int) floor(count($dashboard['charts']['orders']) / 5)) === 0)
                            <span class="text-[10px] font-bold text-slate-400">{{ $point['label'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">منحنى المبيعات</h2>
                <a href="{{ route('partner.analytics.finance') }}" class="text-sm font-black text-solve-600">التقرير المالي</a>
            </div>
            <div class="mt-6 flex h-44 items-end gap-1">
                @foreach ($dashboard['charts']['sales'] as $point)
                    <div class="group flex flex-1 flex-col items-center gap-2">
                        <div title="{{ $point['date'] }}: {{ number_format($point['value']) }} ر.س" class="w-full rounded-t-lg bg-cyan-400 transition group-hover:bg-cyan-600" style="height: {{ max(8, ((float) $point['value'] / $maxSalesChart) * 150) }}px"></div>
                        @if ($loop->first || $loop->last || $loop->iteration % max(1, (int) floor(count($dashboard['charts']['sales']) / 5)) === 0)
                            <span class="text-[10px] font-bold text-slate-400">{{ $point['label'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1.1fr_0.9fr]" data-testid="smart-dashboard">
        <article class="rounded-2xl bg-slate-950 p-6 text-white shadow-[0_22px_70px_rgba(15,23,42,0.18)] dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-300">Solve Intelligence</p>
                    <h2 class="mt-2 text-2xl font-black">Smart Store Health</h2>
                    <p class="mt-2 text-sm font-bold leading-7 text-slate-300">لوحة ذكية تعرض الأولويات حسب بيانات متجرك فقط.</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-5 py-4 text-center">
                    <p class="text-xs font-black text-slate-300">Health Score</p>
                    <p class="mt-1 text-4xl font-black">{{ $smart['health']['score'] ?? 0 }}%</p>
                    <p class="mt-1 text-xs font-black text-cyan-200">{{ $smart['health']['label'] ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-2">
                @foreach (($smart['health']['drivers'] ?? []) as $driver)
                    <div class="rounded-2xl bg-white/10 px-4 py-3">
                        <p class="text-xs font-black text-slate-400">{{ $driver['label'] }}</p>
                        <p class="mt-1 text-sm font-black text-white">{{ $driver['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid gap-3">
                @forelse ($smartAlerts as $alert)
                    <a href="{{ $alert['url'] ?? '#' }}" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-black">{{ $alert['title'] }}</h3>
                            <span class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-black text-cyan-100">{{ $alert['priority'] }}</span>
                        </div>
                        <p class="mt-2 text-xs font-bold leading-6 text-slate-300">{{ $alert['body'] }}</p>
                    </a>
                @empty
                    <div class="rounded-2xl bg-white/5 p-6 text-center text-sm font-black text-slate-300">لا توجد تنبيهات ذكية حالياً.</div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">مساعد Solve AI</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">اسأل عن الأداء، التسويق، التسعير، أو المنتجات.</p>
                </div>
                <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">متصل بالبيانات</span>
            </div>

            <div class="mt-4 grid gap-2">
                @foreach (($smart['assistant']['suggested_prompts'] ?? []) as $prompt)
                    <button type="button" @click="askAssistant(@js($prompt))" class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-right text-sm font-black text-slate-700 transition hover:border-solve-200 hover:bg-solve-50 hover:text-solve-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">
                        {{ $prompt }}
                    </button>
                @endforeach
            </div>

            <div class="mt-4 flex gap-2">
                <input x-model="assistantMessage" @keydown.enter.prevent="askAssistant()" type="text" placeholder="مثال: لماذا انخفضت المبيعات؟"
                    class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-black outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <button type="button" @click="askAssistant()" class="rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white transition hover:bg-solve-700">اسأل</button>
            </div>

            <div x-show="assistantOpen" x-cloak class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                <p x-show="assistantLoading" class="text-sm font-black text-slate-500">جاري تحليل بيانات المتجر...</p>
                <p x-show="!assistantLoading" x-text="assistantAnswer" class="text-sm font-bold leading-7 text-slate-700 dark:text-slate-200"></p>
                <div class="mt-3 flex flex-wrap gap-2" x-show="assistantActions.length">
                    <template x-for="action in assistantActions" :key="action.url + action.label">
                        <a :href="action.url" x-text="action.label" class="rounded-xl bg-white px-3 py-2 text-xs font-black text-solve-700 shadow-sm dark:bg-slate-900"></a>
                    </template>
                </div>
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-3">
        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">Smart Recommendations</h2>
            <div class="mt-4 space-y-3">
                @foreach ($smartRecommendations as $recommendation)
                    <a href="{{ $recommendation['url'] ?? '#' }}" class="block rounded-2xl bg-slate-50 p-4 transition hover:bg-solve-50 dark:bg-slate-950 dark:hover:bg-slate-800">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $recommendation['title'] }}</p>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-black text-slate-500 dark:bg-slate-900">{{ $recommendation['priority'] }}</span>
                        </div>
                        <p class="mt-2 text-xs font-bold leading-6 text-slate-500">{{ $recommendation['body'] }}</p>
                    </a>
                @endforeach
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">Smart Inventory</h2>
            <div class="mt-4 space-y-3">
                @forelse ($smartInventory as $forecast)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $forecast['name'] }}</p>
                            <span class="rounded-full px-3 py-1 text-[11px] font-black {{ $forecast['priority'] === 'high' ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200' : 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200' }}">{{ $forecast['priority'] }}</span>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-500">ينفد خلال {{ $forecast['days_until_stockout'] }} يوم · كمية إعادة الطلب {{ $forecast['reorder_quantity'] }}</p>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm font-black text-slate-500 dark:bg-slate-950">لا توجد منتجات كافية للتوقع حالياً.</div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">Smart Automation</h2>
            <div class="mt-4 space-y-3">
                @forelse (($smart['automation_suggestions'] ?? []) as $automation)
                    <a href="{{ route('partner.apps.automations') }}" class="block rounded-2xl bg-slate-50 p-4 transition hover:bg-solve-50 dark:bg-slate-950 dark:hover:bg-slate-800">
                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ $automation['label'] }}</p>
                        <p class="mt-2 text-xs font-bold text-slate-500">{{ $automation['trigger'] }} -> {{ $automation['action'] }}</p>
                    </a>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm font-black text-slate-500 dark:bg-slate-950">لا توجد أتمتة مقترحة الآن.</div>
                @endforelse
            </div>
        </article>
    </section>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 text-sm font-black text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300" x-show="loading">
        <div class="mb-3">جاري تحديث بيانات الداشبورد من الـ API...</div>
        <div class="grid gap-3 md:grid-cols-3">
            <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
            <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
            <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
        </div>
    </div>
    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200" x-show="error" x-text="error" x-cloak></div>

    <section class="mt-8 grid gap-4 md:grid-cols-2 2xl:grid-cols-6" data-testid="dashboard-featured-kpis">
        @foreach ($featuredCards as $card)
            @php $cardValue = $card['kpi']['value'] ?? '0'; @endphp
            <article class="rounded-2xl bg-white p-5 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900" data-testid="dashboard-kpi-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-black text-slate-500">{{ $card['title'] }}</p>
                        <p class="mt-4 text-2xl font-black text-slate-950 dark:text-white">{{ $cardValue }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200">@include('partner.partials.icon', ['name' => $card['icon'], 'class' => 'h-5 w-5'])</span>
                </div>
                <div class="mt-6 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-solve-600" style="width: {{ min(100, max(8, (int) preg_replace('/[^\d]/', '', (string) $cardValue))) }}%"></div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3 text-xs font-black text-slate-500">
                    <span>{{ $card['kpi']['hint'] ?? '' }}</span>
                    <a href="{{ $card['url'] }}" class="shrink-0 text-slate-600 hover:text-solve-700 dark:text-slate-300">فتح</a>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1fr_1fr]">
        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">إجراءات سريعة</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">روابط مباشرة حسب صلاحيات المستخدم الحالي.</p>
                </div>
                <span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700 dark:bg-solve-500/10 dark:text-solve-200">{{ count($dashboard['quickActions']) }} إجراء</span>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @forelse ($dashboard['quickActions'] as $action)
                    <a href="{{ $action['url'] }}" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-sm font-black text-slate-700 transition hover:border-solve-200 hover:bg-solve-50 hover:text-solve-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800">
                        <span>{{ $action['label'] }}</span>
                        @include('partner.partials.icon', ['name' => 'chevron', 'class' => 'h-4 w-4'])
                    </a>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm font-black text-slate-500 dark:bg-slate-950">لا توجد إجراءات متاحة لهذا الدور.</div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">خطوات تجهيز المتجر</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">مرتبطة بجدول store_onboarding_steps.</p>
                </div>
                <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">{{ $dashboard['setupProgress'] }}%</span>
            </div>
            <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full rounded-full bg-cyan-400" style="width: {{ $dashboard['setupProgress'] }}%"></div>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($dashboard['setup'] as $step)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-200">{{ $step['label'] }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $step['done'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200' }}">{{ $step['done'] ? 'مكتمل' : 'مطلوب' }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm font-black text-slate-500 dark:bg-slate-950">لا توجد خطوات تجهيز مسجلة لهذا المتجر.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1fr_1fr]">
        <article class="relative min-h-[360px] overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-400 via-sky-600 to-slate-950 p-8 text-white shadow-[0_24px_80px_rgba(14,116,144,0.24)]">
            <div class="absolute left-16 top-16 h-28 w-28 rounded-[30px] bg-emerald-300/70 blur-sm"></div>
            <div class="absolute left-36 top-24 h-24 w-24 rounded-[28px] bg-violet-400/80 blur-sm"></div>
            <div class="absolute left-28 top-36 h-24 w-24 rounded-[28px] bg-slate-950/80"></div>
            <div class="absolute right-1/2 top-10 flex h-32 w-32 translate-x-1/2 items-center justify-center rounded-[28px] bg-white/10 text-5xl font-black">S</div>
            <div class="absolute right-24 top-8 rotate-12 rounded-xl bg-white p-3 text-center text-xs font-black text-slate-950 shadow-lg">
                Store
                <div class="mt-2 h-12 w-12 rounded-lg bg-violet-200"></div>
            </div>
            <div class="absolute right-56 top-10 -rotate-12 rounded-xl bg-white p-3 text-center text-xs font-black text-slate-950 shadow-lg">
                Solve
                <div class="mt-2 h-12 w-12 rounded-lg bg-cyan-200"></div>
            </div>

            <div class="relative z-10 flex h-full min-h-[300px] flex-col justify-end">
                <h2 class="text-3xl font-black">بيع مع Solve Chat</h2>
                <p class="mt-4 max-w-xl text-sm font-black leading-7 text-cyan-50">منتجاتك تظهر في محادثات الذكاء الاصطناعي كتوصيات ذكية للتاجر والعميل.</p>
                <div class="mt-8 flex w-28 items-center gap-2 rounded-full bg-white/15 px-4 py-2">
                    <span class="h-2 w-4 rounded-full bg-cyan-100"></span>
                    <span class="h-2 w-4 rounded-full bg-cyan-100/70"></span>
                    <span class="h-2 w-4 rounded-full bg-cyan-100/70"></span>
                    <span class="h-2 w-8 rounded-full bg-white"></span>
                </div>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">تابع أهدافك</h2>
                        <p class="mt-4 text-sm font-bold leading-7 text-slate-500">المؤشرات محسوبة من طلبات الفترة المحددة وترتبط بنفس store_id.</p>
                </div>
                <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700">جاهزية {{ $dashboard['setupProgress'] }}%</span>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
                <div class="flex justify-end gap-3">
                    <a href="{{ route('partner.analytics', ['period' => 'monthly']) }}" class="rounded-xl bg-slate-50 px-5 py-3 text-sm font-black text-slate-950 dark:bg-slate-800 dark:text-white">شهري</a>
                    <a href="{{ route('partner.analytics', ['period' => 'yearly']) }}" class="rounded-xl px-5 py-3 text-sm font-black text-slate-500">سنوي</a>
                </div>
                <p class="mt-5 text-sm font-black text-slate-950 dark:text-white">نسبة تقدمك</p>
                <div class="mt-10">
                    <div class="mb-2 flex justify-between text-xs font-black text-slate-500">
                        <span>{{ $goalPercent }}%</span>
                        <span>0</span>
                        <span>30</span>
                        <span>60</span>
                        <span>90</span>
                        <span>{{ $goalTarget }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-cyan-100">
                        <div class="h-full rounded-full bg-cyan-400" style="width: {{ $goalPercent }}%"></div>
                    </div>
                </div>
                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-100 p-5 text-center dark:border-slate-800">
                        <p class="text-sm font-black text-slate-500">الطلبات الحالية</p>
                        <p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">{{ $goalCurrent }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 p-5 text-center dark:border-slate-800">
                        <p class="text-sm font-black text-slate-500">هدف اليوم</p>
                        <p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">{{ $dashboard['goal']['target'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 p-5 text-center dark:border-slate-800">
                        <p class="text-sm font-black text-slate-500">المتبقي للهدف</p>
                        <p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">{{ $dashboard['goal']['remaining'] }}</p>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-4">
        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">آخر النشاطات</h2>
                <a href="{{ route('partner.activities') }}" class="text-sm font-black text-solve-600">عرض الكل</a>
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($dashboard['activities'] as $activity)
                    <div class="rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950">
                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ $activity['action'] }}</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $activity['actor'] }} · {{ $activity['subject_type'] }} · {{ $activity['created_at'] }}</p>
                    </div>
                @empty
                    <div class="rounded-xl bg-slate-50 px-3 py-8 text-center dark:bg-slate-950">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">لا توجد نشاطات بعد</h3>
                        <p class="mt-1 text-xs font-bold text-slate-500">ستظهر هنا عمليات المتجر من لوحة التاجر والأدمن.</p>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">صحة المتجر</h2>
                <span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700 dark:bg-solve-500/10 dark:text-solve-200">{{ $dashboard['storeHealth']['label'] ?? '-' }}</span>
            </div>
            <p class="mt-2 text-xs font-black text-slate-400">الزوار متاحون في التحليلات المباشرة بدون إضافة كرت زائد.</p>
            <div class="mt-4">
                <div class="flex items-end justify-between">
                    <span class="text-4xl font-black text-slate-950 dark:text-white">{{ $dashboard['storeHealth']['score'] ?? 0 }}%</span>
                    <span class="text-xs font-black text-slate-400">من بيانات الإعداد والطلبات والمخزون</span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-solve-600" style="width: {{ $dashboard['storeHealth']['score'] ?? 0 }}%"></div>
                </div>
                <div class="mt-4 grid gap-2">
                    @foreach (($dashboard['storeHealth']['checks'] ?? []) as $check)
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs font-black dark:bg-slate-950">
                            <span class="text-slate-600 dark:text-slate-300">{{ $check['label'] }}</span>
                            <span class="{{ $check['ok'] ? 'text-emerald-600' : 'text-amber-600' }}">{{ $check['ok'] ? 'جاهز' : 'يحتاج متابعة' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">حالة الاشتراك</h2>
            <div class="mt-4 space-y-2 text-sm font-bold">
                <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">الباقة</span><span>{{ $dashboard['subscription']['plan'] }}</span></div>
                <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">حالة المتجر</span><span>{{ $dashboard['store']['status'] ?? '-' }}</span></div>
                <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">الحالة</span><span>{{ $dashboard['subscription']['status'] }}</span></div>
                <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">التجديد</span><span>{{ $dashboard['subscription']['renewal_at'] ?? '-' }}</span></div>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">تنبيهات مهمة</h2>
            <div class="mt-4 space-y-3">
                @forelse (($dashboard['importantAlerts'] ?? $dashboard['alerts']) as $alert)
                    <a href="{{ $alert['url'] ?? '#' }}" class="block rounded-xl bg-slate-50 p-3 transition hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800">
                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ $alert['title'] }}</p>
                        <p class="mt-1 text-xs font-bold leading-6 text-slate-500">{{ $alert['body'] }}</p>
                    </a>
                @empty
                    <div class="rounded-xl bg-slate-50 px-3 py-8 text-center dark:bg-slate-950">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">لا توجد تنبيهات حالياً</h3>
                        <p class="mt-1 text-xs font-bold text-slate-500">سيظهر هنا أي تنبيه من لوحة الإدارة أو النظام.</p>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">آخر الطلبات</h2>
                <a href="{{ route('partner.orders') }}" class="text-sm font-black text-solve-600">عرض الطلبات</a>
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($dashboard['latestOrders'] as $order)
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $order['order_number'] ?? $order['id'] }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $order['customer'] ?? '-' }}</p>
                        </div>
                        <span class="text-sm font-black text-slate-700 dark:text-slate-200">{{ $order['total'] ?? $order['amount'] ?? '-' }}</span>
                    </div>
                @empty
                    <div class="rounded-xl bg-slate-50 px-3 py-8 text-center dark:bg-slate-950">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">لا توجد طلبات بعد</h3>
                        <p class="mt-1 text-xs font-bold text-slate-500">ستظهر آخر الطلبات هنا عند توفر بيانات المتجر.</p>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-[0_16px_55px_rgba(15,23,42,0.07)] dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">منتجات منخفضة المخزون</h2>
                <a href="{{ route('partner.products.inventory') }}" class="text-sm font-black text-solve-600">إدارة المخزون</a>
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($dashboard['lowStock'] as $product)
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $product['name'] ?? $product['product'] ?? $product['sku'] ?? '-' }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">SKU: {{ $product['sku'] ?? $product['id'] ?? '-' }}</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">{{ $product['stock'] ?? 0 }}</span>
                    </div>
                @empty
                    <div class="rounded-xl bg-slate-50 px-3 py-8 text-center dark:bg-slate-950">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">المخزون مستقر</h3>
                        <p class="mt-1 text-xs font-bold text-slate-500">لا توجد منتجات تحت حد التنبيه حاليًا.</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection
