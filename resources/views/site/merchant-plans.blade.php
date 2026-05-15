@extends('layouts.site')

@section('title', 'اختيار الباقة - Solve')

@section('content')
<main class="min-h-screen px-6 py-10 lg:px-10">
    <section class="mx-auto max-w-7xl rounded-[32px] bg-white p-8 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-black text-brand-600">Merchant onboarding</p>
                <h1 class="mt-2 text-4xl font-black text-slate-950">اختر الباقة المناسبة لمتجرك</h1>
                <p class="mt-3 text-sm font-bold text-slate-500">بدأت الآن على الباقة المجانية. يمكنك دخول الداشبورد أو الترقية لفتح المميزات المقفولة.</p>
            </div>
            <a href="{{ route('merchant.onboarding') }}" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white">متابعة الإعداد</a>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-4">
            @foreach ($plans as $plan)
                <article class="rounded-[28px] border {{ $subscription['plan_name'] === $plan['name'] ? 'border-brand-500 bg-brand-50' : 'border-slate-100 bg-slate-50' }} p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-black text-slate-950">{{ $plan['name'] }}</h2>
                            <p class="mt-1 text-sm font-bold text-slate-500">{{ $plan['price'] === null ? 'Enterprise' : number_format((float) $plan['price']) . ' ر.س / شهر' }}</p>
                        </div>
                        @if ($plan['recommended'] ?? false)
                            <span class="rounded-full bg-brand-600 px-3 py-1 text-xs font-black text-white">مقترحة</span>
                        @endif
                    </div>
                    <ul class="mt-5 space-y-2 text-sm font-bold text-slate-600">
                        @foreach (array_slice($plan['features'], 0, 5) as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @if ($subscription['plan_name'] === $plan['name'])
                        <span class="mt-6 block rounded-2xl bg-white px-4 py-3 text-center text-sm font-black text-brand-700">الباقة الحالية</span>
                    @else
                        <a href="{{ route('partner.subscription.checkout', ['planId' => $plan['slug']]) }}" class="mt-6 block rounded-2xl bg-slate-950 px-4 py-3 text-center text-sm font-black text-white">ترقية الآن</a>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
</main>
@endsection
