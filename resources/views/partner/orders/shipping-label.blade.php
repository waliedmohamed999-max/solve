<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بوليصة شحن {{ $order['order_number'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 p-6 text-slate-950 print:bg-white">
    <main class="mx-auto max-w-2xl rounded-2xl bg-white p-8 shadow-card print:shadow-none">
        <div class="flex items-start justify-between border-b border-slate-200 pb-6">
            <div>
                <p class="text-sm font-black text-slate-400">Solve Shipping Label</p>
                <h1 class="mt-2 text-3xl font-black">بوليصة الشحن</h1>
                <p class="mt-2 text-sm font-bold text-slate-500">{{ $partner['store_id'] }}</p>
            </div>
            <button onclick="window.print()" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white print:hidden">طباعة</button>
        </div>

        <section class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-black text-slate-400">رقم الطلب</p>
                <p class="mt-2 text-xl font-black">{{ $order['order_number'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-black text-slate-400">الشحن</p>
                <p class="mt-2 text-xl font-black">{{ $order['shipping_method'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-black text-slate-400">المستلم</p>
                <p class="mt-2 font-black">{{ $order['customer'] }}</p>
                <p class="mt-1 text-sm font-bold text-slate-500">{{ $order['phone'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-black text-slate-400">العنوان</p>
                <p class="mt-2 font-black">{{ $order['city'] ?? '-' }}</p>
                <p class="mt-1 text-sm font-bold text-slate-500">{{ $order['address'] ?? '-' }}</p>
            </div>
        </section>

        <div class="mt-8 rounded-2xl border border-dashed border-slate-300 p-6 text-center">
            <p class="text-xs font-black text-slate-400">Tracking</p>
            <p class="mt-2 text-3xl font-black tracking-widest">{{ $order['id'] }}</p>
            <div class="mx-auto mt-5 h-16 max-w-md bg-[repeating-linear-gradient(90deg,#0f172a_0,#0f172a_3px,transparent_3px,transparent_8px)]"></div>
        </div>
    </main>
</body>
</html>
