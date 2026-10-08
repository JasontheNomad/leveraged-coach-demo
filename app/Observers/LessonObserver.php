<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Models\Lesson;

class LessonObserver
{
    public function created(Lesson $lesson): void
    {
        // Only announce if the parent course is already published
        $course = $lesson->module?->course;
        if (!$course?->published) {
            return;
        }

        Announcement::create([
            'title'            => 'New lesson added: ' . $lesson->title,
            'body'             => 'In stack: ' . $course->title,
            'type'             => 'lesson',
            'icon'             => Announcement::iconForType('lesson'),
            'visibility_roles' => null,
        ]);
    }
}
