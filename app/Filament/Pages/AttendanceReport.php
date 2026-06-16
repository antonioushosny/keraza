<?php

namespace App\Filament\Pages;

use App\Models\KerazaClass;
use App\Models\Season;
use App\Models\StudentSeasonEnrollment;
use App\Models\AttendanceSession;
use Filament\Pages\Page;

class AttendanceReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $title = 'تقرير الحضور';
    protected static ?string $navigationLabel = 'تقرير الحضور';
    protected static string $view = 'filament.pages.attendance-report';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

    public ?int $selectedClassId = null;
    public array $classes = [];
    public array $reportData = [];
    public array $items = [];
    public ?string $seasonName = null;

    public function mount(): void
    {
        $user = auth()->user();
        
        if ($user->hasRole('super_admin')) {
            $this->classes = KerazaClass::orderBy('level')->get()->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        } else {
            $this->classes = $user->assignedClasses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        }

        if (count($this->classes) === 1) {
            $this->selectedClassId = $this->classes[0]['id'];
            $this->loadReport();
        }

        $activeSeason = Season::active();
        $this->seasonName = $activeSeason?->name;
    }

    public function updatedSelectedClassId(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        if (!$this->selectedClassId) {
            $this->reportData = [];
            $this->items = [];
            return;
        }

        $activeSeason = Season::active();
        if (!$activeSeason) {
            $this->reportData = [];
            $this->items = [];
            return;
        }

        // Fetch attendance sessions for the selected class and active season
        $sessions = AttendanceSession::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->orderBy('date')
            ->get();

        $this->items = $sessions->map(fn($session) => [
            'id' => $session->id,
            'date' => $session->date,
        ])->toArray();

        // Fetch student enrollments with their attendance
        $enrollments = StudentSeasonEnrollment::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->with(['student', 'attendance'])
            ->get();

        $report = [];
        $totalSessionsCount = $sessions->count();

        foreach ($enrollments as $enrollment) {
            $studentAttendance = [];
            $presentCount = 0;
            $excusedCount = 0;
            $absentCount = 0;

            foreach ($sessions as $session) {
                $attObj = $enrollment->attendance->firstWhere('attendance_session_id', $session->id);
                if ($attObj) {
                    $studentAttendance[$session->id] = $attObj->status;
                    
                    if ($attObj->status === 'present') {
                        $presentCount++;
                    } elseif ($attObj->status === 'excused') {
                        $excusedCount++;
                    } elseif ($attObj->status === 'absent') {
                        $absentCount++;
                    }
                } else {
                    $studentAttendance[$session->id] = null;
                }
            }

            // Calculate attendance rate (Present = 100%, Excused = 50%, Absent = 0%)
            $attendedSum = $presentCount + ($excusedCount * 0.5);
            $attendanceRate = $totalSessionsCount > 0 ? round(($attendedSum / $totalSessionsCount) * 100, 1) : 100;

            $report[] = [
                'enrollment_id' => $enrollment->id,
                'student_name' => $enrollment->student->full_name,
                'profile_image' => $enrollment->student->profile_image,
                'attendance' => $studentAttendance,
                'present_count' => $presentCount,
                'excused_count' => $excusedCount,
                'absent_count' => $absentCount,
                'attendance_rate' => $attendanceRate,
            ];
        }

        // Sort by attendance rate descending, then by name
        usort($report, function ($a, $b) {
            if ($b['attendance_rate'] === $a['attendance_rate']) {
                return strcmp($a['student_name'], $b['student_name']);
            }
            return $b['attendance_rate'] <=> $a['attendance_rate'];
        });

        $this->reportData = $report;
    }

    public function export()
    {
        if (!$this->selectedClassId || empty($this->reportData)) {
            return;
        }

        $class = KerazaClass::find($this->selectedClassId);
        $className = $class ? $class->name : 'فصل';
        $filename = "تقرير_الحضور_{$className}_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Arabic characters in Excel
            fwrite($file, "\xEF\xBB\xBF");

            // Build headers
            $headers = ['الترتيب', 'المخدوم'];
            foreach ($this->items as $item) {
                $headers[] = $item['date'];
            }
            $headers[] = 'حضور';
            $headers[] = 'غياب';
            $headers[] = 'إذن';
            $headers[] = 'نسبة الحضور';

            fputcsv($file, $headers);

            // Build rows
            foreach ($this->reportData as $index => $row) {
                $rowData = [
                    $index + 1,
                    $row['student_name']
                ];

                foreach ($this->items as $item) {
                    $status = $row['attendance'][$item['id']] ?? null;
                    $statusLabel = match($status) {
                        'present' => 'حاضر',
                        'excused' => 'مستأذن',
                        'absent' => 'غائب',
                        default => '-',
                    };
                    $rowData[] = $statusLabel;
                }

                $rowData[] = $row['present_count'];
                $rowData[] = $row['absent_count'];
                $rowData[] = $row['excused_count'];
                $rowData[] = $row['attendance_rate'] . "%";

                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}

