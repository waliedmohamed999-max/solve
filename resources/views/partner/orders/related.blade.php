@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $title)

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('partner.orders') }}" class="text-sm font-black text-solve-600 dark:text-solve-300">قائمة الطلبات</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">بيانات حقيقية من `platform_records` ومفلترة حسب {{ $partner['store_id'] }}.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ count($rows) }} سجل</span>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        @if (count($rows))
            @php $columns = array_values(array_unique(collect($rows)->flatMap(fn ($row) => array_keys($row))->reject(fn ($key) => in_array($key, ['store_id'], true))->all())); @endphp
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            @foreach ($columns as $column)
                                <th class="whitespace-nowrap px-4 py-4">{{ $column }}</th>
                            @endforeach
                            <th class="whitespace-nowrap px-4 py-4">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                @foreach ($columns as $column)
                                    <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-700 dark:text-slate-300">{{ is_array($row[$column] ?? null) ? json_encode($row[$column], JSON_UNESCAPED_UNICODE) : ($row[$column] ?? '-') }}</td>
                                @endforeach
                                <td class="whitespace-nowrap px-4 py-4">
                                    @if ($section === 'abandoned_carts')
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('partner.orders.abandoned-carts.remind', ['cart' => $row['id']]) }}">
                                                @csrf
                                                <button class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">إرسال تذكير</button>
                                            </form>
                                            <form method="POST" action="{{ route('partner.orders.abandoned-carts.convert', ['cart' => $row['id']]) }}" onsubmit="return confirm('تحويل السلة إلى طلب؟')">
                                                @csrf
                                                <button class="rounded-full bg-solve-700 px-3 py-2 text-xs font-black text-white hover:bg-solve-800">تحويل لطلب</button>
                                            </form>
                                        </div>
                                    @elseif ($section === 'returns')
                                        <form method="POST" action="{{ route('partner.orders.returns.status', ['return' => $row['id']]) }}" class="flex gap-2">
                                            @csrf
                                            <select name="status" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                <option value="قيد المراجعة">قيد المراجعة</option>
                                                <option value="تمت الموافقة">موافقة</option>
                                                <option value="مرفوض">رفض</option>
                                                <option value="تم استرداد المبلغ">استرداد المبلغ</option>
                                            </select>
                                            <button class="rounded-full bg-solve-700 px-3 py-2 text-xs font-black text-white">تحديث</button>
                                        </form>
                                    @elseif ($section === 'shipments')
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('partner.orders.shipments.status', ['shipment' => $row['id']]) }}" class="flex gap-2">
                                                @csrf
                                                <select name="status" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                    <option value="قيد التجهيز">قيد التجهيز</option>
                                                    <option value="تم التسليم للناقل">تم التسليم للناقل</option>
                                                    <option value="في الطريق">في الطريق</option>
                                                    <option value="تم التسليم">تم التسليم</option>
                                                </select>
                                                <button class="rounded-full bg-solve-700 px-3 py-2 text-xs font-black text-white">تحديث</button>
                                            </form>
                                            <a href="{{ $row['tracking_url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">تتبع</a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد بيانات في {{ $title }}</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">ستظهر البيانات هنا عند إنشائها أو مزامنتها من النظام.</p>
            </div>
        @endif
    </section>
</div>
@endsection
