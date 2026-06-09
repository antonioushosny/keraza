<?php

namespace App\Filament\Pages;

use App\Models\KerazaClass;
use App\Models\Season;
use App\Models\StudentSeasonEnrollment;
use App\Services\ScoringService;
use Filament\Pages\Page;

class ClassRanking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $title = 'ترتيب الفصل';
    protected static ?string $navigationLabel = 'ترتيب الفصل';
    protected static string $view = 'filament.pages.class-ranking';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

    public ?int $selectedClassId = null;
    public array $classes = [];
    public array $rankings = [];
    public ?string $seasonName = null;

    public function mount(): void
    {
        $user = auth()->user();
        
        if ($user->hasRole('super_admin')) {
            $this->classes = KerazaClass::orderBy('level')->get()->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        } else {
            $this->classes = $user->assignedClasses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        }

        // Auto-select first class if only one
        if (count($this->classes) === 1) {
            $this->selectedClassId = $this->classes[0]['id'];
            $this->loadRankings();
        }

        $activeSeason = Season::active();
        $this->seasonName = $activeSeason?->name;
    }

    public function updatedSelectedClassId(): void
    {
        $this->loadRankings();
    }

    public function loadRankings(): void
    {
        if (!$this->selectedClassId) {
            $this->rankings = [];
            return;
        }

        $activeSeason = Season::active();
        if (!$activeSeason) {
            $this->rankings = [];
            return;
        }

        $scoringService = app(ScoringService::class);
        $rankingsCollection = $scoringService->getRankingsWithBadges($activeSeason->id, $this->selectedClassId);

        // Assign rank positions with ties
        $rankings = [];
        $prevScore = null;
        $prevRank = 0;

        foreach ($rankingsCollection as $index => $r) {
            $currentRank = ($prevScore !== null && $r['score'] == $prevScore) ? $prevRank : $index + 1;
            $isRepeated = ($prevScore !== null && $r['score'] == $prevScore);
            $prevScore = $r['score'];
            $prevRank = $currentRank;

            $rankings[] = array_merge($r, [
                'rank_position' => $currentRank,
                'is_repeated' => $isRepeated,
            ]);
        }

        $this->rankings = $rankings;
    }
}
