<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add weights to activities table
        Schema::table('activities', function (Blueprint $table) {
            $table->integer('weight_attendance')->default(20)->after('min_score_to_qualify');
            $table->integer('weight_tasks')->default(30)->after('weight_attendance');
            $table->integer('weight_evaluation')->default(50)->after('weight_tasks');
        });

        // 2. Create activity_attendance_sessions table
        Schema::create('activity_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Create activity_attendances table
        Schema::create('activity_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_attendance_session_id')->constrained('activity_attendance_sessions')->onDelete('cascade');
            $table->foreignId('activity_enrollment_id')->constrained('activity_enrollments')->onDelete('cascade');
            $table->string('status')->default('absent'); // present, absent, excused
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['activity_attendance_session_id', 'activity_enrollment_id'], 'act_att_session_enrollment_unique');
        });

        // 4. Create activity_tasks table
        Schema::create('activity_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->string('title');
            $table->integer('max_score')->default(100);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Create activity_task_scores table
        Schema::create('activity_task_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_task_id')->constrained('activity_tasks')->onDelete('cascade');
            $table->foreignId('activity_enrollment_id')->constrained('activity_enrollments')->onDelete('cascade');
            $table->decimal('score', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['activity_task_id', 'activity_enrollment_id'], 'act_task_enrollment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_task_scores');
        Schema::dropIfExists('activity_tasks');
        Schema::dropIfExists('activity_attendances');
        Schema::dropIfExists('activity_attendance_sessions');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['weight_attendance', 'weight_tasks', 'weight_evaluation']);
        });
    }
};
