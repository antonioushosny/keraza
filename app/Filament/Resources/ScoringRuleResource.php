<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScoringRuleResource\Pages;
use App\Filament\Resources\ScoringRuleResource\RelationManagers;
use App\Models\ScoringRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScoringRuleResource extends Resource
{
    protected static ?string $model = ScoringRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'قاعدة توزيع درجات';

    protected static ?string $pluralModelLabel = 'قواعد توزيع الدرجات';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('season_id')
                    ->label('الموسم')
                    ->relationship('season', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('class_id')
                    ->label('الفصل')
                    ->relationship('class', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('weight_attendance')
                    ->label('وزن الحضور')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('weight_exams')
                    ->label('وزن الامتحانات')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('weight_memorization')
                    ->label('وزن التسميع')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('weight_activities')
                    ->label('وزن الأنشطة')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('weight_behavior')
                    ->label('وزن السلوك')
                    ->numeric()
                    ->required()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('season.name')
                    ->label('الموسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('الفصل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_attendance')
                    ->label('الحضور')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_exams')
                    ->label('الامتحانات')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_memorization')
                    ->label('التسميع')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_activities')
                    ->label('الأنشطة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_behavior')
                    ->label('السلوك')
                    ->sortable(),
            ])
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScoringRules::route('/'),
            'create' => Pages\CreateScoringRule::route('/create'),
            'edit' => Pages\EditScoringRule::route('/{record}/edit'),
        ];
    }
}
