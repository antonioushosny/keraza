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
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant', 'activity_admin']) ?? false;
    }

    public array $stats = [];
    public array $classComparison = [];
    public array $alerts = [];
    public array $attendanceStats = [];
    public array $examStats = [];

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

        // Determine active enrollments (has at least one 'present' or 'excused' attendance)
        $activeEnrollmentsAll = $enrollments->filter(function ($e) {
            return $e->attendance->whereIn('status', ['present', 'excused'])->isNotEmpty();
        });

        // For global stats, use active scores
        $activeScoresList = $activeEnrollmentsAll->map(fn($e) => $scoringService->calculateScore($e));
        
        $avgFinalScore = $activeScoresList->avg('final_score') ?? 0;
        $avgAttendance = $activeScoresList->avg('breakdown.attendance') ?? 0;
        $avgExams = $activeScoresList->avg('breakdown.exams') ?? 0;
        $avgMemorization = $activeScoresList->avg('breakdown.memorization') ?? 0;

        $this->stats = [
            'total_students' => $totalStudents,
            'total_classes' => $totalClasses,
            'avg_final_score' => round($avgFinalScore, 1),
            'avg_attendance' => round($avgAttendance, 1),
            'avg_exams' => round($avgExams, 1),
            'avg_memorization' => round($avgMemorization, 1),
        ];

        // 2. Class Comparison, Attendance, and Exam Stats
        $classes = KerazaClass::all();
        foreach ($classes as $class) {
            $classEnrollments = $enrollments->where('class_id', $class->id);
            if ($classEnrollments->isEmpty()) {
                continue;
            }

            // Identify Active vs Inactive students
            $activeEnrollments = $classEnrollments->filter(function ($e) {
                return $e->attendance->whereIn('status', ['present', 'excused'])->isNotEmpty();
            });
            $inactiveEnrollments = $classEnrollments->reject(function ($e) {
                return $e->attendance->whereIn('status', ['present', 'excused'])->isNotEmpty();
            });

            $activeCount = $activeEnrollments->count();
            $inactiveCount = $inactiveEnrollments->count();

            // Attendance % of total class
            $totalSessionsAll = 0;
            $attendedSessionsAll = 0;
            foreach ($classEnrollments as $e) {
                $totalSessionsAll += $e->attendance->count();
                $attendedSessionsAll += $e->attendance->where('status', 'present')->count() + ($e->attendance->where('status', 'excused')->count() * 0.5);
            }
            $attendanceRateTotal = $totalSessionsAll > 0 ? ($attendedSessionsAll / $totalSessionsAll) * 100 : 100;

            // Attendance % of active students
            $totalSessionsActive = 0;
            $attendedSessionsActive = 0;
            foreach ($activeEnrollments as $e) {
                $totalSessionsActive += $e->attendance->count();
                $attendedSessionsActive += $e->attendance->where('status', 'present')->count() + ($e->attendance->where('status', 'excused')->count() * 0.5);
            }
            $attendanceRateActive = $totalSessionsActive > 0 ? ($attendedSessionsActive / $totalSessionsActive) * 100 : 100;

            $this->attendanceStats[] = [
                'name' => $class->name,
                'total_count' => $classEnrollments->count(),
                'active_count' => $activeCount,
                'inactive_count' => $inactiveCount,
                'rate_total' => round($attendanceRateTotal, 1),
                'rate_active' => round($attendanceRateActive, 1),
            ];

            // Exams stats
            $classExams = \App\Models\Exam::where('class_id', $class->id)->where('season_id', $activeSeason->id)->get();
            $examsCount = $classExams->count();

            $examIds = $classExams->pluck('id')->toArray();
            $allExamScores = \App\Models\ExamScore::whereIn('exam_id', $examIds)->with('exam')->get();

            $activeEnrollmentIds = $activeEnrollments->pluck('id')->toArray();

            $attendeeCounts = [];
            foreach ($classExams as $exam) {
                $attendeeCounts[] = $allExamScores->where('exam_id', $exam->id)
                    ->whereIn('student_season_enrollment_id', $activeEnrollmentIds)
                    ->pluck('student_season_enrollment_id')
                    ->unique()
                    ->count();
            }
            $avgAttendees = count($attendeeCounts) > 0 ? array_sum($attendeeCounts) / count($attendeeCounts) : 0;

            $totalScorePercentageSum = 0;
            $scoresCount = 0;
            $above75Count = 0;
            $above50Count = 0;
            $below50Count = 0;

            foreach ($allExamScores as $es) {
                if (!in_array($es->student_season_enrollment_id, $activeEnrollmentIds)) {
                    continue;
                }
                $total = $es->exam?->total_score ?: 100;
                $percentage = $total > 0 ? ($es->score / $total) * 100 : 0;
                $totalScorePercentageSum += $percentage;
                $scoresCount++;

                if ($percentage > 75) {
                    $above75Count++;
                }
                if ($percentage >= 50) {
                    $above50Count++;
                } else {
                    $below50Count++;
                }
            }

            $avgExamScoreForParticipants = $scoresCount > 0 ? $totalScorePercentageSum / $scoresCount : 0;
            $above75Pct = $scoresCount > 0 ? ($above75Count / $scoresCount) * 100 : 0;
            $above50Pct = $scoresCount > 0 ? ($above50Count / $scoresCount) * 100 : 0;
            $below50Pct = $scoresCount > 0 ? ($below50Count / $scoresCount) * 100 : 0;

            $this->examStats[] = [
                'name' => $class->name,
                'exams_count' => $examsCount,
                'avg_attendees' => round($avgAttendees, 1),
                'avg_score' => round($avgExamScoreForParticipants, 1),
                'above_75_pct' => round($above75Pct, 1),
                'above_50_pct' => round($above50Pct, 1),
                'below_50_pct' => round($below50Pct, 1),
            ];

            // Class performance comparison (based on active students)
            $activeClassScores = $activeEnrollments->map(fn($e) => $scoringService->calculateScore($e));
            
            $this->classComparison[] = [
                'name' => $class->name,
                'students_count' => $classEnrollments->count(),
                'active_students_count' => $activeCount,
                'avg_attendance' => round($activeClassScores->avg('breakdown.attendance') ?? 0, 1),
                'avg_exams' => round($avgExamScoreForParticipants, 1),
                'avg_memorization' => round($activeClassScores->avg('breakdown.memorization') ?? 0, 1),
                'avg_behavior' => round($activeClassScores->avg('breakdown.behavior') ?? 0, 1),
            ];
        }

        // 3. Alerts (Negative Behavior / Low Performance) - Exclude Inactive
        $negativeLogs = BehaviorLog::where('type', 'negative')
            ->whereHas('enrollment', function ($q) use ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            })
            ->with(['enrollment.student', 'enrollment.class', 'enrollment.attendance'])
            ->get();

        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $assignedClassIds = !$isSuperAdmin ? $user->assignedClasses->pluck('id')->toArray() : [];

        foreach ($negativeLogs as $log) {
            $enrollment = $log->enrollment;
            $isActive = $enrollment->attendance->whereIn('status', ['present', 'excused'])->isNotEmpty();
            if (!$isActive) {
                continue;
            }

            // Exclude alerts of classes that the user is not assigned to (if not super_admin)
            if (!$isSuperAdmin && !in_array($enrollment->class_id, $assignedClassIds)) {
                continue;
            }

            $className = $enrollment->class?->name ?? '';
            $this->alerts[] = [
                'student_name' => $enrollment->student->full_name,
                'class_name' => $className,
                'type' => 'سلوك سلبي',
                'description' => $log->reason,
                'value' => $log->points . ' نقطة',
                'severity' => 'danger',
            ];
        }

        foreach ($enrollments as $enrollment) {
            $isActive = $enrollment->attendance->whereIn('status', ['present', 'excused'])->isNotEmpty();
            if (!$isActive) {
                continue;
            }

            // Exclude alerts of classes that the user is not assigned to (if not super_admin)
            if (!$isSuperAdmin && !in_array($enrollment->class_id, $assignedClassIds)) {
                continue;
            }

            $scoreData = $scoringService->calculateScore($enrollment);
            if ($scoreData['breakdown']['exams'] < 50 && $enrollment->examScores->isNotEmpty()) {
                $className = $enrollment->class?->name ?? '';
                $this->alerts[] = [
                    'student_name' => $enrollment->student->full_name,
                    'class_name' => $className,
                    'type' => 'ضعف دراسي (امتحانات)',
                    'description' => 'متوسط درجات الامتحانات يقل عن 50%',
                    'value' => round($scoreData['breakdown']['exams']) . '%',
                    'severity' => 'warning',
                ];
            }
        }
    }
}
