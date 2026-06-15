<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemorizationScoreResource\Pages;
use App\Models\MemorizationScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Models\StudentSeasonEnrollment;
use App\Models\Season;

class MemorizationScoreResource extends Resource
{
    protected static ?string $model = MemorizationScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $modelLabel = 'درجة تسميع';

    protected static ?string $pluralModelLabel = 'درجات التسميع';

    protected static ?string $navigationGroup = 'الامتحانات والتسميع';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
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
                Forms\Components\Select::make('memorization_item_id')
                    ->label('بند التسميع')
                    ->relationship(
                        name: 'memorizationItem',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn ($query) => auth()->user()->hasRole('super_admin')
                            ? $query
                            : $query->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'))
                    )
                    ->required(),
                Forms\Components\TextInput::make('score')
                    ->label('الدرجة')
                    ->numeric()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.full_name')->label('المخدوم')->searchable(),
                Tables\Columns\TextColumn::make('memorizationItem.title')
                    ->label('البند')
                    ->description(fn ($record) => $record->memorizationItem?->class?->name),
                Tables\Columns\TextColumn::make('score')->label('الدرجة')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('memorization_item_id')
                    ->label('البند')
                    ->options(function () {
                        $activeSeason = \App\Models\Season::active();
                        $query = \App\Models\MemorizationItem::query();
                        if ($activeSeason) {
                            $query->where('season_id', $activeSeason->id);
                        }
                        
                        $isSuperAdmin = auth()->user()?->hasRole('super_admin');
                        if (!$isSuperAdmin) {
                            $query->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
                        }
                        
                        return $query->with('class')->get()->mapWithKeys(function ($item) use ($isSuperAdmin) {
                            $label = $item->title;
                            if ($isSuperAdmin && $item->class) {
                                $label .= ' - ' . $item->class->name;
                            }
                            return [$item->id => $label];
                        })->toArray();
                    }),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('الفصل')
                    ->options(\App\Models\KerazaClass::pluck('name', 'id'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }
                        $query->whereHas('memorizationItem', function ($q) use ($data) {
                            $q->where('class_id', $data['value']);
                        });
                    })
                    ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false),
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
        $activeSeason = \App\Models\Season::active();

        if ($activeSeason) {
            $query->whereHas('memorizationItem', function ($q) use ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            });
        }

        if (auth()->user()->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereHas('enrollment', function ($q) {
            $q->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemorizationScores::route('/'),
            'create' => Pages\CreateMemorizationScore::route('/create'),
            'edit' => Pages\EditMemorizationScore::route('/{record}/edit'),
        ];
    }
}
