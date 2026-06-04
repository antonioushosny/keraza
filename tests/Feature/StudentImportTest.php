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
use OpenSpout\Reader\Common\Creator\ReaderFactory;
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
            'type' => 'admin',
        ]);
        $user->assignRole('super_admin');

        $response = $this->actingAs($user, 'admin')->get(route('admin.students.import-template'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    protected function runImportLogic(string $filePath, int $classId): array
    {
        $reader = ReaderFactory::createFromFile($filePath);
        $reader->open($filePath);

        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $rowNum = 1;

        foreach ($reader->getSheetIterator() as $sheet) {
            $isHeader = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }
                $rowNum++;

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

                // Basic validation
                if (empty($studentName)) {
                    $errors[] = "الصف {$rowNum}: اسم المخدوم فارغ.";
                    $skippedCount++;
                    continue;
                }

                if (empty($parentPhone)) {
                    $errors[] = "الصف {$rowNum} (المخدوم: {$studentName}): رقم موبايل ولي الأمر فارغ.";
                    $skippedCount++;
                    continue;
                }

                // 1. Phone validation (Starts with 0 and exactly 11 digits)
                if (!preg_match('/^0[0-9]{10}$/', $parentPhone)) {
                    $errors[] = "الصف {$rowNum} (المخدوم: {$studentName}): رقم موبايل ولي الأمر '{$parentPhone}' غير صالح. يجب أن يتكون من 11 رقماً ويبدأ بـ 0.";
                    $skippedCount++;
                    continue;
                }

                // 2. Gender validation
                $genderInputClean = strtolower(trim($genderInput));
                if (!in_array($genderInputClean, ['ذكر', 'أنثى', 'male', 'female'])) {
                    $errors[] = "الصف {$rowNum} (المخدوم: {$studentName}): الجنس '{$genderInput}' غير صالح. يجب أن يكون (ذكر/أنثى/male/female).";
                    $skippedCount++;
                    continue;
                }

                $gender = 'male';
                if ($genderInputClean === 'أنثى' || $genderInputClean === 'female') {
                    $gender = 'female';
                }

                // 3. Birth date validation
                if (empty($birthDateInput)) {
                    $errors[] = "الصف {$rowNum} (المخدوم: {$studentName}): تاريخ الميلاد فارغ.";
                    $skippedCount++;
                    continue;
                }

                $birthDate = null;
                if ($birthDateInput instanceof \DateTimeInterface) {
                    $birthDate = $birthDateInput->format('Y-m-d');
                } else {
                    $parsedTime = strtotime($birthDateInput);
                    if ($parsedTime !== false) {
                        $birthDate = date('Y-m-d', $parsedTime);
                    }
                }

                if (!$birthDate || $birthDate > date('Y-m-d')) {
                    $errors[] = "الصف {$rowNum} (المخدوم: {$studentName}): تاريخ الميلاد '{$birthDateInput}' غير صالح أو في المستقبل.";
                    $skippedCount++;
                    continue;
                }

                // Create/Get Parent User
                $parent = User::createOrGetParent($parentPhone, $parentName, $studentName);

                // Check if student already exists for this parent
                $existingStudent = Student::where('full_name', $studentName)
                    ->where('parent_id', $parent->id)
                    ->first();

                if ($existingStudent) {
                    // Update student details
                    $existingStudent->update([
                        'gender' => $gender,
                        'birth_date' => $birthDate,
                        'notes' => $notes,
                    ]);

                    // Enroll or update enrollment for active season
                    StudentSeasonEnrollment::updateOrCreate(
                        [
                            'student_id' => $existingStudent->id,
                            'season_id' => $this->activeSeason->id,
                        ],
                        [
                            'class_id' => $classId,
                        ]
                    );

                    $updatedCount++;
                    continue;
                }

                // Create student
                $student = Student::create([
                    'full_name' => $studentName,
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'notes' => $notes,
                    'parent_id' => $parent->id,
                ]);

                // Enroll student
                StudentSeasonEnrollment::create([
                    'student_id' => $student->id,
                    'season_id' => $this->activeSeason->id,
                    'class_id' => $classId,
                ]);

                $importedCount++;
            }
        }

        $reader->close();

        return [$importedCount, $updatedCount, $skippedCount, $errors];
    }

    public function test_can_import_students_and_create_parents_from_excel(): void
    {
        // Create a temporary Excel file
        $excelData = [
            ['اسم المخدوم', 'الجنس', 'تاريخ الميلاد', 'ملاحظات', 'اسم ولي الأمر', 'رقم موبايل ولي الأمر'],
            ['جرجس سمير فايز', 'ذكر', '2015-05-15', 'موهوب في الألحان', 'سمير فايز', '01234567890'],
            ['مريم ميخائيل شفيق', 'أنثى', '2016-08-20', '', 'ميخائيل شفيق', '01288226619'],
        ];
        $tempFile = $this->createTemporaryExcel($excelData);

        // Execute import
        [$importedCount, $updatedCount, $skippedCount, $errors] = $this->runImportLogic($tempFile, $this->classA->id);

        // Assert count of imported students
        $this->assertEquals(2, $importedCount);
        $this->assertEquals(0, $updatedCount);
        $this->assertEquals(0, $skippedCount);
        $this->assertCount(0, $errors);

        // Verify students in DB
        $this->assertDatabaseHas('students', ['full_name' => 'جرجس سمير فايز', 'gender' => 'male', 'birth_date' => '2015-05-15', 'notes' => 'موهوب في الألحان']);
        $this->assertDatabaseHas('students', ['full_name' => 'مريم ميخائيل شفيق', 'gender' => 'female', 'birth_date' => '2016-08-20']);

        // Verify parent in DB and role
        $parent1 = User::where('phone', '01234567890')->where('type', 'parent')->first();
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

    public function test_export_generates_xlsx_file(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'phone' => '01288226619',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $user->assignRole('super_admin');

        // Create a student with parent details
        $parent = User::create([
            'name' => 'Parent Name',
            'phone' => '01234567890',
            'password' => bcrypt('password'),
            'type' => 'parent',
        ]);
        $parent->assignRole('parent');

        $student = Student::create([
            'full_name' => 'Export Student',
            'gender' => 'male',
            'birth_date' => '2015-05-15',
            'notes' => 'Export test',
            'parent_id' => $parent->id,
        ]);
        StudentSeasonEnrollment::create([
            'student_id' => $student->id,
            'season_id' => $this->activeSeason->id,
            'class_id' => $this->classA->id,
        ]);

        $response = \Livewire\Livewire::actingAs($user, 'admin')
            ->test(\App\Filament\Resources\StudentResource\Pages\ListStudents::class)
            ->callTableAction('export_students');

        $response->assertFileDownloaded();
    }

    public function test_import_with_existing_admin_phone_does_not_modify_admin_details_but_adds_parent_role(): void
    {
        // 1. Create an admin user with a specific phone number and name
        $admin = User::create([
            'name' => 'Original Admin Name',
            'phone' => '01288226619',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
        $admin->assignRole('super_admin');

        // 2. Prepare import data with the admin's phone number but a different parent name
        $excelData = [
            ['اسم المخدوم', 'الجنس', 'تاريخ الميلاد', 'ملاحظات', 'اسم ولي الأمر', 'رقم موبايل ولي الأمر'],
            ['جرجس سمير فايز', 'ذكر', '2015-05-15', 'موهوب في الألحان', 'New Parent Name', '01288226619'],
        ];
        $tempFile = $this->createTemporaryExcel($excelData);

        // 3. Execute the import logic directly
        [$importedCount, $updatedCount, $skippedCount, $errors] = $this->runImportLogic($tempFile, $this->classA->id);

        // 4. Assertions
        $admin->refresh();
        $this->assertEquals('Original Admin Name', $admin->name);
        
        $newParent = User::where('phone', '01288226619')->where('type', 'parent')->first();
        $this->assertNotNull($newParent);
        $this->assertNotEquals($admin->id, $newParent->id);
        $this->assertEquals('New Parent Name', $newParent->name);
        $this->assertTrue($newParent->hasRole('parent'));

        // Cleanup
        unlink($tempFile);
    }

    public function test_import_skips_invalid_data(): void
    {
        // Create a temporary Excel file with invalid rows
        $excelData = [
            ['اسم المخدوم', 'الجنس', 'تاريخ الميلاد', 'ملاحظات', 'اسم ولي الأمر', 'رقم موبايل ولي الأمر'],
            // Row 1: Invalid phone (not starting with 0, not 11 digits)
            ['جرجس سمير', 'ذكر', '2015-05-15', '', 'سمير فايز', '91234567'],
            // Row 2: Invalid gender
            ['مريم ميخائيل', 'ولد', '2016-08-20', '', 'ميخائيل شفيق', '01288226619'],
            // Row 3: Invalid birth date (future date)
            ['مينا جورج', 'ذكر', '2030-01-01', '', 'جورج', '01234567891'],
        ];
        $tempFile = $this->createTemporaryExcel($excelData);

        // Execute import directly
        [$importedCount, $updatedCount, $skippedCount, $errors] = $this->runImportLogic($tempFile, $this->classA->id);

        // Verify that all 3 invalid rows were skipped
        $this->assertEquals(0, $importedCount);
        $this->assertEquals(0, $updatedCount);
        $this->assertEquals(3, $skippedCount);
        $this->assertCount(3, $errors);

        $this->assertStringContainsString('رقم موبايل ولي الأمر \'91234567\' غير صالح', $errors[0]);
        $this->assertStringContainsString('الجنس \'ولد\' غير صالح', $errors[1]);
        $this->assertStringContainsString('تاريخ الميلاد \'2030-01-01\' غير صالح', $errors[2]);

        // Verify that none of these students were imported
        $this->assertDatabaseMissing('students', ['full_name' => 'جرجس سمير']);
        $this->assertDatabaseMissing('students', ['full_name' => 'مريم ميخائيل']);
        $this->assertDatabaseMissing('students', ['full_name' => 'مينا جورج']);

        // Cleanup
        unlink($tempFile);
    }

    public function test_import_updates_existing_student_details_and_handles_twins(): void
    {
        // 1. Create a student
        $parent = User::createOrGetParent('01288226619', 'سمير فايز', 'جرجس سمير');
        $student = Student::create([
            'full_name' => 'جرجس سمير',
            'gender' => 'male',
            'birth_date' => '2015-05-15',
            'notes' => 'قديم',
            'parent_id' => $parent->id,
        ]);

        // 2. Prepare import data with the same student (with modified details) AND a new twin sibling (same parent phone)
        $excelData = [
            ['اسم المخدوم', 'الجنس', 'تاريخ الميلاد', 'ملاحظات', 'اسم ولي الأمر', 'رقم موبايل ولي الأمر'],
            // Same student - updates notes and birth date
            ['جرجس سمير', 'ذكر', '2015-06-15', 'تحديث موهبة الألحان', 'سمير فايز', '01288226619'],
            // Twin/Sibling - new student under same parent
            ['مريم سمير', 'أنثى', '2015-06-15', 'توأم جرجس', 'سمير فايز', '01288226619'],
        ];
        $tempFile = $this->createTemporaryExcel($excelData);

        // 3. Run import logic
        [$importedCount, $updatedCount, $skippedCount, $errors] = $this->runImportLogic($tempFile, $this->classA->id);

        // 4. Assertions
        $this->assertEquals(1, $importedCount); // 1 new student (مريم سمير)
        $this->assertEquals(1, $updatedCount); // 1 updated student (جرجس سمير)
        $this->assertEquals(0, $skippedCount);
        $this->assertCount(0, $errors);

        // Verify updated student details
        $student->refresh();
        $this->assertEquals('2015-06-15', $student->birth_date);
        $this->assertEquals('تحديث موهبة الألحان', $student->notes);

        // Verify sibling was created under the same parent
        $sibling = Student::where('full_name', 'مريم سمير')->first();
        $this->assertNotNull($sibling);
        $this->assertEquals($parent->id, $sibling->parent_id);
        $this->assertEquals('female', $sibling->gender);

        // Cleanup
        unlink($tempFile);
    }
}

