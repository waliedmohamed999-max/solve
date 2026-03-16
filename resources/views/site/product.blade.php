@extends('layouts.site')

@section('title', $product['title'] . ' | Solve')

@section('content')
<section class="min-h-screen bg-slate-950 px-6 py-10 text-white lg:px-10">
    <div class="mx-auto max-w-7xl">
        <div class="flex items-center justify-between">
            <a href="{{ route('site.home') }}#catalog" class="rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-slate-300 hover:border-brand-400 hover:text-white">العودة للخدمات</a>
            <img src="{{ asset('solve-logo.png') }}" alt="Solve Logo" class="h-12 w-auto object-contain">
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div class="rounded-[36px] border border-brand-900/70 bg-gradient-to-b {{ $product['panel'] }} p-8 shadow-[0_30px_80px_rgba(0,0,0,0.45)]">
                <p class="text-sm font-bold text-brand-200">{{ $productSection }}</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-[1.7] lg:text-6xl">{{ $product['title'] }}</h1>
                <p class="mt-4 text-xl text-slate-300">{{ $product['subtitle'] }}</p>
                <p class="mt-8 max-w-3xl text-lg leading-9 text-slate-300">{{ $product['description'] }}</p>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    @if ($product['badge'])
                        <span class="rounded-full bg-brand-500 px-4 py-2 text-sm font-bold text-white">{{ $product['badge'] }}</span>
                    @endif
                    @if ($product['price_note'])
                        <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-slate-200">{{ $product['price_note'] }}</span>
                    @endif
                </div>
                <div class="mt-10 flex items-end gap-4">
                    <div>
                        @if ($product['old_price'])
                            <p class="text-lg text-slate-500 line-through">{{ $product['old_price'] }}</p>
                        @endif
                        <p class="text-4xl font-extrabold text-white">{{ $product['price'] }}</p>
                    </div>
                    <a href="#footer" class="rounded-full bg-brand-600 px-8 py-4 text-sm font-bold text-white shadow-soft hover:bg-brand-500">اطلب هذه الخدمة</a>
                </div>
            </div>
            <div class="rounded-[36px] bg-[#0f1020] p-6 shadow-[0_30px_80px_rgba(0,0,0,0.45)]">
                <div class="rounded-[30px] bg-gradient-to-br {{ $product['accent'] }} p-[1px]">
                    <div class="rounded-[29px] bg-[#0b0c18] p-8">
                        <div class="flex items-center justify-between text-sm text-slate-300">
                            <span class="rounded-full bg-white/10 px-4 py-2">Solve Product</span>
                            <span class="text-3xl font-extrabold">{{ $product['code'] }}</span>
                        </div>
                        <div class="mt-10 rounded-[28px] bg-gradient-to-br from-white/10 to-transparent p-8">
                            <div class="grid grid-cols-3 gap-3">
                                <span class="h-3 rounded-full bg-fuchsia-400"></span>
                                <span class="h-3 rounded-full bg-sky-400"></span>
                                <span class="h-3 rounded-full bg-brand-400"></span>
                            </div>
                            <div class="mt-10 space-y-4">
                                @foreach ($product['features'] as $feature)
                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 text-right text-sm font-bold text-slate-100">{{ $feature }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section id="footer" class="mt-12 rounded-[32px] border border-white/10 bg-white/5 p-8">
            <h2 class="text-2xl font-extrabold">بيانات المنتج</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl bg-white/5 p-5">
                    <p class="text-sm text-slate-400">القسم</p>
                    <p class="mt-3 text-xl font-extrabold">{{ $productSection }}</p>
                </div>
                <div class="rounded-3xl bg-white/5 p-5">
                    <p class="text-sm text-slate-400">السعر</p>
                    <p class="mt-3 text-xl font-extrabold">{{ $product['price'] }}</p>
                </div>
                <div class="rounded-3xl bg-white/5 p-5">
                    <p class="text-sm text-slate-400">الهوية اللونية</p>
                    <p class="mt-3 text-xl font-extrabold">{{ $product['code'] }}</p>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection