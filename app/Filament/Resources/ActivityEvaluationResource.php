<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityEvaluationResource\Pages;
use App\Models\ActivityEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityEvaluationResource extends Resource
{
    protected static ?string $model = ActivityEvaluation::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $modelLabel = 'تقييم نشاط نهائي';
    protected static ?string $pluralModelLabel = 'التقييمات النهائية للأنشطة';
    protected static ?string $navigationGroup = 'الأنشطة';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'activity_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'activity_admin']) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'activity_admin']) ?? false;
    }

    public static function canDelete($record): bool
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
            if ($record && $record->activityEnrollment?->enrollment?->student) {
                $studentName = $record->activityEnrollment->enrollment->student->full_name;
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
                Forms\Components\Select::make('activity_id')
                    ->label('النشاط')
                    ->options(function ($record) {
                        $query = \App\Models\Activity::query();
                        
                        $activeSeason = \App\Models\Season::active();
                        if ($activeSeason) {
                            $query->where('season_id', $activeSeason->id);
                        }

                        if (!auth()->user()->hasRole('super_admin')) {
                            $query->whereIn('id', auth()->user()->assignedActivities->pluck('id'));
                        }

                        // Prevent duplicate evaluations (only show activities without an evaluation)
                        $query->where(function ($q) use ($record) {
                            $q->whereNotExists(function ($sub) {
                                $sub->selectRaw('1')
                                    ->from('activity_evaluations')
                                    ->whereColumn('activity_evaluations.activity_id', 'activities.id');
                            });
                            if ($record && $record->activity_id) {
                                $q->orWhere('id', $record->activity_id);
                            }
                        });

                        return $query->pluck('title', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if (!$state) return;

                        $enrollments = \App\Models\ActivityEnrollment::where('activity_id', $state)
                            ->with('enrollment.student')
                            ->get()
                            ->sortBy(fn ($ae) => $ae->enrollment?->student?->full_name);

                        $scores = $enrollments->map(fn ($ae) => [
                            'activity_enrollment_id' => $ae->id,
                            'student_name' => $ae->enrollment?->student?->full_name ?? '—',
                            'raw_score' => 0,
                            'notes' => null,
                        ])->toArray();

                        $set('scores', $scores);
                    }),

                Forms\Components\TextInput::make('max_score')
                    ->label('الدرجة النهائية (من كان)')
                    ->numeric()
                    ->required()
                    ->default(100)
                    ->live(),

                Forms\Components\DatePicker::make('date')
                    ->label('التاريخ')
                    ->required()
                    ->default(now()),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات عامة')
                    ->columnSpanFull(),

                Forms\Components\Section::make('رصد تقييمات المخدومين')
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
                                    <div style="flex: 8; text-align: right;">المخدوم</div>
                                    <div style="flex: 4; text-align: left; padding-left: 20px;">الدرجة</div>
                                </div>
                            ')),

                        Forms\Components\Repeater::make('scores')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\Hidden::make('activity_enrollment_id'),
                                        Forms\Components\Hidden::make('student_name'),
                                        Forms\Components\Placeholder::make('student_name')
                                            ->hiddenLabel()
                                            ->content(function ($record, $get) {
                                                if ($record && $record->activityEnrollment?->enrollment?->student) {
                                                    return $record->activityEnrollment->enrollment->student->full_name;
                                                }
                                                return $get('student_name') ?? '—';
                                            })
                                            ->columnSpan(8),
                                        Forms\Components\TextInput::make('raw_score')
                                            ->hiddenLabel()
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(fn (Forms\Get $get) => $get('../../max_score') ?? 100)
                                            ->columnSpan(4),
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
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activity.title')
                    ->label('النشاط')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_score')
                    ->label('الدرجة النهائية')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('export_scores')
                    ->label('تصدير كشيت درجات')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        $enrollments = \App\Models\ActivityEnrollment::where('activity_id', $record->activity_id)
                            ->with('enrollment.student')
                            ->get()
                            ->sortBy(fn ($ae) => $ae->enrollment?->student?->full_name);

                        $headers = [
                            'student_code' => 'كود المخدوم',
                            'student_name' => 'اسم المخدوم',
                            'score' => 'الدرجة',
                        ];

                        $callback = function () use ($enrollments, $headers, $record) {
                            $file = fopen('php://output', 'w');
                            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                            fputcsv($file, array_values($headers));

                            foreach ($enrollments as $ae) {
                                $existingScore = \App\Models\ActivityScore::where('activity_evaluation_id', $record->id)
                                    ->where('activity_enrollment_id', $ae->id)
                                    ->first();

                                fputcsv($file, [
                                    $ae->enrollment?->student?->code ?? '',
                                    $ae->enrollment?->student?->full_name ?? '',
                                    $existingScore ? $existingScore->raw_score : 0,
                                ]);
                            }
                            fclose($file);
                        };

                        $fileName = 'activity_evaluation_scores_' . str_replace(' ', '_', $record->activity->title) . '.csv';
                        return response()->stream($callback, 200, [
                            'Content-Type' => 'text/csv; charset=utf-8',
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    }),

                Tables\Actions\Action::make('import_scores')
                    ->label('استيراد درجات')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('اختر ملف CSV')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain']),
                    ])
                    ->action(function ($record, array $data) {
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

                        $successCount = 0;
                        $errorsCount = 0;

                        while (($row = fgetcsv($file)) !== false) {
                            if (count($row) < 3) continue;

                            $studentCode = trim($row[0]);
                            if (str_ends_with($studentCode, '.0')) {
                                $studentCode = substr($studentCode, 0, -2);
                            }
                            if (is_numeric($studentCode)) {
                                $studentCode = strval(intval($studentCode));
                            }
                            $rawScore = floatval(trim($row[2]));

                            $student = \App\Models\Student::where('code', $studentCode)->first();
                            if (!$student) {
                                $errorsCount++;
                                continue;
                            }

                            $seasonEnrollment = \App\Models\StudentSeasonEnrollment::where('student_id', $student->id)
                                ->where('season_id', $activeSeason->id)
                                ->first();

                            if (!$seasonEnrollment) {
                                $errorsCount++;
                                continue;
                            }

                            $activityEnrollment = \App\Models\ActivityEnrollment::where('student_season_enrollment_id', $seasonEnrollment->id)
                                ->where('activity_id', $record->activity_id)
                                ->first();

                            if (!$activityEnrollment) {
                                $errorsCount++;
                                continue;
                            }

                            \App\Models\ActivityScore::updateOrCreate([
                                'activity_evaluation_id' => $record->id,
                                'activity_enrollment_id' => $activityEnrollment->id,
                            ], [
                                'raw_score' => $rawScore,
                            ]);

                            $successCount++;
                        }
                        fclose($file);

                        \Filament\Notifications\Notification::make()
                            ->title('اكتمل استيراد الدرجات')
                            ->body("تم بنجاح استيراد وتحديث درجات {$successCount} مخدوم. الأخطاء: {$errorsCount}")
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        $activeSeason = \App\Models\Season::active();

        if ($activeSeason) {
            $query->whereHas('activity', function ($q) use ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            });
        }

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query->where(function ($subQuery) use ($user) {
            if ($user->hasRole('activity_admin')) {
                $subQuery->orWhereIn('activity_id', $user->assignedActivities->pluck('id'));
            }

            if ($user->hasAnyRole(['class_admin', 'class_servant'])) {
                $assignedClassIds = $user->assignedClasses->pluck('id');
                $subQuery->orWhereHas('activity.enrollments.enrollment', function ($q) use ($assignedClassIds) {
                    $q->whereIn('class_id', $assignedClassIds);
                });
            }
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityEvaluations::route('/'),
            'create' => Pages\CreateActivityEvaluation::route('/create'),
            'edit' => Pages\EditActivityEvaluation::route('/{record}/edit'),
        ];
    }
}
