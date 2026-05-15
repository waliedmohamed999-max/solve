<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Solve')</title>
    <link rel="icon" type="image/png" href="{{ asset('solve-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
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
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="text-slate-700 overflow-x-hidden max-w-full">
    @yield('content')
</body>
</html>
