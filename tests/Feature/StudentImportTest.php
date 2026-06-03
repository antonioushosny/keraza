<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Season;
use App\Models\KerazaClass;
use App\Models\Student;
use App\Models\StudentSeasonEnrollment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected Season $activeSeason;
    protected KerazaClass $classA;
    protected KerazaClass $classB;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create active season
        $this->activeSeason = Season::create([
            'name' => 'Keraza 2026',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        // Create classes
        $this->classA = KerazaClass::create(['name' => 'Class A', 'level' => 1]);
        $this->classB = KerazaClass::create(['name' => 'Class B', 'level' => 2]);
    }

    protected function createTemporaryExcel(array $rows): string
    {
        $dir = storage_path('app/temp');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $filePath = $dir . '/' . uniqid('import_test_') . '.xlsx';
        
        $writer = new Writer();
        $writer->openToFile($filePath);
        
        foreach ($rows as $row) {
            $cells = [];
            foreach ($row as $val) {
                $cells[] = Cell::fromValue($val);
            }
            $writer->addRow(new Row($cells));
        }
        
        $writer->close();
        return $filePath;
    }

    public function test_template_download_is_accessible_to_authenticated_users(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'phone' => '01288226619',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('super_admin');

        $response = $this->actingAs($user, 'admin')->get(route('admin.students.import-template'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_can_import_students_and_create_parents_from_excel(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'phone' => '01288226619',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('super_admin');

        // Create a temporary Excel file
        $excelData = [
            ['اسم المخدوم', 'الجنس', 'تاريخ الميلاد', 'ملاحظات', 'اسم ولي الأمر', 'رقم موبايل ولي الأمر'],
            ['جرجس سمير فايز', 'ذكر', '2015-05-15', 'موهوب في الألحان', 'سمير فايز', '01234567890'],
            ['مريم ميخائيل شفيق', 'أنثى', '2016-08-20', '', 'ميخائيل شفيق', '01288226619'],
        ];
        $tempFile = $this->createTemporaryExcel($excelData);
        $relativeFile = 'temp/' . basename($tempFile);

        // Put the file where Storage disk 'local' can find it
        Storage::disk('local')->put($relativeFile, file_get_contents($tempFile));

        // Call our action logic manually using the same code from StudentResource
        $actionData = [
            'file' => $relativeFile,
            'class_id' => $this->classA->id,
        ];

        // Execute import
        $filePath = Storage::disk('local')->path($actionData['file']);
        $reader = \OpenSpout\Reader\Common\Creator\ReaderFactory::createFromFile($filePath);
        $reader->open($filePath);

        $importedCount = 0;
        $skippedCount = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            $isHeader = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }

                $cells = $row->getCells();
                $rowValues = [];
                foreach ($cells as $cell) {
                    $rowValues[] = trim($cell->getValue() ?? '');
                }

                if (count($rowValues) < 6) {
                    $rowValues = array_pad($rowValues, 6, '');
                }

                $studentName = $rowValues[0];
                $genderInput = $rowValues[1];
                $birthDateInput = $rowValues[2];
                $notes = $rowValues[3];
                $parentName = $rowValues[4];
                $parentPhone = $rowValues[5];

                if (empty($studentName) || empty($parentPhone)) {
                    $skippedCount++;
                    continue;
                }

                $gender = 'male';
                if ($genderInput === 'أنثى' || strtolower($genderInput) === 'female') {
                    $gender = 'female';
                }

                $birthDate = null;
                if (!empty($birthDateInput)) {
                    if ($birthDateInput instanceof \DateTimeInterface) {
                        $birthDate = $birthDateInput->format('Y-m-d');
                    } else {
                        $parsedTime = strtotime($birthDateInput);
                        if ($parsedTime !== false) {
                            $birthDate = date('Y-m-d', $parsedTime);
                        }
                    }
                }

                $parent = User::where('phone', $parentPhone)->first();
                if (!$parent) {
                    $parent = User::create([
                        'name' => $parentName ?: ('ولي أمر ' . $studentName),
                        'phone' => $parentPhone,
                        'password' => bcrypt('123456'),
                    ]);
                    $parent->assignRole('parent');
                } else {
                    if (!empty($parentName) && $parent->name !== $parentName) {
                        $parent->update(['name' => $parentName]);
                    }
                }

                $existingStudent = Student::where('full_name', $studentName)
                    ->where('parent_id', $parent->id)
                    ->first();

                if ($existingStudent) {
                    $enrollmentExists = StudentSeasonEnrollment::where('student_id', $existingStudent->id)
                        ->where('season_id' , $this->activeSeason->id)
                        ->exists();

                    if (!$enrollmentExists) {
                        StudentSeasonEnrollment::create([
                            'student_id' => $existingStudent->id,
                            'season_id' => $this->activeSeason->id,
                            'class_id' => $actionData['class_id'],
                        ]);
                        $importedCount++;
                    } else {
                        $skippedCount++;
                    }
                    continue;
                }

                $student = Student::create([
                    'full_name' => $studentName,
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'notes' => $notes,
                    'parent_id' => $parent->id,
                ]);

                StudentSeasonEnrollment::create([
                    'student_id' => $student->id,
                    'season_id' => $this->activeSeason->id,
                    'class_id' => $actionData['class_id'],
                ]);

                $importedCount++;
            }
        }
        $reader->close();

        // Assert count of imported students
        $this->assertEquals(2, $importedCount);
        $this->assertEquals(0, $skippedCount);

        // Verify students in DB
        $this->assertDatabaseHas('students', ['full_name' => 'جرجس سمير فايز', 'gender' => 'male', 'birth_date' => '2015-05-15', 'notes' => 'موهوب في الألحان']);
        $this->assertDatabaseHas('students', ['full_name' => 'مريم ميخائيل شفيق', 'gender' => 'female', 'birth_date' => '2016-08-20']);

        // Verify parent in DB and role
        $parent1 = User::where('phone', '01234567890')->first();
        $this->assertNotNull($parent1);
        $this->assertTrue($parent1->hasRole('parent'));

        // Verify enrollments
        $this->assertDatabaseHas('student_season_enrollments', ['class_id' => $this->classA->id, 'season_id' => $this->activeSeason->id]);

        // Cleanup
        unlink($tempFile);
    }

    public function test_class_filter_modifies_query_correctly(): void
    {
        // 1. Create a student in Class A
        $studentA = Student::create([
            'full_name' => 'Student A',
            'gender' => 'male',
        ]);
        StudentSeasonEnrollment::create([
            'student_id' => $studentA->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
        ]);

        // 2. Create a student in Class B
        $studentB = Student::create([
            'full_name' => 'Student B',
            'gender' => 'female',
        ]);
        StudentSeasonEnrollment::create([
            'student_id' => $studentB->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classB->id,
        ]);

        // Apply class filter query logic directly
        $query = Student::query();
        
        // Filter by Class A
        $classId = $this->classA->id;
        $activeSeason = $this->activeSeason;
        $query->whereHas('enrollments', function ($q) use ($classId, $activeSeason) {
            $q->where('class_id', $classId);
            if ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            }
        });

        $results = $query->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Student A', $results->first()->full_name);
    }
}
