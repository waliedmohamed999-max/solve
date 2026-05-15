<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice['number'] }} | Solve Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #f8fafc; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="text-slate-800">
    <main class="mx-auto max-w-5xl p-6 lg:p-10">
        <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.payments') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700">{{ json_decode('"\u0627\u0644\u0639\u0648\u062F\u0629 \u0625\u0644\u0649 \u0627\u0644\u0645\u062F\u0641\u0648\u0639\u0627\u062A"') }}</a>
            <div class="flex gap-3">
                <button onclick="window.print()" class="rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white">{{ json_decode('"\u0637\u0628\u0627\u0639\u0629 \u0627\u0644\u0641\u0627\u062A\u0648\u0631\u0629"') }}</button>
                <button onclick="window.print()" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700">{{ json_decode('"\u062D\u0641\u0638 PDF"') }}</button>
            </div>
        </div>

        <section class="overflow-hidden rounded-[36px] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
            <div class="grid gap-0 lg:grid-cols-[1.2fr,0.8fr]">
                <div class="p-8 lg:p-10">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-6">
                        <div>
                            <p class="text-sm font-bold text-brand-600">Solve Finance Desk</p>
                            <h1 class="mt-3 text-3xl font-extrabold text-slate-900">{{ json_decode('"\u0641\u0627\u062A\u0648\u0631\u0629 \u0645\u0627\u0644\u064A\u0629"') }}</h1>
                            <p class="mt-3 text-sm text-slate-500">{{ $invoice['number'] }}</p>
                        </div>
                        <span class="rounded-full bg-slate-900 px-4 py-2 text-xs font-bold text-white">{{ $invoice['status'] }}</span>
                    </div>

                    <div class="mt-8 grid gap-4 md:grid-cols-2">
                        <div class="rounded-[24px] bg-slate-50 p-5">
                            <p class="text-xs font-bold text-slate-400">{{ json_decode('"\u0627\u0644\u0639\u0645\u064A\u0644"') }}</p>
                            <p class="mt-2 text-lg font-extrabold text-slate-900">{{ $invoice['customer'] }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $invoice['customer_email'] }}</p>
                        </div>
                        <div class="rounded-[24px] bg-slate-50 p-5">
                            <p class="text-xs font-bold text-slate-400">Merchant / Gateway</p>
                            <p class="mt-2 text-lg font-extrabold text-slate-900">{{ $invoice['gateway'] }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $invoice['merchant_id'] }}</p>
                        </div>
                    </div>

                    <div class="mt-8 overflow-hidden rounded-[28px] border border-slate-200">
                        <div class="grid grid-cols-2 bg-slate-50 text-sm font-bold text-slate-500">
                            <div class="px-5 py-4">{{ json_decode('"\u0627\u0644\u0628\u0646\u062F"') }}</div>
                            <div class="px-5 py-4 text-left">{{ json_decode('"\u0627\u0644\u0642\u064A\u0645\u0629"') }}</div>
                        </div>
                        <div class="divide-y divide-slate-200">
                            <div class="grid grid-cols-2 px-5 py-4 text-sm">
                                <div>{{ json_decode('"\u0642\u064A\u0645\u0629 \u0627\u0644\u0641\u0627\u062A\u0648\u0631\u0629"') }}</div>
                                <div class="text-left font-extrabold text-slate-900">{{ $invoice['amount'] }}</div>
                            </div>
                            <div class="grid grid-cols-2 px-5 py-4 text-sm">
                                <div>{{ json_decode('"\u0627\u0644\u0636\u0631\u064A\u0628\u0629"') }}</div>
                                <div class="text-left font-extrabold text-slate-900">{{ $invoice['tax'] }}</div>
                            </div>
                            <div class="grid grid-cols-2 px-5 py-4 text-sm">
                                <div>{{ json_decode('"\u062A\u0627\u0631\u064A\u062E \u0627\u0644\u0627\u0633\u062A\u062D\u0642\u0627\u0642"') }}</div>
                                <div class="text-left font-extrabold text-slate-900">{{ $invoice['due_date'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="bg-slate-950 p-8 text-white lg:p-10">
                    <p class="text-sm font-bold text-slate-300">{{ json_decode('"\u0627\u0644\u0628\u0627\u0631\u0643\u0648\u062F \u0627\u0644\u0645\u0627\u0644\u064A"') }}</p>
                    <div class="mt-6 overflow-hidden rounded-[28px] bg-white p-4">{!! $invoice['barcode_svg'] !!}</div>
                    <div class="mt-6 rounded-[24px] bg-white/5 p-5 text-sm text-slate-300">
                        <p>{{ json_decode('"\u064A\u0645\u0643\u0646 \u0627\u0633\u062A\u062E\u062F\u0627\u0645 \u0647\u0630\u0647 \u0627\u0644\u0635\u0641\u062D\u0629 \u0644\u0644\u0637\u0628\u0627\u0639\u0629 \u0627\u0644\u0645\u0628\u0627\u0634\u0631\u0629 \u0623\u0648 \u0627\u0644\u062D\u0641\u0638 \u0628\u0635\u064A\u063A\u0629 PDF \u0645\u0646 \u0627\u0644\u0645\u062A\u0635\u0641\u062D."') }}</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>
</body>
</html>
