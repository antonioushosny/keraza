# دليل مبرمجي Laravel المطور لـ Filament v3 و Livewire v3

مرحباً بك! هذا الدليل مصمم خصيصاً لمطور Laravel ذو خبرة (7 سنوات+) لمساعدتك في فهم الأساسيات العميقة وكيفية بناء وتخصيص تطبيقات **Filament v3** و **Livewire v3** بسرعة، مع ربط المفاهيم بنماذج برمجية حية من المشروع الحالي (`keraza`).

---

## 💡 الجزء الأول: فهم فلسفة العمل (Architecture Overview)

قبل الدخول في الكود، يجب استيعاب البنية الأساسية وكيف يتكاملان مع Laravel:

### 1. ما هو Livewire؟
Livewire هو إطار عمل (Frontend Framework) لـ Laravel يتيح لك بناء واجهات تفاعلية ديناميكية (تُشبه Vue أو React) ولكن بكتابة **PHP فقط** دون الحاجة لكتابة Javascript/API.
* **كيف يعمل تحت الصندوق؟**
  1. يتم رندرة المكون (Component) لأول مرة كـ HTML عادي (SSR).
  2. عندما يقوم المستخدم بتفاعل (مثل تغيير مدخل في Select أو ضغط زر)، يقوم Livewire بإرسال طلب **AJAX (POST)** خلف الكواليس يحتوي على الحالة الحالية للمكون (State).
  3. يقوم خادم Laravel بإعادة معالجة المكون وإعادة رندرة قالب الـ Blade الخاص به فقط.
  4. يقوم جافاسكريبت Livewire في المتصفح بمقارنة الـ HTML الجديد بالقديم وتحديث الأجزاء المتغيرة فقط وبذكاء (DOM Diffing).

### 2. ما هو Filament؟
Filament هو نظام إدارة كامل (Admin Panel / Dashboard) مبني بالكامل فوق **Livewire** و **Alpine.js** و **Tailwind CSS**.
* بدلاً من كتابة قوالب HTML و Livewire يدوياً لكل عملية CRUD، يوفر لك Filament فئات PHP جاهزة تسمى **Resources** تُعرف من خلالها شكل الحقول والجداول، وهو يتولى بناء الواجهات والعمليات تلقائياً.

---

## 🛠️ الجزء الثاني: تشريح الـ CRUD الكامل في Filament (Resources)

في Filament، كل عملية CRUD ترتبط بموديل Eloquent ويتم تمثيلها بـ **Resource**. 
مثال: موديل `Activity` يقابله المتحكم الرئيسي `ActivityResource.php` ومجلد فرعي يحتوي على الصفحات الفردية (`ListActivities`, `CreateActivity`, `EditActivity`).

### 1. الفئة الرئيسية للـ Resource (`ActivityResource.php`)
تقوم الفئة بتعريف التكوين الأساسي للـ CRUD مثل الأيقونة، المجموعة، الترتيب، وشكل الاستمارات والجداول.

```php
namespace App\Filament\Resources;

use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityResource extends Resource
{
    // 1. تحديد الموديل المرتبط
    protected static ?string $model = Activity::class;

    // 2. إعدادات القائمة الجانبية (Navigation)
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'نشاط'; // الاسم المفرد بالعربية
    protected static ?string $pluralModelLabel = 'الأنشطة'; // اسم الجمع
    protected static ?string $navigationGroup = 'الأنشطة'; // الجروب في المنيو
    protected static ?int $navigationSort = 5; // ترتيب الظهور

    // 3. بناء استمارة الإدخال والتعديل (Form Schema)
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('العنوان')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('type_id')
                    ->label('نوع النشاط')
                    ->relationship('type', 'name') // ربط بعلاقة Eloquent مباشرة
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('weight_attendance')
                    ->label('وزن الحضور (%)')
                    ->numeric()
                    ->default(20)
                    ->rules([
                        // إضافة قواعد فحص مخصصة (Custom Validations)
                        fn ($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $total = intval($value) + intval($get('weight_tasks')) + intval($get('weight_evaluation'));
                            if ($total !== 100) {
                                $fail('مجموع أوزان الدرجات يجب أن يساوي 100%');
                            }
                        }
                    ]),
            ]);
    }

    // 4. بناء جدول عرض البيانات (Table Schema)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان النشاط')
                    ->searchable() // يتيح البحث بهذا العمود
                    ->sortable(), // يتيح الترتيب بهذا العمود

                Tables\Columns\TextColumn::make('type.name') // جلب حقل من علاقة
                    ->label('النوع'),

                Tables\Columns\TextColumn::make('weight_attendance')
                    ->label('وزن الحضور')
                    ->suffix('%'), // إضافة علامة % بعد الرقم
            ])
            ->filters([
                // الفلاتر لتصفية بيانات الجدول
                Tables\Filters\SelectFilter::make('type_id')
                    ->relationship('type', 'name')
                    ->label('تصفية بالنوع'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // زر التعديل المضمن
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(), // عمليات الحذف الجماعي
            ]);
    }
}
```

---

## ⚙️ الجزء الثالث: التخصيص المتقدم للـ CRUD (Advanced Customization)

كمطور ذو خبرة، ستحتاج حتماً للتحكم بمسار العمليات والأمن وتصفية البيانات:

