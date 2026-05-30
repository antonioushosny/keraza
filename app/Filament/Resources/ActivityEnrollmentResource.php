<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityEnrollmentResource\Pages;
use App\Models\ActivityEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityEnrollmentResource extends Resource
{
    protected static ?string $model = ActivityEnrollment::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $modelLabel = 'تسجيل نشاط';
    protected static ?string $pluralModelLabel = 'تسجيلات الأنشطة';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_season_enrollment_id')
                    ->label('المخدوم')
                    ->relationship('enrollment.student', 'full_name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('activity_id')
                    ->label('النشاط')
                    ->relationship('activity', 'title')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'qualified' => 'متأهل',
                        'disqualified' => 'غير متأهل',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.full_name')->label('المخدوم')->searchable(),
                Tables\Columns\TextColumn::make('activity.title')->label('النشاط'),
                Tables\Columns\SelectColumn::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'qualified' => 'متأهل',
                        'disqualified' => 'غير متأهل',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity')->relationship('activity', 'title')->label('النشاط'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'قيد الانتظار',
                    'qualified' => 'متأهل',
                    'disqualified' => 'غير متأهل',
                ])->label('الحالة'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityEnrollments::route('/'),
            'create' => Pages\CreateActivityEnrollment::route('/create'),
            'edit' => Pages\EditActivityEnrollment::route('/{record}/edit'),
        ];
    }
}
