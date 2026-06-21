<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Season;
use App\Models\KerazaClass;
use App\Models\Student;
use App\Models\StudentSeasonEnrollment;
use App\Models\Setting;
use App\Models\AttendanceSession;
use App\Models\Attendance;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected Season $activeSeason;
    protected KerazaClass $classA;
    protected KerazaClass $classB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->activeSeason = Season::create([
            'name' => 'Active Season 2026',
            'is_active' => true,
        ]);

        $this->classA = KerazaClass::create(['name' => 'Class A', 'level' => 1]);
        $this->classB = KerazaClass::create(['name' => 'Class B', 'level' => 2]);
    }

    /** @test */
    public function settings_page_is_restricted_to_super_admin()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $superAdmin->assignRole('super_admin');

        $classAdmin = User::create([
            'name' => 'Class Admin',
            'phone' => '01111111111',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $classAdmin->assignRole('class_admin');

        $this->actingAs($superAdmin, 'admin');
        $this->get('/admin/manage-settings')->assertStatus(200);

        $this->actingAs($classAdmin, 'admin');
        $this->get('/admin/manage-settings')->assertStatus(302);
    }

    /** @test */
    public function leaderboard_controller_filters_and_limits_based_on_settings()
    {
        // Create settings
        $settings = Setting::getSettings();
        $settings->update([
            'honor_roll_limit_enabled' => true,
            'honor_roll_limit' => 1,
            'show_zero_scores' => false,
        ]);

        // Create student 1 (Score 0)
        $student1 = Student::create(['full_name' => 'Student Zero', 'gender' => 'male']);
        $enroll1 = StudentSeasonEnrollment::create([
            'student_id' => $student1->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
        ]);

        // Create student 2 (Score > 0 via attendance)
        $student2 = Student::create(['full_name' => 'Student High 1', 'gender' => 'female']);
        $enroll2 = StudentSeasonEnrollment::create([
            'student_id' => $student2->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
        ]);

        // Create student 3 (Score > 0 via attendance)
        $student3 = Student::create(['full_name' => 'Student High 2', 'gender' => 'male']);
        $enroll3 = StudentSeasonEnrollment::create([
            'student_id' => $student3->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
        ]);

        $session = AttendanceSession::create([
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
            'date' => '2026-06-07',
        ]);

        // Mark Student Zero as absent so they have 0% attendance and score is 0
        Attendance::create([
            'attendance_session_id' => $session->id,
            'student_season_enrollment_id' => $enroll1->id,
            'status' => 'absent',
        ]);

        Attendance::create([
            'attendance_session_id' => $session->id,
            'student_season_enrollment_id' => $enroll2->id,
            'status' => 'present',
        ]);

        Attendance::create([
            'attendance_session_id' => $session->id,
            'student_season_enrollment_id' => $enroll3->id,
            'status' => 'present',
        ]);

        // Get rankings
        $response = $this->get('/?season_id=' . $this->activeSeason->id . '&class_id=' . $this->classA->id);
        $response->assertStatus(200);

        // Under our settings:
        // - Student Zero is filtered out (score is 0).
        // - Output is limited to 1 unique score level (20%).
        // - Both Student High 1 and Student High 2 share the same score, so they are both returned!
        $rankings = $response->viewData('rankings');
        $this->assertCount(2, $rankings);
        
        $this->assertEquals(1, $rankings[0]['rank_position']);
        $this->assertEquals(1, $rankings[1]['rank_position']);
        
        $this->assertFalse($rankings[0]['is_repeated']);
        $this->assertTrue($rankings[1]['is_repeated']);
        
        $this->assertNotEquals('Student Zero', $rankings[0]['student_name']);
        $this->assertNotEquals('Student Zero', $rankings[1]['student_name']);
    }

    /** @test */
    public function class_admin_only_sees_parents_from_their_class()
    {
        $classAdmin = User::create([
            'name' => 'Class Admin A',
            'phone' => '01234567890',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $classAdmin->assignRole('class_admin');
        $classAdmin->assignedClasses()->attach($this->classA->id);

        // Parent A has student in Class A
        $parentA = User::create([
            'name' => 'Parent A',
            'phone' => '01222222222',
            'password' => bcrypt('password'),
            'type' => 'parent',
        ]);
        $parentA->assignRole('parent');
        $studentA = Student::create(['full_name' => 'Student A', 'gender' => 'male', 'parent_id' => $parentA->id]);
        StudentSeasonEnrollment::create([
            'student_id' => $studentA->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
        ]);

        // Parent B has student in Class B
        $parentB = User::create([
            'name' => 'Parent B',
            'phone' => '01333333333',
            'password' => bcrypt('password'),
            'type' => 'parent',
        ]);
        $parentB->assignRole('parent');
        $studentB = Student::create(['full_name' => 'Student B', 'gender' => 'female', 'parent_id' => $parentB->id]);
        StudentSeasonEnrollment::create([
            'student_id' => $studentB->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classB->id,
        ]);

        $this->actingAs($classAdmin, 'admin');

        // Fetch query list for parent resource in Filament
        $parents = \App\Filament\Resources\ParentResource::getEloquentQuery()->get();

        $this->assertTrue($parents->contains('id', $parentA->id));
        $this->assertFalse($parents->contains('id', $parentB->id));
    }

    /** @test */
    public function exam_report_sorting_and_export_works_properly()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin, 'admin');

        $parent1 = User::create(['name' => 'Parent One', 'phone' => '01234567891', 'password' => bcrypt('password'), 'type' => 'parent']);
        $parent2 = User::create(['name' => 'Parent Two', 'phone' => '01234567892', 'password' => bcrypt('password'), 'type' => 'parent']);

        $student1 = Student::create(['full_name' => 'Alice Student', 'gender' => 'female', 'birth_date' => '2015-05-15', 'parent_id' => $parent1->id]);
        $student2 = Student::create(['full_name' => 'Bob Student', 'gender' => 'male', 'birth_date' => '2016-06-16', 'parent_id' => $parent2->id]);

        $enroll1 = StudentSeasonEnrollment::create(['student_id' => $student1->id, 'season_id' => $this->activeSeason->id, 'class_id' => $this->classA->id]);
        $enroll2 = StudentSeasonEnrollment::create(['student_id' => $student2->id, 'season_id' => $this->activeSeason->id, 'class_id' => $this->classA->id]);

        $cat1 = \App\Models\ExamCategory::create(['name' => 'دراسات كتابية']);
        $cat2 = \App\Models\ExamCategory::create(['name' => 'طقس كنسي']);

        $exam1 = \App\Models\Exam::create([
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
            'category_id' => $cat1->id,
            'title' => 'اختبار دراسات كتابية',
            'total_score' => 100,
            'date' => '2026-06-20',
        ]);

        $exam2 = \App\Models\Exam::create([
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
            'category_id' => $cat2->id,
            'title' => 'اختبار طقس كنسي',
            'total_score' => 100,
            'date' => '2026-06-20',
        ]);

        // Alice: Written = 100, Rituals = 80
        \App\Models\ExamScore::create(['student_season_enrollment_id' => $enroll1->id, 'exam_id' => $exam1->id, 'score' => 100]);
        \App\Models\ExamScore::create(['student_season_enrollment_id' => $enroll1->id, 'exam_id' => $exam2->id, 'score' => 80]);

        // Bob: Written = 90, Rituals = 95
        \App\Models\ExamScore::create(['student_season_enrollment_id' => $enroll2->id, 'exam_id' => $exam1->id, 'score' => 90]);
        \App\Models\ExamScore::create(['student_season_enrollment_id' => $enroll2->id, 'exam_id' => $exam2->id, 'score' => 95]);

        $test = \Livewire\Livewire::test(\App\Filament\Pages\ExamReport::class)
            ->set('selectedClassId', $this->classA->id);

        // Assert percentage sorting: Bob (92.5%) then Alice (90.0%)
        $reportData = $test->get('reportData');
        $this->assertEquals('Bob Student', $reportData[0]['student_name']);
        $this->assertEquals('Alice Student', $reportData[1]['student_name']);

        // Sort by Written exam category (cat1) descending: Alice (100) then Bob (90)
        $test->call('sortBy', 'category_' . $cat1->id);
        $reportData = $test->get('reportData');
        $this->assertEquals('Alice Student', $reportData[0]['student_name']);
        $this->assertEquals('Bob Student', $reportData[1]['student_name']);

        // Sort by Written exam category (cat1) ascending: Bob (90) then Alice (100)
        $test->call('sortBy', 'category_' . $cat1->id);
        $reportData = $test->get('reportData');
        $this->assertEquals('Bob Student', $reportData[0]['student_name']);
        $this->assertEquals('Alice Student', $reportData[1]['student_name']);

        // Test export CSV
        $response = $test->call('export');
        $response->assertStatus(200);
        
        // Assert download headers and CSV content
        $downloadFile = $response->effects['download'] ?? null;
        $this->assertNotNull($downloadFile);
        $this->assertStringContainsString('تقرير_الامتحانات_', $downloadFile['name']);
    }
}
