<x-guest-layout>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Email</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   class="block w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-all"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('email')
                <p class="mt-1.5 text-xs" style="color:#f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Password</label>
            <input id="password" name="password" type="password"
                   required autocomplete="current-password"
                   class="block w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-all"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('password')
                <p class="mt-1.5 text-xs" style="color:#f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center justify-between mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input id="remember_me" name="remember" type="checkbox"
                       class="w-4 h-4 rounded"
                       style="appearance:auto; accent-color:#f5a623;">
                <span class="text-sm" style="color:#9ca3af;">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm transition-colors"
                   style="color:#f5a623;"
                   onmouseover="this.style.color='#e09415';"
                   onmouseout="this.style.color='#f5a623';">
                    Forgot your password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <div class="pt-2">
        <button type="submit"
                class="w-full py-2.5 rounded-lg text-sm font-bold tracking-wide transition-all"
                style="background:#f5a623; color:#111111;"
                onmouseover="this.style.background='#e09415';"
                onmouseout="this.style.background='#f5a623';">
            LOG IN
        </button>
        </div>
    </form>

</x-guest-layout>
