<x-app-layout>
    <x-slot name="header">Members</x-slot>

    <div class="p-8 space-y-6 max-w-3xl" x-data="{ search: '' }">

        {{-- Search --}}
        <input type="text"
               x-model="search"
               placeholder="Search by name…"
               class="w-full rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none"
               style="background:#1a1a1a; border:1px solid #2a2a2a;">

        {{-- Member list --}}
        <div class="rounded-xl overflow-hidden" style="background:#1a1a1a; border:1px solid #2a2a2a;">
            @foreach($members as $member)
                <div x-show="search === '' || '{{ strtolower(addslashes($member->full_name)) }}'.includes(search.toLowerCase())"
                     class="flex items-center gap-3 px-4 py-3"
                     @if(!$loop->last) style="border-bottom:1px solid #2a2a2a;" @endif>

                    {{-- Avatar --}}
                    @if($member->avatar_url)
                        <img src="{{ $member->avatar_url }}" class="w-10 h-auto block rounded-full">
                    @else
                        <span class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                              style="background:#f5a623; color:#111111;">{{ strtoupper(substr($member->full_name, 0, 1)) }}</span>
                    @endif

                    {{-- Name --}}
                    <span class="flex-1 text-sm font-medium text-white truncate">{{ $member->full_name }}</span>

                    {{-- Role badge --}}
                    @if($member->role === 'admin')
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                              style="background:#f5a623; color:#111111;">admin</span>
                    @else
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                              style="color:#9ca3af; border:1px solid #2a2a2a;">member</span>
                    @endif

                    {{-- Message button --}}
                    @if($member->id !== auth()->id() && ! config('app.demo_user_email'))
                        <a href="{{ route('messages.index', ['to' => $member->id]) }}"
                           class="text-xs font-semibold px-3 py-1 rounded-full transition-all"
                           style="background:#f5a623; color:#111111;"
                           onmouseover="this.style.background='#e09415';"
                           onmouseout="this.style.background='#f5a623';">Message</a>
                    @endif
                </div>
            @endforeach
        </div>

    </div>
</x-app-layout>
