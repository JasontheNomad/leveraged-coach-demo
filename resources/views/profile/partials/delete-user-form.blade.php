<section class="space-y-5" x-data="{ confirmingDeletion: false }">

    <div>
        <h2 class="text-lg font-semibold text-white">Delete Account</h2>
        <p class="mt-1 text-sm" style="color:#9ca3af;">
            Once your account is deleted, all of its resources and data will be permanently deleted.
            Before deleting your account, please download any data or information that you wish to retain.
        </p>
    </div>

    <button @click="confirmingDeletion = true"
            class="px-5 py-2.5 text-sm font-semibold rounded-lg transition-all"
            style="background:#dc2626; color:#ffffff;"
            onmouseover="this.style.background='#b91c1c';"
            onmouseout="this.style.background='#dc2626';">
        Delete Account
    </button>

    {{-- Confirmation Modal --}}
    <div x-show="confirmingDeletion"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center px-4"
         style="background:rgba(0,0,0,0.75);"
         @keydown.escape.window="confirmingDeletion = false">

        <div class="w-full max-w-md rounded-xl p-6 space-y-5"
             style="background:#1a1a1a; border:1px solid #2a2a2a;"
             @click.stop>

            <h2 class="text-lg font-semibold text-white">Are you sure you want to delete your account?</h2>
            <p class="text-sm" style="color:#9ca3af;">
                Once your account is deleted, all of its resources and data will be permanently deleted.
                Please enter your password to confirm.
            </p>

            <form method="post" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('delete')

                <div>
                    <label for="delete_password" class="block text-sm font-medium mb-1" style="color:#9ca3af;">Password</label>
                    <input id="delete_password" name="password" type="password"
                           placeholder="Enter your password"
                           class="w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-colors"
                           style="background:#111111; border:1px solid #2a2a2a;"
                           onfocus="this.style.borderColor='#f5a623';"
                           onblur="this.style.borderColor='#2a2a2a';">
                    @if ($errors->userDeletion->get('password'))
                        @foreach ($errors->userDeletion->get('password') as $message)
                            <p class="mt-1 text-xs" style="color:#ef4444;">{{ $message }}</p>
                        @endforeach
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button"
                            @click="confirmingDeletion = false"
                            class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-all"
                            style="background:#2a2a2a; color:#9ca3af;"
                            onmouseover="this.style.background='#3a3a3a';this.style.color='#ffffff';"
                            onmouseout="this.style.background='#2a2a2a';this.style.color='#9ca3af';">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-all"
                            style="background:#dc2626; color:#ffffff;"
                            onmouseover="this.style.background='#b91c1c';"
                            onmouseout="this.style.background='#dc2626';">
                        Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Auto-open modal if there are deletion errors --}}
    @if ($errors->userDeletion->isNotEmpty())
        <script>
            document.addEventListener('alpine:init', () => {
                // handled by x-data init
            });
        </script>
        <div x-init="confirmingDeletion = true" style="display:none;"></div>
    @endif

</section>
