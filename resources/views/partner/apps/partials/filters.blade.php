<form method="GET" action="{{ $action }}" class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-5">
    <input name="q" value="{{ $payload['filters']['q'] ?? '' }}" placeholder="بحث عن تطبيق" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
    <select name="category" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
        @foreach ($payload['categories'] as $key => $label)
            <option value="{{ $key }}" @selected(($payload['filters']['category'] ?? 'all') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
        @foreach ($payload['statusOptions'] as $key => $label)
            <option value="{{ $key }}" @selected(($payload['filters']['status'] ?? 'all') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">فلترة</button>
    <a href="{{ $action }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm font-black dark:border-slate-700">إعادة ضبط</a>
</form>
