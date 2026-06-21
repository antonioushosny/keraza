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
    public string $sortField = 'percentage';
    public string $sortDirection = 'desc';

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

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
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
            'category_id' => $exam->category_id,
            'title' => $exam->title,
            'total_score' => $exam->total_score,
        ])->toArray();

        // Fetch student enrollments with their exam scores
        $enrollments = StudentSeasonEnrollment::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->with(['student.parent', 'examScores.exam'])
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
                'birth_date' => $enrollment->student->birth_date,
                'parent_phone' => $enrollment->student->parent?->phone ?? '-',
                'scores' => $studentScores,
                'total_score' => $totalScore,
                'total_max_score' => $totalMaxScore,
                'total_percentage' => $overallPercentage,
                'has_scores' => $totalScore > 0,
            ];
        }

        // Apply sorting
        if (str_starts_with($this->sortField, 'category_')) {
            $categoryId = (int) str_replace('category_', '', $this->sortField);
            // Find exam(s) in this class/season belonging to this category
            $targetExamIds = Exam::where('class_id', $this->selectedClassId)
                ->where('season_id', $activeSeason->id)
                ->where('category_id', $categoryId)
                ->pluck('id')
                ->toArray();

            usort($report, function ($a, $b) use ($targetExamIds) {
                $scoreA = 0;
                $scoreB = 0;
                $hasScoreA = false;
                $hasScoreB = false;
                foreach ($targetExamIds as $eId) {
                    if (isset($a['scores'][$eId])) {
                        $scoreA += $a['scores'][$eId]['score'];
                        $hasScoreA = true;
                    }
                    if (isset($b['scores'][$eId])) {
                        $scoreB += $b['scores'][$eId]['score'];
                        $hasScoreB = true;
                    }
                }
                $valA = $hasScoreA ? $scoreA : -1;
                $valB = $hasScoreB ? $scoreB : -1;

                if ($valA === $valB) {
                    return strcmp($a['student_name'], $b['student_name']);
                }

                return $this->sortDirection === 'asc'
                    ? $valA <=> $valB
                    : $valB <=> $valA;
            });
        } elseif (str_starts_with($this->sortField, 'exam_')) {
            $examId = (int) str_replace('exam_', '', $this->sortField);
            usort($report, function ($a, $b) use ($examId) {
                $scoreA = $a['scores'][$examId]['score'] ?? -1;
                $scoreB = $b['scores'][$examId]['score'] ?? -1;

                if ($scoreA === $scoreB) {
                    return strcmp($a['student_name'], $b['student_name']);
                }

                return $this->sortDirection === 'asc'
                    ? $scoreA <=> $scoreB
                    : $scoreB <=> $scoreA;
            });
        } elseif ($this->sortField === 'name') {
            usort($report, function ($a, $b) {
                return $this->sortDirection === 'asc'
                    ? strcmp($a['student_name'], $b['student_name'])
                    : strcmp($b['student_name'], $a['student_name']);
            });
        } elseif ($this->sortField === 'total_score') {
            usort($report, function ($a, $b) {
                if ($a['total_score'] === $b['total_score']) {
                    return strcmp($a['student_name'], $b['student_name']);
                }
                return $this->sortDirection === 'asc'
                    ? $a['total_score'] <=> $b['total_score']
                    : $b['total_score'] <=> $a['total_score'];
            });
        } else {
            // Default to percentage
            usort($report, function ($a, $b) {
                if ($b['total_percentage'] === $a['total_percentage']) {
                    return strcmp($a['student_name'], $b['student_name']);
                }
                return $this->sortDirection === 'asc'
                    ? $a['total_percentage'] <=> $b['total_percentage']
                    : $b['total_percentage'] <=> $a['total_percentage'];
            });
        }

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
            $headers = [
                'الترتيب',
                'اسم المخدوم كامل',
                'رقم جوال ولي الأمر',
                'تاريخ الميلاد'
            ];
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
                    $row['student_name'],
                    $row['parent_phone'] ?? '-',
                    $row['birth_date'] ?? '-'
                ];

                foreach ($this->items as $item) {
                    $scoreData = $row['scores'][$item['id']] ?? null;
                    if ($scoreData) {
                        $rowData[] = $scoreData['score'];
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

