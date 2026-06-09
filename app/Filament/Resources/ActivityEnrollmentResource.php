<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityEnrollmentResource\Pages;
use App\Models\ActivityEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Models\StudentSeasonEnrollment;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;

class ActivityEnrollmentResource extends Resource
{
    protected static ?string $model = ActivityEnrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $modelLabel = 'تسجيل نشاط';

    protected static ?string $pluralModelLabel = 'تسجيلات الأنشطة';

    protected static ?string $navigationGroup = 'الحضور والأنشطة';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'activity_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_season_enrollment_id')
                    ->label('المخدوم')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        $query = StudentSeasonEnrollment::query()
                            ->whereHas('student', function ($sq) use ($search) {
                                $sq->where('full_name', 'like', "%{$search}%");
                            });

                        if (!auth()->user()->hasRole('super_admin')) {
                            $query->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
                        }

                        $activeSeason = Season::active();
                        if ($activeSeason) {
                            $query->where('season_id', $activeSeason->id);
                        }

                        return $query->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($record) => [
                                $record->id => $record->student->full_name . ($record->class ? ' - ' . $record->class->name : '')
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $record = StudentSeasonEnrollment::with(['student', 'class'])->find($value);
                        if (!$record) {
                            return null;
                        }
                        return $record->student->full_name . ($record->class ? ' - ' . $record->class->name : '');
                    })
                    ->required(),
                Forms\Components\Select::make('activity_id')
                    ->label('النشاط')
                    ->relationship(
                        name: 'activity',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn ($query) => auth()->user()->hasRole('super_admin')
                            ? $query
                            : $query->whereIn('id', auth()->user()->assignedActivities->pluck('id'))
                    )
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'qualified' => 'متأهل',
                        'disqualified' => 'غير متأهل',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.full_name')->label('المخدوم')->searchable(),
                Tables\Columns\TextColumn::make('activity.title')->label('النشاط'),
                Tables\Columns\SelectColumn::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'qualified' => 'متأهل',
                        'disqualified' => 'غير متأهل',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('activity')->relationship('activity', 'title')->label('النشاط'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'قيد الانتظار',
                    'qualified' => 'متأهل',
                    'disqualified' => 'غير متأهل',
                ])->label('الحالة'),
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
            'index' => Pages\ListActivityEnrollments::route('/'),
            'create' => Pages\CreateActivityEnrollment::route('/create'),
            'edit' => Pages\EditActivityEnrollment::route('/{record}/edit'),
        ];
    }
}
