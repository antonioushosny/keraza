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
