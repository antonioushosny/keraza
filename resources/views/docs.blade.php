<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دليل النظام والتوثيق - مهرجان الكرازة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #0f172a; /* Slate 900 for absolute premium dark mode */
            color: #e2e8f0;
        }
        .gold-glow {
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.15);
        }
        .active-tab {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6) 100%);
            border-right: 4px solid #f59e0b;
            color: #ffffff;
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        pre {
            background: #1e293b;
            padding: 1rem;
            border-radius: 12px;
            overflow-x: auto;
            border: 1px solid #334155;
            direction: ltr;
            text-align: left;
        }
        code {
            font-family: monospace;
            color: #f472b6;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col" x-data="{ activeTab: 'board', sidebarOpen: false }">

    <!-- Header -->
    <header class="bg-slate-900 border-b border-slate-800 py-4 px-6 sticky top-0 z-50 shadow-lg gold-glow">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="/icon.png" class="w-10 h-10 rounded-full border border-amber-500 shadow-md" alt="Logo">
                <div>
                    <h1 class="text-lg font-black text-amber-500">منصة مهرجان الكرازة</h1>
                    <p class="text-[10px] text-slate-400 font-bold">دليل التشغيل والتوثيق التفاعلي</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="/" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-slate-700">
                    🏆 لوحة الشرف
                </a>
                <a href="/admin" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-md">
                    🔒 لوحة الإدارة
                </a>
                <!-- Mobile Menu Button -->
                <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 text-slate-400 hover:text-white bg-slate-800 rounded-xl border border-slate-700">
                    📂
                </button>
            </div>
        </div>
    </header>

    <div class="flex-grow max-w-7xl w-full mx-auto flex">
        
        <!-- Sidebar Navigation -->
        <aside :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full md:translate-x-0'" class="w-80 bg-slate-900 border-l border-slate-800 p-6 fixed md:sticky top-[73px] right-0 bottom-0 z-40 transition-transform duration-300 md:h-[calc(100vh-73px)] overflow-y-auto">
            <div class="mb-6">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">أقسام التوثيق</h3>
            </div>
            <nav class="space-y-2">
                <button @click="activeTab = 'board'; sidebarOpen = false" :class="activeTab === 'board' ? 'active-tab shadow-sm' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white'" class="w-full text-right px-4 py-3 rounded-xl font-bold text-sm transition flex items-center gap-3">
                    <span class="text-lg">🌟</span>
                    <span>1. لوحة الشرف الشاشة العامة</span>
                </button>
                <button @click="activeTab = 'scoring'; sidebarOpen = false" :class="activeTab === 'scoring' ? 'active-tab shadow-sm' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white'" class="w-full text-right px-4 py-3 rounded-xl font-bold text-sm transition flex items-center gap-3">
                    <span class="text-lg">📊</span>
                    <span>2. معايير التقييم والدرجات</span>
                </button>
                <button @click="activeTab = 'parent'; sidebarOpen = false" :class="activeTab === 'parent' ? 'active-tab shadow-sm' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white'" class="w-full text-right px-4 py-3 rounded-xl font-bold text-sm transition flex items-center gap-3">
                    <span class="text-lg">👨‍👩‍👧‍👦</span>
                    <span>3. بوابة حساب ولي الأمر</span>
                </button>
                <button @click="activeTab = 'admin'; sidebarOpen = false" :class="activeTab === 'admin' ? 'active-tab shadow-sm' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white'" class="w-full text-right px-4 py-3 rounded-xl font-bold text-sm transition flex items-center gap-3">
                    <span class="text-lg">🎛️</span>
                    <span>4. لوحة الإدارة والصلاحيات</span>
                </button>
                <button @click="activeTab = 'guide'; sidebarOpen = false" :class="activeTab === 'guide' ? 'active-tab shadow-sm' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white'" class="w-full text-right px-4 py-3 rounded-xl font-bold text-sm transition flex items-center gap-3">
                    <span class="text-lg">🚀</span>
                    <span>5. دليل إدخال البيانات للسيستم</span>
                </button>
            </nav>
            <div class="mt-8 pt-8 border-t border-slate-800 text-center">
                <a href="/resources/docs.md" download class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-amber-500 rounded-xl text-xs font-bold transition border border-slate-700">
                    📥 تحميل التوثيق كملف Markdown
                </a>
            </div>
        </aside>

        <!-- Overlay for mobile sidebar -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 z-30 md:hidden transition-opacity"></div>

        <!-- Documentation Content -->
        <main class="flex-grow p-6 sm:p-10 lg:p-12 overflow-y-auto max-w-4xl">
            
            <!-- Section 1: Public Leaderboard -->
            <div x-show="activeTab === 'board'" class="space-y-8">
                <div>
                    <h2 class="text-3xl font-black text-amber-500 flex items-center gap-3">
                        <span>🌟</span> الشاشة العامة ولوحة الشرف
                    </h2>
                    <p class="text-slate-400 mt-2">عرض متكامل لواجهة النظام الجماهيرية وتكريم المتميزين.</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-800 rounded-3xl p-6 space-y-4">
                    <h3 class="text-lg font-bold text-white">مميزات لوحة الشرف الجماهيرية</h3>
                    <p class="text-slate-300 leading-relaxed">
                        تعد لوحة الشرف هي شاشة العرض الرئيسية لجمهور المهرجان. تم تصميمها بطريقة بصرية فاخرة وألوان ذهبية غنية تليق بتكريم الطلاب وتُحفزهم على تطوير مستوياتهم الدراسية والسلوكية.
                    </p>
                    <div class="grid sm:grid-cols-2 gap-4 pt-2">
                        <div class="bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
                            <div class="text-2xl mb-2">🥇</div>
                            <h4 class="font-bold text-white text-sm">ميداليات التميز الفائق</h4>
                            <p class="text-xs text-slate-400 mt-1">تمييز المراكز الثلاثة الأولى بميداليات ووهج ذهبي براق.</p>
                        </div>
                        <div class="bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
                            <div class="text-2xl mb-2">🎯</div>
                            <h4 class="font-bold text-white text-sm">تصفية سريعة للفصول</h4>
                            <p class="text-xs text-slate-400 mt-1">فرز فوري لترتيب مخدومي أي فصل دراسي بضغطة زر واحدة.</p>
                        </div>
                        <div class="bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
                            <div class="text-2xl mb-2">⚡</div>
                            <h4 class="font-bold text-white text-sm">مؤشر أداء مرئي</h4>
                            <p class="text-xs text-slate-400 mt-1">رسم بياني ملون متدرج يعرض تميز المخدوم بصرياً مقارنة بزملائه.</p>
                        </div>
                        <div class="bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
                            <div class="text-2xl mb-2">✨</div>
                            <h4 class="font-bold text-white text-sm">الأوسمة والبادجات</h4>
                            <p class="text-xs text-slate-400 mt-1">عرض شارات التميز التي حصل عليها الطالب بجوار اسمه مباشرة.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-2xl flex items-start gap-3">
                    <span class="text-xl">💡</span>
                    <div>
                        <h4 class="font-bold text-amber-500 text-sm">ملاحظة ذكية للأولاد بدون صورة</h4>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            إذا لم يرفع ولي الأمر صورة شخصية لابنه، يقوم النظام تلقائياً بإنشاء رمز دائري بـ لون عشوائي متناسق (HSL) يحمل الحروف الأولى من اسم الطفل للحفاظ على المظهر الجمالي للوحة.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Scoring System -->
            <div x-show="activeTab === 'scoring'" class="space-y-8">
                <div>
                    <h2 class="text-3xl font-black text-amber-500 flex items-center gap-3">
                        <span>📊</span> معايير التقييم والدرجات
                    </h2>
                    <p class="text-slate-400 mt-2">كيفية احتساب درجات الطلاب وأوزان التصنيفات رياضياً.</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-800 rounded-3xl p-6 space-y-6">
                    <h3 class="text-lg font-bold text-white">التوزيع النسبي للدرجات (الأوزان الافتراضية)</h3>
                    <p class="text-slate-300 text-sm leading-relaxed">
                        يتم توزيع الدرجات بمجموع كلي يعادل 100%، ويمكن تعديل هذه الأوزان لكل فصل دراسي أو موسم بشكل مستقل من لوحة المدير (`ScoringRule`):
                    </p>

                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                <span>📅 الحضور والغياب</span>
                                <span>20%</span>
                            </div>
                            <div class="w-full bg-slate-900 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full" style="width: 20%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                <span>📝 الامتحانات التحريرية</span>
                                <span>30%</span>
                            </div>
                            <div class="w-full bg-slate-900 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-blue-500 h-full" style="width: 30%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                <span>📖 المحفوظات والتسميع</span>
                                <span>20%</span>
                            </div>
                            <div class="w-full bg-slate-900 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full" style="width: 20%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                <span>🎯 الأنشطة والمسابقات</span>
                                <span>20%</span>
                            </div>
                            <div class="w-full bg-slate-900 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-purple-500 h-full" style="width: 20%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                <span>✨ السلوك والملاحظات التراكمية</span>
                                <span>10%</span>
                            </div>
                            <div class="w-full bg-slate-900 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-pink-500 h-full" style="width: 10%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-xl font-bold text-white border-b border-slate-800 pb-2">شرح معادلة رصد التقييمات:</h3>
                    
                    <div class="space-y-4 text-sm leading-relaxed text-slate-300">
                        <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                            <h4 class="font-bold text-emerald-400 mb-2">1. الحضور والغياب (Attendance):</h4>
                            <p class="text-xs">
                                يُحسب الحضور بنسبة مئوية بناءً على إجمالي عدد الجمع الدراسية المتاحة.
                                <br><strong class="text-white">معادلة الحضور:</strong> (عدد أيام الحضور + عدد أيام الاعتذار المقبول) / إجمالي الجمع الدراسية للفصل × 100.
                                <br><span class="text-yellow-400">ملاحظة هامة:</span> حالات الاعتذار المصدق عليها من الخادم تُحسب لصالح الطالب كحضور وتساوي حضوراً تاماً في رصد الدرجة.
                            </p>
                        </div>

                        <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                            <h4 class="font-bold text-blue-400 mb-2">2. الامتحانات (Exams):</h4>
                            <p class="text-xs">
                                يقوم النظام باحتساب متوسط الدرجات الحاصل عليها المخدوم في كافة الامتحانات التي خاضها مقسومة على الدرجة النهائية لتلك الامتحانات.
                            </p>
                        </div>

                        <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                            <h4 class="font-bold text-amber-400 mb-2">3. المحفوظات (Memorization):</h4>
                            <p class="text-xs">
                                متوسط أداء المخدوم في تسميع بنود المحفوظات المطلوبة منه ليعطي نسبة مئوية إجمالية لمدى تقدمه في التسميع والحفظ.
                            </p>
                        </div>

                        <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                            <h4 class="font-bold text-purple-400 mb-2">4. الأنشطة (Activities):</h4>
                            <p class="text-xs">
                                متوسط درجات المخدوم في كافة الأنشطة والمسابقات التي تم تأهيله للاشتراك فيها ورصد نقاط أداء له بها.
                            </p>
                        </div>

                        <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                            <h4 class="font-bold text-pink-400 mb-2">5. السلوك والملاحظات (Behavior):</h4>
                            <p class="text-xs">
                                رصد تراكمي للنقاط السلوكية الإيجابية (إضافة نقاط) والسلبية (خصم نقاط) والتي تؤثر في المجموع السلوكي النهائي للطالب.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Parent Portal -->
            <div x-show="activeTab === 'parent'" class="space-y-8">
                <div>
                    <h2 class="text-3xl font-black text-amber-500 flex items-center gap-3">
                        <span>👨‍👩‍👧‍👦</span> بوابة حساب ولي الأمر
                    </h2>
                    <p class="text-slate-400 mt-2">لوحة متابعة تفاعلية شاملة أولياء الأمور لتتبع أداء أبنائهم بالكامل.</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-800 rounded-3xl p-6 space-y-6">
                    <h3 class="text-lg font-bold text-white">ما يراه ولي الأمر ويستطيع فعله:</h3>
                    
                    <ul class="space-y-4 text-sm text-slate-300">
                        <li class="flex items-start gap-3">
                            <span class="bg-amber-500/20 text-amber-400 p-1 rounded-lg text-xs">✔</span>
                            <div>
                                <strong class="text-white block">متابعة قائمة الأبناء:</strong>
                                عرض جميع الأبناء المرتبطين بحسابه كأولياء أمور بشكل مباشر ببطاقات إحصائية جميلة.
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="bg-amber-500/20 text-amber-400 p-1 rounded-lg text-xs">✔</span>
                            <div>
                                <strong class="text-white block">تعديل الصورة الشخصية للأبناء:</strong>
                                إمكانية النقر مباشرة على صورة الابن لرفع صورة شخصية جديدة له وتحديثها لحظياً.
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="bg-amber-500/20 text-amber-400 p-1 rounded-lg text-xs">✔</span>
                            <div>
                                <strong class="text-white block">شارة الترتيب والأوسمة:</strong>
                                معرفة ترتيب الابن على مستوى فصله وأوسمة البادجات الحاصل عليها لتعزيز ثقته بنفسه.
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="bg-amber-500/20 text-amber-400 p-1 rounded-lg text-xs">✔</span>
                            <div>
                                <strong class="text-white block">التفاصيل التفصيلية مقسمة لـ Tabs:</strong>
                                بضغطة زر ينكشف سجل كامل وتفاعلي من 5 أقسام رئيسية:
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mt-2">
                                    <span class="bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800 text-[10px] font-bold text-center block">📅 الحضور والغياب</span>
                                    <span class="bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800 text-[10px] font-bold text-center block">📝 الامتحانات</span>
                                    <span class="bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800 text-[10px] font-bold text-center block">📖 المحفوظات</span>
                                    <span class="bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800 text-[10px] font-bold text-center block">🎯 الأنشطة</span>
                                    <span class="bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800 text-[10px] font-bold text-center block">✨ السلوك</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Section 4: Admin and Roles -->
            <div x-show="activeTab === 'admin'" class="space-y-8">
                <div>
                    <h2 class="text-3xl font-black text-amber-500 flex items-center gap-3">
                        <span>🎛️</span> لوحة الإدارة والصلاحيات
                    </h2>
                    <p class="text-slate-400 mt-2">نظام تحكم إداري وصلاحيات دقيقة للخدام والمدراء.</p>
                </div>

                <div class="grid sm:grid-cols-2 gap-6">
                    <div class="bg-slate-800/50 border border-slate-800 rounded-3xl p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">👤</span>
                            <h3 class="text-lg font-bold text-white">المدير العام (Super Admin)</h3>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            صلاحيات كاملة وغير مقيدة على مستوى النظام بأكمله:
                        </p>
                        <ul class="space-y-2 text-xs text-slate-400">
                            <li>• إدارة المواسم الدراسية وتعديلها وتنشيطها.</li>
                            <li>• إدارة حسابات المستخدمين والخدام وصلاحياتهم.</li>
                            <li>• تعديل هيكل الفصول والأنشطة الإدارية.</li>
                            <li>• ضبط قواعد توزيع الدرجات (Scoring Rules).</li>
                            <li>• رفع واستيراد كشوف الحضور والدرجات الجماعية CSV.</li>
                        </ul>
                    </div>

                    <div class="bg-slate-800/50 border border-slate-800 rounded-3xl p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">👥</span>
                            <h3 class="text-lg font-bold text-white">الخادم (Servant)</h3>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            صلاحيات مقيدة تلقائياً بنطاق الفصول والأنشطة المكلف بها فقط:
                        </p>
                        <ul class="space-y-2 text-xs text-slate-400">
                            <li>• رؤية فقط الفصول المكلف برعايتها وإدارتها.</li>
                            <li>• تسجيل جمع حضور جديدة ورصد المخدومين بها.</li>
                            <li>• رصد درجات امتحانات الطلاب وتسميع المحفوظات.</li>
                            <li>• تسجيل النقاط السلوكية اليومية للمخدومين.</li>
                            <li>• رصد تقييمات الأنشطة التي يشارك بها فصله.</li>
                        </ul>
                    </div>
                </div>

                <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 text-xs text-slate-400">
                    <span class="text-yellow-500 font-bold block mb-1">⚠️ تنبيه أمني:</span>
                    أولياء الأمور ليس لديهم أي صلاحية أو وصول للوحة الإدارة الإدارية (Filament Panel) نهائياً، وفي حال قيامهم بطلبها يتم توجيههم تلقائياً لصفحتهم المخصصة بورتال ولي الأمر (`/parent`).
                </div>
            </div>

            <!-- Section 5: Data Entry Guide -->
            <div x-show="activeTab === 'guide'" class="space-y-8">
                <div>
                    <h2 class="text-3xl font-black text-amber-500 flex items-center gap-3">
                        <span>🚀</span> دليل إدخال البيانات للسيستم
                    </h2>
                    <p class="text-slate-400 mt-2">التسلسل المنهجي الصحيح لإعداد النظام وبدء رصد درجات الطلاب.</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-800 rounded-3xl p-6 space-y-6">
                    <h3 class="text-lg font-bold text-white">التسلسل الصحيح لخطوات إدخال البيانات</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        يجب الالتزام بالخطوات التالية لتجنب أي مشاكل متعلقة بارتباط العلاقات الإدارية في قاعدة البيانات:
                    </p>

                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm flex-shrink-0">1</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">المواسم الدراسية (Seasons)</h4>
                                <p class="text-xs text-slate-400 mt-1">إنشاء الموسم الجديد وتفعيله ليكون هو "الموسم النشط" لكامل النظام.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm flex-shrink-0">2</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">الفصول والأنشطة (Classes & Activities)</h4>
                                <p class="text-xs text-slate-400 mt-1">إدخال الفصول الدراسية (أولى ابتدائي، ثانية، إلخ) وتعريف الأنشطة المتنوعة.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm flex-shrink-0">3</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">قواعد احتساب الدرجات (Scoring Rules)</h4>
                                <p class="text-xs text-slate-400 mt-1">تحديد نسب وأوزان كل تقييم لكل فصل لضمان الدقة وتفادي ظهور درجات صفرية.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm flex-shrink-0">4</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">تسجيل الخدام وتكليفاتهم (Servants & Assignments)</h4>
                                <p class="text-xs text-slate-400 mt-1">تسجيل حسابات الخدام وتحديد الفصول والأنشطة التي يستطيعون إدارتها ورصدها.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm flex-shrink-0">5</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">تسجيل أولياء الأمور (Parents)</h4>
                                <p class="text-xs text-slate-400 mt-1">تسجيل أولياء الأمور بأسمائهم وهواتفهم لإنشاء حسابات بورتال ولي الأمر.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm flex-shrink-0">6</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">المخدومين وتسجيلهم (Students & Enrollments)</h4>
                                <p class="text-xs text-slate-400 mt-1">إدخال الطلاب وربطهم بـ فصولهم وربطهم بحسابات أولياء أمورهم بنجاح.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm flex-shrink-0">7</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">أدوات التقييم ورصد الدرجات</h4>
                                <p class="text-xs text-slate-400 mt-1">البدء في إدخال الامتحانات والمحفوظات ورصد الحضور والدرجات والسلوك التراكمي.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800 py-6 text-center text-xs text-slate-500 mt-auto">
        <p>✝ نظام إدارة وتقييم مهرجان الكرازة المرقسية © 2026 ✝</p>
    </footer>

</body>
</html>
