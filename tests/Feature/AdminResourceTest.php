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
        Role::create(['name' => 'super_admin']);
        \App\Models\Season::create([
            'name' => 'Keraza 2026',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_student_resource()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $user->assignRole('super_admin');

        $this->actingAs($user, 'admin');

        $response = $this->get('/admin/students');

        $response->assertStatus(200);
        $response->assertSee('المخدومين');
    }

    public function test_admin_can_access_season_resource()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $user->assignRole('super_admin');

        $this->actingAs($user, 'admin');

        $response = $this->get('/admin/seasons');

        $response->assertStatus(200);
        $response->assertSee('المواسم');
    }
}
