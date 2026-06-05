@php
    $isE3dady = request()->is('e3dady') || request()->is('e3dady/*');
    $routePrefix = $isE3dady ? 'e3dady.' : '';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعديل الحساب - مهرجان الكرازة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f0f2f5; }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen pb-20">
        {{-- Header --}}
        <div class="bg-white shadow-sm border-b px-4 py-4 sticky top-0 z-50">
            <div class="max-w-md mx-auto flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <img src="/icon.png" class="w-10 h-10 rounded-full object-cover border border-amber-500/30" alt="Logo">
                    <h1 class="text-base sm:text-lg font-black text-gray-800">كنيسة العذراء مريم المطرية</h1>
                </div>
                <a href="{{ route($routePrefix . 'parent.dashboard') }}" class="text-sm font-bold text-gray-500 bg-gray-100 px-4 py-2 rounded-xl">عودة</a>
            </div>
        </div>

        <div class="max-w-md mx-auto p-4 sm:p-6 mt-6">
            {{-- Form Container --}}
            <div class="bg-white rounded-3xl border shadow-sm p-6 sm:p-8">
                <h2 class="text-xl font-black text-gray-800 mb-6 text-center">تحديث بيانات ولي الأمر</h2>

                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl">
                        <ul class="list-disc list-inside text-xs font-bold space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route($routePrefix . 'parent.profile') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-400 mb-2">الاسم بالكامل</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-bold text-gray-400 mb-2">رقم الهاتف (الذي تسجل به الدخول)</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" required dir="ltr" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition text-right">
                    </div>

                    <div class="border-t border-dashed my-6 pt-4">
                        <div class="text-xs font-bold text-gray-400 mb-4">تغيير كلمة المرور (اتركه فارغاً إذا كنت لا ترغب في تغييره)</div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="password" class="block text-[10px] font-bold text-gray-400 mb-2">كلمة المرور الجديدة</label>
                                <input type="password" name="password" id="password" dir="ltr" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-[10px] font-bold text-gray-400 mb-2">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-amber-500 hover:bg-amber-600 text-white font-black rounded-2xl shadow-lg shadow-amber-500/20 transition duration-300">
                        حفظ التعديلات
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
