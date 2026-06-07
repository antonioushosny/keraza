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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('honor_roll_limit_enabled')->default(true);
            $table->integer('honor_roll_limit')->default(15);
            $table->boolean('show_zero_scores')->default(true);
            $table->boolean('show_attendance_percentage')->default(true);
            $table->timestamps();
        });

        // Seed default settings row
        DB::table('settings')->insert([
            'honor_roll_limit_enabled' => true,
            'honor_roll_limit' => 15,
            'show_zero_scores' => true,
            'show_attendance_percentage' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
