<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Models\StudentSeasonEnrollment;
use App\Models\Season;
use Filament\Tables\Filters\Filter;
class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'الحضور والأنشطة';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'حضور فردي';

    protected static ?string $pluralModelLabel = 'الحضور الفردي';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('class_id')
                    ->label('الفصل')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->hasRole('super_admin')) {
                            return \App\Models\KerazaClass::pluck('name', 'id')->toArray();
                        }
                        return $user->assignedClasses->pluck('name', 'id')->toArray();
                    })
                    ->live()
                    ->required()
                    ->dehydrated(false)
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('student_season_enrollment_id')
                    ->label('المخدوم')
                    ->options(function (Forms\Get $get) {
                        $classId = $get('class_id');
                        if (!$classId) {
                            return [];
                        }
                        $activeSeason = \App\Models\Season::active();
                        if (!$activeSeason) {
                            return [];
                        }
                        return \App\Models\StudentSeasonEnrollment::where('class_id', $classId)
                            ->where('season_id', $activeSeason->id)
                            ->with('student')
                            ->get()
                            ->mapWithKeys(fn ($item) => [$item->id => $item->student->full_name])
                            ->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\DatePicker::make('date')
                    ->label('التاريخ')
                    ->required()
                    ->default(now()),

                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'excused' => 'معتذر',
                    ])
                    ->default('present')
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->maxLength(65535)
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('enrollment.class.name')
                    ->label('الفصل')
                    ->sortable(),
                Tables\Columns\TextColumn::make('session.date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'excused' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'excused' => 'معتذر',
                        default => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('الفصل')
                    ->options(\App\Models\KerazaClass::pluck('name', 'id'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }
                        $query->whereHas('enrollment', function ($q) use ($data) {
                            $q->where('class_id', $data['value']);
                        });
                    }),
                Filter::make('date')
                    ->label('التاريخ')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('تاريخ الحضور'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date'],
                            fn (Builder $query, $date) => $query->whereHas('session', fn($q) => $q->whereDate('date', $date))
                        );
                    }),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
