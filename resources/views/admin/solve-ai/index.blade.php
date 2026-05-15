@extends('layouts.admin')

@section('title', 'ذكاء Solve | Admin')

@section('admin-content')
<div class="space-y-6">
    <section class="rounded-[32px] bg-slate-950 p-6 text-white shadow-card">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-black text-brand-200">AI Operations</p>
                <h1 class="mt-2 text-4xl font-black">ذكاء Solve</h1>
                <p class="mt-2 max-w-2xl text-sm font-bold leading-7 text-slate-300">مراقبة استخدام الذكاء، الأدوات، استهلاك التوكنز، وحدود الباقات من لوحة الإدارة.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('api.admin.solve-ai.usage') }}" class="rounded-2xl border border-white/20 px-5 py-3 text-sm font-black">Usage API</a>
                <a href="{{ route('api.admin.solve-ai.tools') }}" class="rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950">Tools API</a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl bg-white p-5 shadow-card">
            <p class="text-xs font-black text-slate-400">طلبات AI</p>
            <p class="mt-2 text-3xl font-black">{{ $usage['total_requests'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-card">
            <p class="text-xs font-black text-slate-400">Tokens</p>
            <p class="mt-2 text-3xl font-black">{{ number_format($usage['tokens'] ?? 0) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-card">
            <p class="text-xs font-black text-slate-400">الأدوات</p>
            <p class="mt-2 text-3xl font-black">{{ count($tools ?? []) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-card">
            <p class="text-xs font-black text-slate-400">Retention</p>
            <p class="mt-2 text-3xl font-black">{{ $settings['data_retention_days'] ?? 180 }} يوم</p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <div class="rounded-[30px] bg-white p-5 shadow-card">
            <h2 class="text-2xl font-black">الأدوات المتاحة</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach (($tools ?? []) as $tool)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black">{{ $tool['name'] }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $tool['category'] }} · {{ $tool['required_plan'] }}</p>
                            </div>
                            <span class="rounded-full {{ $tool['enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-black">{{ $tool['enabled'] ? 'مفعل' : 'معطل' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[30px] bg-white p-5 shadow-card">
                <h2 class="text-2xl font-black">أعلى المتاجر استخداماً</h2>
                <div class="mt-4 space-y-2">
                    @forelse (($usage['stores'] ?? []) as $store => $count)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-3 text-sm font-black">
                            <span>{{ $store }}</span>
                            <span>{{ $count }}</span>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-5 text-center text-sm font-black text-slate-500">لا يوجد استخدام حتى الآن.</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-[30px] bg-white p-5 shadow-card">
                <h2 class="text-2xl font-black">أكثر الأدوات استخداماً</h2>
                <div class="mt-4 space-y-2">
                    @forelse (($usage['tools'] ?? []) as $tool => $count)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-3 text-sm font-black">
                            <span>{{ $tool ?: 'unknown' }}</span>
                            <span>{{ $count }}</span>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-5 text-center text-sm font-black text-slate-500">لا يوجد استخدام حتى الآن.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
