<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Services\GroupService;
use Livewire\Component;

class CourseViewer extends Component
{
    public Course $course;
    public ?int $currentLessonId = null;
    public bool $isCompleted = false;

    public function mount(Course $course, ?int $initialLessonId = null): void
    {
        $this->course = $course;

        abort_unless($this->canAccessCurrentCourse(), 403);

        if ($initialLessonId && $this->lessonBelongsToCourse($initialLessonId)) {
            $this->currentLessonId = $initialLessonId;
        } else {
            $firstLesson = $course->modules()
                ->orderBy('position')
                ->with(['lessons' => fn ($q) => $q->orderBy('position')])
                ->get()
                ->flatMap(fn ($m) => $m->lessons)
                ->first();

            if ($firstLesson) {
                $this->currentLessonId = $firstLesson->id;
            }
        }

        $this->recordStarted();
        $this->syncCompletionState();
    }

    public function selectLesson(int $lessonId): void
    {
        abort_unless($this->lessonIsAccessible($lessonId), 403);

        $this->currentLessonId = $lessonId;
        $this->recordStarted();
        $this->syncCompletionState();
    }

    public function markComplete(): void
    {
        if (! auth()->check() || ! $this->lessonIsAccessible($this->currentLessonId)) {
            return;
        }

        LessonProgress::updateOrCreate(
            ['user_id' => auth()->id(), 'lesson_id' => $this->currentLessonId],
            ['completed_at' => now()]
        );

        $this->isCompleted = true;
    }

    private function recordStarted(): void
    {
        if (! auth()->check() || ! $this->lessonIsAccessible($this->currentLessonId)) {
            return;
        }

        LessonProgress::firstOrCreate(
            ['user_id' => auth()->id(), 'lesson_id' => $this->currentLessonId],
            ['started_at' => now(), 'completed_at' => null]
        );
    }

    private function syncCompletionState(): void
    {
        $this->isCompleted = $this->currentLessonId && auth()->check()
            ? LessonProgress::where('user_id', auth()->id())
                ->where('lesson_id', $this->currentLessonId)
                ->whereNotNull('completed_at')
                ->exists()
            : false;
    }

    /**
     * A lesson is accessible only if the current user can access this course
     * AND the lesson actually belongs to this course. Guards against tampering
     * with the user-controlled currentLessonId / selectLesson() / ?lesson= input.
     */
    private function lessonIsAccessible(?int $lessonId): bool
    {
        return $lessonId
            && $this->canAccessCurrentCourse()
            && $this->lessonBelongsToCourse($lessonId);
    }

    private function canAccessCurrentCourse(): bool
    {
        $user = auth()->user();

        return $user !== null
            && app(GroupService::class)->canAccessContent($user, 'course', $this->course->id);
    }

    private function lessonBelongsToCourse(int $lessonId): bool
    {
        return Lesson::where('id', $lessonId)
            ->whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))
            ->exists();
    }

    private function resolveNextLesson(?Lesson $currentLesson): ?Lesson
    {
        if (! $currentLesson) {
            return null;
        }

        $currentModule = $currentLesson->module;

        // Next lesson in the same module
        $next = Lesson::where('module_id', $currentModule->id)
            ->where('position', '>', $currentLesson->position)
            ->orderBy('position')
            ->first();

        if ($next) {
            return $next;
        }

        // First lesson of the next module
        $nextModule = Module::where('course_id', $currentModule->course_id)
            ->where('position', '>', $currentModule->position)
            ->orderBy('position')
            ->first();

        if ($nextModule) {
            return Lesson::where('module_id', $nextModule->id)
                ->orderBy('position')
                ->first();
        }

        return null;
    }

    public function render()
    {
        $modules = $this->course->modules()
            ->orderBy('position')
            ->with(['lessons' => fn ($q) => $q->orderBy('position')])
            ->get();

        $allIds = $modules->flatMap(fn ($m) => $m->lessons->pluck('id'))->toArray();

        $completedIds = auth()->check()
            ? LessonProgress::where('user_id', auth()->id())
                ->whereIn('lesson_id', $allIds)
                ->whereNotNull('completed_at')
                ->pluck('lesson_id')
                ->toArray()
            : [];

        $currentLesson = $this->lessonIsAccessible($this->currentLessonId)
            ? Lesson::with('module')->find($this->currentLessonId)
            : null;

        $nextLesson = $this->resolveNextLesson($currentLesson);

        return view('livewire.course-viewer', [
            'modules'       => $modules,
            'currentLesson' => $currentLesson,
            'completedIds'  => $completedIds,
            'nextLesson'    => $nextLesson,
        ]);
    }
}
