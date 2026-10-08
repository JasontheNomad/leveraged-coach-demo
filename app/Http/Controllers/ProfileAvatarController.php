<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileAvatarController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar from R2 if it exists
        if ($user->avatar_url) {
            $r2Base = rtrim(config('filesystems.disks.r2.url'), '/');
            if (str_starts_with($user->avatar_url, $r2Base)) {
                $oldPath = ltrim(str_replace($r2Base, '', $user->avatar_url), '/');
                Storage::disk('r2')->delete($oldPath);
            }
        }

        // Store new avatar
        $ext      = $request->file('avatar')->getClientOriginalExtension();
        $filename = 'avatars/' . Str::uuid() . '.' . strtolower($ext);

        Storage::disk('r2')->putFileAs(
            'avatars',
            $request->file('avatar'),
            basename($filename),
            'public'
        );

        $publicUrl = rtrim(config('filesystems.disks.r2.url'), '/') . '/' . $filename;

        $user->update(['avatar_url' => $publicUrl]);

        return back()->with('avatar-updated', 'Avatar updated.');
    }
}
