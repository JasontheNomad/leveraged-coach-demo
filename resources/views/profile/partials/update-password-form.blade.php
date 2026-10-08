<section>
    <h2 class="text-lg font-semibold text-white">Update Password</h2>
    <p class="mt-1 text-sm" style="color:#9ca3af;">Ensure your account is using a long, random password to stay secure.</p>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        {{-- Current Password --}}
        <div>
            <label for="update_password_current_password" class="block text-sm font-medium mb-1" style="color:#9ca3af;">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password"
                   autocomplete="current-password"
                   class="w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-colors"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @if ($errors->updatePassword->get('current_password'))
                @foreach ($errors->updatePassword->get('current_password') as $message)
                    <p class="mt-1 text-xs" style="color:#ef4444;">{{ $message }}</p>
                @endforeach
            @endif
        </div>

        {{-- New Password --}}
        <div>
            <label for="update_password_password" class="block text-sm font-medium mb-1" style="color:#9ca3af;">New Password</label>
            <input id="update_password_password" name="password" type="password"
                   autocomplete="new-password"
                   class="w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-colors"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @if ($errors->updatePassword->get('password'))
                @foreach ($errors->updatePassword->get('password') as $message)
                    <p class="mt-1 text-xs" style="color:#ef4444;">{{ $message }}</p>
                @endforeach
            @endif
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium mb-1" style="color:#9ca3af;">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                   autocomplete="new-password"
                   class="w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-colors"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @if ($errors->updatePassword->get('password_confirmation'))
                @foreach ($errors->updatePassword->get('password_confirmation') as $message)
                    <p class="mt-1 text-xs" style="color:#ef4444;">{{ $message }}</p>
                @endforeach
            @endif
        </div>

        {{-- Save --}}
        <div class="flex items-center gap-4 pt-1">
            <button type="submit"
                    class="px-5 py-2.5 text-sm font-semibold rounded-lg transition-all"
                    style="background:#f5a623; color:#111111;"
                    onmouseover="this.style.background='#e09415';"
                    onmouseout="this.style.background='#f5a623';">
                Update Password
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }"
                   x-show="show"
                   x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm"
                   style="color:#16a34a;">Saved.</p>
            @endif
        </div>
    </form>
</section>
