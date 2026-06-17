<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\RelationManagers;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'نشاط';

    protected static ?string $pluralModelLabel = 'الأنشطة';

    protected static ?string $navigationGroup = 'الأنشطة';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'activity_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        $weightRule = function ($get) {
            return function (string $attribute, $value, \Closure $fail) use ($get) {
                $total = intval($get('weight_attendance') ?? 0) + intval($get('weight_tasks') ?? 0) + intval($get('weight_evaluation') ?? 0);
                if ($total !== 100) {
                    $fail('مجموع أوزان الدرجات يجب أن يساوي 100% حالياً المجموع هو ' . $total . '%');
                }
            };
        };

        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('العنوان')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type_id')
                    ->label('نوع النشاط')
                    ->relationship('type', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('season_id')
                    ->label('الموسم')
                    ->relationship('season', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('min_score_to_qualify')
                    ->label('الحد الأدنى للتأهل')
                    ->numeric()
                    ->required(),
                Forms\Components\Section::make('أوزان درجات النشاط')
                    ->schema([
                        Forms\Components\TextInput::make('weight_attendance')
                            ->label('وزن نسبة الحضور (%)')
                            ->numeric()
                            ->default(20)
                            ->required()
                            ->rules([$weightRule])
                            ->live(),
                        Forms\Components\TextInput::make('weight_tasks')
                            ->label('وزن نسبة المهام (%)')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->rules([$weightRule])
                            ->live(),
                        Forms\Components\TextInput::make('weight_evaluation')
                            ->label('وزن نسبة التقييم النهائي (%)')
                            ->numeric()
                            ->default(50)
                            ->required()
                            ->rules([$weightRule])
                            ->live(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type.name')
                    ->label('نوع النشاط')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('season.name')
                    ->label('الموسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_score_to_qualify')
                    ->label('الحد الأدنى للتأهل')
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (auth()->user()->hasRole('super_admin')) {
            return $query;
        }
        
        if (auth()->user()->hasRole('activity_admin')) {
            $assignedActivityIds = auth()->user()->assignedActivities->pluck('id');
            return $query->whereIn('id', $assignedActivityIds);
        }
        
        return $query->whereRaw('1 = 0');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
