@extends('layouts.site')

@section('title', 'ابدأ تجربة Solve المجانية')

@section('content')
<main class="min-h-screen px-6 py-8 lg:px-10">
    <section class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <aside class="rounded-[32px] bg-slate-950 p-8 text-white shadow-card">
            <a href="{{ route('site.home') }}" class="inline-flex items-center gap-3">
                <img src="{{ asset('solve-logo.png') }}" alt="Solve" class="h-14 w-auto rounded-2xl bg-white p-2">
                <span class="text-xl font-black">Solve</span>
            </a>
            <h1 class="mt-10 text-4xl font-black leading-[1.5]">أنشئ متجرك خلال دقائق وابدأ التجربة المجانية</h1>
            <p class="mt-4 text-lg leading-9 text-slate-300">تدفق تجاري مختصر: بيانات المتجر، اختيار الباقة، إنشاء الحساب، ثم تجهيز لوحة التاجر والـ onboarding تلقائياً.</p>
            <div class="mt-8 grid gap-3">
                @foreach (['تجربة مجانية 14 يوم', 'تجهيز تلقائي للمتجر', 'Checklist ذكية داخل الداشبورد', 'إمكانية الترقية لاحقاً'] as $item)
                    <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm font-black">{{ $item }}</div>
                @endforeach
            </div>
        </aside>

        <section class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-card" x-data="{ plan: @js($selectedPlan) }">
            <div>
                <p class="text-sm font-black text-brand-600">Signup Wizard</p>
                <h2 class="mt-2 text-3xl font-black text-slate-950">ابدأ الآن</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">كل الحقول تحفظ في قاعدة البيانات وتربط المتجر بالباقات والاشتراك.</p>
            </div>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-100 bg-rose-50 p-4 text-sm font-bold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ $registerAction ?? route('commercial.signup.store') }}" class="mt-6 space-y-6">
                @csrf
                <section>
                    <h3 class="text-xl font-black text-slate-950">1. اختر الباقة</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        @foreach ($plans as $key => $item)
                            <label class="cursor-pointer rounded-[24px] border p-4 transition" :class="plan === '{{ $key }}' ? 'border-brand-500 bg-brand-50' : 'border-slate-100 bg-slate-50'">
                                <input type="radio" name="plan" value="{{ $key }}" x-model="plan" class="sr-only">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="text-lg font-black text-slate-950">{{ $item['name'] }}</h4>
                                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $item['price'] ? number_format($item['price']) . ' ر.س / شهر' : 'حسب الاتفاق' }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-brand-700">{{ $item['trial_days'] }} يوم Trial</span>
                                </div>
                                <ul class="mt-4 space-y-2 text-xs font-bold text-slate-600">
                                    @foreach (array_slice($item['features'], 0, 3) as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h3 class="text-xl font-black text-slate-950">2. بيانات المتجر</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">اسم المتجر</span>
                            <input name="store_name" value="{{ old('store_name') }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">اسم المالك</span>
                            <input name="owner_name" value="{{ old('owner_name') }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">البريد</span>
                            <input name="email" type="email" value="{{ old('email') }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">الجوال</span>
                            <input name="phone" value="{{ old('phone') }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">الدولة</span>
                            <input name="country" value="{{ old('country', 'Saudi Arabia') }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">المدينة</span>
                            <input name="city" value="{{ old('city') }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">نوع النشاط</span>
                            <input name="activity_type" value="{{ old('activity_type') }}" placeholder="Fashion, perfumes, restaurants" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">رابط متجر مقترح</span>
                            <input name="store_slug" value="{{ old('store_slug') }}" placeholder="atlas-store" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                    </div>
                </section>

                <section>
                    <h3 class="text-xl font-black text-slate-950">3. حساب الدخول</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">كلمة المرور</span>
                            <input name="password" type="password" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">تأكيد كلمة المرور</span>
                            <input name="password_confirmation" type="password" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-400">
                        </label>
                    </div>
                </section>

                <label class="flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-600">
                    <input type="checkbox" name="terms" value="1" required class="mt-1">
                    <span>أوافق على الشروط وسياسة الخصوصية، وأفهم أن الباقة المجانية تفتح لوحة التحكم مع قفل بعض المميزات حتى الترقية.</span>
                </label>

                <div class="flex flex-col gap-3 rounded-[24px] bg-slate-50 p-4 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm font-bold leading-7 text-slate-500">بعد الإنشاء سيتم فتح لوحة التاجر مباشرة، وتظهر خطوات الإعداد الذكية حسب المتجر والباقة.</p>
                    <button class="h-12 rounded-2xl bg-slate-950 px-7 text-sm font-black text-white">إنشاء المتجر</button>
                </div>
            </form>
        </section>
    </section>
</main>
@endsection
