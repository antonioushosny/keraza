<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamScoreResource\Pages;
use App\Models\ExamScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Models\StudentSeasonEnrollment;
use App\Models\Season;

class ExamScoreResource extends Resource
{
    protected static ?string $model = ExamScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $modelLabel = 'درجة امتحان';

    protected static ?string $pluralModelLabel = 'درجات الامتحانات';

    protected static ?string $navigationGroup = 'الامتحانات والتسميع';

    protected static ?int $navigationSort = 2;

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
                Forms\Components\Select::make('exam_id')
                    ->label('الامتحان')
                    ->relationship(
                        name: 'exam',
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
                Tables\Columns\TextColumn::make('exam.title')
                    ->label('الامتحان')
                    ->description(fn ($record) => $record->exam?->class?->name),
                Tables\Columns\TextColumn::make('score')->label('الدرجة')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('exam')->relationship('exam', 'title')->label('الامتحان'),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('الفصل')
                    ->options(\App\Models\KerazaClass::pluck('name', 'id'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }
                        $query->whereHas('exam', function ($q) use ($data) {
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
            $query->whereHas('exam', function ($q) use ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            });
        }

        if (auth()->user()->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereHas('exam', function ($q) {
            $q->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamScores::route('/'),
            'create' => Pages\CreateExamScore::route('/create'),
            'edit' => Pages\EditExamScore::route('/{record}/edit'),
        ];
    }
}
