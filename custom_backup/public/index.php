<?php

$stats = [
    ['label' => 'إجمالي المتاجر', 'value' => '12,480', 'change' => '+18.4%', 'tone' => 'primary'],
    ['label' => 'المتاجر النشطة', 'value' => '10,932', 'change' => '+12.1%', 'tone' => 'success'],
    ['label' => 'المتاجر المتوقفة', 'value' => '274', 'change' => '-4.3%', 'tone' => 'danger'],
    ['label' => 'التجار الجدد', 'value' => '486', 'change' => '+21.8%', 'tone' => 'info'],
    ['label' => 'إجمالي الطلبات', 'value' => '248K', 'change' => '+16.7%', 'tone' => 'primary'],
    ['label' => 'الإيرادات الشهرية', 'value' => '3.84M ر.س', 'change' => '+9.2%', 'tone' => 'success'],
    ['label' => 'معدل النمو', 'value' => '24.6%', 'change' => '+3.1%', 'tone' => 'info'],
    ['label' => 'تذاكر الدعم المفتوحة', 'value' => '92', 'change' => '-11.5%', 'tone' => 'warning'],
];

$stores = [
    ['name' => 'متجر أطلس', 'owner' => 'سارة الحربي', 'status' => 'نشط', 'plan' => 'Enterprise', 'sales' => '418,200 ر.س', 'orders' => '2,418', 'created_at' => '15 يناير 2026'],
    ['name' => 'أبعاد بيوتي', 'owner' => 'هدى القحطاني', 'status' => 'معلق', 'plan' => 'Growth', 'sales' => '126,540 ر.س', 'orders' => '1,109', 'created_at' => '09 فبراير 2026'],
    ['name' => 'رواء هوم', 'owner' => 'محمد الشهري', 'status' => 'نشط', 'plan' => 'Starter', 'sales' => '88,200 ر.س', 'orders' => '704', 'created_at' => '21 ديسمبر 2025'],
    ['name' => 'شهد بوتيك', 'owner' => 'نوف العتيبي', 'status' => 'مراجعة', 'plan' => 'Growth', 'sales' => '213,900 ر.س', 'orders' => '1,680', 'created_at' => '03 مارس 2026'],
];

$toneMap = [
    'primary' => 'bg-brand-50 text-brand-600',
    'success' => 'bg-emerald-50 text-emerald-600',
    'danger' => 'bg-rose-50 text-rose-600',
    'info' => 'bg-sky-50 text-sky-600',
    'warning' => 'bg-amber-50 text-amber-600',
];

