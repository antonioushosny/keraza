<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BehaviorLogResource\Pages;
use App\Filament\Resources\BehaviorLogResource\RelationManagers;
use App\Models\BehaviorLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BehaviorLogResource extends Resource
{
    protected static ?string $model = BehaviorLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'سجل سلوك';

    protected static ?string $pluralModelLabel = 'سجلات السلوك';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_season_enrollment_id')
                    ->label('المخدوم')
                    ->relationship('enrollment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->student->full_name . ' - ' . $record->season->name)
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('type')
                    ->label('النوع')
                    ->options([
                        'positive' => 'إيجابي',
                        'negative' => 'سلبي',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('points')
                    ->label('النقاط')
                    ->numeric()
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->label('السبب')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('created_by')
                    ->label('بواسطة')
                    ->relationship('creator', 'name')
                    ->default(auth()->id())
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.full_name')
                    ->label('المخدوم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'positive' => 'إيجابي',
                        'negative' => 'سلبي',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('points')
                    ->label('النقاط')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(50),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('بواسطة')
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $activeSeason = \App\Models\Season::active();

        return parent::getEloquentQuery()
            ->when($activeSeason, function ($query) use ($activeSeason) {
                $query->whereHas('enrollment', function ($q) use ($activeSeason) {
                    $q->where('season_id', $activeSeason->id);
                });
            })
            ->when(!auth()->user()->hasRole('super_admin'), function ($query) {
                $query->whereHas('enrollment', function ($q) {
                    $q->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
                });
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBehaviorLogs::route('/'),
            'create' => Pages\CreateBehaviorLog::route('/create'),
            'edit' => Pages\EditBehaviorLog::route('/{record}/edit'),
        ];
    }
}
