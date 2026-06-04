<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_filament_user_can_access_profile_page_and_update_details(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'phone' => '01288226619',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $user->assignRole('super_admin');

        // Verify accessing profile page
        $response = $this->actingAs($user, 'admin')->get('/admin/profile');
        $response->assertStatus(200);

        // Test form submission via Livewire
        Livewire::actingAs($user, 'admin')
            ->test(\App\Filament\Pages\Auth\EditProfile::class)
            ->set('data.name', 'Updated Admin')
            ->set('data.phone', '01122334455')
            ->set('data.password', 'newpassword')
            ->set('data.passwordConfirmation', 'newpassword')
            ->call('save');

        $user->refresh();
        $this->assertEquals('Updated Admin', $user->name);
        $this->assertEquals('01122334455', $user->phone);
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_parent_user_can_access_profile_page_and_update_details(): void
    {
        $parent = User::create([
            'name' => 'Parent Name',
            'phone' => '01234567890',
            'password' => bcrypt('password'),
            'type' => 'parent',
        ]);
        $parent->assignRole('parent');

        // Unauthenticated user is redirected
        $this->get(route('parent.profile'))->assertRedirect(route('login'));

        // Authenticated parent can access profile page
        $response = $this->actingAs($parent)->get(route('parent.profile'));
        $response->assertStatus(200);
        $response->assertSee('تحديث بيانات ولي الأمر');

        // Submit form
        $response = $this->actingAs($parent)->post(route('parent.profile'), [
            'name' => 'Updated Parent Name',
            'phone' => '01299998888',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $parent->refresh();

        $this->assertEquals('Updated Parent Name', $parent->name);
        $this->assertEquals('01299998888', $parent->phone);
        $this->assertTrue(Hash::check('newpassword123', $parent->password));
    }

    public function test_parent_profile_unique_phone_validation(): void
    {
        $parent1 = User::create([
            'name' => 'Parent One',
            'phone' => '01234567890',
            'password' => bcrypt('password'),
            'type' => 'parent',
        ]);
        $parent1->assignRole('parent');

        $parent2 = User::create([
            'name' => 'Parent Two',
            'phone' => '01234567891',
            'password' => bcrypt('password'),
            'type' => 'parent',
        ]);
        $parent2->assignRole('parent');

        // Parent 1 tries to change phone to Parent 2's phone
        $response = $this->actingAs($parent1)->post(route('parent.profile'), [
            'name' => 'Parent One',
            'phone' => '01234567891',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('phone');
        $parent1->refresh();
        $this->assertEquals('01234567890', $parent1->phone); // Unchanged

        // Parent 1 keeps their own phone - validation should pass
        $response = $this->actingAs($parent1)->post(route('parent.profile'), [
            'name' => 'Parent One Updated Name',
            'phone' => '01234567890',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $parent1->refresh();
        $this->assertEquals('Parent One Updated Name', $parent1->name);
    }
}
