<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_season_enrollment_id')->constrained('student_season_enrollments')->onDelete('cascade');
            $table->enum('type', ['positive', 'negative']);
            $table->integer('points');
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_logs');
    }
};
