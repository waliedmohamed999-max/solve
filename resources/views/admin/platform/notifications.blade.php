@extends('layouts.admin')

@section('title', 'Solve Admin | مركز التنبيهات')

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <p class="text-sm font-bold text-brand-600">Notification Center</p>
    <h2 class="mt-2 text-3xl font-extrabold text-slate-900">مركز التنبيهات</h2>
    <p class="mt-3 text-sm leading-8 text-slate-500">طلبات جديدة، مدفوعات، اشتراكات قرب الانتهاء، تذاكر دعم، ومنتجات منخفضة المخزون.</p>
</section>

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <a href="{{ $notification->url ?: '#' }}" class="block rounded-3xl border border-slate-100 bg-slate-50 p-4 transition hover:bg-brand-50">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="font-extrabold text-slate-900">{{ $notification->title }}</p>
                        <p class="mt-2 text-sm leading-7 text-slate-500">{{ $notification->body }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-2xl bg-white px-3 py-2 text-xs font-bold text-slate-600">{{ $notification->type }}</span>
                        <span class="rounded-2xl bg-brand-600 px-3 py-2 text-xs font-bold text-white">{{ $notification->severity }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-3xl bg-slate-50 p-10 text-center text-sm font-bold text-slate-500">لا توجد تنبيهات حتى الآن.</div>
        @endforelse
    </div>
    @if (method_exists($notifications, 'links'))
        <div class="mt-5">{{ $notifications->links() }}</div>
    @endif
</section>
@endsection
