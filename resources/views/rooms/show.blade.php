<x-app-layout>
    <x-slot name="header">
        <span>{{ $room->title }}</span>
        <button onclick="leaveRoom()"
                class="px-4 py-2 text-sm font-semibold rounded-lg transition-all"
                style="background:#dc2626; color:#ffffff;"
                onmouseover="this.style.background='#b91c1c';"
                onmouseout="this.style.background='#dc2626';">
            Leave Room
        </button>
    </x-slot>

    <div class="px-8 pt-6">
        <div style="position: relative; width: min(100%, calc((100vh - 88px) * 16 / 9)); aspect-ratio: 16/9; margin-inline: auto; border-radius: 0.75rem; overflow: hidden; background: #000000;">
            <div id="daily-frame" style="position: absolute; inset: 0; width: 100%; height: 100%;"></div>
        </div>
    </div>

    @push('scripts')
    <script>
        function exitRoom() {
            window.close();
            // Fallback: browser refused to close the tab (not script-opened)
            setTimeout(function () {
                window.location.href = '/live-sessions';
            }, 200);
        }

        function leaveRoom() {
            if (typeof call !== 'undefined') {
                call.leave().finally(exitRoom);
            } else {
                exitRoom();
            }
        }
    </script>
    @if($room->daily_room_url && $token)
    <script src="https://unpkg.com/@daily-co/daily-js"></script>
    <script>
        var call = DailyIframe.createFrame(
            document.getElementById('daily-frame'),
            {
                iframeStyle: { width: '100%', height: '100%', border: 'none' },
                showLeaveButton: true,
                theme: {
                    colors: {
                        accent: '#f5a623',
                        accentText: '#ffffff',
                        background: '#0f0f0f',
                        backgroundAccent: '#1a1a1a',
                        baseText: '#ffffff',
                        border: '#2a2a2a',
                        mainAreaBg: '#0f0f0f',
                        mainAreaBgAccent: '#111111',
                        mainAreaText: '#ffffff',
                        supportiveText: '#9ca3af',
                    },
                },
            }
        );

        call.join({
            url: '{{ $room->daily_room_url }}',
            token: '{{ $token }}',
        });

        @if($room->enable_recording)
        call.on('joined-meeting', async () => {
            try {
                await call.startRecording({
                    width: 1920,
                    height: 1080,
                    videoBitrate: 4000,
                    audioBitrate: 128,
                    minIdleTimeOut: 60,
                    maxDuration: 14400
                });
            } catch (e) {
                console.log('Recording start failed or already recording:', e);
            }
        });

        call.on('left-meeting', async () => {
            try {
                await call.stopRecording();
            } catch (e) {
                // no active recording or already stopped — safe to ignore
            }
            exitRoom();
        });
        @endif
    </script>
    @elseif($room->daily_room_url)
    <script>
        document.getElementById('daily-frame').innerHTML =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#9ca3af;font-size:0.875rem;">Couldn\'t connect to the session — please refresh or try again in a moment.</div>';
    </script>
    @else
    <script>
        document.getElementById('daily-frame').innerHTML =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#9ca3af;font-size:0.875rem;">Room not configured — please contact support.</div>';
    </script>
    @endif
    @endpush
</x-app-layout>
