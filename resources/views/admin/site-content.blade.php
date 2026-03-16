@extends('layouts.admin')

@section('title', 'Solve Admin | إدارة محتوى الموقع')

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-sm font-bold text-brand-600">Solve Admin</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $pageTitle }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-8 text-slate-500">{{ $pageDescription }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('site.home') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700">معاينة الموقع</a>
            <button form="site-content-form" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">حفظ التعديلات</button>
        </div>
    </div>
</section>

@if (session('status'))
    <section class="mt-6 rounded-[28px] border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700 shadow-card">
        {{ session('status') }}
    </section>
@endif

<form id="site-content-form" method="POST" action="{{ route('admin.site-content.update') }}" class="mt-6 space-y-6">
    @csrf

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">الصور المتاحة بالمشروع</h3>
        <div class="mt-5 flex flex-wrap gap-3">
            @foreach ($availableImages as $image)
                <span class="rounded-full bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">{{ $image }}</span>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">الهيدر والبنر الأول</h3>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach ($content['navLinks'] as $index => $link)
                <div class="rounded-[24px] bg-slate-50 p-4">
                    <p class="text-sm font-bold text-brand-600">رابط {{ $index + 1 }}</p>
                    <input name="navLinks[{{ $index }}][label]" value="{{ $link['label'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <input name="navLinks[{{ $index }}][href]" value="{{ $link['href'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                </div>
            @endforeach
            <div class="rounded-[24px] bg-slate-50 p-4 md:col-span-2">
                <input name="hero[title]" value="{{ $content['hero']['title'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                <textarea name="hero[description]" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">{{ $content['hero']['description'] }}</textarea>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <input name="hero[image]" value="{{ $content['hero']['image'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <input name="hero[primary_label]" value="{{ $content['hero']['primary_label'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <input name="hero[primary_href]" value="{{ $content['hero']['primary_href'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                </div>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <input name="hero[secondary_label]" value="{{ $content['hero']['secondary_label'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <input name="hero[secondary_href]" value="{{ $content['hero']['secondary_href'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-extrabold text-slate-900">كروت الخدمات</h3>
            <div class="grid w-full max-w-xl gap-3 md:grid-cols-2">
                <input name="servicesHeading" value="{{ $content['servicesHeading'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <input name="servicesSubheading" value="{{ $content['servicesSubheading'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            </div>
        </div>
        <div class="mt-6 grid gap-4 xl:grid-cols-4">
            @foreach ($content['serviceCards'] as $index => $card)
                <div class="rounded-[26px] bg-slate-50 p-4">
                    <input name="serviceCards[{{ $index }}][title]" value="{{ $card['title'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm" placeholder="عنوان الكارت">
                    <input name="serviceCards[{{ $index }}][icon]" value="{{ $card['icon'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm" placeholder="الأيقونة">
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-extrabold text-slate-900">أقسام المميزات</h3>
            <input name="featuresHeading" value="{{ $content['featuresHeading'] }}" class="w-full max-w-sm rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
        </div>
        <div class="mt-6 grid gap-4 xl:grid-cols-2">
            @foreach ($content['featureSections'] as $index => $section)
                <div class="rounded-[28px] bg-slate-50 p-5">
                    <input name="featureSections[{{ $index }}][title]" value="{{ $section['title'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <textarea name="featureSections[{{ $index }}][description]" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">{{ $section['description'] }}</textarea>
                    <input name="featureSections[{{ $index }}][link]" value="{{ $section['link'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <input name="featureSections[{{ $index }}][image]" value="{{ $section['image'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-extrabold text-slate-900">أمثلة المتاجر</h3>
            <input name="showcaseHeading" value="{{ $content['showcaseHeading'] }}" class="w-full max-w-sm rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
        </div>
        <div class="mt-6 grid gap-4 xl:grid-cols-2">
            @foreach ($content['showcaseStores'] as $index => $store)
                <div class="rounded-[28px] bg-slate-50 p-5">
                    <input name="showcaseStores[{{ $index }}][name]" value="{{ $store['name'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <input name="showcaseStores[{{ $index }}][badge]" value="{{ $store['badge'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                        <select name="showcaseStores[{{ $index }}][tone]" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                            @foreach ($toneOptions as $tone)
                                <option value="{{ $tone }}" @selected($store['tone'] === $tone)>{{ $tone }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-extrabold text-slate-900">كتالوج الخدمات</h3>
            <input name="catalogHeading" value="{{ $content['catalogHeading'] }}" class="w-full max-w-sm rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
        </div>
        <div class="mt-6 space-y-6">
            @foreach ($content['catalogSections'] as $sectionIndex => $section)
                <div class="rounded-[30px] bg-slate-50 p-5">
                    <input name="catalogSections[{{ $sectionIndex }}][title]" value="{{ $section['title'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold">
                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        @foreach ($section['items'] as $itemIndex => $item)
                            <div class="rounded-[26px] border border-slate-200 bg-white p-4">
                                <div class="grid gap-3 md:grid-cols-2">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][slug]" value="{{ $item['slug'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][code]" value="{{ $item['code'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                </div>
                                <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][title]" value="{{ $item['title'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][subtitle]" value="{{ $item['subtitle'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                <textarea name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][description]" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ $item['description'] }}</textarea>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][price]" value="{{ $item['price'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][old_price]" value="{{ $item['old_price'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][price_note]" value="{{ $item['price_note'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][badge]" value="{{ $item['badge'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][accent]" value="{{ $item['accent'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                    <input name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][panel]" value="{{ $item['panel'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                </div>
                                <textarea name="catalogSections[{{ $sectionIndex }}][items][{{ $itemIndex }}][features]" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">{{ implode(PHP_EOL, $item['features']) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-extrabold text-slate-900">آراء العملاء</h3>
            <div class="grid w-full max-w-xl gap-3 md:grid-cols-2">
                <input name="testimonialsHeading" value="{{ $content['testimonialsHeading'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <input name="testimonialsSubheading" value="{{ $content['testimonialsSubheading'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            </div>
        </div>
        <div class="mt-6 grid gap-4 xl:grid-cols-2">
            @foreach ($content['testimonials'] as $index => $item)
                <div class="rounded-[26px] bg-slate-50 p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <input name="testimonials[{{ $index }}][name]" value="{{ $item['name'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                        <input name="testimonials[{{ $index }}][brand]" value="{{ $item['brand'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    </div>
                    <textarea name="testimonials[{{ $index }}][quote]" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">{{ $item['quote'] }}</textarea>
                    <input name="testimonials[{{ $index }}][rating]" value="{{ $item['rating'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-extrabold text-slate-900">الأسئلة الشائعة</h3>
            <div class="grid w-full max-w-xl gap-3 md:grid-cols-2">
                <input name="faqHeading" value="{{ $content['faqHeading'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <input name="faqSubheading" value="{{ $content['faqSubheading'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            </div>
        </div>
        <div class="mt-6 grid gap-4 xl:grid-cols-2">
            @foreach ($content['faqs'] as $index => $faq)
                <div class="rounded-[26px] bg-slate-50 p-4">
                    <input name="faqs[{{ $index }}][question]" value="{{ $faq['question'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <textarea name="faqs[{{ $index }}][answer]" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">{{ $faq['answer'] }}</textarea>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">تطبيق التاجر والنداء الختامي</h3>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-[28px] bg-slate-50 p-5">
                <input name="appSection[title]" value="{{ $content['appSection']['title'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                <textarea name="appSection[description]" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">{{ $content['appSection']['description'] }}</textarea>
                <div class="mt-3 grid gap-3">
                    <input name="appSection[image]" value="{{ $content['appSection']['image'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <input name="appSection[google_label]" value="{{ $content['appSection']['google_label'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                    <input name="appSection[appstore_label]" value="{{ $content['appSection']['appstore_label'] }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                </div>
            </div>
            <div class="rounded-[28px] bg-slate-50 p-5">
                <textarea name="ctaSection[title]" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">{{ $content['ctaSection']['title'] }}</textarea>
                <input name="ctaSection[button_label]" value="{{ $content['ctaSection']['button_label'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                <input name="ctaSection[button_href]" value="{{ $content['ctaSection']['button_href'] }}" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
            </div>
        </div>
    </section>

    <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">الفوتر</h3>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <input name="footer[about_title]" value="{{ $content['footer']['about_title'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <input name="footer[links_title]" value="{{ $content['footer']['links_title'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <input name="footer[contact_title]" value="{{ $content['footer']['contact_title'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <input name="footer[contact_description]" value="{{ $content['footer']['contact_description'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <input name="footer[commercial_register]" value="{{ $content['footer']['commercial_register'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <input name="footer[tax_number]" value="{{ $content['footer']['tax_number'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <input name="footer[copyright]" value="{{ $content['footer']['copyright'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm md:col-span-2">
            <textarea name="footer[about_links]" rows="6" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">{{ implode(PHP_EOL, $content['footer']['about_links']) }}</textarea>
            <textarea name="footer[links]" rows="6" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">{{ implode(PHP_EOL, $content['footer']['links']) }}</textarea>
        </div>
    </section>
</form>
@endsection