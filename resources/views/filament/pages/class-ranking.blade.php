<x-filament-panels::page>
    {{-- Class Selector --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">🏆</div>
                <div>
                    <h2 class="text-base font-black text-gray-950 dark:text-white">ترتيب المخدومين حسب الفصل</h2>
                    @if($seasonName)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $seasonName }}</p>
                    @endif
                </div>
            </div>
            <div class="w-full sm:w-auto sm:mr-auto">
                <select wire:model.live="selectedClassId"
                        class="w-full sm:w-64 text-sm font-bold rounded-lg px-4 py-2.5 transition
                               bg-white dark:bg-white/5
                               border border-gray-300 dark:border-white/20
                               text-gray-950 dark:text-white
                               focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">-- اختر الفصل --</option>
                    @foreach($classes as $class)
                        <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if(!$selectedClassId)
        {{-- Empty State --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center mt-6">
            <div class="text-6xl mb-4">📚</div>
            <h3 class="text-xl font-bold text-gray-950 dark:text-white">اختر فصل لعرض الترتيب</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">حدد الفصل من القائمة أعلاه لرؤية ترتيب المخدومين مع تفصيل حساب النقاط.</p>
        </div>
    @elseif(empty($rankings))
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center mt-6">
            <div class="text-6xl mb-4">📋</div>
            <h3 class="text-xl font-bold text-gray-950 dark:text-white">لا يوجد مخدومين في هذا الفصل</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">لا يوجد مخدومين مسجلين في هذا الفصل في الموسم الحالي.</p>
        </div>
    @else
        {{-- Rankings List --}}
        <div class="space-y-3 mt-6" x-data="{ expandedId: null }">
            @foreach($rankings as $index => $rank)
                @php
                    $rankPos = $rank['rank_position'];
                    $isRepeated = $rank['is_repeated'];
                    $isTop3 = $rankPos <= 3;
                    $medal = match($rankPos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => null };
                    $maxScore = collect($rankings)->max('score') ?: 1;
                    $barWidth = round(($rank['score'] / $maxScore) * 100);
                    $cardStyle = match($rankPos) {
                        1 => 'border-color: #d97706; background: linear-gradient(135deg, rgba(251,191,36,0.08) 0%, rgba(245,158,11,0.03) 100%);',
                        2 => 'border-color: #9ca3af; background: linear-gradient(135deg, rgba(156,163,175,0.08) 0%, rgba(156,163,175,0.02) 100%);',
                        3 => 'border-color: #c2410c; background: linear-gradient(135deg, rgba(234,88,12,0.08) 0%, rgba(234,88,12,0.02) 100%);',
                        default => '',
                    };
                    $data = $rank['data'];
                    $breakdown = $data['breakdown'];
                    $weights = $data['weights'];
                    $weighted = $data['weighted_breakdown'];
                    $labels = [
                        'attendance' => ['label' => 'الحضور', 'icon' => '📅', 'color' => '#10b981'],
                        'exams' => ['label' => 'الامتحانات', 'icon' => '📝', 'color' => '#6366f1'],
                        'memorization' => ['label' => 'التسميع', 'icon' => '📖', 'color' => '#f59e0b'],
                        'activities' => ['label' => 'الأنشطة', 'icon' => '🎯', 'color' => '#0ea5e9'],
                        'behavior' => ['label' => 'السلوك', 'icon' => '✨', 'color' => '#22c55e'],
                    ];
                @endphp

                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden transition-all duration-300"
                     @if($isTop3) style="{{ $cardStyle }}" @endif>
                    {{-- Main Row --}}
                    <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-5 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition"
                         @click="expandedId = expandedId === {{ $rank['enrollment_id'] }} ? null : {{ $rank['enrollment_id'] }}">

                        {{-- Rank --}}
                        <div class="flex-shrink-0 w-10 sm:w-14 text-center">
                            @if($medal)
                                <span class="text-xl sm:text-2xl">{{ $medal }}</span>
                                @if($isRepeated)
                                    <span class="block text-[9px] font-bold" style="color: #f59e0b;">مكرر</span>
                                @endif
                            @else
                                <span class="text-base sm:text-lg font-black text-gray-400 dark:text-gray-500">#{{ $rankPos }}</span>
                                @if($isRepeated)
                                    <span class="block text-[9px] font-bold text-gray-400 dark:text-gray-500">مكرر</span>
                                @endif
                            @endif
                        </div>

                        {{-- Avatar --}}
                        <div class="flex-shrink-0">
                            @if($rank['profile_image'])
                                <img src="{{ asset('storage/' . $rank['profile_image']) }}" alt="{{ $rank['student_name'] }}"
                                     class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl object-cover"
                                     style="{{ $isTop3 ? 'border: 2px solid #f59e0b; box-shadow: 0 4px 12px rgba(245,158,11,0.25);' : 'border: 1px solid rgba(156,163,175,0.3);' }}">
                            @else
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-sm sm:text-base font-bold text-white"
                                     style="background: linear-gradient(135deg, hsl({{ ($index * 47) % 360 }}, 65%, 55%), hsl({{ (($index * 47) + 40) % 360 }}, 65%, 45%)); {{ $isTop3 ? 'border: 2px solid #f59e0b; box-shadow: 0 4px 12px rgba(245,158,11,0.25);' : 'border: 1px solid rgba(156,163,175,0.3);' }}">
                                    {{ mb_substr($rank['student_name'], 0, 2) }}
                                </div>
                            @endif
                        </div>

                        {{-- Name & Score Bar --}}
                        <div class="flex-grow min-w-0">
                            <h3 class="font-bold text-gray-950 dark:text-white text-sm sm:text-base truncate">{{ $rank['student_name'] }}</h3>
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="flex-grow h-2 rounded-full overflow-hidden" style="background: rgba(156,163,175,0.15);">
                                    <div class="h-full rounded-full transition-all duration-700" style="width: {{ $barWidth }}%; background: linear-gradient(90deg, #f59e0b, #ea580c);"></div>
                                </div>
                                <span class="text-xs font-black whitespace-nowrap" style="color: {{ $isTop3 ? '#f59e0b' : '#9ca3af' }};">
                                    {{ number_format($rank['score'], 1) }}%
                                </span>
                            </div>
                            {{-- Badges inline --}}
                            @if(!empty($rank['badges']) && count($rank['badges']) > 0)
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach($rank['badges'] as $badge)
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-lg text-[10px] font-bold"
                                              style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.25); color: #f59e0b;"
                                              title="{{ $badge['title'] }}">
                                            {{ $badge['icon'] }}
                                            <span class="hidden sm:inline">{{ $badge['title'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Expand Arrow --}}
                        <div class="flex-shrink-0 text-gray-400 dark:text-gray-500">
                            <svg class="w-5 h-5 transition-transform duration-300" :class="expandedId === {{ $rank['enrollment_id'] }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    {{-- Expanded Score Breakdown --}}
                    <div x-show="expandedId === {{ $rank['enrollment_id'] }}" x-collapse x-cloak>
                        <div class="p-4 sm:p-5" style="border-top: 1px solid rgba(156,163,175,0.15); background: rgba(0,0,0,0.02);">
                            <h4 class="text-xs font-black text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                                <span class="w-1 h-3 rounded-full" style="background: #f59e0b;"></span>
                                تفصيل حساب النقاط
                            </h4>

                            {{-- Score Breakdown Table --}}
                            <div class="overflow-x-auto rounded-lg" style="border: 1px solid rgba(156,163,175,0.15);">
                                <table class="w-full text-right border-collapse">
                                    <thead>
                                        <tr style="background: rgba(0,0,0,0.03); border-bottom: 1px solid rgba(156,163,175,0.15);">
                                            <th class="px-4 py-3 text-[11px] font-bold text-gray-500 dark:text-gray-400">البند</th>
                                            <th class="px-4 py-3 text-[11px] font-bold text-gray-500 dark:text-gray-400">الدرجة الخام</th>
                                            <th class="px-4 py-3 text-[11px] font-bold text-gray-500 dark:text-gray-400">الوزن</th>
                                            <th class="px-4 py-3 text-[11px] font-bold text-gray-500 dark:text-gray-400">الدرجة المرجحة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($labels as $key => $info)
                                            <tr style="border-bottom: 1px solid rgba(156,163,175,0.1);" class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                                <td class="px-4 py-3 text-sm font-bold text-gray-800 dark:text-gray-200">
                                                    <span class="ml-1">{{ $info['icon'] }}</span> {{ $info['label'] }}
                                                </td>
                                                <td class="px-4 py-3 text-sm font-bold" style="color: {{ $info['color'] }};">
                                                    {{ $key === 'behavior' ? $breakdown[$key] . ' pt' : round($breakdown[$key]) . '%' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-bold">
                                                    × {{ $weights[$key] }}%
                                                </td>
                                                <td class="px-4 py-3 text-sm font-black text-gray-800 dark:text-gray-200">
                                                    = {{ $weighted[$key] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="border-top: 2px solid rgba(245,158,11,0.3); background: rgba(245,158,11,0.05);">
                                            <td class="px-4 py-3.5 text-sm font-black text-gray-900 dark:text-white" colspan="3">
                                                🏆 المجموع النهائي
                                            </td>
                                            <td class="px-4 py-3.5 text-base font-black" style="color: #f59e0b;">
                                                = {{ $rank['score'] }}%
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Summary Stats --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 mt-6">
            <h3 class="text-sm font-black text-gray-950 dark:text-white mb-3 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full" style="background: #3b82f6;"></span>
                📊 ملخص الفصل
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="text-center p-3 rounded-lg" style="background: rgba(156,163,175,0.06);">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">عدد المخدومين</div>
                    <div class="text-xl font-black text-gray-950 dark:text-white">{{ count($rankings) }}</div>
                </div>
                <div class="text-center p-3 rounded-lg" style="background: rgba(16,185,129,0.06);">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">أعلى درجة</div>
                    <div class="text-xl font-black" style="color: #10b981;">{{ number_format(collect($rankings)->max('score'), 1) }}%</div>
                </div>
                <div class="text-center p-3 rounded-lg" style="background: rgba(239,68,68,0.06);">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">أقل درجة</div>
                    <div class="text-xl font-black" style="color: #ef4444;">{{ number_format(collect($rankings)->min('score'), 1) }}%</div>
                </div>
                <div class="text-center p-3 rounded-lg" style="background: rgba(245,158,11,0.06);">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">المتوسط</div>
                    <div class="text-xl font-black" style="color: #f59e0b;">{{ number_format(collect($rankings)->avg('score'), 1) }}%</div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
