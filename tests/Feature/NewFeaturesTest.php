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
        // - Output is limited to 1 (because honor_roll_limit = 1).
        $rankings = $response->viewData('rankings');
        $this->assertCount(1, $rankings);
        $this->assertNotEquals('Student Zero', $rankings[0]['student_name']);
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
}
