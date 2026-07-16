<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $codeLifetimeMinutes = 15;

    public function create()
    {
        return Inertia::render('Auth/ForgotPasswordView');
    }

    // POST /forgot-password
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // Invalidate any previous unused codes for this email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($this->codeLifetimeMinutes),
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($request->email)->send(new PasswordResetMail($code));

        $request->session()->put('password_reset_email', $request->email);

        return redirect()->route('password.verify.show');
    }

    // GET /verify-reset-code
    public function checkVerifyResetCode(Request $request)
    {
        $email = $request->session()->get('password_reset_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/VerifyResetCodeView', [
            'email' => $email,
        ]);
    }

    // POST /verify-reset-code
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $email = $request->session()->get('password_reset_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => 'No active reset code found. Please request a new one.',
            ]);
        }

        if (now()->greaterThan($record->expires_at)) {
            throw ValidationException::withMessages([
                'code' => 'This code has expired. Please request a new one.',
            ]);
        }

        if ($record->attempts >= $this->maxAttempts) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please request a new code.',
            ]);
        }

        if (!Hash::check($request->code, $record->token)) {
            DB::table('password_reset_tokens')
                ->where('id', $record->id)
                ->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'The provided code is invalid.',
            ]);
        }

        // Mark this specific token row as verified (still not "used" until password is actually reset)
        $request->session()->put('password_reset_verified', true);
        $request->session()->put('password_reset_token_id', $record->id);

        return redirect()->route('password.reset.show');
    }

    // GET /reset-password
    public function checkResetPassword(Request $request)
    {
        $email = $request->session()->get('password_reset_email');
        $verified = $request->session()->get('password_reset_verified');

        if (!$email || !$verified) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ResetPasswordView', [
            'email' => $email,
        ]);
    }

    // POST /reset-password
    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $email = $request->session()->get('password_reset_email');
        $verified = $request->session()->get('password_reset_verified');
        $tokenId = $request->session()->get('password_reset_token_id');

        if (!$email || !$verified || !$tokenId) {
            return redirect()->route('password.request');
        }

        // Re-check the token row is still valid (not expired/reused since verification)
        $record = DB::table('password_reset_tokens')
            ->where('id', $tokenId)
            ->where('email', $email)
            ->whereNull('used_at')
            ->first();

        if (!$record || now()->greaterThan($record->expires_at)) {
            $request->session()->forget([
                'password_reset_email',
                'password_reset_verified',
                'password_reset_token_id',
            ]);

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Your session expired. Please start over.']);
        }

        $user = User::where('email', $email)->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        DB::table('password_reset_tokens')
            ->where('id', $tokenId)
            ->update(['used_at' => now()]);

        $request->session()->forget([
            'password_reset_email',
            'password_reset_verified',
            'password_reset_token_id',
        ]);

        return redirect()->route('login')->with('status', 'Password reset successfully. Please sign in.');
    }
}