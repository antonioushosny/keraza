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

    public function test_user_can_login_with_phone_number()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
        ]);

        Livewire::test(Login::class)
            ->set('data.phone', '01000000000')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password()
    {
        $user = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'password' => bcrypt('password'),
        ]);

        Livewire::test(Login::class)
            ->set('data.phone', '01000000000')
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors();

        $this->assertGuest();
    }
}
