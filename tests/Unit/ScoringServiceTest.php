<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Season;
use App\Models\KerazaClass;
use App\Models\Student;
use App\Models\StudentSeasonEnrollment;
use App\Models\ScoringRule;
use App\Models\ExamCategory;
use App\Models\Exam;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_weighted_score_correctly()
    {
        $service = new ScoringService();

        $season = Season::create(['name' => 'Test Season', 'is_active' => true]);
        $class = KerazaClass::create(['season_id' => $season->id, 'name' => 'Test Class']);
        $student = Student::create(['full_name' => 'Test Student', 'gender' => 'male']);
        
        $enrollment = StudentSeasonEnrollment::create([
            'student_id' => $student->id,
            'season_id' => $season->id,
            'class_id' => $class->id,
        ]);

        // Create scoring rules
        ScoringRule::create([
            'season_id' => $season->id,
            'weight_attendance' => 20,
            'weight_exams' => 30,
            'weight_memorization' => 20,
            'weight_activities' => 20,
            'weight_behavior' => 10,
        ]);

        // Create dependencies for ExamScore
        $category = ExamCategory::create(['name' => 'Academic']);
        $exam = Exam::create([
            'category_id' => $category->id,
            'class_id' => $class->id,
            'season_id' => $season->id,
            'title' => 'Midterm',
            'total_score' => 100
        ]);

        // Mock some data
        $enrollment->attendance()->create(['date' => now(), 'status' => 'present', 'points' => 100]); // 100 * 0.2 = 20
        $enrollment->examScores()->create(['exam_id' => $exam->id, 'score' => 80]); // 80 * 0.3 = 24
        
        $scoreData = $service->calculateScore($enrollment);

        // Expected: (100 * 0.2) + (80 * 0.3) + 0 + 0 + 0 = 20 + 24 = 44
        $this->assertEquals(44, $scoreData['final_score']);
    }

    public function test_it_calculates_attendance_score_with_excused_status_correctly()
    {
        $service = new ScoringService();

        $season = Season::create(['name' => 'Test Season 2', 'is_active' => true]);
        $class = KerazaClass::create(['season_id' => $season->id, 'name' => 'Test Class 2']);
        $student = Student::create(['full_name' => 'Test Student 2', 'gender' => 'male']);
        
        $enrollment = StudentSeasonEnrollment::create([
            'student_id' => $student->id,
            'season_id' => $season->id,
            'class_id' => $class->id,
        ]);

        ScoringRule::create([
            'season_id' => $season->id,
            'weight_attendance' => 20,
            'weight_exams' => 30,
            'weight_memorization' => 20,
            'weight_activities' => 20,
            'weight_behavior' => 10,
        ]);

        // Mock 2 present attendances and 2 excused attendances (total 4 sessions)
        // present = 2 * 1 = 2
        // excused = 2 * 0.5 = 1
        // total points = 3. Out of 4 total sessions, attendance score should be 3/4 * 100 = 75%.
        $enrollment->attendance()->create(['date' => now()->subDays(3), 'status' => 'present']);
        $enrollment->attendance()->create(['date' => now()->subDays(2), 'status' => 'present']);
        $enrollment->attendance()->create(['date' => now()->subDays(1), 'status' => 'excused']);
        $enrollment->attendance()->create(['date' => now(), 'status' => 'excused']);

        $scoreData = $service->calculateScore($enrollment);

        // Expected attendance score: 75
        $this->assertEquals(75, $scoreData['breakdown']['attendance']);
        // Final score: 75 * 0.2 = 15
        $this->assertEquals(15, $scoreData['final_score']);
    }

    public function test_it_caps_behavior_points_between_0_and_100()
    {
        $service = new ScoringService();

        $season = Season::create(['name' => 'Test Season 3', 'is_active' => true]);
        $class = KerazaClass::create(['season_id' => $season->id, 'name' => 'Test Class 3']);
        $student = Student::create(['full_name' => 'Test Student 3', 'gender' => 'male']);
        
        $enrollment = StudentSeasonEnrollment::create([
            'student_id' => $student->id,
            'season_id' => $season->id,
            'class_id' => $class->id,
        ]);

        ScoringRule::create([
            'season_id' => $season->id,
            'weight_attendance' => 20,
            'weight_exams' => 30,
            'weight_memorization' => 20,
            'weight_activities' => 20,
            'weight_behavior' => 10,
        ]);

        // Case 1: Behavior points exceeds 100 (e.g. 150 points)
        $enrollment->behaviorLogs()->create(['points' => 150, 'type' => 'positive', 'reason' => 'Excellent']);
        $scoreData = $service->calculateScore($enrollment);
        // Should cap behaviorPoints at 100, so weighted behavior score is 100 * 0.10 = 10
        // Final score: 20 (default attendance 100%) + 10 (behavior) = 30
        $this->assertEquals(100, $scoreData['breakdown']['behavior']);
        $this->assertEquals(30, $scoreData['final_score']);

        // Case 2: Behavior points is negative (e.g. -50 points)
        $enrollment->behaviorLogs()->delete();
        $enrollment->behaviorLogs()->create(['points' => -50, 'type' => 'negative', 'reason' => 'Bad']);
        $scoreData = $service->calculateScore($enrollment);
        // Should floor behaviorPoints at 0, so weighted behavior score is 0
        // Final score: 20 (default attendance 100%) + 0 (behavior) = 20
        $this->assertEquals(0, $scoreData['breakdown']['behavior']);
        $this->assertEquals(20, $scoreData['final_score']);
    }
}
