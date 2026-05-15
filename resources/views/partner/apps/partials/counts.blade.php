<div class="grid gap-4 md:grid-cols-4">
    @foreach ($counts as $label => $value)
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-3xl font-black">{{ $value }}</p>
        </div>
    @endforeach
</div>
