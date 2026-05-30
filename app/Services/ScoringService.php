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
            $attendedSessions = $enrollment->attendance->whereIn('status', ['present', 'excused'])->count();
        } else {
            $totalSessions = $enrollment->attendance()->count();
            $attendedSessions = $enrollment->attendance()->whereIn('status', ['present', 'excused'])->count();
        }
        
        $attendanceScore = $totalSessions > 0 ? ($attendedSessions / $totalSessions) * 100 : 100;

        $examScore = $enrollment->relationLoaded('examScores')
            ? $enrollment->examScores->avg('score')
            : $enrollment->examScores()->avg('score');
        $examScore = $examScore ?? 0;

        $memorizationScore = $enrollment->relationLoaded('memorizationScores')
            ? $enrollment->memorizationScores->avg('score')
            : $enrollment->memorizationScores()->avg('score');
        $memorizationScore = $memorizationScore ?? 0;
        
        $activityScore = 0;
        if ($enrollment->relationLoaded('activityEnrollments')) {
            $activityScore = $enrollment->activityEnrollments
                ->flatMap(fn($e) => $e->relationLoaded('scores') ? $e->scores : $e->scores()->get())
                ->avg('score') ?? 0;
        } else {
            $activityScore = $enrollment->activityEnrollments()
                ->with('scores')
                ->get()
                ->flatMap(fn($e) => $e->scores)
                ->avg('score') ?? 0;
        }

        $behaviorPoints = $enrollment->relationLoaded('behaviorLogs')
            ? $enrollment->behaviorLogs->sum('points')
            : $enrollment->behaviorLogs()->sum('points');

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
            ]
        ];
    }

    public function getRankings(int $seasonId, int $classId = null): Collection
    {
        $enrollments = StudentSeasonEnrollment::where('season_id', $seasonId)
            ->when($classId, fn($q) => $q->where('class_id', $classId))
            ->with(['attendance', 'examScores', 'memorizationScores', 'activityEnrollments.scores', 'behaviorLogs', 'student'])
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
                'examScores', 
                'memorizationScores', 
                'activityEnrollments.scores', 
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
                ]),
                'data' => $scoreData,
            ];
        })->sortByDesc('score')->values();
    }
}
