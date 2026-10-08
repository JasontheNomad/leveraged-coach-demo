<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public demo only: when DEMO_USER_EMAIL is set, every visitor is
 * logged in as that member so they can explore without signing up.
 * Visitors share that account, so chat and profile writes are blocked.
 */
class DemoAutoLogin
{
    private const BLOCKED_ROUTES = [
        'profile.update',
        'profile.destroy',
        'profile.avatar.update',
        'password.update',
        'messages.store',
        'messages.send',
        'messages.react',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $email = config('app.demo_user_email');

        if (! $email) {
            return $next($request);
        }

        if (! auth()->check()) {
            $user = User::where('email', $email)->first();

            if ($user) {
                auth()->login($user);
            }
        }

        abort_if($request->routeIs(...self::BLOCKED_ROUTES), 403, 'Disabled in the demo.');

        return $next($request);
    }
}
