<header class="grid-pattern sticky top-4 z-30 rounded-[32px] border border-white/60 bg-white/85 p-4 shadow-card backdrop-blur-xl">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-center gap-3">
            <button class="rounded-2xl border border-slate-200 p-3 text-slate-600 lg:hidden" @click="mobileNav = true">☰</button>
            <div class="relative w-full max-w-xl">
                <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">⌕</span>
                <input type="text" placeholder="ابحث عن متجر، تذكرة، دفعة أو تقرير"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pr-11 pl-4 text-sm outline-none transition focus:border-brand-300 focus:bg-white">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
            <img src="{{ asset('solve-logo.png') }}" alt="Solve Logo" class="h-10 w-auto object-contain">
        </div>
            <button class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 shadow-sm">العربية | EN</button>
            <button class="relative rounded-2xl border border-slate-200 bg-white p-3 text-slate-600 shadow-sm">🔔<span class="absolute left-2 top-2 h-2.5 w-2.5 rounded-full bg-rose-500"></span></button>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-600 text-sm font-extrabold text-white">SV</div>
                <div>
                    <p class="text-sm font-extrabold text-slate-900">فريق الإدارة</p>
                    <p class="text-xs text-slate-500">admin@solve.sa</p>
                </div>
            </div>
            <div class="relative" @click.outside="quickOpen = false">
                <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-card" @click="quickOpen = !quickOpen">إجراءات سريعة +</button>
                <div x-show="quickOpen" x-transition class="absolute left-0 top-16 w-72 rounded-3xl border border-slate-100 bg-white p-3 shadow-soft">
                    <a href="{{ route('admin.stores') }}" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">إضافة متجر جديد</a>
                    <a href="{{ route('admin.subscriptions') }}" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">إنشاء كوبون باقة</a>
                    <a href="{{ route('admin.support') }}" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">تعيين تذكرة للدعم</a>
                    <a href="{{ route('admin.shipping') }}" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">ربط شريك شحن جديد</a>
                </div>
            </div>
        </div>
    </div>
</header>