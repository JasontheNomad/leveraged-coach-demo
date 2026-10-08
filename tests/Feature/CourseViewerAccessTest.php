<?php

namespace Tests\Feature;

use App\Livewire\CourseViewer;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Guards the lesson IDOR fix in App\Livewire\CourseViewer: currentLessonId
 * (set via ?lesson=, selectLesson(), or a tampered public prop) must never
 * escape the mounted course or the course's group gating.
 */
class CourseViewerAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Build a published course with one module and $lessonCount lessons. */
    private function makeCourse(string $slug, int $lessonCount): array
    {
        $course = Course::create([
            'title'     => $slug,
            'slug'      => $slug,
            'published' => true,
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title'     => 'Module 1',
            'position'  => 1,
        ]);

        $lessons = [];
        for ($i = 1; $i <= $lessonCount; $i++) {
            $lessons[] = Lesson::create([
                'module_id' => $module->id,
                'title'     => "Lesson {$i}",
                'position'  => $i,
            ]);
        }

        return [$course, $lessons];
    }

    /**
     * Non-admin user with no groups; public course A (>=2 lessons) the user can
     * see; gated course C locked to a group the user does NOT belong to.
     */
    private function fixtures(): array
    {
        $user = User::factory()->create(['role' => 'member']);

        [$courseA, $lessonsA] = $this->makeCourse('persuasion-engineering', 3);
        [$courseC, $lessonsC] = $this->makeCourse('youtube-profit-formula', 2);

        $group = Group::create(['name' => 'Premium', 'slug' => 'premium']);
        app(GroupService::class)->syncContentGroups('course', $courseC->id, [$group->id]);

        return [$user, $courseA, $lessonsA, $courseC, $lessonsC];
    }

    public function test_mount_without_lesson_param_selects_first_lesson(): void
    {
        [$user, $courseA, $lessonsA] = $this->fixtures();

        $this->actingAs($user);

        Livewire::test(CourseViewer::class, ['course' => $courseA])
            ->assertSet('currentLessonId', $lessonsA[0]->id);
    }

    public function test_mount_with_foreign_gated_lesson_falls_back_to_first_lesson(): void
    {
        [$user, $courseA, $lessonsA, , $lessonsC] = $this->fixtures();

        $this->actingAs($user);

        $component = Livewire::test(CourseViewer::class, [
            'course'          => $courseA,
            'initialLessonId' => $lessonsC[0]->id, // foreign + gated
        ]);

        $component->assertSet('currentLessonId', $lessonsA[0]->id);
        $this->assertNotSame($lessonsC[0]->id, $component->get('currentLessonId'));
    }

    public function test_select_lesson_within_course_is_allowed(): void
    {
        [$user, $courseA, $lessonsA] = $this->fixtures();

        $this->actingAs($user);

        Livewire::test(CourseViewer::class, ['course' => $courseA])
            ->call('selectLesson', $lessonsA[1]->id)
            ->assertSet('currentLessonId', $lessonsA[1]->id);
    }

    public function test_select_foreign_lesson_aborts_403(): void
    {
        [$user, $courseA, , , $lessonsC] = $this->fixtures();

        $this->actingAs($user);

        Livewire::test(CourseViewer::class, ['course' => $courseA])
            ->call('selectLesson', $lessonsC[0]->id)
            ->assertStatus(403);
    }

    public function test_mount_gated_course_as_non_member_aborts_403(): void
    {
        [$user, , , $courseC] = $this->fixtures();

        $this->actingAs($user);

        Livewire::test(CourseViewer::class, ['course' => $courseC])
            ->assertStatus(403);
    }

    public function test_mark_complete_with_tampered_foreign_id_writes_nothing(): void
    {
        [$user, $courseA, , , $lessonsC] = $this->fixtures();

        $this->actingAs($user);

        Livewire::test(CourseViewer::class, ['course' => $courseA])
            ->set('currentLessonId', $lessonsC[0]->id) // tamper the public prop
            ->call('markComplete');

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id'   => $user->id,
            'lesson_id' => $lessonsC[0]->id,
        ]);
    }
}
