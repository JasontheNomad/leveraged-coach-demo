<x-app-layout>
    <x-slot name="header">Live Sessions</x-slot>

    <div class="p-8 space-y-10">

        {{-- Upcoming --}}
        <section>
            <h3 class="text-lg font-semibold text-white mb-6">Upcoming</h3>

            @if($upcoming->isEmpty())
                <div class="rounded-xl p-6 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                    <p class="text-sm" style="color:#9ca3af;">No upcoming sessions scheduled. Check back soon.</p>
                </div>
            @else
                @php
                    $hero = $upcoming->first();
                    $rest = $upcoming->skip(1);
                @endphp

                <div class="max-w-3xl mx-auto space-y-8">

                {{-- ── Hero card ──────────────────────────────────────── --}}
                <div class="rounded-xl overflow-hidden" style="background:#1a1a1a; border:1px solid #2a2a2a;">

                    @if($hero->room->thumbnail)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('r2')->url($hero->room->thumbnail) }}"
                             alt="{{ $hero->title }}"
                             class="w-full h-auto block rounded-t-xl">
                    @else
                        <div class="w-full flex items-center justify-center" style="aspect-ratio:16/9; background:#111;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="#3a3a3a">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5 20.47 6.97A.75.75 0 0 1 21.75 7.5v9a.75.75 0 0 1-1.28.53l-4.72-3.53v-3Z"/>
                                <rect x="1.5" y="4.5" width="13.5" height="15" rx="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    @endif

                    <div class="p-6 flex flex-col gap-4">
                        <div>
                            <h4 class="text-xl font-bold text-white leading-snug mb-1">{{ $hero->title }}</h4>
                            <p class="text-sm" style="color:#9ca3af;">
                                <span data-localtime="{{ $hero->start->toIso8601String() }}" data-format="full"></span> – <span data-localtime="{{ $hero->end->toIso8601String() }}" data-format="time"></span>
                            </p>
                        </div>

                        {{-- Metadata chips --}}
                        <div class="flex flex-wrap gap-2">
                            <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background:#1c1917; color:#d6d3d1;" data-countdown="{{ $hero->start->toIso8601String() }}"></span>

                            <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background:#1a2a1a; color:#86efac;">Live Room</span>

                            @if($hero->room->host)
                                <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background:#1e1e2e; color:#a5b4fc;">{{ $hero->room->host->full_name }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 flex-wrap">
                            <a href="{{ route('live-sessions.show', $hero->room->daily_room_name) }}"
                               target="_blank" rel="noopener"
                               class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold rounded-lg transition-all"
                               style="background:#f5a623; color:#111111;"
                               onmouseover="this.style.background='#e09415';"
                               onmouseout="this.style.background='#f5a623';">
                                Join Session →
                            </a>

                            @if(auth()->user()?->role === 'admin')
                                <form method="POST"
                                      action="{{ route('live-sessions.cancel-occurrence', ['room' => $hero->room_id]) }}"
                                      onsubmit="return confirm('Cancel this session? This only cancels this one date.')">
                                    @csrf
                                    <input type="hidden" name="date" value="{{ $hero->start->toDateString() }}">
                                    <button type="submit"
                                            class="inline-flex items-center px-5 py-2.5 text-sm font-semibold rounded-lg"
                                            style="background:#7f1d1d; color:#fca5a5; border:none; cursor:pointer;">
                                        Cancel This Date
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Remaining sessions grouped by month ──────────────── --}}
                @if($rest->isNotEmpty())
                    @php
                        $grouped = $rest->groupBy(fn($r) => $r->start->copy()->setTimezone('America/Phoenix')->format('F Y'));
                    @endphp

                    @foreach($grouped as $month => $sessions)
                        <div class="mt-8">
                            <h5 class="text-sm font-bold uppercase tracking-widest mb-3" style="color:#6b7280;">{{ $month }}</h5>

                            <div class="flex flex-col gap-2">
                                @foreach($sessions as $room)
                                    <div class="rounded-xl overflow-hidden flex items-center gap-4 px-4 py-3" style="background:#1a1a1a; border:1px solid #2a2a2a;">

                                        {{-- Thumbnail --}}
                                        @if($room->room->thumbnail)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('r2')->url($room->room->thumbnail) }}"
                                                 alt="{{ $room->title }}"
                                                 class="w-32 h-auto block rounded-lg flex-shrink-0">
                                        @endif

                                        {{-- Info --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-white truncate">{{ $room->title }}</p>
                                            <p class="text-xs mt-0.5" style="color:#6b7280;">
                                                <span data-localtime="{{ $room->start->toIso8601String() }}" data-format="short"></span> – <span data-localtime="{{ $room->end->toIso8601String() }}" data-format="time"></span>
                                            </p>
                                            <span class="text-xs font-medium mt-1 inline-block" style="color:#86efac;">Live Room</span>
                                        </div>

                                        {{-- Join + admin cancel --}}
                                        <div class="flex-shrink-0 flex items-center gap-2">
                                            <a href="{{ route('live-sessions.show', $room->room->daily_room_name) }}"
                                               target="_blank" rel="noopener"
                                               class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                                               style="background:#f5a623; color:#111111;"
                                               onmouseover="this.style.background='#e09415';"
                                               onmouseout="this.style.background='#f5a623';">
                                                Join →
                                            </a>

                                            @if(auth()->user()?->role === 'admin')
                                                <form method="POST"
                                                      action="{{ route('live-sessions.cancel-occurrence', ['room' => $room->room_id]) }}"
                                                      onsubmit="return confirm('Cancel this session? This only cancels this one date.')">
                                                    @csrf
                                                    <input type="hidden" name="date" value="{{ $room->start->toDateString() }}">
                                                    <button type="submit"
                                                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg"
                                                            style="background:#7f1d1d; color:#fca5a5; border:none; cursor:pointer;">
                                                        Cancel
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                </div>{{-- /max-w-3xl mx-auto --}}

            @endif
        </section>


    </div>

    @include('partials.localtime')
</x-app-layout>
