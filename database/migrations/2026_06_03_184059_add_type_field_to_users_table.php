<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->default('admin')->after('phone');
        });

        // Update existing users' type based on roles
        if (Schema::hasTable('model_has_roles')) {
            DB::table('users')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->where('model_has_roles.model_type', 'App\Models\User')
                        ->where('roles.name', 'parent');
                })
                ->update(['type' => 'parent']);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['phone', 'type'], 'users_phone_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_type_unique');
            $table->dropColumn('type');
        });
    }
};
