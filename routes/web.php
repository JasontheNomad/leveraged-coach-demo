<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

Route::get('/dashboard', function () {
    $user   = auth()->user();
    $userId = $user->id;

    // ── Live sessions ──────────────────────────────────────────
    $groupService     = app(\App\Services\GroupService::class);
    $from             = now();
    $to               = now()->addMonths(3);
    $roomQuery        = \App\Models\Room::whereNotNull('scheduled_at')
        ->with(['exceptions', 'host']);
    $groupService->scopeAccessible($roomQuery, $user);
    $allUpcoming      = $roomQuery
        ->get()
        ->flatMap(fn ($room) => $room->occurrences($from, $to))
        ->sortBy('start')
        ->values();
    $bannerRoom       = $allUpcoming->first();
    $upcomingSessions = $allUpcoming->skip(1)->take(5)->values();

    // ── Course progress data ───────────────────────────────────
    $completedLessonIds  = \App\Models\LessonProgress::where('user_id', $userId)
        ->whereNotNull('completed_at')->pluck('lesson_id')->all();

    $progressedLessonIds = \App\Models\LessonProgress::where('user_id', $userId)
        ->pluck('lesson_id')->unique()->all();

    $lastAtByCourse = \Illuminate\Support\Facades\DB::table('lesson_progress as lp')
        ->join('lessons as l', 'l.id', '=', 'lp.lesson_id')
        ->join('modules as m', 'm.id', '=', 'l.module_id')
        ->where('lp.user_id', $userId)
        ->groupBy('m.course_id')
        ->selectRaw('m.course_id, MAX(lp.updated_at) as last_at')
        ->pluck('last_at', 'course_id');

    $courseQuery  = \App\Models\Course::published()->with('modules.lessons');
    $groupService->scopeAccessible($courseQuery, $user);
    $allCourses = $courseQuery->get();

    $completedCount = 0;
    $inProgress     = collect();
    $notStarted     = collect();

    foreach ($allCourses as $course) {
        $lessonIds = $course->modules->flatMap(fn($m) => $m->lessons->pluck('id'))->all();
        if (!$lessonIds) continue;

        if (empty(array_diff($lessonIds, $completedLessonIds))) {
            $completedCount++;
            continue;
        }

        if (array_intersect($lessonIds, $progressedLessonIds)) {
            $course->_last_at = $lastAtByCourse[$course->id] ?? '0000-00-00';
            $inProgress->push($course);
        } else {
            $notStarted->push($course);
        }
    }

    $inProgress  = $inProgress->sortByDesc('_last_at')->take(3);
    $yourCourses = collect();

    foreach ($inProgress as $course) {
        $lessonIds    = $course->modules->flatMap(fn($m) => $m->lessons->pluck('id'))->all();
        $totalLessons = count($lessonIds);
        $doneLessons  = count(array_intersect($lessonIds, $completedLessonIds));
        $pct          = $totalLessons > 0 ? (int) round($doneLessons / $totalLessons * 100) : 0;

        $lastLessonId = \App\Models\LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->latest('updated_at')
            ->value('lesson_id');

        $yourCourses->push([
            'course'       => $course,
            'status'       => 'in_progress',
            'progress_pct' => $pct,
            'url'          => $lastLessonId
                ? route('courses.show', $course->slug) . '?lesson=' . $lastLessonId
                : route('courses.show', $course->slug),
        ]);
    }

    $needed = 3 - $yourCourses->count();
    if ($needed > 0) {
        foreach ($notStarted->take($needed) as $course) {
            $yourCourses->push([
                'course'       => $course,
                'status'       => 'not_started',
                'progress_pct' => 0,
                'url'          => route('courses.show', $course->slug),
            ]);
        }
    }

    // ── Messages ───────────────────────────────────────────────
    $recentConversations = $user->conversations()
        ->with(['latestMessage.sender', 'participants'])
        ->orderByDesc('conversations.updated_at')
        ->take(3)
        ->get()
        ->map(function ($conv) use ($user) {
            $other   = $conv->participants->firstWhere('id', '!=', $user->id);
            $name    = $other?->full_name ?? 'Unknown';
            $parts   = array_values(array_filter(explode(' ', $name)));
            $initials = count($parts) >= 2
                ? strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1))
                : strtoupper(substr($name, 0, 1));
            $last    = $conv->latestMessage;
            return [
                'id'       => $conv->id,
                'name'     => $name,
                'initials' => $initials,
                'preview'  => $last?->body
                    ? \Illuminate\Support\Str::limit($last->body, 55)
                    : ($last ? 'Attachment' : 'No messages yet'),
                'time'     => $last?->created_at?->diffForHumans() ?? '',
                'unread'   => $conv->unreadCountFor($user) > 0,
            ];
        });

    // ── Announcements ──────────────────────────────────────────
    $dismissedIds = \App\Models\AnnouncementDismissal::where('user_id', $user->id)
        ->pluck('announcement_id')
        ->all();

    $announcements = \App\Models\Announcement::visibleTo($user)
        ->when(count($dismissedIds), fn($q) => $q->whereNotIn('id', $dismissedIds))
        ->latest()
        ->take(5)
        ->get();

    // ── Progress stats ─────────────────────────────────────────
    $totalCourses = $allCourses->count();

    return view('dashboard', compact(
        'bannerRoom', 'upcomingSessions', 'yourCourses',
        'recentConversations', 'announcements',
        'completedCount', 'totalCourses'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

// ── Announcement dismissal ──────────────────────────────────────
Route::post('/announcements/{announcement}/dismiss', function (\App\Models\Announcement $announcement) {
    \App\Models\AnnouncementDismissal::firstOrCreate([
        'user_id'         => auth()->id(),
        'announcement_id' => $announcement->id,
    ], [
        'dismissed_at' => now(),
    ]);

    return back();
})->middleware('auth')->name('announcements.dismiss');

Route::post('/announcements/dismiss-all', function () {
    $user = auth()->user();

    $dismissedIds = \App\Models\AnnouncementDismissal::where('user_id', $user->id)
        ->pluck('announcement_id')
        ->all();

    $visible = \App\Models\Announcement::visibleTo($user)
        ->when(count($dismissedIds), fn($q) => $q->whereNotIn('id', $dismissedIds))
        ->pluck('id');

    $now = now();
    $rows = $visible->map(fn($id) => [
        'user_id'         => $user->id,
        'announcement_id' => $id,
        'dismissed_at'    => $now,
    ])->all();

    if ($rows) {
        \App\Models\AnnouncementDismissal::insertOrIgnore($rows);
    }

    return back();
})->middleware('auth')->name('announcements.dismiss-all');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileAvatarController::class, 'update'])->name('profile.avatar.update');
});

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->middleware('auth')->name('courses.show');

