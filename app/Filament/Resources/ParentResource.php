<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParentResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'ولي أمر';

    protected static ?string $pluralModelLabel = 'أولياء الأمور';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات ولي الأمر')
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
                                    return $rule->where('type', 'parent');
                                }
                            ),
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),

                Forms\Components\Section::make('الأبناء (المخدومين)')
                    ->schema([
                        Forms\Components\Select::make('student_ids')
                            ->label('الأبناء المرتبطين')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => \App\Models\Student::pluck('full_name', 'id'))
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->students()->pluck('id')->toArray());
                                }
                            }),
                    ]),
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
                Tables\Columns\TextColumn::make('students.full_name')
                    ->label('الأبناء')
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'parent');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParents::route('/'),
            'create' => Pages\CreateParent::route('/create'),
            'edit' => Pages\EditParent::route('/{record}/edit'),
        ];
    }
}
