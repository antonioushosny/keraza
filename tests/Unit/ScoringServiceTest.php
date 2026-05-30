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
}
