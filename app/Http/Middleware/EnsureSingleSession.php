<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    /**
     * Handle an incoming request.
     *
     * Enforces single active session per user in production only.
     * If the user's active_session_id does not match the current session id
     * and the stored session is still valid, the current request is logged out.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldEnforce()) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || $user->active_session_id === null) {
            return $next($request);
        }

        $currentSessionId = $request->session()->getId();

        if ($user->active_session_id === $currentSessionId) {
            return $next($request);
        }

        // If stored session is stale (expired), clear it and allow request.
        if (! $user->hasActiveSession()) {
            $user->forceFill([
                'active_session_id' => $currentSessionId,
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            return $next($request);
        }

        // Stored session is still valid but differs from current -> concurrent login.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'This account is already logged in on another device.',
            ], 409);
        }

        // Inertia / web redirect with error.
        if ($request->header('X-Inertia')) {
            return redirect()->route('login')->withErrors([
                'email' => 'This account is already logged in on another device. Please log out from the other device or contact admin.',
            ]);
        }

        return redirect()->route('login')->withErrors([
            'email' => 'This account is already logged in on another device. Please log out from the other device or contact admin.',
        ]);
    }

    private function shouldEnforce(): bool
    {
        // Enabled only in production, or when explicitly enabled via env.
        if (config('session.single_session.enabled') !== null) {
            return (bool) config('session.single_session.enabled');
        }

        return app()->environment('production');
    }
}
