<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemorizationItemResource\Pages;
use App\Models\MemorizationItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MemorizationItemResource extends Resource
{
    protected static ?string $model = MemorizationItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $modelLabel = 'بند تسميع';
    protected static ?string $pluralModelLabel = 'بنود التسميع';
    protected static ?string $navigationGroup = 'الامتحانات والتسميع';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

    public static function form(Form $form): Form
    {
        $shouldHide = function ($get, $record) {
            $search = $get('../../student_search');
            if (blank($search)) {
                return false;
            }

            $studentName = '';
            if ($record && $record->enrollment?->student) {
                $studentName = $record->enrollment->student->full_name;
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
                Forms\Components\Hidden::make('season_id')
                    ->default(fn () => \App\Models\Season::active()?->id),
                Forms\Components\Select::make('class_id')
                    ->label('المرحلة')
                    ->relationship(
                        name: 'class',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => auth()->user()->hasRole('super_admin')
                            ? $query
                            : $query->whereIn('id', auth()->user()->assignedClasses->pluck('id'))
                    )
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if (!$state) return;
                        
                        $activeSeasonId = \App\Models\Season::active()?->id;
                        if (!$activeSeasonId) return;

                        $enrollments = \App\Models\StudentSeasonEnrollment::where('class_id', $state)
                            ->where('season_id', $activeSeasonId)
                            ->with('student')
                            ->get()
                            ->sortBy(fn ($e) => $e->student?->full_name);

                        $scores = $enrollments->map(fn ($enrollment) => [
                            'student_season_enrollment_id' => $enrollment->id,
                            'student_name' => $enrollment->student->full_name,
                            'score' => 0,
                            'accuracy' => 100,
                        ])->toArray();

                        $set('scores', $scores);
                    }),
                Forms\Components\TextInput::make('title')
                    ->label('العنوان (مثلاً: المزمور الأول)')
                    ->required(),
                Forms\Components\TextInput::make('max_points')
                    ->label('الدرجة القصوى')
                    ->numeric()
                    ->default(100),

                Forms\Components\Section::make('رصد تسميع المخدومين')
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
                                        Forms\Components\Hidden::make('student_season_enrollment_id'),
                                        Forms\Components\Hidden::make('student_name'),
                                        Forms\Components\Placeholder::make('student_name')
                                            ->hiddenLabel()
                                            ->content(function ($record, $get) {
                                                if ($record && $record->enrollment?->student) {
                                                    return $record->enrollment->student->full_name;
                                                }
                                                return $get('student_name') ?? '—';
                                            })
                                            ->columnSpan(8),
                                        Forms\Components\TextInput::make('score')
                                            ->hiddenLabel()
                                            ->numeric()
                                            ->required()
                                            ->default(0)
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
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable(),
                Tables\Columns\TextColumn::make('class.name')->label('المرحلة'),
                Tables\Columns\TextColumn::make('max_points')->label('الدرجة القصوى'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class')
                    ->relationship('class', 'name')
                    ->label('المرحلة')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('export_scores')
                    ->label('تصدير كشيت تسميع')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        $activeSeason = \App\Models\Season::active();
                        if (!$activeSeason) return;

                        $enrollments = \App\Models\StudentSeasonEnrollment::where('class_id', $record->class_id)
                            ->where('season_id', $activeSeason->id)
                            ->with('student')
                            ->get()
                            ->sortBy(fn ($e) => $e->student?->full_name);

                        $headers = [
                            'student_code' => 'كود المخدوم',
                            'student_name' => 'اسم المخدوم',
                            'score' => 'الدرجة',
                        ];

                        $callback = function () use ($enrollments, $headers, $record) {
                            $file = fopen('php://output', 'w');
                            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                            fputcsv($file, array_values($headers));

                            foreach ($enrollments as $enrollment) {
                                $existingScore = \App\Models\MemorizationScore::where('memorization_item_id', $record->id)
                                    ->where('student_season_enrollment_id', $enrollment->id)
                                    ->first();

                                fputcsv($file, [
                                    $enrollment->student->code,
                                    $enrollment->student->full_name,
                                    $existingScore ? $existingScore->score : 0,
                                ]);
                            }
                            fclose($file);
                        };

                        $fileName = 'memorization_' . str_replace(' ', '_', $record->title) . '.csv';
                        return response()->stream($callback, 200, [
                            'Content-Type' => 'text/csv; charset=utf-8',
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    }),

                Tables\Actions\Action::make('import_scores')
                    ->label('استيراد كشيت تسميع')
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
                            $score = floatval(trim($row[2]));

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

                            \App\Models\MemorizationScore::updateOrCreate([
                                'memorization_item_id' => $record->id,
                                'student_season_enrollment_id' => $enrollment->id,
                            ], [
                                'score' => $score,
                                'accuracy' => $score,
                            ]);

                            $successCount++;
                        }
                        fclose($file);

                        \Filament\Notifications\Notification::make()
                            ->title('اكتمل استيراد كشيت التسميع')
                            ->body("تم بنجاح تحديث حالة تسميع {$successCount} مخدوم. الأخطاء: {$errorsCount}")
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
        $activeSeason = \App\Models\Season::active();

        return parent::getEloquentQuery()
            ->when($activeSeason, function ($query) use ($activeSeason) {
                $query->where('season_id', $activeSeason->id);
            })
            ->when(!auth()->user()->hasRole('super_admin'), function ($query) {
                $query->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemorizationItems::route('/'),
            'create' => Pages\CreateMemorizationItem::route('/create'),
            'edit' => Pages\EditMemorizationItem::route('/{record}/edit'),
        ];
    }
}
