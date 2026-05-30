<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memorization_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_season_enrollment_id')->constrained('student_season_enrollments')->onDelete('cascade');
            $table->foreignId('memorization_item_id')->constrained('memorization_items')->onDelete('cascade');
            $table->decimal('score', 8, 2);
            $table->integer('accuracy')->default(100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memorization_scores');
    }
};
