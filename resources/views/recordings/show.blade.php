<x-app-layout>
    <x-slot name="header">{{ $recording->title }}</x-slot>

    <div class="p-8 max-w-5xl mx-auto">

        {{-- Back link --}}
        <a href="{{ url('/recordings') }}"
           class="inline-flex items-center gap-2 text-sm mb-6 transition-colors"
           style="color:#9ca3af;"
           onmouseover="this.style.color='#ffffff';"
           onmouseout="this.style.color='#9ca3af';">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Recordings
        </a>

        {{-- Video player --}}
        <div class="rounded-xl overflow-hidden shadow-lg mb-6" style="background:#000000;">
            <video src="{{ $recording->r2_url }}"
                   controls
                   class="w-full"
                   style="max-height:70vh; display:block;"
                   @if($recording->thumbnail_url) poster="{{ $recording->thumbnail_url }}" @endif>
                Your browser does not support the video tag.
            </video>
        </div>

        {{-- Metadata --}}
        <div class="rounded-xl p-5" style="background:#1a1a1a; border:1px solid #2a2a2a;">
            <h1 class="text-xl font-bold text-white mb-2">{{ $recording->title }}</h1>

            <div class="flex items-center gap-4 text-sm" style="color:#9ca3af;">
                @if($recording->room)
                    <span>{{ $recording->room->title }}</span>
                @endif
                @if($recording->recorded_at)
                    <span>·</span>
                    <span>{{ $recording->recorded_at->format('M j, Y · g:i A') }}</span>
                @endif
                @if($recording->duration_seconds)
                    <span>·</span>
                    <span>{{ $recording->formattedDuration() }}</span>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