Route::get('/live-sessions', [RoomController::class, 'index'])->name('live-sessions.index');
Route::get('/live-sessions/{roomName}', [RoomController::class, 'show'])->middleware('auth')->name('live-sessions.show');
Route::post('/admin/rooms/{room}/occurrences/cancel', [RoomController::class, 'cancelOccurrence'])
    ->middleware(['auth', 'can:admin'])
    ->name('live-sessions.cancel-occurrence');

// Redirect old /rooms URLs to /live-sessions
Route::redirect('/rooms', '/live-sessions');
Route::get('/rooms/{roomName}', fn (string $roomName) => redirect("/live-sessions/{$roomName}"));

Route::get('/recordings', [RecordingController::class, 'index'])->middleware('auth')->name('recordings.index');
Route::get('/recordings/{recording}', [RecordingController::class, 'show'])->middleware('auth')->name('recordings.show');

Route::middleware('auth')->group(function () {
    Route::get('/messages',                              [ConversationController::class, 'index'])->name('messages.index');
    Route::post('/messages',                             [ConversationController::class, 'store'])->name('messages.store');
    Route::get('/messages/{conversation}',               [ConversationController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/send',         [MessageController::class, 'store'])->name('messages.send');
    Route::post('/messages/{conversation}/read',         [ConversationController::class, 'markRead'])->name('messages.read');
    Route::post('/messages/{message}/react',             [MessageController::class, 'react'])->name('messages.react');
    Route::get('/members',                               [MembersController::class, 'index'])->name('members.index');
    Route::get('/members/search',                        [ConversationController::class, 'searchMembers'])->name('members.search');
    Route::get('/groups/members',                        [ConversationController::class, 'groupMembers'])->name('groups.members');
    Route::get('/attachments/{attachment}',              [AttachmentController::class, 'show'])->name('attachments.show');
});

require __DIR__.'/auth.php';
