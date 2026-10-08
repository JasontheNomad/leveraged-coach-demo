<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    // Skip LessonObserver so seeding doesn't post a "new lesson" announcement per lesson.
    use WithoutModelEvents;

    /**
     * Seed the demo courses from database/seeders/data/demo-courses.json.
     */
    public function run(): void
    {
        $courses = json_decode(file_get_contents(__DIR__ . '/data/demo-courses.json'), true);

        foreach ($courses as $data) {
            $course = Course::create([
                'title'       => $data['title'],
                'slug'        => $data['slug'],
                'description' => $data['description'],
                'thumbnail'   => $data['thumbnail'],
                'price_type'  => $data['price_type'],
                'published'   => $data['published'],
            ]);

            foreach ($data['modules'] as $moduleData) {
                $module = $course->modules()->create([
                    'title'    => $moduleData['title'],
                    'position' => $moduleData['position'],
                ]);

                foreach ($moduleData['lessons'] as $lesson) {
                    $module->lessons()->create($lesson);
                }
            }
        }
    }
}
