<article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-xl font-black">{{ $app['name'] }}</h2>
            <p class="mt-1 text-sm font-bold text-slate-500">{{ $app['provider'] ?? '-' }} · {{ $app['category'] ?? '-' }} · {{ $app['plan'] ?? 'Starter' }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-black {{ in_array(($app['status_key'] ?? ''), ['installed'], true) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $app['status'] }}</span>
    </div>
    <div class="mt-4 grid gap-2 text-sm font-bold text-slate-500">
        <p>{{ implode(' · ', $app['features'] ?? []) }}</p>
        <p>الصلاحيات: {{ implode(', ', $app['permissions'] ?? []) }}</p>
        <p>السعر: {{ $app['price'] ?? '-' }}</p>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('partner.apps.show', ['app' => $app['id']]) }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">التفاصيل</a>
        @if (($app['status_key'] ?? '') === 'not_installed')
            <form method="POST" action="{{ route('partner.apps.install', ['app' => $app['id']]) }}">@csrf<button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">تثبيت</button></form>
        @elseif (($app['status_key'] ?? '') !== 'admin_paused')
            <a href="{{ route('partner.apps.settings', ['app' => $app['id']]) }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">الإعدادات</a>
            <form method="POST" action="{{ route('partner.apps.test', ['app' => $app['id']]) }}">@csrf<button class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">اختبار</button></form>
        @endif
    </div>
</article>
