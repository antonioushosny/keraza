<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->unique()->constrained('seasons')->onDelete('cascade');
            $table->integer('weight_attendance')->default(20);
            $table->integer('weight_tasks')->default(30);
            $table->integer('weight_evaluation')->default(50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_scoring_rules');
    }
};
