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
                Tables\Filters\SelectFilter::make('class')
                    ->label('الفصل')
                    ->options(\App\Models\KerazaClass::all()->pluck('name', 'id'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $activeSeason = \App\Models\Season::active();
                            $query->whereHas('enrollments', function ($q) use ($data, $activeSeason) {
                                $q->where('class_id', $data['value']);
                                if ($activeSeason) {
                                    $q->where('season_id', $activeSeason->id);
                                }
                            });
                        }
                    })
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
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
                    }),
                Tables\Actions\Action::make('import_students')
                    ->label('استيراد مخدومين من إكسيل')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('info')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('ملف إكسيل')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv'
                            ])
                            ->disk('local')
                            ->directory('temp')
                            ->helperText(new \Illuminate\Support\HtmlString('حمل ملف إكسيل (.xlsx) أو ملف CSV. يمكنك تنزيل ملف المثال من <a href="/admin/students/import-template" class="text-primary-600 underline font-bold" target="_blank">هنا</a>')),

                        Forms\Components\Select::make('class_id')
                            ->label('الفصل المستهدف')
                            ->required()
                            ->options(function () {
                                if (auth()->user()->hasRole('super_admin')) {
                                    return \App\Models\KerazaClass::all()->pluck('name', 'id');
                                }
                                return auth()->user()->assignedClasses->pluck('name', 'id');
                            })
                            ->default(function () {
                                if (!auth()->user()->hasRole('super_admin')) {
                                    $assigned = auth()->user()->assignedClasses;
                                    if ($assigned->count() === 1) {
                                        return $assigned->first()->id;
                                    }
                                }
                                return null;
                            }),
                    ])
                    ->action(function (array $data) {
                        $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                        
                        $activeSeason = \App\Models\Season::active();
                        if (!$activeSeason) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل الاستيراد')
                                ->body('لا يوجد موسم نشط حاليًا بالسيستم.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $classId = $data['class_id'];
                        
                        // Parse Excel/CSV using OpenSpout
                        $reader = \OpenSpout\Reader\Common\Creator\ReaderFactory::createFromFile($filePath);
                        $reader->open($filePath);

                        $importedCount = 0;
                        $skippedCount = 0;

                        foreach ($reader->getSheetIterator() as $sheet) {
                            $isHeader = true;
                            foreach ($sheet->getRowIterator() as $row) {
                                if ($isHeader) {
                                    $isHeader = false;
                                    continue;
                                }

                                $cells = $row->getCells();
                                $rowValues = [];
                                foreach ($cells as $cell) {
                                    $rowValues[] = trim($cell->getValue() ?? '');
                                }

                                if (count($rowValues) < 6) {
                                    $rowValues = array_pad($rowValues, 6, '');
                                }

                                $studentName = $rowValues[0];
                                $genderInput = $rowValues[1];
                                $birthDateInput = $rowValues[2];
                                $notes = $rowValues[3];
                                $parentName = $rowValues[4];
                                $parentPhone = $rowValues[5];

                                // Basic validation
                                if (empty($studentName) || empty($parentPhone)) {
                                    $skippedCount++;
                                    continue;
                                }

                                // Format gender
                                $gender = 'male';
                                if ($genderInput === 'أنثى' || strtolower($genderInput) === 'female') {
                                    $gender = 'female';
                                }

                                // Format birth_date
                                $birthDate = null;
                                if (!empty($birthDateInput)) {
                                    if ($birthDateInput instanceof \DateTimeInterface) {
                                        $birthDate = $birthDateInput->format('Y-m-d');
                                    } else {
                                        $parsedTime = strtotime($birthDateInput);
                                        if ($parsedTime !== false) {
                                            $birthDate = date('Y-m-d', $parsedTime);
                                        }
                                    }
                                }

                                // Create/Get Parent User
                                $parent = \App\Models\User::createOrGetParent($parentPhone, $parentName, $studentName);

                                // Check if student already exists for this parent
                                $existingStudent = \App\Models\Student::where('full_name', $studentName)
                                    ->where('parent_id', $parent->id)
                                    ->first();

                                if ($existingStudent) {
                                    $enrollmentExists = \App\Models\StudentSeasonEnrollment::where('student_id', $existingStudent->id)
                                        ->where('season_id', $activeSeason->id)
                                        ->exists();

                                    if (!$enrollmentExists) {
                                        \App\Models\StudentSeasonEnrollment::create([
                                            'student_id' => $existingStudent->id,
                                            'season_id' => $activeSeason->id,
                                            'class_id' => $classId,
                                        ]);
                                        $importedCount++;
                                    } else {
                                        $skippedCount++;
                                    }
                                    continue;
                                }

                                // Create student
                                $student = \App\Models\Student::create([
                                    'full_name' => $studentName,
                                    'gender' => $gender,
                                    'birth_date' => $birthDate,
                                    'notes' => $notes,
                                    'parent_id' => $parent->id,
                                ]);

                                // Enroll student
                                \App\Models\StudentSeasonEnrollment::create([
                                    'student_id' => $student->id,
                                    'season_id' => $activeSeason->id,
                                    'class_id' => $classId,
                                ]);

                                $importedCount++;
                            }
                        }

                        $reader->close();

                        // Delete the temporary file
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('تمت عملية الاستيراد بنجاح')
                            ->body("تم استيراد {$importedCount} مخدوم بنجاح، وتخطي {$skippedCount} مخدومين/صفوف مكررة أو غير صالحة.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('export_students')
                    ->label('تصدير إلى إكسيل')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (\Filament\Tables\Contracts\HasTable $livewire) {
                        $tempFile = tempnam(sys_get_temp_dir(), 'export') . '.xlsx';
                        
                        $writer = new \OpenSpout\Writer\XLSX\Writer();
                        $writer->openToFile($tempFile);

                        $isSuperAdmin = auth()->user()->hasRole('super_admin');
                        $activeSeason = \App\Models\Season::active();

                        // Headers
                        $headerCells = [
                            \OpenSpout\Common\Entity\Cell::fromValue('الكود'),
                            \OpenSpout\Common\Entity\Cell::fromValue('الاسم بالكامل'),
                            \OpenSpout\Common\Entity\Cell::fromValue('الجنس'),
                            \OpenSpout\Common\Entity\Cell::fromValue('تاريخ الميلاد'),
                            \OpenSpout\Common\Entity\Cell::fromValue('اسم ولي الأمر'),
                            \OpenSpout\Common\Entity\Cell::fromValue('رقم موبايل ولي الأمر'),
                            \OpenSpout\Common\Entity\Cell::fromValue('ملاحظات'),
                        ];
                        if ($isSuperAdmin) {
                            $headerCells[] = \OpenSpout\Common\Entity\Cell::fromValue('الفصل');
                        }
                        $writer->addRow(new \OpenSpout\Common\Entity\Row($headerCells));

                        // Get records matching current filtered table query
                        $records = $livewire->getFilteredTableQuery()->get();

                        foreach ($records as $record) {
                            $rowCells = [
                                \OpenSpout\Common\Entity\Cell::fromValue($record->code),
                                \OpenSpout\Common\Entity\Cell::fromValue($record->full_name),
                                \OpenSpout\Common\Entity\Cell::fromValue($record->gender === 'male' ? 'ذكر' : 'أنثى'),
                                \OpenSpout\Common\Entity\Cell::fromValue($record->birth_date),
                                \OpenSpout\Common\Entity\Cell::fromValue($record->parent?->name),
                                \OpenSpout\Common\Entity\Cell::fromValue($record->parent?->phone),
                                \OpenSpout\Common\Entity\Cell::fromValue($record->notes),
                            ];
                            if ($isSuperAdmin) {
                                $enrollment = $activeSeason ? $record->enrollments()->where('season_id', $activeSeason->id)->first() : null;
                                $className = $enrollment?->class?->name ?? '';
                                $rowCells[] = \OpenSpout\Common\Entity\Cell::fromValue($className);
                            }
                            $writer->addRow(new \OpenSpout\Common\Entity\Row($rowCells));
                        }

                        $writer->close();

                        return response()->download($tempFile, 'students_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend(true);
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
