<x-guest-layout>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        {{-- Full Name --}}
        <div>
            <label for="full_name" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Full Name</label>
            <input id="full_name" name="full_name" type="text"
                   value="{{ old('full_name') }}"
                   required autofocus autocomplete="name"
                   class="block w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-all"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('full_name')
                <p class="mt-1.5 text-xs" style="color:#f87171;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Email</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   required autocomplete="username"
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
            <label for="password_confirmation" class="block text-sm font-medium mb-1.5" style="color:#9ca3af;">Confirm Password</label>
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
            CREATE ACCOUNT
        </button>

        <p class="text-center text-sm" style="color:#9ca3af;">
            Already have an account?
            <a href="{{ route('login') }}"
               style="color:#f5a623;"
               onmouseover="this.style.color='#e09415';"
               onmouseout="this.style.color='#f5a623';">Sign in</a>
        </p>
    </form>

</x-guest-layout>
