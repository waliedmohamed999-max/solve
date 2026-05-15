@extends('layouts.admin')

@section('title', 'Solve Admin | Executive Command Center')

@section('admin-content')
@php
    $executive = $executive ?? [];
    $kpis = $executive['kpis'] ?? [];
    $alerts = $executive['alerts'] ?? [];
    $feed = $executive['feed'] ?? [];
    $insights = $executive['insights'] ?? [];
    $healthStores = $executive['health_stores'] ?? [];
    $commands = $executive['commands'] ?? [];
    $toneClasses = [
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'blue' => 'bg-blue-50 text-blue-700 border-blue-100',
        'violet' => 'bg-violet-50 text-violet-700 border-violet-100',
        'cyan' => 'bg-cyan-50 text-cyan-700 border-cyan-100',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-100',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
        'slate' => 'bg-slate-50 text-slate-700 border-slate-100',
    ];
    $priorityClasses = [
        'critical' => 'bg-rose-50 text-rose-700 border-rose-200',
        'high' => 'bg-amber-50 text-amber-700 border-amber-200',
        'medium' => 'bg-blue-50 text-blue-700 border-blue-200',
        'low' => 'bg-slate-50 text-slate-600 border-slate-200',
    ];
@endphp

<div
    class="space-y-6 py-6"
    x-data="executiveOps({
        searchUrl: @js(route('admin.api.executive.search')),
        commandUrl: @js(route('admin.api.executive.command')),
        alertUrl: @js(url('/admin/api/executive/alerts')),
        feedUrl: @js(route('admin.api.executive.feed')),
        token: @js(csrf_token())
    })"
    x-init="bindShortcuts()"
