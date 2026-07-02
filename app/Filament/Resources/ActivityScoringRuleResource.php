<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityScoringRuleResource\Pages;
use App\Models\ActivityScoringRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityScoringRuleResource extends Resource
{
    protected static ?string $model = ActivityScoringRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $modelLabel = 'قاعدة توزيع درجات الأنشطة';

    protected static ?string $pluralModelLabel = 'قواعد توزيع درجات الأنشطة';

    protected static ?string $navigationGroup = 'إعدادات النظام';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
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
                Forms\Components\Select::make('season_id')
                    ->label('الموسم')
                    ->relationship('season', 'name')
                    ->required()
                    ->unique('activity_scoring_rules', 'season_id', ignoreRecord: true)
                    ->searchable()
                    ->preload(),
                Forms\Components\Section::make('توزيع درجات الأنشطة')
                    ->schema([
                        Forms\Components\TextInput::make('weight_attendance')
                            ->label('وزن الحضور (%)')
                            ->numeric()
                            ->required()
                            ->default(20)
                            ->rules([$weightRule])
                            ->live(),
                        Forms\Components\TextInput::make('weight_tasks')
                            ->label('وزن المهام (%)')
                            ->numeric()
                            ->required()
                            ->default(30)
                            ->rules([$weightRule])
                            ->live(),
                        Forms\Components\TextInput::make('weight_evaluation')
                            ->label('وزن التقييم النهائي (%)')
                            ->numeric()
                            ->required()
                            ->default(50)
                            ->rules([$weightRule])
                            ->live(),
                    ])->columns(3),
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
                Tables\Columns\TextColumn::make('weight_attendance')
                    ->label('وزن الحضور')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_tasks')
                    ->label('وزن المهام')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_evaluation')
                    ->label('وزن التقييم النهائي')
                    ->suffix('%')
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
            'index' => Pages\ListActivityScoringRules::route('/'),
            'create' => Pages\CreateActivityScoringRule::route('/create'),
            'edit' => Pages\EditActivityScoringRule::route('/{record}/edit'),
        ];
    }
}
