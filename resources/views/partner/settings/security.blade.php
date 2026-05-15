@extends('layouts.partner')

@section('title', 'Solve Merchant | الأمان')

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    <nav class="flex flex-wrap items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400">
        <a href="{{ route('partner.dashboard') }}" class="hover:text-solve-600">لوحة التحكم</a><span>/</span>
        <a href="{{ route('partner.settings') }}" class="hover:text-solve-600">الإعدادات</a><span>/</span>
        <span class="text-slate-950 dark:text-white">الأمان</span>
    </nav>

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-black text-solve-600">Security • {{ $partner['store_id'] }}</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">الأمان والجلسات</h1>
                <p class="mt-2 text-sm font-bold text-slate-500">إدارة المصادقة الثنائية، الجلسات النشطة، وسجل تسجيل الدخول للمتجر الحالي.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="/api/partner/security/sessions" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-black">Sessions API</a>
                <a href="/api/partner/security/login-history" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-black">Login API</a>
            </div>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_360px]">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">الجلسات النشطة</h2>
            <div class="mt-4 space-y-3">
                @foreach ($securitySessions['sessions'] as $session)
                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-100 p-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-black text-slate-950 dark:text-white">{{ $session['device'] ?? $session['id'] }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $session['ip_address'] ?? '-' }} • {{ $session['location'] ?? '-' }} • {{ $session['status'] }}</p>
                        </div>
                        @if ($canManageSettings)
                            <form method="POST" action="/api/partner/security/sessions/{{ $session['id'] }}">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-full border border-rose-200 px-4 py-2 text-xs font-black text-rose-700">إنهاء الجلسة</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </article>

        <aside class="space-y-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">المصادقة الثنائية</h2>
                @if ($canManageSettings)
                    <div class="mt-4 grid gap-3">
                        <form method="POST" action="/api/partner/security/2fa/enable">@csrf<button class="w-full rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">تفعيل 2FA</button></form>
                        <form method="POST" action="/api/partner/security/2fa/disable">@csrf<button class="w-full rounded-full border border-slate-200 px-5 py-3 text-sm font-black">تعطيل 2FA</button></form>
                    </div>
                @else
                    <p class="mt-3 rounded-2xl bg-amber-50 p-4 text-sm font-black text-amber-700">صلاحية عرض فقط.</p>
                @endif
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">سجل الدخول</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($loginHistory['rows'] as $row)
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                            <p class="text-sm font-black text-slate-950 dark:text-white">{{ $row['event'] ?? 'login' }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $row['ip_address'] ?? '-' }} • {{ $row['device'] ?? '-' }}</p>
                        </div>
                    @endforeach
                </div>
            </article>
        </aside>
    </section>
</div>
@endsection
