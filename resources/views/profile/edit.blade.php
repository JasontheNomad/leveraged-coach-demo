<x-app-layout>
    <x-slot name="header">Profile</x-slot>

    <div class="p-8 space-y-6 max-w-2xl">

        @if(config('app.demo_user_email'))
        <div class="rounded-xl p-6 text-sm" style="background:#1a1a1a; border:1px solid #2a2a2a; color:#9ca3af;">
            Profile editing is disabled in this demo.
        </div>
        @else

        <div class="rounded-xl p-6" style="background:#1a1a1a; border:1px solid #2a2a2a;">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-xl p-6" style="background:#1a1a1a; border:1px solid #2a2a2a;">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-xl p-6" style="background:#1a1a1a; border:1px solid #2a2a2a;">
            @include('profile.partials.delete-user-form')
        </div>
        @endif

    </div>
</x-app-layout>
