<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Models\ClerkInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ClerkRegistrationController extends Controller
{
    public function create(Request $request)
    {
        $token = $request->route('token');
        $invitation = ClerkInvitation::where('token', $token)->firstOrFail();

        abort_if(!$invitation->isValid(), 410, 'This invitation link has expired or already been used.');

        return Inertia::render('Auth/RegistrationView', [
            'token' => $token,
            'email' => $invitation->email,
        ]);
    }

    public function store(RegistrationRequest $request)
    {
        $token = $request->input('token');
        $invitation = ClerkInvitation::where('token', $token)->firstOrFail();

        abort_if(!$invitation->isValid(), 410, 'This invitation link has expired or already been used.');

        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'contact_number' => $validated['contact_number'],
            'email' => strtolower($validated['email']),
            'email_verified_at' => now(),
            'password' => bcrypt($validated['password']),
            'role' => 'clerk',
        ]);

        $invitation->update(['used_at' => now()]);

        return to_route('login')->with('success', 'Registration complete! You can now log in.');
    }
}
