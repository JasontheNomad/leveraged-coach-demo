<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Models\Course;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseObserver
{
    public function updated(Course $course): void
    {
        if ($course->wasChanged('published') && $course->published) {
            Announcement::create([
                'title'            => 'New stack available: ' . $course->title,
                'body'             => null,
                'type'             => 'course',
                'icon'             => Announcement::iconForType('course'),
                'visibility_roles' => null,
            ]);
        }
    }

    public function deleted(Course $course): void
    {
        $thumbnail = $course->thumbnail;

        if (!$thumbnail || str_starts_with($thumbnail, 'http')) {
            return;
        }

        try {
            if (Storage::disk('r2')->delete($thumbnail)) {
                Log::info('R2 course thumbnail deleted', ['path' => $thumbnail]);
            } else {
                Log::warning('R2 course thumbnail not found or delete returned false', ['path' => $thumbnail]);
            }
        } catch (\Throwable $e) {
            Log::error('R2 course thumbnail deletion failed', [
                'path'    => $thumbnail,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
