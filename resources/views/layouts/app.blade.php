<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Leveraged Coach™</title>
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        [data-tooltip] { position: relative; }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: #1a1a1a;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 500;
            white-space: nowrap;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #2a2a2a;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
            z-index: 9999;
        }
        [data-tooltip]:hover::after { opacity: 1; }

        /* Announcement modal — rendered Markdown */
        .ann-prose { font-size: 1rem; line-height: 1.75; color: #9ca3af; }
        .ann-prose h1, .ann-prose h2, .ann-prose h3, .ann-prose h4 { color: #ffffff; font-weight: 600; margin: 1em 0 0.4em; line-height: 1.3; }
        .ann-prose h1 { font-size: 1.25rem; }
        .ann-prose h2 { font-size: 1.125rem; }
        .ann-prose h3 { font-size: 1rem; }
        .ann-prose p { margin: 0.6em 0; }
        .ann-prose strong { color: #ffffff; font-weight: 600; }
        .ann-prose em { color: #d1d5db; font-style: italic; }
        .ann-prose a { color: #f5a623; text-decoration: underline; text-underline-offset: 3px; }
        .ann-prose ul, .ann-prose ol { margin: 0.6em 0 0.6em 1.4em; }
        .ann-prose li { margin: 0.25em 0; }
        .ann-prose ul { list-style-type: disc; }
        .ann-prose ol { list-style-type: decimal; }
        .ann-prose code { background: #111111; border: 1px solid #2a2a2a; border-radius: 4px; padding: 1px 6px; font-size: 0.875em; color: #f5a623; }
        .ann-prose hr { border: none; border-top: 1px solid #2a2a2a; margin: 1em 0; }
        .ann-prose blockquote { border-left: 3px solid #f5a623; padding-left: 0.75em; color: #d1d5db; margin: 0.6em 0; }
    </style>
</head>
<body class="font-sans antialiased" style="background:#0f0f0f; color:#ffffff;">

    {{-- ── Top bar ── --}}
    <header class="fixed top-0 left-0 right-0 z-50 flex items-center gap-6 h-16 px-6"
            style="background:#111111; border-bottom:1px solid #2a2a2a;">
        <span class="text-base font-bold tracking-tight leading-none flex-shrink-0">
            <span style="color:#f5a623;">Leveraged</span><span style="color:#ffffff;"> Coach</span><sup style="color:#f5a623; font-size:0.55rem;">™</sup>
        </span>
        @isset($header)
            <span class="flex-shrink-0" style="color:#2a2a2a;">|</span>
            <div class="text-lg font-semibold text-white">{{ $header }}</div>
        @endisset
    </header>

    <div class="flex" style="padding-top:64px; min-height:100vh;">

        {{-- ── Sidebar — always 64px, icons only ── --}}
        <aside class="fixed z-40 flex flex-col overflow-hidden"
               style="top:64px; bottom:0; left:0; width:64px; background:#111111; border-right:1px solid #2a2a2a;">

            <nav class="flex-1 flex flex-col items-center py-3 gap-1 overflow-y-auto overflow-x-hidden">

                @php
                    $unreadMessages = auth()->check() ? auth()->user()->totalUnreadMessages() : 0;
                    $links = [
                        ['href' => route('dashboard'),           'label' => 'Dashboard',     'active' => request()->routeIs('dashboard'),         'badge' => 0,
                         'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['href' => route('courses.index'),       'label' => 'Stacks',       'active' => request()->routeIs('courses.*'),         'badge' => 0,
                         'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['href' => route('live-sessions.index'), 'label' => 'Live Sessions', 'active' => request()->routeIs('live-sessions.*'),   'badge' => 0,
                         'icon' => 'M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                        ['href' => route('recordings.index'),    'label' => 'Recordings',    'active' => request()->routeIs('recordings.*'),      'badge' => 0,
                         'icon' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z'],
                        ['href' => route('messages.index'),      'label' => 'Messages',      'active' => request()->routeIs('messages.*'),        'badge' => $unreadMessages,
                         'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                        ['href' => route('members.index'),       'label' => 'Members',       'active' => request()->routeIs('members.index'),     'badge' => 0,
                         'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['href' => route('profile.edit'),        'label' => 'Profile',       'active' => request()->routeIs('profile.*'),         'badge' => 0,
                         'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ];
                @endphp

                @foreach($links as $link)
                    <a href="{{ $link['href'] }}"
                       data-tooltip="{{ $link['label'] }}"
                       class="relative w-10 h-10 flex items-center justify-center rounded-lg transition-colors duration-150"
                       style="{{ $link['active'] ? 'background:#f5a623; color:#111111;' : 'color:#9ca3af;' }}"
                       @if(!$link['active'])
                           onmouseover="this.style.background='#1a1a1a';this.style.color='#ffffff';"
                           onmouseout="this.style.background='transparent';this.style.color='#9ca3af';"
                       @endif>
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                        </svg>
                        @if($link['label'] === 'Messages')
                            <span x-data
                                  x-show="$store.messaging.unread > 0"
                                  x-text="$store.messaging.unread > 99 ? '99+' : $store.messaging.unread"
                                  class="absolute top-1 right-1 flex items-center justify-center rounded-full text-white font-bold"
                                  style="background:#ef4444; min-width:14px; height:14px; font-size:0.55rem; padding:0 3px; line-height:1;">
                            </span>
                        @elseif($link['badge'] > 0)
                            <span class="absolute top-1 right-1 flex items-center justify-center rounded-full text-white font-bold"
                                  style="background:#ef4444; min-width:14px; height:14px; font-size:0.55rem; padding:0 3px; line-height:1;">
                                {{ $link['badge'] > 99 ? '99+' : $link['badge'] }}
                            </span>
                        @endif
                    </a>
                @endforeach

                @auth
                @if(auth()->user()->role === 'admin')
                    <a href="/admin"
                       target="_blank"
                       rel="noopener noreferrer"
                       data-tooltip="Admin Panel"
                       class="w-10 h-10 flex items-center justify-center rounded-lg transition-colors duration-150"
                       style="color:#9ca3af;"
                       onmouseover="this.style.background='#1a1a1a';this.style.color='#ffffff';"
                       onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>
                @endif
                @endauth

            </nav>

            @auth
            <div class="flex-shrink-0 flex flex-col items-center gap-2 pb-4" style="border-top:1px solid #2a2a2a; padding-top:12px;">

                {{-- Avatar → /profile --}}
                <a href="{{ route('profile.edit') }}"
                   data-tooltip="Profile"
                   class="w-10 h-10 rounded-full flex-shrink-0 transition-opacity hover:opacity-70 overflow-hidden flex items-center justify-center">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}"
                             alt="Avatar"
                             class="w-10 h-10 rounded-full object-cover">
                    @else
                        <span class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                              style="background:#f5a623; color:#111111;">
                            {{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}
                        </span>
                    @endif
                </a>

                {{-- Sign out --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            data-tooltip="Sign out"
                            class="w-10 h-10 flex items-center justify-center rounded-lg transition-colors duration-150"
                            style="color:#9ca3af;"
                            onmouseover="this.style.background='#1a1a1a';this.style.color='#ffffff';"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>

            </div>
            @endauth
        </aside>

        {{-- ── Main content ── --}}
        <div class="flex flex-col flex-1 min-w-0" style="margin-left:64px;">
            <main class="flex-1 overflow-auto">
                {{ $slot }}
            </main>
        </div>

    </div>

@auth
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('messaging', {
            unread: {{ auth()->user()->totalUnreadMessages() }},
        });
    });
</script>
<div x-data x-init="
    window.Echo.private('App.Models.User.{{ auth()->id() }}')
        .listen('NewMessageReceived', (data) => {
            if (window.activeConversationId && window.activeConversationId == data.conversation_id) return;
            $store.messaging.unread++;
        });
"></div>
@endauth

@livewireScripts
@stack('scripts')
</body>
</html>
