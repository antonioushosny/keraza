<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\KerazaClass;
use App\Models\Season;
use App\Models\ActivityEnrollment;
use App\Services\ScoringService;
use Filament\Pages\Page;

class ActivityReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'تقرير الأنشطة المطور';
    protected static ?string $navigationLabel = 'تقرير الأنشطة المطور';
    protected static string $view = 'filament.pages.activity-report';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 6;

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

        if (count($this->activities) === 1) {
            $this->selectedActivityId = $this->activities[0]['id'];
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

    public function loadReport(): void
    {
        if (!$this->selectedActivityId) {
            $this->reportData = [];
            return;
        }

        $query = ActivityEnrollment::where('activity_id', $this->selectedActivityId)
            ->with(['enrollment.student', 'enrollment.class', 'activity']);

        if ($this->selectedClassId) {
            $query->whereHas('enrollment', function ($q) {
                $q->where('class_id', $this->selectedClassId);
            });
        } else {
            $user = auth()->user();
            if (!$user->hasRole('super_admin') && !$user->hasRole('activity_admin')) {
                $assignedClassIds = $user->assignedClasses->pluck('id')->toArray();
                $query->whereHas('enrollment', function ($q) use ($assignedClassIds) {
                    $q->whereIn('class_id', $assignedClassIds);
                });
            }
        }

        $enrollments = $query->get();
        $scoringService = app(ScoringService::class);

        $report = [];
        foreach ($enrollments as $ae) {
            $scores = $scoringService->calculateActivityEnrollmentScore($ae);
            $report[] = [
                'enrollment_id' => $ae->id,
                'student_name' => $ae->enrollment?->student?->full_name ?? 'غير معروف',
                'profile_image' => $ae->enrollment?->student?->profile_image ?? null,
                'class_name' => $ae->enrollment?->class?->name ?? 'غير معروف',
                'attendance_score' => $scores['attendance'],
                'tasks_score' => $scores['tasks'],
                'evaluation_score' => $scores['evaluation'],
                'final_score' => $scores['final'],
            ];
        }

        usort($report, function ($a, $b) {
            if ($b['final_score'] === $a['final_score']) {
                return strcmp($a['student_name'], $b['student_name']);
            }
            return $b['final_score'] <=> $a['final_score'];
        });

        $this->reportData = $report;
    }

    public function export()
    {
        if (!$this->selectedActivityId || empty($this->reportData)) {
            return;
        }

        $activity = Activity::find($this->selectedActivityId);
        $activityTitle = $activity ? $activity->title : 'نشاط';
        $filename = "تقرير_نشاط_{$activityTitle}_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'الترتيب',
                'المخدوم',
                'الفصل',
                'حضور الأنشطة',
                'مهام الأنشطة',
                'التقييم العام للنشاط',
                'الدرجة الكلية الموزونة'
            ]);

            foreach ($this->reportData as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row['student_name'],
                    $row['class_name'],
                    $row['attendance_score'] . '%',
                    $row['tasks_score'] . '%',
                    $row['evaluation_score'] . '%',
                    $row['final_score'] . '%'
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
