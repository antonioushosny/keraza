<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Pages\Auth\Login;
use Livewire\Livewire;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_login_with_phone_number()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $user->assignRole('super_admin');

        Livewire::test(Login::class)
            ->set('data.phone', '01000000000')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user, 'admin');
    }

    public function test_user_cannot_login_with_wrong_password()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $user->assignRole('super_admin');

        Livewire::test(Login::class)
            ->set('data.phone', '01000000000')
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors();

        $this->assertGuest();
    }
}
