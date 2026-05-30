<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_season_enrollments', function (Blueprint $table) {
            $table->index(['student_id', 'season_id', 'class_id'], 'student_enrollment_index');
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->index(['attendance_session_id', 'status'], 'attendance_status_index');
            $table->index('student_season_enrollment_id');
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->index(['season_id', 'class_id', 'date'], 'session_lookup_index');
        });

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->index('student_season_enrollment_id');
            $table->index('exam_id');
        });

        Schema::table('memorization_scores', function (Blueprint $table) {
            $table->index('student_season_enrollment_id');
            $table->index('memorization_item_id');
        });

        Schema::table('activity_enrollments', function (Blueprint $table) {
            $table->index('student_season_enrollment_id');
            $table->index('activity_id');
        });

        Schema::table('behavior_logs', function (Blueprint $table) {
            $table->index('student_season_enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_season_enrollments', function (Blueprint $table) {
            $table->dropIndex('student_enrollment_index');
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropIndex('attendance_status_index');
            $table->dropIndex(['student_season_enrollment_id']);
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropIndex('session_lookup_index');
        });

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropIndex(['student_season_enrollment_id']);
            $table->dropIndex(['exam_id']);
        });

        Schema::table('memorization_scores', function (Blueprint $table) {
            $table->dropIndex(['student_season_enrollment_id']);
            $table->dropIndex(['memorization_item_id']);
        });

        Schema::table('activity_enrollments', function (Blueprint $table) {
            $table->dropIndex(['student_season_enrollment_id']);
            $table->dropIndex(['activity_id']);
        });

        Schema::table('behavior_logs', function (Blueprint $table) {
            $table->dropIndex(['student_season_enrollment_id']);
        });
    }
};
