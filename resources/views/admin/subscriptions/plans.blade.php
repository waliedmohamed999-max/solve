@extends('layouts.admin')

@section('title', 'الباقات والاشتراكات - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')
    <section class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-black text-brand-600">Subscription Engine</p>
                    <h1 class="mt-2 text-3xl font-black text-slate-950">إدارة الباقات</h1>
                    <p class="mt-2 text-sm font-bold text-slate-500">الباقات مرتبطة مباشرة بحدود المنتجات والطلبات والموظفين والتطبيقات والقنوات.</p>
                </div>
                <a href="{{ route('admin.plans.new') }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">إنشاء باقة</a>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            @foreach ($plans as $plan)
                <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-2xl font-black text-slate-950">{{ $plan['name'] }}</h2>
                                @if ($plan['recommended'])
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Recommended</span>
                                @endif
                            </div>
                            <p class="mt-2 text-xs font-black uppercase text-slate-400">{{ $plan['status'] }} / Trial {{ $plan['trial_days'] }} days</p>
                        </div>
                        <div class="text-left">
                            <p class="text-xl font-black text-brand-700">{{ $plan['price_label'] }}</p>
                            <p class="text-xs font-bold text-slate-400">{{ $plan['yearly_price_label'] }} سنوي</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-2">
                        @foreach ($plan['limits'] as $key => $limit)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                <span class="font-bold text-slate-500">{{ $key }}</span>
                                <span class="font-black text-slate-900">{{ $limit }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($plan['feature_flags'] as $feature => $enabled)
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">{{ $feature }}</span>
                        @endforeach
                    </div>

                    <div class="mt-6 flex gap-2">
                        <a href="{{ route('admin.plans.edit', $plan['id']) }}" class="flex-1 rounded-2xl bg-brand-600 px-4 py-3 text-center text-sm font-black text-white">تعديل</a>
                        <form method="POST" action="/api/admin/plans/{{ $plan['id'] }}" onsubmit="event.preventDefault(); fetch(this.action,{method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>location.reload())">
                            @csrf
                            <button class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">إيقاف</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection
