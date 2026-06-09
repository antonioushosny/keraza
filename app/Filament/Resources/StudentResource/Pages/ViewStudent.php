<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Season;
use App\Models\StudentSeasonEnrollment;
use App\Services\ScoringService;
use Filament\Resources\Pages\Page;

class ViewStudent extends Page
{
    protected static string $resource = StudentResource::class;
    protected static string $view = 'filament.resources.student-resource.pages.view-student';
    protected static ?string $title = 'تقرير المخدوم';

    public $record;
    public $student;
    public $enrollment;
    public $scoreData;
    public $rank;
    public $rankingsCount;

    public function mount($record): void
    {
        $this->record = $record;
        $this->student = \App\Models\Student::with('parent')->findOrFail($record);

        $activeSeason = Season::active();
        if (!$activeSeason) {
            $this->enrollment = null;
            $this->scoreData = null;
            $this->rank = null;
            $this->rankingsCount = 0;
            return;
        }

        $this->enrollment = StudentSeasonEnrollment::where('student_id', $this->student->id)
            ->where('season_id', $activeSeason->id)
            ->with([
                'class',
                'attendance.session',
                'examScores.exam',
                'memorizationScores.memorizationItem',
                'activityEnrollments.activity',
                'activityEnrollments.scores',
                'behaviorLogs.creator',
                'badges.badge',
            ])
            ->first();

        if (!$this->enrollment) {
            $this->scoreData = null;
            $this->rank = null;
            $this->rankingsCount = 0;
            return;
        }

        $scoringService = app(ScoringService::class);
        $this->scoreData = $scoringService->calculateScore($this->enrollment);

        // Calculate rank within class
        $rankings = $scoringService->getRankings($activeSeason->id, $this->enrollment->class_id);
        $this->rankingsCount = $rankings->count();
        $this->rank = null;
        foreach ($rankings->values() as $index => $r) {
            if ($r['enrollment_id'] === $this->enrollment->id) {
                $this->rank = $index + 1;
                break;
            }
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            StudentResource::getUrl() => 'المخدومين',
            '#' => $this->student->full_name,
        ];
    }
}
