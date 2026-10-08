<x-guest-layout>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Email</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="username"
                   class="block w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-all"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('email')
                <p class="mt-1.5 text-xs" style="color:#f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- New Password --}}
        <div>
            <label for="password" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">New Password</label>
            <input id="password" name="password" type="password"
                   required autocomplete="new-password"
                   class="block w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-all"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('password')
                <p class="mt-1.5 text-xs" style="color:#f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   required autocomplete="new-password"
                   class="block w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-all"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('password_confirmation')
                <p class="mt-1.5 text-xs" style="color:#f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full py-2.5 rounded-lg text-sm font-bold tracking-wide transition-all"
                style="background:#f5a623; color:#111111;"
                onmouseover="this.style.background='#e09415';"
                onmouseout="this.style.background='#f5a623';">
            RESET PASSWORD
        </button>
    </form>

</x-guest-layout>
