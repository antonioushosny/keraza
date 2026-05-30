<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مهرجان الكرازة</title>

    {{-- Favicon & Icons --}}
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="shortcut icon" href="/icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="msapplication-TileImage" content="/icon-192.png">
    <meta name="msapplication-TileColor" content="#1a1a2e">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="/manifest.json">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN Fallback -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Cairo', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    @if(request()->is('admin/*'))
        @livewireStyles
    @endif
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        [x-cloak] { display: none !important; }

        /* Site-wide logo header */
        .site-logo-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            border-bottom: 3px solid #c9a84c;
            box-shadow: 0 2px 20px rgba(201, 168, 76, 0.25);
        }
        .site-logo-header img {
            filter: drop-shadow(0 2px 8px rgba(201,168,76,0.4));
            transition: transform 0.3s ease;
        }
        .site-logo-header img:hover {
            transform: scale(1.05) rotate(-1deg);
        }
        .site-logo-title {
            background: linear-gradient(180deg, #f6d365 0%, #c9a84c 50%, #f6d365 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="antialiased">

    {{-- Logo Header (hidden on admin pages which have their own Filament sidebar) --}}
    @if(!request()->is('admin/*') && !request()->is('admin'))
    <header class="site-logo-header">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-center gap-4">
            <img src="/icon.png" alt="شعار مهرجان الكرازة" class="w-14 h-14 rounded-full object-cover">
            <div class="text-center">
                <h1 class="site-logo-title text-xl font-black leading-tight">مهرجان الكرازة المرقسية</h1>
                <p class="text-amber-200/70 text-xs font-semibold mt-0.5">يعظم انتصارنا بالذي أحبنا</p>
            </div>
            <img src="/icon.png" alt="شعار مهرجان الكرازة" class="w-14 h-14 rounded-full object-cover">
        </div>
    </header>
    @endif

    <div class="min-h-screen">
        @if(request()->is('admin/*'))
            @livewireScripts
        @endif
        {{ $slot ?? $__env->yieldContent('content') }}
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            // Force fetch latest sw.js by appending a timestamp to bypass HTTP cache
            navigator.serviceWorker.register('/sw.js?v=' + new Date().getTime()).then((reg) => {
                reg.update();
            });
            
            // Forcibly clear old caches from previous broken SW
            caches.keys().then(function(names) {
                for (let name of names) {
                    if(name === 'keraza-store-v2') {
                        caches.delete(name);
                    }
                }
            });
        }
    </script>
</body>
</html>
