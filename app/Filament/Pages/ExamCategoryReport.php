<?php

namespace App\Filament\Pages;

use App\Models\KerazaClass;
use App\Models\Season;
use App\Models\StudentSeasonEnrollment;
use App\Models\Exam;
use App\Models\ExamCategory;
use Filament\Pages\Page;

class ExamCategoryReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $title = 'تقرير تصنيفات الامتحانات';
    protected static ?string $navigationLabel = 'تقرير تصنيفات الامتحانات';
    protected static string $view = 'filament.pages.exam-category-report';
    protected static ?string $navigationGroup = 'التقارير ولوحات الشرف';
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'class_admin', 'class_servant']) ?? false;
    }

    public ?int $selectedClassId = null;
    public array $classes = [];
    public array $categories = [];
    public array $reportData = [];
    public ?string $seasonName = null;
    public string $sortField = 'overall';
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
            $this->categories = [];
            return;
        }

        $activeSeason = Season::active();
        if (!$activeSeason) {
            $this->reportData = [];
            $this->categories = [];
            return;
        }

        // Fetch all exams for this class and active season
        $exams = Exam::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->with('category')
            ->get();

        // Get unique categories that actually have exams in this class and season
        $this->categories = $exams->pluck('category')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ])
            ->toArray();

        // Fetch student enrollments with their exam scores
        $enrollments = StudentSeasonEnrollment::where('class_id', $this->selectedClassId)
            ->where('season_id', $activeSeason->id)
            ->with(['student.parent', 'examScores.exam'])
            ->get();

        $report = [];

        foreach ($enrollments as $enrollment) {
            $categoryPercentages = [];
            $allExamsPercentages = [];
            $hasScores = false;

            foreach ($this->categories as $category) {
                $categoryExams = $exams->where('category_id', $category['id']);
                $categoryScores = [];

                foreach ($categoryExams as $exam) {
                    $scoreObj = $enrollment->examScores->firstWhere('exam_id', $exam->id);
                    if ($scoreObj) {
                        $hasScores = true;
                        $pct = $exam->total_score > 0 ? ($scoreObj->score / $exam->total_score) * 100 : 0;
                        $categoryScores[] = $pct;
                        $allExamsPercentages[] = $pct;
                    }
                }

                $categoryPercentages[$category['id']] = count($categoryScores) > 0 
                    ? round(array_sum($categoryScores) / count($categoryScores), 1) 
                    : null;
            }

            // Calculate overall percentage of exams student sat for
            $overallPercentage = count($allExamsPercentages) > 0 
                ? round(array_sum($allExamsPercentages) / count($allExamsPercentages), 1) 
                : 0;

            $report[] = [
                'enrollment_id' => $enrollment->id,
                'student_name' => $enrollment->student->full_name,
                'gender' => $enrollment->student->gender,
                'profile_image' => $enrollment->student->profile_image,
                'birth_date' => $enrollment->student->birth_date,
                'parent_phone' => $enrollment->student->parent?->phone ?? '-',
                'category_percentages' => $categoryPercentages,
                'overall_percentage' => $overallPercentage,
                'has_scores' => $hasScores,
            ];
        }

        // Apply sorting
        if (str_starts_with($this->sortField, 'category_')) {
            $categoryId = (int) str_replace('category_', '', $this->sortField);
            usort($report, function ($a, $b) use ($categoryId) {
                $valA = $a['category_percentages'][$categoryId] ?? -1;
                $valB = $b['category_percentages'][$categoryId] ?? -1;

                if ($valA === $valB) {
                    return strcmp($a['student_name'], $b['student_name']);
                }

                return $this->sortDirection === 'asc'
                    ? $valA <=> $valB
                    : $valB <=> $valA;
            });
        } elseif ($this->sortField === 'name') {
            usort($report, function ($a, $b) {
                return $this->sortDirection === 'asc'
                    ? strcmp($a['student_name'], $b['student_name'])
                    : strcmp($b['student_name'], $a['student_name']);
            });
        } else {
            // Sort by overall percentage
            usort($report, function ($a, $b) {
                $valA = $a['overall_percentage'];
                $valB = $b['overall_percentage'];

                if ($valA === $valB) {
                    return strcmp($a['student_name'], $b['student_name']);
                }

                return $this->sortDirection === 'asc'
                    ? $valA <=> $valB
                    : $valB <=> $valA;
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
        $filename = "تقرير_تصنيفات_الامتحانات_{$className}_" . now()->format('Y-m-d') . ".csv";

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
                'اسم المخدوم',
                'الفصل',
                'تاريخ الميلاد',
                'رقم موبايل ولي الامر',
                'النوع'
            ];

            foreach ($this->categories as $category) {
                $headers[] = 'نسبة ' . $category['name'];
            }
            $headers[] = 'النسبة الإجمالية';

            fputcsv($file, $headers);

            $class = KerazaClass::find($this->selectedClassId);
            $className = $class ? $class->name : '-';

            // Build rows
            foreach ($this->reportData as $index => $row) {
                $rowData = [
                    $index + 1,
                    $row['student_name'],
                    $className,
                    $row['birth_date'] ?? '-',
                    $row['parent_phone'] ?? '-',
                    $row['gender'] === 'male' ? 'ذكر' : 'أنثى'
                ];

                foreach ($this->categories as $category) {
                    $pct = $row['category_percentages'][$category['id']] ?? null;
                    $rowData[] = $pct !== null ? $pct . '%' : '-';
                }

                $rowData[] = $row['overall_percentage'] . '%';

                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
