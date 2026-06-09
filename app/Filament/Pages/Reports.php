<?php

namespace App\Filament\Pages;

use App\Models\Season;
use App\Models\KerazaClass;
use App\Models\StudentSeasonEnrollment;
use App\Models\BehaviorLog;
use App\Services\ScoringService;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $title = 'التقارير والإحصائيات';
    protected static ?string $navigationLabel = 'التقارير';
    protected static string $view = 'filament.pages.reports';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public array $stats = [];
    public array $classComparison = [];
    public array $alerts = [];

    public function mount(ScoringService $scoringService): void
    {
        $activeSeason = Season::active();
        if (!$activeSeason) {
            return;
        }

        // 1. Core Stats
        $enrollments = StudentSeasonEnrollment::where('season_id', $activeSeason->id)
            ->with(['attendance', 'examScores.exam', 'memorizationScores.memorizationItem', 'activityEnrollments.scores', 'behaviorLogs', 'student'])
            ->get();

        $totalStudents = $enrollments->count();
        $totalClasses = KerazaClass::count();

        $scoresList = $enrollments->map(fn($e) => $scoringService->calculateScore($e));
        
        $avgFinalScore = $scoresList->avg('final_score') ?? 0;
        $avgAttendance = $scoresList->avg('breakdown.attendance') ?? 0;
        $avgExams = $scoresList->avg('breakdown.exams') ?? 0;
        $avgMemorization = $scoresList->avg('breakdown.memorization') ?? 0;

        $this->stats = [
            'total_students' => $totalStudents,
            'total_classes' => $totalClasses,
            'avg_final_score' => round($avgFinalScore, 1),
            'avg_attendance' => round($avgAttendance, 1),
            'avg_exams' => round($avgExams, 1),
            'avg_memorization' => round($avgMemorization, 1),
        ];

        // 2. Class Comparison
        $classes = KerazaClass::all();
        foreach ($classes as $class) {
            $classEnrollments = $enrollments->where('class_id', $class->id);
            if ($classEnrollments->isEmpty()) {
                continue;
            }

            $classScores = $classEnrollments->map(fn($e) => $scoringService->calculateScore($e));
            
            $this->classComparison[] = [
                'name' => $class->name,
                'students_count' => $classEnrollments->count(),
                'avg_attendance' => round($classScores->avg('breakdown.attendance') ?? 0, 1),
                'avg_exams' => round($classScores->avg('breakdown.exams') ?? 0, 1),
                'avg_memorization' => round($classScores->avg('breakdown.memorization') ?? 0, 1),
                'avg_behavior' => round($classScores->avg('breakdown.behavior') ?? 0, 1),
            ];
        }

        // 3. Alerts (Negative Behavior / Low Performance)
        $negativeLogs = BehaviorLog::where('type', 'negative')
            ->whereHas('enrollment', function ($q) use ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            })
            ->with('enrollment.student')
            ->get();

        foreach ($negativeLogs as $log) {
            $this->alerts[] = [
                'student_name' => $log->enrollment->student->full_name,
                'type' => 'سلوك سلبي',
                'description' => $log->reason,
                'value' => $log->points . ' نقطة',
                'severity' => 'danger',
            ];
        }

        foreach ($enrollments as $enrollment) {
            $scoreData = $scoringService->calculateScore($enrollment);
            if ($scoreData['breakdown']['exams'] < 50 && $enrollment->examScores->isNotEmpty()) {
                $this->alerts[] = [
                    'student_name' => $enrollment->student->full_name,
                    'type' => 'ضعف دراسي (امتحانات)',
                    'description' => 'متوسط درجات الامتحانات يقل عن 50%',
                    'value' => round($scoreData['breakdown']['exams']) . '%',
                    'severity' => 'warning',
                ];
            }
        }
    }
}
