@extends('layouts.admin')

@section('title', $module['title'] . ' - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')
    @include('admin.components.confirm-dialog')

    <section class="mt-6 space-y-6">
        <div class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-card">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-sm font-black text-brand-600">Solve Merchant</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">{{ $module['title'] }}</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">{{ $module['summary'] }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white" @click="$dispatch('solve-toast', 'تم تنفيذ الإجراء')">{{ $module['primaryAction'] }}</button>
                    <button type="button" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700" @click="$dispatch('confirm-action', { title: 'تأكيد التغيير', body: 'سيتم تطبيق العملية على العناصر المحددة فقط.' })">إجراء سريع</button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($module['stats'] as $stat)
                <article class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft">
                    <p class="text-sm font-bold text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-xs font-black text-brand-600">{{ $stat['hint'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="rounded-[28px] border border-slate-100 bg-white p-5 shadow-card">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    @foreach ($module['quick'] as $item)
                        <button type="button" class="rounded-2xl bg-slate-50 px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">{{ $item }}</button>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <input type="search" placeholder="بحث سريع" class="h-11 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-300 focus:bg-white">
                    <button type="button" class="h-11 rounded-2xl border border-slate-200 px-4 text-sm font-black text-slate-600">فلترة</button>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
            <section class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-card">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-black text-slate-950">{{ $module['table']['title'] }}</h3>
                    <button type="button" class="rounded-2xl bg-slate-50 px-4 py-2 text-sm font-black text-slate-600">تصدير</button>
                </div>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                @foreach ($module['table']['columns'] as $column)
                                    <th class="p-4 font-black">{{ $column }}</th>
                                @endforeach
                                <th class="p-4 font-black">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($module['table']['rows'] as $row)
                                <tr class="hover:bg-slate-50/60">
                                    @foreach ($row as $cell)
                                        <td class="p-4 font-bold text-slate-700">{{ $cell }}</td>
                                    @endforeach
                                    <td class="p-4">
                                        <button type="button" class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-black text-brand-700" @click="$dispatch('solve-toast', 'تم فتح التفاصيل')">عرض</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-4">
                @foreach ($module['side'] as $card)
                    <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card">
                        <h4 class="text-lg font-black text-slate-950">{{ $card['title'] }}</h4>
                        <p class="mt-2 text-sm leading-7 text-slate-500">{{ $card['body'] }}</p>
                    </div>
                @endforeach
                @include('admin.components.empty-state', ['icon' => '✓', 'title' => 'تجربة بسيطة', 'description' => 'الصفحة تعرض ما يحتاجه التاجر يوميا فقط، وتترك التفاصيل المتقدمة عند الطلب.'])
            </aside>
        </div>
    </section>
@endsection
