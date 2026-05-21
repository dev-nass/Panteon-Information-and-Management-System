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
    public function create(string $token)
    {
        $invitation_token = ClerkInvitation::firstOrFail(['token' => $token]);

        abort_if(!$invitation_token->isValid(), 410, 'This invitation link has expired or already been used.');

        return Inertia::render('Auth/RegistrationView');
    }

    public function store(RegistrationRequest $request, string $token)
    {
        $invitation = ClerkInvitation::where('token', $token)->firstOrFail();

        abort_if(!$invitation->isValid(), 410, 'This invitation link has expired or already been used.');

        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'contact_number' => $validated['contact_number'],
            'email' => strtolower($validated['email']),
            'password' => bcrypt($validated['password']),
            'role' => 'clerk',
        ]);

        $invitation->update(['used_at' => now()]);

        return to_route('login')->with('success', 'Registration complete! You can now log in.');
    }
}
