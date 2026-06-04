<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentSeasonEnrollmentResource\Pages;
use App\Filament\Resources\StudentSeasonEnrollmentResource\RelationManagers;
use App\Models\StudentSeasonEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentSeasonEnrollmentResource extends Resource
{
    protected static ?string $model = StudentSeasonEnrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'تسجيل مخدوم في موسم';

    protected static ?string $pluralModelLabel = 'تسجيلات المخدومين في المواسم';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('المخدوم')
                    ->relationship('student', 'full_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Hidden::make('season_id')
                    ->default(fn () => \App\Models\Season::active()?->id),
                Forms\Components\Select::make('class_id')
                    ->label('الفصل')
                    ->relationship('class', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('المخدوم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('season.name')
                    ->label('الموسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('الفصل')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('season_id')
                    ->label('الموسم')
                    ->relationship('season', 'name'),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('الفصل')
                    ->relationship('class', 'name'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
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
            'index' => Pages\ListStudentSeasonEnrollments::route('/'),
            'create' => Pages\CreateStudentSeasonEnrollment::route('/create'),
            'edit' => Pages\EditStudentSeasonEnrollment::route('/{record}/edit'),
        ];
    }
}
