<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class SiteContent
{
    private const STORAGE_PATH = 'app/site-content.json';

    public static function get(): array
    {
        $defaults = self::defaults();
        $path = storage_path(self::STORAGE_PATH);

        if (! File::exists($path)) {
            return $defaults;
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            return $defaults;
        }

        return self::mergeWithDefaults($decoded, $defaults);
    }

    public static function put(array $content): void
    {
        $path = storage_path(self::STORAGE_PATH);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(self::mergeWithDefaults($content, self::defaults()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function defaults(): array
    {
        return [
            'navLinks' => [
                ['label' => 'الخدمات', 'href' => '#services'],
                ['label' => 'المنتجات', 'href' => '#catalog'],
                ['label' => 'تسجيل الدخول', 'href' => '/admin'],
            ],
            'hero' => [
                'title' => 'منصة Solve',
                'description' => 'تحوّل إلى التجارة الإلكترونية بسهولة وسرعة وامتلك متجر إلكتروني خاص بك يجمع مزايا التجارة الإلكترونية مع توفير الخدمات المساندة له.',
                'image' => 'منصة_متاجر.png',
                'primary_label' => 'ابدأ الآن',
                'primary_href' => '#services',
                'secondary_label' => 'لوحة التحكم',
                'secondary_href' => '/admin',
            ],
            'servicesHeading' => 'الخدمات الأساسية',
            'servicesSubheading' => 'كل ما يحتاجه متجرك للانطلاق من البداية حتى التشغيل والتحسين.',
            'serviceCards' => [
                ['title' => 'كتابة تعريف المتجر', 'icon' => '◫'],
                ['title' => 'تحسين التصنيفات', 'icon' => '▤'],
                ['title' => 'تصميم واجهة المتجر', 'icon' => '◌'],
                ['title' => 'هيكلة واجهة المتجر', 'icon' => '⌂'],
                ['title' => 'ربط خريطة Sitemap', 'icon' => '◎'],
                ['title' => 'ربط Google Analytics', 'icon' => '◔'],
                ['title' => 'تفعيل وحجز الدومين', 'icon' => '◍'],
                ['title' => 'تهيئة تقنية SEO', 'icon' => '⚙'],
            ],
            'featuresHeading' => 'ما يميز Solve',
            'featureSections' => [
                [
                    'title' => 'إدارة كل شيء بسهولة',
                    'description' => 'تستطيع من جوالك متابعة طلبات المتجر وعملاءك بكل سهولة وفي أي وقت وبخطوات بسيطة.',
                    'link' => 'اكتشف كيف تدير متجرك بسهولة',
                    'image' => 'ما يُميز متاجر.png',
                ],
                [
                    'title' => 'الدفع',
                    'description' => 'وفّر لعملائك خيارات متعددة للشراء من متجرك وقبلها بكل سهولة: مدى، Apple Pay، فيزا، ماستركارد.',
                    'link' => 'اكتشف المزيد عن الدفع الإلكتروني',
                    'image' => 'الدفع.png',
                ],
                [
                    'title' => 'الشحن',
                    'description' => 'استخدم خيارات شحن متنوعة مع عملائك وبسعر مخفّض خاص بمتاجر Solve والشحن عبر سمسا وأرامكس والبريد السعودي.',
                    'link' => 'اكتشف الخيارات والمزايا للشحن',
                    'image' => 'الشحن.png',
                ],
                [
                    'title' => 'خدمات الشركاء',
                    'description' => 'من خلال شركائنا وفرنا للتاجر نخبة من أفضل مقدمي الخدمات وبسعر خاص مثل التصميم والتغليف والتسويق والتصوير.',
                    'link' => 'تعرّف على خدمات الشركاء',
                    'image' => 'خدمات الشُركاء.png',
                ],
            ],
            'showcaseHeading' => 'أمثلة لمتاجر Solve',
            'showcaseStores' => [
                ['name' => 'عطر الفنادق', 'badge' => 'AF', 'tone' => 'bg-sky-500'],
                ['name' => 'Solar Free', 'badge' => 'SF', 'tone' => 'bg-amber-300 text-slate-900'],
                ['name' => 'شهد قيفت', 'badge' => 'SH', 'tone' => 'bg-violet-500'],
                ['name' => 'مدارس تالات', 'badge' => 'TS', 'tone' => 'bg-rose-400'],
            ],
            'catalogHeading' => 'خدمات ومنتجات Solve',
            'catalogSections' => [
                [
                    'title' => 'باقاتنا المميزة',
                    'items' => [
                        [
                            'slug' => 'tiktok-package',
                            'code' => 'TT',
                            'title' => 'باقة تيك توكش',
                            'subtitle' => 'تفعيل وإدارة الحملات على تيك توك',
                            'description' => 'باقة احترافية لإطلاق حملات تيك توك وتحسين الوصول وإدارة الميزانية الإعلانية.',
                            'price' => '3740 ر.س',
                            'price_note' => 'يبدأ من',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-fuchsia-500 via-brand-600 to-sky-500',
                            'panel' => 'from-[#1e1534] to-[#231f3f]',
                            'features' => ['إعداد الحملة', 'تحسين الجمهور', 'تقارير أسبوعية'],
                        ],
                        [
                            'slug' => 'seo-boost',
                            'code' => 'G',
                            'title' => 'تحسين ظهور المنتجات',
                            'subtitle' => 'على Google SEO وكتابة المنتجات',
                            'description' => 'رفع ظهور صفحات المتجر والمنتجات في محركات البحث مع تحسين المحتوى والوصف.',
                            'price' => '1799 ر.س',
                            'price_note' => 'عرض لفترة محدودة',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-sky-500 via-cyan-400 to-brand-500',
                            'panel' => 'from-[#1a1630] to-[#262240]',
                            'features' => ['SEO تقني', 'كتابة المنتجات', 'تحسين الأرشفة'],
                        ],
                        [
                            'slug' => 'whatsapp-premium',
                            'code' => 'WA',
                            'title' => 'خدمة ربط الواتساب',
                            'subtitle' => 'تفعيل الردود الذكية عبر واتساب',
                            'description' => 'تهيئة قنوات واتساب للمبيعات والدعم وربط الردود السريعة مع متجر Solve.',
                            'price' => '390 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-emerald-500 via-teal-400 to-brand-500',
                            'panel' => 'from-[#14192f] to-[#1f2442]',
                            'features' => ['ربط رسمي', 'ردود تلقائية', 'قوالب محادثات'],
                        ],
                        [
                            'slug' => 'flow-follow-up',
                            'code' => 'FL',
                            'title' => 'باقة أفلو',
                            'subtitle' => 'تشغيل وإدارة التسويق عبر الرسائل',
                            'description' => 'بناء تدفقات متابعة للعملاء ورفع معدل التحويل من عربات التسوق المهجورة.',
                            'price' => '1390 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-violet-500 via-brand-600 to-fuchsia-500',
                            'panel' => 'from-[#191631] to-[#242145]',
                            'features' => ['رسائل تذكير', 'تقسيم العملاء', 'تحليلات التحويل'],
                        ],
                        [
                            'slug' => 'consulting-pro',
                            'code' => 'Q',
                            'title' => 'باقة إي كونسلتينج برو',
                            'subtitle' => 'إدارة فنية لتطوير المتجر الإلكتروني',
                            'description' => 'استشارات تشغيلية وتقنية لتحسين الأداء، تنظيم المبيعات، وتشغيل الحملات.',
                            'price' => '3599 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => 'الأكثر مبيعاً',
                            'accent' => 'from-brand-500 via-fuchsia-500 to-sky-400',
                            'panel' => 'from-[#19162f] to-[#25233f]',
                            'features' => ['جلسات شهرية', 'تحسين التحويل', 'خطة تشغيل'],
                        ],
                    ],
                ],
                [
                    'title' => 'تاجر جديد',
                    'items' => [
                        [
                            'slug' => 'maroof-verification',
                            'code' => 'M',
                            'title' => 'خدمة مساندة',
                            'subtitle' => 'استخراج شهادة معروف للمتجر',
                            'description' => 'خدمة متخصصة لاستخراج شهادة معروف وتوثيق متجر Solve الرسمي.',
                            'price' => '149 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-emerald-400 via-teal-400 to-brand-500',
                            'panel' => 'from-[#17142f] to-[#231f43]',
                            'features' => ['تجهيز البيانات', 'رفع الطلب', 'المتابعة حتى التفعيل'],
                        ],
                        [
                            'slug' => 'work-license',
                            'code' => 'HR',
                            'title' => 'خدمة مساندة',
                            'subtitle' => 'استخراج وثيقة العمل الحر',
                            'description' => 'دعم كامل لإصدار وثيقة العمل الحر واستكمال متطلبات المتجر القانونية.',
                            'price' => '149 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-sky-500 via-brand-500 to-violet-500',
                            'panel' => 'from-[#17142f] to-[#241f43]',
                            'features' => ['تدقيق البيانات', 'تجهيز الطلب', 'متابعة الإصدار'],
                        ],
                        [
                            'slug' => 'brand-identity',
                            'code' => 'Q',
                            'title' => 'باقة الاسم الرنان',
                            'subtitle' => 'ابتكار اسم تجاري وهوية مميزة',
                            'description' => 'اقتراح أسماء تجارية، تصميم هوية أولية، وصياغة تموضع مناسب للمتجر.',
                            'price' => '879 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-fuchsia-500 via-rose-400 to-amber-300',
                            'panel' => 'from-[#1c1530] to-[#2a2344]',
                            'features' => ['3 اقتراحات اسم', 'موجه هوية', 'استخدامات الشعار'],
                        ],
                        [
                            'slug' => 'official-email',
                            'code' => 'ZOHO',
                            'title' => 'إنشاء وربط بريد إلكتروني',
                            'subtitle' => 'رسمي باسم متجرك',
                            'description' => 'إعداد بريد احترافي وربطه بالدومين الخاص بمتجرك مع إعدادات الإرسال والاستقبال.',
                            'price' => '149 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-brand-500 via-sky-500 to-cyan-400',
                            'panel' => 'from-[#15142d] to-[#221f40]',
                            'features' => ['إعداد الدومين', 'ربط MX', 'اختبار الإرسال'],
                        ],
                        [
                            'slug' => 'launch-store',
                            'code' => 'START',
                            'title' => 'باقة بونك | إطلاق وتجهيز المتاجر',
                            'subtitle' => 'المتخصصة',
                            'description' => 'باقة إطلاق كاملة لبناء متجر جديد، ضبط الإعدادات، وتجهيز الواجهة والبيانات الأساسية.',
                            'price' => '2399 ر.س',
                            'price_note' => '',
                            'old_price' => '',
                            'badge' => '',
                            'accent' => 'from-amber-400 via-brand-500 to-sky-500',
                            'panel' => 'from-[#17142d] to-[#211f3e]',
                            'features' => ['إعداد المتجر', 'ضبط الدفع والشحن', 'رفع المنتجات الأولية'],
                        ],
                    ],
                ],
            ],
            'testimonialsHeading' => 'آراء العملاء',
            'testimonialsSubheading' => 'قالوا عن خدماتنا',
            'testimonials' => [
                ['name' => 'rowa scarf', 'brand' => 'rowa scarf', 'quote' => 'عمل احترافي ودقة في المواعيد وتعامل راقٍ ومريح، الكل أعجبه المتجر شكراً لكم.', 'rating' => 5],
                ['name' => 'SWAV', 'brand' => 'SWAV', 'quote' => 'التقييم أكثر من رائع، دقة وإخلاص بالعمل، سرعة عالية بالتسليم، وحلول ومراجعة بعد الشغل.', 'rating' => 5],
                ['name' => 'ZIYA ABAYA', 'brand' => 'ZIYA', 'quote' => 'The best of the best! highly recommend! thank you for your patience and amazing services.', 'rating' => 5],
                ['name' => 'ZARI ABAYA', 'brand' => 'ZARI', 'quote' => 'مع قدوم صار كل شيء أسهل وأسرع، شغلهم جبار وسريعين ومرنين، أي أحد يبي يفتح متجر إلكتروني لا يفكر بغيرهم.', 'rating' => 5],
                ['name' => 'LENDA SWEETS', 'brand' => 'LENDA', 'quote' => 'جداً جداً أشكركم، حقيقي الخدمة من القلب وكان موقعي موفقهم وتواصل واهتمام في التفاصيل وكل شيء ماقصروا.', 'rating' => 5],
                ['name' => 'book club al jalees', 'brand' => 'AL JALEES', 'quote' => 'لو كنت تريد فتح متجر من أي نوع، أنصحك باختيار قدوم، فريق شغوف يسهل القرار والتنفيذ أدق.', 'rating' => 5],
            ],
            'faqHeading' => 'الأسئلة الشائعة',
            'faqSubheading' => 'إجابات على أكثر الاستفسارات شيوعاً حول خدماتنا',
            'faqs' => [
                ['question' => 'كم يستغرق تجهيز المتجر؟', 'answer' => 'تختلف المدة حسب الباقة المختارة، ولكن عادة ما تتراوح بين 3 إلى 7 أيام عمل للباقة التأسيسية، وقد تصل إلى 14 يوماً للباقات المتقدمة لضمان أعلى جودة.'],
                ['question' => 'هل تشمل الباقات رسوم اشتراك سنوية؟', 'answer' => 'لا، رسوم الاشتراك على المنصة منفصلة عن رسوم الخدمات والباقات. باقاتنا تغطي التصميم، الإعداد، والتسويق الذي نقدمه نحن.'],
                ['question' => 'هل تقدمون خدمات دعم فني بعد التسليم؟', 'answer' => 'نعم، نقدم دعماً فنياً مجانياً لمدة أسبوعين بعد تسليم المشروع للتأكد من أن كل شيء يعمل بسلاسة.'],
                ['question' => 'ماذا لو لم يعجبني التصميم؟', 'answer' => 'نحن نضمن رضاك التام، وتشمل جميع باقاتنا جولات تعديل حسب الباقة لضمان الوصول للنتيجة التي تطمح إليها.'],
            ],
            'appSection' => [
                'title' => 'تطبيق التاجر',
                'description' => 'من خلال تطبيق التاجر تقدر تدير متجرك بكل سهولة من جوالك.',
                'image' => 'تطبيق_متاجر.png',
                'google_label' => 'Google Play',
                'appstore_label' => 'App Store',
            ],
            'ctaSection' => [
                'title' => 'ركز على نمو تجارتك واترك لنا مهمة تشغيل متجرك الإلكتروني',
                'button_label' => 'ابدأ الآن',
                'button_href' => '#catalog',
            ],
            'footer' => [
                'about_title' => 'عن Solve',
                'about_links' => ['شركاؤنا', 'الأسئلة الشائعة', 'سياسة الخصوصية', 'الشروط والأحكام', 'آراء عملائنا', 'انضم لنا', 'تواصل معنا'],
                'links_title' => 'روابط',
                'links' => ['يونيتس', 'برنامج التسويق بالعمولة', 'تسجيل جديد', 'تسجيل الدخول', 'مركز المساعدة'],
                'contact_title' => 'تواصل معنا',
                'contact_description' => 'هوية Solve الرسمية عبر الموقع ولوحة الإدارة.',
                'commercial_register' => '4030324087',
                'tax_number' => '310311630500003',
                'copyright' => 'جميع الحقوق محفوظة لـ Solve © 2026',
            ],
        ];
    }

    private static function mergeWithDefaults(mixed $data, mixed $defaults): mixed
    {
        if (! is_array($defaults)) {
            return is_scalar($data) || $data === null ? $data : $defaults;
        }

        if (! is_array($data)) {
            return $defaults;
        }

        if (array_is_list($defaults)) {
            if ($defaults === []) {
                return $data;
            }

            $defaultItem = $defaults[0];

            return array_values(array_map(function ($item) use ($defaultItem) {
                if (is_array($defaultItem)) {
                    return self::mergeWithDefaults(is_array($item) ? $item : [], $defaultItem);
                }

                return is_scalar($item) || $item === null ? $item : $defaultItem;
            }, $data));
        }

        $merged = [];

        foreach ($defaults as $key => $value) {
            $merged[$key] = self::mergeWithDefaults($data[$key] ?? null, $value);
        }

        return $merged;
    }
}