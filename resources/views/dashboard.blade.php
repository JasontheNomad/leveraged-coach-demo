<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="p-6 lg:p-8">

        {{-- Welcome --}}
        <section class="mb-8">
            <h2 class="text-2xl font-bold text-white">Welcome back, {{ auth()->user()->full_name }} 👋</h2>
        </section>

        {{-- Two-column layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- ══════════════════════════════════════════════════════ --}}
            {{-- LEFT COLUMN (2/3)                                      --}}
            {{-- ══════════════════════════════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Next Live Session Banner --}}
                @if($bannerRoom)
                <div class="rounded-xl p-5 flex items-center gap-5" style="background:#1a1a1a; border:1px solid #2a2a2a; border-left:4px solid #f5a623;">

                    @if($bannerRoom->room->thumbnail)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('r2')->url($bannerRoom->room->thumbnail) }}"
                             alt="{{ $bannerRoom->title }}"
                             class="w-32 h-auto block rounded-lg">
                    @else
                        <div class="w-16 h-16 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#2a2a2a;">
                            <svg class="w-7 h-7" fill="none" stroke="#f5a623" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full" style="background:#f5a623; color:#111111;"
                                  data-countdown="{{ $bannerRoom->start->toIso8601String() }}"></span>
                            <span class="text-xs font-medium" style="color:#9ca3af;">Next Live Session</span>
                        </div>
                        <h4 class="font-bold text-white text-base leading-snug truncate">{{ $bannerRoom->title }}</h4>
                        <p class="text-xs mt-0.5" style="color:#ffffff;">
                            <span data-localtime="{{ $bannerRoom->start->toIso8601String() }}" data-format="long"></span> – <span data-localtime="{{ $bannerRoom->end->toIso8601String() }}" data-format="time"></span>
                        </p>
                    </div>

                    <div class="flex-shrink-0 flex items-center gap-2">
                        <a href="{{ route('live-sessions.show', $bannerRoom->room->daily_room_name) }}"
                           target="_blank" rel="noopener"
                           class="px-4 py-2 text-sm font-semibold rounded-lg transition-all"
                           style="background:#f5a623; color:#111111;"
                           onmouseover="this.style.background='#e09415';"
                           onmouseout="this.style.background='#f5a623';">
                            Join →
                        </a>

                        @if(auth()->user()?->role === 'admin')
                            <form method="POST"
                                  action="{{ route('live-sessions.cancel-occurrence', ['room' => $bannerRoom->room_id]) }}"
                                  onsubmit="return confirm('Cancel this session? This only cancels this one date.')">
                                @csrf
                                <input type="hidden" name="date" value="{{ $bannerRoom->start->toDateString() }}">
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-semibold rounded-lg"
                                        style="background:#7f1d1d; color:#fca5a5; border:none; cursor:pointer;">
                                    Cancel
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Recommended Courses --}}
                @if($yourCourses->isNotEmpty())
                <section>
                    <h3 class="text-lg font-semibold text-white mb-4">Recommended Stacks</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        @foreach($yourCourses as $entry)
                            @php $course = $entry['course']; @endphp
                            <a href="{{ $entry['url'] }}"
                               class="rounded-xl overflow-hidden flex flex-col transition-all"
                               style="background:#1a1a1a; border:1px solid #2a2a2a;"
                               onmouseover="this.style.borderColor='#f5a623';"
                               onmouseout="this.style.borderColor='#2a2a2a';">

                                @if($course->thumbnail_url)
                                    <img src="{{ $course->thumbnail_url }}"
                                         alt="{{ $course->title }}"
                                         class="w-full h-auto block rounded-lg">
                                @else
                                    <div class="w-full h-36 flex items-center justify-center" style="background:#111111;">
                                        <svg class="w-8 h-8" fill="none" stroke="#3a3a3a" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                @endif

                                <div class="p-4 flex flex-col flex-1">
                                    <h4 class="font-semibold text-white text-sm leading-snug mb-3">{{ $course->title }}</h4>

                                    @if($entry['status'] === 'in_progress')
                                        <div class="mb-3">
                                            <div class="flex justify-between text-xs mb-1" style="color:#ffffff;">
                                                <span>Progress</span>
                                                <span>{{ $entry['progress_pct'] }}%</span>
                                            </div>
                                            <div class="w-full rounded-full h-1.5" style="background:#2a2a2a;">
                                                <div class="h-1.5 rounded-full" style="background:#f5a623; width:{{ $entry['progress_pct'] }}%;"></div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mt-auto inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold rounded-lg"
                                         style="background:#f5a623; color:#111111;">
                                        {{ $entry['status'] === 'in_progress' ? 'Continue →' : 'Start →' }}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- Upcoming Sessions list --}}
                @if($upcomingSessions->isNotEmpty())
                <section>
                    <h3 class="text-lg font-semibold text-white mb-4">Upcoming Sessions</h3>

                    <div class="rounded-xl overflow-hidden" style="border:1px solid #2a2a2a;">
                        @foreach($upcomingSessions as $i => $room)
                            <div class="flex items-center gap-4 px-4 py-3 transition-all {{ $i > 0 ? 'border-t' : '' }}"
                                 style="{{ $i > 0 ? 'border-color:#2a2a2a;' : '' }} background:#1a1a1a;"
                                 onmouseover="this.style.background='#222222';"
                                 onmouseout="this.style.background='#1a1a1a';">

                                @if($room->room->thumbnail)
                                    <a href="{{ route('live-sessions.show', $room->room->daily_room_name) }}" class="flex-shrink-0">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('r2')->url($room->room->thumbnail) }}"
                                             alt="{{ $room->title }}"
                                             class="w-32 h-auto block rounded-lg">
                                    </a>
                                @else
                                    <a href="{{ route('live-sessions.show', $room->room->daily_room_name) }}"
                                       class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0"
                                       style="background:#2a2a2a;">
                                        <svg class="w-5 h-5" fill="none" stroke="#f5a623" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                        </svg>
                                    </a>
                                @endif

                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('live-sessions.show', $room->room->daily_room_name) }}"
                                       class="text-sm font-semibold text-white truncate block hover:underline">{{ $room->title }}</a>
                                    <p class="text-xs mt-0.5" style="color:#ffffff;">
                                        <span data-localtime="{{ $room->start->toIso8601String() }}" data-format="long"></span>
                                    </p>
                                </div>

                                <span class="flex-shrink-0 text-xs font-medium px-2 py-1 rounded" style="background:#2a2a2a; color:#9ca3af;">
                                    Live Session
                                </span>

                                @if(auth()->user()?->role === 'admin')
                                    <form method="POST"
                                          action="{{ route('live-sessions.cancel-occurrence', ['room' => $room->room_id]) }}"
                                          onsubmit="return confirm('Cancel this session? This only cancels this one date.')">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $room->start->toDateString() }}">
                                        <button type="submit"
                                                class="flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-lg"
                                                style="background:#7f1d1d; color:#fca5a5; border:none; cursor:pointer;">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif

            </div>{{-- end left column --}}

            {{-- ══════════════════════════════════════════════════════ --}}
            {{-- RIGHT SIDEBAR (1/3)                                    --}}
            {{-- ══════════════════════════════════════════════════════ --}}
            <div class="space-y-6">

                {{-- Messages --}}
                <section>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-white">Messages</h3>
                        <a href="{{ route('messages.index') }}" class="text-xs font-medium" style="color:#f5a623;">View all →</a>
                    </div>

                    @if($recentConversations->isNotEmpty())
                        <div class="rounded-xl overflow-hidden" style="border:1px solid #2a2a2a;">
                            @foreach($recentConversations as $i => $conv)
                                <a href="{{ route('messages.index') }}?conversation={{ $conv['id'] }}"
                                   class="flex items-center gap-3 px-4 py-3 transition-all {{ $i > 0 ? 'border-t' : '' }}"
                                   style="{{ $i > 0 ? 'border-color:#2a2a2a;' : '' }} background:#1a1a1a;"
                                   onmouseover="this.style.background='#222222';"
                                   onmouseout="this.style.background='#1a1a1a';">

                                    {{-- Avatar initials --}}
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold" style="background:#2a2a2a; color:#f5a623;">
                                        {{ $conv['initials'] }}
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-semibold text-white truncate">{{ $conv['name'] }}</span>
                                            <span class="text-xs flex-shrink-0" style="color:#ffffff;">{{ $conv['time'] }}</span>
                                        </div>
                                        <p class="text-xs truncate mt-0.5" style="color:#9ca3af;">{{ $conv['preview'] }}</p>
                                    </div>

                                    @if($conv['unread'])
                                        <div class="w-2 h-2 rounded-full flex-shrink-0" style="background:#f5a623;"></div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl p-4 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                            <p class="text-xs" style="color:#9ca3af;">No conversations yet.</p>
                        </div>
                    @endif
                </section>

                {{-- Announcements --}}
                <section x-data="{ open: false, ann: { title: '', body: '', isManual: false } }">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-white">Announcements</h3>
                        @if($announcements->isNotEmpty())
                            <form method="POST" action="{{ route('announcements.dismiss-all') }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium" style="color:#f5a623;">Mark all as read</button>
                            </form>
                        @endif
                    </div>

                    @if($announcements->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($announcements as $ann)
                                @php $isManual = $ann->type === 'manual'; @endphp
                                <div class="rounded-xl px-4 py-3 flex gap-3 items-start cursor-pointer"
                                     style="background:#1a1a1a; border:1px solid {{ $isManual ? '#dc2626' : '#2a2a2a' }}; {{ $isManual ? 'border-left:4px solid #dc2626;' : '' }}"
                                     @click="ann = {{ json_encode(['title' => $ann->title, 'body' => $ann->body ?? '', 'isManual' => $isManual, 'bodyHtml' => $isManual ? markdown_to_html($ann->body ?? '') : '']) }}; open = true">

                                    {{-- Icon --}}
                                    <div class="flex-shrink-0 mt-0.5">
                                        @switch($ann->type)
                                            @case('recording')
                                                <svg class="w-4 h-4" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                                </svg>
                                                @break
                                            @case('course')
                                                <svg class="w-4 h-4" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/>
                                                </svg>
                                                @break
                                            @case('lesson')
                                                <svg class="w-4 h-4" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                </svg>
                                                @break
                                            @case('schedule')
                                                <svg class="w-4 h-4" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                @break
                                            @default
                                                <svg class="w-4 h-4" fill="none" stroke="#dc2626" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                                </svg>
                                        @endswitch
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold leading-snug" style="color:{{ $isManual ? '#f87171' : '#ffffff' }};">
                                            {{ $ann->title }}
                                        </p>
                                        @if($ann->body)
                                            @if($isManual)
                                                <p class="text-xs mt-0.5 line-clamp-3" style="color:#9ca3af;">{{ strip_tags(markdown_to_html($ann->body)) }}</p>
                                            @else
                                                <p class="text-xs mt-0.5 line-clamp-3" style="color:#9ca3af; white-space:pre-line;">{{ $ann->body }}</p>
                                            @endif
                                        @endif
                                        <p class="text-xs mt-1" style="color:#ffffff;">{{ $ann->created_at->diffForHumans() }}</p>
                                    </div>

                                    {{-- Dismiss --}}
                                    <form method="POST" action="{{ route('announcements.dismiss', $ann) }}" class="flex-shrink-0" @click.stop>
                                        @csrf
                                        <button type="submit" title="Dismiss" style="color:#6b7280; line-height:1;" class="hover:text-white transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl p-4 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                            <p class="text-xs" style="color:#9ca3af;">No New Announcements</p>
                        </div>
                    @endif

                    {{-- Announcement detail modal --}}
                    <template x-teleport="body">
                        <div x-show="open"
                             x-cloak
                             class="fixed inset-0 z-[200] flex items-center justify-center p-4"
                             style="background:rgba(0,0,0,0.75);"
                             @click="open = false"
                             @keydown.escape.window="open = false">
                            <div class="relative w-full max-w-md rounded-2xl p-5 overflow-y-auto max-h-[80vh] bg-[#1e1e1e]"
                                 :style="ann.isManual ? 'border:1px solid #dc2626; border-left:4px solid #dc2626;' : 'border:1px solid #2a2a2a;'"
                                 @click.stop>
                                <button @click="open = false"
                                        class="absolute top-3 right-3 transition-colors"
                                        style="color:#6b7280;"
                                        onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#6b7280'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <p class="text-xl font-semibold mb-3 pr-6" style="color:#ffffff;" x-text="ann.title"></p>
                                <div x-show="ann.isManual"
                                     x-html="ann.bodyHtml"
                                     class="ann-prose"></div>
                                <p x-show="!ann.isManual"
                                   class="text-base leading-7"
                                   style="color:#9ca3af; white-space:pre-line;"
                                   x-text="ann.body"></p>
                            </div>
                        </div>
                    </template>
                </section>

                {{-- Your Progress --}}
                <section>
                    <h3 class="text-base font-semibold text-white mb-3">Your Progress</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl p-4 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                            <p class="text-2xl font-bold" style="color:#f5a623;">
                                {{ $completedCount }}<span class="text-base font-normal" style="color:#ffffff;">/{{ $totalCourses }}</span>
                            </p>
                            <p class="text-xs mt-1" style="color:#9ca3af;">Stacks<br>Completed</p>
                        </div>

                        <div class="rounded-xl p-4 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                            <p class="text-2xl font-bold" style="color:#f5a623;">—</p>
                            <p class="text-xs mt-1" style="color:#9ca3af;">Sessions<br>Attended</p>
                        </div>
                    </div>
                </section>

            </div>{{-- end right sidebar --}}

        </div>{{-- end grid --}}

    </div>

    @include('partials.localtime')
</x-app-layout>
