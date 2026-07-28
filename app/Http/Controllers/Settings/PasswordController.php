<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/ChangePasswordView');
    }

    public function update(ChangePasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Password changed successfully. Please log in again.');
    }
}
