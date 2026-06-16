<?php

namespace App\Filament\Pages;

use App\Models\KerazaClass;
use App\Models\Season;
use App\Models\StudentSeasonEnrollment;
use App\Models\Exam;
use Filament\Pages\Page;

class ExamReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $title = 'تقرير الامتحانات';
    protected static ?string $navigationLabel = 'تقرير الامتحانات';
    protected static string $view = 'filament.pages.exams-report';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 4;

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

        // Fetch exams for the selected class and active season
        $exams = Exam::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->orderBy('id')
            ->get();

        $this->items = $exams->map(fn($exam) => [
            'id' => $exam->id,
            'title' => $exam->title,
            'total_score' => $exam->total_score,
        ])->toArray();

        // Fetch student enrollments with their exam scores
        $enrollments = StudentSeasonEnrollment::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->with(['student', 'examScores.exam'])
            ->get();

        $report = [];

        foreach ($enrollments as $enrollment) {
            $studentScores = [];
            $totalScore = 0;
            $totalMaxScore = 0;
            $hasScores = false;
            $scorePercentages = [];

            foreach ($exams as $exam) {
                $scoreObj = $enrollment->examScores->firstWhere('exam_id', $exam->id);
                if ($scoreObj) {
                    $hasScores = true;
                    $pct = $exam->total_score > 0 ? round(($scoreObj->score / $exam->total_score) * 100, 1) : 0;
                    $studentScores[$exam->id] = [
                        'score' => $scoreObj->score,
                        'percentage' => $pct,
                    ];
                    $totalScore += $scoreObj->score;
                    $totalMaxScore += $exam->total_score;
                    $scorePercentages[] = $pct;
                } else {
                    $studentScores[$exam->id] = null;
                }
            }

            // Calculate overall average percentage of the exams the student sat for
            $overallPercentage = count($scorePercentages) > 0 ? round(array_sum($scorePercentages) / count($scorePercentages), 1) : 0;

            $report[] = [
                'enrollment_id' => $enrollment->id,
                'student_name' => $enrollment->student->full_name,
                'profile_image' => $enrollment->student->profile_image,
                'scores' => $studentScores,
                'total_score' => $totalScore,
                'total_max_score' => $totalMaxScore,
                'total_percentage' => $overallPercentage,
                'has_scores' => $totalScore > 0,
            ];
        }

        // Sort by overall average percentage descending, then by name
        usort($report, function ($a, $b) {
            if ($b['total_percentage'] === $a['total_percentage']) {
                return strcmp($a['student_name'], $b['student_name']);
            }
            return $b['total_percentage'] <=> $a['total_percentage'];
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
        $filename = "تقرير_الامتحانات_{$className}_" . now()->format('Y-m-d') . ".csv";

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
                $headers[] = $item['title'] . " (عظمى: " . $item['total_score'] . ")";
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

                $rowData[] = $row['total_score'] . " / " . $row['total_max_score'];
                $rowData[] = $row['total_percentage'] . "%";

                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}

