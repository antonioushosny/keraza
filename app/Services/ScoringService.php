<?php

namespace App\Services;

use App\Models\StudentSeasonEnrollment;
use App\Models\ScoringRule;
use Illuminate\Support\Collection;

class ScoringService
{
    public function calculateScore(StudentSeasonEnrollment $enrollment): array
    {
        // Cache scoring rules to avoid repeated queries for the same season/class
        static $rules = [];
        $ruleKey = "{$enrollment->season_id}_{$enrollment->class_id}";
        
        if (!isset($rules[$ruleKey])) {
            $rules[$ruleKey] = ScoringRule::where('season_id', $enrollment->season_id)
                ->where(function ($query) use ($enrollment) {
                    $query->where('class_id', $enrollment->class_id)
                          ->orWhereNull('class_id');
                })
                ->orderByDesc('class_id')
                ->first() ?: (object) [
                    'weight_attendance' => 20,
                    'weight_exams' => 30,
                    'weight_memorization' => 20,
                    'weight_activities' => 20,
                    'weight_behavior' => 10,
                ];
        }

        $rule = $rules[$ruleKey];

        // Check if relations are already loaded to avoid N+1 queries
        if ($enrollment->relationLoaded('attendance')) {
            $totalSessions = $enrollment->attendance->count();
            $presentCount = $enrollment->attendance->where('status', 'present')->count();
            $excusedCount = $enrollment->attendance->where('status', 'excused')->count();
            $attendedSessions = $presentCount + ($excusedCount * 0.5);
        } else {
            $attendances = $enrollment->attendance()->select('status')->get();
            $totalSessions = $attendances->count();
            $presentCount = $attendances->where('status', 'present')->count();
            $excusedCount = $attendances->where('status', 'excused')->count();
            $attendedSessions = $presentCount + ($excusedCount * 0.5);
        }
        
        $attendanceScore = $totalSessions > 0 ? ($attendedSessions / $totalSessions) * 100 : 100;

        if ($enrollment->relationLoaded('examScores')) {
            $examScores = $enrollment->examScores;
        } else {
            $examScores = $enrollment->examScores()->with('exam')->get();
        }

        if ($examScores->isNotEmpty()) {
            $examScore = $examScores->map(function ($es) {
                $total = $es->exam?->total_score ?: 100;
                return $total > 0 ? ($es->score / $total) * 100 : 0;
            })->average();
        } else {
            $examScore = 0;
        }

        if ($enrollment->relationLoaded('memorizationScores')) {
            $memorizationScores = $enrollment->memorizationScores;
        } else {
            $memorizationScores = $enrollment->memorizationScores()->with('memorizationItem')->get();
        }

        if ($memorizationScores->isNotEmpty()) {
            $memorizationScore = $memorizationScores->map(function ($ms) {
                $max = $ms->memorizationItem?->max_points ?: 100;
                return $max > 0 ? ($ms->score / $max) * 100 : 0;
            })->average();
        } else {
            $memorizationScore = 0;
        }
        
        $activityScore = 0;
        $enrollmentActivities = $enrollment->activityEnrollments;
        if ($enrollmentActivities->isNotEmpty()) {
            $activityScoresList = $enrollmentActivities->map(function ($ae) {
                return $this->calculateActivityEnrollmentScore($ae)['final'];
            });
            $activityScore = $activityScoresList->average() ?? 0;
        }

        $behaviorPoints = $enrollment->relationLoaded('behaviorLogs')
            ? $enrollment->behaviorLogs->sum('points')
            : $enrollment->behaviorLogs()->sum('points');

        // Cap behavior points between 0 and 100 so it does not exceed its weight (100% of the weight)
        $behaviorPoints = max(0, min(100, $behaviorPoints));

        // Weighted calculation
        $finalScore = (
            ($attendanceScore * ($rule->weight_attendance / 100)) +
            ($examScore * ($rule->weight_exams / 100)) +
            ($memorizationScore * ($rule->weight_memorization / 100)) +
            ($activityScore * ($rule->weight_activities / 100)) +
            ($behaviorPoints * ($rule->weight_behavior / 100))
        );

        return [
            'final_score' => round($finalScore, 2),
            'breakdown' => [
                'attendance' => round($attendanceScore, 2),
                'exams' => round($examScore, 2),
                'memorization' => round($memorizationScore, 2),
                'activities' => round($activityScore, 2),
                'behavior' => $behaviorPoints,
            ],
            'weights' => [
                'attendance' => $rule->weight_attendance,
                'exams' => $rule->weight_exams,
                'memorization' => $rule->weight_memorization,
                'activities' => $rule->weight_activities,
                'behavior' => $rule->weight_behavior,
            ],
            'weighted_breakdown' => [
                'attendance' => round($attendanceScore * ($rule->weight_attendance / 100), 2),
                'exams' => round($examScore * ($rule->weight_exams / 100), 2),
                'memorization' => round($memorizationScore * ($rule->weight_memorization / 100), 2),
                'activities' => round($activityScore * ($rule->weight_activities / 100), 2),
                'behavior' => round($behaviorPoints * ($rule->weight_behavior / 100), 2),
            ],
        ];
    }

