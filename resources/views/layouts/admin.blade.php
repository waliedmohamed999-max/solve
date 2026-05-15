<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Solve Admin')</title>
    <link rel="icon" type="image/png" href="{{ asset('solve-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Tajawal', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8',
                            500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81'
                        }
                    },
                    boxShadow: {
                        soft: '0 20px 50px rgba(79,70,229,0.08)',
                        card: '0 10px 30px rgba(15,23,42,0.06)'
                    }
                }
            }
        };
    </script>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: #f8fafc;
        }
        .grid-pattern {
            background-image: linear-gradient(rgba(99,102,241,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(99,102,241,0.05) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="font-sans max-w-full overflow-x-hidden text-slate-800">
    <div class="min-h-screen p-4 transition lg:p-6" x-data="{ mobileNav: false, quickOpen: false, darkMode: false }" :class="darkMode ? 'bg-slate-950 text-slate-100' : ''">
        <div class="mx-auto max-w-[1800px]">
            @include('admin.partials.sidebar', ['activeRoute' => $activeRoute ?? 'admin.dashboard'])
            <main class="min-w-0 lg:mr-[254px]">
                @include('admin.partials.topbar')
                @if ($showInsight ?? false)
                    @include('admin.partials.insight-banner')
                @endif
                @yield('admin-content')
            </main>
        </div>
    </div>
</body>
</html>
