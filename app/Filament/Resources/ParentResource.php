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

    protected static ?string $navigationGroup = 'إدارة المخدومين';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

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
                            ->options(function ($record) {
                                $user = auth()->user();
                                $activeSeason = \App\Models\Season::active();
                                $query = \App\Models\Student::query();
                                
                                if ($user && !$user->hasRole('super_admin')) {
                                    $assignedClassIds = $user->assignedClasses->pluck('id');
                                    $query->where(function ($q) use ($assignedClassIds, $activeSeason, $record) {
                                        $q->whereHas('enrollments', function ($enrollmentQuery) use ($assignedClassIds, $activeSeason) {
                                            $enrollmentQuery->whereIn('class_id', $assignedClassIds);
                                            if ($activeSeason) {
                                                $enrollmentQuery->where('season_id', $activeSeason->id);
                                            }
                                        });
                                        if ($record) {
                                            $q->orWhere('parent_id', $record->id);
                                        }
                                    });
                                }
                                return $query->pluck('full_name', 'id');
                            })
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
                Tables\Filters\SelectFilter::make('class')
                    ->label('الفصل')
                    ->options(\App\Models\KerazaClass::all()->pluck('name', 'id'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $activeSeason = \App\Models\Season::active();
                            $query->whereHas('students', function ($studentQuery) use ($data, $activeSeason) {
                                $studentQuery->whereHas('enrollments', function ($q) use ($data, $activeSeason) {
                                    $q->where('class_id', $data['value']);
                                    if ($activeSeason) {
                                        $q->where('season_id', $activeSeason->id);
                                    }
                                });
                            });
                        }
                    })
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
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
        $query = parent::getEloquentQuery()->where('type', 'parent');
        
        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin')) {
            $assignedClassIds = $user->assignedClasses->pluck('id');
            $activeSeason = \App\Models\Season::active();

            $query->whereHas('students', function ($studentQuery) use ($assignedClassIds, $activeSeason) {
                $studentQuery->whereHas('enrollments', function ($enrollmentQuery) use ($assignedClassIds, $activeSeason) {
                    $enrollmentQuery->whereIn('class_id', $assignedClassIds);
                    if ($activeSeason) {
                        $enrollmentQuery->where('season_id', $activeSeason->id);
                    }
                });
            });
        }
        
        return $query;
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
