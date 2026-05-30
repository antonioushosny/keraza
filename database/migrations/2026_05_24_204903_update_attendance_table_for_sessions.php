<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->foreignId('attendance_session_id')->after('id')->nullable()->constrained('attendance_sessions')->onDelete('cascade');
            $table->dropColumn('date');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->date('date')->after('student_season_enrollment_id')->nullable();
            $table->dropConstrainedForeignId('attendance_session_id');
        });
    }
};
