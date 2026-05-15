<div class="rounded-3xl border border-slate-100 bg-white/90 p-4 shadow-card">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.25em] text-brand-500">{{ $eyebrow ?? 'Solve SaaS' }}</p>
            <h2 class="mt-1 text-2xl font-black text-slate-900">{{ $title ?? 'إدارة البيانات' }}</h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <input type="search" placeholder="بحث سريع" class="h-11 min-w-56 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-brand-300">
            <button type="button" class="h-11 rounded-2xl border border-slate-200 px-4 text-sm font-bold text-slate-600">فلترة</button>
            <button type="button" class="h-11 rounded-2xl bg-slate-900 px-4 text-sm font-bold text-white">تصدير</button>
        </div>
    </div>
</div>
