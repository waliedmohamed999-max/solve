@extends('layouts.site')

@section('title', 'Solve | منصة إنشاء المتاجر الإلكترونية')

@section('content')
<div
    x-data="{
        testimonialPage: 0,
        testimonials: @js($testimonials),
        get testimonialPages() {
            const size = 3;
            const pages = [];
            for (let i = 0; i < this.testimonials.length; i += size) {
                pages.push(this.testimonials.slice(i, i + size));
            }
            return pages;
        }
    }"
>
    <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-8 lg:px-10">
        <div class="flex items-center gap-3">
            <img src="{{ asset('solve-logo.png') }}" alt="Solve Logo" class="h-24 w-auto max-w-[240px] object-contain lg:h-32 lg:max-w-[320px]">
        </div>
        <nav class="hidden items-center gap-8 text-lg text-slate-600 md:flex">
            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}" class="transition hover:text-brand-600">{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ $hero['primary_href'] }}" class="rounded-full bg-brand-600 px-8 py-3 text-white shadow-soft transition hover:bg-brand-700">{{ $hero['primary_label'] }}</a>
        </nav>
    </header>

    <section class="mx-auto grid max-w-7xl gap-10 px-6 pb-24 pt-10 lg:grid-cols-2 lg:px-10 lg:pb-32 lg:pt-16 lg:[direction:ltr]">
        <div class="order-1 flex items-center justify-center lg:order-1">
            <img src="{{ asset($hero['image']) }}" alt="{{ $hero['title'] }}" class="w-full max-w-2xl drop-shadow-[0_35px_60px_rgba(91,95,202,0.16)]">
        </div>
        <div class="order-2 flex flex-col items-start justify-center text-right lg:order-2 lg:pr-8 lg:[direction:rtl]">
            <h1 class="text-4xl font-extrabold leading-[1.8] text-brand-600 lg:text-6xl">{{ $hero['title'] }}</h1>
            <p class="mt-6 max-w-2xl text-2xl leading-[2.1] text-slate-600 lg:text-3xl">{{ $hero['description'] }}</p>
            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ $hero['primary_href'] }}" class="rounded-full bg-brand-600 px-12 py-4 text-xl font-bold text-white shadow-soft hover:bg-brand-700">{{ $hero['primary_label'] }}</a>
                <a href="{{ $hero['secondary_href'] }}" class="rounded-full border border-brand-200 px-10 py-4 text-xl font-bold text-brand-600">{{ $hero['secondary_label'] }}</a>
            </div>
        </div>
    </section>

    <section id="services" class="bg-slate-950 px-6 py-16 text-white lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold lg:text-5xl">{{ $servicesHeading }}</h2>
                <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-300">{{ $servicesSubheading }}</p>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($serviceCards as $card)
                    <div class="rounded-[30px] border border-brand-300/50 bg-[#171827] p-6 text-center shadow-[0_18px_60px_rgba(0,0,0,0.35)] transition hover:-translate-y-1 hover:border-brand-300">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[24px] border border-brand-300/60 bg-brand-500/10 text-4xl text-brand-200">{{ $card['icon'] }}</div>
                        <h3 class="mt-6 text-3xl font-extrabold leading-[1.6] text-white">{{ $card['title'] }}</h3>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-6 py-16 lg:px-10">
        <div class="mx-auto max-w-7xl text-center">
            <h2 class="text-5xl font-medium text-slate-700">{{ $featuresHeading }}</h2>
            <div class="mx-auto mt-5 h-1 w-24 rounded-full bg-brand-500"></div>
        </div>
        <div class="mx-auto mt-20 max-w-7xl space-y-24 lg:space-y-32">
            @foreach ($featureSections as $section)
                <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_1fr] lg:[direction:ltr]">
                    <div class="relative flex justify-center section-glow">
                        <img src="{{ asset($section['image']) }}" alt="{{ $section['title'] }}" class="relative z-10 w-full max-w-2xl">
                    </div>
                    <div class="text-center lg:text-right lg:[direction:rtl]">
                        <h3 class="text-5xl font-medium text-brand-600">{{ $section['title'] }}</h3>
                        <p class="mx-auto mt-6 max-w-2xl text-2xl leading-[2] text-slate-600 lg:mx-0">{{ $section['description'] }}</p>
                        <a href="#catalog" class="mt-6 inline-flex items-center gap-2 text-2xl text-brand-500 hover:text-brand-700">{{ $section['link'] }} <span>‹</span></a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-10">
        <div class="text-center">
            <h2 class="text-5xl font-medium text-slate-700">{{ $showcaseHeading }}</h2>
            <div class="mx-auto mt-5 h-1 w-24 rounded-full bg-brand-500"></div>
        </div>
        <div class="mt-16 grid gap-6 lg:grid-cols-[70px_1fr_1fr_1fr_1fr_70px]">
            <button class="flex h-14 w-14 items-center justify-center self-center justify-self-center rounded-full bg-brand-600 text-3xl text-white shadow-soft">‹</button>
            @foreach ($showcaseStores as $store)
                <div class="rounded-[28px] bg-white px-8 py-10 text-center shadow-card">
                    <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-[28px] {{ $store['tone'] }} text-3xl font-extrabold text-white">{{ $store['badge'] }}</div>
                    <h3 class="mt-6 text-3xl text-slate-700">{{ $store['name'] }}</h3>
                    <div class="mt-8 flex items-center justify-center gap-3 text-brand-500">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full border border-brand-200 text-xl">◉</span>
                        <span class="flex h-12 w-12 items-center justify-center rounded-full border border-brand-200 text-xl">△</span>
                        <span class="flex h-12 w-12 items-center justify-center rounded-full border border-brand-200 text-xl">▷</span>
                    </div>
                </div>
            @endforeach
            <button class="flex h-14 w-14 items-center justify-center self-center justify-self-center rounded-full bg-brand-600 text-3xl text-white shadow-soft">›</button>
        </div>
        <div class="mt-8 flex justify-center gap-3">
            <span class="h-3 w-3 rounded-full border border-brand-200 bg-white"></span>
            <span class="h-3 w-3 rounded-full bg-brand-500"></span>
        </div>
    </section>

    <section id="catalog" class="bg-slate-950 px-6 py-20 text-white lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold text-white lg:text-5xl">{{ $catalogHeading }}</h2>
                <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-300">حلول جاهزة للتشغيل والنمو، مصممة بلغة بصرية أقرب لهوية Solve وتفتح صفحة منتج كاملة عند الضغط.</p>
            </div>
            <div class="mt-16 space-y-16">
                @foreach ($catalogSections as $section)
                    <div>
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-3xl font-extrabold text-brand-200">{{ $section['title'] }}</h3>
                            <a href="#footer" class="text-sm font-bold text-slate-400 hover:text-white">اطلب خدمة مخصصة</a>
                        </div>
                        <div class="grid gap-5 lg:grid-cols-3 xl:grid-cols-5">
                            @foreach ($section['items'] as $product)
                                <a href="{{ route('site.products.show', $product['slug']) }}" class="group rounded-[28px] border border-brand-900/70 bg-gradient-to-b {{ $product['panel'] }} p-4 shadow-[0_20px_60px_rgba(0,0,0,0.35)] transition duration-300 hover:-translate-y-1 hover:border-brand-400/50">
                                    <div class="relative overflow-hidden rounded-[24px] bg-black/40 p-4">
                                        @if ($product['badge'])
                                            <span class="absolute left-3 top-3 rounded-full bg-brand-500 px-3 py-1 text-xs font-bold text-white">{{ $product['badge'] }}</span>
                                        @endif
                                        <div class="rounded-[22px] bg-gradient-to-br {{ $product['accent'] }} p-[1px]">
                                            <div class="flex aspect-[4/5] flex-col justify-between rounded-[21px] bg-[#0f1020] p-5">
                                                <div class="flex items-center justify-between text-xs text-slate-300">
                                                    <span class="rounded-full bg-white/10 px-3 py-1">Solve</span>
                                                    <span class="text-lg font-extrabold text-white">{{ $product['code'] }}</span>
                                                </div>
                                                <div class="space-y-3 text-right">
                                                    <h4 class="text-xl font-extrabold leading-8 text-white">{{ $product['title'] }}</h4>
                                                    <p class="text-sm leading-7 text-slate-300">{{ $product['subtitle'] }}</p>
                                                </div>
                                                <div class="grid grid-cols-3 gap-2 opacity-80">
                                                    <span class="h-2 rounded-full bg-fuchsia-400"></span>
                                                    <span class="h-2 rounded-full bg-sky-400"></span>
                                                    <span class="h-2 rounded-full bg-brand-400"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-2 pb-2 pt-5 text-right">
                                        <h4 class="text-xl font-extrabold leading-8 text-white transition group-hover:text-brand-200">{{ $product['title'] }}</h4>
                                        <p class="mt-2 min-h-[56px] text-sm leading-7 text-slate-300">{{ $product['description'] }}</p>
                                        <div class="mt-4 flex items-end justify-between gap-3">
                                            <span class="text-sm font-bold text-brand-200">عرض التفاصيل</span>
                                            <div class="text-left">
                                                @if ($product['old_price'])
                                                    <p class="text-sm text-slate-500 line-through">{{ $product['old_price'] }}</p>
                                                @endif
                                                <p class="text-2xl font-extrabold text-white">{{ $product['price'] }}</p>
                                                @if ($product['price_note'])
                                                    <p class="text-xs text-slate-400">{{ $product['price_note'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-slate-950 px-6 py-20 text-white lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-brand-200">
                    <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full border border-brand-300/40 bg-white/5 text-2xl" @click="testimonialPage = testimonialPage === 0 ? testimonialPages.length - 1 : testimonialPage - 1">‹</button>
                    <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full border border-brand-300/40 bg-white/5 text-2xl" @click="testimonialPage = testimonialPage === testimonialPages.length - 1 ? 0 : testimonialPage + 1">›</button>
                </div>
                <div class="text-right">
                    <h2 class="text-4xl font-extrabold text-brand-200">{{ $testimonialsHeading }}</h2>
                    <p class="mt-3 text-lg text-slate-400">{{ $testimonialsSubheading }}</p>
                </div>
            </div>
            <template x-for="(page, pageIndex) in testimonialPages" :key="pageIndex">
                <div x-show="testimonialPage === pageIndex" x-transition class="mt-12">
                    <div class="grid gap-6 lg:grid-cols-3">
                        <template x-for="item in page" :key="item.name">
                            <div class="rounded-[30px] bg-[#1c1d33] p-8 text-center shadow-[0_20px_60px_rgba(0,0,0,0.3)]">
                                <div class="text-right text-6xl text-white/10">“</div>
                                <p class="-mt-4 text-sm font-bold uppercase tracking-[0.18em] text-brand-200" x-text="item.brand"></p>
                                <h3 class="mt-6 text-3xl font-extrabold" x-text="item.name"></h3>
                                <p class="mt-3 text-2xl text-amber-300">★★★★★</p>
                                <p class="mt-6 text-xl leading-[2] text-slate-200" x-text="item.quote"></p>
                            </div>
                        </template>
                    </div>
                    <div class="mt-8 flex justify-center gap-3">
                        <template x-for="(dot, dotIndex) in testimonialPages" :key="dotIndex">
                            <span class="h-1.5 w-16 rounded-full" :class="testimonialPage === dotIndex ? 'bg-brand-300' : 'bg-brand-900'"></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </section>

    <section class="bg-slate-950 px-6 py-20 text-white lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold text-brand-200 lg:text-5xl">{{ $faqHeading }}</h2>
                <p class="mt-4 text-lg text-slate-400">{{ $faqSubheading }}</p>
            </div>
            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                @foreach ($faqs as $faq)
                    <div x-data="{ open: true }" class="rounded-[28px] border border-brand-300/50 bg-[#1c1d33] p-6 shadow-[0_20px_60px_rgba(0,0,0,0.28)]">
                        <button type="button" class="flex w-full items-start justify-between gap-4 text-right" @click="open = !open">
                            <div>
                                <h3 class="text-2xl font-extrabold text-brand-100">{{ $faq['question'] }}</h3>
                            </div>
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-300/20 text-3xl text-brand-100" x-text="open ? '−' : '+'"></span>
                        </button>
                        <p x-show="open" x-transition class="mt-6 text-xl leading-[2] text-slate-200">{{ $faq['answer'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl items-center gap-10 px-6 py-20 lg:grid-cols-2 lg:px-10 lg:[direction:ltr]">
        <div class="text-center lg:text-right lg:[direction:rtl]">
            <h2 class="text-5xl font-medium text-brand-600">{{ $appSection['title'] }}</h2>
            <p class="mt-6 text-2xl leading-[2] text-slate-600">{{ $appSection['description'] }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-4 lg:justify-start">
                <span class="rounded-xl bg-black px-6 py-3 text-xl text-white">{{ $appSection['google_label'] }}</span>
                <span class="rounded-xl bg-black px-6 py-3 text-xl text-white">{{ $appSection['appstore_label'] }}</span>
            </div>
        </div>
        <div class="flex justify-center">
            <img src="{{ asset($appSection['image']) }}" alt="{{ $appSection['title'] }}" class="w-full max-w-xl">
        </div>
    </section>

    <section class="mt-10 bg-brand-500 px-6 py-20 text-center text-white lg:px-10">
        <h2 class="text-4xl font-bold lg:text-6xl">{{ $ctaSection['title'] }}</h2>
        <a href="{{ $ctaSection['button_href'] }}" class="mx-auto mt-10 inline-flex rounded-full bg-white px-12 py-4 text-2xl font-bold text-brand-600">{{ $ctaSection['button_label'] }}</a>
    </section>

    <footer id="footer" class="bg-white px-6 pb-8 pt-16 lg:px-10">
        <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1.2fr_1fr_1fr]">
            <div class="text-center lg:text-right">
                <h3 class="text-3xl font-medium text-slate-700">{{ $footer['about_title'] }}</h3>
                <div class="mt-8 space-y-4 text-xl text-brand-500">
                    @foreach ($footer['about_links'] as $item)
                        <p>{{ $item }}</p>
                    @endforeach
                </div>
            </div>
            <div class="text-center">
                <h3 class="text-3xl font-medium text-slate-700">{{ $footer['links_title'] }}</h3>
                <div class="mt-8 space-y-4 text-xl text-brand-500">
                    @foreach ($footer['links'] as $item)
                        <p>{{ $item }}</p>
                    @endforeach
                </div>
            </div>
            <div class="text-center lg:text-left">
                <div class="flex items-center justify-center gap-3 lg:justify-start">
                    <img src="{{ asset('solve-logo.png') }}" alt="Solve Logo" class="h-14 w-auto object-contain">
                    <div>
                        <h3 class="text-3xl font-medium text-slate-700">{{ $footer['contact_title'] }}</h3>
                        <p class="mt-2 text-lg text-slate-500">{{ $footer['contact_description'] }}</p>
                    </div>
                </div>
                <div class="mt-8 flex justify-center gap-4 lg:justify-start">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl">▶</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl">f</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl">in</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl">◎</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl">𝕏</span>
                </div>
                <div class="mt-10 space-y-2 text-lg text-slate-500">
                    <p>السجل التجاري: {{ $footer['commercial_register'] }}</p>
                    <p>الرقم الضريبي: {{ $footer['tax_number'] }}</p>
                </div>
            </div>
        </div>
        <div class="mt-14 border-t border-slate-100 pt-6 text-center text-lg text-slate-500">{{ $footer['copyright'] }}</div>
    </footer>
</div>
@endsection