<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportCircleExport extends Command
{
    protected $signature = 'circle:import';
    protected $description = 'Import Circle courses/sections/lessons into the database via Circle API';

    private string $baseUrl = 'https://app.circle.so/api/admin/v2';
    private string $token;

    public function handle(): int
    {
        $this->token = config('services.circle.token');

        if (! $this->token) {
            $this->error('CIRCLE_API_TOKEN is not set in .env');
            return 1;
        }

        $this->info('Fetching spaces from Circle...');
        $spaces = $this->fetchAll('/spaces');
        $courses = collect($spaces)->where('space_type', 'course')->values();
        $this->info("  Found {$courses->count()} courses");

        $this->info('Fetching course sections...');
        $allSections = collect($this->fetchAll('/course_sections'))->groupBy('space_id');

        $this->info('Fetching course lessons...');
        $allLessons = collect($this->fetchAll('/course_lessons'))->groupBy('section_id');

        DB::transaction(function () use ($courses, $allSections, $allLessons) {
            foreach ($courses as $courseData) {
                $this->importCourse($courseData, $allSections, $allLessons);
            }
        });

        $this->info('Import complete.');
        return 0;
    }

    private function importCourse(array $courseData, $allSections, $allLessons): void
    {
        $title = $courseData['name'];
        $slug = Str::slug($title);

        $course = Course::firstOrCreate(
            ['slug' => $slug],
            ['title' => $title, 'published' => false]
        );

        $action = $course->wasRecentlyCreated ? 'Created' : 'Found';
        $this->line("{$action} course: {$title}");

        $sections = collect($allSections->get($courseData['id'], []))
            ->sortBy(fn($s) => $s['position'] ?? $s['id']);

        $position = 1;
        foreach ($sections as $sectionData) {
            $this->importSection($sectionData, $course, $position, $allLessons);
            $position++;
        }
    }

    private function importSection(array $sectionData, Course $course, int $position, $allLessons): void
    {
        $title = $sectionData['name'];

        $module = Module::firstOrCreate(
            ['course_id' => $course->id, 'title' => $title],
            ['position' => $position]
        );

        $this->line("  Module: {$title}");

        $lessons = collect($allLessons->get($sectionData['id'], []))
            ->sortBy(fn($l) => $l['position'] ?? $l['id']);

        foreach ($lessons as $index => $lessonData) {
            $this->importLesson($lessonData, $module, $index + 1);
        }
    }

    private function importLesson(array $lessonData, Module $module, int $position): void
    {
        $title = $lessonData['name'];
        $body = $lessonData['body_html'] ?? null;

        $lesson = Lesson::firstOrCreate(
            ['module_id' => $module->id, 'title' => $title],
            ['position' => $position, 'body' => $body]
        );

        $this->line("    Lesson: {$title}");
    }

    private function fetchAll(string $path): array
    {
        $results = [];
        $page = 1;

        do {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}{$path}", ['per_page' => 100, 'page' => $page]);

            if ($response->failed()) {
                $this->error("ERROR {$response->status()} on GET {$this->baseUrl}{$path}");
                $this->error($response->body());
                exit(1);
            }

            $data = $response->json();
            $records = $data['records'] ?? [];
            $results = array_merge($results, $records);
            $hasNextPage = $data['has_next_page'] ?? false;
            $page++;
        } while ($hasNextPage);

        return $results;
    }
}
