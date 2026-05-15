<div class="rounded-3xl border border-dashed border-slate-200 bg-white/80 p-8 text-center shadow-card">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-sm font-black text-brand-600">{{ $icon ?? 'S' }}</div>
    <h3 class="mt-4 text-lg font-black text-slate-900">{{ $title ?? 'لا توجد بيانات' }}</h3>
    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">{{ $description ?? 'ستظهر البيانات هنا عند توفرها.' }}</p>
</div>
