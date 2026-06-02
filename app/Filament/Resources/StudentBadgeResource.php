<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentBadgeResource\Pages;
use App\Models\StudentBadge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Models\StudentSeasonEnrollment;
use App\Models\Season;

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
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        $query = StudentSeasonEnrollment::query()
                            ->whereHas('student', function ($sq) use ($search) {
                                $sq->where('full_name', 'like', "%{$search}%");
                            });

                        if (!auth()->user()->hasRole('super_admin')) {
                            $query->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
                        }

                        $activeSeason = Season::active();
                        if ($activeSeason) {
                            $query->where('season_id', $activeSeason->id);
                        }

                        return $query->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($record) => [
                                $record->id => $record->student->full_name . ($record->class ? ' - ' . $record->class->name : '')
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $record = StudentSeasonEnrollment::with(['student', 'class'])->find($value);
                        if (!$record) {
                            return null;
                        }
                        return $record->student->full_name . ($record->class ? ' - ' . $record->class->name : '');
                    })
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
            ->defaultSort('created_at', 'desc')
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
