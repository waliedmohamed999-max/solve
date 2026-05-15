@extends('layouts.admin')

@section('title', 'Production Readiness | Solve')

@section('admin-content')
<section class="rounded-[32px] bg-slate-950 p-8 text-white shadow-card">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.3em] text-cyan-300">Solve Launch Gate</p>
            <h1 class="mt-4 text-3xl font-black">جاهزية بيع المتاجر للشركاء والتجار</h1>
            <p class="mt-2 text-sm font-black text-cyan-200">جاهزية الإنتاج</p>
            <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-slate-300">هذه الصفحة تفحص المتطلبات التشغيلية التي تمنع إطلاق منصة SaaS قبل اكتمالها.</p>
        </div>
        <div class="rounded-3xl bg-white/10 px-6 py-5 text-center">
            <p class="text-xs font-black text-slate-300">النتيجة</p>
            <p class="mt-2 text-4xl font-black">{{ $readiness['score'] }}%</p>
        </div>
    </div>
</section>

<section class="mt-6 grid gap-3 md:grid-cols-4">
    @foreach (['Environment Config', 'Backup Strategy', 'Database Indexing', 'Monitoring'] as $control)
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-card">{{ $control }}</div>
    @endforeach
</section>

<section class="mt-6 grid gap-4 md:grid-cols-4">
    @foreach ($readiness['metrics'] as $metric)
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-card">
            <p class="text-sm font-black text-slate-500">{{ $metric['label'] }}</p>
            <p class="mt-3 text-2xl font-black text-slate-950">{{ $metric['value'] }}</p>
        </article>
    @endforeach
</section>

<section class="mt-6 rounded-[32px] border border-slate-200 bg-white p-6 shadow-card">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-black text-slate-950">قائمة الإطلاق</h2>
        <span class="rounded-full px-4 py-2 text-sm font-black {{ $readiness['ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
            {{ $readiness['ready'] ? 'جاهز للإطلاق' : 'غير جاهز بعد' }}
        </span>
    </div>

    <div class="mt-6 grid gap-3">
        @foreach ($readiness['checks'] as $check)
            <div class="flex items-start justify-between gap-4 rounded-2xl border px-4 py-4 {{ $check['passed'] ? 'border-emerald-100 bg-emerald-50' : 'border-rose-100 bg-rose-50' }}">
                <div>
                    <p class="text-sm font-black {{ $check['passed'] ? 'text-emerald-800' : 'text-rose-800' }}">{{ $check['label'] }}</p>
                    <p class="mt-1 text-xs font-bold {{ $check['passed'] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $check['message'] }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-black {{ $check['passed'] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $check['passed'] ? 'مكتمل' : 'مطلوب' }}</span>
            </div>
        @endforeach
    </div>
</section>
@endsection
