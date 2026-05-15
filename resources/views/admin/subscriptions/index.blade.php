@extends('layouts.admin')

@section('title', 'اشتراكات المتاجر - Solve Admin')

@section('admin-content')
    <section class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-card">
            <p class="text-sm font-black text-brand-600">Subscriptions</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">اشتراكات المتاجر</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">ترقية الباقة، إيقاف الاشتراك، إعادة التفعيل، ومراجعة الفواتير تتم من هنا.</p>
            <p class="mt-1 text-xs font-black text-slate-400">Starter / Growth / Enterprise</p>
            <div class="mt-5 grid gap-3 md:grid-cols-5">
                @foreach ($dashboard['summary'] as $label => $value)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black text-slate-400">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white shadow-card">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-xs font-black text-slate-500">
                    <tr>
                        <th class="p-4">المتجر</th>
                        <th class="p-4">الباقة</th>
                        <th class="p-4">الحالة</th>
                        <th class="p-4">التجديد</th>
                        <th class="p-4">الاستخدام</th>
                        <th class="p-4">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($dashboard['subscriptions'] as $subscription)
                        <tr>
                            <td class="p-4">
                                <p class="font-black text-slate-950">{{ $subscription['store'] }}</p>
                                <p class="text-xs font-bold text-slate-400">{{ $subscription['store_id'] }} / {{ $subscription['owner_email'] }}</p>
                            </td>
                            <td class="p-4 font-black text-brand-700">{{ $subscription['plan_name'] }}</td>
                            <td class="p-4"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $subscription['status'] }}</span></td>
                            <td class="p-4 font-bold text-slate-600">{{ $subscription['renews_at'] ?? '-' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach (array_slice($subscription['usage'], 0, 3) as $usage)
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-500">{{ $usage['key'] }} {{ $usage['used'] }}/{{ $usage['limit'] }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="p-4"><a class="rounded-xl bg-slate-950 px-3 py-2 text-xs font-black text-white" href="{{ route('admin.subscriptions.show', $subscription['store_id']) }}">عرض</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center font-black text-slate-400">لا توجد اشتراكات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
