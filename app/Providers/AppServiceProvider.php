<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Recording;
use App\Models\Room;
use App\Models\User;
use App\Listeners\RevokeGroupAccessOnCancellation;
use App\Observers\CourseObserver;
use App\Observers\LessonObserver;
use App\Observers\RecordingObserver;
use App\Observers\RoomObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Room::observe(RoomObserver::class);
        Recording::observe(RecordingObserver::class);
        Course::observe(CourseObserver::class);
        Lesson::observe(LessonObserver::class);

        Event::listen(WebhookReceived::class, RevokeGroupAccessOnCancellation::class);

        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'admin') {
                return true;
            }
        });
    }
}
