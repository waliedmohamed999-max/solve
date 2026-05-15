@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $title)

@section('partner-content')
@php
    $columns = array_values(array_unique(collect($rows)->flatMap(fn ($row) => array_keys($row))->reject(fn ($key) => in_array($key, ['store_id'], true))->all()));
@endphp

<div class="px-4 py-6 lg:px-8">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('partner.customers') }}" class="text-sm font-black text-solve-600 dark:text-solve-300">العملاء</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">سجلات حقيقية من قاعدة البيانات ومفلترة حسب {{ $partner['store_id'] }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ count($rows) }} سجل</span>
            <a href="{{ route('api.partner.' . match($section) { 'customer_groups' => 'customer-groups.index', 'customer_reviews' => 'reviews.index', 'customer_questions' => 'questions.index', default => 'back-in-stock.index' }) }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-black dark:border-slate-700">API</a>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        @if ($section === 'customer_groups')
            <form method="POST" action="{{ route('partner.customers.groups.store') }}" class="grid gap-3 border-b border-slate-100 p-4 dark:border-slate-800 md:grid-cols-6">
                @csrf
                <input name="name" required placeholder="اسم المجموعة" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <select name="condition_type" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="orders_count">عدد الطلبات</option>
                    <option value="total_spent">إجمالي المشتريات</option>
                    <option value="city">المدينة</option>
                    <option value="last_purchase">آخر شراء</option>
                </select>
                <input name="condition_value" placeholder="قيمة الشرط" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <input name="linked_campaign" placeholder="حملة أو كوبون مرتبط" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <input name="status" value="نشط" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <button class="h-11 rounded-xl bg-solve-700 px-4 text-sm font-black text-white">إضافة</button>
            </form>
        @endif

        @if (count($rows))
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
                            <tr class="align-top hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                @foreach ($columns as $column)
                                    <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-700 dark:text-slate-300">
                                        {{ is_array($row[$column] ?? null) ? json_encode($row[$column], JSON_UNESCAPED_UNICODE) : ($row[$column] ?? '-') }}
                                    </td>
                                @endforeach
                                <td class="min-w-72 px-4 py-4">
                                    @if ($section === 'customer_groups')
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('partner.customers.groups.update', ['group' => $row['id']]) }}" class="flex flex-wrap gap-2">
                                                @csrf
                                                <input name="name" value="{{ $row['name'] ?? '' }}" class="h-9 w-36 rounded-full border border-slate-200 px-3 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                <input type="hidden" name="condition_type" value="{{ $row['condition_type'] ?? 'orders_count' }}">
                                                <input name="condition_value" value="{{ $row['condition_value'] ?? '' }}" class="h-9 w-28 rounded-full border border-slate-200 px-3 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                <input type="hidden" name="status" value="{{ $row['status'] ?? 'نشط' }}">
                                                <button class="rounded-full bg-solve-700 px-4 py-2 text-xs font-black text-white">تعديل</button>
                                            </form>
                                            <form method="POST" action="{{ route('partner.customers.groups.delete', ['group' => $row['id']]) }}" onsubmit="return confirm('تأكيد حذف مجموعة العملاء؟')">
                                                @csrf
                                                <button class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black dark:border-slate-700">حذف</button>
                                            </form>
                                        </div>
                                    @elseif ($section === 'customer_reviews')
                                        <div class="space-y-2">
                                            <form method="POST" action="{{ route('partner.customers.reviews.status', ['review' => $row['id']]) }}" class="flex gap-2">
                                                @csrf
                                                <select name="status" class="h-9 rounded-full border border-slate-200 px-3 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                    @foreach (\App\Support\PartnerCustomers::REVIEW_STATUSES as $key => $label)
                                                        <option value="{{ $key }}" @selected(($row['status_key'] ?? '') === $key)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="rounded-full bg-slate-950 px-4 py-2 text-xs font-black text-white dark:bg-white dark:text-slate-950">حفظ</button>
                                            </form>
                                            <form method="POST" action="{{ route('partner.customers.reviews.reply', ['review' => $row['id']]) }}" class="flex gap-2">
                                                @csrf
                                                <input name="reply" required placeholder="رد التاجر" class="h-9 rounded-full border border-slate-200 px-3 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                <button class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black dark:border-slate-700">رد</button>
                                            </form>
                                        </div>
                                    @elseif ($section === 'customer_questions')
                                        <div class="space-y-2">
                                            <form method="POST" action="{{ route('partner.customers.questions.reply', ['question' => $row['id']]) }}" class="flex gap-2">
                                                @csrf
                                                <input name="reply" required placeholder="إجابة السؤال" class="h-9 rounded-full border border-slate-200 px-3 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                <button class="rounded-full bg-solve-700 px-4 py-2 text-xs font-black text-white">رد</button>
                                            </form>
                                            <form method="POST" action="{{ route('partner.customers.questions.status', ['question' => $row['id']]) }}" class="flex gap-2">
                                                @csrf
                                                <select name="status" class="h-9 rounded-full border border-slate-200 px-3 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                                                    @foreach (\App\Support\PartnerCustomers::QUESTION_STATUSES as $key => $label)
                                                        <option value="{{ $key }}" @selected(($row['status_key'] ?? '') === $key)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black dark:border-slate-700">تحديث</button>
                                            </form>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('partner.customers.back-in-stock.notify', ['alert' => $row['id']]) }}" onsubmit="return confirm('إرسال إشعار توفر المخزون؟')">
                                            @csrf
                                            <button class="rounded-full bg-solve-700 px-4 py-2 text-xs font-black text-white">إرسال إشعار</button>
                                        </form>
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
                <p class="mt-2 text-sm font-bold text-slate-500">ستظهر البيانات هنا عند إضافتها أو مزامنتها من المتجر.</p>
            </div>
        @endif
    </section>
</div>
@endsection
