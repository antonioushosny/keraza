<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Season;
use App\Models\KerazaClass;
use App\Models\Student;
use App\Models\StudentSeasonEnrollment;
use App\Models\Badge;
use App\Models\ExamCategory;
use App\Models\Exam;
use App\Models\ExamScore;
use App\Models\ActivityType;
use App\Models\Activity;
use App\Models\ActivityEnrollment;
use App\Models\ActivityScore;
use App\Models\Attendance;
use App\Models\BehaviorLog;
use App\Models\ScoringRule;
use App\Models\MemorizationItem;
use App\Models\MemorizationScore;
use App\Models\StudentBadge;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }
        
        // Clear existing data
        $tables = [
            'student_badges', 'badges', 'behavior_logs', 'activity_scores', 
            'activity_enrollments', 'activities', 'activity_types', 
            'memorization_scores', 'memorization_items', 'exam_scores', 
            'exams', 'exam_categories', 'attendance', 
            'attendance_sessions', 'student_season_enrollments', 'students', 'classes', 'seasons',
            'users', 'class_servant', 'activity_supervisor'
        ];
        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
            } catch (\Exception $e) {
                // Table might not exist yet
            }
        }
        
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Admin User
        $user = User::create([
            'phone' => '01000000000',
            'name' => 'المدير العام',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('super_admin');

        // Clear permissions cache after assigning roles to ensure the current process sees them
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Parent User
        $parent = User::create([
            'phone' => '01222222222',
            'name' => 'ولي أمر تجريبي',
            'password' => bcrypt('password'),
        ]);
        $parent->assignRole('parent');

        // Seasons
        $seasons = [
            Season::create(['name' => 'مهرجان الكرازة 2025', 'is_active' => false, 'start_date' => '2025-01-01']),
            Season::create(['name' => 'مهرجان الكرازة 2026', 'is_active' => true, 'start_date' => '2026-01-01']),
        ];

        // Categories & Types
        $examCats = [
            ExamCategory::create(['name' => 'دراسات كتابية']),
            ExamCategory::create(['name' => 'طقس كنسي']),
            ExamCategory::create(['name' => 'عقيدة وتاريخ']),
        ];

        $actTypes = [
            ActivityType::create(['name' => 'رسم وتلوين']),
            ActivityType::create(['name' => 'أشغال يدوية']),
            ActivityType::create(['name' => 'رياضة']),
            ActivityType::create(['name' => 'موسيقى وألحان']),
        ];

        $badges = [
            Badge::create(['title' => 'المواظب المثالي', 'icon' => '📅', 'description' => 'حضور كامل']),
            Badge::create(['title' => 'بطل الألحان', 'icon' => '🎶', 'description' => 'تميز في التسميع']),
            Badge::create(['title' => 'نجم الرياضة', 'icon' => '⚽', 'description' => 'أداء متميز']),
            Badge::create(['title' => 'القلب النقي', 'icon' => '❤️', 'description' => 'سلوك متميز']),
        ];

        $classes = [
            1 => KerazaClass::create(['name' => 'أولى ابتدائي', 'level' => 1]),
            2 => KerazaClass::create(['name' => 'ثانية ابتدائي', 'level' => 2]),
            3 => KerazaClass::create(['name' => 'ثالثة ابتدائي', 'level' => 3]),
            4 => KerazaClass::create(['name' => 'رابعة ابتدائي', 'level' => 4]),
            5 => KerazaClass::create(['name' => 'خامسة ابتدائي', 'level' => 5]),
            6 => KerazaClass::create(['name' => 'سادسة ابتدائي', 'level' => 6]),
        ];

        // Arabic Names Pool
        $names = [
            'مينا', 'كيرلس', 'بيشوي', 'جورج', 'انطونيوس', 'مارك', 'ابانوب', 'يوحنا', 'توماس', 'ستيفن',
            'مارينا', 'دميانة', 'فيرونيا', 'جوستينا', 'ميرنا', 'كيريت', 'ايرينى', 'مريم', 'سارة', 'رفقة'
        ];
        $surnames = ['جرجس', 'مخلص', 'صبحي', 'فوزي', 'منير', 'عادل', 'سامح', 'نصيف', 'رأفت', 'كمال'];

        foreach ($seasons as $season) {
            // Scoring Rule for each season
            ScoringRule::create([
                'season_id' => $season->id,
                'weight_attendance' => 20,
                'weight_exams' => 30,
                'weight_memorization' => 20,
                'weight_activities' => 20,
                'weight_behavior' => 10,
            ]);

            // Classes used for this season in seeding
            $seasonClasses = [$classes[1], $classes[2], $classes[3]];

            foreach ($seasonClasses as $class) {
                // Attendance Sessions (10 weeks)
                $sessions = [];
                for ($w = 0; $w < 10; $w++) {
                    $sessions[] = \App\Models\AttendanceSession::create([
                        'season_id' => $season->id,
                        'class_id' => $class->id,
                        'date' => now()->subWeeks($w)->format('Y-m-d'),
                        'notes' => 'حضور الأسبوع ' . (10 - $w),
                    ]);
                }

                // Exams for this class
                $exams = [];
                foreach ($examCats as $cat) {
                    $exams[] = Exam::create([
                        'season_id' => $season->id,
                        'class_id' => $class->id,
                        'category_id' => $cat->id,
                        'title' => 'اختبار ' . $cat->name,
                        'total_score' => 100,
                        'date' => now()->subDays(rand(1, 30)),
                    ]);
                }

                // Memorization Items
                $memoItems = [
                    MemorizationItem::create(['season_id' => $season->id, 'class_id' => $class->id, 'title' => 'المزمور الأول', 'max_points' => 100]),
                    MemorizationItem::create(['season_id' => $season->id, 'class_id' => $class->id, 'title' => 'قانون الإيمان', 'max_points' => 100]),
                ];

                // Activities
                $activities = [];
                foreach ($actTypes as $type) {
                    $activities[] = Activity::create([
                        'season_id' => $season->id,
                        'type_id' => $type->id,
                        'title' => 'مسابقة ' . $type->name,
                        'min_score_to_qualify' => 60,
                    ]);
                }

                // Students for this class
                for ($i = 0; $i < 20; $i++) {
                    $firstName = $names[array_rand($names)];
                    $lastName = $surnames[array_rand($surnames)];
                    $student = Student::create([
                        'parent_id' => ($i < 2 && $class->id == 1) ? $parent->id : null,
                        'full_name' => $firstName . ' ' . $lastName,
                        'gender' => array_rand(['male', 'female']) == 0 ? 'male' : 'female',
                        'birth_date' => now()->subYears(rand(6, 12))->format('Y-m-d'),
                    ]);

                    $enrollment = StudentSeasonEnrollment::create([
                        'student_id' => $student->id,
                        'season_id' => $season->id,
                        'class_id' => $class->id,
                    ]);

                    // Attendance for each session
                    foreach ($sessions as $session) {
                        $status = rand(1, 10) > 2 ? 'present' : 'absent';
                        Attendance::create([
                            'attendance_session_id' => $session->id,
                            'student_season_enrollment_id' => $enrollment->id,
                            'status' => $status,
                        ]);
                    }

                    // Exam Scores
                    foreach ($exams as $exam) {
                        ExamScore::create([
                            'student_season_enrollment_id' => $enrollment->id,
                            'exam_id' => $exam->id,
                            'score' => rand(70, 100),
                        ]);
                    }

                    // Memorization
                    foreach ($memoItems as $item) {
                        MemorizationScore::create([
                            'student_season_enrollment_id' => $enrollment->id,
                            'memorization_item_id' => $item->id,
                            'score' => rand(80, 100),
                            'accuracy' => rand(90, 100),
                        ]);
                    }

                    // Activity Enrollments (random 1 activity)
                    $act = $activities[array_rand($activities)];
                    $actEnroll = ActivityEnrollment::create([
                        'student_season_enrollment_id' => $enrollment->id,
                        'activity_id' => $act->id,
                        'status' => 'qualified',
                    ]);
                    ActivityScore::create([
                        'activity_enrollment_id' => $actEnroll->id,
                        'score' => rand(75, 100),
                    ]);

                    // Behavior
                    if (rand(1, 10) > 7) {
                        BehaviorLog::create([
                            'student_season_enrollment_id' => $enrollment->id,
                            'type' => 'positive',
                            'points' => 5,
                            'reason' => 'مساعدة الزملاء',
                            'created_by' => $user->id,
                        ]);
                    }

                    // Badges
                    if (rand(1, 10) > 8) {
                        StudentBadge::create([
                            'student_season_enrollment_id' => $enrollment->id,
                            'badge_id' => $badges[array_rand($badges)]->id,
                            'awarded_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
