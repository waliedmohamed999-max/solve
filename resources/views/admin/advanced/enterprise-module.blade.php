@extends('layouts.admin')

@section('title', $module['title'] . ' - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')
    @include('admin.components.confirm-dialog')

    <section class="space-y-6">
        <div class="overflow-hidden rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-brand-500">{{ $module['eyebrow'] }}</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">{{ $module['title'] }}</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">{{ $module['summary'] }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-black text-white shadow-card" @click="$dispatch('solve-toast', 'تم حفظ الإعدادات')">حفظ الإعدادات</button>
                    <button type="button" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600" @click="$dispatch('confirm-action', { title: 'تأكيد العملية', body: 'سيتم تطبيق التغيير على المتاجر المحددة فقط.' })">إجراء متقدم</button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($module['stats'] as $stat)
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-card transition hover:-translate-y-1 hover:shadow-soft">
                    <p class="text-sm font-bold text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6">
            @foreach ($module['sections'] as $section)
                @if ($section['type'] === 'cards')
                    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-2xl font-black text-slate-950">{{ $section['title'] }}</h3>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500">قابل للتفعيل</span>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($section['items'] as $item)
                                @php
                                    $statusClasses = match ($item['status']) {
                                        'متصل', 'نشط', 'مفعل', 'WhatsApp', 'Email', 'SMS' => 'bg-emerald-50 text-emerald-700',
                                        'يحتاج إعداد', 'مراجعة', 'اقتراح', 'فرصة نمو' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp
                                <article class="rounded-3xl border border-slate-100 bg-slate-50/60 p-5 transition hover:bg-white hover:shadow-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-lg font-black text-slate-900">{{ $item['name'] }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $item['meta'] }}</p>
                                        </div>
                                        <span class="{{ $statusClasses }} shrink-0 rounded-full px-3 py-1 text-xs font-black">{{ $item['status'] }}</span>
                                    </div>
                                    <div class="mt-5 flex gap-2">
                                        <button class="rounded-2xl bg-slate-900 px-4 py-2 text-xs font-black text-white" @click="$dispatch('solve-toast', 'تم فتح إعداد {{ $item['name'] }}')">إعداد</button>
                                        <button class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-600">اختبار</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @elseif ($section['type'] === 'table')
                    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <h3 class="text-2xl font-black text-slate-950">{{ $section['title'] }}</h3>
                            <div class="flex flex-wrap gap-2">
                                <input type="search" placeholder="بحث وفلترة" class="h-11 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-brand-300">
                                <button class="h-11 rounded-2xl border border-slate-200 px-4 text-sm font-black text-slate-600">تصدير</button>
                            </div>
                        </div>
                        <div class="mt-5 max-h-[430px] overflow-auto rounded-2xl border border-slate-100">
                            <table class="min-w-full text-right text-sm">
                                <thead class="sticky top-0 bg-slate-50 text-slate-500">
                                    <tr>
                                        @foreach ($section['columns'] as $column)
                                            <th class="px-4 py-3 font-black">{{ $column }}</th>
                                        @endforeach
                                        <th class="px-4 py-3 font-black">إجراء</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($section['rows'] as $row)
                                        <tr class="bg-white hover:bg-slate-50/70">
                                            @foreach ($row as $cell)
                                                <td class="px-4 py-4 font-bold text-slate-700">{{ $cell }}</td>
                                            @endforeach
                                            <td class="px-4 py-4"><button class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-black text-brand-700" @click="$dispatch('solve-toast', 'تم فتح التفاصيل')">عرض</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @elseif ($section['type'] === 'builder')
                    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                        <h3 class="text-2xl font-black text-slate-950">{{ $section['title'] }}</h3>
                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($section['steps'] as $index => $step)
                                <div class="rounded-3xl border border-dashed border-brand-200 bg-brand-50/50 p-5">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-sm font-black text-brand-600">{{ $index + 1 }}</div>
                                    <p class="mt-4 text-lg font-black text-slate-900">{{ $step }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">اسحب الخطوة أو عدلها حسب احتياج المتجر.</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    </section>
@endsection
