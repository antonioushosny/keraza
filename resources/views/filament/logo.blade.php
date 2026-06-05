<div class="flex items-center gap-3">
    <img src="{{ asset('icon.png') }}" class="w-10 h-10 rounded-full object-cover border-2 border-amber-500 shadow-sm" alt="Logo">
    <div class="text-right">
        <div class="text-sm font-black text-gray-900 dark:text-white leading-tight">مهرجان الكرازة{{ (request()->is('e3dady') || request()->is('e3dady/*')) ? ' (إعدادي)' : '' }}</div>
        <div class="text-[10px] font-bold text-amber-600 dark:text-amber-400 leading-none mt-1">كنيسة العذراء مريم المطرية</div>
    </div>
</div>