    public function calculateActivityEnrollmentScore($ae): array
    {
        $activity = $ae->activity ?? \App\Models\Activity::find($ae->activity_id);
        if (!$activity) {
            return [
                'attendance' => 0,
                'tasks' => 0,
                'evaluation' => 0,
                'final' => 0,
            ];
        }

        // 1. Attendance Score
        $totalSessions = \App\Models\ActivityAttendanceSession::where('activity_id', $ae->activity_id)->count();
        if ($totalSessions > 0) {
            $present = \App\Models\ActivityAttendance::where('activity_enrollment_id', $ae->id)
                ->where('status', 'present')
                ->count();
            $excused = \App\Models\ActivityAttendance::where('activity_enrollment_id', $ae->id)
                ->where('status', 'excused')
                ->count();
            $attScore = (($present + ($excused * 0.5)) / $totalSessions) * 100;
        } else {
            $attScore = 0;
        }

        // 2. Tasks Score
        $totalMaxScore = \App\Models\ActivityTask::where('activity_id', $ae->activity_id)->sum('max_score');
        if ($totalMaxScore > 0) {
            $studentTaskSum = \App\Models\ActivityTaskScore::where('activity_enrollment_id', $ae->id)->sum('score');
            $tasksScore = ($studentTaskSum / $totalMaxScore) * 100;
        } else {
            $tasksScore = 0;
        }

        // 3. Evaluation Score
        $evalScore = \App\Models\ActivityScore::where('activity_enrollment_id', $ae->id)->avg('score') ?? 0;

        // 4. Final Weighted Score
        $wAttendance = $activity->weight_attendance ?? 20;
        $wTasks = $activity->weight_tasks ?? 30;
        $wEvaluation = $activity->weight_evaluation ?? 50;

        $final = ($attScore * ($wAttendance / 100)) + ($tasksScore * ($wTasks / 100)) + ($evalScore * ($wEvaluation / 100));

        return [
            'attendance' => round($attScore, 2),
            'tasks' => round($tasksScore, 2),
            'evaluation' => round($evalScore, 2),
            'final' => round($final, 2),
        ];
    }

    public function getRankings(int $seasonId, int $classId = null): Collection
    {
        $enrollments = StudentSeasonEnrollment::where('season_id', $seasonId)
            ->when($classId, fn($q) => $q->where('class_id', $classId))
            ->with(['attendance', 'examScores.exam', 'memorizationScores.memorizationItem', 'activityEnrollments.scores', 'activityEnrollments.activity', 'behaviorLogs', 'student'])
            ->get();

        return $enrollments->map(function ($enrollment) {
            $scoreData = $this->calculateScore($enrollment);
            return [
                'enrollment_id' => $enrollment->id,
                'student_name' => $enrollment->student->full_name,
                'score' => $scoreData['final_score'],
                'data' => $scoreData,
            ];
        })->sortByDesc('score')->values();
    }

    public function getRankingsWithBadges(int $seasonId, int $classId = null): Collection
    {
        $enrollments = StudentSeasonEnrollment::where('season_id', $seasonId)
            ->when($classId, fn($q) => $q->where('class_id', $classId))
            ->with([
                'student', 
                'badges.badge', 
                'class',
                'attendance', 
                'examScores.exam', 
                'memorizationScores.memorizationItem',  
                'activityEnrollments.scores', 
                'activityEnrollments.activity',
                'behaviorLogs'
            ])
            ->get();

        return $enrollments->map(function ($enrollment) {
            $scoreData = $this->calculateScore($enrollment);
            return [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student->full_name,
                'profile_image' => $enrollment->student->profile_image,
                'class_name' => $enrollment->class?->name,
                'score' => $scoreData['final_score'],
                'badges' => $enrollment->badges->map(fn($sb) => [
                    'title' => $sb->badge->title,
                    'icon' => $sb->badge->icon,
                ])->toArray(),
                'data' => $scoreData,
            ];
        })->sortByDesc('score')->values();
    }
}
