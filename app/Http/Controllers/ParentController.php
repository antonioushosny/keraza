<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Season;
use App\Services\ScoringService;

class ParentController extends Controller
{
    public function index(Request $request, ScoringService $scoringService)
    {
        $user = auth()->user();
        
        // Ensure a season is active, fallback to latest
        $season = Season::where('is_active', true)->first() ?? Season::latest()->first();

        $childrenData = [];

        if ($season) {
            // Fetch children belonging to this parent
            $children = Student::where('parent_id', $user->id)
                ->with(['enrollments' => function($query) use ($season) {
                    $query->where('season_id', $season->id)
                          ->with([
                              'class', 
                              'attendance.session', 
                              'examScores.exam', 
                              'memorizationScores.memorizationItem', 
                              'activityEnrollments.activity',
                              'activityEnrollments.scores',
                              'behaviorLogs', 
                              'badges.badge'
                          ]);
                }])
                ->get();

            foreach ($children as $child) {
                $enrollment = $child->enrollments->first();
                if ($enrollment) {
                    // Use scoring service to get rankings/scores for the child's class
                    $rankings = $scoringService->getRankingsWithBadges($season->id, $enrollment->class_id);
                    
                    // Find the child in the rankings
                    $childRanking = $rankings->firstWhere('student_id', $child->id);
                    
                    $childrenData[] = [
                        'student' => $child,
                        'enrollment' => $enrollment,
                        'ranking_info' => $childRanking,
                        'rank_position' => $rankings->search(fn($r) => $r['student_id'] === $child->id) !== false 
                            ? $rankings->search(fn($r) => $r['student_id'] === $child->id) + 1 
                            : null,
                    ];
                } else {
                    $childrenData[] = [
                        'student' => $child,
                        'enrollment' => null,
                        'ranking_info' => null,
                        'rank_position' => null,
                    ];
                }
            }
        }

        return view('parent-dashboard', [
            'season' => $season,
            'childrenData' => $childrenData,
        ]);
    }

    public function uploadImage(Request $request, Student $student)
    {
        // Ensure the student belongs to the authenticated parent
        if ($student->parent_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($student->profile_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($student->profile_image);
            }

            // Store new image in 'students' folder on the 'public' disk
            $path = $request->file('profile_image')->store('students', 'public');
            
            $student->update([
                'profile_image' => $path,
            ]);

            return back()->with('success', 'تم تحديث الصورة الشخصية بنجاح!');
        }

        return back()->with('error', 'حدث خطأ أثناء رفع الصورة.');
    }

    public function showProfile()
    {
        return view('parent-profile', [
            'user' => auth()->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^0[0-9]{10}$/',
                \Illuminate\Validation\Rule::unique('users', 'phone')
                    ->ignore($user->id)
                    ->where('type', 'parent')
            ],
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.required' => 'حقل الاسم مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex' => 'يجب أن يكون رقم الهاتف 11 رقماً ويبدأ بـ 0.',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل لولي أمر آخر.',
            'password.min' => 'يجب أن تكون كلمة المرور 6 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        $referer = request()->headers->get('referer', '');
        $routeName = (request()->is('e3dady') || request()->is('e3dady/*') || str_contains($referer, '/e3dady')) ? 'e3dady.parent.dashboard' : 'parent.dashboard';
        return redirect()->route($routeName)->with('success', 'تم تحديث بيانات الحساب بنجاح!');
    }
}
