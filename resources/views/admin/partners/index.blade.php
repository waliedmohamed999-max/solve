@extends('layouts.admin')

@section('title', 'Solve Admin | الشركاء')

@section('admin-content')
@php
    $total = count($allPartners);
    $active = count(array_filter($allPartners, fn ($partner) => ($partner['status'] ?? '') === 'نشط'));
    $review = count(array_filter($allPartners, fn ($partner) => ($partner['status'] ?? '') === 'تحت المراجعة'));
    $statusClass = [
        'نشط' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'موقوف' => 'bg-rose-50 text-rose-700 border-rose-100',
        'تحت المراجعة' => 'bg-amber-50 text-amber-700 border-amber-100',
    ];
@endphp

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-sm font-bold text-brand-600">Multi-Tenant Partners</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $pageTitle }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-8 text-slate-500">{{ $pageDescription }}</p>
        </div>
        <a href="{{ route('partner.login') }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-card">بوابة دخول الشريك</a>
    </div>
</section>

<section class="mt-6 grid gap-4 md:grid-cols-3">
    @foreach ([
        ['label' => 'إجمالي الشركاء', 'value' => $total, 'change' => 'كل المتاجر', 'tone' => 'bg-brand-50 text-brand-700'],
        ['label' => 'الشركاء النشطون', 'value' => $active, 'change' => 'جاهزون للعمل', 'tone' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'تحت المراجعة', 'value' => $review, 'change' => 'تحتاج اعتماد', 'tone' => 'bg-amber-50 text-amber-700'],
    ] as $card)
        <div class="rounded-[28px] border border-white/70 bg-white p-5 shadow-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-4 text-3xl font-extrabold text-slate-900">{{ $card['value'] }}</p>
                </div>
                <span class="rounded-2xl px-3 py-2 text-sm font-bold {{ $card['tone'] }}">{{ $card['change'] }}</span>
            </div>
        </div>
    @endforeach
</section>

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <form method="GET" action="{{ route('admin.partners') }}" class="grid gap-4 lg:grid-cols-[1fr_220px_auto]">
        <input name="q" value="{{ $filters['q'] }}" placeholder="ابحث باسم المتجر، المالك، البريد، الباقة..." class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-300 focus:bg-white">
        <select name="status" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-300 focus:bg-white">
            @foreach ($filters['statuses'] as $status)
                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status === 'all' ? 'كل الحالات' : $status }}</option>
            @endforeach
        </select>
        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white">تطبيق الفلترة</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-right">
                <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">الشريك / المتجر</th>
                        <th class="px-5 py-4">المالك</th>
                        <th class="px-5 py-4">التواصل</th>
                        <th class="px-5 py-4">الحالة</th>
                        <th class="px-5 py-4">الباقة</th>
                        <th class="px-5 py-4">الاشتراك</th>
                        <th class="px-5 py-4">آخر دخول</th>
                        <th class="px-5 py-4">الإجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-sm">
                    @forelse ($partners as $partner)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ asset($partner['logo']) }}" alt="{{ $partner['name'] }}" class="h-12 w-12 rounded-2xl border border-slate-100 object-contain p-1">
                                    <div>
                                        <p class="font-extrabold text-slate-900">{{ $partner['name'] }}</p>
                                        <a href="{{ $partner['store_url'] }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-brand-600">{{ $partner['store_url'] }}</a>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-700">{{ $partner['owner'] }}</td>
                            <td class="px-5 py-4 text-slate-500">
                                <p>{{ $partner['email'] }}</p>
                                <p class="mt-1">{{ $partner['phone'] }}</p>
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold {{ $statusClass[$partner['status']] ?? 'bg-slate-50 text-slate-600 border-slate-100' }}">{{ $partner['status'] }}</span></td>
                            <td class="px-5 py-4 font-extrabold text-slate-900">{{ $partner['plan'] }}</td>
                            <td class="px-5 py-4 text-slate-500">{{ $partner['subscription_at'] }}</td>
                            <td class="px-5 py-4 text-slate-500">{{ $partner['last_login'] }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.partners.show', ['partner' => $partner['id']]) }}" class="rounded-2xl bg-brand-50 px-4 py-2 text-xs font-extrabold text-brand-700">عرض التفاصيل</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm font-bold text-slate-500">لا توجد نتائج مطابقة للبحث الحالي.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
