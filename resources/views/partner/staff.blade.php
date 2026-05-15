@extends('layouts.partner')

@section('title', 'Solve Partner | الموظفون والصلاحيات')

@section('partner-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <p class="text-sm font-bold text-brand-600">Store RBAC</p>
    <h1 class="mt-2 text-3xl font-extrabold text-slate-900">الموظفون والصلاحيات</h1>
    <p class="mt-3 max-w-3xl text-sm leading-8 text-slate-500">إدارة مستخدمي {{ $partner['name'] }} فقط. لا يمكن عرض أو تعديل مستخدمي أي متجر آخر.</p>
</section>

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($partner['users'] as $user)
            <div class="rounded-[28px] border border-slate-100 bg-slate-50 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900">{{ $user['name'] }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $user['email'] }}</p>
                        <p class="mt-1 text-xs font-bold text-slate-400">{{ $user['username'] }}</p>
                    </div>
                    <span class="rounded-2xl bg-brand-600 px-3 py-2 text-xs font-bold text-white">{{ $user['role'] }}</span>
                </div>
                <div class="mt-5 grid gap-2 text-sm font-bold text-slate-600">
                    <span class="rounded-2xl bg-white px-4 py-3">النطاق: {{ $partner['store_id'] }}</span>
                    <span class="rounded-2xl bg-white px-4 py-3">الصلاحيات: {{ $user['role'] === 'partner_admin' ? 'إدارة كاملة للمتجر' : 'قراءة وتشغيل محدود' }}</span>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
