<section>
    <h2 class="text-lg font-semibold text-white">Profile Information</h2>
    <p class="mt-1 text-sm" style="color:#9ca3af;">Update your account's profile information and email address.</p>

    {{-- ── Avatar upload ── --}}
    <div class="mt-6 flex items-center gap-5">

        {{-- Current avatar --}}
        @if(auth()->user()->avatar_url)
            <img src="{{ auth()->user()->avatar_url }}"
                 alt="Avatar"
                 class="w-20 h-20 rounded-full object-cover flex-shrink-0"
                 style="border:2px solid #2a2a2a;">
        @else
            <div class="w-20 h-20 rounded-full flex items-center justify-center flex-shrink-0 text-xl font-bold"
                 style="background:#1f1f1f; border:2px solid #2a2a2a; color:#f5a623;">
                {{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}
            </div>
        @endif

        {{-- Upload form --}}
        <form method="post"
              action="{{ route('profile.avatar.update') }}"
              enctype="multipart/form-data"
              id="avatar-form">
            @csrf
            <input type="file"
                   id="avatar-input"
                   name="avatar"
                   accept="image/jpeg,image/png,image/webp"
                   class="hidden"
                   onchange="document.getElementById('avatar-form').submit();">

            <div class="flex flex-col gap-1.5">
                <button type="button"
                        onclick="document.getElementById('avatar-input').click();"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition-all"
                        style="background:#2a2a2a; color:#ffffff;"
                        onmouseover="this.style.background='#3a3a3a';"
                        onmouseout="this.style.background='#2a2a2a';">
                    Change avatar
                </button>
                <span class="text-xs" style="color:#6b7280;">JPG, PNG or WebP · max 2 MB</span>
            </div>
        </form>

        @if (session('avatar-updated'))
            <p class="text-sm" style="color:#16a34a;">{{ session('avatar-updated') }}</p>
        @endif

        @error('avatar')
            <p class="text-sm" style="color:#ef4444;">{{ $message }}</p>
        @enderror
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        {{-- Name --}}
        <div>
            <label for="full_name" class="block text-sm font-medium mb-1" style="color:#9ca3af;">Name</label>
            <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $user->full_name) }}"
                   required autofocus autocomplete="name"
                   class="w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-colors"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('full_name')
                <p class="mt-1 text-xs" style="color:#ef4444;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium mb-1" style="color:#9ca3af;">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                   required autocomplete="username"
                   class="w-full rounded-lg px-4 py-2.5 text-sm text-white outline-none transition-colors"
                   style="background:#111111; border:1px solid #2a2a2a;"
                   onfocus="this.style.borderColor='#f5a623';"
                   onblur="this.style.borderColor='#2a2a2a';">
            @error('email')
                <p class="mt-1 text-xs" style="color:#ef4444;">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm" style="color:#9ca3af;">
                        Your email address is unverified.
                        <button form="send-verification"
                                class="underline transition-colors"
                                style="color:#f5a623;"
                                onmouseover="this.style.color='#e09415';"
                                onmouseout="this.style.color='#f5a623';">
                            Click here to re-send the verification email.
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 text-sm font-medium" style="color:#16a34a;">
                            A new verification link has been sent to your email address.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Save --}}
        <div class="flex items-center gap-4 pt-1">
            <button type="submit"
                    class="px-5 py-2.5 text-sm font-semibold rounded-lg transition-all"
                    style="background:#f5a623; color:#111111;"
                    onmouseover="this.style.background='#e09415';"
                    onmouseout="this.style.background='#f5a623';">
                Save Changes
            </button>

            @if (session('status') === 'profile-updated')
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
