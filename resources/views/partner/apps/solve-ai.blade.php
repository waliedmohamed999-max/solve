@extends('layouts.partner')

@section('title', 'ذكاء Solve | Solve')

@php
    $mode = $solveAi['mode'] ?? 'home';
    $tabs = [
        'home' => ['label' => 'الرئيسية', 'route' => 'partner.apps.solve-ai'],
        'tools' => ['label' => 'الأدوات', 'route' => 'partner.apps.solve-ai.tools'],
        'chat' => ['label' => 'المساعد', 'route' => 'partner.apps.solve-ai.chat'],
        'history' => ['label' => 'السجل', 'route' => 'partner.apps.solve-ai.history'],
        'settings' => ['label' => 'الإعدادات', 'route' => 'partner.apps.solve-ai.settings'],
    ];
    $categories = $solveAi['categories'] ?? [];
    $tools = collect($solveAi['tools'] ?? []);
    $usage = $solveAi['usage'] ?? ['used' => 0, 'limit' => 0, 'remaining' => 0, 'percent' => 0];
@endphp

@section('partner-content')
<div
    class="space-y-6"
    x-data="solveAiHub({
        generateUrl: @js($solveAi['api']['generate']),
        applyUrl: @js($solveAi['api']['apply']),
        chatUrl: @js($solveAi['api']['chat']),
        token: @js(csrf_token()),
        defaultTool: @js($tools->first()['id'] ?? 'store_growth_advisor')
    })"
