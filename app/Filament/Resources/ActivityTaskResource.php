<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityTaskResource\Pages;
use App\Models\ActivityTask;
use App\Models\ActivityEnrollment;
use App\Models\ActivityTaskScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityTaskResource extends Resource
{
    protected static ?string $model = ActivityTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $modelLabel = 'مهمة نشاط';

    protected static ?string $pluralModelLabel = 'مهام الأنشطة';

    protected static ?string $navigationGroup = 'الأنشطة';

    protected static ?int $navigationSort = 2;

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
                Forms\Components\Section::make('بيانات المهمة')
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
                                    ->where('status', 'qualified')
                                    ->with('enrollment.student')
                                    ->get()
                                    ->sortBy(fn ($e) => $e->enrollment?->student?->full_name ?? '');

                                $scores = $enrollments->map(fn ($enrollment) => [
                                    'activity_enrollment_id' => $enrollment->id,
                                    'student_name' => $enrollment->enrollment->student->full_name ?? '—',
                                    'score' => 0,
                                ])->toArray();

                                $set('taskScores', $scores);
                            }),
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان المهمة')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('max_score')
                            ->label('الدرجة النهائية')
                            ->numeric()
                            ->default(100)
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('رصد درجات المهمة')
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

                        Forms\Components\Repeater::make('taskScores')
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان المهمة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('activity.title')
                    ->label('النشاط')
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
                Tables\Filters\SelectFilter::make('activity_id')
                    ->label('النشاط')
                    ->relationship('activity', 'title')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListActivityTasks::route('/'),
            'create' => Pages\CreateActivityTask::route('/create'),
            'edit' => Pages\EditActivityTask::route('/{record}/edit'),
        ];
    }
}
