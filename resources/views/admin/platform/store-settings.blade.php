@extends('layouts.admin')

@section('title', 'Solve Admin | إعدادات المتجر')

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <p class="text-sm font-bold text-brand-600">Advanced Store Settings</p>
    <h2 class="mt-2 text-3xl font-extrabold text-slate-900">إعدادات المتجر: {{ $storeId }}</h2>
    <p class="mt-3 text-sm leading-8 text-slate-500">الهوية، اللوجو، الألوان، الدفع، الشحن، الضرائب، والفواتير.</p>
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-2">
    @foreach (['identity' => 'الهوية', 'branding' => 'اللوجو والألوان', 'payments' => 'طرق الدفع', 'shipping' => 'الشحن', 'taxes' => 'الضرائب', 'invoices' => 'الفواتير'] as $key => $label)
        <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
            <h3 class="text-2xl font-extrabold text-slate-900">{{ $label }}</h3>
            <pre class="mt-4 overflow-auto rounded-2xl bg-slate-950 p-4 text-left text-xs text-slate-100">{{ json_encode($settings->{$key} ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endforeach
</section>
@endsection
