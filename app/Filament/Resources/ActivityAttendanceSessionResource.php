<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityAttendanceSessionResource\Pages;
use App\Models\ActivityAttendanceSession;
use App\Models\ActivityEnrollment;
use App\Models\ActivityAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityAttendanceSessionResource extends Resource
{
    protected static ?string $model = ActivityAttendanceSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'حضور نشاط';

    protected static ?string $pluralModelLabel = 'حضور الأنشطة';

    protected static ?string $navigationGroup = 'الأنشطة';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'activity_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        $shouldHide = function ($get, $record) {
            $search = $get('../../student_search');
            if (blank($search)) {
                return false;
            }

            $studentName = '';
            if ($record && $record->enrollment?->enrollment?->student) {
                $studentName = $record->enrollment->enrollment->student->full_name;
            } else {
                $studentName = $get('student_name') ?? '';
            }

            $normalize = function ($str) {
                $str = trim($str);
                $str = str_replace(['أ', 'إ', 'آ'], 'ا', $str);
                $str = str_replace('ة', 'ه', $str);
                $str = str_replace('ى', 'ي', $str);
                return $str;
            };

            return !str_contains($normalize($studentName), $normalize($search));
        };

        return $form
            ->schema([
                Forms\Components\Section::make('بيانات يوم حضور النشاط')
                    ->schema([
                        Forms\Components\Select::make('activity_id')
                            ->label('النشاط')
                            ->relationship(
                                name: 'activity',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn ($query) => auth()->user()->hasRole('super_admin')
                                    ? $query
                                    : $query->whereIn('id', auth()->user()->assignedActivities->pluck('id'))
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;

                                $enrollments = ActivityEnrollment::where('activity_id', $state)
                                    // ->where('status', 'qualified')
                                    ->with('enrollment.student')
                                    ->get()
                                    ->sortBy(fn ($e) => $aeName = $e->enrollment?->student?->full_name ?? '');

                                $attendances = $enrollments->map(fn ($enrollment) => [
                                    'activity_enrollment_id' => $enrollment->id,
                                    'student_name' => $enrollment->enrollment->student->full_name ?? '—',
                                    'status' => 'absent',
                                ])->toArray();

                                $set('attendances', $attendances);
                            }),
                        Forms\Components\DatePicker::make('date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('تسجيل حضور المشتركين')
                    ->schema([
                        Forms\Components\TextInput::make('student_search')
                            ->label('بحث بالاسم')
                            ->placeholder('اكتب اسم المخدوم للبحث...')
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('search_style')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <style>
                                    .fi-fo-repeater-item:has([data-search-hidden="true"]),
                                    .filament-forms-repeater-item:has([data-search-hidden="true"]) {
                                        display: none !important;
                                    }
                                </style>
                            ')),

                        Forms\Components\Placeholder::make('headers')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div style="display: flex; direction: rtl; font-weight: bold; font-size: 0.875rem; border-bottom: 1px solid rgba(156, 163, 175, 0.3); padding-bottom: 8px; margin-bottom: 12px; color: #9ca3af; padding-left: 16px; padding-right: 16px;">
                                    <div style="flex: 6; text-align: right;">المخدوم</div>
                                    <div style="flex: 6; text-align: left; padding-left: 20px;">الحالة</div>
                                </div>
                            ')),

                        Forms\Components\Repeater::make('attendances')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\Hidden::make('activity_enrollment_id'),
                                        Forms\Components\Hidden::make('student_name'),
                                        Forms\Components\Placeholder::make('student_name')
                                            ->hiddenLabel()
                                            ->content(function ($record, $get) {
                                                if ($record && $record->enrollment?->enrollment?->student) {
                                                    return $record->enrollment->enrollment->student->full_name;
                                                }
                                                return $get('student_name') ?? '—';
                                            })
                                            ->columnSpan(6),
                                        Forms\Components\Radio::make('status')
                                            ->hiddenLabel()
                                            ->options([
                                                'present' => 'حاضر',
                                                'absent' => 'غائب',
                                                'excused' => 'معتذر',
                                            ])
                                            ->inline()
                                            ->required()
                                            ->default('absent')
                                            ->columnSpan(6),
                                    ])
                                    ->extraAttributes(function ($get, $record) use ($shouldHide) {
                                        if ($shouldHide($get, $record)) {
                                            return [
                                                'data-search-hidden' => 'true',
                                            ];
                                        }
                                        return [];
                                    }),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->hiddenLabel(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('activity.title')
                    ->label('النشاط')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('إجمالي الحضور')
                    ->state(fn ($record) => $record->attendances()->count())
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('activity_id')
                    ->label('النشاط')
                    ->relationship('activity', 'title')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_activity_template')
                    ->label('تحميل نموذج الحضور (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('activity_id')
                            ->label('النشاط')
                            ->options(function () {
                                $query = \App\Models\Activity::query();
                                if (!auth()->user()->hasRole('super_admin')) {
                                    $query->whereIn('id', auth()->user()->assignedActivities->pluck('id'));
                                }
                                return $query->pluck('title', 'id')->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('اختر النشاط'),
                        Forms\Components\DatePicker::make('date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (array $data) {
                        $activityId = $data['activity_id'];
                        $date = $data['date'];

                        $enrollments = ActivityEnrollment::where('activity_id', $activityId)
                            ->with('enrollment.student')
                            ->get()
                            ->sortBy(fn ($e) => $e->enrollment?->student?->full_name ?? '');

                        $session = ActivityAttendanceSession::where('activity_id', $activityId)
                            ->whereDate('date', $date)
                            ->first();

                        $existingAttendances = [];
                        if ($session) {
                            $existingAttendances = ActivityAttendance::where('activity_attendance_session_id', $session->id)
                                ->pluck('status', 'activity_enrollment_id')
                                ->toArray();
                        }

                        $headers = [
                            'student_code' => 'كود المخدوم',
                            'student_name' => 'اسم المخدوم',
                            'date'         => 'التاريخ',
                            'status'       => 'الحالة (present, absent, excused)',
                        ];

                        $callback = function () use ($enrollments, $headers, $date, $existingAttendances) {
                            $file = fopen('php://output', 'w');
                            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                            fputcsv($file, array_values($headers));

                            foreach ($enrollments as $enrollment) {
                                $student = $enrollment->enrollment?->student;
                                if (!$student) continue;
                                $status = $existingAttendances[$enrollment->id] ?? 'absent';
                                fputcsv($file, [
                                    $student->code,
                                    $student->full_name,
                                    $date,
                                    $status,
                                ]);
                            }
                            fclose($file);
                        };

                        $activity = \App\Models\Activity::find($activityId);
                        $activitySlug = $activity ? \Illuminate\Support\Str::slug($activity->title, '_') : $activityId;
                        $fileName = 'activity_attendance_' . $activitySlug . '_' . $date . '.csv';

                        return response()->stream($callback, 200, [
                            'Content-Type'        => 'text/csv; charset=utf-8',
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    }),

                Tables\Actions\Action::make('import_activity_attendance')
                    ->label('استيراد حضور (CSV)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('activity_id')
                            ->label('النشاط')
                            ->options(function () {
                                $query = \App\Models\Activity::query();
                                if (!auth()->user()->hasRole('super_admin')) {
                                    $query->whereIn('id', auth()->user()->assignedActivities->pluck('id'));
                                }
                                return $query->pluck('title', 'id')->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('اختر النشاط'),
                        Forms\Components\FileUpload::make('file')
                            ->label('اختر ملف CSV')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                            ->helperText('استخدم الملف المُحمَّل من زر "تحميل نموذج الحضور" لضمان التنسيق الصحيح.'),
                    ])
                    ->action(function (array $data) {
                        $selectedActivityId = $data['activity_id'];

                        $fileState = $data['file'];
                        $fileName = is_array($fileState) ? (\Illuminate\Support\Arr::first($fileState) ?? '') : $fileState;
                        $filePath = storage_path('app/public/' . $fileName);

                        if (!file_exists($filePath)) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل الاستيراد')
                                ->body('لم يتم العثور على الملف المرفوع.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $file = fopen($filePath, 'r');

                        // Strip BOM if present
                        $bom = fread($file, 3);
                        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                            rewind($file);
                        }

                        // Skip header row
                        fgetcsv($file);

                        $successCount = 0;
                        $errorsCount  = 0;

                        \Illuminate\Support\Facades\DB::transaction(function () use ($file, $selectedActivityId, &$successCount, &$errorsCount) {
                            $sessions = [];

                            while (($row = fgetcsv($file)) !== false) {
                                if (count($row) < 4) continue;

                                $studentCode = trim($row[0]);
                                if (str_ends_with($studentCode, '.0')) {
                                    $studentCode = substr($studentCode, 0, -2);
                                }
                                if (is_numeric($studentCode)) {
                                    $studentCode = strval(intval($studentCode));
                                }

                                $dateStr = trim($row[2]);
                                $status  = trim($row[3]);

                                // Normalize Arabic numerals in date
                                $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
                                $num    = ['0','1','2','3','4','5','6','7','8','9'];
                                $dateStr = str_replace($arabic, $num, $dateStr);
                                $cleanedDate = str_replace(['.', '/'], '-', $dateStr);

                                $date = null;
                                $parsedTime = strtotime($cleanedDate);
                                if ($parsedTime !== false) {
                                    $date = date('Y-m-d', $parsedTime);
                                } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $cleanedDate)) {
                                    $date = $cleanedDate;
                                }

                                if (!$date) {
                                    $errorsCount++;
                                    continue;
                                }

                                if (!in_array($status, ['present', 'absent', 'excused'])) {
                                    $status = 'absent';
                                }

                                // Find student by code
                                $student = \App\Models\Student::where('code', $studentCode)->first();
                                if (!$student) {
                                    $errorsCount++;
                                    continue;
                                }

                                $activeSeason = \App\Models\Season::active();
                                if (!$activeSeason) {
                                    $errorsCount++;
                                    continue;
                                }

                                // Find student's season enrollment
                                $seasonEnrollment = \App\Models\StudentSeasonEnrollment::where('student_id', $student->id)
                                    ->where('season_id', $activeSeason->id)
                                    ->first();

                                if (!$seasonEnrollment) {
                                    $errorsCount++;
                                    continue;
                                }

                                // Find activity enrollment for this student in the selected activity
                                $activityEnrollment = ActivityEnrollment::where('student_season_enrollment_id', $seasonEnrollment->id)
                                    ->where('activity_id', $selectedActivityId)
                                    ->first();

                                if (!$activityEnrollment) {
                                    $errorsCount++;
                                    continue;
                                }

                                $sessionKey = $selectedActivityId . '_' . $date;

                                if (!isset($sessions[$sessionKey])) {
                                    $sessions[$sessionKey] = ActivityAttendanceSession::firstOrCreate([
                                        'activity_id' => $selectedActivityId,
                                        'date'        => $date,
                                    ], [
                                        'notes' => 'مستورد تلقائياً من شيت إكسيل',
                                    ]);
                                }

                                $attendanceSession = $sessions[$sessionKey];

                                ActivityAttendance::updateOrCreate([
                                    'activity_attendance_session_id' => $attendanceSession->id,
                                    'activity_enrollment_id'         => $activityEnrollment->id,
                                ], [
                                    'status' => $status,
                                ]);

                                $successCount++;
                            }
                        });

                        fclose($file);

                        \Filament\Notifications\Notification::make()
                            ->title('اكتمل الاستيراد')
                            ->body("تم بنجاح تسجيل حضور {$successCount} مخدوم. الأخطاء: {$errorsCount}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->user()->hasRole('super_admin')) {
            return $query;
        }

        if (auth()->user()->hasRole('activity_admin')) {
            $assignedActivityIds = auth()->user()->assignedActivities->pluck('id');
            return $query->whereIn('activity_id', $assignedActivityIds);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityAttendanceSessions::route('/'),
            'create' => Pages\CreateActivityAttendanceSession::route('/create'),
            'edit' => Pages\EditActivityAttendanceSession::route('/{record}/edit'),
        ];
    }
}
