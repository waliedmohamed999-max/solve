@extends('layouts.partner')

@section('title', 'الباقة الحالية - Solve Merchant')

@section('partner-content')
    @php $subscription = $subscriptionSuite['subscription']; @endphp
    <section class="space-y-6">
        @if (session('subscription_warning'))
            <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5 text-sm font-black text-amber-800">
                {{ session('subscription_warning') }}
                @if (session('upgrade_prompt'))
                    <span class="mt-1 block text-xs font-bold text-amber-700">{{ session('upgrade_prompt') }}</span>
                @endif
            </div>
        @endif

        <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-black text-solve-600">Subscription</p>
                    <h1 class="mt-2 text-3xl font-black">الباقة الحالية: {{ $subscription['plan_name'] }}</h1>
                    <p class="mt-2 text-sm font-bold text-slate-500">التجديد في {{ $subscription['renews_at'] ?? '-' }} / الحالة {{ $subscription['status'] }}</p>
                </div>
                <a href="{{ route('partner.subscription.plans') }}" class="rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">ترقية الباقة</a>
            </div>
        </div>

        @if (count($subscriptionSuite['alerts']))
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($subscriptionSuite['alerts'] as $alert)
                    <div class="rounded-3xl bg-amber-50 p-5 text-sm font-black text-amber-800">{{ $alert['message'] }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
                <h2 class="text-xl font-black">استخدام المميزات</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($subscription['usage'] as $usage)
                        <div>
                            <div class="mb-2 flex justify-between text-xs font-black text-slate-500">
                                <span>{{ $usage['key'] }}</span>
                                <span>{{ $usage['used'] }} / {{ $usage['limit'] }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-3 rounded-full bg-solve-600" style="width: {{ $usage['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
                <h2 class="text-xl font-black">المميزات المتاحة</h2>
                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    @foreach ($subscriptionSuite['features'] as $feature)
                        <div class="rounded-2xl px-4 py-3 text-sm font-black {{ $feature['enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' }}">
                            {{ $feature['label'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
