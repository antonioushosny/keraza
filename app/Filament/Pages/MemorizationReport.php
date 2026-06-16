<?php

namespace App\Filament\Pages;

use App\Models\KerazaClass;
use App\Models\Season;
use App\Models\StudentSeasonEnrollment;
use App\Models\MemorizationItem;
use Filament\Pages\Page;

class MemorizationReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $title = 'تقرير التسميع';
    protected static ?string $navigationLabel = 'تقرير التسميع';
    protected static string $view = 'filament.pages.memorization-report';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

    public ?int $selectedClassId = null;
    public array $classes = [];
    public array $reportData = [];
    public array $items = [];
    public ?string $seasonName = null;

    public function mount(): void
    {
        $user = auth()->user();
        
        if ($user->hasRole('super_admin')) {
            $this->classes = KerazaClass::orderBy('level')->get()->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        } else {
            $this->classes = $user->assignedClasses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        }

        if (count($this->classes) === 1) {
            $this->selectedClassId = $this->classes[0]['id'];
            $this->loadReport();
        }

        $activeSeason = Season::active();
        $this->seasonName = $activeSeason?->name;
    }

    public function updatedSelectedClassId(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        if (!$this->selectedClassId) {
            $this->reportData = [];
            $this->items = [];
            return;
        }

        $activeSeason = Season::active();
        if (!$activeSeason) {
            $this->reportData = [];
            $this->items = [];
            return;
        }

        // Fetch memorization items for the selected class and active season
        $memorizationItems = MemorizationItem::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->orderBy('id')
            ->get();

        $this->items = $memorizationItems->map(fn($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'max_points' => $item->max_points,
        ])->toArray();

        // Fetch student enrollments with their scores
        $enrollments = StudentSeasonEnrollment::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->with(['student', 'memorizationScores'])
            ->get();

        $report = [];
        $totalMaxPoints = $memorizationItems->sum('max_points');

        foreach ($enrollments as $enrollment) {
            $studentScores = [];
            $totalScore = 0;
            $hasScores = false;

            foreach ($memorizationItems as $item) {
                $scoreObj = $enrollment->memorizationScores->firstWhere('memorization_item_id', $item->id);
                if ($scoreObj) {
                    $hasScores = true;
                    $studentScores[$item->id] = [
                        'score' => $scoreObj->score,
                        'accuracy' => $scoreObj->accuracy,
                        'percentage' => $item->max_points > 0 ? round(($scoreObj->score / $item->max_points) * 100, 1) : 0,
                    ];
                    $totalScore += $scoreObj->score;
                } else {
                    $studentScores[$item->id] = null;
                }
            }

            // Calculate overall percentage
            $overallPercentage = $totalMaxPoints > 0 ? round(($totalScore / $totalMaxPoints) * 100, 1) : 0;

            $report[] = [
                'enrollment_id' => $enrollment->id,
                'student_name' => $enrollment->student->full_name,
                'profile_image' => $enrollment->student->profile_image,
                'scores' => $studentScores,
                'total_score' => $totalScore,
                'total_percentage' => $overallPercentage,
                'has_scores' => $hasScores,
            ];
        }

        // Sort by total score descending, then by name
        usort($report, function ($a, $b) {
            if ($b['total_score'] === $a['total_score']) {
                return strcmp($a['student_name'], $b['student_name']);
            }
            return $b['total_score'] <=> $a['total_score'];
        });

        $this->reportData = $report;
    }

    public function export()
    {
        if (!$this->selectedClassId || empty($this->reportData)) {
            return;
        }

        $class = KerazaClass::find($this->selectedClassId);
        $className = $class ? $class->name : 'فصل';
        $filename = "تقرير_التسميع_{$className}_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Arabic characters in Excel
            fwrite($file, "\xEF\xBB\xBF");

            // Build headers
            $headers = ['الترتيب', 'المخدوم'];
            foreach ($this->items as $item) {
                $headers[] = $item['title'] . " (عظمى: " . $item['max_points'] . ")";
            }
            $headers[] = 'إجمالي الدرجات';
            $headers[] = 'النسبة الكلية';

            fputcsv($file, $headers);

            // Build rows
            foreach ($this->reportData as $index => $row) {
                $rowData = [
                    $index + 1,
                    $row['student_name']
                ];

                foreach ($this->items as $item) {
                    $scoreData = $row['scores'][$item['id']] ?? null;
                    if ($scoreData) {
                        $rowData[] = $scoreData['score'] . " (" . $scoreData['percentage'] . "%)";
                    } else {
                        $rowData[] = '-';
                    }
                }

                $rowData[] = $row['total_score'];
                $rowData[] = $row['total_percentage'] . "%";

                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}

