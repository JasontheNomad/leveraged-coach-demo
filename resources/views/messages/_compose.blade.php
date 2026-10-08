{{-- Compose modal — included on both index and show pages --}}
<div id="compose-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center"
     style="background:rgba(0,0,0,0.7);"
     @if(!empty($messageTarget))
     x-init="$nextTick(() => document.getElementById('compose-modal').classList.remove('hidden'))"
     @endif
     x-data="{
         query: '',
         results: [],
         selected: [
             @if(!empty($messageTarget))
             { id: {{ $messageTarget->id }}, name: @js($messageTarget->full_name), avatar_url: @js($messageTarget->avatar_url) },
             @endif
         ],
         groupId: '',
         groups: {{ $groups->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->values()->toJson() }},
         async search() {
             if (this.query.length < 1) { this.results = []; return; }
             const res = await fetch('/members/search?q=' + encodeURIComponent(this.query), {
                 headers: { 'X-Requested-With': 'XMLHttpRequest' }
             });
             this.results = await res.json();
         },
         add(user) {
             if (!this.selected.find(u => u.id === user.id)) this.selected.push(user);
             this.query = ''; this.results = [];
         },
         remove(id) { this.selected = this.selected.filter(u => u.id !== id); },
         async addGroup(gid) {
             if (!gid) return;
             const res = await fetch('/groups/members?group_id=' + gid, {
                 headers: { 'X-Requested-With': 'XMLHttpRequest' }
             });
             const members = await res.json();
             members.forEach(m => { if (!this.selected.find(u => u.id === m.id)) this.selected.push(m); });
             this.groupId = '';
         }
     }">

    <div class="w-full max-w-lg mx-4 rounded-xl overflow-hidden" style="background:#1a1a1a; border:1px solid #2a2a2a;">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid #2a2a2a;">
            <h3 class="text-base font-semibold text-white">New Message</h3>
            <button onclick="document.getElementById('compose-modal').classList.add('hidden')"
                    class="text-gray-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('messages.store') }}" class="p-5 flex flex-col gap-4">
            @csrf

            {{-- Admin: group selector --}}
            @if(auth()->user()->role === 'admin')
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color:#9ca3af;">Add entire group</label>
                    <select x-model="groupId" @change="addGroup(groupId)"
                            class="w-full rounded-lg px-3 py-2 text-sm text-white focus:outline-none"
                            style="background:#111111; border:1px solid #2a2a2a;">
                        <option value="">Select a group…</option>
                        <template x-for="g in groups" :key="g.id">
                            <option :value="g.id" x-text="g.name"></option>
                        </template>
                    </select>
                </div>
            @endif

            {{-- Recipient search --}}
            <div class="relative">
                <label class="block text-xs font-medium mb-1.5" style="color:#9ca3af;">To</label>

                {{-- Selected chips --}}
                <div class="flex flex-wrap gap-1.5 mb-2" x-show="selected.length > 0">
                    <template x-for="u in selected" :key="u.id">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                              style="background:#2a2a2a; color:#ffffff;">
                            <span x-text="u.name"></span>
                            <button type="button" @click="remove(u.id)" class="hover:text-red-400 transition-colors">×</button>
                            <input type="hidden" :name="'participant_ids[]'" :value="u.id">
                        </span>
                    </template>
                </div>

                <input type="text"
                       x-model="query"
                       @input.debounce.300ms="search()"
                       placeholder="Search by name…"
                       autocomplete="off"
                       class="w-full rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none"
                       style="background:#111111; border:1px solid #2a2a2a;">

                {{-- Dropdown results --}}
                <div x-show="results.length > 0"
                     class="absolute z-10 w-full mt-1 rounded-lg overflow-hidden"
                     style="background:#1a1a1a; border:1px solid #2a2a2a;">
                    <template x-for="u in results" :key="u.id">
                        <button type="button"
                                @click="add(u)"
                                class="w-full flex items-center gap-2 text-left px-3 py-2 text-sm text-white transition-colors"
                                style="hover:background:#2a2a2a;"
                                onmouseover="this.style.background='#2a2a2a';"
                                onmouseout="this.style.background='transparent';">
                            <template x-if="u.avatar_url">
                                <img :src="u.avatar_url" class="w-7 h-auto block rounded-full">
                            </template>
                            <template x-if="!u.avatar_url">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold"
                                      style="background:#f5a623; color:#111111;"
                                      x-text="u.name.charAt(0).toUpperCase()"></span>
                            </template>
                            <span x-text="u.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Message body --}}
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color:#9ca3af;">Message</label>
                <textarea name="body"
                          rows="4"
                          placeholder="Write your message…"
                          required
                          class="w-full rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none resize-none"
                          style="background:#111111; border:1px solid #2a2a2a;"></textarea>
            </div>

            <button type="submit"
                    class="self-end px-5 py-2 text-sm font-semibold rounded-lg transition-all"
                    style="background:#f5a623; color:#111111;"
                    onmouseover="this.style.background='#e09415';"
                    onmouseout="this.style.background='#f5a623';">
                Send
            </button>
        </form>
    </div>
</div>
