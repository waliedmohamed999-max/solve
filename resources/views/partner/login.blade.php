<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول الشريك | Solve</title>
    <link rel="icon" type="image/png" href="{{ asset('solve-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100">
    <main class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-6 py-14">
        <section class="grid w-full overflow-hidden rounded-[32px] border border-white/10 bg-white/5 shadow-2xl shadow-slate-950/40 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="bg-gradient-to-br from-cyan-300 via-sky-500 to-brand-700 p-8 text-slate-950 sm:p-10 lg:p-14">
                <p class="text-sm font-extrabold uppercase tracking-[0.35em]">Solve Partner</p>
                <h1 class="mt-12 max-w-lg text-4xl font-extrabold leading-[1.5]">لوحة مستقلة لكل متجر.</h1>
                <p class="mt-6 max-w-lg text-lg leading-9 text-slate-900/80">
                    يدخل كل شريك بحسابه الخاص ليشاهد بيانات متجره فقط، مع عزل كامل للطلبات والمنتجات والعملاء والمدفوعات.
                </p>
                <div class="mt-12 grid gap-3 text-sm font-bold text-slate-900/80">
                    <span class="rounded-2xl bg-white/35 px-4 py-3 backdrop-blur-sm">بوابة دخول الشركاء والتجار</span>
                    <span class="rounded-2xl bg-white/35 px-4 py-3 backdrop-blur-sm">صلاحيات منفصلة للتاجر والموظفين</span>
                </div>
            </div>

            <div class="p-8 sm:p-10 lg:p-12">
                <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-sky-300">Partner Login</p>
                <h2 class="mt-4 text-3xl font-extrabold">تسجيل دخول الشريك</h2>
                <p class="mt-3 text-sm leading-7 text-slate-300">استخدم بيانات المستخدم المرتبطة بمتجرك.</p>

                <form method="POST" action="{{ route('partner.login.store') }}" class="mt-10 space-y-6">
                    @csrf

                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-300">اسم المستخدم</span>
                        <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-base text-white outline-none transition focus:border-sky-400" required>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-300">كلمة المرور</span>
                        <input type="password" name="password" autocomplete="current-password" class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-base text-white outline-none transition focus:border-sky-400" required>
                    </label>

                    @if ($errors->any())
                        <div class="rounded-2xl border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm font-bold text-rose-100">{{ $errors->first() }}</div>
                    @endif

                    <button type="submit" class="w-full rounded-2xl bg-sky-400 px-4 py-3 text-base font-extrabold text-slate-950 transition hover:bg-sky-300">دخول</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
