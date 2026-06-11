<x-filament-panels::page>
    {{-- Student Profile Header --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        <div class="p-6 sm:p-8" style="background: linear-gradient(to left, rgba(245,158,11,0.08), transparent);">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                {{-- Avatar --}}
                <div class="relative flex-shrink-0">
                    @if($student->profile_image)
                        <img src="{{ asset('storage/' . $student->profile_image) }}" 
                             alt="{{ $student->full_name }}" 
                             class="w-20 h-20 rounded-2xl object-cover shadow-lg"
                             style="border: 2px solid #f59e0b;">
                    @else
                        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-3xl font-black"
                             style="background: linear-gradient(135deg, #fde68a, #fbbf24); color: #92400e; border: 2px solid #f59e0b;">
                            {{ mb_substr($student->full_name, 0, 1) }}
                        </div>
                    @endif
                    @if($rank && $rank <= 3)
                        <div class="absolute -top-2 -right-2 text-2xl">
                            {{ $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉') }}
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-grow min-w-0">
                    <h2 class="text-xl sm:text-2xl font-black text-gray-950 dark:text-white">{{ $student->full_name }}</h2>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-xl"
                              style="background: rgba(156,163,175,0.1); border: 1px solid rgba(156,163,175,0.2); color: #6b7280;">
                            🆔 كود: {{ $student->code }}
                        </span>
                        @if($enrollment)
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-xl"
                                  style="background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); color: #3b82f6;">
                                📚 {{ $enrollment->class?->name ?? 'غير محدد' }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-xl"
                              style="background: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.2); color: #a855f7;">
                            {{ $student->gender === 'male' ? '👦 ذكر' : '👧 أنثى' }}
                        </span>
                        @if($student->birth_date)
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-xl"
                                  style="background: rgba(236,72,153,0.1); border: 1px solid rgba(236,72,153,0.2); color: #ec4899;">
                                🎂 {{ $student->birth_date }}
                            </span>
                        @endif
                        @if($rank)
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-xl"
                                  style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); color: #f59e0b;">
                                🏆 الترتيب: {{ $rank }} من {{ $rankingsCount }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Score Badge --}}
                @if($scoreData)
                    <div class="flex-shrink-0 text-white rounded-2xl px-5 py-3 text-center shadow-lg"
                         style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="text-2xl font-black">{{ $scoreData['final_score'] }}%</div>
                        <div class="text-[10px] font-bold uppercase mt-0.5" style="color: rgba(255,255,255,0.8);">المعدل النهائي</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Parent Info --}}
        @if($student->parent)
            <div class="px-6 sm:px-8 py-4" style="border-top: 1px solid rgba(156,163,175,0.15); background: rgba(0,0,0,0.02);">
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">👨‍👩‍👦 ولي الأمر:</span>
                    <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $student->parent->name }}</span>
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">📱 الموبايل:</span>
                    <span class="text-sm font-bold text-gray-950 dark:text-white" dir="ltr">{{ $student->parent->phone }}</span>
                </div>
            </div>
        @endif
    </div>

    @if(!$enrollment)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center mt-6">
            <div class="text-6xl mb-4">📋</div>
            <h3 class="text-xl font-bold text-gray-950 dark:text-white">لا يوجد تسجيل في الموسم النشط</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-2">هذا المخدوم غير مسجل في الموسم الحالي.</p>
        </div>
    @else
        {{-- Quick Stats Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mt-6">
            @php
                $statCards = [
                    ['label' => '📅 الحضور', 'value' => round($scoreData['breakdown']['attendance']) . '%', 'color' => '#10b981'],
                    ['label' => '📝 الامتحانات', 'value' => round($scoreData['breakdown']['exams']) . '%', 'color' => '#6366f1'],
                    ['label' => '📖 التسميع', 'value' => round($scoreData['breakdown']['memorization']) . '%', 'color' => '#f59e0b'],
                    ['label' => '🎯 الأنشطة', 'value' => round($scoreData['breakdown']['activities']) . '%', 'color' => '#0ea5e9'],
                    ['label' => '✨ السلوك', 'value' => $scoreData['breakdown']['behavior'] . ' pt', 'color' => $scoreData['breakdown']['behavior'] >= 0 ? '#22c55e' : '#ef4444'],
                ];
            @endphp
            @foreach($statCards as $card)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 text-center">
                    <div class="text-xs font-bold mb-1" style="color: {{ $card['color'] }};">{{ $card['label'] }}</div>
                    <div class="text-2xl font-black text-gray-950 dark:text-white">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Badges --}}
        @if($enrollment->badges->count() > 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 mt-6">
                <h3 class="text-sm font-black text-gray-950 dark:text-white mb-3 flex items-center gap-2">
                    <span class="w-1.5 h-4 rounded-full" style="background: #f59e0b;"></span>
                    🎖️ الأوسمة والبادجات
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($enrollment->badges as $sb)
                        <div class="px-3 py-1.5 rounded-2xl flex items-center gap-2 hover:scale-105 transition-transform"
                             style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2);">
                            <span class="text-lg">{{ $sb->badge->icon }}</span>
                            <span class="text-xs font-bold" style="color: #f59e0b;">{{ $sb->badge->title }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Detailed Sections --}}
        <div class="grid gap-6 mt-6" x-data="{ activeTab: 'attendance' }">
            {{-- Tab Navigation --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-2">
                <div class="flex flex-wrap gap-1">
                    @php
                        $tabs = [
                            'attendance' => '📅 الحضور',
                            'exams' => '📝 الامتحانات',
                            'memorization' => '📖 التسميع',
                            'activities' => '🎯 الأنشطة',
                            'behavior' => '✨ السلوك',
                        ];
                    @endphp
                    @foreach($tabs as $tabKey => $tabLabel)
                        <button type="button"
                                @click="activeTab = '{{ $tabKey }}'"
                                :class="activeTab === '{{ $tabKey }}'
                                    ? 'text-white shadow-md'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'"
                                :style="activeTab === '{{ $tabKey }}'
                                    ? 'background: #f59e0b;'
                                    : 'background: rgba(156,163,175,0.08);'"
                                class="px-4 py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all whitespace-nowrap">
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Attendance Tab --}}
            <div x-show="activeTab === 'attendance'" x-transition class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4" style="border-bottom: 1px solid rgba(156,163,175,0.15);">
                    <h3 class="text-sm font-black text-gray-950 dark:text-white flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full" style="background: #10b981;"></span>
                        سجل الحضور والغياب
                    </h3>
                </div>
                @if($enrollment->attendance->count() > 0)
                    <div>
                        @foreach($enrollment->attendance->sortByDesc(fn($a) => $a->session?->date) as $att)
                            @php
                                $statusConfig = match($att->status) {
                                    'present' => ['label' => 'حاضر', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)', 'border' => 'rgba(16,185,129,0.2)'],
                                    'excused' => ['label' => 'معتذر', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)', 'border' => 'rgba(245,158,11,0.2)'],
                                    default => ['label' => 'غائب', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)', 'border' => 'rgba(239,68,68,0.2)'],
                                };
                            @endphp
                            <div class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                                 style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full" style="background: {{ $statusConfig['color'] }};"></div>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $att->session?->date ?? 'غير محدد' }}</span>
                                    @if($att->notes)
                                        <span class="text-xs text-gray-400 dark:text-gray-500">📌 {{ $att->notes }}</span>
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
                    <div class="p-8 text-center text-gray-400 dark:text-gray-500 text-sm">لا يوجد سجل حضور حتى الآن.</div>
                @endif
            </div>

            {{-- Exams Tab --}}
            <div x-show="activeTab === 'exams'" x-transition class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4" style="border-bottom: 1px solid rgba(156,163,175,0.15);">
                    <h3 class="text-sm font-black text-gray-950 dark:text-white flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full" style="background: #6366f1;"></span>
                        درجات الامتحانات
                    </h3>
                </div>
                @if($enrollment->examScores->count() > 0)
                    <div>
                        @foreach($enrollment->examScores as $es)
                            @php
                                $percent = $es->exam?->total_score > 0 ? ($es->score / $es->exam->total_score) * 100 : 0;
                                $barGradient = $percent >= 90
                                    ? 'linear-gradient(90deg, #10b981, #14b8a6)'
                                    : ($percent >= 50
                                        ? 'linear-gradient(90deg, #f59e0b, #ea580c)'
                                        : 'linear-gradient(90deg, #ef4444, #dc2626)');
                                $textColor = $percent >= 90 ? '#10b981' : ($percent >= 50 ? '#f59e0b' : '#ef4444');
                            @endphp
                            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                                 style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $es->exam?->title }}</span>
                                        @if($es->exam?->date)
                                            <span class="text-xs text-gray-400 dark:text-gray-500 mr-2">{{ $es->exam->date }}</span>
                                        @endif
                                    </div>
                                    <span class="text-sm font-black" style="color: {{ $textColor }};">{{ $es->score }} / {{ $es->exam?->total_score }}</span>
                                </div>
                                <div style="width: 100%; height: 8px; border-radius: 9999px; background: rgba(156,163,175,0.15); overflow: hidden;">
                                    <div style="width: {{ round($percent) }}%; height: 100%; border-radius: 9999px; background: {{ $barGradient }}; transition: width 0.5s ease-in-out;"></div>
                                </div>
                                @if($es->notes)
                                    <div class="text-xs font-bold px-2.5 py-1 rounded-lg mt-2 inline-block"
                                         style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.15); color: #f59e0b;">
                                        📌 {{ $es->notes }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-gray-400 dark:text-gray-500 text-sm">لا توجد درجات امتحانات حتى الآن.</div>
                @endif
            </div>

            {{-- Memorization Tab --}}
            <div x-show="activeTab === 'memorization'" x-transition class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4" style="border-bottom: 1px solid rgba(156,163,175,0.15);">
                    <h3 class="text-sm font-black text-gray-950 dark:text-white flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full" style="background: #f59e0b;"></span>
                        درجات التسميع والمحفوظات
                    </h3>
                </div>
                @if($enrollment->memorizationScores->count() > 0)
                    <div>
                        @foreach($enrollment->memorizationScores as $ms)
                            @php
                                $maxPoints = $ms->memorizationItem?->max_points ?: 100;
                                $mPercent = ($ms->score / $maxPoints) * 100;
                                $mBarGradient = $mPercent >= 90
                                    ? 'linear-gradient(90deg, #10b981, #14b8a6)'
                                    : ($mPercent >= 50
                                        ? 'linear-gradient(90deg, #f59e0b, #ea580c)'
                                        : 'linear-gradient(90deg, #ef4444, #dc2626)');
                                $mTextColor = $mPercent >= 90 ? '#10b981' : ($mPercent >= 50 ? '#f59e0b' : '#ef4444');
                            @endphp
                            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                                 style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $ms->memorizationItem?->title ?? 'محفوظة' }}</span>
                                    <span class="text-sm font-black" style="color: {{ $mTextColor }};">{{ $ms->score }} / {{ $maxPoints }}</span>
                                </div>
                                <div style="width: 100%; height: 8px; border-radius: 9999px; background: rgba(156,163,175,0.15); overflow: hidden;">
                                    <div style="width: {{ round($mPercent) }}%; height: 100%; border-radius: 9999px; background: {{ $mBarGradient }}; transition: width 0.5s ease-in-out;"></div>
                                </div>
                                @if($ms->notes)
                                    <div class="text-xs font-bold px-2.5 py-1 rounded-lg mt-2 inline-block"
                                         style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.15); color: #f59e0b;">
                                        📌 {{ $ms->notes }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-gray-400 dark:text-gray-500 text-sm">لا توجد درجات تسميع حتى الآن.</div>
                @endif
            </div>

            {{-- Activities Tab --}}
            <div x-show="activeTab === 'activities'" x-transition class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4" style="border-bottom: 1px solid rgba(156,163,175,0.15);">
                    <h3 class="text-sm font-black text-gray-950 dark:text-white flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full" style="background: #0ea5e9;"></span>
                        الأنشطة والدرجات
                    </h3>
                </div>
                @if($enrollment->activityEnrollments->count() > 0)
                    <div>
                        @foreach($enrollment->activityEnrollments as $ae)
                            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                                 style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $ae->activity?->title ?? 'نشاط' }}</span>
                                    <div class="flex items-center gap-2">
                                        @if($ae->scores->count() > 0)
                                            <span class="text-sm font-black" style="color: #0ea5e9;">{{ round($ae->scores->avg('score'), 1) }}%</span>
                                        @else
                                            <span class="text-xs text-gray-400 dark:text-gray-500">لم يتم التقييم</span>
                                        @endif
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-lg"
                                              style="{{ $ae->status === 'active'
                                                  ? 'background: rgba(16,185,129,0.1); color: #10b981;'
                                                  : 'background: rgba(156,163,175,0.1); color: #9ca3af;' }}">
                                            {{ $ae->status === 'active' ? 'نشط' : $ae->status }}
                                        </span>
                                    </div>
                                </div>
                                @if($ae->scores->count() > 1)
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @foreach($ae->scores as $i => $sc)
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded"
                                                  style="background: rgba(14,165,233,0.08); border: 1px solid rgba(14,165,233,0.15); color: #0ea5e9;">
                                                تقييم {{ $i + 1 }}: {{ $sc->score }}%
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-gray-400 dark:text-gray-500 text-sm">لا يوجد اشتراك في أنشطة حتى الآن.</div>
                @endif
            </div>

            {{-- Behavior Tab --}}
            <div x-show="activeTab === 'behavior'" x-transition class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4" style="border-bottom: 1px solid rgba(156,163,175,0.15);">
                    <h3 class="text-sm font-black text-gray-950 dark:text-white flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full" style="background: #22c55e;"></span>
                        سجل السلوك والملاحظات
                    </h3>
                </div>
                @if($enrollment->behaviorLogs->count() > 0)
                    <div>
                        @foreach($enrollment->behaviorLogs->sortByDesc('created_at') as $log)
                            @php
                                $isPositive = $log->type === 'positive';
                                $logColor = $isPositive ? '#10b981' : '#ef4444';
                            @endphp
                            <div class="px-6 py-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                                 style="border-bottom: 1px solid rgba(156,163,175,0.08);">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-sm">{{ $isPositive ? '✨' : '⚠️' }}</span>
                                        <div>
                                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $log->reason }}</span>
                                            @if($log->creator)
                                                <span class="text-[10px] text-gray-400 dark:text-gray-500 mr-2">بواسطة: {{ $log->creator->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-xs font-black px-2.5 py-1 rounded-lg"
                                          style="background: {{ $isPositive ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' }}; color: {{ $logColor }};">
                                        {{ $isPositive ? '+' : '' }}{{ $log->points }} نقطة
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-6 py-3" style="border-top: 1px solid rgba(156,163,175,0.15); background: rgba(0,0,0,0.02);">
                        @php $totalBehavior = $enrollment->behaviorLogs->sum('points'); @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400">إجمالي نقاط السلوك</span>
                            <span class="text-sm font-black" style="color: {{ $totalBehavior >= 0 ? '#10b981' : '#ef4444' }};">
                                {{ $totalBehavior }} نقطة
                            </span>
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center text-gray-400 dark:text-gray-500 text-sm">لا يوجد سجل سلوك حتى الآن.</div>
                @endif
            </div>

            {{-- Notes --}}
            @if($student->notes)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
                    <h3 class="text-sm font-black text-gray-950 dark:text-white mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full" style="background: #9ca3af;"></span>
                        📝 ملاحظات عامة
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $student->notes }}</p>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
