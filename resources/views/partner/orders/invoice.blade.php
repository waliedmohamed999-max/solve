<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة {{ $order['order_number'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{font-family:Tahoma,Arial,sans-serif}@media print{.no-print{display:none}}</style>
</head>
<body class="bg-slate-100 p-6 text-slate-950">
    <main class="mx-auto max-w-3xl rounded-2xl bg-white p-8 shadow">
        <div class="flex items-start justify-between border-b pb-6">
            <div>
                <h1 class="text-3xl font-black">فاتورة</h1>
                <p class="mt-2 text-sm font-bold text-slate-500">{{ $order['order_number'] }}</p>
            </div>
            <div class="text-left">
                <p class="text-xl font-black">Solve</p>
                <p class="mt-1 text-sm font-bold text-slate-500">{{ $partner['name'] }}</p>
                <p class="text-xs font-bold text-slate-400">{{ $partner['store_id'] }}</p>
            </div>
        </div>

        <section class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <h2 class="font-black">العميل</h2>
                <p class="mt-2 text-sm font-bold">{{ $order['customer'] }}</p>
                <p class="text-sm text-slate-500">{{ $order['phone'] }}</p>
            </div>
            <div>
                <h2 class="font-black">تفاصيل الطلب</h2>
                <p class="mt-2 text-sm font-bold">الحالة: {{ $order['status'] }}</p>
                <p class="text-sm text-slate-500">الدفع: {{ $order['payment_status'] }}</p>
                <p class="text-sm text-slate-500">التاريخ: {{ $order['created_at'] }}</p>
            </div>
        </section>

        <table class="mt-8 min-w-full divide-y text-right text-sm">
            <thead><tr class="bg-slate-50"><th class="px-4 py-3">المنتج</th><th class="px-4 py-3">الكمية</th><th class="px-4 py-3">السعر</th></tr></thead>
            <tbody class="divide-y">
                @foreach ($order['items'] as $item)
                    <tr><td class="px-4 py-3 font-bold">{{ $item['name'] ?? '-' }}</td><td class="px-4 py-3">{{ $item['qty'] ?? 1 }}</td><td class="px-4 py-3">{{ $item['price'] ?? $order['total'] }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-8 flex justify-end">
            <div class="w-64 rounded-2xl bg-slate-50 p-4">
                <div class="flex justify-between text-sm font-bold"><span>الإجمالي</span><span>{{ $order['total'] }}</span></div>
                <div class="mt-2 flex justify-between text-sm font-bold"><span>الضريبة</span><span>مشمول</span></div>
                <div class="mt-4 border-t pt-4 flex justify-between text-lg font-black"><span>المطلوب</span><span>{{ $order['total'] }}</span></div>
            </div>
        </div>

        <button onclick="window.print()" class="no-print mt-8 rounded-full bg-solve-700 px-6 py-3 text-sm font-black text-white">طباعة</button>
    </main>
</body>
</html>
