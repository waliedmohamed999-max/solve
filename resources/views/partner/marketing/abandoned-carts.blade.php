@extends('layouts.partner')

@section('title', 'Solve Merchant | السلات المتروكة')

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('partner.marketing') }}" class="text-sm font-black text-solve-700 dark:text-solve-300">التسويق</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">السلات المتروكة</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">استرجاع السلات، كوبونات الاسترجاع، وجدولة الرسائل لمتجر {{ $partner['store_id'] }}.</p>
        </div>
        <a href="{{ route('api.partner.abandoned-carts.index') }}" class="rounded-full border border-slate-200 px-4 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        @if (count($rows))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr><th class="px-4 py-4">العميل</th><th class="px-4 py-4">المنتجات</th><th class="px-4 py-4">القيمة</th><th class="px-4 py-4">آخر تحديث</th><th class="px-4 py-4">الحالة</th><th class="px-4 py-4">إجراءات</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                <td class="px-4 py-4 font-black">{{ $row['customer'] ?? 'زائر' }}<p class="text-xs font-bold text-slate-500">{{ $row['phone'] ?? '-' }}</p></td>
                                <td class="px-4 py-4 font-bold">{{ $row['items_count'] ?? count($row['items'] ?? []) }}</td>
                                <td class="px-4 py-4 font-black">{{ $row['total'] ?? '0 ر.س' }}</td>
                                <td class="px-4 py-4 font-bold text-slate-500">{{ $row['updated_at'] ?? $row['last_activity'] ?? '-' }}</td>
                                <td class="px-4 py-4 font-bold">{{ $row['status'] ?? '-' }}<p class="text-xs text-slate-400">{{ $row['recovery_coupon'] ?? '' }}</p></td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('partner.marketing.abandoned-carts.remind', ['cart' => $row['id']]) }}">
                                            @csrf
                                            <button class="rounded-full bg-solve-700 px-3 py-2 text-xs font-black text-white">إرسال تذكير</button>
                                        </form>
                                        <form method="POST" action="{{ route('partner.marketing.abandoned-carts.coupon', ['cart' => $row['id']]) }}">
                                            @csrf
                                            <button class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black dark:border-slate-700">كوبون استرجاع</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد سلات متروكة</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">ستظهر السلات غير المكتملة هنا تلقائيا.</p>
            </div>
        @endif
    </section>
</div>
@endsection
