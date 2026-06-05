<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة الشرف - {{ $seasonName }}</title>
    <meta name="description" content="لوحة شرف مهرجان الكرازة - ترتيب المخدومين المتميزين">

    {{-- Favicon & Icons --}}
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="shortcut icon" href="/icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#1a1a2e">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Cairo', sans-serif; }
        [x-cloak] { display: none !important; }

        body {
            background: #f8f8f8;
            min-height: 100vh;
        }

        .honor-board {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            border: 8px solid #c9a84c;
            box-shadow:
                0 0 0 2px #1a1a2e,
                0 0 0 10px #b8860b,
                0 0 40px rgba(201, 168, 76, 0.3),
                inset 0 0 80px rgba(201, 168, 76, 0.05);
        }

        .gold-text {
            background: linear-gradient(180deg, #f6d365 0%, #c9a84c 50%, #f6d365 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .rank-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .rank-card:hover {
            transform: translateY(-4px) scale(1.02);
        }

        .top-1-card {
            background: linear-gradient(135deg, rgba(255,215,0,0.15) 0%, rgba(255,215,0,0.05) 100%);
            border: 2px solid rgba(255,215,0,0.4);
            box-shadow: 0 0 30px rgba(255,215,0,0.15);
        }
        .top-2-card {
            background: linear-gradient(135deg, rgba(192,192,192,0.12) 0%, rgba(192,192,192,0.04) 100%);
            border: 2px solid rgba(192,192,192,0.3);
            box-shadow: 0 0 20px rgba(192,192,192,0.1);
        }
        .top-3-card {
            background: linear-gradient(135deg, rgba(205,127,50,0.12) 0%, rgba(205,127,50,0.04) 100%);
            border: 2px solid rgba(205,127,50,0.3);
            box-shadow: 0 0 20px rgba(205,127,50,0.1);
        }
        .normal-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .avatar-ring-1 { box-shadow: 0 0 0 3px #ffd700, 0 0 15px rgba(255,215,0,0.5); }
        .avatar-ring-2 { box-shadow: 0 0 0 3px #c0c0c0, 0 0 15px rgba(192,192,192,0.4); }
        .avatar-ring-3 { box-shadow: 0 0 0 3px #cd7f32, 0 0 15px rgba(205,127,50,0.4); }

        .badge-chip {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.15);
            transition: all 0.2s ease;
        }
        .badge-chip:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }

        .score-bar {
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            border-radius: 999px;
            transition: width 1s ease-out;
        }

        .confetti {
            position: fixed;
            pointer-events: none;
            z-index: 50;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        .shimmer {
            background: linear-gradient(90deg,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.1) 50%,
                rgba(255,255,255,0) 100%);
            background-size: 200% 100%;
            animation: shimmer 3s infinite;
        }

        @keyframes sparkle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .sparkle { animation: sparkle 2s ease-in-out infinite; }

        .class-btn {
            transition: all 0.3s ease;
        }
        .class-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(201, 168, 76, 0.3);
        }
        .class-btn.active {
            background: linear-gradient(135deg, #c9a84c, #f6d365);
            color: #1a1a2e;
            box-shadow: 0 4px 20px rgba(201, 168, 76, 0.4);
        }

        .star-decoration {
            position: absolute;
            color: rgba(201, 168, 76, 0.15);
            font-size: 1.5rem;
            pointer-events: none;
        }
    </style>
</head>
<body class="antialiased">

    <div class="min-h-screen py-6 px-2 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">

            {{-- Top Action Bar --}}
            <div class="flex justify-between items-center mb-6">
                <a href="/admin" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-gray-700 shadow-sm">
                    🔒 لوحة التحكم الإدارية
                </a>
                <a href="{{ route('parent.dashboard') }}" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-amber-400 shadow-sm">
                    👤 حساب ولي الأمر
                </a>
            </div>

            {{-- Honor Board Frame --}}
            <div class="honor-board rounded-2xl overflow-hidden relative">

                {{-- Logo Header directly on top of the board --}}
                <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%); border-bottom: 3px solid #c9a84c; box-shadow: 0 2px 20px rgba(201,168,76,0.25);">
                    <div style="padding: 14px 20px; display: flex; align-items: center; justify-content: center; gap: 16px;">
                        <img src="/icon.png" alt="شعار مهرجان الكرازة"
                             style="width: 58px; height: 58px; border-radius: 50%; object-fit: cover; filter: drop-shadow(0 2px 8px rgba(201,168,76,0.5));">
                        <div style="text-align: center;">
                            <div style="background: linear-gradient(180deg, #f6d365 0%, #c9a84c 50%, #f6d365 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 1.2rem; font-weight: 900; line-height: 1.3;">مهرجان الكرازة المرقسية</div>
                            <div style="color: #ffd700; font-size: 0.85rem; font-weight: 700; margin-top: 2px;">كنيسة العذراء مريم المطرية</div>
                            <div style="color: rgba(251,191,36,0.75); font-size: 0.72rem; font-weight: 600; margin-top: 3px; letter-spacing: 0.03em;">يعظم انتصارنا بالذي أحبنا</div>
                        </div>
                        <img src="/icon.png" alt="شعار مهرجان الكرازة"
                             style="width: 58px; height: 58px; border-radius: 50%; object-fit: cover; filter: drop-shadow(0 2px 8px rgba(201,168,76,0.5));">
                    </div>
                </div>

                <div class="p-3 sm:p-10 relative">

                {{-- Decorative corner stars --}}
                <div class="star-decoration top-4 right-4 text-2xl sparkle">✦</div>
                <div class="star-decoration top-4 left-4 text-2xl sparkle" style="animation-delay: 0.5s">✦</div>
                <div class="star-decoration bottom-4 right-4 text-xl sparkle" style="animation-delay: 1s">✦</div>
                <div class="star-decoration bottom-4 left-4 text-xl sparkle" style="animation-delay: 1.5s">✦</div>

                {{-- Shimmer overlay --}}
                <div class="absolute inset-0 shimmer rounded-2xl pointer-events-none"></div>

                {{-- Header --}}
                <div class="text-center mb-8 relative">
                    <div class="text-4xl mb-3 float-animation">🏆</div>
                    <h1 class="text-3xl sm:text-4xl font-black gold-text mb-2">لوحة الشرف</h1>
                    <p class="text-base text-amber-200/70 font-semibold">{{ $seasonName }}</p>
                    @if($currentClass)
                        <div class="mt-3 inline-block px-4 py-1.5 rounded-full bg-amber-500/20 border border-amber-500/30">
                            <span class="text-amber-300 font-bold text-sm">📚 {{ $currentClass->name }}</span>
                        </div>
                    @endif
                </div>

                {{-- Class Filter Buttons --}}
                @if($classes->count() > 0)
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    <a href="{{ route('rankings', ['season_id' => $seasonId]) }}"
                       class="class-btn px-4 py-2 rounded-xl text-sm font-bold border border-amber-500/30 {{ !$classId ? 'active' : 'text-amber-300/70 hover:text-amber-200' }}">
                        الكل
                    </a>
                    @foreach($classes as $class)
                        <a href="{{ route('rankings', ['season_id' => $seasonId, 'class_id' => $class->id]) }}"
                           class="class-btn px-4 py-2 rounded-xl text-sm font-bold border border-amber-500/30 {{ $classId == $class->id ? 'active' : 'text-amber-300/70 hover:text-amber-200' }}">
                            {{ $class->name }}
                        </a>
                    @endforeach
                </div>
                @endif

                {{-- Rankings List --}}
                @if($rankings->count() > 0)
                <div class="space-y-3 relative">
                    @foreach($rankings as $index => $rank)
                        @php
                            $position = $index + 1;
                            $isTop3 = $position <= 3;
                            $cardClass = match($position) {
                                1 => 'top-1-card',
                                2 => 'top-2-card',
                                3 => 'top-3-card',
                                default => 'normal-card',
                            };
                            $avatarRing = match($position) {
                                1 => 'avatar-ring-1',
                                2 => 'avatar-ring-2',
                                3 => 'avatar-ring-3',
                                default => '',
                            };
                            $medal = match($position) {
                                1 => '🥇',
                                2 => '🥈',
                                3 => '🥉',
                                default => null,
                            };
                            $maxScore = $rankings->max('score') ?: 1;
                            $barWidth = round(($rank['score'] / $maxScore) * 100);
                            $initials = mb_substr($rank['student_name'], 0, 2);
                        @endphp

                        <div class="rank-card {{ $cardClass }} rounded-2xl p-3 sm:p-5 flex items-center gap-2 sm:gap-4"
                             style="animation-delay: {{ $index * 0.05 }}s">

                            {{-- Rank Number / Medal --}}
                            <div class="flex-shrink-0 w-8 sm:w-10 text-center">
                                @if($medal)
                                    <span class="text-xl sm:text-2xl {{ $position === 1 ? 'float-animation' : '' }}">{{ $medal }}</span>
                                @else
                                    <span class="text-base sm:text-lg font-black text-white/40">#{{ $position }}</span>
                                @endif
                            </div>

                            {{-- Avatar --}}
                            <div class="flex-shrink-0">
                                @if($rank['profile_image'])
                                    <img src="{{ asset('storage/' . $rank['profile_image']) }}"
                                         alt="{{ $rank['student_name'] }}"
                                         class="w-10 h-10 sm:w-14 sm:h-14 rounded-full object-cover {{ $avatarRing }}">
                                @else
                                    <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-full flex items-center justify-center text-sm sm:text-lg font-bold {{ $avatarRing }}"
                                         style="background: linear-gradient(135deg,
                                            hsl({{ ($index * 47) % 360 }}, 70%, 60%),
                                            hsl({{ (($index * 47) + 40) % 360 }}, 70%, 50%));">
                                        <span class="text-white drop-shadow">{{ $initials }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Student Info --}}
                            <div class="flex-grow min-w-0">
                                <h3 class="font-bold text-white text-sm sm:text-base md:text-lg truncate">{{ $rank['student_name'] }}</h3>

                                {{-- Score bar --}}
                                <div class="mt-1.5 flex items-center gap-2">
                                    <div class="flex-grow h-2 bg-white/10 rounded-full overflow-hidden">
                                        <div class="score-bar h-full" style="width: {{ $barWidth }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold {{ $isTop3 ? 'text-amber-300' : 'text-white/60' }} whitespace-nowrap">
                                        {{ number_format($rank['score'], 1) }}
                                    </span>
                                </div>

                                {{-- Badges --}}
                                @if(!empty($rank['badges']))
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @foreach($rank['badges'] as $badge)
                                            <span class="badge-chip inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs text-white/80"
                                                  title="{{ $badge['title'] }}">
                                                <span>{{ $badge['icon'] }}</span>
                                                <span class="hidden sm:inline">{{ $badge['title'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Stars for top 5 --}}
                            @if($position <= 5)
                                <div class="flex-shrink-0 text-lg sparkle" style="animation-delay: {{ $index * 0.3 }}s">
                                    ⭐
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @else
                    {{-- Empty State --}}
                    <div class="text-center py-16">
                        <div class="text-5xl mb-4">📋</div>
                        <p class="text-amber-200/60 text-lg font-semibold">لا يوجد بيانات لعرضها</p>
                        <p class="text-amber-200/40 text-sm mt-2">اختر فصل لعرض ترتيب المخدومين</p>
                    </div>
                @endif

                {{-- Footer --}}
                <div class="mt-8 text-center">
                    <div class="inline-flex items-center gap-2 text-amber-200/30 text-xs">
                        <span>✝</span>
                        <span>مهرجان الكرازة</span>
                        <span>-</span>
                        <span>كنيسة العذراء مريم المطرية</span>
                        <span>✝</span>
                    </div>
                </div>
                </div> {{-- end p-6 content wrapper --}}
            </div>
        </div>
    </div>
    
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?v=' + new Date().getTime()).then((reg) => {
                reg.update();
            });
            caches.keys().then(function(names) {
                for (let name of names) {
                    if(name === 'keraza-store-v2') caches.delete(name);
                }
            });
        }
    </script>
</body>
</html>
