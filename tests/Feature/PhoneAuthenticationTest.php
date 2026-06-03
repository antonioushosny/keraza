<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PhoneAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_create_admin_and_parent_with_same_phone(): void
    {
        // 1. Create a parent user
        $parent = User::create([
            'name' => 'Parent User',
            'phone' => '01288226619',
            'password' => bcrypt('parent-password'),
            'type' => 'parent',
        ]);
        $parent->assignRole('parent');

        // 2. Create an admin user with the SAME phone
        $admin = User::create([
            'name' => 'Admin User',
            'phone' => '01288226619',
            'password' => bcrypt('admin-password'),
            'type' => 'admin',
        ]);
        $admin->assignRole('super_admin');

        $this->assertDatabaseCount('users', 2);
    }

    public function test_login_authenticates_correct_user_based_on_password(): void
    {
        $parent = User::create([
            'name' => 'Parent User',
            'phone' => '01288226619',
            'password' => bcrypt('parent-password'),
            'type' => 'parent',
        ]);
        $parent->assignRole('parent');

        $admin = User::create([
            'name' => 'Admin User',
            'phone' => '01288226619',
            'password' => bcrypt('admin-password'),
            'type' => 'admin',
        ]);
        $admin->assignRole('super_admin');

        // Attempt login as parent
        $loggedInParent = Auth::attempt([
            'phone' => '01288226619',
            'password' => 'parent-password',
        ]);
        $this->assertTrue($loggedInParent);
        $this->assertEquals($parent->id, Auth::id());

        Auth::logout();

        // Attempt login as admin
        $loggedInAdmin = Auth::attempt([
            'phone' => '01288226619',
            'password' => 'admin-password',
        ]);
        $this->assertTrue($loggedInAdmin);
        $this->assertEquals($admin->id, Auth::id());
    }

    public function test_parent_unique_validation_ignores_admin_phone(): void
    {
        // Create an admin with phone
        $admin = User::create([
            'name' => 'Admin User',
            'phone' => '01288226619',
            'password' => bcrypt('admin-password'),
            'type' => 'admin',
        ]);
        $admin->assignRole('super_admin');

        // Create the unique rule as constructed in ParentResource
        $rule = \Illuminate\Validation\Rule::unique('users', 'phone')->where(function ($query) {
            $query->where('type', 'parent');
        });

        // Run validation
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['phone' => '01288226619'],
            ['phone' => [$rule]]
        );

        // Validation should pass because the phone is only taken by an admin, not a parent
        $this->assertFalse($validator->fails());
    }

    public function test_admin_unique_validation_ignores_parent_phone(): void
    {
        // Create a parent with phone
        $parent = User::create([
            'name' => 'Parent User',
            'phone' => '01288226619',
            'password' => bcrypt('parent-password'),
            'type' => 'parent',
        ]);
        $parent->assignRole('parent');

        // Create the unique rule as constructed in UserResource
        $rule = \Illuminate\Validation\Rule::unique('users', 'phone')->where(function ($query) {
            $query->where('type', 'admin');
        });

        // Run validation
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['phone' => '01288226619'],
            ['phone' => [$rule]]
        );

        // Validation should pass because the phone is only taken by a parent, not an admin
        $this->assertFalse($validator->fails());
    }

    public function test_validation_messages_are_translated_to_arabic(): void
    {
        // 1. Create a parent user
        $parent1 = User::create([
            'name' => 'Parent 1',
            'phone' => '01288226619',
            'password' => bcrypt('parent-password'),
            'type' => 'parent',
        ]);
        $parent1->assignRole('parent');

        // Create the unique rule as constructed in ParentResource
        $rule = \Illuminate\Validation\Rule::unique('users', 'phone')->where(function ($query) {
            $query->where('type', 'parent');
        });

        // Run validation for another parent trying to use the same phone
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['phone' => '01288226619'],
            ['phone' => [$rule]],
            [],
            ['phone' => 'رقم الموبايل']
        );

        $this->assertTrue($validator->fails());
        $messages = $validator->errors()->get('phone');
        $this->assertContains('قيمة الحقل رقم الموبايل مُستخدمة من قبل.', $messages);
    }
}