### 1. تقييد استعلامات الجدول وصلاحيات البيانات (`getEloquentQuery`)
بشكل افتراضي، يعرض Filament كافة السجلات من قاعدة البيانات. لتصفية السجلات بناءً على دور المستخدم الحالي (مثال: مسئول نشاط يرى فقط الأنشطة المسندة إليه):

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
        return $query; // السوبر يرى كل شيء
    }

    if ($user->hasRole('activity_admin')) {
        // تصفية الاستعلام بناءً على علاقة المشرفين بالنشاط
        return $query->whereIn('id', $user->assignedActivities->pluck('id'));
    }

    return $query->whereRaw('1 = 0'); // حظر الوصول الباقي
}
```

### 2. التحكم في الصلاحيات والـ Guard (Policy Integration)
يعتمد Filament تلقائياً على Laravel Policies لحماية الـ Resources.
إذا كنت تريد حظر الوصول برمجياً مباشرة داخل الكود بدون Policy:

```php
public static function canViewAny(): bool
{
    // يرجع true ليسمح بالظهور والدخول، أو false للمنع التام
    return auth()->user()?->hasAnyRole(['super_admin', 'activity_admin']) ?? false;
}
```

---

## ⚡ الجزء الرابع: كيف تصنع صفحة تقرير مخصصة باستخدام Livewire؟

أحياناً لا يكون الـ CRUD كافياً وتحتاج لإنشاء لوحة تحكم مخصصة (مثل صفحة فلترة التقارير وتصديرها). هنا ندمج قوة **Livewire** مع **Filament** بإنشاء صفحة مخصصة (Custom Page).

### 1. هيكل صفحة مخصصة (`ActivityReport.php`)
تتكون من كود PHP للتحكم بالحالة والمنطق وقالب Blade للعرض.

```php
namespace App\Filament\Pages;

use App\Models\Activity;
use Filament\Pages\Page;

class ActivityReport extends Page
{
    // تعريف الأيقونة والمجموعة
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'تقرير الأنشطة المطور';
    protected static ?string $navigationLabel = 'تقرير الأنشطة المطور';
    protected static string $view = 'filament.pages.activity-report'; // ملف الـ Blade
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';

    // متغيرات الحالة (State) - أي تغيير فيها يعيد رندرة الصفحة فورياً
    public ?int $selectedActivityId = null;
    public array $reportData = [];

    // دالة التمهيد (تُشبه __construct أو دالة Controller العادية)
    public function mount(): void
    {
        $this->selectedActivityId = Activity::first()?->id;
        $this->loadReport();
    }

    // دالة سحرية (Hook) تنطلق تلقائياً عندما يتغير متغيرselectedActivityId في الفرونت إند
    public function updatedSelectedActivityId(): void
    {
        $this->loadReport(); // إعادة تحميل بيانات الجدول
    }

    public function loadReport(): void
    {
        if (!$this->selectedActivityId) {
            $this->reportData = [];
            return;
        }

        // منطق جلب البيانات المألوف في Laravel
        $this->reportData = Activity::find($this->selectedActivityId)
            ->enrollments()
            ->with('student')
            ->get()
            ->toArray();
    }

    // إرسال رد تحميل ملف (CSV Export)
    public function export()
    {
        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['المخدوم', 'النسبة']);
            foreach ($this->reportData as $row) {
                fputcsv($file, [$row['student']['full_name'], $row['score']]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'report.csv');
    }
}
```

### 2. قالب العرض والتفاعل في Blade (`activity-report.blade.php`)
تستخدم توجيهات Livewire الشهيرة للربط الثنائي للمدخلات (Data Binding) واستدعاء الدوال:

```html
<x-filament-panels::page>
    <div class="bg-white p-5 rounded-xl dark:bg-gray-950">
        {{-- ربط الـ Select مع المتغير selectedActivityId بصفة تفاعلية حية --}}
        <select wire:model.live="selectedActivityId" class="rounded-lg border-gray-300">
            <option value="">اختر نشاط</option>
            @foreach($activities as $act)
                <option value="{{ $act['id'] }}">{{ $act['title'] }}</option>
            @endforeach
        </select>

        {{-- استدعاء دالة التصدير برمجياً بضغطة زر دون ريفريش --}}
        <button wire:click="export" class="bg-orange-500 text-white px-4 py-2 rounded-lg">
            تصدير إكسيل
        </button>
    </div>

    {{-- جدول عرض البيانات الديناميكي --}}
    <table class="w-full mt-6">
        <thead>
            <tr>
                <th>المخدوم</th>
                <th>الدرجة النهائية</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
                <tr>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['final_score'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-filament-panels::page>
```

---

## 🔍 الجزء الخامس: قاموس المفاهيم والروابط السريعة (Cheat Sheet)

* **`wire:model.live`**: يقوم بربط قيمة المدخل (Input) بمتغير PHP في المكون، ويقوم بإرسال طلب وتحديث الصفحة **فوراً** عند التغيير.
* **`wire:click="methodName"`**: يستدعي دالة PHP في المكون بدون إعادة تحميل كامل الصفحة.
* **`mount()`**: تعمل مرة واحدة فقط عند تحميل الصفحة لتهيئة الحالات الافتراضية.
* **`updated{PropertyName}()`**: طريقة لمراقبة التغيير في المتغيرات وتنفيذ منطق مخصص (Watcher).
* **`canViewAny()` / `canCreate()`**: بوابات التحقق من الصلاحيات (Authorization Gates).
* **`getPages()`**: دالة توجيهية داخل الـ Resource لتسجيل مسارات الصفحات.
* **`Table` / `Form` Object**: مكونات البناء الأساسية التفاعلية لـ Filament.
