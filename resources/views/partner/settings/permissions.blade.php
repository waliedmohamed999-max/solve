@extends('layouts.partner')

@section('title', 'Solve Merchant | الصلاحيات')

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    <nav class="flex flex-wrap items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400">
        <a href="{{ route('partner.dashboard') }}" class="hover:text-solve-600">لوحة التحكم</a><span>/</span>
        <a href="{{ route('partner.settings') }}" class="hover:text-solve-600">الإعدادات</a><span>/</span>
        <span class="text-slate-950 dark:text-white">الصلاحيات</span>
    </nav>

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-black text-solve-600">RBAC • {{ $partner['store_id'] }}</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">الأدوار والصلاحيات</h1>
                <p class="mt-2 text-sm font-bold text-slate-500">أدوار جاهزة وأدوار مخصصة تتحكم في ظهور الأقسام والأزرار والعمليات.</p>
            </div>
            <a href="/api/partner/roles" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-black">API JSON</a>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_360px]">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">الأدوار</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($rolesPage['roles'] as $role)
                    <div class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-black text-slate-950 dark:text-white">{{ $role['name'] }}</h3>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $role['description'] }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-500">{{ ($role['custom'] ?? false) ? 'مخصص' : 'أساسي' }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach (array_slice($role['permissions'] ?? [], 0, 8) as $permission)
                                <span class="rounded-full bg-solve-50 px-2.5 py-1 text-[11px] font-black text-solve-700">{{ $permission }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <aside class="space-y-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">Role مخصص</h2>
                @if ($canManageSettings)
                    <form method="POST" action="/api/partner/roles" class="mt-4 space-y-3">
                        @csrf
                        <input name="name" required placeholder="اسم الدور" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold">
                        <textarea name="description" rows="3" placeholder="وصف الدور" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold"></textarea>
                        <input name="permissions[]" value="view-orders" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold">
                        <button class="w-full rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إنشاء الدور</button>
                    </form>
                @else
                    <p class="mt-3 rounded-2xl bg-amber-50 p-4 text-sm font-black text-amber-700">صلاحية عرض فقط.</p>
                @endif
            </article>
        </aside>
    </section>
</div>
@endsection
