<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Solve')</title>
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
                            50: '#eef0ff', 100: '#e4e8ff', 200: '#cfd5ff', 300: '#afb7ff',
                            400: '#8d92f8', 500: '#6b6fd6', 600: '#5b5fca', 700: '#4d51b4',
                            800: '#45489a', 900: '#3f4180'
                        }
                    },
                    boxShadow: {
                        soft: '0 20px 60px rgba(91, 95, 202, 0.10)',
                        card: '0 16px 45px rgba(15, 23, 42, 0.06)'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background:
                radial-gradient(circle at top right, rgba(107, 111, 214, 0.08), transparent 24%),
                radial-gradient(circle at 20% 15%, rgba(56, 189, 248, 0.06), transparent 20%),
                #fbfbfe;
        }
        .section-glow::before {
            content: '';
            position: absolute;
            inset: auto;
            width: 260px;
            height: 260px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(107, 111, 214, 0.14), rgba(107, 111, 214, 0));
            filter: blur(8px);
            z-index: 0;
        }
    </style>
</head>
<body class="text-slate-700">
    @yield('content')
</body>
</html>