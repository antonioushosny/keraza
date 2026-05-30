<x-filament-panels::page>
    @if(empty($stats))
        <div class="bg-white rounded-2xl p-12 text-center border shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="text-6xl mb-4">🏆</div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">لا يوجد موسم نشط حاليًا</h3>
            <p class="text-gray-500 mt-2 dark:text-gray-400">يرجى تفعيل أحد المواسم من صفحة المواسم لعرض تقارير الأداء.</p>
        </div>
    @else
        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-2xl rounded-2xl">👨‍🎓</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">إجمالي المخدومين</div>
                    <div class="text-3xl font-black text-gray-800 dark:text-white">{{ $stats['total_students'] }}</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-2xl rounded-2xl">📚</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">عدد المراحل</div>
                    <div class="text-3xl font-black text-gray-800 dark:text-white">{{ $stats['total_classes'] }}</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-2xl rounded-2xl">📅</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">معدل الحضور العام</div>
                    <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ $stats['avg_attendance'] }}%</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-2xl rounded-2xl">📝</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">متوسط درجات الامتحانات</div>
                    <div class="text-3xl font-black text-purple-600 dark:text-purple-400">{{ $stats['avg_exams'] }}%</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/50 flex items-center justify-center text-2xl rounded-2xl">📖</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">نسبة التسميع الكلية</div>
                    <div class="text-3xl font-black text-pink-600 dark:text-pink-400">{{ $stats['avg_memorization'] }}%</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center text-2xl rounded-2xl">🏆</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">التقييم العام للموسم</div>
                    <div class="text-3xl font-black text-amber-600 dark:text-amber-400">{{ $stats['avg_final_score'] }}%</div>
                </div>
            </div>
        </div>

        {{-- Class Comparison Table --}}
        <div class="bg-white rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mt-8">
            <div class="px-6 py-5 border-b dark:border-gray-700">
                <h3 class="text-lg font-black text-gray-800 dark:text-white">📊 مقارنة أداء الفصول والمراحل</h3>
                <p class="text-xs text-gray-400 mt-1">مقارنة شاملة لنسب ومعدلات التحصيل بمختلف المراحل الدراسية</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b dark:bg-gray-700/50 dark:border-gray-700">
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">اسم الفصل</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">عدد المخدومين</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">الحضور</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">الامتحانات</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">المحفوظات</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">نقاط السلوك</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($classComparison as $comp)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-4 text-sm font-bold text-gray-800 dark:text-white">{{ $comp['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $comp['students_count'] }} مخدوم</td>
                                <td class="px-6 py-4 text-sm text-emerald-600 dark:text-emerald-400 font-bold">{{ $comp['avg_attendance'] }}%</td>
                                <td class="px-6 py-4 text-sm text-purple-600 dark:text-purple-400 font-bold">{{ $comp['avg_exams'] }}%</td>
                                <td class="px-6 py-4 text-sm text-pink-600 dark:text-pink-400 font-bold">{{ $comp['avg_memorization'] }}%</td>
                                <td class="px-6 py-4 text-sm font-bold {{ $comp['avg_behavior'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $comp['avg_behavior'] }} pt</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Alerts Section --}}
        <div class="bg-white rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mt-8">
            <div class="px-6 py-5 border-b dark:border-gray-700">
                <h3 class="text-lg font-black text-gray-800 dark:text-white">🔔 تنبيهات المتابعة والدعم</h3>
                <p class="text-xs text-gray-400 mt-1">تنبيهات فورية لمستوى المخدومين وسلوكهم لمساعدة الخدام في المتابعة</p>
            </div>
            <div class="p-6">
                @if(empty($alerts))
                    <div class="text-center py-6 text-gray-400 text-sm">كل المخدومين بمستوى ممتاز وسلوك رائع! لا توجد تنبيهات متابعة.</div>
                @else
                    <div class="grid gap-3">
                        @foreach($alerts as $alert)
                            <div class="p-4 rounded-2xl border flex flex-col sm:flex-row justify-between sm:items-center gap-3 {{ $alert['severity'] === 'danger' ? 'bg-red-50/50 border-red-100 dark:bg-red-950/20 dark:border-red-900/50' : 'bg-yellow-50/50 border-yellow-100 dark:bg-yellow-950/20 dark:border-yellow-900/50' }}">
                                <div class="flex items-start gap-3">
                                    <div class="text-lg">{{ $alert['severity'] === 'danger' ? '⚠️' : '💡' }}</div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-800 dark:text-white">{{ $alert['student_name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $alert['description'] }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mr-auto sm:mr-0">
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-lg {{ $alert['severity'] === 'danger' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' }}">
                                        {{ $alert['type'] }}
                                    </span>
                                    <span class="text-sm font-black {{ $alert['severity'] === 'danger' ? 'text-red-600' : 'text-yellow-600' }}">{{ $alert['value'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
