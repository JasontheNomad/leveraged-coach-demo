<x-app-layout>
    <x-slot name="header">{{ $room->title }}</x-slot>

    <div class="p-8">
        <div class="rounded-xl p-12 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="#3a3a3a" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="text-base font-medium text-white mb-1">Live sessions are disabled in this demo</p>
            <p class="text-sm" style="color:#9ca3af;">In the real platform this opens a live video room powered by Daily.co.</p>
        </div>
    </div>
</x-app-layout>
