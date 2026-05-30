<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_enrollment_id')->constrained('activity_enrollments')->onDelete('cascade');
            $table->decimal('score', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_scores');
    }
};
