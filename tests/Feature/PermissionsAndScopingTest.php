<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Season;
use App\Models\KerazaClass;
use App\Models\Student;
use App\Models\StudentSeasonEnrollment;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\ActivityResource;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsAndScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Season $activeSeason;
    protected KerazaClass $classA;
    protected KerazaClass $classB;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create active season
        $this->activeSeason = Season::create([
            'name' => 'Keraza 2026',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        // Create classes
        $this->classA = KerazaClass::create(['name' => 'Class A', 'level' => 1]);
        $this->classB = KerazaClass::create(['name' => 'Class B', 'level' => 2]);
    }

    public function test_super_admin_can_access_user_resource_and_student_resource(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(StudentResource::canViewAny());
    }

    public function test_class_servant_cannot_access_user_resource_but_can_access_student_resource(): void
    {
        $servant = User::create([
            'name' => 'Servant',
            'phone' => '01222222222',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $servant->assignRole('class_servant');

        $this->actingAs($servant);

        // Blocked from UserResource
        $this->assertFalse(UserResource::canViewAny());
        // Can access StudentResource
        $this->assertTrue(StudentResource::canViewAny());
    }

    public function test_activity_admin_can_access_activity_resource_but_cannot_access_student_resource(): void
    {
        $actAdmin = User::create([
            'name' => 'Activity Admin',
            'phone' => '01333333333',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $actAdmin->assignRole('activity_admin');

        $this->actingAs($actAdmin);

        // Can access ActivityResource
        $this->assertTrue(ActivityResource::canViewAny());
        // Blocked from StudentResource
        $this->assertFalse(StudentResource::canViewAny());
    }

    public function test_class_servant_only_sees_students_from_their_assigned_class(): void
    {
        // 1. Create students
        $studentA = Student::create([
            'full_name' => 'Student A',
            'gender' => 'male',
        ]);
        StudentSeasonEnrollment::create([
            'student_id' => $studentA->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
        ]);

        $studentB = Student::create([
            'full_name' => 'Student B',
            'gender' => 'female',
        ]);
        StudentSeasonEnrollment::create([
            'student_id' => $studentB->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classB->id,
        ]);

        // 2. Create class servant assigned to Class A
        $servant = User::create([
            'name' => 'Servant A',
            'phone' => '01444444444',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $servant->assignRole('class_servant');
        $servant->assignedClasses()->attach($this->classA->id);

        $this->actingAs($servant);

        // 3. Query students as servant
        $studentsQuery = StudentResource::getEloquentQuery();
        
        $this->assertEquals(1, $studentsQuery->count());
        $this->assertEquals($studentA->id, $studentsQuery->first()->id);
    }
}
