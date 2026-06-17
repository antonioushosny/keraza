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
                                    ->where('status', 'qualified')
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
