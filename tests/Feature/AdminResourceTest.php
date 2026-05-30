<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Super Admin']);
    }

    public function test_admin_can_access_student_resource()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Super Admin');

        $this->actingAs($user);

        $response = $this->get('/admin/students');

        $response->assertStatus(200);
        $response->assertSee('الطلاب');
    }

    public function test_admin_can_access_season_resource()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Super Admin');

        $this->actingAs($user);

        $response = $this->get('/admin/seasons');

        $response->assertStatus(200);
        $response->assertSee('المواسم');
    }
}
