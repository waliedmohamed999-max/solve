@extends('layouts.partner')

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    <nav class="flex flex-wrap items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400">
        <a href="{{ route('partner.dashboard') }}" class="hover:text-solve-600">لوحة التحكم</a><span>/</span>
        <a href="{{ route('partner.settings') }}" class="hover:text-solve-600">الإعدادات</a><span>/</span>
        <span class="text-slate-950 dark:text-white">الموظفين</span>
    </nav>

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-black text-solve-600">Store ID: {{ $partner['store_id'] }}</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">الموظفين</h1>
                <p class="mt-2 text-sm font-bold text-slate-500">إدارة فريق المتجر والدعوات والأدوار، وكل صف مرتبط بالمتجر الحالي فقط.</p>
            </div>
            <a href="/api/partner/staff" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-black">API JSON</a>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_360px]">
        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">فريق العمل</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">{{ $staffPage['meta']['total'] }} مستخدم داخل {{ $partner['store_id'] }}.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-right text-sm">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950">
                        <tr>
                            <th class="px-5 py-4">الاسم</th>
                            <th class="px-5 py-4">البريد</th>
                            <th class="px-5 py-4">الدور</th>
                            <th class="px-5 py-4">الحالة</th>
                            <th class="px-5 py-4">آخر دخول</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($staffPage['staff'] as $staff)
                            <tr>
                                <td class="px-5 py-4 font-black text-slate-950 dark:text-white">{{ $staff['name'] }}</td>
                                <td class="px-5 py-4 font-bold text-slate-500">{{ $staff['email'] }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700">{{ $staff['role'] }}</span></td>
                                <td class="px-5 py-4">{{ $staff['status'] }}</td>
                                <td class="px-5 py-4">{{ $staff['last_login_at'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="space-y-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">دعوة موظف</h2>
                @if ($canManageSettings)
                    <form method="POST" action="/api/partner/staff/invite" class="mt-4 space-y-3">
                        @csrf
                        <input name="name" required placeholder="اسم الموظف" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold">
                        <input name="email" required type="email" placeholder="email@example.com" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold">
                        <select name="role" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold">
                            @foreach ($staffPage['roles'] as $role)
                                <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                            @endforeach
                        </select>
                        <button class="w-full rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إرسال الدعوة</button>
                    </form>
                @else
                    <p class="mt-3 rounded-2xl bg-amber-50 p-4 text-sm font-black text-amber-700">صلاحية عرض فقط.</p>
                @endif
            </article>
        </aside>
    </section>
</div>
@endsection
