<?php

namespace App\Filament\Resources\ActivityEnrollmentResource\Pages;

use App\Filament\Resources\ActivityEnrollmentResource;
use App\Models\Activity;
use App\Models\ActivityEnrollment;
use App\Models\Season;
use App\Models\Student;
use App\Models\StudentSeasonEnrollment;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListActivityEnrollments extends ListRecords
{
    protected static string $resource = ActivityEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('import_enrollments')
                ->label('استيراد من Excel')
                ->icon('heroicon-o-document-arrow-up')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('activity_id')
                        ->label('النشاط')
                        ->required()
                        ->options(function () {
                            $query = Activity::query();
                            if (!auth()->user()->hasRole('super_admin')) {
                                $query->whereIn('id', auth()->user()->assignedActivities->pluck('id'));
                            }
                            return $query->pluck('title', 'id')->toArray();
                        })
                        ->searchable()
                        ->placeholder('اختر النشاط'),

                    Forms\Components\FileUpload::make('file')
                        ->label('ملف Excel')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                        ])
                        ->disk('local')
                        ->directory('temp')
                        ->helperText(new HtmlString(
                            'ملف Excel يحتوي على عمود "كود المخدوم". ' .
                            'يمكنك تنزيل ملف المثال من <a href="/admin/activity-enrollments/import-template" class="text-primary-600 underline font-bold" target="_blank">هنا</a>'
                        )),
                ])
                ->action(function (array $data) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);

                    $activeSeason = Season::active();
                    if (!$activeSeason) {
                        Notification::make()
                            ->title('فشل الاستيراد')
                            ->body('لا يوجد موسم نشط حاليًا بالسيستم.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $activityId = $data['activity_id'];

                    // Parse file using OpenSpout (same library used elsewhere)
                    $reader = \OpenSpout\Reader\Common\Creator\ReaderFactory::createFromFile($filePath);
                    $reader->open($filePath);

                    $addedCount   = 0;
                    $skippedCount = 0;
                    $errors       = [];
                    $rowNum       = 1;
                    $codeColIndex = 0; // Default: first column

                    foreach ($reader->getSheetIterator() as $sheet) {
                        $isHeader = true;
                        foreach ($sheet->getRowIterator() as $row) {
                            $cells     = $row->getCells();
                            $rowValues = [];
                            foreach ($cells as $cell) {
                                $rowValues[] = trim((string) ($cell->getValue() ?? ''));
                            }

                            // Detect header row and find the code column
                            if ($isHeader) {
                                $isHeader = false;
                                foreach ($rowValues as $index => $headerVal) {
                                    $headerVal = preg_replace('/\s+/', ' ', trim($headerVal));
                                    if (in_array($headerVal, ['كود المخدوم', 'الكود', 'كود', 'code', 'Code'])) {
                                        $codeColIndex = $index;
                                        break;
                                    }
                                }
                                continue;
                            }

                            $rowNum++;

                            $code = $rowValues[$codeColIndex] ?? '';
                            $code = trim($code);

                            if (empty($code)) {
                                // Skip completely empty rows silently
                                continue;
                            }

                            // Find student by code
                            $student = Student::where('code', $code)->first();
                            if (!$student) {
                                $errors[] = "الصف {$rowNum}: لم يتم العثور على مخدوم بالكود \"{$code}\".";
                                $skippedCount++;
                                continue;
                            }

                            // Find student's enrollment in active season
                            $seasonEnrollment = StudentSeasonEnrollment::where('student_id', $student->id)
                                ->where('season_id', $activeSeason->id)
                                ->first();

                            if (!$seasonEnrollment) {
                                $errors[] = "الصف {$rowNum}: المخدوم \"{$student->full_name}\" (كود: {$code}) غير مسجل في الموسم الحالي.";
                                $skippedCount++;
                                continue;
                            }

                            // Check if already enrolled in this activity
                            $alreadyEnrolled = ActivityEnrollment::where('student_season_enrollment_id', $seasonEnrollment->id)
                                ->where('activity_id', $activityId)
                                ->exists();

                            if ($alreadyEnrolled) {
                                $errors[] = "الصف {$rowNum}: المخدوم \"{$student->full_name}\" (كود: {$code}) مسجل بالفعل في هذا النشاط.";
                                $skippedCount++;
                                continue;
                            }

                            // Create enrollment
                            ActivityEnrollment::create([
                                'student_season_enrollment_id' => $seasonEnrollment->id,
                                'activity_id'                  => $activityId,
                                'status'                       => 'pending',
                            ]);

                            $addedCount++;
                        }
                    }

                    $reader->close();

                    // Clean up temp file
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    // Build notification
                    $notificationBody = "تم تسجيل {$addedCount} مخدوم في النشاط بنجاح.";

                    if (count($errors) > 0) {
                        $notificationBody .= "<br><br><strong>الصفوف التي تم تخطيها ({$skippedCount}):</strong>";
                        $notificationBody .= "<ul style='list-style-type: disc; padding-inline-start: 20px; color: #dc2626; margin-top: 5px;'>";
                        foreach ($errors as $error) {
                            $notificationBody .= '<li>' . e($error) . '</li>';
                        }
                        $notificationBody .= '</ul>';
                    }

                    $notification = Notification::make()
                        ->title(count($errors) > 0 ? 'تمت عملية الاستيراد مع ملاحظات' : 'تمت عملية الاستيراد بنجاح')
                        ->body(new HtmlString($notificationBody));

                    if (count($errors) > 0 && $addedCount === 0) {
                        $notification->danger();
                    } elseif (count($errors) > 0) {
                        $notification->warning();
                    } else {
                        $notification->success();
                    }

                    $notification->send();
                }),
        ];
    }
}
