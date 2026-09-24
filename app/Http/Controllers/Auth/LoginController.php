<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create(Request $request)
    {
        if (Auth::check()) {
            $user = $request->user() ?? Auth::user();

            if ($user && $user->terminated_at !== null) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return Inertia::render('Auth/LoginView');
            }

            if ($user && $user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user && $user->role === 'clerk') {
                return redirect()->route('clerk.dashboard');
            }

            return redirect()->route('visitor.index');
        }

        return Inertia::render('Auth/LoginView');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        /** @var User|null $targetUser */
        $targetUser = User::where('email', $credentials['email'])->first();

        // Production-only single session check BEFORE attempting auth.
        if ($targetUser !== null && $this->shouldEnforceSingleSession()) {
            // Only check if password is actually correct to avoid leaking existence on wrong password.
            $passwordValid = Hash::check($credentials['password'], $targetUser->password);

            if ($passwordValid && $targetUser->hasActiveSession()) {
                return back()->withErrors([
                    'email' => 'This account is already logged in on another device. Please log out from the other device or contact admin.',
                ]);
            }
        }

        if (Auth::attempt($credentials)) {
            if (Auth::user()->terminated_at !== null) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Account terminated. Contact admin.',
                ]);
            }

            $request->session()->regenerate();

            // Track active session (production only, but safe to store always).
            $request->user()->forceFill([
                'active_session_id' => $request->session()->getId(),
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            if (Auth::user()->role === 'admin') {
                return to_route('admin.dashboard');
            }

            if (Auth::user()->role === 'clerk') {
                return to_route('clerk.dashboard');
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function destroy(Request $request)
    {
        if (Auth::check()) {
            $user = $request->user();
            // Clear only if this session is the active one.
            if ($user && $user->active_session_id === $request->session()->getId()) {
                $user->forceFill([
                    'active_session_id' => null,
                    'last_login_at' => null,
                    'last_login_ip' => null,
                ])->save();
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('visitor.index');
    }

    private function shouldEnforceSingleSession(): bool
    {
        if (config('session.single_session.enabled') !== null) {
            return (bool) config('session.single_session.enabled');
        }

        return app()->environment('production');
    }
}
