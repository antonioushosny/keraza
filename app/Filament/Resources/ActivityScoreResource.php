<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityScoreResource\Pages;
use App\Models\ActivityScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Models\ActivityEnrollment;

class ActivityScoreResource extends Resource
{
    protected static ?string $model = ActivityScore::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $modelLabel = 'تقييم نشاط نهائي';
    protected static ?string $pluralModelLabel = 'التقييمات النهائية للأنشطة';
    protected static ?string $navigationGroup = 'الأنشطة';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant', 'activity_admin']) ?? false;
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
        return $form
            ->schema([
                Forms\Components\Select::make('activity_enrollment_id')
                    ->label('المشارك')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        $query = ActivityEnrollment::query();

                        if (!auth()->user()->hasRole('super_admin')) {
                            $query->whereIn('activity_id', auth()->user()->assignedActivities->pluck('id'));
                        }

                        return $query->where(function ($q) use ($search) {
                            $q->whereHas('enrollment.student', function ($sq) use ($search) {
                                $sq->where('full_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('activity', function ($aq) use ($search) {
                                $aq->where('title', 'like', "%{$search}%");
                            });
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($record) => [
                            $record->id => $record->enrollment->student->full_name . ' - ' . $record->activity->title
                        ])
                        ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $record = ActivityEnrollment::with(['enrollment.student', 'activity'])->find($value);
                        if (!$record) {
                            return null;
                        }
                        return $record->enrollment->student->full_name . ' - ' . $record->activity->title;
                    })
                    ->required(),
                Forms\Components\TextInput::make('score')
                    ->label('الدرجة (%)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('أدخل نسبة التقييم من 0 إلى 100'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activityEnrollment.enrollment.student.full_name')
                    ->label('المخدوم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activityEnrollment.activity.title')
                    ->label('النشاط')
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('الدرجة (%)')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 75 => 'info',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query->where(function ($subQuery) use ($user) {
            // If they are activity_admin
            if ($user->hasRole('activity_admin')) {
                $subQuery->orWhereHas('activityEnrollment.activity', function ($q) use ($user) {
                    $q->whereIn('id', $user->assignedActivities->pluck('id'));
                });
            }

            // If they are class_admin or class_servant
            if ($user->hasAnyRole(['class_admin', 'class_servant'])) {
                $assignedClassIds = $user->assignedClasses->pluck('id');
                $activeSeason = \App\Models\Season::active();

                $subQuery->orWhereHas('activityEnrollment.enrollment', function ($q) use ($assignedClassIds, $activeSeason) {
                    $q->whereIn('class_id', $assignedClassIds);
                    if ($activeSeason) {
                        $q->where('season_id', $activeSeason->id);
                    }
                });
            }
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityScores::route('/'),
            'create' => Pages\CreateActivityScore::route('/create'),
            'edit' => Pages\EditActivityScore::route('/{record}/edit'),
        ];
    }
}
