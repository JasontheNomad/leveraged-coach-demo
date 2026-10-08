<x-guest-layout>

    <p class="mb-5 text-sm text-center" style="color:#9ca3af;">
        Enter your email and we'll send you a password reset link.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Email</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   required autofocus
                   class="block w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-all"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('email')
                <p class="mt-1.5 text-xs" style="color:#f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full py-2.5 rounded-lg text-sm font-bold tracking-wide transition-all"
                style="background:#f5a623; color:#111111;"
                onmouseover="this.style.background='#e09415';"
                onmouseout="this.style.background='#f5a623';">
            SEND RESET LINK
        </button>

        <p class="text-center text-sm" style="color:#9ca3af;">
            <a href="{{ route('login') }}"
               style="color:#f5a623;"
               onmouseover="this.style.color='#e09415';"
               onmouseout="this.style.color='#f5a623';">← Back to login</a>
        </p>
    </form>

</x-guest-layout>
