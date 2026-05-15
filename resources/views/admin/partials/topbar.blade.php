<header class="sticky top-4 z-30 rounded-[28px] border border-slate-100 bg-white/95 p-4 shadow-card backdrop-blur">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-center gap-3">
            <button class="rounded-2xl border border-slate-200 p-3 text-slate-600 lg:hidden" @click="mobileNav = true">☰</button>
            <div class="relative w-full max-w-2xl"
                x-data="{ q: '', results: null, loading: false, open: false, async search() { if (this.q.length < 2) { this.results = null; return; } this.loading = true; this.open = true; const response = await fetch('{{ route('admin.api.search') }}?q=' + encodeURIComponent(this.q)); this.results = await response.json(); this.loading = false; } }"
                x-on:keydown.window.ctrl.k.prevent="$refs.searchBox.focus(); open = true"
                x-on:keydown.window.meta.k.prevent="$refs.searchBox.focus(); open = true"
                @click.outside="open = false">
                <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">⌕</span>
                <input x-ref="searchBox" x-model.debounce.300ms="q" x-on:input="search()" x-on:focus="open = true" type="text"
                    placeholder="ابحث عن طلب، منتج، عميل أو فاتورة"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-11 pl-4 text-sm font-bold outline-none transition focus:border-brand-300 focus:bg-white">
                <div x-cloak x-show="open && (loading || results)" x-transition class="absolute right-0 top-14 z-50 w-full overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-soft">
                    <div x-cloak x-show="loading" class="p-4 text-sm font-bold text-slate-500">جاري البحث...</div>
                    <template x-if="results">
                        <div class="max-h-96 overflow-y-auto p-3">
                            <template x-for="(items, group) in results" :key="group">
                                <div class="mb-3">
                                    <p class="px-2 text-xs font-black uppercase tracking-[0.2em] text-brand-500" x-text="group"></p>
                                    <template x-for="item in items" :key="item.id || item.order_number || item.name || item.title">
                                        <div class="mt-2 rounded-2xl bg-slate-50 px-3 py-2 text-sm">
                                            <p class="font-black text-slate-900" x-text="item.name || item.order_number || item.id || item.title"></p>
                                            <p class="text-xs text-slate-500" x-text="item.store || item.customer || item.status || item.owner || ''"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.products', ['create' => 1]) }}" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white">إضافة منتج</a>
            <a href="{{ route('admin.notifications') }}" class="relative rounded-2xl border border-slate-200 bg-white p-3 text-slate-600">🔔<span class="absolute left-2 top-2 h-2.5 w-2.5 rounded-full bg-rose-500"></span></a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-500">خروج</button>
            </form>
        </div>
    </div>
</header>
