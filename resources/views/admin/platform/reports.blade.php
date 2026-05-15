@extends('layouts.admin')

@section('title', 'Solve Admin | التقارير')

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <p class="text-sm font-bold text-brand-600">Advanced Reports</p>
    <h2 class="mt-2 text-3xl font-extrabold text-slate-900">التقارير المتقدمة</h2>
    <p class="mt-3 text-sm leading-8 text-slate-500">مقارنة فترات، فلترة حسب المتجر والحالة والتاريخ، وتصدير PDF / Excel.</p>
</section>

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <form method="GET" class="grid gap-4 md:grid-cols-5">
        <select name="store_id" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><option value="">كل المتاجر</option>@foreach($stores as $store)<option value="{{ $store['id'] ?? '' }}">{{ $store['name'] ?? '' }}</option>@endforeach</select>
        <input name="date_from" type="date" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
        <input name="date_to" type="date" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
        <select name="period" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><option>هذا الشهر</option><option>الشهر السابق</option><option>ربع سنوي</option></select>
        <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">تطبيق</button>
    </form>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl bg-slate-50 p-5"><p class="text-sm font-bold text-slate-500">الطلبات</p><p class="mt-3 text-3xl font-extrabold">{{ count($orders) }}</p></div>
        <div class="rounded-3xl bg-slate-50 p-5"><p class="text-sm font-bold text-slate-500">المدفوعات</p><p class="mt-3 text-3xl font-extrabold">{{ count($payments) }}</p></div>
        <div class="rounded-3xl bg-slate-50 p-5"><p class="text-sm font-bold text-slate-500">الاشتراكات</p><p class="mt-3 text-3xl font-extrabold">{{ count($subscriptions) }}</p></div>
    </div>
    <div class="mt-6 flex gap-3"><button class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold">تصدير PDF</button><button class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold">تصدير Excel</button></div>
</section>
@endsection
