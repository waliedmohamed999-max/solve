@extends('layouts.admin')

@section('title', 'تفاصيل الاشتراك - Solve Admin')

@section('admin-content')
    @php abort_unless($subscription, 404); @endphp
    <section class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-card">
            <p class="text-sm font-black text-brand-600">{{ $subscription['store_id'] }}</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $subscription['store'] }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">{{ $subscription['owner'] }} / {{ $subscription['owner_email'] }}</p>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="rounded-3xl bg-white p-6 shadow-card">
                <p class="text-sm font-black text-slate-500">الباقة الحالية</p>
                <p class="mt-3 text-3xl font-black text-brand-700">{{ $subscription['plan_name'] }}</p>
                <p class="mt-2 text-sm font-bold text-slate-500">تجديد: {{ $subscription['renews_at'] ?? '-' }}</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-card lg:col-span-2">
                <p class="text-sm font-black text-slate-500">الاستخدام</p>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ($subscription['usage'] as $usage)
                        <div>
                            <div class="mb-1 flex justify-between text-xs font-black text-slate-500"><span>{{ $usage['key'] }}</span><span>{{ $usage['used'] }} / {{ $usage['limit'] }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-brand-600" style="width: {{ $usage['percent'] }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
