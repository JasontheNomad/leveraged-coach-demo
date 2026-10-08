<x-app-layout>
    <x-slot name="header">Messages</x-slot>

    {{-- Full-height two-column layout --}}
    <div class="flex" style="height:calc(100vh - 64px);">

        {{-- ── Left: conversation list ── --}}
        <div class="flex flex-col flex-shrink-0 overflow-y-auto overflow-x-hidden" style="width:300px; background:#111111; border-right:1px solid #2a2a2a;">

            <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid #2a2a2a;">
                <span class="text-sm font-semibold text-white">Messages</span>
                @unless(config('app.demo_user_email'))
                <button onclick="document.getElementById('compose-modal').classList.remove('hidden')"
                        data-tooltip="New message"
                        class="w-7 h-7 flex items-center justify-center rounded-lg transition-colors"
                        style="color:#9ca3af;"
                        onmouseover="this.style.color='#ffffff'; this.style.background='#1a1a1a';"
                        onmouseout="this.style.color='#9ca3af'; this.style.background='transparent';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                @endunless
            </div>

            @forelse($conversations as $conv)
                @php
                    $others  = $conv->participants->where('id', '!=', auth()->id());
                    $names   = $others->map(fn($u) => $u->full_name)->join(', ');
                    $unread  = $conv->unreadCountFor(auth()->user());
                    $latest  = $conv->latestMessage;
                    $active  = $conv->id === $conversation->id;
                    $first   = $others->first();
                @endphp
                <a href="{{ route('messages.show', $conv) }}"
                   class="flex items-center gap-3 px-4 py-3 transition-colors"
                   style="{{ $active ? 'background:#1a1a1a; border-left:2px solid #f5a623;' : 'border-left:2px solid transparent;' }}"
                   @if(!$active)
                       onmouseover="this.style.background='#161616';"
                       onmouseout="this.style.background='transparent';"
                   @endif>

                    <div class="flex-shrink-0 w-9 h-9 rounded-full overflow-hidden flex items-center justify-center text-xs font-bold"
                         style="background:#f5a623; color:#111111;">
                        @if($first?->avatar_url)
                            <img src="{{ $first->avatar_url }}" class="w-9 h-9 object-cover">
                        @else
                            {{ strtoupper(substr($first?->full_name ?? '?', 0, 1)) }}
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold truncate {{ $active ? 'text-white' : '' }}" style="{{ $active ? '' : 'color:#d1d5db;' }}">
                            {{ $names ?: 'Unknown' }}
                        </p>
                        @if($latest)
                            <p class="text-xs truncate mt-0.5" style="color:#6b7280;">
                                {{ Str::limit($latest->body, 35) }}
                            </p>
                        @endif
                    </div>

                    @if($unread > 0)
                        <span class="flex-shrink-0 w-4 h-4 rounded-full text-xs font-bold flex items-center justify-center"
                              style="background:#f5a623; color:#111111; font-size:0.6rem;">{{ $unread }}</span>
                    @endif

                </a>
            @empty
                <p class="px-4 py-6 text-xs text-center" style="color:#6b7280;">No conversations yet.</p>
            @endforelse
        </div>

        {{-- ── Right: active thread ── --}}
        @php
            $others      = $conversation->participants->where('id', '!=', auth()->id());
            $threadTitle = $others->map(fn($u) => $u->full_name)->join(', ');
        @endphp

        <div class="flex flex-col flex-1 min-w-0 relative overflow-hidden"
             x-data="{
                 newMessages: [],
                 body: '',
                 sending: false,
                 pendingFiles: [],
                 dragging: false,
                 dragCounter: 0,
                 lightboxUrl: null,

                 addFiles(fileList) {
                     this.pendingFiles = [...this.pendingFiles, ...fileList].slice(0, 5);
                     this.$nextTick(() => { this.$el.querySelector('textarea').focus(); });
                 },

                 removeFile(index) {
                     this.pendingFiles.splice(index, 1);
                 },

                 formatSize(bytes) {
                     if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                     return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                 },

                 openLightbox(url) {
                     this.lightboxUrl = url;
                 },

                 closeLightbox() {
                     this.lightboxUrl = null;
                 },

                 async send() {
                     if ((!this.body.trim() && !this.pendingFiles.length) || this.sending) return;
                     this.sending = true;
                     const text = this.body;
                     const files = [...this.pendingFiles];
                     this.body = '';
                     this.pendingFiles = [];
                     this.$nextTick(() => { this.$el.querySelector('textarea').style.height = 'auto'; });

                     this.newMessages.push({
                         id: 'pending-' + Date.now(),
                         body: text,
                         sender_id: {{ auth()->id() }},
                         sender: @js(auth()->user()->full_name),
                         avatar: @js(auth()->user()->avatar_url),
                         mine: true,
                         created_at: new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }),
                         attachments: files.map(f => ({
                             url: f.type.startsWith('image/') ? URL.createObjectURL(f) : null,
                             filename: f.name,
                             mime_type: f.type,
                             size: f.size,
                             is_image: f.type.startsWith('image/'),
                         })),
                     });
                     this.$nextTick(() => { this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });

                     try {
                         const formData = new FormData();
                         if (text) formData.append('body', text);
                         files.forEach((file, i) => formData.append(`files[${i}]`, file));

                         await fetch('{{ route('messages.send', $conversation) }}', {
                             method: 'POST',
                             headers: {
                                 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                 'Accept': 'application/json',
                                 'X-Socket-ID': window.Echo.socketId(),
                             },
                             body: formData
                         });
                     } catch(e) {}
                     this.sending = false;
                 },

                 init() {
                     this.$nextTick(() => { this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });

                     window.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.closeLightbox(); });

                     window.Echo.private('conversation.{{ $conversation->id }}')
                         .listen('MessageSent', (data) => {
                             this.newMessages.push({ ...data, mine: data.sender_id === {{ auth()->id() }} });
                             this.$nextTick(() => { this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight; });
                             fetch('{{ route('messages.read', $conversation) }}', {
                                 method: 'POST',
                                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                             });
                         })
                         .listen('ReactionToggled', (data) => {
                             // Re-emit to a window event bus; each reactionBar bubble
                             // self-filters by message_id (no central bubble registry).
                             window.dispatchEvent(new CustomEvent('reaction-toggled', { detail: data }));
                         });
                 }
             }"
             x-init="init()"
             @dragenter.prevent="dragCounter++; dragging = true"
             @dragleave.prevent="dragCounter--; if (dragCounter === 0) dragging = false"
             @dragover.prevent
             @drop.prevent="dragCounter = 0; dragging = false; addFiles($event.dataTransfer.files)">

            {{-- Drop zone overlay --}}
            <div x-show="dragging"
                 class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none"
                 style="background:rgba(245,166,35,0.06); border:2px dashed #f5a623;">
                <div class="flex flex-col items-center gap-2" style="color:#f5a623;">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span class="text-sm font-semibold">Drop files to attach</span>
                </div>
            </div>

            {{-- Lightbox --}}
            <div x-show="lightboxUrl"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center"
                 style="background:rgba(0,0,0,0.92);"
                 @click.self="closeLightbox()">
                <button @click="closeLightbox()"
                        class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full"
                        style="background:#1a1a1a; color:#ffffff; border:1px solid #2a2a2a;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <img :src="lightboxUrl" class="object-contain rounded-lg" style="max-width:90vw; max-height:90vh;">
            </div>

            {{-- Thread header --}}
            <div class="flex-shrink-0 flex items-center px-6 py-4" style="border-bottom:1px solid #2a2a2a;">
                <p class="font-semibold text-white text-sm">{{ $threadTitle ?: 'Conversation' }}</p>
            </div>

            {{-- Messages --}}
            <div x-ref="thread" class="flex-1 overflow-y-auto px-6 py-4 flex flex-col gap-3">

                {{-- Server-rendered messages --}}
                @foreach($conversation->messages as $msg)
                    @php
                        $mine    = $msg->sender_id === auth()->id();
                        $grouped = $msg->reactions->groupBy('emoji');
                        $reactSeed = [];
                        foreach (['👍', '❤️', '😂', '🎉'] as $e) {
                            $g = $grouped->get($e);
                            if ($g && $g->count() > 0) {
                                $reactSeed[] = [
                                    'emoji' => $e,
                                    'count' => $g->count(),
                                    'mine'  => $g->contains('user_id', auth()->id()),
                                ];
                            }
                        }
                    @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }} gap-2">
                        @if(!$mine)
                            <div class="flex-shrink-0 w-7 h-7 rounded-full overflow-hidden flex items-center justify-center text-xs font-bold self-end"
                                 style="background:#f5a623; color:#111111;">
                                @if($msg->sender->avatar_url)
                                    <img src="{{ $msg->sender->avatar_url }}" class="w-7 h-7 object-cover">
                                @else
                                    {{ strtoupper(substr($msg->sender->full_name, 0, 1)) }}
                                @endif
                            </div>
                        @endif
                        <div class="max-w-xs lg:max-w-md relative"
                             x-data="reactionBar('/messages/{{ $msg->id }}/react', @js($reactSeed))"
                             @mouseenter="hover = true" @mouseleave="hover = false">
                            @if(!$mine)
                                <p class="text-xs mb-1" style="color:#6b7280;">{{ $msg->sender->full_name }}</p>
                            @endif

                            {{-- Hover reaction picker --}}
                            @unless(config('app.demo_user_email'))
                            <div x-show="hover"
                                 class="absolute z-10 flex items-center gap-1 px-1.5 py-1 rounded-full"
                                 style="top:-1rem; {{ $mine ? 'right:0;' : 'left:0;' }} background:#1a1a1a; border:1px solid #2a2a2a;">
                                <template x-for="e in emojis" :key="e">
                                    <button type="button" @click="react(e)"
                                            class="w-6 h-6 flex items-center justify-center rounded-full text-sm"
                                            style="transition:transform .1s;"
                                            onmouseover="this.style.transform='scale(1.25)'; this.style.background='#2a2a2a';"
                                            onmouseout="this.style.transform='scale(1)'; this.style.background='transparent';"
                                            x-text="e"></button>
                                </template>
                            </div>
                            @endunless
                            @if($msg->body)
                                <div class="px-4 py-2.5 rounded-2xl text-sm leading-snug break-words whitespace-pre-line"
                                     style="{{ $mine ? 'background:#f5a623; color:#111111; border-bottom-right-radius:4px;' : 'background:#1a1a1a; color:#ffffff; border:1px solid #2a2a2a; border-bottom-left-radius:4px;' }}">{!! \App\Support\RichText::linkify(trim($msg->body)) !!}</div>
                            @endif
                            @if($msg->attachments->isNotEmpty())
                                <div class="mt-1 flex flex-wrap gap-2">
                                    @foreach($msg->attachments as $att)
                                        @if($att->isImage())
                                            <button type="button" @click="openLightbox('{{ $att->url() }}')">
                                                <img src="{{ $att->url() }}" class="rounded-xl object-cover"
                                                     style="max-width:12rem; max-height:12rem;">
                                            </button>
                                        @else
                                            <a href="{{ $att->url() }}" target="_blank" download="{{ $att->filename }}"
                                               class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs"
                                               style="{{ $mine ? 'background:rgba(0,0,0,0.15); color:#111111;' : 'background:#111111; border:1px solid #2a2a2a; color:#d1d5db;' }}">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                                <span class="max-w-32 truncate">{{ $att->filename }}</span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            {{-- Reaction pills --}}
                            <div x-show="reactions.length" class="mt-1 flex flex-wrap gap-1 {{ $mine ? 'justify-end' : '' }}">
                                <template x-for="r in reactions" :key="r.emoji">
                                    <button type="button" @click="react(r.emoji)"
                                            class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs"
                                            :style="r.mine
                                                ? 'background:rgba(245,166,35,0.15); border:1px solid #f5a623; color:#f5a623;'
                                                : 'background:#1a1a1a; border:1px solid #2a2a2a; color:#d1d5db;'">
                                        <span x-text="r.emoji"></span>
                                        <span x-text="r.count"></span>
                                    </button>
                                </template>
                            </div>
                            <p class="text-xs mt-1 {{ $mine ? 'text-right' : '' }}" style="color:#4b5563;">
                                {{ $msg->created_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @endforeach

                {{-- Alpine-rendered new messages (via Echo) --}}
                <template x-for="msg in newMessages" :key="msg.id">
                    <div :class="msg.mine ? 'flex justify-end gap-2' : 'flex justify-start gap-2'">
                        <template x-if="!msg.mine">
                            <div class="flex-shrink-0 w-7 h-7 rounded-full overflow-hidden flex items-center justify-center text-xs font-bold self-end"
                                 style="background:#f5a623; color:#111111;">
                                <template x-if="msg.avatar">
                                    <img :src="msg.avatar" class="w-7 h-7 object-cover">
                                </template>
                                <template x-if="!msg.avatar">
                                    <span x-text="msg.sender.charAt(0).toUpperCase()"></span>
                                </template>
                            </div>
                        </template>
                        <div class="max-w-xs lg:max-w-md relative"
                             x-data="reactionBar('/messages/' + msg.id + '/react', [])"
                             @mouseenter="hover = true" @mouseleave="hover = false">
                            <template x-if="!msg.mine">
                                <p class="text-xs mb-1" style="color:#6b7280;" x-text="msg.sender"></p>
                            </template>

                            {{-- Hover reaction picker --}}
                            @unless(config('app.demo_user_email'))
                            <div x-show="hover"
                                 class="absolute z-10 flex items-center gap-1 px-1.5 py-1 rounded-full"
                                 :style="'top:-1rem; background:#1a1a1a; border:1px solid #2a2a2a;' + (msg.mine ? 'right:0;' : 'left:0;')">
                                <template x-for="e in emojis" :key="e">
                                    <button type="button" @click="react(e)"
                                            class="w-6 h-6 flex items-center justify-center rounded-full text-sm"
                                            style="transition:transform .1s;"
                                            onmouseover="this.style.transform='scale(1.25)'; this.style.background='#2a2a2a';"
                                            onmouseout="this.style.transform='scale(1)'; this.style.background='transparent';"
                                            x-text="e"></button>
                                </template>
                            </div>
                            @endunless
                            <template x-if="msg.body">
                                <div class="px-4 py-2.5 rounded-2xl text-sm leading-snug break-words whitespace-pre-line"
                                     :style="msg.mine
                                         ? 'background:#f5a623; color:#111111; border-bottom-right-radius:4px;'
                                         : 'background:#1a1a1a; color:#ffffff; border:1px solid #2a2a2a; border-bottom-left-radius:4px;'"
                                     x-html="linkify(msg.body)">
                                </div>
                            </template>
                            <template x-if="msg.attachments && msg.attachments.length">
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <template x-for="att in msg.attachments" :key="att.url || att.filename">
                                        <template x-if="att.is_image && att.url">
                                            <button type="button" @click="openLightbox(att.url)">
                                                <img :src="att.url" class="rounded-xl object-cover"
                                                     style="max-width:12rem; max-height:12rem;">
                                            </button>
                                        </template>
                                        <template x-if="!att.is_image">
                                            <a :href="att.url || '#'" target="_blank" :download="att.filename"
                                               class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs"
                                               :style="msg.mine ? 'background:rgba(0,0,0,0.15); color:#111111;' : 'background:#111111; border:1px solid #2a2a2a; color:#d1d5db;'">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                                <span x-text="att.filename" class="max-w-32 truncate"></span>
                                            </a>
                                        </template>
                                    </template>
                                </div>
                            </template>
                            {{-- Reaction pills --}}
                            <div x-show="reactions.length" class="mt-1 flex flex-wrap gap-1" :class="msg.mine ? 'justify-end' : ''">
                                <template x-for="r in reactions" :key="r.emoji">
                                    <button type="button" @click="react(r.emoji)"
                                            class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs"
                                            :style="r.mine
                                                ? 'background:rgba(245,166,35,0.15); border:1px solid #f5a623; color:#f5a623;'
                                                : 'background:#1a1a1a; border:1px solid #2a2a2a; color:#d1d5db;'">
                                        <span x-text="r.emoji"></span>
                                        <span x-text="r.count"></span>
                                    </button>
                                </template>
                            </div>
                            <p class="text-xs mt-1" :class="msg.mine ? 'text-right' : ''" style="color:#4b5563;" x-text="msg.created_at"></p>
                        </div>
                    </div>
                </template>

            </div>

            @if(config('app.demo_user_email'))
                <div class="flex-shrink-0 px-6 py-4 text-center text-sm" style="border-top:1px solid #2a2a2a; color:#6b7280;">
                    Sending messages is disabled in this demo.
                </div>
            @else
            {{-- Send input --}}
            <div class="flex-shrink-0 px-6 py-4" style="border-top:1px solid #2a2a2a;">

                {{-- File preview strip --}}
                <div x-show="pendingFiles.length > 0" class="flex flex-wrap gap-2 mb-3">
                    <template x-for="(file, index) in pendingFiles" :key="index">
                        <div class="flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-lg text-xs"
                             style="background:#1a1a1a; border:1px solid #2a2a2a; color:#d1d5db;">
                            <span x-text="file.name" class="max-w-32 truncate"></span>
                            <span x-text="formatSize(file.size)" style="color:#6b7280;" class="flex-shrink-0"></span>
                            <button @click="removeFile(index)" type="button"
                                    class="flex-shrink-0 ml-0.5 w-4 h-4 flex items-center justify-center rounded"
                                    style="color:#6b7280;"
                                    onmouseover="this.style.color='#ffffff';"
                                    onmouseout="this.style.color='#6b7280';">×</button>
                        </div>
                    </template>
                </div>

                <div class="flex items-end gap-3">
                    {{-- Hidden file input --}}
                    <input type="file"
                           x-ref="fileInput"
                           multiple
                           accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain"
                           class="hidden"
                           @change="addFiles($event.target.files); $event.target.value = '';">

                    {{-- Attach button --}}
                    <button @click="$refs.fileInput.click()" type="button"
                            class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl transition-colors"
                            style="color:#6b7280; background:#1a1a1a; border:1px solid #2a2a2a;"
                            onmouseover="this.style.color='#ffffff'; this.style.borderColor='#3a3a3a';"
                            onmouseout="this.style.color='#6b7280'; this.style.borderColor='#2a2a2a';">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </button>

                    <textarea x-model="body"
                              @keydown.enter="if(!$event.shiftKey){ $event.preventDefault(); send() }"
                              placeholder="Write a message… (Enter to send, Shift+Enter for new line)"
                              rows="1"
                              class="flex-1 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none resize-none"
                              style="background:#1a1a1a; border:1px solid #2a2a2a; max-height:120px;"
                              @input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"></textarea>

                    <button @click="send()"
                            :disabled="sending || (!body.trim() && !pendingFiles.length)"
                            class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl transition-all"
                            :style="(sending || (!body.trim() && !pendingFiles.length))
                                ? 'background:#f5a623; color:#111111; opacity:0.4; cursor:not-allowed;'
                                : 'background:#f5a623; color:#111111;'"
                            onmouseover="if(!this.disabled) this.style.background='#e09415';"
                            onmouseout="this.style.background='#f5a623';">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </div>

            @endif
        </div>
    </div>

    @include('messages._compose', ['members' => $members, 'groups' => $groups])

    <script>
        window.linkify = function (text) {
            const esc = (text || '').trim()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            return esc.replace(/(https?:\/\/[^\s<]+[^\s<.,:;!?)'])|([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/gi, function (match, url, email) {
                if (url) {
                    return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" class="underline">' + url + '</a>';
                }
                return '<a href="mailto:' + email + '" class="underline">' + email + '</a>';
            });
        };

        window.activeConversationId = {{ $conversation->id }};
        document.addEventListener('alpine:init', () => {
            // Reset badge when viewing a conversation.
            // Guard: the messaging store is defined in the layout's alpine:init,
            // which renders after $slot — so it may not exist when this fires.
            // Without the guard the throw aborts reactionBar registration below.
            if (Alpine.store('messaging')) { Alpine.store('messaging').unread = 0; }

            Alpine.data('reactionBar', (postUrl, seed) => ({
                hover: false,
                reactions: seed || [],
                emojis: ['👍', '❤️', '😂', '🎉'],
                messageId: parseInt((postUrl.match(/\/messages\/(\d+)\/react/) || [])[1], 10),
                init() {
                    // Single top-level Echo listener re-emits ReactionToggled as a
                    // window event; this bubble applies it only if message_id matches.
                    window.addEventListener('reaction-toggled', (e) => {
                        if (Number(e.detail.message_id) !== this.messageId) return;
                        this.applyRemote(e.detail.emoji, e.detail.action);
                    });
                },
                applyRemote(emoji, action) {
                    const cur = this.find(emoji);
                    if (action === 'added') {
                        if (cur) {
                            cur.count += 1;
                        } else {
                            // Remote reaction is never "mine" (broadcast used toOthers()).
                            this.reactions.push({ emoji, count: 1, mine: false });
                        }
                    } else { // removed
                        if (cur) {
                            cur.count -= 1;
                            if (cur.count <= 0) {
                                this.reactions = this.reactions.filter(r => r.emoji !== emoji);
                            }
                        }
                    }
                },
                find(emoji) {
                    return this.reactions.find(r => r.emoji === emoji);
                },
                async react(emoji) {
                    @if(config('app.demo_user_email')) return; @endif
                    // Optimistic local toggle
                    const cur = this.find(emoji);
                    if (cur && cur.mine) {
                        cur.count -= 1;
                        cur.mine = false;
                        if (cur.count <= 0) {
                            this.reactions = this.reactions.filter(r => r.emoji !== emoji);
                        }
                    } else if (cur) {
                        cur.count += 1;
                        cur.mine = true;
                    } else {
                        this.reactions.push({ emoji, count: 1, mine: true });
                    }
                    try {
                        await fetch(postUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Socket-ID': window.Echo.socketId(),
                            },
                            body: JSON.stringify({ emoji }),
                        });
                    } catch (e) {}
                },
            }));
        });
    </script>

</x-app-layout>
