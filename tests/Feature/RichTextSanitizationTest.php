<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the output sanitizer (App\Support\RichText) via the model accessors
 * that the Blade {!! !!} lines render: Course->description_html and
 * Lesson->body_html. Legit RichEditor formatting must survive; injected
 * script / event handlers / unsafe schemes must be stripped; and <div>-wrapped
 * content must NOT blank out (the 92-lesson regression).
 */
class RichTextSanitizationTest extends TestCase
{
    use RefreshDatabase;

    private int $slugSeq = 0;

    /** Build a lesson (course -> module -> lesson) carrying the given raw body. */
    private function lessonWithBody(?string $body): Lesson
    {
        $course = Course::create([
            'title'     => 'Course',
            'slug'      => 'course-' . (++$this->slugSeq),
            'published' => true,
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title'     => 'Module',
            'position'  => 1,
        ]);

        return Lesson::create([
            'module_id' => $module->id,
            'title'     => 'Lesson',
            'body'      => $body,
            'position'  => 1,
        ]);
    }

    private function courseWithDescription(?string $description): Course
    {
        return Course::create([
            'title'       => 'Course',
            'slug'        => 'course-' . (++$this->slugSeq),
            'description' => $description,
            'published'   => true,
        ]);
    }

    public function test_preserves_formatting_and_forces_safe_rel_on_links(): void
    {
        $html = $this->lessonWithBody(
            '<p><strong>bold</strong> <em>italic</em> '
            . '<a href="https://docs.google.com/d/abc" target="_blank">Open doc</a></p>'
        )->body_html;

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
        $this->assertStringContainsString('href="https://docs.google.com/d/abc"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        // rel is force-injected even though the input had none.
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_div_wrapped_content_survives_non_blank(): void
    {
        $html = $this->lessonWithBody('<div><p>hello</p></div>')->body_html;

        $this->assertNotSame('', trim(strip_tags($html)));
        $this->assertStringContainsString('hello', $html);
        $this->assertStringContainsString('<div>', $html);
    }

    public function test_div_attributes_are_stripped_but_div_kept(): void
    {
        $html = $this->lessonWithBody('<div class="x" onclick="bad()"><p>kept</p></div>')->body_html;

        $this->assertStringContainsString('<div>', $html);
        $this->assertStringContainsString('kept', $html);
        $this->assertStringNotContainsString('class=', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_script_tag_is_stripped(): void
    {
        $html = $this->lessonWithBody('<p>hi</p><script>alert(document.cookie)</script>')->body_html;

        $this->assertStringContainsString('hi', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(document.cookie)', $html);
    }

    public function test_img_with_onerror_is_stripped(): void
    {
        $html = $this->lessonWithBody('<p>pic</p><img src=x onerror="steal()">')->body_html;

        $this->assertStringContainsString('pic', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_javascript_scheme_and_handlers_stripped_text_kept(): void
    {
        $html = $this->lessonWithBody('<a href="javascript:alert(1)" onclick="x()">click</a>')->body_html;

        $this->assertStringContainsString('click', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_null_and_empty_return_empty_string(): void
    {
        $this->assertSame('', $this->lessonWithBody(null)->body_html);
        $this->assertSame('', $this->lessonWithBody('')->body_html);
    }

    public function test_course_description_accessor_sanitizes_too(): void
    {
        $course = $this->courseWithDescription('<p><strong>ok</strong></p><script>alert(1)</script>');

        $this->assertStringContainsString('<strong>ok</strong>', $course->description_html);
        $this->assertStringNotContainsString('<script', $course->description_html);
        $this->assertSame('', $this->courseWithDescription(null)->description_html);
    }
}
