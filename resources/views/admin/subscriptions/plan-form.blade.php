@extends('layouts.admin')

@section('title', ($plan ? 'تعديل باقة' : 'إنشاء باقة') . ' - Solve Admin')

@section('admin-content')
    <section class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-card">
            <p class="text-sm font-black text-brand-600">Plans</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $plan ? 'تعديل باقة ' . $plan['name'] : 'إنشاء باقة جديدة' }}</h1>
        </div>

        <form class="grid gap-5 rounded-3xl bg-white p-6 shadow-card lg:grid-cols-2" method="POST" action="{{ $plan ? '/api/admin/plans/' . $plan['id'] : '/api/admin/plans' }}"
            onsubmit="event.preventDefault(); fetch(this.action,{method:'{{ $plan ? 'PATCH' : 'POST' }}',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:new FormData(this)}).then(async r=>{if(!r.ok){alert('تعذر الحفظ');return;} window.location='{{ route('admin.plans') }}';})">
            @csrf
            <label class="space-y-2 text-sm font-black text-slate-700">اسم الباقة
                <input name="name" value="{{ $plan['name'] ?? '' }}" class="h-12 w-full rounded-2xl border border-slate-200 px-4" required>
            </label>
            <label class="space-y-2 text-sm font-black text-slate-700">السعر الشهري
                <input name="price" type="number" value="{{ $plan['price'] ?? '' }}" class="h-12 w-full rounded-2xl border border-slate-200 px-4">
            </label>
            <label class="space-y-2 text-sm font-black text-slate-700">السعر السنوي
                <input name="yearly_price" type="number" value="{{ $plan['yearly_price'] ?? '' }}" class="h-12 w-full rounded-2xl border border-slate-200 px-4">
            </label>
            <label class="space-y-2 text-sm font-black text-slate-700">أيام التجربة
                <input name="trial_days" type="number" value="{{ $plan['trial_days'] ?? 14 }}" class="h-12 w-full rounded-2xl border border-slate-200 px-4">
            </label>

            @foreach (['products','orders','staff','branches','apps','channels'] as $limit)
                <label class="space-y-2 text-sm font-black text-slate-700">حد {{ $limit }}
                    <input name="limit_{{ $limit }}" value="{{ $plan['limits'][$limit] ?? 0 }}" class="h-12 w-full rounded-2xl border border-slate-200 px-4">
                </label>
            @endforeach

            <label class="space-y-2 text-sm font-black text-slate-700 lg:col-span-2">المميزات
                <textarea name="features" rows="4" class="w-full rounded-2xl border border-slate-200 p-4">{{ implode("\n", $plan['features'] ?? []) }}</textarea>
            </label>

            <div class="lg:col-span-2 grid gap-3 md:grid-cols-4">
                @foreach (['pos','apps','ai','advanced_reports','custom_domain','staff','api_access','automation'] as $flag)
                    <label class="flex items-center gap-2 rounded-2xl bg-slate-50 p-3 text-sm font-black">
                        <input type="hidden" name="{{ $flag }}" value="0">
                        <input type="checkbox" name="{{ $flag }}" value="1" @checked($plan['feature_flags'][$flag] ?? false)>
                        {{ $flag }}
                    </label>
                @endforeach
            </div>

            <div class="lg:col-span-2 flex gap-3">
                <button class="rounded-2xl bg-brand-600 px-6 py-3 text-sm font-black text-white">حفظ الباقة</button>
                <a href="{{ route('admin.plans') }}" class="rounded-2xl border border-slate-200 px-6 py-3 text-sm font-black text-slate-600">إلغاء</a>
            </div>
        </form>
    </section>
@endsection
