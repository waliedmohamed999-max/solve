@extends('layouts.admin')

@section('title', 'Solve Admin | Marketplace Apps')

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <p class="text-sm font-bold text-brand-600">Marketplace Apps</p>
    <h2 class="mt-2 text-3xl font-extrabold text-slate-900">تطبيقات السوق</h2>
    <p class="mt-3 text-sm leading-8 text-slate-500">تطبيقات الدفع، الشحن، التسويق، والتحليلات.</p>
</section>

<section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    @foreach ($apps as $app)
        @php $data = is_array($app) ? $app : $app->toArray(); @endphp
        <div class="rounded-[28px] border border-white/70 bg-white p-5 shadow-card">
            <span class="rounded-2xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700">{{ $data['category'] }}</span>
            <h3 class="mt-5 text-xl font-extrabold text-slate-900">{{ $data['name'] }}</h3>
            <p class="mt-2 text-sm font-bold text-slate-500">{{ $data['provider'] }}</p>
            <p class="mt-4 min-h-16 text-sm leading-7 text-slate-500">{{ $data['description'] }}</p>
            <button class="mt-5 w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">{{ $data['status'] === 'installed' ? 'إدارة' : 'تثبيت' }}</button>
        </div>
    @endforeach
</section>
@endsection
