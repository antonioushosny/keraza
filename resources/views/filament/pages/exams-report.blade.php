<x-filament-panels::page>
    {{-- Class Selector --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">📝</div>
                <div>
                    <h2 class="text-base font-black text-gray-950 dark:text-white">تقرير الامتحانات</h2>
                    @if($seasonName)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $seasonName }}</p>
                    @endif
                </div>
            </div>
            <div class="w-full sm:w-auto sm:mr-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
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
                @if($selectedClassId && !empty($reportData))
                    <button wire:click="export" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-amber-600 border border-transparent rounded-lg hover:bg-amber-500 transition dark:bg-amber-500 dark:hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"></path></svg>
                        تصدير التقرير (CSV)
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if(!$selectedClassId)
        {{-- Empty State --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center mt-6">
            <div class="text-6xl mb-4">📝</div>
            <h3 class="text-xl font-bold text-gray-950 dark:text-white">اختر فصل لعرض تقرير الامتحانات</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">حدد الفصل من القائمة أعلاه لرؤية درجات الامتحانات التفصيلية لكل مخدوم.</p>
        </div>
    @elseif(empty($reportData))
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center mt-6">
            <div class="text-6xl mb-4">📋</div>
            <h3 class="text-xl font-bold text-gray-950 dark:text-white">لا يوجد مخدومين في هذا الفصل</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">لا يوجد مخدومين مسجلين في هذا الفصل في الموسم الحالي.</p>
        </div>
    @else
        {{-- Report Stats Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-indigo-50 dark:bg-indigo-950/30 text-indigo-500">👥</div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي المخدومين</div>
                    <div class="text-lg font-black text-gray-950 dark:text-white">{{ count($reportData) }}</div>
                </div>
            </div>
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-amber-50 dark:bg-amber-950/30 text-amber-500">✔️</div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">الذين أدوا الامتحانات</div>
                    <div class="text-lg font-black text-gray-950 dark:text-white">{{ collect($reportData)->where('has_scores', true)->count() }}</div>
                </div>
            </div>
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-blue-50 dark:bg-blue-950/30 text-blue-500">📊</div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">عدد الامتحانات المتاحة</div>
                    <div class="text-lg font-black text-gray-950 dark:text-white">{{ count($items) }}</div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden mt-6">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-100 dark:border-white/10">
                            <th class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 w-16 text-center">الترتيب</th>
                            <th class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 min-w-[200px]">المخدوم</th>
                            
                            @foreach($items as $item)
                                <th class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 text-center min-w-[120px]">
                                    <div>{{ $item['title'] }}</div>
                                    <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">الدرجة العظمى: {{ $item['total_score'] }}</div>
                                </th>
                            @endforeach

                            <th class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 text-center min-w-[100px]">إجمالي الدرجات</th>
                            <th class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 text-center min-w-[100px]">النسبة الكلية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($reportData as $index => $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-4 text-center">
                                    @if($index === 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-black">🥇</span>
                                    @elseif($index === 1)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-900/30 text-slate-600 dark:text-slate-400 text-xs font-black">🥈</span>
                                    @elseif($index === 2)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 text-xs font-black">🥉</span>
                                    @else
                                        <span class="text-sm font-bold text-gray-400 dark:text-gray-500">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($row['profile_image'])
                                            <img src="{{ asset('storage/' . $row['profile_image']) }}" alt="{{ $row['student_name'] }}"
                                                 class="w-8 h-8 rounded-lg object-cover ring-1 ring-gray-950/10 dark:ring-white/10">
                                        @else
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white"
                                                 style="background: linear-gradient(135deg, hsl({{ ($index * 47) % 360 }}, 65%, 55%), hsl({{ (($index * 47) + 40) % 360 }}, 65%, 45%));">
                                                {{ mb_substr($row['student_name'], 0, 2) }}
                                            </div>
                                        @endif
                                        <div class="font-bold text-sm text-gray-900 dark:text-white truncate">
                                            {{ $row['student_name'] }}
                                        </div>
                                    </div>
                                </td>

                                @foreach($items as $item)
                                    @php
                                        $scoreData = $row['scores'][$item['id']] ?? null;
                                    @endphp
                                    <td class="px-4 py-4 text-center">
                                        @if($scoreData)
                                            @php
                                                $pct = $scoreData['percentage'];
                                                $badgeClass = match(true) {
                                                    $pct >= 95 => 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 ring-emerald-600/20 dark:ring-emerald-400/20',
                                                    $pct >= 75 => 'bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400 ring-blue-600/20 dark:ring-blue-400/20',
                                                    $pct >= 50 => 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 ring-amber-600/20 dark:ring-amber-400/20',
                                                    default => 'bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 ring-rose-600/20 dark:ring-rose-400/20',
                                                };
                                            @endphp
                                            <div class="inline-flex flex-col items-center px-2.5 py-1 rounded-md text-xs font-bold ring-1 {{ $badgeClass }}">
                                                <span class="text-[11px]">{{ $scoreData['score'] }} / {{ $item['total_score'] }}</span>
                                                <span class="text-[9px] opacity-75 mt-0.5">{{ $pct }}%</span>
                                            </div>
                                        @else
                                            <span class="text-sm font-bold text-gray-300 dark:text-gray-600">-</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-4 py-4 text-center font-black text-sm text-gray-900 dark:text-white">
                                    {{ $row['total_score'] }} / {{ $row['total_max_score'] }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @php
                                        $overallPct = $row['total_percentage'];
                                        $overallColor = match(true) {
                                            $overallPct >= 95 => 'text-emerald-600 dark:text-emerald-400',
                                            $overallPct >= 75 => 'text-blue-600 dark:text-blue-400',
                                            $overallPct >= 50 => 'text-amber-600 dark:text-amber-400',
                                            default => 'text-rose-600 dark:text-rose-400',
                                        };
                                    @endphp
                                    <span class="font-extrabold text-sm {{ $overallColor }}">{{ $overallPct }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
