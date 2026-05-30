<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamScoreResource\Pages;
use App\Models\ExamScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamScoreResource extends Resource
{
    protected static ?string $model = ExamScore::class;
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $modelLabel = 'درجة امتحان';
    protected static ?string $pluralModelLabel = 'درجات الامتحانات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_season_enrollment_id')
                    ->label('المخدوم')
                    ->relationship('enrollment.student', 'full_name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('exam_id')
                    ->label('الامتحان')
                    ->relationship('exam', 'title')
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
                Tables\Columns\TextColumn::make('exam.title')->label('الامتحان'),
                Tables\Columns\TextColumn::make('score')->label('الدرجة'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam')->relationship('exam', 'title')->label('الامتحان'),
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
