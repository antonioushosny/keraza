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
        body { font-family: 'Cairo', sans-serif; background: #f0f2f5; }
        .student-card {
            background: white;
            border-radius: 24px;
            transition: all 0.3s ease;
        }
        .score-badge {
            background: linear-gradient(135deg, #c9a84c, #f6d365);
            color: #1a1a2e;
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen pb-20">
        {{-- Header --}}
        <div class="bg-white shadow-sm border-b px-4 py-4 sticky top-0 z-50">
            <div class="max-w-4xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <img src="/icon.png" class="w-10 h-10 rounded-full" alt="Logo">
                    <h1 class="text-xl font-bold text-gray-800">حساب ولي الأمر</h1>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm font-bold text-red-500 bg-red-50 px-4 py-2 rounded-xl">خروج</button>
                </form>
            </div>
        </div>

        <div class="max-w-4xl mx-auto p-4 sm:p-6">
            {{-- Welcome --}}
            <div class="mb-8">
                <h2 class="text-2xl font-black text-gray-800">أهلاً بك، {{ auth()->user()->name }} 👋</h2>
                <p class="text-gray-500 mt-1">تابع أداء ومستوى أولادك في مهرجان الكرازة {{ $season?->name }}</p>
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
                <div class="grid gap-6">
                    @foreach($childrenData as $data)
                        @php
                            $child = $data['student'];
                            $enrollment = $data['enrollment'];
                            $rankInfo = $data['ranking_info'];
                            $rank = $data['rank_position'];
                        @endphp
                        
                        <div class="student-card shadow-sm border p-6" x-data="{ open: false }">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-4">
                                    <div class="relative group">
                                        <form action="{{ route('parent.student.upload-image', $child->id) }}" method="POST" enctype="multipart/form-data" id="upload-form-{{ $child->id }}">
                                            @csrf
                                            <label class="cursor-pointer block relative">
                                                <input type="file" name="profile_image" accept="image/*" class="hidden" onchange="document.getElementById('upload-form-{{ $child->id }}').submit()">
                                                
                                                @if($child->profile_image)
                                                    <img src="/storage/{{ $child->profile_image }}" class="w-16 h-16 rounded-2xl object-cover border-2 border-amber-500 shadow-sm group-hover:opacity-75 transition" alt="">
                                                @else
                                                    <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center text-2xl border-2 border-amber-500 group-hover:opacity-75 transition">
                                                        {{ mb_substr($child->full_name, 0, 1) }}
                                                    </div>
                                                @endif
                                                
                                                {{-- Hover camera overlay --}}
                                                <div class="absolute inset-0 bg-black/40 rounded-2xl opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-[10px] font-bold">
                                                    📷 تعديل
                                                </div>
                                            </label>
                                        </form>
                                        @if($rank <= 3 && $rank !== null)
                                            <div class="absolute -top-2 -right-2 bg-yellow-400 text-xs font-black p-1 rounded-full shadow-lg border border-white z-10">
                                                {{ $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-gray-800">{{ $child->full_name }}</h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="bg-blue-50 text-blue-600 text-xs font-bold px-2 py-1 rounded-lg">
                                                📚 {{ $enrollment?->class?->name ?? 'غير مسجل' }}
                                            </span>
                                            @if($rank)
                                                <span class="bg-amber-50 text-amber-600 text-xs font-bold px-2 py-1 rounded-lg">
                                                    🏆 الترتيب: {{ $rank }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-black text-amber-600">{{ $rankInfo['score'] ?? 0 }}%</div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">المعدل العام</div>
                                </div>
                            </div>

                            @if($enrollment)
                                {{-- Quick Stats --}}
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                                    <div class="bg-gray-50 rounded-2xl p-3 text-center border border-gray-100">
                                        <div class="text-sm font-bold text-gray-500 mb-1">📅 الحضور</div>
                                        <div class="text-lg font-black text-gray-800">{{ round($rankInfo['data']['breakdown']['attendance'] ?? 0) }}%</div>
                                    </div>
                                    <div class="bg-gray-50 rounded-2xl p-3 text-center border border-gray-100">
                                        <div class="text-sm font-bold text-gray-500 mb-1">📝 الامتحانات</div>
                                        <div class="text-lg font-black text-gray-800">{{ round($rankInfo['data']['breakdown']['exams'] ?? 0) }}%</div>
                                    </div>
                                    <div class="bg-gray-50 rounded-2xl p-3 text-center border border-gray-100">
                                        <div class="text-sm font-bold text-gray-500 mb-1">📖 التسميع</div>
                                        <div class="text-lg font-black text-gray-800">{{ round($rankInfo['data']['breakdown']['memorization'] ?? 0) }}%</div>
                                    </div>
                                    <div class="bg-gray-50 rounded-2xl p-3 text-center border border-gray-100">
                                        <div class="text-sm font-bold text-gray-500 mb-1">🎯 الأنشطة</div>
                                        <div class="text-lg font-black text-gray-800">{{ round($rankInfo['data']['breakdown']['activities'] ?? 0) }}%</div>
                                    </div>
                                </div>

                                {{-- Badges --}}
                                @if(!empty($rankInfo['badges']))
                                    <div class="mb-6">
                                        <div class="text-xs font-bold text-gray-400 mb-3 uppercase tracking-wider">الأوسمة والبادجات</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($rankInfo['badges'] as $badge)
                                                <div class="bg-amber-50 border border-amber-100 px-3 py-1.5 rounded-xl flex items-center gap-2">
                                                    <span class="text-lg">{{ $badge['icon'] }}</span>
                                                    <span class="text-xs font-bold text-amber-800">{{ $badge['title'] }}</span>
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
                                    <div class="mb-6">
                                        <div class="text-xs font-bold text-gray-400 mb-3 uppercase tracking-wider">سجل السلوك</div>
                                        <div class="space-y-2">
                                            @foreach($positiveLogs->take(2) as $log)
                                                <div class="bg-green-50 border border-green-100 p-2 rounded-xl flex items-center gap-2">
                                                    <span class="text-green-600">✨</span>
                                                    <span class="text-xs font-bold text-green-800">{{ $log->reason }}</span>
                                                    <span class="text-[10px] text-green-500 mr-auto">+{{ $log->points }}</span>
                                                </div>
                                            @endforeach
                                            @foreach($negativeLogs->take(2) as $log)
                                                <div class="bg-red-50 border border-red-100 p-2 rounded-xl flex items-center gap-2">
                                                    <span class="text-red-600">⚠️</span>
                                                    <span class="text-xs font-bold text-red-800">{{ $log->reason }}</span>
                                                    <span class="text-[10px] text-red-500 mr-auto">{{ $log->points }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <button @click="open = !open" class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition flex items-center justify-center gap-2">
                                    <span x-text="open ? 'إخفاء التفاصيل' : 'عرض كافة التفاصيل'"></span>
                                    <svg x-show="!open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    <svg x-show="open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                </button>

                                 <div x-show="open" x-collapse class="mt-6 space-y-6 pt-6 border-t border-dashed" x-data="{ activeTab: 'attendance' }">
                                     {{-- Custom elegant tab navigation --}}
                                     <div class="flex border-b overflow-x-auto gap-2 scrollbar-none pb-2">
                                         <button @click="activeTab = 'attendance'" :class="activeTab === 'attendance' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 text-xs sm:text-sm font-bold rounded-xl transition whitespace-nowrap">📅 الحضور والغياب</button>
                                         <button @click="activeTab = 'exams'" :class="activeTab === 'exams' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 text-xs sm:text-sm font-bold rounded-xl transition whitespace-nowrap">📝 الامتحانات</button>
                                         <button @click="activeTab = 'memorization'" :class="activeTab === 'memorization' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 text-xs sm:text-sm font-bold rounded-xl transition whitespace-nowrap">📖 المحفوظات</button>
                                         <button @click="activeTab = 'activities'" :class="activeTab === 'activities' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 text-xs sm:text-sm font-bold rounded-xl transition whitespace-nowrap">🎯 الأنشطة</button>
                                         <button @click="activeTab = 'behavior'" :class="activeTab === 'behavior' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 text-xs sm:text-sm font-bold rounded-xl transition whitespace-nowrap">✨ السلوك والملاحظات</button>
                                     </div>

                                     {{-- Tab contents --}}
                                     
                                     {{-- Attendance Tab --}}
                                     <div x-show="activeTab === 'attendance'" class="space-y-3">
                                         <h4 class="font-bold text-gray-700 text-sm">سجل الحضور والغياب التفصيلي:</h4>
                                         @if($enrollment->attendance->count() > 0)
                                             <div class="grid gap-2">
                                                 @foreach($enrollment->attendance->sortByDesc('session.date') as $att)
                                                     <div class="flex justify-between items-center bg-gray-50 border p-3 rounded-xl">
                                                         <div>
                                                             <div class="text-xs font-bold text-gray-400">اليوم الدراسي</div>
                                                             <div class="text-sm font-bold text-gray-700">{{ $att->session?->date ?? 'غير محدد' }}</div>
                                                             @if($att->notes)
                                                                 <div class="text-xs text-amber-600 font-bold mt-1">📌 ملاحظة الخادم: {{ $att->notes }}</div>
                                                             @endif
                                                         </div>
                                                         <div>
                                                             @if($att->status === 'present')
                                                                 <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-lg">حاضر</span>
                                                             @elseif($att->status === 'excused')
                                                                 <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-1 rounded-lg">معتذر</span>
                                                             @else
                                                                 <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-lg">غائب</span>
                                                             @endif
                                                         </div>
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-6 text-gray-400 text-xs">لا يوجد سجل حضور حتى الآن.</div>
                                         @endif
                                     </div>

                                     {{-- Exams Tab --}}
                                     <div x-show="activeTab === 'exams'" class="space-y-3">
                                         <h4 class="font-bold text-gray-700 text-sm">درجات الامتحانات التفصيلية:</h4>
                                         @if($enrollment->examScores->count() > 0)
                                             <div class="grid gap-3">
                                                 @foreach($enrollment->examScores as $score)
                                                     <div class="bg-gray-50 border p-3 rounded-xl space-y-2">
                                                         <div class="flex justify-between items-center">
                                                             <div>
                                                                 <div class="text-sm font-bold text-gray-700">{{ $score->exam?->title }}</div>
                                                                 <div class="text-[10px] text-gray-400 font-bold">{{ $score->exam?->date }}</div>
                                                             </div>
                                                             <div class="text-sm font-black text-amber-600">
                                                                 {{ $score->score }} / {{ $score->exam?->total_score }}
                                                             </div>
                                                         </div>
                                                         @php
                                                             $percent = $score->exam?->total_score > 0 ? ($score->score / $score->exam->total_score) * 100 : 0;
                                                         @endphp
                                                         <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                                                             <div class="bg-amber-500 h-full rounded-full" style="width: {{ $percent }}%"></div>
                                                         </div>
                                                         @if($score->notes)
                                                             <div class="text-xs text-amber-600 font-bold">📌 ملاحظة: {{ $score->notes }}</div>
                                                         @endif
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-6 text-gray-400 text-xs">لا يوجد درجات امتحانات مسجلة.</div>
                                         @endif
                                     </div>

                                     {{-- Memorization Tab --}}
                                     <div x-show="activeTab === 'memorization'" class="space-y-3">
                                         <h4 class="font-bold text-gray-700 text-sm">سجل المحفوظات والتسميع:</h4>
                                         @if($enrollment->memorizationScores->count() > 0)
                                             <div class="grid gap-2">
                                                 @foreach($enrollment->memorizationScores as $memo)
                                                     <div class="flex justify-between items-center bg-gray-50 border p-3 rounded-xl">
                                                         <div>
                                                             <div class="text-sm font-bold text-gray-700">{{ $memo->memorizationItem?->title }}</div>
                                                             @if($memo->notes)
                                                                 <div class="text-xs text-amber-600 font-bold mt-1">📌 ملاحظة: {{ $memo->notes }}</div>
                                                             @endif
                                                         </div>
                                                         <div class="flex items-center gap-2">
                                                             @if($memo->score >= 100)
                                                                 <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-lg">✨ تم الحفظ</span>
                                                             @else
                                                                 <span class="bg-gray-100 text-gray-500 text-xs font-bold px-3 py-1 rounded-lg">لم يتم الحفظ</span>
                                                             @endif
                                                         </div>
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-6 text-gray-400 text-xs">لا يوجد محفوظات مسجلة بعد.</div>
                                         @endif
                                     </div>

                                     {{-- Activities Tab --}}
                                     <div x-show="activeTab === 'activities'" class="space-y-3">
                                         <h4 class="font-bold text-gray-700 text-sm">الأنشطة والمسابقات المشترك بها الطفل:</h4>
                                         @if($enrollment->activityEnrollments->count() > 0)
                                             <div class="grid gap-3">
                                                 @foreach($enrollment->activityEnrollments as $actEnroll)
                                                     <div class="bg-gray-50 border p-3 rounded-xl">
                                                         <div class="flex justify-between items-center">
                                                             <div>
                                                                 <div class="text-sm font-bold text-gray-700">🎯 {{ $actEnroll->activity?->title }}</div>
                                                                 <div class="text-[10px] text-gray-400 font-bold">الحالة: {{ $actEnroll->status === 'qualified' ? 'مؤهل' : 'مشترك' }}</div>
                                                             </div>
                                                             @php
                                                                 $scoreVal = $actEnroll->scores->avg('score') ?? 0;
                                                             @endphp
                                                             <div class="text-sm font-black text-amber-600">
                                                                 الدرجة: {{ round($scoreVal) }}%
                                                             </div>
                                                         </div>
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-6 text-gray-400 text-xs">الطفل غير مشترك في أي أنشطة بعد.</div>
                                         @endif
                                     </div>

                                     {{-- Behavior & Remarks Tab --}}
                                     <div x-show="activeTab === 'behavior'" class="space-y-4">
                                         <div>
                                             <h4 class="font-bold text-gray-700 text-sm mb-2">سجل النقاط والملاحظات السلوكية:</h4>
                                             @if($enrollment->behaviorLogs->count() > 0)
                                                 <div class="space-y-2">
                                                     @foreach($enrollment->behaviorLogs as $log)
                                                         <div class="p-3 rounded-xl border flex justify-between items-center {{ $log->type === 'positive' ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                             <div>
                                                                 <div class="text-xs font-bold text-gray-400">{{ $log->created_at->format('Y-m-d') }}</div>
                                                                 <div class="text-sm font-bold text-gray-700">{{ $log->reason }}</div>
                                                             </div>
                                                             <div>
                                                                 <span class="text-sm font-black {{ $log->type === 'positive' ? 'text-green-600' : 'text-red-600' }}">
                                                                     {{ $log->type === 'positive' ? '+' : '' }}{{ $log->points }}
                                                                 </span>
                                                             </div>
                                                         </div>
                                                     @endforeach
                                                 </div>
                                             @else
                                                 <div class="text-center py-4 text-gray-400 text-xs">سجل السلوك نظيف ولا توجد ملاحظات سلوكية.</div>
                                             @endif
                                         </div>

                                         <div class="bg-blue-50 p-4 rounded-2xl">
                                             <h4 class="font-bold text-blue-800 mb-2">💡 نصيحة للتشجيع</h4>
                                             <p class="text-sm text-blue-600">ابنك متفوق جداً في {{ $rankInfo['data']['breakdown']['exams'] > 90 ? 'الامتحانات' : 'الحضور' }}، شجعه على الاستمرار وتطوير مستواه في باقي المجالات!</p>
                                         </div>
                                     </div>
                                 </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>
</html>
