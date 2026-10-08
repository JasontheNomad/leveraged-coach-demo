<x-app-layout>
    <x-slot name="header">Stacks</x-slot>

    <div class="p-8">
        @if($courses->isEmpty())
            <div class="rounded-xl p-12 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                <p style="color:#9ca3af;" class="text-sm">No stacks available yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($courses as $course)
                    <div class="rounded-xl overflow-hidden flex flex-col transition-all"
                         style="background:#1a1a1a; border:1px solid #2a2a2a;"
                         onmouseover="this.style.borderColor='#f5a623';"
                         onmouseout="this.style.borderColor='#2a2a2a';">

                        @if($course->thumbnail_url)
                            <img src="{{ $course->thumbnail_url }}"
                                 alt="{{ $course->title }}"
                                 class="w-full h-auto block rounded-t-xl">
                        @else
                            <div class="w-full h-48 flex items-center justify-center" style="background:#111111;">
                                <svg class="w-10 h-10" fill="none" stroke="#3a3a3a" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                        @endif

                        <div class="p-5 flex flex-col flex-1">
                            <div class="mb-3">
                                <span class="text-xs font-semibold uppercase tracking-wide px-2.5 py-0.5 rounded-full"
                                      style="{{ $course->price_type === 'free' ? 'background:#052e16; color:#86efac;' : ($course->price_type === 'collective' ? 'background:transparent; color:#93c5fd; border:1px solid #93c5fd;' :'background:transparent; color:#EF9F27; border:1px solid #854F0B;') }}">
                                    {{ ['free' => 'Free', 'collective' => 'Collective', 'leveraged_coach' => 'Leveraged Coach'][$course->price_type] ?? $course->price_type }}
                                </span>
                            </div>

                            <h3 class="font-semibold text-white text-lg leading-snug">{{ $course->title }}</h3>

                            @if($course->description)
                                <div class="mt-2 text-sm line-clamp-3 flex-1 prose-sm" style="color:#9ca3af;">
                                    {!! $course->description_html !!}
                                </div>
                            @endif

                            @if($completedCourseIds->contains($course->id))
                                <a href="{{ route('courses.show', $course->slug) }}"
                                   class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg"
                                   style="background:#16a34a; color:#ffffff;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Stack Complete
                                </a>
                            @else
                                <a href="{{ route('courses.show', $course->slug) }}"
                                   class="mt-4 inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg transition-all"
                                   style="background:#f5a623; color:#111111;"
                                   onmouseover="this.style.background='#e09415';"
                                   onmouseout="this.style.background='#f5a623';">
                                    View Stack →
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
