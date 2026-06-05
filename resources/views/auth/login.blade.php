<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول - مهرجان الكرازة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f8f8; }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 2px solid #c9a84c;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <img src="/icon.png" alt="Logo" class="w-24 h-24 mx-auto mb-4 rounded-full shadow-lg border-2 border-amber-500">
            <h1 class="text-2xl font-black text-gray-800">مهرجان الكرازة المرقسية</h1>
            <p class="text-amber-600 font-bold text-sm mt-1">كنيسة العذراء مريم المطرية</p>
            <p class="text-gray-500 text-sm mt-1">تسجيل دخول أولياء الأمور</p>
        </div>

        <div class="login-card p-8">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">رقم الموبايل</label>
                    <input class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 outline-none transition" 
                           id="phone" type="text" name="phone" value="{{ old('phone') }}" required autofocus>
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">كلمة المرور</label>
                    <input class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 outline-none transition" 
                           id="password" type="password" name="password" required>
                </div>

                <div class="flex items-center mb-6">
                    <input type="checkbox" name="remember" id="remember" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                    <label for="remember" class="mr-2 text-sm text-gray-600">تذكرني</label>
                </div>

                <button class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 px-4 rounded-xl transition shadow-lg shadow-amber-200" type="submit">
                    دخول
                </button>
            </form>
        </div>
        
        <div class="text-center mt-8">
            <a href="/" class="text-amber-600 font-bold hover:underline">العودة للرئيسية</a>
        </div>
    </div>
</body>
</html>