>
    <section class="relative overflow-hidden rounded-[32px] border border-white/70 bg-slate-950 p-6 text-white shadow-card">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(124,58,237,.45),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(6,182,212,.35),transparent_30%)]"></div>
        <div class="relative grid gap-6 lg:grid-cols-[1fr_340px]">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black backdrop-blur">Solve AI Hub</span>
                    <span class="rounded-full bg-emerald-400/15 px-4 py-2 text-xs font-black text-emerald-100">مرتبط ببيانات {{ $solveAi['store_id'] }}</span>
                </div>
                <h1 class="mt-5 text-4xl font-black lg:text-5xl">ذكاء Solve</h1>
                <p class="mt-3 max-w-3xl text-base font-bold leading-8 text-slate-200">
                    مساعد ذكي يقرأ منتجاتك وطلباتك وعملاءك ومخزونك ليقترح وصف منتجات، حملات، كوبونات، SEO، وتحليلات قابلة للتطبيق داخل المتجر.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('partner.apps.solve-ai.chat') }}" class="rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 shadow-xl transition hover:-translate-y-0.5">ابدأ محادثة</a>
                    <a href="{{ route('partner.apps.solve-ai.tools') }}" class="rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-black text-white backdrop-blur transition hover:bg-white/15">استعرض الأدوات</a>
                    <a href="{{ route('api.partner.solve-ai.tools') }}" class="rounded-2xl border border-white/20 px-5 py-3 text-sm font-black text-white/80 transition hover:text-white">API</a>
                </div>
            </div>
            <div class="rounded-[28px] border border-white/15 bg-white/10 p-5 backdrop-blur-xl">
                <p class="text-sm font-black text-slate-200">استخدام الشهر</p>
                <div class="mt-4 flex items-end justify-between">
                    <div>
                        <p class="text-4xl font-black">{{ $usage['used'] ?? 0 }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-300">من {{ $usage['limit'] ?? 0 }} طلب</p>
                    </div>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black">{{ $usage['remaining'] ?? 0 }} متبقي</span>
                </div>
                <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-gradient-to-l from-cyan-300 to-violet-400" style="width: {{ $usage['percent'] ?? 0 }}%"></div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3 text-center text-xs font-black text-slate-200">
                    <div class="rounded-2xl bg-white/10 p-3">{{ $usage['tokens'] ?? 0 }} Token</div>
                    <div class="rounded-2xl bg-white/10 p-3">{{ $usage['credits_used'] ?? 0 }} Credit</div>
                </div>
            </div>
        </div>
    </section>

    <nav class="flex flex-wrap gap-2 rounded-3xl border border-slate-200 bg-white p-2 shadow-card dark:border-slate-800 dark:bg-slate-900">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route($tab['route']) }}" class="rounded-2xl px-5 py-3 text-sm font-black transition {{ $mode === $key ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    @if (in_array($mode, ['home', 'tools'], true))
        <section class="grid gap-5 xl:grid-cols-[260px_1fr]">
            <aside class="rounded-[28px] border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="px-2 text-lg font-black">تصنيفات الذكاء</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($categories as $key => $label)
                        <button type="button" @click="filter = @js($key)" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-black transition" :class="filter === @js($key) ? 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                            <span>{{ $label }}</span>
                            <span class="text-xs">{{ $key === 'all' ? $tools->count() : ($key === 'popular' ? $tools->where('popular', true)->count() : ($key === 'new' ? $tools->where('new', true)->count() : $tools->where('category', $key)->count())) }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <div class="space-y-5">
                <div class="grid gap-4 md:grid-cols-4">
                    @foreach (($solveAi['insights'] ?? []) as $insight)
                        <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-xs font-black text-slate-400">{{ $insight['label'] }}</p>
                            <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white">{{ $insight['value'] }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $insight['hint'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($tools as $tool)
                        <article
                            x-show="matchesTool(@js($tool['category']), @js($tool['popular']), @js($tool['new']))"
                            x-transition
                            class="group rounded-[28px] border border-slate-200 bg-white/90 p-5 shadow-card backdrop-blur transition hover:-translate-y-1 hover:border-solve-200 hover:shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-solve-50 to-cyan-50 text-xl dark:from-solve-900/40 dark:to-cyan-900/20">✦</div>
                                <span class="rounded-full {{ $tool['locked'] ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-1 text-xs font-black">
                                    {{ $tool['locked'] ? 'ترقية' : $tool['badge'] }}
                                </span>
                            </div>
                            <h3 class="mt-4 text-lg font-black text-slate-950 dark:text-white">{{ $tool['name'] }}</h3>
                            <p class="mt-2 min-h-[48px] text-sm font-bold leading-6 text-slate-500">{{ $tool['description'] }}</p>
                            <div class="mt-5 flex gap-2">
                                <button type="button" @click="selectTool(@js($tool['id']), @js($tool['name']), @js($tool['locked']))" class="flex-1 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white transition hover:bg-solve-700 dark:bg-white dark:text-slate-950">توليد سريع</button>
                                <a href="{{ route('partner.apps.solve-ai.chat') }}?tool={{ $tool['id'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">شات</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($mode === 'chat')
        <section class="grid gap-5 lg:grid-cols-[1fr_360px]">
            <div class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-2xl font-black">مساعد ذكاء Solve</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">اسأل عن المبيعات، المنتجات، المخزون، الحملات أو اطلب محتوى جاهز.</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Live</span>
                </div>
                <div class="mt-5 min-h-[360px] space-y-4 rounded-[24px] bg-slate-50 p-4 dark:bg-slate-950">
                    <template x-for="message in messages" :key="message.id">
                        <div class="space-y-2">
                            <div class="mr-auto max-w-[78%] rounded-3xl bg-slate-950 px-5 py-4 text-sm font-bold leading-7 text-white" x-text="message.prompt"></div>
                            <div class="max-w-[86%] rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm font-bold leading-8 text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" x-text="message.output"></div>
                        </div>
                    </template>
                    <div x-show="messages.length === 0" class="flex h-[300px] items-center justify-center text-center">
                        <div>
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-solve-50 text-2xl">✺</div>
                            <h3 class="mt-4 text-xl font-black">ابدأ بسؤال واضح</h3>
                            <p class="mt-2 text-sm font-bold text-slate-500">مثلاً: ما المنتجات الراكدة؟ أو اقترح حملة لهذا الأسبوع.</p>
                        </div>
                    </div>
                    <div x-show="loading" class="rounded-3xl border border-slate-200 bg-white p-4 text-sm font-black text-slate-500 dark:border-slate-800 dark:bg-slate-900">يكتب ذكاء Solve...</div>
                </div>
                <form class="mt-4 flex gap-3" @submit.prevent="sendChat">
                    <input x-model="prompt" class="h-14 flex-1 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950" placeholder="اكتب سؤالك هنا...">
                    <button class="rounded-2xl bg-solve-700 px-7 text-sm font-black text-white shadow-lg shadow-solve-700/20">إرسال</button>
                </form>
            </div>
            <aside class="space-y-4">
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-lg font-black">اقتراحات جاهزة</h3>
                    <div class="mt-4 space-y-2">
                        @foreach (['كيف أزيد المبيعات هذا الأسبوع؟', 'ما المنتجات منخفضة المخزون؟', 'اكتب وصفاً احترافياً لمنتج جديد.', 'اقترح حملة واتساب للعملاء.'] as $suggestion)
                            <button type="button" @click="prompt = @js($suggestion); sendChat()" class="w-full rounded-2xl bg-slate-50 px-4 py-3 text-right text-sm font-black text-slate-600 transition hover:bg-solve-50 hover:text-solve-700 dark:bg-slate-950 dark:text-slate-300">{{ $suggestion }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-lg font-black">توصيات ذكية</h3>
                    <div class="mt-4 space-y-3">
                        @foreach (($solveAi['recommendations'] ?? []) as $item)
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                                <p class="text-sm font-black">{{ $item['title'] }}</p>
                                <p class="mt-1 text-xs font-bold leading-5 text-slate-500">{{ $item['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </section>
    @endif

    @if ($mode === 'history')
        <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-2xl font-black">سجل المحادثات والتوليد</h2>
            <div class="mt-5 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse (($solveAi['history'] ?? []) as $row)
                    <div class="py-4">
                        <p class="text-sm font-black text-slate-950 dark:text-white">{{ $row['message'] ?? $row['prompt'] ?? '-' }}</p>
                        <p class="mt-2 text-sm font-bold leading-7 text-slate-500">{{ $row['answer'] ?? $row['output'] ?? '-' }}</p>
                        <p class="mt-2 text-xs font-black text-slate-400">{{ $row['created_at'] ?? $row['updated_at_human'] ?? '' }}</p>
                    </div>
                @empty
                    <div class="rounded-3xl bg-slate-50 p-10 text-center text-sm font-black text-slate-500 dark:bg-slate-950">لا يوجد سجل حتى الآن.</div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($mode === 'settings')
        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-[30px] border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-2xl font-black">إعدادات الخصوصية والاستخدام</h2>
                <div class="mt-5 space-y-3">
                    @foreach (($solveAi['settings']['data_sources'] ?? []) as $source)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                            <span class="text-sm font-black">{{ $source }}</span>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">مفعل</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-[30px] border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-2xl font-black">حدود الباقة</h2>
                <p class="mt-3 text-sm font-bold text-slate-500">كل طلب AI محمي بـ store_id ولا يقرأ بيانات متجر آخر. البيانات الحساسة لا تظهر في الردود.</p>
                <div class="mt-5 rounded-3xl bg-slate-950 p-5 text-white">
                    <p class="text-sm font-black">الخطة الحالية: {{ $usage['plan'] ?? '-' }}</p>
                    <p class="mt-2 text-3xl font-black">{{ $usage['used'] ?? 0 }} / {{ $usage['limit'] ?? 0 }}</p>
                </div>
            </div>
        </section>
    @endif

    <div x-show="quickOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" @click.self="quickOpen = false">
        <div class="w-full max-w-2xl rounded-[32px] bg-white p-6 shadow-2xl dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-black text-solve-700">Quick Generate</p>
                    <h3 class="mt-2 text-2xl font-black" x-text="selectedToolName"></h3>
                </div>
                <button type="button" @click="quickOpen = false" class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black dark:bg-slate-800">إغلاق</button>
            </div>
            <textarea x-model="prompt" rows="4" class="mt-5 w-full rounded-2xl border border-slate-200 bg-white p-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950" placeholder="اكتب المنتج أو الهدف أو السؤال..."></textarea>
            <button type="button" @click="generate" class="mt-3 w-full rounded-2xl bg-solve-700 px-5 py-4 text-sm font-black text-white">توليد</button>
            <div x-show="notice" class="mt-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700" x-text="notice"></div>
            <div x-show="result" class="mt-5 rounded-3xl bg-slate-50 p-5 text-sm font-bold leading-8 text-slate-700 dark:bg-slate-950 dark:text-slate-200" x-text="result"></div>
            <div x-show="result" class="mt-4 grid gap-2 sm:grid-cols-3">
                <button type="button" @click="applyResult" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white transition hover:bg-solve-700">تطبيق النتيجة</button>
                <button type="button" @click="copyResult" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">نسخ</button>
                <button type="button" @click="regenerate" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">إعادة التوليد</button>
            </div>
        </div>
    </div>
</div>

<script>
function solveAiHub(config) {
    return {
        filter: 'all',
        selectedTool: config.defaultTool,
        selectedToolName: 'ذكاء Solve',
        prompt: '',
        result: '',
        loading: false,
        quickOpen: false,
        messages: [],
        matchesTool(category, popular, fresh) {
            return this.filter === 'all'
                || (this.filter === 'popular' && popular)
                || (this.filter === 'new' && fresh)
                || this.filter === category;
        },
        selectTool(id, name, locked) {
            if (locked) {
                window.location.href = @js(route('partner.subscription.plans'));
                return;
            }
            this.selectedTool = id;
            this.selectedToolName = name;
            this.quickOpen = true;
            this.result = '';
        },
        async generate() {
            if (!this.prompt.trim()) return;
            this.loading = true;
            const response = await fetch(config.generateUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token},
                body: JSON.stringify({tool: this.selectedTool, prompt: this.prompt})
            });
            const payload = await response.json();
            this.result = payload.output || payload.message || 'تعذر توليد النتيجة.';
            this.loading = false;
        },
        async sendChat() {
            if (!this.prompt.trim()) return;
            const current = this.prompt;
            this.loading = true;
            this.prompt = '';
            const response = await fetch(config.chatUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token},
                body: JSON.stringify({tool: this.selectedTool, prompt: current})
            });
            const payload = await response.json();
            this.messages.push({id: Date.now(), prompt: current, output: payload.output || payload.message || 'تعذر توليد الرد.'});
            this.loading = false;
        }
    }
}
</script>
<script>
function solveAiHub(config) {
    return {
        filter: 'all',
        selectedTool: config.defaultTool,
        selectedToolName: 'ذكاء Solve',
        prompt: '',
        result: '',
        lastPrompt: '',
        notice: '',
        loading: false,
        quickOpen: false,
        messages: [],
        matchesTool(category, popular, fresh) {
            return this.filter === 'all'
                || (this.filter === 'popular' && popular)
                || (this.filter === 'new' && fresh)
                || this.filter === category;
        },
        selectTool(id, name, locked) {
            if (locked) {
                window.location.href = @js(route('partner.subscription.plans'));
                return;
            }
            this.selectedTool = id;
            this.selectedToolName = name;
            this.quickOpen = true;
            this.result = '';
            this.notice = '';
        },
        async generate() {
            if (!this.prompt.trim()) return;
            this.loading = true;
            this.notice = '';
            this.lastPrompt = this.prompt;
            const response = await fetch(config.generateUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token},
                body: JSON.stringify({tool: this.selectedTool, prompt: this.prompt})
            });
            const payload = await response.json();
            if (response.status === 402) {
                this.result = payload.message || 'هذه الأداة تحتاج ترقية الباقة أو رصيد AI إضافي.';
                this.notice = 'يمكنك مقارنة الباقات أو ترقية الرصيد من صفحة الاشتراك.';
                this.loading = false;
                return;
            }
            this.result = payload.output || payload.message || 'تعذر توليد النتيجة.';
            this.loading = false;
        },
        async regenerate() {
            if (this.lastPrompt) this.prompt = this.lastPrompt;
            await this.generate();
        },
        async copyResult() {
            if (!this.result) return;
            await navigator.clipboard.writeText(this.result);
            this.notice = 'تم نسخ النتيجة.';
        },
        async applyResult() {
            if (!this.result) return;
            this.loading = true;
            this.notice = '';
            const response = await fetch(config.applyUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token},
                body: JSON.stringify({tool: this.selectedTool, prompt: this.lastPrompt || this.prompt, output: this.result})
            });
            const payload = await response.json();
            this.notice = payload.applied ? 'تم تطبيق النتيجة داخل بيانات المتجر.' : (payload.message || 'تعذر تطبيق النتيجة.');
            this.loading = false;
        },
        async sendChat() {
            if (!this.prompt.trim()) return;
            const current = this.prompt;
            this.loading = true;
            this.prompt = '';
            const response = await fetch(config.chatUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token},
                body: JSON.stringify({tool: this.selectedTool, prompt: current})
            });
            const payload = await response.json();
            if (response.status === 402) {
                this.messages.push({id: Date.now(), prompt: current, output: payload.message || 'هذه المحادثة تحتاج ترقية الباقة أو رصيد AI إضافي.'});
                this.loading = false;
                return;
            }
            this.messages.push({id: Date.now(), prompt: current, output: payload.output || payload.message || 'تعذر توليد الرد.'});
            this.loading = false;
        }
    }
}
</script>
@endsection
