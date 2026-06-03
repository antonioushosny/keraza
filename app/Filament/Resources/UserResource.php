<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'مستخدم/خادم';

    protected static ?string $pluralModelLabel = 'المستخدمين والخادم';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المستخدم')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الموبايل')
                            ->required()
                            ->unique(
                                table: 'users',
                                ignorable: fn ($record) => $record,
                                modifyRuleUsing: function ($rule) {
                                    return $rule->where(function ($query) {
                                        $query->whereNotExists(function ($q) {
                                            $q->selectRaw(1)
                                                ->from('model_has_roles')
                                                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                                                ->whereColumn('model_has_roles.model_id', 'users.id')
                                                ->where('model_has_roles.model_type', \App\Models\User::class)
                                                ->where('roles.name', 'parent');
                                        });
                                    });
                                }
                            ),
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),

                Forms\Components\Section::make('الأدوار والصلاحيات')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('الأدوار')
                            ->relationship('roles', 'name', fn ($query) => $query->where('name', '!=', 'parent'))
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('التكليفات (للخدام)')
                    ->schema([
                        Forms\Components\Select::make('assignedClasses')
                            ->label('الفصول المسئول عنها')
                            ->relationship('assignedClasses', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('assignedActivities')
                            ->label('الأنشطة المسئول عنها')
                            ->relationship('assignedActivities', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الموبايل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الأدوار')
                    ->badge(),
                Tables\Columns\TextColumn::make('assignedClasses.name')
                    ->label('الفصول')
                    ->listWithLineBreaks(),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'parent');
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
