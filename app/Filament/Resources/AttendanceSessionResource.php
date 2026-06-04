<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceSessionResource\Pages;
use App\Filament\Resources\AttendanceSessionResource\RelationManagers;
use App\Models\AttendanceSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceSessionResource extends Resource
{
    protected static ?string $model = AttendanceSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'يوم حضور';

    protected static ?string $pluralModelLabel = 'أيام الحضور';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات اليوم')
                    ->schema([
                        Forms\Components\Hidden::make('season_id')
                            ->default(fn () => \App\Models\Season::active()?->id),
                        Forms\Components\Select::make('class_id')
                            ->label('الفصل')
                            ->relationship(
                                name: 'class',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => auth()->user()->hasRole('super_admin')
                                    ? $query
                                    : $query->whereIn('id', auth()->user()->assignedClasses->pluck('id'))
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;

                                $activeSeasonId = \App\Models\Season::active()?->id;
                                if (!$activeSeasonId) return;

                                $enrollments = \App\Models\StudentSeasonEnrollment::where('class_id', $state)
                                    ->where('season_id', $activeSeasonId)
                                    ->with('student')
                                    ->get();

                                $attendances = $enrollments->map(fn ($enrollment) => [
                                    'student_season_enrollment_id' => $enrollment->id,
                                    'student_name' => $enrollment->student->full_name,
                                    'status' => 'present',
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

                Forms\Components\Section::make('تسجيل الحضور')
                    ->schema([
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
                                Forms\Components\Hidden::make('student_season_enrollment_id'),
                                Forms\Components\Placeholder::make('student_name')
                                    ->hiddenLabel()
                                    ->content(function ($record, $get) {
                                        if ($record && $record->enrollment?->student) {
                                            return $record->enrollment->student->full_name;
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
                                    ->default('present')
                                    ->columnSpan(6),
                            ])
                            ->columns(12)
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
                Tables\Columns\TextColumn::make('class.name')
                    ->label('الفصل')
                    ->sortable(),
                Tables\Columns\TextColumn::make('season.name')
                    ->label('الموسم')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('إجمالي المخدومين')
                    ->sortable(),
                Tables\Columns\TextColumn::make('present_attendances_count')
                    ->label('حاضر')
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('excused_attendances_count')
                    ->label('معتذر')
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('absent_attendances_count')
                    ->label('غائب')
                    ->color('danger')
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_template')
                    ->label('تحميل نموذج الحضور (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('class_id')
                            ->label('الفصل')
                            ->relationship('class', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (array $data) {
                        $classId = $data['class_id'];
                        $date = $data['date'];
                        
                        $activeSeason = \App\Models\Season::active();
                        if (!$activeSeason) return;

                        $enrollments = \App\Models\StudentSeasonEnrollment::where('class_id', $classId)
                            ->where('season_id', $activeSeason->id)
                            ->with('student')
                            ->get();

                        $headers = [
                            'student_code' => 'كود المخدوم',
                            'student_name' => 'اسم المخدوم',
                            'date' => 'التاريخ',
                            'status' => 'الحالة (present, absent, excused)',
                        ];

                        $callback = function () use ($enrollments, $headers, $date) {
                            $file = fopen('php://output', 'w');
                            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                            
                            fputcsv($file, array_values($headers));

                            foreach ($enrollments as $enrollment) {
                                fputcsv($file, [
                                    $enrollment->student->code,
                                    $enrollment->student->full_name,
                                    $date,
                                    'present',
                                ]);
                            }
                            fclose($file);
                        };

                        $fileName = 'attendance_template_' . $date . '.csv';
                        return response()->stream($callback, 200, [
                            'Content-Type' => 'text/csv; charset=utf-8',
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    }),
                    
                Tables\Actions\Action::make('import_attendance')
                    ->label('استيراد حضور (CSV)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('اختر ملف CSV')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain']),
                    ])
                    ->action(function (array $data) {
                        $filePath = storage_path('app/public/' . $data['file']);
                        
                        if (!file_exists($filePath)) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل الاستيراد')
                                ->body('لم يتم العثور على الملف المرفوع.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $activeSeason = \App\Models\Season::active();
                        if (!$activeSeason) return;

                        $file = fopen($filePath, 'r');
                        
                        $bom = fread($file, 3);
                        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                            rewind($file);
                        }

                        $headers = fgetcsv($file);

                        $sessionCreated = false;
                        $attendanceSession = null;
                        $successCount = 0;
                        $errorsCount = 0;

                        while (($row = fgetcsv($file)) !== false) {
                            if (count($row) < 4) continue;

                            $studentCode = trim($row[0]);
                            $date = trim($row[2]);
                            $status = trim($row[3]);

                            if (!in_array($status, ['present', 'absent', 'excused'])) {
                                $status = 'present';
                            }

                            $student = \App\Models\Student::where('code', $studentCode)->first();
                            if (!$student) {
                                $errorsCount++;
                                continue;
                            }

                            $enrollment = \App\Models\StudentSeasonEnrollment::where('student_id', $student->id)
                                ->where('season_id', $activeSeason->id)
                                ->first();

                            if (!$enrollment) {
                                $errorsCount++;
                                continue;
                            }

                            if (!$sessionCreated) {
                                $attendanceSession = \App\Models\AttendanceSession::firstOrCreate([
                                    'season_id' => $activeSeason->id,
                                    'class_id' => $enrollment->class_id,
                                    'date' => $date,
                                ], [
                                    'notes' => 'مستورد تلقائياً من شيت إكسيل',
                                ]);
                                $sessionCreated = true;
                            }

                            \App\Models\Attendance::updateOrCreate([
                                'attendance_session_id' => $attendanceSession->id,
                                'student_season_enrollment_id' => $enrollment->id,
                            ], [
                                'status' => $status,
                            ]);

                            $successCount++;
                        }
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
        $activeSeason = \App\Models\Season::active();

        return parent::getEloquentQuery()
            ->withCount([
                'attendances',
                'attendances as present_attendances_count' => function ($query) {
                    $query->where('status', 'present');
                },
                'attendances as excused_attendances_count' => function ($query) {
                    $query->where('status', 'excused');
                },
                'attendances as absent_attendances_count' => function ($query) {
                    $query->where('status', 'absent');
                },
            ])
            ->when($activeSeason, function ($query) use ($activeSeason) {
                $query->where('season_id', $activeSeason->id);
            })
            ->when(!auth()->user()->hasRole('super_admin'), function ($query) {
                $query->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceSessions::route('/'),
            'create' => Pages\CreateAttendanceSession::route('/create'),
            'edit' => Pages\EditAttendanceSession::route('/{record}/edit'),
        ];
    }
}
