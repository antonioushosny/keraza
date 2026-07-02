<x-filament-panels::page>
    {{-- Selectors --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">🏀</div>
                <div>
                    <h2 class="text-base font-black text-gray-950 dark:text-white">تقرير مشاركات الأنشطة العام</h2>
                    @if($seasonName)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $seasonName }}</p>
                    @endif
                </div>
            </div>
            <div class="w-full md:w-auto md:mr-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                {{-- Class Selector --}}
                <select wire:model.live="selectedClassId"
                        class="w-full sm:w-48 text-sm font-bold rounded-lg px-4 py-2.5 transition
                                bg-white dark:bg-white/5
                                border border-gray-300 dark:border-white/20
                                text-gray-950 dark:text-white
                                focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">كل الفصول</option>
                    @foreach($classes as $class)
                        <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                    @endforeach
                </select>

                {{-- Activity Filter (Optional) --}}
                <select wire:model.live="selectedActivityId"
                        class="w-full sm:w-64 text-sm font-bold rounded-lg px-4 py-2.5 transition
                                bg-white dark:bg-white/5
                                border border-gray-300 dark:border-white/20
                                text-gray-950 dark:text-white
                                focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">كل الأنشطة</option>
                    @foreach($activities as $act)
                        <option value="{{ $act['id'] }}">{{ $act['title'] }}</option>
                    @endforeach
                </select>

                @if(!empty($reportData))
                    <button wire:click="export" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-500 transition dark:bg-blue-500 dark:hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"></path></svg>
                        تصدير التقرير (Excel)
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if(empty($reportData))
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center mt-6">
            <div class="text-6xl mb-4">📋</div>
            <h3 class="text-xl font-bold text-gray-950 dark:text-white">لا توجد بيانات للعرض</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">حدد خيارات فلترة مختلفة لرؤية تقرير مشاركات الأنشطة.</p>
        </div>
    @else
        {{-- Report Stats Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-blue-50 dark:bg-blue-950/30 text-blue-500">👥</div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي المخدومين بالتقرير</div>
                    <div class="text-lg font-black text-gray-950 dark:text-white">{{ count($reportData) }}</div>
                </div>
            </div>
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-emerald-50 dark:bg-emerald-950/30 text-emerald-500">🏆</div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">الذين لديهم مشاركات أنشطة</div>
                    <div class="text-lg font-black text-gray-950 dark:text-white">{{ collect($reportData)->where('has_activities', true)->count() }}</div>
                </div>
            </div>
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-indigo-50 dark:bg-indigo-950/30 text-indigo-500">🎈</div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الأنشطة المتاحة</div>
                    <div class="text-lg font-black text-gray-950 dark:text-white">{{ count($activities) }}</div>
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
                            <th wire:click="sortBy('name')" class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 min-w-[200px] cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>المخدوم</span>
                                    @if($sortField === 'name')
                                        <span class="text-[10px]">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 text-center w-28">الفصل</th>
                            
                            @foreach($activities as $act)
                                <th wire:click="sortBy('activity_{{ $act['id'] }}')" class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 text-center min-w-[120px] cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                                    <div class="flex items-center gap-1 justify-center">
                                        <span>{{ $act['title'] }}</span>
                                        @if($sortField === 'activity_' . $act['id'])
                                            <span class="text-[10px]">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                        @endif
                                    </div>
                                </th>
                            @endforeach

                            <th wire:click="sortBy('max_activity_score')" class="px-4 py-4 text-xs font-black text-gray-500 dark:text-gray-400 text-center min-w-[130px] cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-1 justify-center">
                                    <span>أعلى تقييم (الأنشطة)</span>
                                    @if($sortField === 'max_activity_score')
                                        <span class="text-[10px]">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($reportData as $index => $row)
                            <tr wire:click="showStudentDetails({{ $row['enrollment_id'] }})" class="hover:bg-gray-50 dark:hover:bg-white/5 transition cursor-pointer">
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
                                <td class="px-4 py-4 text-center font-bold text-sm text-gray-600 dark:text-gray-300">
                                    {{ $row['class_name'] }}
                                </td>

                                @foreach($activities as $act)
                                    @php
                                        $actScore = $row['student_activities'][$act['id']]['final'] ?? null;
                                    @endphp
                                    <td class="px-4 py-4 text-center">
                                        @if($actScore !== null)
                                            @php
                                                $badgeClass = match(true) {
                                                    $actScore >= 95 => 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 ring-emerald-600/20 dark:ring-emerald-400/20',
                                                    $actScore >= 75 => 'bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400 ring-blue-600/20 dark:ring-blue-400/20',
                                                    $actScore >= 50 => 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 ring-amber-600/20 dark:ring-amber-400/20',
                                                    default => 'bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 ring-rose-600/20 dark:ring-rose-400/20',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold ring-1 {{ $badgeClass }}">
                                                {{ $actScore }}%
                                            </span>
                                        @else
                                            <span class="text-sm font-bold text-gray-300 dark:text-gray-600">-</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-4 py-4 text-center">
                                    @php
                                        $overallPct = $row['max_activity_score'];
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

    {{-- Details Modal --}}
    @if($selectedStudentDetails)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-950/75 transition-opacity" wire:click="closeStudentDetails"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal Body --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 dark:border-white/10">
                    <div class="bg-white dark:bg-gray-900 px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start border-b border-gray-100 dark:border-white/10 pb-4 mb-4">
                            <h3 class="text-lg font-black text-gray-950 dark:text-white" id="modal-title">
                                تفاصيل مشاركة الأنشطة للمخدوم: {{ $selectedStudentDetails['student_name'] }}
                            </h3>
                            <button type="button" wire:click="closeStudentDetails" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="text-xl">&times;</span>
                            </button>
                        </div>

                        {{-- Metadata --}}
                        <div class="grid grid-cols-2 gap-4 text-sm mb-6 bg-gray-50 dark:bg-white/5 p-4 rounded-xl">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-bold">الفصل:</span>
                                <span class="text-gray-900 dark:text-white font-extrabold mr-1">{{ $selectedStudentDetails['class_name'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-bold">النوع:</span>
                                <span class="text-gray-900 dark:text-white font-extrabold mr-1">{{ $selectedStudentDetails['gender'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-bold">تاريخ الميلاد:</span>
                                <span class="text-gray-900 dark:text-white font-extrabold mr-1">{{ $selectedStudentDetails['birth_date'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-bold">رقم ولي الأمر:</span>
                                <span class="text-gray-900 dark:text-white font-extrabold mr-1" dir="ltr">{{ $selectedStudentDetails['parent_phone'] }}</span>
                            </div>
                        </div>

                        {{-- Activity Details Table --}}
                        <div class="overflow-hidden border border-gray-100 dark:border-white/10 rounded-xl">
                            <table class="w-full text-right border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-100 dark:border-white/10">
                                        <th class="px-4 py-3 text-xs font-black text-gray-500 dark:text-gray-400">النشاط</th>
                                        <th class="px-4 py-3 text-xs font-black text-gray-500 dark:text-gray-400 text-center">الحضور</th>
                                        <th class="px-4 py-3 text-xs font-black text-gray-500 dark:text-gray-400 text-center">المهام</th>
                                        <th class="px-4 py-3 text-xs font-black text-gray-500 dark:text-gray-400 text-center">التقييم</th>
                                        <th class="px-4 py-3 text-xs font-black text-gray-500 dark:text-gray-400 text-center">التقييم النهائي</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                    @forelse($selectedStudentDetails['activities'] as $act)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5">
                                            <td class="px-4 py-3 font-bold text-sm text-gray-900 dark:text-white">
                                                {{ $act['title'] }}
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-bold text-blue-600 dark:text-blue-400">
                                                {{ $act['attendance'] }}%
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-bold text-pink-600 dark:text-pink-400">
                                                {{ $act['tasks'] }}%
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-bold text-amber-600 dark:text-amber-400">
                                                {{ $act['evaluation'] }}%
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-black text-emerald-600 dark:text-emerald-400">
                                                {{ $act['final'] }}%
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                                                هذا المخدوم غير مسجل في أي نشاط حالياً.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-white/5 px-6 py-4 flex justify-end gap-3 border-t border-gray-100 dark:border-white/10">
                        <button type="button" wire:click="closeStudentDetails" class="inline-flex justify-center rounded-lg border border-gray-300 dark:border-white/20 bg-white dark:bg-transparent px-4 py-2 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none">
                            إغلاق
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
