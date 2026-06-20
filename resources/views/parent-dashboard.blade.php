@php
    $isE3dady = request()->is('e3dady') || request()->is('e3dady/*');
    $routePrefix = $isE3dady ? 'e3dady.' : '';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة تحكم ولي الأمر - مهرجان الكرازة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            letter-spacing: normal !important;
        }
        html {
            width: 100%;
            overflow-x: hidden;
        }
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8fafc;
            background-image: radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.05) 0px, transparent 50%),
                              radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.03) 0px, transparent 50%);
            background-attachment: fixed;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        .student-card {
            background: white;
            border-radius: 28px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        .score-badge {
            background: linear-gradient(135deg, #c9a84c, #f6d365);
            color: #1a1a2e;
        }
        /* Scrollbar hiding */
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }

        /* ── Mobile dropdown selector (< 768px) ── */
        .tab-dropdown {
            display: none;
            width: 100%;
            box-sizing: border-box;
            position: relative;
        }
        .tab-dropdown-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 14px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: border-color 0.2s;
            text-align: right;
        }
        .tab-dropdown-trigger:focus { outline: none; border-color: #f59e0b; }
        .tab-dropdown-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            left: 0;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            z-index: 100;
            box-shadow: 0 8px 24px rgba(0,0,0,0.10);
        }
        .tab-dropdown-menu button {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 16px;
            background: transparent;
            border: none;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            text-align: right;
            transition: background 0.15s;
            border-bottom: 1px solid #f3f4f6;
        }
        .tab-dropdown-menu button:last-child { border-bottom: none; }
        .tab-dropdown-menu button:hover { background: #fef3c7; }
        .tab-dropdown-menu button.active {
            background: #fff7ed;
            color: #b45309;
            font-weight: 700;
        }
        .tab-dropdown-menu button.active::before {
            content: '✓';
            margin-left: 8px;
            color: #f59e0b;
            font-weight: 900;
        }

        /* ── Desktop horizontal pill tabs (≥ 768px) ── */
        .tabs-desktop {
            display: none;
            gap: 4px;
            padding: 6px;
            background: rgba(243, 244, 246, 0.8);
            border-radius: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        .tabs-desktop button {
            flex: 1;
            white-space: nowrap;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Cairo', sans-serif;
            color: #6b7280;
        }
        .tabs-desktop button.active {
            background: white;
            color: #111827;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .tabs-desktop button:not(.active):hover { color: #111827; }

        @media (max-width: 767px) {
            .tab-dropdown  { display: block; }
            .tabs-desktop  { display: none !important; }
        }
        @media (min-width: 768px) {
            .tab-dropdown  { display: none !important; }
            .tabs-desktop  { display: flex; }
        }
        /* Details section never causes overflow */
        .details-section {
            width: 100%;
            max-width: 100%;
            overflow: visible;
            box-sizing: border-box;
        }
        /* Child name: truncate with ellipsis rather than expanding */
        .child-name {
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        /* Hide Alpine elements until Alpine initializes */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen pb-20" style="width:100%; max-width:100%; overflow-x:hidden;">
        {{-- Header --}}
        <div class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-100/80 px-4 py-3 sticky top-0 z-50">
            <div class="max-w-4xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <img src="/icon.png" class="w-10 h-10 rounded-full object-cover border border-amber-500/30 shadow-sm" alt="Logo">
                    <h1 class="text-sm sm:text-base font-black text-gray-800">كنيسة العذراء مريم المطرية</h1>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route($routePrefix . 'parent.profile') }}" class="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-amber-700 bg-amber-50 hover:bg-amber-100/80 active:scale-95 transition-all px-3 py-2 rounded-2xl border border-amber-200/40 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="hidden sm:inline">حسابي</span>
                    </a>
                    <form action="{{ route($routePrefix . 'logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100/80 active:scale-95 transition-all px-3 py-2 rounded-2xl border border-red-200/40 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span class="hidden sm:inline">خروج</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto p-4 sm:p-6" style="min-width:0; width:100%; box-sizing:border-box;">
            {{-- Welcome --}}
            <div class="mb-6">
                <h2 class="text-xl sm:text-2xl font-black text-gray-900 tracking-normal flex items-center gap-2">
                    أهلاً بك، {{ auth()->user()->name }} 
                    <span class="animate-bounce inline-block origin-bottom-right">👋</span>
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                    تابع أداء ومستوى أولادك في مهرجان الكرازة {{ $season?->name }}
                </p>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">✅</span>
                        <span class="font-bold text-sm">{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">❌</span>
                        <span class="font-bold text-sm">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if(empty($childrenData))
                <div class="bg-white rounded-3xl p-12 text-center shadow-sm">
                    <div class="text-6xl mb-4">🏠</div>
                    <h3 class="text-xl font-bold text-gray-800">لا يوجد أبناء مسجلين</h3>
                    <p class="text-gray-500 mt-2">يرجى التواصل مع إدارة المهرجان لربط أبنائك بحسابك.</p>
                </div>
            @else
                <div class="grid gap-6" style="width:100%; min-width:0; box-sizing:border-box;">
                    @foreach($childrenData as $data)
                        @php
                            $child = $data['student'];
                            $enrollment = $data['enrollment'];
                            $rankInfo = $data['ranking_info'];
                            $rank = $data['rank_position'];
                        @endphp
                        
                        <div class="student-card shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100/80 p-0 overflow-hidden relative transition-all duration-300 hover:shadow-[0_12px_40px_rgb(0,0,0,0.04)] mb-6" x-data="{ open: false }">
                            <div class="p-6 sm:p-8" style="background: linear-gradient(to left, rgba(245,158,11,0.08), transparent); border-bottom: 1px solid rgba(156,163,175,0.12); width:100%; box-sizing:border-box;">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 justify-between">
                                    <div class="flex flex-row items-center gap-4 min-w-0 flex-grow">
                                        {{-- Avatar --}}
                                        <div style="position:relative; flex-shrink:0;">
                                            <form action="{{ route($routePrefix . 'parent.student.upload-image', $child->id) }}" method="POST" enctype="multipart/form-data" id="upload-form-{{ $child->id }}">
                                                @csrf
                                                <label class="cursor-pointer block relative group" style="display:block; position:relative;">
                                                    <input type="file" name="profile_image" accept="image/*" class="hidden" onchange="document.getElementById('upload-form-{{ $child->id }}').submit()">
                                                    @if($child->profile_image)
                                                        <img src="/storage/{{ $child->profile_image }}" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover shadow-lg" style="border: 2px solid #f59e0b;" alt="{{ $child->full_name }}">
                                                    @else
                                                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center text-2xl sm:text-3xl font-black shadow-lg"
                                                             style="background: linear-gradient(135deg, #fde68a, #fbbf24); color: #92400e; border: 2px solid #f59e0b;">
                                                            {{ mb_substr($child->full_name, 0, 1) }}
                                                        </div>
                                                    @endif
                                                    <div class="absolute inset-0 bg-black/40 rounded-2xl opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center text-white text-[10px] font-bold">تعديل</div>
                                                    <div style="position:absolute; bottom:-6px; left:-6px; background:#f59e0b; color:white; padding:4px; border-radius:8px; border:2px solid white;">
                                                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"></path></svg>
                                                    </div>
                                                </label>
                                            </form>
                                            @if($rank <= 3 && $rank !== null)
                                                <div style="position:absolute; top:-10px; right:-10px; background:white; font-size:16px; padding:4px; border-radius:50%; box-shadow:0 2px 8px rgba(0,0,0,0.15); border:1px solid #fef3c7; z-index:10;" class="animate-bounce" style="animation-duration:3s;">
                                                    {{ $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉') }}
                                                </div>
                                            @endif
                                        </div>
                                        {{-- Name + Badges --}}
                                        <div class="min-w-0">
                                            <h2 class="text-base sm:text-2xl font-black text-gray-950">{{ $child->full_name }}</h2>
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-600 text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-xl border border-blue-100/55">
                                                    📚 {{ $enrollment?->class?->name ?? 'غير مسجل' }}
                                                </span>
                                                @if($rank)
                                                    <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-600 text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-xl border border-amber-100/55">
                                                        🏆 الترتيب: {{ $rank }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Score Badge --}}
                                    <div class="flex-shrink-0 text-white rounded-2xl px-5 py-3 text-center shadow-lg sm:mr-auto w-full sm:w-auto mt-3 sm:mt-0"
                                         style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                        <div class="text-2xl font-black">{{ $rankInfo['score'] ?? 0 }}%</div>
                                        <div class="text-[10px] font-bold uppercase mt-0.5" style="color: rgba(255,255,255,0.8);">المعدل</div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 sm:p-7">
                                @if($enrollment)
                                {{-- Quick Stats --}}
                                <div class="grid grid-cols-2 {{ $settings->show_attendance_percentage ? 'sm:grid-cols-4' : 'sm:grid-cols-3' }} gap-3 mb-5">
                                    @if($settings->show_attendance_percentage)
                                    <div class="bg-gradient-to-br from-emerald-50/70 to-emerald-50/30 rounded-2xl p-3.5 text-center border border-emerald-100/50 shadow-[0_4px_20px_rgba(16,185,129,0.02)] transition hover:scale-[1.02]">
                                        <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-emerald-700 mb-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"></path>
                                            </svg>
                                            الحضور
                                        </div>
                                        <div class="text-lg font-black text-emerald-800">{{ round($rankInfo['data']['breakdown']['attendance'] ?? 0) }}%</div>
                                    </div>
                                    @endif
                                    <div class="bg-gradient-to-br from-indigo-50/70 to-indigo-50/30 rounded-2xl p-3.5 text-center border border-indigo-100/50 shadow-[0_4px_20px_rgba(99,102,241,0.02)] transition hover:scale-[1.02]">
                                        <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-indigo-700 mb-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path>
                                            </svg>
                                            الامتحانات
                                        </div>
                                        <div class="text-lg font-black text-indigo-800">{{ round($rankInfo['data']['breakdown']['exams'] ?? 0) }}%</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-amber-50/70 to-amber-50/30 rounded-2xl p-3.5 text-center border border-amber-100/50 shadow-[0_4px_20px_rgba(245,158,11,0.02)] transition hover:scale-[1.02]">
                                        <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-amber-700 mb-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"></path>
                                            </svg>
                                            التسميع
                                        </div>
                                        <div class="text-lg font-black text-amber-800">{{ round($rankInfo['data']['breakdown']['memorization'] ?? 0) }}%</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-sky-50/70 to-sky-50/30 rounded-2xl p-3.5 text-center border border-sky-100/50 shadow-[0_4px_20px_rgba(14,165,233,0.02)] transition hover:scale-[1.02]">
                                        <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-sky-700 mb-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.64 8.38m6 .59a14.98 14.98 0 01-3 1.82m-3-1.82a14.98 14.98 0 01-3 1.82M9.64 8.38a14.98 14.98 0 00-6.16 12.12A14.98 14.98 0 0015.6 14.37m-5.84-6a6 6 0 01-7.38 5.84h4.8m1.16-5.84a14.98 14.98 0 00-6.16 12.12"></path>
                                            </svg>
                                            الأنشطة
                                        </div>
                                        <div class="text-lg font-black text-sky-800">{{ round($rankInfo['data']['breakdown']['activities'] ?? 0) }}%</div>
                                    </div>
                                </div>

                                {{-- Badges --}}
                                @if(!empty($rankInfo['badges']))
                                    <div class="mb-5">
                                        <div class="text-xs font-bold text-gray-400 mb-2.5 uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-1 h-3 rounded-full bg-amber-500"></span>
                                            الأوسمة والبادجات
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($rankInfo['badges'] as $badge)
                                                <div class="bg-gradient-to-r from-amber-50/80 to-amber-100/40 border border-amber-200/50 px-3 py-1.5 rounded-2xl flex items-center gap-2 shadow-[0_2px_10px_rgba(245,158,11,0.03)] hover:scale-105 transition-transform duration-200">
                                                    <span class="text-lg filter drop-shadow-sm">{{ $badge['icon'] }}</span>
                                                    <span class="text-xs font-bold text-amber-900">{{ $badge['title'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Behavior --}}
                                @php
                                    $positiveLogs = $enrollment->behaviorLogs->where('type', 'positive');
                                    $negativeLogs = $enrollment->behaviorLogs->where('type', 'negative');
                                @endphp
                                @if($positiveLogs->count() > 0 || $negativeLogs->count() > 0)
                                    <div class="mb-5">
                                        <div class="text-xs font-bold text-gray-400 mb-2.5 uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-1 h-3 rounded-full bg-emerald-500"></span>
                                            سجل السلوك الأخير
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($positiveLogs->take(2) as $log)
                                                <div class="bg-emerald-50/40 border border-emerald-100/50 p-2.5 rounded-2xl flex items-center gap-2.5 shadow-[0_2px_10px_rgba(16,185,129,0.02)]">
                                                    <span class="text-emerald-500 text-sm">✨</span>
                                                    <span class="text-xs font-bold text-emerald-800/90 leading-relaxed">{{ $log->reason }}</span>
                                                    <span class="text-xs font-extrabold text-emerald-600 mr-auto bg-emerald-100/50 px-2 py-0.5 rounded-lg">+{{ $log->points }}</span>
                                                </div>
                                            @endforeach
                                            @foreach($negativeLogs->take(2) as $log)
                                                <div class="bg-rose-50/40 border border-rose-100/50 p-2.5 rounded-2xl flex items-center gap-2.5 shadow-[0_2px_10px_rgba(244,63,94,0.02)]">
                                                    <span class="text-rose-500 text-sm">⚠️</span>
                                                    <span class="text-xs font-bold text-rose-800/90 leading-relaxed">{{ $log->reason }}</span>
                                                    <span class="text-xs font-extrabold text-rose-600 mr-auto bg-rose-100/50 px-2 py-0.5 rounded-lg">-{{ abs($log->points) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <button @click="open = !open" class="w-full py-3.5 bg-gray-50/80 hover:bg-gray-100 border border-gray-100 hover:border-gray-200/80 text-gray-700 font-bold rounded-2xl transition active:scale-[0.98] duration-200 flex items-center justify-center gap-2 mt-4">
                                    <span class="text-sm" x-text="open ? 'إخفاء التفاصيل' : 'عرض كافة التفاصيل'"></span>
                                    <svg x-show="!open" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                                    <svg x-show="open" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"></path></svg>
                                </button>

                                <div x-show="open" x-collapse class="details-section mt-6 space-y-6 pt-6 border-t border-dashed border-gray-200" x-data="{ activeTab: 'attendance' }">
                                     {{-- Unified Pill Tabs --}}
                                     <div class="bg-white border border-gray-200/60 rounded-2xl p-2 shadow-sm">
                                         <div class="flex flex-wrap gap-1">
                                             @php
                                                 $tabs = [
                                                     'attendance' => '📅 الحضور والغياب',
                                                     'exams' => '📝 الامتحانات',
                                                     'memorization' => '📖 المحفوظات',
                                                     'activities' => '🎯 الأنشطة',
                                                     'behavior' => '✨ السلوك والملاحظات',
                                                 ];
                                             @endphp
                                             @foreach($tabs as $tabKey => $tabLabel)
                                                 <button type="button"
                                                         @click="activeTab = '{{ $tabKey }}'"
                                                         :class="activeTab === '{{ $tabKey }}' ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                                                         :style="activeTab === '{{ $tabKey }}' ? 'background: #f59e0b;' : 'background: rgba(156,163,175,0.08);'"
                                                         class="px-4 py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all whitespace-nowrap border-none cursor-pointer">
                                                     {{ $tabLabel }}
                                                 </button>
                                             @endforeach
                                         </div>
                                     </div>

                                     {{-- Tab contents --}}
                                     
                                     {{-- Attendance Tab --}}
                                     <div x-show="activeTab === 'attendance'" class="space-y-4">
                                         <div class="px-2 py-1">
                                             <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                                                 <span class="w-1.5 h-4 rounded-full bg-emerald-500"></span>
                                                 سجل الحضور والغياب التفصيلي:
                                             </h4>
                                         </div>
                                         @if($enrollment->attendance->count() > 0)
                                             <div class="bg-white border border-gray-200/60 rounded-2xl overflow-hidden shadow-sm">
                                                 @foreach($enrollment->attendance->sortByDesc('session.date') as $att)
                                                     @php
                                                         $statusConfig = match($att->status) {
                                                             'present' => ['label' => 'حاضر', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)', 'border' => 'rgba(16,185,129,0.2)'],
                                                             'excused' => ['label' => 'معتذر', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)', 'border' => 'rgba(245,158,11,0.2)'],
                                                             default => ['label' => 'غائب', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)', 'border' => 'rgba(239,68,68,0.2)'],
                                                         };
                                                     @endphp
                                                     <div class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition" style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                                         <div class="flex items-center gap-3">
                                                             <div class="w-2 h-2 rounded-full" style="background: {{ $statusConfig['color'] }};"></div>
                                                             <span class="text-sm font-bold text-gray-800">{{ $att->session?->date ?? 'غير محدد' }}</span>
                                                             @if($att->notes)
                                                                 <span class="text-xs text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-100/50">📌 {{ $att->notes }}</span>
                                                             @endif
                                                         </div>
                                                         <span class="text-xs font-black px-3 py-1 rounded-lg"
                                                               style="background: {{ $statusConfig['bg'] }}; border: 1px solid {{ $statusConfig['border'] }}; color: {{ $statusConfig['color'] }};">
                                                             {{ $statusConfig['label'] }}
                                                         </span>
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-8 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200 text-gray-400 text-xs">لا يوجد سجل حضور حتى الآن.</div>
                                         @endif
                                     </div>

                                     {{-- Exams Tab --}}
                                     <div x-show="activeTab === 'exams'" class="space-y-4">
                                         <div class="px-2 py-1">
                                             <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                                                 <span class="w-1.5 h-4 rounded-full bg-indigo-500"></span>
                                                 درجات الامتحانات التفصيلية:
                                             </h4>
                                         </div>
                                         @if($enrollment->examScores->count() > 0)
                                             <div class="bg-white border border-gray-200/60 rounded-2xl overflow-hidden shadow-sm">
                                                 @foreach($enrollment->examScores as $score)
                                                     @php
                                                         $percent = $score->exam?->total_score > 0 ? ($score->score / $score->exam->total_score) * 100 : 0;
                                                         $barGradient = $percent >= 90
                                                             ? 'linear-gradient(90deg, #10b981, #14b8a6)'
                                                             : ($percent >= 50
                                                                 ? 'linear-gradient(90deg, #f59e0b, #ea580c)'
                                                                 : 'linear-gradient(90deg, #ef4444, #dc2626)');
                                                         $textColor = $percent >= 90 ? '#10b981' : ($percent >= 50 ? '#f59e0b' : '#ef4444');
                                                     @endphp
                                                     <div class="px-5 py-4 hover:bg-gray-50 transition" style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                                         <div class="flex items-center justify-between mb-2">
                                                             <div>
                                                                 <span class="text-sm font-bold text-gray-800">{{ $score->exam?->title }}</span>
                                                                 @if($score->exam?->date)
                                                                     <span class="text-xs text-gray-400 mr-2">{{ $score->exam->date }}</span>
                                                                 @endif
                                                             </div>
                                                             <span class="text-sm font-black" style="color: {{ $textColor }};">{{ $score->score }} / {{ $score->exam?->total_score }}</span>
                                                         </div>
                                                         
                                                         <div style="width: 100%; height: 8px; border-radius: 9999px; background: rgba(156,163,175,0.15); overflow: hidden;">
                                                             <div style="width: {{ round($percent) }}%; height: 100%; border-radius: 9999px; background: {{ $barGradient }}; transition: width 0.5s ease-in-out;"></div>
                                                         </div>
                                                         
                                                         @if($score->notes)
                                                             <div class="text-xs text-amber-700 font-bold bg-amber-50 px-2.5 py-1 rounded-lg mt-2 inline-block border border-amber-100/50">📌 {{ $score->notes }}</div>
                                                         @endif
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-8 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200 text-gray-400 text-xs">لا يوجد درجات امتحانات مسجلة.</div>
                                         @endif
                                     </div>

                                     {{-- Memorization Tab --}}
                                     <div x-show="activeTab === 'memorization'" class="space-y-4">
                                         <div class="px-2 py-1">
                                             <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                                                 <span class="w-1.5 h-4 rounded-full bg-amber-500"></span>
                                                 سجل المحفوظات والتسميع التفصيلي:
                                             </h4>
                                         </div>
                                         @if($enrollment->memorizationScores->count() > 0)
                                             <div class="bg-white border border-gray-200/60 rounded-2xl overflow-hidden shadow-sm">
                                                 @foreach($enrollment->memorizationScores as $memo)
                                                     @php
                                                         $maxPoints = $memo->memorizationItem?->max_points ?: 100;
                                                         $mPercent = $maxPoints > 0 ? ($memo->score / $maxPoints) * 100 : 0;
                                                         $mBarGradient = $mPercent >= 90
                                                             ? 'linear-gradient(90deg, #10b981, #14b8a6)'
                                                             : ($mPercent >= 50
                                                                 ? 'linear-gradient(90deg, #f59e0b, #ea580c)'
                                                                 : 'linear-gradient(90deg, #ef4444, #dc2626)');
                                                         $mTextColor = $mPercent >= 90 ? '#10b981' : ($mPercent >= 50 ? '#f59e0b' : '#ef4444');
                                                     @endphp
                                                     <div class="px-5 py-4 hover:bg-gray-50 transition" style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                                         <div class="flex items-center justify-between mb-2">
                                                             <span class="text-sm font-bold text-gray-800">{{ $memo->memorizationItem?->title ?? 'محفوظة' }}</span>
                                                             <span class="text-sm font-black" style="color: {{ $mTextColor }};">{{ $memo->score }} / {{ $maxPoints }}</span>
                                                         </div>
                                                         
                                                         <div style="width: 100%; height: 8px; border-radius: 9999px; background: rgba(156,163,175,0.15); overflow: hidden;">
                                                             <div style="width: {{ round($mPercent) }}%; height: 100%; border-radius: 9999px; background: {{ $mBarGradient }}; transition: width 0.5s ease-in-out;"></div>
                                                         </div>
                                                         
                                                         @if($memo->notes)
                                                             <div class="text-xs text-amber-700 font-bold bg-amber-50 px-2.5 py-1 rounded-lg mt-2 inline-block border border-amber-100/50">📌 {{ $memo->notes }}</div>
                                                         @endif
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-8 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200 text-gray-400 text-xs">لا يوجد محفوظات مسجلة بعد.</div>
                                         @endif
                                     </div>

                                     {{-- Activities Tab --}}
                                     <div x-show="activeTab === 'activities'" class="space-y-4">
                                         <div class="px-2 py-1">
                                             <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                                                 <span class="w-1.5 h-4 rounded-full bg-sky-500"></span>
                                                 الأنشطة والمسابقات المشترك بها الطفل:
                                             </h4>
                                         </div>
                                         @if($enrollment->activityEnrollments->count() > 0)
                                             <div class="bg-white border border-gray-200/60 rounded-2xl overflow-hidden shadow-sm">
                                                 @foreach($enrollment->activityEnrollments as $actEnroll)
                                                     @php
                                                         $isQualified = $actEnroll->status === 'qualified';
                                                         $statusBadge = $isQualified ? 'background: rgba(16,185,129,0.1); color: #10b981;' : 'background: rgba(14,165,233,0.1); color: #0ea5e9;';
                                                         $scoreVal = $actEnroll->scores->avg('score') ?? 0;
                                                     @endphp
                                                     <div class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition" style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                                         <div class="flex items-center gap-2.5">
                                                             <span class="text-sm font-bold text-gray-800">{{ $actEnroll->activity?->title }}</span>
                                                             <span class="text-[10px] font-black px-2 py-0.5 rounded-lg" style="{{ $statusBadge }}">
                                                                 {{ $isQualified ? 'مؤهل' : 'مشترك' }}
                                                             </span>
                                                         </div>
                                                         <div class="flex items-center gap-2">
                                                             @if($scoreVal > 0)
                                                                 <span class="text-sm font-black text-amber-600">{{ round($scoreVal) }}%</span>
                                                             @else
                                                                 <span class="text-xs text-gray-400">لم يتم التقييم</span>
                                                             @endif
                                                         </div>
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-8 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200 text-gray-400 text-xs">الطفل غير مشترك في أي أنشطة بعد.</div>
                                         @endif
                                     </div>

                                     {{-- Behavior & Remarks Tab --}}
                                     <div x-show="activeTab === 'behavior'" class="space-y-4">
                                         <div class="px-2 py-1">
                                             <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                                                 <span class="w-1.5 h-4 rounded-full bg-emerald-500"></span>
                                                 سجل النقاط والملاحظات السلوكية:
                                             </h4>
                                         </div>
                                         @if($enrollment->behaviorLogs->count() > 0)
                                             <div class="bg-white border border-gray-200/60 rounded-2xl overflow-hidden shadow-sm">
                                                 @foreach($enrollment->behaviorLogs->sortByDesc('created_at') as $log)
                                                     @php
                                                         $isPos = $log->type === 'positive';
                                                         $logBg = $isPos ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
                                                         $logColor = $isPos ? '#10b981' : '#ef4444';
                                                     @endphp
                                                     <div class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition" style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                                         <div class="flex items-center gap-2.5">
                                                             <span class="text-sm">{{ $isPos ? '✨' : '⚠️' }}</span>
                                                             <div>
                                                                 <span class="text-sm font-bold text-gray-800">{{ $log->reason }}</span>
                                                                 <span class="text-[10px] text-gray-400 mr-2">{{ $log->created_at->format('Y-m-d') }}</span>
                                                             </div>
                                                         </div>
                                                         <span class="text-xs font-black px-2.5 py-1 rounded-lg" style="background: {{ $logBg }}; color: {{ $logColor }};">
                                                             {{ $isPos ? '+' : '-' }}{{ abs($log->points) }} نقطة
                                                         </span>
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-8 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200 text-gray-400 text-xs">سجل السلوك نظيف ولا توجد ملاحظات سلوكية.</div>
                                         @endif

                                         <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/40 border border-blue-100/50 p-4 rounded-2xl shadow-sm flex items-start gap-3 mt-4">
                                             <div class="bg-white p-2 rounded-xl shadow-sm text-blue-600 flex-shrink-0">
                                                 <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                                 </svg>
                                             </div>
                                             <div>
                                                 <h4 class="font-extrabold text-blue-800 text-sm">💡 نصيحة للتشجيع</h4>
                                                 <p class="text-xs text-blue-700 mt-1 leading-relaxed">ابنك متفوق جداً في {{ $rankInfo['data']['breakdown']['exams'] > 90 ? 'الامتحانات' : ($settings->show_attendance_percentage ? 'الحضور' : 'الامتحانات') }}، شجعه على الاستمرار وتطوير مستواه في باقي المجالات!</p>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                            @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>
</html>
