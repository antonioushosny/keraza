<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create activity_evaluations table
        Schema::create('activity_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->unique()->constrained('activities')->onDelete('cascade');
            $table->integer('max_score')->default(100);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Add columns to activity_scores table
        Schema::table('activity_scores', function (Blueprint $table) {
            $table->foreignId('activity_evaluation_id')->nullable()->constrained('activity_evaluations')->onDelete('cascade');
            $table->decimal('raw_score', 8, 2)->nullable();
        });

        // 3. Migrate existing data
        $existingScores = DB::table('activity_scores')->get();
        foreach ($existingScores as $score) {
            $enrollment = DB::table('activity_enrollments')
                ->where('id', $score->activity_enrollment_id)
                ->first();

            if ($enrollment) {
                $evalId = DB::table('activity_evaluations')
                    ->where('activity_id', $enrollment->activity_id)
                    ->value('id');

                if (!$evalId) {
                    $evalId = DB::table('activity_evaluations')->insertGetId([
                        'activity_id' => $enrollment->activity_id,
                        'max_score' => 100,
                        'date' => $score->created_at ?? now(),
                        'created_at' => $score->created_at ?? now(),
                        'updated_at' => $score->updated_at ?? now(),
                    ]);
                }

                DB::table('activity_scores')
                    ->where('id', $score->id)
                    ->update([
                        'activity_evaluation_id' => $evalId,
                        'raw_score' => $score->score,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('activity_scores', function (Blueprint $table) {
            $table->dropForeign(['activity_evaluation_id']);
            $table->dropColumn(['activity_evaluation_id', 'raw_score']);
        });

        Schema::dropIfExists('activity_evaluations');
    }
};
