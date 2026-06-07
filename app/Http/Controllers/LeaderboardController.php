<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\KerazaClass;
use App\Services\ScoringService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, ScoringService $scoringService)
    {
        $activeSeason = Season::where('is_active', true)->first();

        // Use active season if season_id is not specified in query
        $seasonId = $request->input('season_id', $activeSeason?->id);
        $classId = $request->input('class_id');

        $classes = KerazaClass::all();

        $rankings = collect();
        if ($seasonId) {
            $rankings = $scoringService->getRankingsWithBadges($seasonId, $classId);

            $settings = \App\Models\Setting::getSettings();

            if (!$settings->show_zero_scores) {
                $rankings = $rankings->filter(fn($r) => $r['score'] > 0);
            }

            if ($settings->honor_roll_limit_enabled && $settings->honor_roll_limit > 0) {
                $uniqueScores = $rankings->pluck('score')->unique()->take($settings->honor_roll_limit);
                $rankings = $rankings->whereIn('score', $uniqueScores);
            }

            // Compute dense ranks
            $currentRank = 0;
            $currentScore = null;
            $rankings = $rankings->map(function ($rank) use (&$currentRank, &$currentScore) {
                if ($currentScore === null || $rank['score'] != $currentScore) {
                    $currentRank++;
                    $currentScore = $rank['score'];
                    $isRepeated = false;
                } else {
                    $isRepeated = true;
                }

                $rank['rank_position'] = $currentRank;
                $rank['is_repeated'] = $isRepeated;

                return $rank;
            });
        }

        $currentClass = $classId ? KerazaClass::find($classId) : null;

        return view('leaderboard', [
            'classes' => $classes,
            'rankings' => $rankings,
            'seasonId' => $seasonId,
            'classId' => $classId,
            'currentClass' => $currentClass,
            'seasonName' => $activeSeason?->name ?? 'مهرجان الكرازة',
        ]);
    }
}
