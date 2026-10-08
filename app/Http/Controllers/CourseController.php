<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\GroupService;

class CourseController extends Controller
{
    public function index(GroupService $groups)
    {
        $query = Course::published()->with('modules.lessons');

        if (auth()->check()) {
            $groups->scopeAccessible($query, auth()->user());
        } else {
            $query->whereNull('group_id');
        }

        $courses = $query->get();

        $completedCourseIds = collect();

        if (auth()->check()) {
            $completedLessonIds = LessonProgress::where('user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->pluck('lesson_id')
                ->toArray();

            $completedCourseIds = $courses->filter(function ($course) use ($completedLessonIds) {
                $lessonIds = $course->modules->flatMap(fn ($m) => $m->lessons->pluck('id'))->toArray();
                return count($lessonIds) > 0 && count(array_diff($lessonIds, $completedLessonIds)) === 0;
            })->pluck('id');
        }

        return view('courses.index', compact('courses', 'completedCourseIds'));
    }

    public function show(Course $course, GroupService $groups)
    {
        if (! $course->published) {
            abort(404);
        }

        abort_unless($groups->canAccessContent(auth()->user(), 'course', $course->id), 403);

        $course->load('modules.lessons');

        return view('courses.show', compact('course'));
    }
}
