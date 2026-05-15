@extends('layouts.admin')

@section('title', 'الباقات والاشتراكات - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')
    @include('admin.components.confirm-dialog')

    <section class="space-y-6">
        @include('admin.components.data-toolbar', ['eyebrow' => 'Subscription Engine', 'title' => 'الباقات وحدود الاستخدام'])

        <div class="grid gap-5 xl:grid-cols-3">
            @foreach ($plans as $plan)
                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card transition hover:-translate-y-1 hover:shadow-soft">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900">{{ $plan['name'] }}</h3>
                            <p class="mt-2 text-sm font-bold text-slate-500">{{ $plan['status'] }}</p>
                        </div>
                        <span class="rounded-2xl bg-brand-50 px-4 py-2 text-sm font-black text-brand-700">{{ $plan['price'] }}</span>
                    </div>

                    <div class="mt-6 grid gap-3">
                        @foreach ($plan['limits'] as $key => $value)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-3 text-sm">
                                <span class="font-bold text-slate-500">{{ $key }}</span>
                                <span class="font-black text-slate-900">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <p class="text-sm font-black text-slate-900">المميزات المقيدة</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($plan['locked_features'] as $feature)
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">{{ $feature }}</span>
                            @empty
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">كل المميزات متاحة</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-6 flex gap-2">
                        <button class="flex-1 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white" @click="$dispatch('solve-toast', 'تم تحديث حدود الباقة')">تعديل الحدود</button>
                        <button class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600" @click="$dispatch('confirm-action', { title: 'إيقاف الباقة؟', body: 'سيتم إيقاف المميزات المرتبطة بهذه الباقة للمتاجر المشتركة.' })">إيقاف</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
            <h3 class="text-xl font-black text-slate-900">سجل المدفوعات والتجديدات</h3>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                @foreach (['تنبيه انتهاء اشتراك خلال 7 أيام', 'تجديد Enterprise لمتجر أطلس', 'ترقية متجر رواء إلى Growth'] as $event)
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-600">{{ $event }}</div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
