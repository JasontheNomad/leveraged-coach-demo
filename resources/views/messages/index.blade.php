<x-app-layout>
    <x-slot name="header">Messages</x-slot>

    <div class="p-8 space-y-6 max-w-3xl">

        {{-- Header row --}}
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">Conversations</h3>
            @unless(config('app.demo_user_email'))
            <button onclick="document.getElementById('compose-modal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all"
                    style="background:#f5a623; color:#111111;"
                    onmouseover="this.style.background='#e09415';"
                    onmouseout="this.style.background='#f5a623';">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Message
            </button>
            @endunless
        </div>

        {{-- Conversation list --}}
        @if($conversations->isEmpty())
            <div class="rounded-xl p-10 text-center" style="background:#1a1a1a; border:1px solid #2a2a2a;">
                <p class="text-sm" style="color:#6b7280;">No conversations yet. Start one!</p>
            </div>
        @else
            <div class="flex flex-col gap-2">
                @foreach($conversations as $conv)
                    @php
                        $others  = $conv->participants->where('id', '!=', auth()->id());
                        $names   = $others->map(fn($u) => $u->full_name)->join(', ');
                        $unread  = $conv->unreadCountFor(auth()->user());
                        $latest  = $conv->latestMessage;
                    @endphp
                    <a href="{{ route('messages.show', $conv) }}"
                       class="flex items-center gap-4 px-4 py-3 rounded-xl transition-colors"
                       style="background:#1a1a1a; border:1px solid #2a2a2a;"
                       onmouseover="this.style.borderColor='#3a3a3a';"
                       onmouseout="this.style.borderColor='#2a2a2a';">

                        {{-- Avatar --}}
                        @php $first = $others->first(); @endphp
                        <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden flex items-center justify-center text-sm font-bold"
                             style="background:#f5a623; color:#111111;">
                            @if($first?->avatar_url)
                                <img src="{{ $first->avatar_url }}" class="w-10 h-10 object-cover">
                            @else
                                {{ strtoupper(substr($first?->full_name ?? '?', 0, 1)) }}
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate">{{ $names ?: 'Unknown' }}</p>
                            @if($latest)
                                <p class="text-xs truncate mt-0.5" style="color:#6b7280;">
                                    {{ $latest->sender_id === auth()->id() ? 'You: ' : '' }}{{ Str::limit($latest->body, 60) }}
                                </p>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <div class="flex-shrink-0 flex flex-col items-end gap-1">
                            @if($latest)
                                <span class="text-xs" style="color:#6b7280;">{{ $latest->created_at->diffForHumans(null, true) }}</span>
                            @endif
                            @if($unread > 0)
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold"
                                      style="background:#f5a623; color:#111111;">{{ $unread }}</span>
                            @endif
                        </div>

                    </a>
                @endforeach
            </div>
        @endif

    </div>

    @include('messages._compose', ['members' => \App\Models\User::where('id', '!=', auth()->id())->orderBy('full_name')->get(['id','full_name','avatar_url']), 'groups' => auth()->user()->role === 'admin' ? \App\Models\Group::orderBy('name')->get(['id','name']) : collect()])

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('messaging').unread = 0;
        });
    </script>

</x-app-layout>