$menu = ['الرئيسية', 'المتاجر', 'الاشتراكات', 'المدفوعات', 'الشحن', 'التحليلات', 'خدمات الشركاء', 'الدعم الفني', 'التطبيقات', 'الإعدادات'];
$icons = ['⌂', '▣', '◫', '◌', '◭', '◔', '✦', '◎', '◍', '⚙'];
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solve Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Tajawal', 'sans-serif'] }, colors: { brand: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81' } }, boxShadow: { soft: '0 20px 50px rgba(79, 70, 229, 0.08)', card: '0 10px 30px rgba(15, 23, 42, 0.06)' } } } };
    </script>
    <style>
        body { font-family: 'Tajawal', sans-serif; background: radial-gradient(circle at top left, rgba(99,102,241,.08), transparent 32%), radial-gradient(circle at bottom right, rgba(56,189,248,.08), transparent 24%), #f8fafc; }
        .grid-pattern { background-image: linear-gradient(rgba(99,102,241,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(99,102,241,.05) 1px, transparent 1px); background-size: 28px 28px; }
    </style>
</head>
<body class="text-slate-800">
<div class="min-h-screen p-4 lg:p-6" x-data="{ mobileNav: false }">
    <div class="mx-auto flex max-w-[1800px] gap-4 lg:gap-6">
        <aside class="fixed inset-y-4 right-4 z-40 w-[300px] rounded-[32px] border border-white/60 bg-white/90 p-5 shadow-soft backdrop-blur-xl lg:static lg:block" :class="mobileNav ? 'block' : 'hidden lg:block'">
            <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-xl font-extrabold text-white shadow-card">S</div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-500">Solve</p>
                            <h1 class="text-2xl font-extrabold text-slate-900">Admin Hub</h1>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-500">مركز إدارة عمليات المنصة، المتاجر، الاشتراكات، الدعم والتحليلات.</p>
                </div>
                <button class="rounded-2xl border border-slate-200 p-2 text-slate-500 lg:hidden" @click="mobileNav = false">✕</button>
            </div>
            <nav class="mt-6 space-y-2 text-sm font-bold">
                <?php foreach ($menu as $index => $item): ?>
                    <a href="#section-<?= $index ?>" class="<?= $index === 0 ? 'bg-brand-600 text-white shadow-card' : 'text-slate-600 hover:bg-slate-50' ?> flex items-center justify-between rounded-2xl px-4 py-3 transition">
                        <span><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="<?= $index === 0 ? 'bg-white/20 text-white' : 'bg-brand-50 text-brand-600' ?> flex h-10 w-10 items-center justify-center rounded-xl text-base"><?= $icons[$index] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <main class="min-w-0 flex-1">
            <header class="grid-pattern sticky top-4 z-30 rounded-[32px] border border-white/60 bg-white/85 p-4 shadow-card backdrop-blur-xl">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center gap-3">
                        <button class="rounded-2xl border border-slate-200 p-3 text-slate-600 lg:hidden" @click="mobileNav = true">☰</button>
                        <div class="relative w-full max-w-xl"><span class="absolute inset-y-0 right-4 flex items-center text-slate-400">⌕</span><input type="text" placeholder="ابحث عن متجر، تذكرة، دفعة أو تقرير" class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pr-11 pl-4 text-sm outline-none"></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 shadow-sm">العربية | EN</button>
                        <button class="relative rounded-2xl border border-slate-200 bg-white p-3 text-slate-600 shadow-sm">🔔<span class="absolute left-2 top-2 h-2.5 w-2.5 rounded-full bg-rose-500"></span></button>
                    </div>
                </div>
            </header>
            <section class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                <div class="overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-brand-900 to-brand-600 p-7 text-white shadow-soft">
                    <div class="flex flex-col gap-8 xl:flex-row xl:items-center xl:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-bold text-brand-100">Solve Platform Control Center</p>
                            <h2 class="mt-4 text-3xl font-extrabold leading-[1.6] lg:text-5xl">لوحة تحكم احترافية لإدارة المنصة، حل المشاكل، ودفع نمو المتاجر.</h2>
                            <p class="mt-4 max-w-xl text-sm leading-8 text-slate-200 lg:text-base">واجهة SaaS عربية نظيفة ومستقرة للمعاينة السريعة.</p>
                        </div>
                        <div class="relative mx-auto w-full max-w-sm"><img src="/منصة_متاجر.png" alt="منصة متاجر" class="relative mx-auto w-full max-w-xs drop-shadow-2xl"></div>
                    </div>
                </div>
                <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <p class="text-sm font-bold text-brand-600">ملخص اليوم</p>
                    <h3 class="mt-2 text-2xl font-extrabold text-slate-900">صحة التشغيل العامة</h3>
                    <div class="mt-6 space-y-4">
                        <div class="rounded-3xl bg-slate-50 p-4"><div class="flex items-center justify-between text-sm text-slate-500"><span>توفر المنصة</span><span>99.98%</span></div><div class="mt-3 h-2 rounded-full bg-slate-200"><div class="h-2 w-[99%] rounded-full bg-gradient-to-l from-emerald-400 to-brand-500"></div></div></div>
                        <div class="rounded-3xl bg-slate-50 p-4"><div class="flex items-center justify-between text-sm text-slate-500"><span>أداء المدفوعات</span><span>97.6%</span></div><div class="mt-3 h-2 rounded-full bg-slate-200"><div class="h-2 w-[97%] rounded-full bg-gradient-to-l from-sky-400 to-brand-500"></div></div></div>
                    </div>
                </div>
            </section>
            <section class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                <?php foreach ($stats as $i => $stat): ?>
                    <div class="rounded-[28px] border border-white/70 bg-white p-5 shadow-card"><div class="flex items-start justify-between gap-3"><div><p class="text-sm font-bold text-slate-500"><?= $stat['label'] ?></p><p class="mt-4 text-3xl font-extrabold text-slate-900"><?= $stat['value'] ?></p></div><span class="rounded-2xl px-3 py-2 text-sm font-bold <?= $toneMap[$stat['tone']] ?>"><?= $stat['change'] ?></span></div><div class="mt-5 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-gradient-to-l from-brand-400 to-sky-400" style="width: <?= 62 + ($i * 4) ?>%"></div></div></div>
                <?php endforeach; ?>
            </section>
            <section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"><div><p class="text-sm font-bold text-brand-600">إدارة المتاجر</p><h3 class="mt-2 text-2xl font-extrabold text-slate-900">قاعدة بيانات المتاجر</h3></div></div>
                <div class="mt-6 overflow-x-auto"><table class="min-w-full text-right text-sm"><thead><tr class="text-slate-400"><th class="px-4 py-4">اسم المتجر</th><th class="px-4 py-4">اسم التاجر</th><th class="px-4 py-4">الحالة</th><th class="px-4 py-4">الباقة</th><th class="px-4 py-4">المبيعات</th></tr></thead><tbody><?php foreach ($stores as $store): ?><tr class="border-t border-slate-100"><td class="px-4 py-5 font-extrabold text-slate-900"><?= $store['name'] ?></td><td class="px-4 py-5 text-slate-600"><?= $store['owner'] ?></td><td class="px-4 py-5"><span class="rounded-full px-3 py-2 text-xs font-bold <?= $store['status'] === 'نشط' ? 'bg-emerald-50 text-emerald-600' : ($store['status'] === 'معلق' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600') ?>"><?= $store['status'] ?></span></td><td class="px-4 py-5 text-slate-600"><?= $store['plan'] ?></td><td class="px-4 py-5 text-slate-600"><?= $store['sales'] ?></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
        </main>
    </div>
</div>
</body>
</html>