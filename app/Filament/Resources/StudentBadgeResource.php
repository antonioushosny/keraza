<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentBadgeResource\Pages;
use App\Models\StudentBadge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentBadgeResource extends Resource
{
    protected static ?string $model = StudentBadge::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $modelLabel = 'منح وسام';
    protected static ?string $pluralModelLabel = 'أوسمة المخدومين';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_season_enrollment_id')
                    ->label('المخدوم')
                    ->relationship('enrollment.student', 'full_name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('badge_id')
                    ->label('الوسام')
                    ->relationship('badge', 'title')
                    ->required(),
                Forms\Components\DateTimePicker::make('awarded_at')
                    ->label('تاريخ المنح')
                    ->default(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.full_name')->label('المخدوم')->searchable(),
                Tables\Columns\TextColumn::make('badge.title')->label('الوسام'),
                Tables\Columns\TextColumn::make('awarded_at')->label('تاريخ المنح')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('badge')->relationship('badge', 'title')->label('الوسام'),
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
            'index' => Pages\ListStudentBadges::route('/'),
            'create' => Pages\CreateStudentBadge::route('/create'),
            'edit' => Pages\EditStudentBadge::route('/{record}/edit'),
        ];
    }
}
