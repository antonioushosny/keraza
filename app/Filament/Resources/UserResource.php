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

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?int $navigationSort = 1;

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
                                    return $rule->where('type', 'admin');
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
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'suspended' ? 'موقوف' : 'نشط')
                    ->color(fn (string $state): string => $state === 'suspended' ? 'danger' : 'success'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('suspend')
                    ->label('إيقاف')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') && $record->isActive() && $record->id !== auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading('إيقاف المستخدم')
                    ->modalDescription('سيتم تسجيل خروج هذا المستخدم فورًا من كل الأجهزة، ولن يستطيع تسجيل الدخول مرة أخرى حتى يتم تفعيل حسابه مجددًا. هل أنت متأكد؟')
                    ->modalSubmitActionLabel('نعم، إيقاف')
                    ->action(function ($record) {
                        $record->suspend();
                        \Filament\Notifications\Notification::make()
                            ->title('تم إيقاف المستخدم')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') && $record->isSuspended())
                    ->requiresConfirmation()
                    ->modalHeading('تفعيل المستخدم')
                    ->action(function ($record) {
                        $record->activate();
                        \Filament\Notifications\Notification::make()
                            ->title('تم تفعيل المستخدم')
                            ->success()
                            ->send();
                    }),
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
            ->where('type', 'admin');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
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
