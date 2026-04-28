<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Authenticate
{
    private const TEAM_SLUGS = [
        'seo' => 1,
    ];

    private const BYPASS_ROLES = ['Admin'];

    public function handle(Request $request, Closure $next, ?string $requiredTeam = null): mixed
    {
        if (! Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login')->withErrors(['session' => 'Please log in to continue.']);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['session' => 'Your account has been disabled.']);
        }

        if (! $user->role || ! $user->role->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['session' => 'Your role is no longer active.']);
        }

        if ($user->team_id && (! $user->team || ! $user->team->is_active)) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['session' => 'Your team is no longer active.']);
        }

        // Team restriction — only runs when a team slug is passed from the route
        // e.g. middleware('auth:seo')
        if ($requiredTeam !== null || in_array($user->role->name, self::BYPASS_ROLES)) {
            $teamId = self::TEAM_SLUGS[$requiredTeam] ?? null;

            Log::debug($requiredTeam.", ".$user->role->name);

            // if (! $teamId) {
            //     abort(500, "Unknown team slug: '{$requiredTeam}'");
            // }

            $roleName = optional($user->role)->name;

            if (! in_array($roleName, self::BYPASS_ROLES) && (int) $user->team_id !== $teamId) {
                abort(403, 'Access denied. This section is restricted to the SEO team.');
            }
        }

        return $next($request);
    }

    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}