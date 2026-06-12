<x-filament-panels::page>
    @if(empty($stats))
        <div class="bg-white rounded-2xl p-12 text-center border shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="text-6xl mb-4">🏆</div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">لا يوجد موسم نشط حاليًا</h3>
            <p class="text-gray-500 mt-2 dark:text-gray-400">يرجى تفعيل أحد المواسم من صفحة المواسم لعرض تقارير الأداء.</p>
        </div>
    @else
        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
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
                    <div class="text-sm font-bold text-gray-400">معدل الحضور العام للنشطين</div>
                    <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ $stats['avg_attendance'] }}%</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-2xl rounded-2xl">📝</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">متوسط درجات الامتحانات للنشطين</div>
                    <div class="text-3xl font-black text-purple-600 dark:text-purple-400">{{ $stats['avg_exams'] }}%</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4 font-bold font-bold">
                <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/50 flex items-center justify-center text-2xl rounded-2xl">📖</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">نسبة التسميع للنشطين</div>
                    <div class="text-3xl font-black text-pink-600 dark:text-pink-400">{{ $stats['avg_memorization'] }}%</div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center text-2xl rounded-2xl">🏆</div>
                <div>
                    <div class="text-sm font-bold text-gray-400">التقييم العام للنشطين</div>
                    <div class="text-3xl font-black text-amber-600 dark:text-amber-400">{{ $stats['avg_final_score'] }}%</div>
                </div>
            </div>
        </div>

        {{-- Performance Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-md font-black text-gray-800 dark:text-white mb-4">📈 مقارنة حضور الطلاب بالصفوف (%)</h3>
                <div class="relative w-full" style="height: 300px;">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-md font-black text-gray-800 dark:text-white mb-4">📊 توزيع مستويات الامتحانات بالصفوف (%)</h3>
                <div class="relative w-full" style="height: 300px;">
                    <canvas id="examsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Attendance & Counts Table --}}
        <div class="bg-white rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mt-8">
            <div class="px-6 py-5 border-b dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white">📅 تقرير أعداد وحضور المخدومين بالصفوف</h3>
                    <p class="text-xs text-gray-400 mt-1">توزيع المخدومين بين نشطين وغير نشطين مع نسب الحضور التفصيلية</p>
                </div>
                <button type="button" onclick="exportTableToCsv('attendanceTable', 'تقرير_حضور_واعداد_المخدومين')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition border-none cursor-pointer flex items-center gap-2">
                    📥 تصدير إكسيل
                </button>
            </div>
            <div class="overflow-x-auto">
                <table id="attendanceTable" class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b dark:bg-gray-700/50 dark:border-gray-700">
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">اسم الفصل</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">عدد المخدومين الكلي</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">المخدومين النشطين (حضور)</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">المخدومين غير النشطين</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">نسبة حضور إجمالي الفصل</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">نسبة حضور النشطين فقط</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($attendanceStats as $att)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-4 text-sm font-bold text-gray-800 dark:text-white">{{ $att['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $att['total_count'] }} مخدوم</td>
                                <td class="px-6 py-4 text-sm text-emerald-600 dark:text-emerald-400 font-bold">{{ $att['active_count'] }} مخدوم</td>
                                <td class="px-6 py-4 text-sm text-red-500 dark:text-red-400 font-bold">{{ $att['inactive_count'] }} مخدوم</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-bold">{{ $att['rate_total'] }}%</td>
                                <td class="px-6 py-4 text-sm text-emerald-600 dark:text-emerald-400 font-bold">{{ $att['rate_active'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Exams Stats Table --}}
        <div class="bg-white rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mt-8">
            <div class="px-6 py-5 border-b dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white">📝 تقرير إحصائيات الامتحانات للفصول</h3>
                    <p class="text-xs text-gray-400 mt-1">تفاصيل نتائج ومستويات الممتحنين الفعليين بالصفوف</p>
                </div>
                <button type="button" onclick="exportTableToCsv('examsTable', 'تقرير_احصائيات_الامتحانات')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition border-none cursor-pointer flex items-center gap-2">
                    📥 تصدير إكسيل
                </button>
            </div>
            <div class="overflow-x-auto">
                <table id="examsTable" class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b dark:bg-gray-700/50 dark:border-gray-700">
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">اسم الفصل</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">عدد الامتحانات</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">متوسط الممتحنين فعلياً</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">متوسط درجات الممتحنين فقط</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">نسبة الحاصلين على >75%</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">نسبة الحاصلين على >=50%</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">نسبة الراسبين <50%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($examStats as $ex)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-4 text-sm font-bold text-gray-800 dark:text-white">{{ $ex['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $ex['exams_count'] }} امتحان</td>
                                <td class="px-6 py-4 text-sm text-purple-600 dark:text-purple-400 font-bold">{{ $ex['avg_attendees'] }} مخدوم</td>
                                <td class="px-6 py-4 text-sm text-purple-600 dark:text-purple-400 font-bold">{{ $ex['avg_score'] }}%</td>
                                <td class="px-6 py-4 text-sm text-emerald-600 dark:text-emerald-400 font-bold">{{ $ex['above_75_pct'] }}%</td>
                                <td class="px-6 py-4 text-sm text-yellow-600 dark:text-yellow-400 font-bold">{{ $ex['above_50_pct'] }}%</td>
                                <td class="px-6 py-4 text-sm text-red-600 dark:text-red-400 font-bold">{{ $ex['below_50_pct'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Class Comparison Table --}}
        <div class="bg-white rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mt-8">
            <div class="px-6 py-5 border-b dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white">📊 مقارنة أداء الفصول والمراحل (للمخدومين النشطين)</h3>
                    <p class="text-xs text-gray-400 mt-1">مقارنة شاملة لمعدلات التحصيل بناءً على المخدومين النشطين فقط</p>
                </div>
                <button type="button" onclick="exportTableToCsv('comparisonTable', 'مقارنة_اداء_الفصول')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition border-none cursor-pointer flex items-center gap-2">
                    📥 تصدير إكسيل
                </button>
            </div>
            <div class="overflow-x-auto">
                <table id="comparisonTable" class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b dark:bg-gray-700/50 dark:border-gray-700">
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">اسم الفصل</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">عدد المخدومين الكلي</th>
                            <th class="px-6 py-4 text-sm font-bold text-gray-600 dark:text-gray-300">عدد المخدومين النشطين</th>
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
                                <td class="px-6 py-4 text-sm text-emerald-600 dark:text-emerald-400 font-bold">{{ $comp['active_students_count'] }} مخدوم</td>
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
        @php
            $user = auth()->user();
            $isSuperAdmin = $user->hasRole('super_admin');
            $assignedClassNames = !$isSuperAdmin ? $user->assignedClasses->pluck('name')->toArray() : [];
            
            $allClassNames = collect($classComparison)->pluck('name')->toArray();
            if (!$isSuperAdmin) {
                $allClassNames = array_intersect($allClassNames, $assignedClassNames);
            }
            
            $groupedAlerts = collect($alerts)->groupBy('class_name')->toArray();
            $initialTab = count($allClassNames) > 0 ? reset($allClassNames) : '';
        @endphp
        <div class="bg-white rounded-3xl border shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mt-8"
             x-data="{ activeAlertTab: '{{ $initialTab }}' }">
            <div class="px-6 py-5 border-b dark:border-gray-700 flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white">🔔 تنبيهات المتابعة والدعم (للمخدومين النشطين)</h3>
                    <p class="text-xs text-gray-400 mt-1">تنبيهات فورية لمستوى المخدومين وسلوكهم مقسمة بالفصول</p>
                </div>
                <button type="button" onclick="exportAlerts()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition border-none cursor-pointer flex items-center gap-2">
                    📥 تصدير إكسيل
                </button>
            </div>
            
            <div class="px-6 py-4 border-b dark:border-gray-700">
                @if(count($allClassNames) > 0)
                    {{-- Alert Tabs Navigation --}}
                    <div class="flex flex-wrap gap-1.5 mt-4">
                        @foreach($allClassNames as $cName)
                            @php 
                                $alertCount = isset($groupedAlerts[$cName]) ? count($groupedAlerts[$cName]) : 0; 
                            @endphp
                            <button type="button" 
                                    @click="activeAlertTab = '{{ $cName }}'"
                                    :class="activeAlertTab === '{{ $cName }}' ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600/50'"
                                    class="px-4 py-2 text-xs font-bold rounded-xl transition whitespace-nowrap border-none cursor-pointer">
                                {{ $cName }} ({{ $alertCount }})
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <div class="p-6">
                @if(empty($alerts))
                    <div class="text-center py-6 text-gray-400 text-sm">كل المخدومين النشطين بمستوى ممتاز وسلوك رائع! لا توجد تنبيهات متابعة.</div>
                @else
                    @foreach($allClassNames as $cName)
                        @php 
                            $cAlerts = $groupedAlerts[$cName] ?? []; 
                        @endphp
                        <div x-show="activeAlertTab === '{{ $cName }}'" class="grid gap-3" x-transition x-cloak>
                            @if(count($cAlerts) > 0)
                                @foreach($cAlerts as $alert)
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
                            @else
                                <div class="text-center py-6 text-gray-400 text-sm">كل المخدومين النشطين في هذا الفصل بمستوى ممتاز وسلوك رائع! لا توجد تنبيهات متابعة.</div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Export script & ChartJS scripts --}}
        <script>
            function exportTableToCsv(tableId, filename) {
                const table = document.getElementById(tableId);
                if (!table) return;
                
                let csv = [];
                const rows = table.querySelectorAll("tr");
                
                for (let i = 0; i < rows.length; i++) {
                    const row = [], cols = rows[i].querySelectorAll("td, th");
                    
                    for (let j = 0; j < cols.length; j++) {
                        let data = cols[j].innerText.trim();
                        data = data.replace(/"/g, '""');
                        row.push('"' + data + '"');
                    }
                    
                    csv.push(row.join(","));
                }
                
                const csvContent = "\uFEFF" + csv.join("\n");
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.setAttribute("href", url);
                link.setAttribute("download", filename + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            function exportAlerts() {
                const alertsData = @json($alerts);
                const assignedClasses = @json($assignedClassNames);
                const isSuperAdmin = @json($isSuperAdmin);
                
                let csv = [];
                csv.push(["اسم الطالب", "اسم الفصل", "نوع التنبيه", "الوصف", "القيمة"].map(h => '"' + h + '"').join(","));
                
                alertsData.forEach(alert => {
                    if (!isSuperAdmin && !assignedClasses.includes(alert.class_name)) {
                        return;
                    }
                    const row = [
                        alert.student_name,
                        alert.class_name,
                        alert.type,
                        alert.description,
                        alert.value
                    ].map(val => '"' + (val || '').toString().replace(/"/g, '""') + '"');
                    csv.push(row.join(","));
                });
                
                const csvContent = "\uFEFF" + csv.join("\n");
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.setAttribute("href", url);
                link.setAttribute("download", "تنبيهات_المتابعة_والدعم.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                    const attendanceData = @json($attendanceStats);
                    const examData = @json($examStats);

                    const labels = attendanceData.map(item => item.name);
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#9ca3af' : '#4b5563';
                    const gridColor = isDark ? 'rgba(75, 85, 99, 0.2)' : 'rgba(229, 231, 235, 0.5)';

                    // Chart 1: Attendance Rates
                    new Chart(document.getElementById('attendanceChart'), {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'نسبة الحضور الإجمالي (%)',
                                    data: attendanceData.map(item => item.rate_total),
                                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                                    borderColor: 'rgb(59, 130, 246)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'نسبة الحضور من النشطين (%)',
                                    data: attendanceData.map(item => item.rate_active),
                                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                                    borderColor: 'rgb(16, 185, 129)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: textColor,
                                        font: { family: 'Tajawal, sans-serif', size: 11 }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: { color: gridColor },
                                    ticks: { color: textColor }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: textColor }
                                }
                            }
                        }
                    });

                    // Chart 2: Exam Grade Distribution
                    new Chart(document.getElementById('examsChart'), {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'فوق 75% (%)',
                                    data: examData.map(item => item.above_75_pct),
                                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                                    borderColor: 'rgb(16, 185, 129)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'أعلى من 50% (%)',
                                    data: examData.map(item => item.above_50_pct),
                                    backgroundColor: 'rgba(245, 158, 11, 0.6)',
                                    borderColor: 'rgb(245, 158, 11)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'راسبين أقل من 50% (%)',
                                    data: examData.map(item => item.below_50_pct),
                                    backgroundColor: 'rgba(239, 68, 68, 0.6)',
                                    borderColor: 'rgb(239, 68, 68)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: textColor,
                                        font: { family: 'Tajawal, sans-serif', size: 11 }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: { color: gridColor },
                                    ticks: { color: textColor }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: textColor }
                                }
                            }
                        }
                    });
                });
        </script>
    @endif
</x-filament-panels::page>
