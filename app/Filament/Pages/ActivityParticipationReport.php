<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\KerazaClass;
use App\Models\Season;
use App\Models\StudentSeasonEnrollment;
use App\Models\ActivityEnrollment;
use App\Services\ScoringService;
use Filament\Pages\Page;

class ActivityParticipationReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'تقرير مشاركات الأنشطة';
    protected static ?string $navigationLabel = 'تقرير مشاركات الأنشطة';
    protected static string $view = 'filament.pages.activity-participation-report';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 7;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant', 'activity_admin']) ?? false;
    }

    public ?int $selectedActivityId = null;
    public ?int $selectedClassId = null;
    public array $activities = [];
    public array $classes = [];
    public array $reportData = [];
    public ?string $seasonName = null;
    public string $sortField = 'max_activity_score';
    public string $sortDirection = 'desc';

    // State for the student details modal
    public ?array $selectedStudentDetails = null;

    public function mount(): void
    {
        $user = auth()->user();
        $activeSeason = Season::active();
        
        if ($activeSeason) {
            $this->seasonName = $activeSeason->name;
            
            // Fetch activities
            $activitiesQuery = Activity::where('season_id', $activeSeason->id);
            if (!$user->hasRole('super_admin') && $user->hasRole('activity_admin')) {
                $activitiesQuery->whereIn('id', $user->assignedActivities->pluck('id'));
            }
            $this->activities = $activitiesQuery->orderBy('title')->get()->map(fn($a) => ['id' => $a->id, 'title' => $a->title])->toArray();
        }

        if ($user->hasRole('super_admin') || $user->hasRole('activity_admin')) {
            $this->classes = KerazaClass::orderBy('level')->get()->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        } else {
            $this->classes = $user->assignedClasses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        }

        if (count($this->classes) === 1) {
            $this->selectedClassId = $this->classes[0]['id'];
        }

        $this->loadReport();
    }

    public function updatedSelectedActivityId(): void
    {
        $this->loadReport();
    }

    public function updatedSelectedClassId(): void
    {
        $this->loadReport();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $activeSeason = Season::active();
        if (!$activeSeason) {
            $this->reportData = [];
            return;
        }

        // Fetch enrollments with relations
        $enrollmentsQuery = StudentSeasonEnrollment::where('season_id', $activeSeason->id)
            ->with(['student.parent', 'class', 'activityEnrollments.activity']);

        // Filter by class
        if ($this->selectedClassId) {
            $enrollmentsQuery->where('class_id', $this->selectedClassId);
        } else {
            $user = auth()->user();
            if (!$user->hasRole('super_admin') && !$user->hasRole('activity_admin')) {
                $assignedClassIds = $user->assignedClasses->pluck('id')->toArray();
                $enrollmentsQuery->whereIn('class_id', $assignedClassIds);
            }
        }

        // Filter by activity if selected
        if ($this->selectedActivityId) {
            $enrollmentsQuery->whereHas('activityEnrollments', function ($q) {
                $q->where('activity_id', $this->selectedActivityId);
            });
        }

        $enrollments = $enrollmentsQuery->get();
        $scoringService = app(ScoringService::class);

        $report = [];
        foreach ($enrollments as $enrollment) {
            $studentActivities = [];
            $activityScores = [];

            foreach ($enrollment->activityEnrollments as $ae) {
                $scores = $scoringService->calculateActivityEnrollmentScore($ae);
                $studentActivities[$ae->activity_id] = [
                    'title' => $ae->activity?->title ?? 'نشاط',
                    'attendance' => $scores['attendance'],
                    'tasks' => $scores['tasks'],
                    'evaluation' => $scores['evaluation'],
                    'final' => $scores['final'],
                ];
                $activityScores[] = $scores['final'];
            }

            // The final evaluation for activities takes the maximum score
            $maxActivityScore = count($activityScores) > 0 ? max($activityScores) : 0;

            $report[] = [
                'enrollment_id' => $enrollment->id,
                'student_name' => $enrollment->student->full_name,
                'gender' => $enrollment->student->gender,
                'profile_image' => $enrollment->student->profile_image,
                'birth_date' => $enrollment->student->birth_date,
                'parent_phone' => $enrollment->student->parent?->phone ?? '-',
                'class_name' => $enrollment->class?->name ?? 'غير معروف',
                'student_activities' => $studentActivities,
                'max_activity_score' => $maxActivityScore,
                'has_activities' => count($activityScores) > 0,
            ];
        }

        // Apply sorting
        if (str_starts_with($this->sortField, 'activity_')) {
            $activityId = (int) str_replace('activity_', '', $this->sortField);
            usort($report, function ($a, $b) use ($activityId) {
                $valA = $a['student_activities'][$activityId]['final'] ?? -1;
                $valB = $b['student_activities'][$activityId]['final'] ?? -1;

                if ($valA === $valB) {
                    return strcmp($a['student_name'], $b['student_name']);
                }

                return $this->sortDirection === 'asc'
                    ? $valA <=> $valB
                    : $valB <=> $valA;
            });
        } elseif ($this->sortField === 'name') {
            usort($report, function ($a, $b) {
                return $this->sortDirection === 'asc'
                    ? strcmp($a['student_name'], $b['student_name'])
                    : strcmp($b['student_name'], $a['student_name']);
            });
        } else {
            // Sort by maximum activity score
            usort($report, function ($a, $b) {
                $valA = $a['max_activity_score'];
                $valB = $b['max_activity_score'];

                if ($valA === $valB) {
                    return strcmp($a['student_name'], $b['student_name']);
                }

                return $this->sortDirection === 'asc'
                    ? $valA <=> $valB
                    : $valB <=> $valA;
            });
        }

        $this->reportData = $report;
    }

    public function showStudentDetails(int $enrollmentId): void
    {
        $enrollment = StudentSeasonEnrollment::with(['student.parent', 'class', 'activityEnrollments.activity'])
            ->find($enrollmentId);

        if (!$enrollment) {
            return;
        }

        $scoringService = app(ScoringService::class);
        $activities = [];

        foreach ($enrollment->activityEnrollments as $ae) {
            $scores = $scoringService->calculateActivityEnrollmentScore($ae);
            $activities[] = [
                'title' => $ae->activity?->title ?? 'نشاط',
                'attendance' => $scores['attendance'],
                'tasks' => $scores['tasks'],
                'evaluation' => $scores['evaluation'],
                'final' => $scores['final'],
            ];
        }

        $this->selectedStudentDetails = [
            'student_name' => $enrollment->student->full_name,
            'class_name' => $enrollment->class?->name ?? 'غير معروف',
            'birth_date' => $enrollment->student->birth_date ?? '-',
            'parent_phone' => $enrollment->student->parent?->phone ?? '-',
            'gender' => $enrollment->student->gender === 'male' ? 'ذكر' : 'أنثى',
            'activities' => $activities,
        ];
    }

    public function closeStudentDetails(): void
    {
        $this->selectedStudentDetails = null;
    }

    public function export()
    {
        if (empty($this->reportData)) {
            return;
        }

        $filename = "تقرير_مشاركات_الأنشطة_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            // Build headers
            $headers = [
                'الترتيب',
                'اسم المخدوم',
                'الفصل',
                'تاريخ الميلاد',
                'رقم موبايل ولي الامر',
                'النوع'
            ];

            foreach ($this->activities as $act) {
                $headers[] = 'تقييم ' . $act['title'];
            }
            $headers[] = 'أعلى تقييم (الأنشطة)';

            fputcsv($file, $headers);

            // Build rows
            foreach ($this->reportData as $index => $row) {
                $rowData = [
                    $index + 1,
                    $row['student_name'],
                    $row['class_name'],
                    $row['birth_date'] ?? '-',
                    $row['parent_phone'] ?? '-',
                    $row['gender'] === 'male' ? 'ذكر' : 'أنثى'
                ];

                foreach ($this->activities as $act) {
                    $score = $row['student_activities'][$act['id']]['final'] ?? null;
                    $rowData[] = $score !== null ? $score . '%' : '-';
                }

                $rowData[] = $row['max_activity_score'] . '%';

                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
