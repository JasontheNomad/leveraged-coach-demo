<x-app-layout>
    <x-slot name="header">Recordings</x-slot>

    <div class="p-8">
        @if($recordings->isEmpty())
            <div class="rounded-xl p-12 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="#3a3a3a" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <p class="text-base font-medium text-white mb-1">No recordings yet</p>
                <p class="text-sm" style="color:#9ca3af;">Check back after your next live session.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($recordings as $recording)
                    <div class="rounded-xl overflow-hidden flex flex-col transition-all"
                         style="background:#1a1a1a; border:1px solid #2a2a2a;"
                         onmouseover="this.style.borderColor='#f5a623';"
                         onmouseout="this.style.borderColor='#2a2a2a';">

                        {{-- Thumbnail --}}
                        @if($recording->thumbnail_url)
                            <img src="{{ $recording->thumbnail_url }}"
                                 alt="{{ $recording->title }}"
                                 class="w-full object-cover"
                                 style="aspect-ratio:16/9;">
                        @else
                            <div class="w-full flex items-center justify-center" style="background:#111111; aspect-ratio:16/9;">
                                <svg class="w-10 h-10" fill="none" stroke="#3a3a3a" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="p-5 flex flex-col flex-1 gap-2">
                            <h3 class="font-semibold text-white text-base leading-snug">{{ $recording->title }}</h3>

                            <p class="text-xs" style="color:#9ca3af;">
                                {{ $recording->room?->title }}
                            </p>

                            <div class="flex items-center gap-3 text-xs" style="color:#6b7280;">
                                @if($recording->recorded_at)
                                    <span>{{ $recording->recorded_at->format('M j, Y') }}</span>
                                @endif
                                @if($recording->duration_seconds)
                                    <span>·</span>
                                    <span>{{ $recording->formattedDuration() }}</span>
                                @endif
                            </div>

                            <a href="{{ url('/recordings/' . $recording->id) }}"
                               class="mt-auto inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg transition-all"
                               style="background:#f5a623; color:#111111;"
                               onmouseover="this.style.background='#e09415';"
                               onmouseout="this.style.background='#f5a623';">
                                Watch →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $recordings->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
