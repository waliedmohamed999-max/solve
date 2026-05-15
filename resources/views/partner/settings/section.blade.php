@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $settingsSection['title'])

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <nav class="flex flex-wrap items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400">
        @foreach ($settingsSection['breadcrumbs'] as $crumb)
            @if ($crumb['url'])
                <a href="{{ $crumb['url'] }}" class="hover:text-solve-600">{{ $crumb['label'] }}</a>
            @else
                <span class="text-slate-950 dark:text-white">{{ $crumb['label'] }}</span>
            @endif
            @if (! $loop->last)<span>/</span>@endif
        @endforeach
    </nav>

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200">
                        @include('partner.partials.icon', ['name' => $settingsSection['icon']])
                    </span>
                    <div>
                        <p class="text-sm font-black text-solve-600 dark:text-solve-300">إعدادات المتجر</p>
                        <h1 class="mt-1 text-3xl font-black text-slate-950 dark:text-white">{{ $settingsSection['title'] }}</h1>
                    </div>
                </div>
                <p class="mt-4 text-sm font-bold leading-7 text-slate-500 dark:text-slate-400">{{ $settingsSection['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $settingsSection['apiUrl'] }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">API JSON</a>
                <a href="{{ route('partner.settings') }}" class="rounded-full bg-slate-950 px-5 py-2 text-sm font-black text-white transition hover:bg-solve-700 dark:bg-white dark:text-slate-950">كل الإعدادات</a>
            </div>
        </div>
    </section>

    <section class="mt-4 grid gap-3 md:grid-cols-3">
        @foreach ($settingsSection['summary'] as $item)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $item['label'] }}</p>
                <p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">{{ $item['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_360px]">
        <article class="rounded-3xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">بيانات القسم</h2>
                <p class="mt-1 text-sm font-bold text-slate-500 dark:text-slate-400">الحفظ يتم داخل `store_settings.{{ $settingsSection['bucket'] }}` لمتجر {{ $settingsSection['storeScope']['store_id'] }} فقط.</p>
            </div>

            @if ($settingsSection['editable'] && ($canManageSettings ?? false))
                <form method="POST" action="{{ route('partner.settings.section.update', ['section' => $settingsSection['key']]) }}" class="grid gap-4 p-5 md:grid-cols-2">
                    @csrf
                    @foreach ($settingsSection['fields'] as $field)
                        @php
                            $type = $field['type'] ?? 'text';
                            $readonly = ($field['readonly'] ?? false) === true;
                        @endphp
                        <label class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }} block">
                            <span class="text-sm font-black text-slate-700 dark:text-slate-300">{{ $field['label'] }}</span>

                            @if ($type === 'select')
                                <select name="settings[{{ $field['key'] }}]" @disabled($readonly)
                                    class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none transition focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    @foreach (($field['options'] ?? []) as $option)
                                        <option value="{{ $option }}" @selected((string) $field['value'] === (string) $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif ($type === 'textarea')
                                <textarea name="settings[{{ $field['key'] }}]" rows="5" @disabled($readonly)
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold leading-7 outline-none transition focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ $field['value'] }}</textarea>
                            @else
                                <input type="{{ $type === 'color' ? 'color' : ($type === 'number' ? 'number' : ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text'))) }}"
                                    name="settings[{{ $field['key'] }}]" value="{{ $field['value'] }}" @disabled($readonly)
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                    class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none transition focus:border-solve-300 disabled:bg-slate-50 disabled:text-slate-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:disabled:bg-slate-900">
                            @endif
                        </label>
                    @endforeach

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 dark:border-slate-800 md:col-span-2 sm:flex-row">
                        <button class="rounded-full bg-solve-700 px-7 py-3 text-sm font-black text-white transition hover:bg-solve-800">حفظ الإعدادات</button>
                        <a href="{{ route('partner.settings.section', ['section' => $settingsSection['key']]) }}" class="rounded-full border border-slate-200 px-7 py-3 text-center text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">إلغاء التغييرات</a>
                    </div>
                </form>
            @else
                @if ($settingsSection['editable'] && ! ($canManageSettings ?? false))
                    <div class="mx-5 mt-5 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-black text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">
                        لديك صلاحية عرض فقط. تعديل هذه الإعدادات متاح لمدير المتجر.
                    </div>
                @endif

                <div class="grid gap-3 p-5 md:grid-cols-2">
                    @forelse ($settingsSection['fields'] as $field)
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                            <p class="text-xs font-black text-slate-400">{{ $field['label'] }}</p>
                            <p class="mt-2 break-words text-sm font-black text-slate-900 dark:text-white">{{ $field['value'] ?: '-' }}</p>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 p-5 text-sm font-black text-slate-500 dark:bg-slate-950">
                            هذا القسم مرتبط بأداة مستقلة داخل النظام.
                        </div>
                    @endforelse
                </div>
            @endif
        </article>

        <aside class="space-y-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">أدوات القسم</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($settingsSection['tools'] as $tool)
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                            <p class="text-xs font-black text-slate-400">{{ $tool['label'] }}</p>
                            <p class="mt-1 break-words text-sm font-black text-slate-800 dark:text-slate-100">{{ $tool['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">حماية البيانات</h2>
                <div class="mt-4 space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                    <div class="flex justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950"><span>Store Scope</span><span>{{ $settingsSection['storeScope']['store_id'] }}</span></div>
                    <div class="flex justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950"><span>Role</span><span>{{ $roleLabel }}</span></div>
                    <div class="flex justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950"><span>Write Access</span><span>{{ ($canManageSettings ?? false) ? 'مسموح' : 'قراءة فقط' }}</span></div>
                </div>
            </article>
        </aside>
    </section>
</div>
@endsection
