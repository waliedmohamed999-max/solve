@php
    $navItems = [
        ['label' => 'الرئيسية', 'route' => 'admin.dashboard', 'icon' => '⌂'],
        ['label' => 'المتاجر', 'route' => 'admin.stores', 'icon' => '▣'],
        ['label' => 'الاشتراكات', 'route' => 'admin.subscriptions', 'icon' => '◫'],
        ['label' => 'المدفوعات', 'route' => 'admin.payments', 'icon' => '◌'],
        ['label' => 'الشحن', 'route' => 'admin.shipping', 'icon' => '◭'],
        ['label' => 'التحليلات', 'route' => 'admin.analytics', 'icon' => '◔'],
        ['label' => 'خدمات الشركاء', 'route' => 'admin.partners', 'icon' => '✦'],
        ['label' => 'الدعم الفني', 'route' => 'admin.support', 'icon' => '◎'],
        ['label' => 'التطبيقات', 'route' => 'admin.apps', 'icon' => '◍'],
        ['label' => 'محتوى الموقع', 'route' => 'admin.site-content', 'icon' => '▤'],
        ['label' => 'الإعدادات', 'route' => 'admin.settings', 'icon' => '⚙'],
    ];
@endphp

<aside class="fixed inset-y-4 right-4 z-40 w-[300px] rounded-[32px] border border-white/60 bg-white/90 p-5 shadow-soft backdrop-blur-xl lg:static lg:block"
    :class="mobileNav ? 'block' : 'hidden lg:block'">
    <div class="flex items-center justify-between border-b border-slate-100 pb-5">
        <div>
            <div class="flex items-center gap-3">
                <img src="{{ asset('solve-logo.png') }}" alt="Solve Logo" class="h-12 w-auto object-contain lg:h-14">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-500">Solve</p>
                    <h1 class="text-2xl font-extrabold text-slate-900">Admin Hub</h1>
                </div>
            </div>
            <p class="mt-3 text-sm leading-6 text-slate-500">مركز إدارة عمليات منصة Solve: المتاجر، الاشتراكات، الدعم والتحليلات.</p>
        </div>
        <button class="rounded-2xl border border-slate-200 p-2 text-slate-500 lg:hidden" @click="mobileNav = false">✕</button>
    </div>

    <nav class="mt-6 space-y-2 text-sm font-bold">
        @foreach ($navItems as $item)
            @php $isActive = ($activeRoute ?? '') === $item['route']; @endphp
            <a href="{{ route($item['route']) }}"
                class="{{ $isActive ? 'bg-brand-600 text-white shadow-card' : 'text-slate-600 hover:bg-slate-50' }} flex items-center justify-between rounded-2xl px-4 py-3 transition">
                <span>{{ $item['label'] }}</span>
                <span class="{{ $isActive ? 'bg-white/20 text-white' : 'bg-brand-50 text-brand-600' }} flex h-10 w-10 items-center justify-center rounded-xl text-base">
                    {{ $item['icon'] }}
                </span>
            </a>
        @endforeach
    </nav>
</aside>