<?php

namespace App\Filament\Resources\BehaviorLogResource\Pages;

use App\Filament\Resources\BehaviorLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBehaviorLog extends CreateRecord
{
    protected static string $resource = BehaviorLogResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $targetType = $data['target_type'] ?? 'single';
        $type = $data['type'];
        $points = $data['points'];
        $reason = $data['reason'];
        $createdBy = $data['created_by'] ?? auth()->id();

        $activeSeason = \App\Models\Season::active();
        if (!$activeSeason) {
            throw new \Exception('لا يوجد موسم نشط حاليًا.');
        }

        $enrollmentIds = [];

        if ($targetType === 'single') {
            $enrollmentIds[] = $data['student_season_enrollment_id'];
        } elseif ($targetType === 'class_active') {
            $classId = $data['class_id'];
            // Find active students in the class (have at least one 'present' or 'excused' attendance record)
            $enrollmentIds = \App\Models\StudentSeasonEnrollment::where('class_id', $classId)
                ->where('season_id', $activeSeason->id)
                ->whereHas('attendance', function ($q) {
                    $q->whereIn('status', ['present', 'excused']);
                })
                ->pluck('id')
                ->toArray();
        } elseif ($targetType === 'attendance_date') {
            $classId = $data['class_id'];
            $date = $data['attendance_date'];
            // Find students in the class who were marked present/excused on the selected date
            $enrollmentIds = \App\Models\StudentSeasonEnrollment::where('class_id', $classId)
                ->where('season_id', $activeSeason->id)
                ->whereHas('attendance', function ($q) use ($date) {
                    $q->whereIn('status', ['present', 'excused'])
                      ->whereHas('session', function ($sq) use ($date) {
                          $sq->whereDate('date', $date);
                      });
                })
                ->pluck('id')
                ->toArray();
        } elseif ($targetType === 'multi') {
            $enrollmentIds = $data['student_season_enrollment_ids'] ?? [];
        }

        if (empty($enrollmentIds)) {
            throw new \Exception('لم يتم العثور على أي مخدومين مستوفين للشروط المحددة.');
        }

        $lastRecord = null;
        foreach ($enrollmentIds as $enrollmentId) {
            $lastRecord = \App\Models\BehaviorLog::create([
                'student_season_enrollment_id' => $enrollmentId,
                'type' => $type,
                'points' => $points,
                'reason' => $reason,
                'created_by' => $createdBy,
            ]);
        }

        return $lastRecord;
    }
}