>
    <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-slate-950 p-6 text-white shadow-card">
        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <div>
                <p class="text-sm font-black text-violet-200">Executive Experience & Operational Excellence</p>
                <h1 class="mt-3 text-4xl font-black">مركز قيادة Solve اليومي</h1>
                <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-slate-300">
                    مؤشرات قليلة، تنبيهات ذكية، أوامر تشغيل، وبحث شامل مبني على سجلات المتاجر والطلبات والفوترة وذكاء Solve.
                </p>
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <a href="{{ route('admin.activity') }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-black backdrop-blur">مراقبة Logs</a>
                    <a href="{{ route('admin.billing') }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-black backdrop-blur">Billing Risk</a>
                    <a href="{{ route('admin.solve-ai.usage') }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-black backdrop-blur">AI Usage</a>
                </div>
            </div>
            <div class="rounded-[28px] border border-white/10 bg-white/10 p-5 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-black text-slate-300">Global Search</p>
                        <p class="mt-1 text-sm font-bold text-slate-400">اضغط Ctrl / ⌘ + K</p>
                    </div>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black">Live Index</span>
                </div>
                <div class="mt-4 flex gap-2">
                    <input x-model.debounce.350ms="search" @input="runSearch" class="h-12 flex-1 rounded-2xl border border-white/10 bg-white/10 px-4 text-sm font-bold text-white outline-none placeholder:text-slate-400" placeholder="ابحث عن متجر، طلب، اشتراك، فاتورة، تطبيق...">
                    <button type="button" @click="runSearch" class="rounded-2xl bg-white px-5 text-sm font-black text-slate-950">بحث</button>
                </div>
                <div x-show="results.length" x-cloak class="mt-3 max-h-52 space-y-2 overflow-y-auto rounded-2xl bg-slate-950/70 p-2">
                    <template x-for="item in results" :key="item.type + item.title">
                        <a :href="item.url" class="block rounded-xl px-3 py-2 text-sm hover:bg-white/10">
                            <span class="font-black" x-text="item.title"></span>
                            <span class="block text-xs text-slate-400" x-text="item.type + ' · ' + item.subtitle"></span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-7">
        @foreach ($kpis as $kpi)
            <article class="rounded-[24px] border bg-white p-4 shadow-card {{ $toneClasses[$kpi['tone'] ?? 'slate'] ?? $toneClasses['slate'] }}">
                <p class="text-xs font-black opacity-80">{{ $kpi['label'] }}</p>
                <p class="mt-3 text-2xl font-black text-slate-950">{{ $kpi['value'] }}</p>
                <p class="mt-2 text-xs font-bold opacity-80">{{ $kpi['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <div class="space-y-5">
            <div class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950">Smart Alerts Center</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">Resolve / Ignore / Assign لكل تنبيه تشغيلي.</p>
                    </div>
                    <a href="{{ route('admin.notifications') }}" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white">كل التنبيهات</a>
                </div>
                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    @forelse ($alerts as $alert)
                        <article class="rounded-3xl border p-4 {{ $priorityClasses[$alert['priority'] ?? 'low'] ?? $priorityClasses['low'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span class="rounded-full bg-white/70 px-3 py-1 text-xs font-black">{{ $alert['priority'] }}</span>
                                    <h3 class="mt-3 text-lg font-black text-slate-950">{{ $alert['title'] }}</h3>
                                    <p class="mt-2 text-sm font-bold leading-6 text-slate-600">{{ $alert['body'] }}</p>
                                    @if (! empty($alert['store_id']))
                                        <p class="mt-2 text-xs font-black text-slate-500">Store: {{ $alert['store_id'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" @click="alertAction(@js($alert['id']), 'resolve')" class="rounded-xl bg-slate-950 px-3 py-2 text-xs font-black text-white">Resolve</button>
                                <button type="button" @click="alertAction(@js($alert['id']), 'ignore')" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700">Ignore</button>
                                <button type="button" @click="alertAction(@js($alert['id']), 'assign', 'ops-team')" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700">Assign Ops</button>
                                <a href="{{ $alert['url'] }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700">فتح</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-3xl bg-slate-50 p-10 text-center text-sm font-black text-slate-500 lg:col-span-2">لا توجد تنبيهات حرجة حالياً.</div>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card">
                    <h2 class="text-2xl font-black text-slate-950">Cross-System Insights</h2>
                    <div class="mt-5 space-y-3">
                        @foreach ($insights as $insight)
                            <a href="{{ $insight['url'] }}" class="block rounded-3xl bg-slate-50 p-4 transition hover:bg-violet-50">
                                <p class="font-black text-slate-950">{{ $insight['title'] }}</p>
                                <p class="mt-2 text-sm font-bold leading-6 text-slate-500">{{ $insight['body'] }}</p>
                                <p class="mt-3 text-xs font-black text-violet-700">{{ $insight['action'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card">
                    <h2 class="text-2xl font-black text-slate-950">SaaS Health Score</h2>
                    <div class="mt-5 space-y-3">
                        @foreach ($healthStores as $store)
                            <a href="{{ $store['url'] }}" class="block rounded-3xl border border-slate-100 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-black text-slate-950">{{ $store['name'] }}</p>
                                        <p class="text-xs font-bold text-slate-500">{{ $store['store_id'] }} · {{ $store['plan'] }}</p>
                                    </div>
                                    <span class="rounded-2xl bg-slate-950 px-3 py-2 text-sm font-black text-white">{{ $store['score'] }}%</span>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-violet-600" style="width: {{ $store['score'] }}%"></div>
                                </div>
                                <p class="mt-2 text-xs font-bold text-slate-500">{{ $store['recommendation'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </article>
            </div>
        </div>

        <aside class="space-y-5">
            <div class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card">
                <h2 class="text-2xl font-black text-slate-950">Command Center</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">أوامر سريعة مسجلة في Audit Log.</p>
                <div class="mt-5 grid gap-2">
                    @foreach ($commands as $command)
                        <button type="button" @click="runCommand(@js($command['key']), @js($command['payload']))" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-950 hover:text-white">
                            <span>{{ $command['label'] }}</span>
                            <span>↵</span>
                        </button>
                    @endforeach
                </div>
                <div x-show="commandMessage" x-cloak class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700" x-text="commandMessage"></div>
            </div>

            <div class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-black text-slate-950">Realtime Operations Feed</h2>
                    <button type="button" @click="refreshFeed" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black">Refresh</button>
                </div>
                <div class="mt-5 max-h-[560px] space-y-3 overflow-y-auto">
                    <template x-for="event in liveFeed" :key="event.id">
                        <div class="rounded-2xl border border-slate-100 p-3">
                            <p class="text-sm font-black text-slate-950" x-text="event.action"></p>
                            <p class="mt-1 text-xs font-bold text-slate-500" x-text="(event.store_id || 'platform') + ' · ' + (event.actor || '-') + ' · ' + (event.created_at || '')"></p>
                        </div>
                    </template>
                    @foreach ($feed as $event)
                        <div class="rounded-2xl border border-slate-100 p-3" x-show="liveFeed.length === 0">
                            <p class="text-sm font-black text-slate-950">{{ $event['action'] }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $event['store_id'] ?? 'platform' }} · {{ $event['actor'] }} · {{ $event['created_at'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </section>
</div>

<script>
function executiveOps(config) {
    return {
        search: '',
        results: [],
        commandMessage: '',
        liveFeed: [],
        bindShortcuts() {
            window.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    const input = this.$root.querySelector('input[type="search"], input[x-model\\.debounce\\.350ms="search"]');
                    if (input) input.focus();
                }
            });
        },
        async runSearch() {
            if (!this.search.trim()) {
                this.results = [];
                return;
            }
            const response = await fetch(config.searchUrl + '?q=' + encodeURIComponent(this.search), {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            this.results = payload.results || [];
        },
        async alertAction(alertId, action, assignee = null) {
            const response = await fetch(config.alertUrl + '/' + encodeURIComponent(alertId), {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token},
                body: JSON.stringify({action, assignee})
            });
            const payload = await response.json();
            this.commandMessage = payload.updated ? 'تم تحديث التنبيه.' : 'تعذر تحديث التنبيه.';
        },
        async runCommand(command, payload = {}) {
            const response = await fetch(config.commandUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token},
                body: JSON.stringify({command, payload})
            });
            const result = await response.json();
            this.commandMessage = result.executed ? 'تم تنفيذ الأمر: ' + command : (result.message || 'تعذر تنفيذ الأمر.');
            await this.refreshFeed();
        },
        async refreshFeed() {
            const response = await fetch(config.feedUrl, {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            this.liveFeed = payload.feed || [];
        }
    }
}
</script>
@endsection
