<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'مخدوم';
    protected static ?string $pluralModelLabel = 'المخدومين';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الشخصية')
                    ->schema([
                        Forms\Components\FileUpload::make('profile_image')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1'])
                            ->circleCropper()
                            ->directory('students')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('ارفع صورة للمخدوم (JPG أو PNG، حد أقصى 2 ميجابايت)')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('full_name')
                            ->label('الاسم بالكامل')
                            ->required(),

                        Forms\Components\Select::make('gender')
                            ->label('الجنس')
                            ->options([
                                'male'   => 'ذكر',
                                'female' => 'أنثى',
                            ])
                            ->required(),

                        Forms\Components\Select::make('class_id')
                            ->label('الفصل')
                            ->options(\App\Models\KerazaClass::all()->pluck('name', 'id'))
                            ->required()
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                if ($record) {
                                    $activeSeason = \App\Models\Season::active();
                                    if ($activeSeason) {
                                        $enrollment = $record->enrollments()->where('season_id', $activeSeason->id)->first();
                                        if ($enrollment) {
                                            $component->state($enrollment->class_id);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('تاريخ الميلاد'),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('بيانات ولي الأمر')
                    ->schema([
                        Forms\Components\TextInput::make('parent_phone')
                            ->label('رقم موبايل ولي الأمر')
                            ->required()
                            ->maxLength(15)
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record && $record->parent) {
                                    $component->state($record->parent->phone);
                                }
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $parent = \App\Models\User::where('phone', $state)->first();
                                    if ($parent) {
                                        $set('parent_name', $parent->name);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('parent_name')
                            ->label('اسم ولي الأمر')
                            ->required()
                            ->maxLength(255)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record && $record->parent) {
                                    $component->state($record->parent->name);
                                }
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('profile_image')
                    ->label('الصورة')
                    ->circular(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn (string $state): string => $state === 'male' ? 'ذكر' : 'أنثى'),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('تاريخ الميلاد')
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('promote_students')
                    ->label('ترقية جماعية للفصل التالي')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('ترقية جماعية لجميع المخدومين للفصل التالي')
                    ->modalDescription('سيؤدي هذا الإجراء إلى نقل جميع المخدومين النشطين في الموسم السابق إلى المستوى الدراسي التالي في الموسم النشط الحالي. المخدومون في الصف السادس سيتم اعتبارهم خريجين ولن يتم ترقيتهم. هل أنت متأكد؟')
                    ->action(function () {
                        $activeSeason = \App\Models\Season::active();
                        if (!$activeSeason) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل الترقية')
                                ->body('لا يوجد موسم نشط حاليًا بالسيستم.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $previousSeason = \App\Models\Season::where('id', '!=', $activeSeason->id)
                            ->orderBy('start_date', 'desc')
                            ->first();

                        if (!$previousSeason) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل الترقية')
                                ->body('لا يوجد موسم سابق في النظام لنقل المخدومين منه.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $enrollments = \App\Models\StudentSeasonEnrollment::where('season_id', $previousSeason->id)
                            ->with(['class', 'student'])
                            ->get();

                        $promotedCount = 0;
                        $graduatedCount = 0;

                        foreach ($enrollments as $enrollment) {
                            $student = $enrollment->student;
                            $currentClass = $enrollment->class;
                            if (!$currentClass) continue;

                            $currentLevel = intval($currentClass->level);

                            if ($currentLevel >= 6) {
                                $graduatedCount++;
                                continue;
                            }

                            $nextClass = \App\Models\KerazaClass::where('level', $currentLevel + 1)->first();
                            if (!$nextClass) continue;

                            $exists = \App\Models\StudentSeasonEnrollment::where('student_id', $student->id)
                                ->where('season_id', $activeSeason->id)
                                ->exists();

                            if (!$exists) {
                                \App\Models\StudentSeasonEnrollment::create([
                                    'student_id' => $student->id,
                                    'season_id' => $activeSeason->id,
                                    'class_id' => $nextClass->id,
                                ]);
                                $promotedCount++;
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('تمت الترقية بنجاح')
                            ->body("تم ترقية {$promotedCount} مخدوم بنجاح إلى الصف التالي، وتخرج {$graduatedCount} مخدومين من الصف السادس.")
                            ->success()
                            ->send();
                    })
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $activeSeason = \App\Models\Season::active();
        
        return parent::getEloquentQuery()
            ->whereHas('enrollments', function ($query) use ($activeSeason) {
                if ($activeSeason) {
                    $query->where('season_id', $activeSeason->id);
                }
                if (!auth()->user()->hasRole('super_admin')) {
                    $query->whereIn('class_id', auth()->user()->assignedClasses->pluck('id'));
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
