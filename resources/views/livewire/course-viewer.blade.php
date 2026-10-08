<div class="flex" style="height: calc(100vh - 64px);">

    {{-- Lesson sidebar --}}
    <aside class="w-72 flex-shrink-0 overflow-y-auto" style="background:#111111; border-right:1px solid #2a2a2a;">
        <div class="px-4 py-4" style="border-bottom:1px solid #2a2a2a;">
            <h2 class="font-semibold text-white text-sm leading-snug">{{ $this->course->title }}</h2>
        </div>

        @foreach($modules as $module)
            <div style="border-bottom:1px solid #2a2a2a;">
                <div class="px-4 py-3" style="background:#0f0f0f;">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:#6b7280;">{{ $module->title }}</p>
                </div>
                @foreach($module->lessons as $lesson)
                    <button wire:key="lesson-btn-{{ $lesson->id }}"
                            wire:click="selectLesson({{ $lesson->id }})"
                            class="flex items-center gap-3 w-full text-left px-4 py-3 text-sm transition-all"
                            style="{{ $currentLessonId === $lesson->id
                                ? 'background:#f5a623; color:#111111; font-weight:600;'
                                : 'color:#9ca3af;' }}"
                            @if($currentLessonId !== $lesson->id)
                                onmouseover="this.style.background='#1a1a1a';this.style.color='#ffffff';"
                                onmouseout="this.style.background='transparent';this.style.color='#9ca3af';"
                            @endif>

                        @if(in_array($lesson->id, $completedIds))
                            <span class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center"
                                  style="background:#16a34a;">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        @else
                            <span class="flex-shrink-0 w-5 h-5 rounded-full border-2"
                                  style="{{ $currentLessonId === $lesson->id ? 'border-color:#111111;' : 'border-color:#3a3a3a;' }}">
                            </span>
                        @endif

                        <span class="flex-1 leading-snug">{{ $lesson->title }}</span>

                        @if($lesson->is_preview)
                            <span class="text-xs font-medium" style="{{ $currentLessonId === $lesson->id ? 'color:#111111;' : 'color:#86efac;' }}">Free</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endforeach
    </aside>

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto" style="background:#0f0f0f;">
        @if($currentLesson)
            <div class="max-w-4xl mx-auto p-8">

                {{-- Video --}}
                @if($currentLesson->video_url)
                    <div class="rounded-xl overflow-hidden shadow-lg mb-6" style="background:#000000;">
                        @if(in_array($currentLesson->video_type, ['youtube', 'vimeo', 'bunny']))
                            <iframe src="{{ $currentLesson->embed_url }}"
                                    width="100%"
                                    height="480"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    class="w-full aspect-video">
                            </iframe>
                        @else
                            <video src="{{ $currentLesson->embed_url }}"
                                   controls
                                   class="w-full aspect-video">
                                Your browser does not support the video tag.
                            </video>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl flex items-center justify-center aspect-video mb-6" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                        <p class="text-sm" style="color:#6b7280;">No video for this lesson</p>
                    </div>
                @endif

                {{-- Title + actions --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-white">{{ $currentLesson->title }}</h1>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if($isCompleted)
                            <span class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold"
                                  style="background:#052e16; color:#86efac;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Completed
                            </span>
                        @else
                            <button wire:click="markComplete"
                                    wire:loading.attr="disabled"
                                    class="px-5 py-2 rounded-lg text-sm font-semibold transition-all disabled:opacity-60"
                                    style="background:#f5a623; color:#111111;"
                                    onmouseover="this.style.background='#e09415';"
                                    onmouseout="this.style.background='#f5a623';">
                                <span wire:loading.remove wire:target="markComplete">Mark complete</span>
                                <span wire:loading wire:target="markComplete">Saving…</span>
                            </button>
                        @endif

                        @if($nextLesson)
                            <button wire:key="next-lesson-{{ $nextLesson->id }}"
                                    wire:click="selectLesson({{ $nextLesson->id }})"
                                    class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition-all"
                                    style="background:#1a1a1a; border:1px solid #3a3a3a; color:#ffffff;"
                                    onmouseover="this.style.borderColor='#f5a623';this.style.color='#f5a623';"
                                    onmouseout="this.style.borderColor='#3a3a3a';this.style.color='#ffffff';">
                                Next lesson
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        @elseif($isCompleted)
                            <span wire:key="course-complete"
                                  class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold"
                                  style="background:#1e3a5f; color:#93c5fd;">
                                🎉 Stack complete!
                            </span>
                        @endif
                    </div>
                </div>

                @if($currentLesson->duration_seconds)
                    <p class="mt-2 text-sm" style="color:#6b7280;">
                        {{ gmdate('G:i:s', $currentLesson->duration_seconds) }} duration
                    </p>
                @endif

                @if($currentLesson->body)
                    <div class="lesson-body prose prose-invert max-w-none mt-6"
                         style="color:#d1d5db;">
                        {!! $currentLesson->body_html !!}
                    </div>
                @endif

            </div>
        @else
            <div class="flex items-center justify-center h-full">
                <p class="text-sm" style="color:#6b7280;">Select a lesson to get started.</p>
            </div>
        @endif
    </main>

</div>
